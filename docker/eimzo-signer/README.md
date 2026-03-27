## E-IMZO Signer

Simple Java HTTP service for server-side PKCS#7 signing from a `.pfx` certificate.

### Endpoints

- `GET /health`
- `POST /generate`

### Request example

```json
{
  "pfxFilePath": "company.pfx",
  "password": "secret",
  "alias": "",
  "data": "123456789",
  "attached": true
}
```

### Response example

```json
{
  "success": true,
  "pkcs7": "<base64>",
  "pkcs7b64": "<base64>",
  "signature": "<hex>",
  "signatureHex": "<hex>",
  "signerSerialNumber": "<hex>",
  "attached": true
}
```

### Docker URL

Inside Docker network, use:

```text
http://eimzo-signer:8080/generate
```

### Dev

Build and run only the signer:

```bash
docker compose -f docker-compose.yml build eimzo-signer
docker compose -f docker-compose.yml up -d eimzo-signer
docker compose -f docker-compose.yml logs -f eimzo-signer
```

Health check:

```bash
docker exec eimzo-signer sh -lc 'wget -qO- http://127.0.0.1:8080/health'
```

### Prod

Build and run only the signer:

```bash
docker compose -f docker-compose-prod.yml build eimzo-signer
docker compose -f docker-compose-prod.yml up -d eimzo-signer
docker compose -f docker-compose-prod.yml logs -f eimzo-signer
```

### Real request example

If your app stores `.pfx` files in `keys`, the signer reads them via `/opt/eimzo/keys/...`:

```bash
curl -X POST http://127.0.0.1:8080/generate \
  -H 'Content-Type: application/json' \
  -d '{
    "pfxFilePath": "company.pfx",
    "password": "12345678",
    "alias": "",
    "data": "123456789",
    "attached": true
  }'
```

### Shop settings

These values should be aligned in `settings`:

```text
didox_signer_url = http://eimzo-signer:8080/generate
didox_pfx_path   = <your-file>.pfx
didox_pfx_password = <your-password>
```

The app may store only the file name in the database. The signer resolves these inputs to `/opt/eimzo/keys/<your-file>.pfx` inside the container:

- `<your-file>.pfx`
- `keys/<your-file>.pfx`
- `/var/www/html/keys/<your-file>.pfx`

Only the `keys` directory is mounted into the signer container:

```text
./keys -> /opt/eimzo/keys (read-only)
```

Example SQL:

```sql
UPDATE settings
SET content = 'http://eimzo-signer:8080/generate'
WHERE type = 'didox_signer_url';
```
