# E-IMZO Mobile Integration Guide

## Overview

app marketplace supports authentication via E-IMZO digital signature.
Two flows are available:

1. **Desktop flow** — user signs with E-IMZO desktop app (WebSocket `wss://127.0.0.1:64443`)
2. **Mobile flow** — user signs with E-IMZO mobile app via deeplink

After E-IMZO authentication (Step 1), users can optionally connect Didox (Step 2) to enrich their profile with business data.

## Authentication Flow

```
Step 1: E-IMZO Login (required)
├── Desktop: POST /api/eimzo/challenge → sign → POST /api/eimzo/auth
└── Mobile: POST /api/eimzo/mobile/auth → deeplink → poll → POST /api/eimzo/mobile/auth-result
    → Returns: { token, user, certificate }
    → User can access basic features

Step 2: Didox Connect (optional)
└── POST /api/user/eimzo-auth { didox_token, tax_id }
    → Enriches profile with Didox business data (address, bank, OKED, etc.)
```

---

## Mobile Flow (Deeplink)

### Step 1: Initiate Authentication

```
POST /api/eimzo/mobile/auth
Content-Type: application/json

(empty body)
```

**Response:**
```json
{
  "success": true,
  "data": {
    "siteId": "3383",
    "documentId": "B7735734",
    "challenge": "AB8C2ED52B12DCBAB3FBD8C11007E4C0C7BF6A2F5818C05DEB61F3EE39052BDC",
    "pollInterval": 5,
    "timeout": 120
  }
}
```

### Step 2: Hash the Challenge

Calculate the hash of the `challenge` string using **OzDSt1106Digest** algorithm:

```java
// Android (Java)
OzDSt1106Digest digest = new OzDSt1106Digest();
byte[] challengeBytes = challenge.getBytes(StandardCharsets.UTF_8);
digest.update(challengeBytes, 0, challengeBytes.length);
byte[] hash = new byte[digest.getDigestSize()];
digest.doFinal(hash, 0);
String hashHex = Hex.encode(hash);
```

