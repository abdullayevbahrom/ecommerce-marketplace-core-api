import java.io.*;
import java.security.*;
import java.security.cert.*;
import java.util.*;
import org.bouncycastle.cms.*;
import org.bouncycastle.cms.jcajce.*;
import org.bouncycastle.cert.jcajce.*;
import org.bouncycastle.jce.provider.*;
import org.bouncycastle.operator.*;
import org.bouncycastle.operator.jcajce.*;
import org.bouncycastle.util.encoders.Base64;
import org.bouncycastle.jcajce.provider.config.ConfigurableProvider;

public class SignChallenge {
    public static void main(String[] args) throws Exception {
        if (args.length < 3) {
            System.err.println("Usage: SignChallenge <pfxPath> <password> <challenge>");
            System.exit(1);
        }
        
        String pfxPath = args[0];
        String password = args[1];
        String challenge = args[2];
        
        // Register BouncyCastle and configure with UzDST GOST algorithms
        BouncyCastleProvider bcProvider = new BouncyCastleProvider();
        Security.insertProviderAt(bcProvider, 1);
        
        // Configure BC with Uzbekistan GOST algorithms
        uz.yt.pkix.jcajce.provider.YTProvider.configure((ConfigurableProvider) bcProvider);
        System.err.println("Configured BC with YTProvider GOST algorithms");
        
        // Load PFX
        KeyStore ks = KeyStore.getInstance("PKCS12", bcProvider);
        ks.load(new FileInputStream(pfxPath), password.toCharArray());
        
        String alias = ks.aliases().nextElement();
        System.err.println("Alias: " + alias);
        
        PrivateKey privateKey = (PrivateKey) ks.getKey(alias, password.toCharArray());
        X509Certificate cert = (X509Certificate) ks.getCertificate(alias);
        
        System.err.println("Subject: " + cert.getSubjectDN());
        System.err.println("Key algo: " + privateKey.getAlgorithm());
        System.err.println("Sig algo: " + cert.getSigAlgName());
        
        // Sign the challenge
        byte[] data = challenge.getBytes("UTF-8");
        CMSTypedData cmsData = new CMSProcessableByteArray(data);
        
        ContentSigner signer = new JcaContentSignerBuilder(cert.getSigAlgName())
            .setProvider(bcProvider)
            .build(privateKey);
        
        CMSSignedDataGenerator gen = new CMSSignedDataGenerator();
        gen.addSignerInfoGenerator(
            new JcaSignerInfoGeneratorBuilder(
                new JcaDigestCalculatorProviderBuilder().setProvider(bcProvider).build()
            ).build(signer, cert)
        );
        
        List<X509Certificate> certList = new ArrayList<>();
        certList.add(cert);
        gen.addCertificates(new JcaCertStore(certList));
        
        CMSSignedData signedData = gen.generate(cmsData, true);
        byte[] pkcs7 = signedData.getEncoded();
        
        String b64 = new String(Base64.encode(pkcs7));
        System.out.println(b64);
        System.err.println("OK: PKCS#7 " + pkcs7.length + " bytes");
    }
}
