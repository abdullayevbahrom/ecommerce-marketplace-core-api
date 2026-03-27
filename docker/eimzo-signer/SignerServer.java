import com.google.gson.Gson;
import com.google.gson.JsonParseException;
import com.sun.net.httpserver.Headers;
import com.sun.net.httpserver.HttpExchange;
import com.sun.net.httpserver.HttpHandler;
import com.sun.net.httpserver.HttpServer;
import java.io.ByteArrayOutputStream;
import java.io.FileInputStream;
import java.io.IOException;
import java.io.InputStream;
import java.io.OutputStream;
import java.net.InetSocketAddress;
import java.nio.charset.StandardCharsets;
import java.security.cert.Certificate;
import java.security.KeyStore;
import java.security.PrivateKey;
import java.security.Security;
import java.security.cert.X509Certificate;
import java.util.ArrayList;
import java.util.Collection;
import java.util.LinkedHashMap;
import java.util.List;
import java.util.Map;
import java.util.concurrent.Executors;
import org.bouncycastle.cert.jcajce.JcaCertStore;
import org.bouncycastle.cms.CMSProcessableByteArray;
import org.bouncycastle.cms.CMSSignedData;
import org.bouncycastle.cms.CMSSignedDataGenerator;
import org.bouncycastle.cms.CMSTypedData;
import org.bouncycastle.cms.SignerInformation;
import org.bouncycastle.cms.jcajce.JcaSignerInfoGeneratorBuilder;
import org.bouncycastle.jcajce.provider.config.ConfigurableProvider;
import org.bouncycastle.jce.provider.BouncyCastleProvider;
import org.bouncycastle.operator.ContentSigner;
import org.bouncycastle.operator.jcajce.JcaContentSignerBuilder;
import org.bouncycastle.operator.jcajce.JcaDigestCalculatorProviderBuilder;
import org.bouncycastle.util.encoders.Base64;
import org.bouncycastle.util.encoders.Hex;

public class SignerServer {
    private static final Gson GSON = new Gson();

    public static void main(String[] args) throws Exception {
        int port = args.length > 0 ? Integer.parseInt(args[0]) : 8080;

        BouncyCastleProvider bcProvider = new BouncyCastleProvider();
        Security.insertProviderAt(bcProvider, 1);
        uz.yt.pkix.jcajce.provider.YTProvider.configure((ConfigurableProvider) bcProvider);

        HttpServer server = HttpServer.create(new InetSocketAddress("0.0.0.0", port), 0);
        server.createContext("/generate", new GenerateHandler(bcProvider));
        server.createContext("/health", new HealthHandler());
        server.setExecutor(Executors.newCachedThreadPool());
        server.start();

        System.out.println("SignerServer listening on :" + port);
    }

    static class GenerateHandler implements HttpHandler {
        private final BouncyCastleProvider provider;

        GenerateHandler(BouncyCastleProvider provider) {
            this.provider = provider;
        }

        @Override
        public void handle(HttpExchange exchange) throws IOException {
            try {
                if (!"POST".equalsIgnoreCase(exchange.getRequestMethod())) {
                    sendJson(exchange, 405, error("Only POST is allowed"));
                    return;
                }

                GenerateRequest request = GSON.fromJson(readBody(exchange.getRequestBody()), GenerateRequest.class);
                if (request == null) {
                    sendJson(exchange, 400, error("Request body is required"));
                    return;
                }

                String pfxFilePath = normalizePfxFilePath(safeTrim(request.pfxFilePath));
                String password = request.password == null ? "" : request.password;
                String data = request.data == null ? "" : request.data;
                String alias = safeTrim(request.alias);
                boolean attached = request.attached == null || request.attached.booleanValue();
                boolean dataBase64 = request.dataBase64 != null && request.dataBase64.booleanValue();

                if (pfxFilePath.isEmpty()) {
                    sendJson(exchange, 400, error("pfxFilePath is required"));
                    return;
                }

                if (password.isEmpty()) {
                    sendJson(exchange, 400, error("password is required"));
                    return;
                }

                SignResult result = sign(pfxFilePath, password, alias, data, attached, dataBase64, provider);
                Map<String, Object> response = new LinkedHashMap<String, Object>();
                response.put("success", true);
                response.put("pkcs7", result.pkcs7Base64);
                response.put("pkcs7b64", result.pkcs7Base64);
                response.put("signature", result.signatureHex);
                response.put("signatureHex", result.signatureHex);
                response.put("signerSerialNumber", result.signerSerialNumber);
                response.put("attached", attached);
                sendJson(exchange, 200, response);
            } catch (JsonParseException e) {
                sendJson(exchange, 400, error("Invalid JSON: " + e.getMessage()));
            } catch (Exception e) {
                sendJson(exchange, 500, error(e.getMessage()));
            }
        }
    }

    static class HealthHandler implements HttpHandler {
        @Override
        public void handle(HttpExchange exchange) throws IOException {
            Map<String, Object> response = new LinkedHashMap<String, Object>();
            response.put("success", true);
            response.put("status", "ok");
            sendJson(exchange, 200, response);
        }
    }