> **Note:** The OzDSt1106Digest implementation is available in the
> [SampleAndroidAppCallDeeplinkEIMZO](https://github.com/qo0p/SampleAndroidAppCallDeeplinkEIMZO)
> repository (`OzDSt1106Digest.java`, `GOST28147Engine.java`, `Hex.java`).

### Step 3: Build QR Code String

```java
String combined = siteId + documentId + hashHex;
byte[] combinedBytes = Hex.decode(combined);

// Calculate CRC32
CRC32 crc = new CRC32();
crc.update(combinedBytes);
String crc32Hex = String.format("%08X", crc.getValue());

String qrCode = combined + crc32Hex;
```

### Step 4: Open E-IMZO Deeplink

```java
String deeplink = "eimzo://sign?qc=" + qrCode;
Intent intent = new Intent(Intent.ACTION_VIEW, Uri.parse(deeplink));
startActivity(intent);
```

For **iOS**:
```swift
let deeplink = "eimzo://sign?qc=\(qrCode)"
if let url = URL(string: deeplink) {
    UIApplication.shared.open(url)
}
```

The E-IMZO mobile app will:
1. Open and display the signing dialog
2. User selects their key and enters password
3. App signs the document and uploads PKCS#7 to the E-IMZO service
4. E-IMZO service delivers the signed PKCS#7 to our server

### Step 5: Poll Status

After opening the deeplink, poll for completion:

```
POST /api/eimzo/mobile/status
Content-Type: application/json

{ "documentId": "B7735734" }
```

**Response (pending):**
```json
{ "success": true, "data": { "status": 2, "complete": false } }
```

**Response (complete):**
```json
{ "success": true, "data": { "status": 1, "complete": true } }
```

**Poll every 5 seconds, timeout after 120 seconds (24 attempts).**

### Step 6: Get Authentication Result

Once status is `complete`, retrieve the result and log in:

```
POST /api/eimzo/mobile/auth-result
Content-Type: application/json

{
  "documentId": "B7735734",
  "user_type": "fiz"
}
```

`user_type` is `"fiz"` (individual) or `"yur"` (legal entity).

**Response:**
```json
{
  "success": true,
  "data": {
    "token": "abc123...bearer_token",
    "user": {
      "id": 42,
      "name": "IVAN",
      "lastname": "IVANOV",
      "eimzo_tax_id": "30000000000000",
      "type": "fiz",
      "eimzo_auth_completed": true,
      "didox_auth_completed": false
    },
    "certificate": {
      "serialNumber": "7700000",
      "subjectName": {
        "CN": "IVANOV IVAN IVANOVICH",
        "1.2.860.3.16.1.2": "30000000000000"
      },
      "validFrom": "2024-01-01 00:00:00",
      "validTo": "2026-01-01 23:59:59"
    }
  }
}
```

Use the `token` as `Authorization: Bearer <token>` for subsequent API calls.

---

## Desktop Flow

### Step 1: Get Challenge

```
POST /api/eimzo/challenge
```

### Step 2: Sign Challenge

Using E-IMZO JavaScript library (`e-imzo.js` + `e-imzo-client.js`):

```javascript
// 1. List keys
EIMZOClient.listAllUserKeys(function(o, i) {
    // Display key list to user
}, function(e, r) {});

// 2. Load selected key
EIMZOClient.loadKey(selectedKey, function(keyId) {
    // 3. Sign the challenge
    EIMZOClient.createPkcs7(keyId, btoa(challenge), function(pkcs7) {
        // pkcs7 is base64-encoded PKCS#7
        // Send to timestamp endpoint
    });
});
```

### Step 3: Attach Timestamp

```
POST /api/eimzo/timestamp
Content-Type: application/json

{ "pkcs7b64": "<base64 PKCS#7 from E-IMZO>" }
```

### Step 4: Authenticate

```
POST /api/eimzo/auth
Content-Type: application/json

{
  "pkcs7b64": "<timestamped PKCS#7>",
  "user_type": "fiz"
}
```

Returns same response format as mobile auth-result.

---

## Document Signing (Mobile)

For signing documents (not authentication), use the signing flow:

### Initiate Signing
```
POST /api/eimzo/mobile/sign
Authorization: Bearer <token>
```

### Hash Document
Hash the document content (not the challenge) using OzDSt1106Digest, then build QR code and open deeplink as above.

### Verify Signed Document
```
POST /api/eimzo/mobile/verify
Authorization: Bearer <token>
Content-Type: application/json

{
  "documentId": "850FF727",
  "document": "<base64 encoded original document>"
}
```

---

## Step 2: Connect Didox (Optional)

After E-IMZO login, the user can connect their Didox account:

```
POST /api/user/eimzo-auth
Authorization: Bearer <token>
Content-Type: application/json

{
  "didox_token": "<token from Didox login>",
  "tax_id": "30000000000000",
  "user_type": "fiz"
}
```

This enriches the user profile with:
- Business address, OKED, bank details
- Organization name (for yur)
- Email, phone from Didox profile

---

## Error Codes

| Code | HTTP | Description |
|------|------|-------------|
| -40 | 502 | E-IMZO server unreachable |
| -41 | 422 | PKCS#7 data required |
| -42 | 502 | Timestamp attachment failed |
| -43 | 401 | Authentication failed |
| -44 | 400 | INN not found in certificate |
| -45 | 500 | User creation failed |
| -49 | 502 | Mobile init failed |
| -51 | 410 | Mobile session expired |
| -52 | 422 | DocumentID required |

---

## Required Libraries

### Android
- `OzDSt1106Digest.java` — Uzbekistan national hash standard
- `GOST28147Engine.java` — Block cipher engine
- `Hex.java` — Hex encoding utility

All available at: https://github.com/qo0p/SampleAndroidAppCallDeeplinkEIMZO

### iOS
- Port `OzDSt1106Digest` to Swift/ObjC, or use a bridge
- The hash algorithm is based on GOST 28147 block cipher

---

## Server Configuration

The E-IMZO server is deployed as a Docker container alongside the PHP backend.
Configuration is in `docker/eimzo-server/config.properties`.

**Requirements for production:**
1. VPN key file (`.key`) from НИЦ НТ (Замира опа @baxaabdu)
2. SiteID: `3383` (already registered for `api.example.com`)
3. Upload URL: `https://api.example.com/v1/integration/eimzo`
4. Redis for mobile DocumentID state management
5. Java 8 runtime (amazoncorretto:8-alpine)