    private static SignResult sign(
        String pfxFilePath,
        String password,
        String alias,
        String data,
        boolean attached,
        boolean dataBase64,
        BouncyCastleProvider provider
    ) throws Exception {
        KeyStore keyStore = KeyStore.getInstance("PKCS12", provider);
        FileInputStream inputStream = new FileInputStream(pfxFilePath);
        try {
            keyStore.load(inputStream, password.toCharArray());
        } finally {
            inputStream.close();
        }

        String resolvedAlias = alias.isEmpty() ? firstPrivateKeyAlias(keyStore, password) : alias;
        if (!keyStore.containsAlias(resolvedAlias)) {
            throw new IllegalArgumentException("Alias not found: " + resolvedAlias);
        }

        PrivateKey privateKey = (PrivateKey) keyStore.getKey(resolvedAlias, password.toCharArray());
        if (privateKey == null) {
            throw new IllegalArgumentException("Private key not found for alias: " + resolvedAlias);
        }

        X509Certificate cert = (X509Certificate) keyStore.getCertificate(resolvedAlias);
        if (cert == null) {
            throw new IllegalArgumentException("Certificate not found for alias: " + resolvedAlias);
        }

        byte[] rawData = dataBase64 ? Base64.decode(data) : data.getBytes(StandardCharsets.UTF_8);
        CMSTypedData cmsData = new CMSProcessableByteArray(rawData);

        ContentSigner signer = new JcaContentSignerBuilder(cert.getSigAlgName())
            .setProvider(provider)
            .build(privateKey);

        CMSSignedDataGenerator generator = new CMSSignedDataGenerator();
        generator.addSignerInfoGenerator(
            new JcaSignerInfoGeneratorBuilder(
                new JcaDigestCalculatorProviderBuilder().setProvider(provider).build()
            ).build(signer, cert)
        );

        List<X509Certificate> certList = new ArrayList<X509Certificate>();
        Certificate[] certificateChain = keyStore.getCertificateChain(resolvedAlias);
        if (certificateChain != null && certificateChain.length > 0) {
            for (Certificate chainCertificate : certificateChain) {
                if (chainCertificate instanceof X509Certificate) {
                    certList.add((X509Certificate) chainCertificate);
                }
            }
        }
        if (certList.isEmpty()) {
            certList.add(cert);
        }
        generator.addCertificates(new JcaCertStore(certList));

        CMSSignedData signedData = generator.generate(cmsData, attached);
        byte[] pkcs7 = signedData.getEncoded();

        Collection<SignerInformation> signers = signedData.getSignerInfos().getSigners();
        if (signers.isEmpty()) {
            throw new IllegalStateException("Signer info not found in generated PKCS7");
        }

        SignerInformation signerInfo = signers.iterator().next();
        String signatureHex = Hex.toHexString(signerInfo.getSignature());

        SignResult result = new SignResult();
        result.pkcs7Base64 = new String(Base64.encode(pkcs7), StandardCharsets.UTF_8);
        result.signatureHex = signatureHex;
        result.signerSerialNumber = cert.getSerialNumber().toString(16);
        return result;
    }

    private static String firstPrivateKeyAlias(KeyStore keyStore, String password) throws Exception {
        java.util.Enumeration<String> aliases = keyStore.aliases();
        if (!aliases.hasMoreElements()) {
            throw new IllegalArgumentException("No aliases found in PFX");
        }
        while (aliases.hasMoreElements()) {
            String currentAlias = aliases.nextElement();
            try {
                if (keyStore.isKeyEntry(currentAlias) && keyStore.getKey(currentAlias, password.toCharArray()) != null) {
                    return currentAlias;
                }
            } catch (Exception ignored) {
            }
        }
        throw new IllegalArgumentException("No private key entry found in PFX");
    }

    private static String readBody(InputStream inputStream) throws IOException {
        ByteArrayOutputStream outputStream = new ByteArrayOutputStream();
        byte[] buffer = new byte[4096];
        int read;
        while ((read = inputStream.read(buffer)) != -1) {
            outputStream.write(buffer, 0, read);
        }
        return new String(outputStream.toByteArray(), StandardCharsets.UTF_8);
    }

    private static Map<String, Object> error(String message) {
        Map<String, Object> response = new LinkedHashMap<String, Object>();
        response.put("success", false);
        response.put("message", message);
        return response;
    }

    private static void sendJson(HttpExchange exchange, int statusCode, Map<String, Object> payload) throws IOException {
        byte[] body = GSON.toJson(payload).getBytes(StandardCharsets.UTF_8);
        Headers headers = exchange.getResponseHeaders();
        headers.set("Content-Type", "application/json; charset=utf-8");
        exchange.sendResponseHeaders(statusCode, body.length);
        OutputStream outputStream = exchange.getResponseBody();
        try {
            outputStream.write(body);
        } finally {
            outputStream.close();
        }
    }

    private static String safeTrim(String value) {
        return value == null ? "" : value.trim();
    }

    private static String normalizePfxFilePath(String path) {
        if (path.isEmpty()) {
            return path;
        }

        String normalized = path.replace('\\', '/');

        if (normalized.startsWith("/opt/eimzo/keys/")) {
            return normalized;
        }

        if (normalized.startsWith("/var/www/html/keys/")) {
            return "/opt/eimzo/keys/" + normalized.substring("/var/www/html/keys/".length());
        }

        if (normalized.startsWith("keys/")) {
            return "/opt/eimzo/keys/" + normalized.substring("keys/".length());
        }

        if (!normalized.contains("/")) {
            return "/opt/eimzo/keys/" + normalized;
        }

        return normalized;
    }

    static class GenerateRequest {
        String pfxFilePath;
        String password;
        String alias;
        String data;
        Boolean attached;
        Boolean dataBase64;
    }

    static class SignResult {
        String pkcs7Base64;
        String signatureHex;
        String signerSerialNumber;
    }
}
