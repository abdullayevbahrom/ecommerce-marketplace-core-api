# MyID Integration Guide

Backend API for identity verification via [MyID](https://identity.example.com). This document describes how **web frontend** and **mobile apps** should integrate with our backend API to verify users.

**Official MyID docs:**
- WebSDK: https://docs.identity.example.com/#/en/websdk
- Mobile SDK: https://docs.identity.example.com/#/en/sdk

---

## Table of Contents

1. [API Endpoints Overview](#api-endpoints-overview)
2. [Web Integration (WebSDK)](#web-integration-websdk)
3. [Mobile Integration (Android & iOS SDK)](#mobile-integration-android--ios-sdk)
4. [Response Format](#response-format)
5. [Error Codes](#error-codes)
6. [Configuration](#configuration)

---

## API Endpoints Overview

| Method | Endpoint | Auth | Description |
|--------|----------|------|-------------|
| `POST` | `/api/myid/init-web` | Optional | Create WebSDK session, returns web URL |
| `POST` | `/api/myid/callback` | Optional | Submit auth_code after WebSDK verification |
| `GET` | `/api/myid/session-result` | Optional | Poll WebSDK session status |
| `POST` | `/api/myid/verify` | Optional | Submit auth code from Mobile SDK |
| `POST` | `/api/myid/register` | No | Register new user via MyID |
| `GET` | `/api/myid/status` | Required | Get current user's verification status |
| `GET` | `/api/myid/sdk-config` | No | Get SDK config for mobile apps |

**Authentication:** Pass `Authorization: Bearer {user_token}` header when available. This links the verification to the authenticated user.

---

## Web Integration (WebSDK)

### Flow Overview

```
Frontend                    Backend API                  MyID
   |                            |                          |
   |-- POST /api/myid/init-web --->                        |
   |                            |-- client_credentials --->|
   |                            |<-- access_token ---------|
   |                            |-- POST /web/sessions --->|
   |                            |<-- session_id -----------|
   |<-- { session_id, web_url } |                          |
   |                            |                          |
   |-- Redirect/iframe to web_url ----------------------->|
   |                            |                          |
   |<-- redirect with auth_code --------------------------|
   |                            |                          |
   |-- POST /api/myid/callback -->                         |
   |                            |-- exchange auth_code --->|
   |                            |<-- access_token ---------|
   |                            |-- GET /users/me -------->|
   |                            |<-- user profile ---------|
   |<-- { user, verification }  |                          |
```

### Step 1: Initialize Session

```http
POST /api/myid/init-web
Content-Type: application/json
Authorization: Bearer {user_token}  (optional)

{
  "redirect_uri": "https://yoursite.com/myid/callback",
  "pinfl": "12345678901234",       // optional - pre-fill if known
  "birth_date": "2000-12-31",      // optional - pre-fill if known
  "lang": "en"                     // optional - en|ru|uz
}
```

**Response:**
```json
{
  "message": "Success",
  "error_code": 0,
  "data": {
    "session_id": "abc-123-def",
    "external_id": "550e8400-e29b-41d4-a716-446655440000",
    "web_url": "https://web.identity.example.com/?session_id=abc-123-def&redirect_uri=https://yoursite.com/myid/callback&lang=en"
  }
}
```

### Step 2: Redirect User to MyID

**Option A: Full Redirect**
```javascript
// Redirect user to the verification page
window.location.href = response.data.web_url;
```

**Option B: Iframe Embed**
```html
<iframe
  id="myid-iframe"
  src="https://web.identity.example.com/?session_id=SESSION_ID&redirect_uri=YOUR_URI&iframe=true"
  allow="camera"
  style="width:100%; height:100%; border:none;">
</iframe>

<script>
// Listen for verification events from iframe
window.addEventListener("message", function(e) {
  if (e.data.source !== "MyIDWebSDK") return;

  switch (e.data.status) {
    case "LIVENESS_PASSED":
      console.log("Verification successful");
      break;
    case "LIVENESS_FAILED":
      console.log("Verification failed");
      break;
  }
});

// Required: sync screen dimensions with iframe
function screenChangeListener() {
  var iframe = document.getElementById("myid-iframe");
  iframe.contentWindow.postMessage({
    cmd: "screen",
    screen: window.screen,
    height: window.innerHeight,
    width: window.innerWidth
  }, "*");
}
window.addEventListener("resize", screenChangeListener);
window.addEventListener("orientationchange", screenChangeListener);
</script>
```

### Step 3: Handle Callback

After verification, MyID redirects to your `redirect_uri` with query params:
```
https://yoursite.com/myid/callback?auth_code=XXXX&session_id=YYYY
```

Send the `auth_code` to the backend:

```http
POST /api/myid/callback
Content-Type: application/json
Authorization: Bearer {user_token}  (optional)

{
  "code": "auth_code_from_redirect",
  "session_id": "session_id"
}
```

**Response:**
```json
{
  "message": "Verification successful",
  "error_code": 0,
  "data": {
    "user": {
      "id": 123,
      "token": "auth_token_here",
      "name": "John",
      "lastname": "Doe",
      "middlename": "Smith",
      "phone": "998901234567",
      "myid_verified": 1
    },
    "verification": {
      "id": 1,
      "pinfl": "12345678901234",
      "full_name": "Doe John Smith",
      "first_name": "John",
      "last_name": "Doe",
      "middle_name": "Smith",
      "birth_date": "2000-12-31",
      "gender": 1,
      "gender_label": "Male",
      "passport": "AA1234567",
      "verification_status": 1,
      "verification_status_label": "Verified",
      "verified_at": "2025-01-15 10:30:00"
    }
  }
}
```

### Optional: Poll Session Status

If using iframe, you can poll session status instead of waiting for redirect:

```http
GET /api/myid/session-result?session_id=abc-123-def
```

**Response (pending):**
```json
{
  "error_code": 0,
  "data": {
    "status": "pending",
    "auth_code": null,
    "attempts": []
  }
}
```

**Response (completed):**
```json
{
  "error_code": 0,
  "data": {
    "status": "completed",
    "auth_code": "AUTHORIZATION_CODE_HERE",
    "attempts": [...]
  }
}
```

When `status` is `"completed"`, take the `auth_code` and call `POST /api/myid/callback`.

---

## Mobile Integration (Android & iOS SDK)

### Flow Overview

```
Mobile App                 Backend API                  MyID
   |                            |                          |
   |-- GET /api/myid/sdk-config ->                         |
   |<-- { client_id, ... }      |                          |
   |                            |                          |
   |-- Initialize MyID SDK (with client_id) -------------->|
   |-- User completes biometric check -------------------->|
   |<-- SDK returns code (authorization code) -------------|
   |                            |                          |
   |-- POST /api/myid/verify --->                          |
   |                            |-- exchange code -------->|
   |                            |<-- access_token ---------|
   |                            |-- GET /users/me -------->|
   |                            |<-- user profile ---------|
   |<-- { user, verification }  |                          |
```

### Step 1: Get SDK Config

```http
GET /api/myid/sdk-config
```

**Response:**
```json
{
  "error_code": 0,
  "data": {
    "client_id": "your_client_id",
    "base_url": "https://identity.example.com",
    "scope": "common_data",
    "sdk_hash": "sha256_hash_value",
    "timestamp": 1705312800000
  }
}
```

### Step 2: Initialize SDK

#### Android (Kotlin)

```kotlin
// Add dependency in build.gradle
// implementation 'uz.myid.sdk:myid-sdk:latest'

val myIdConfig = MyIdConfig.builder(clientId = sdkConfig.clientId)
    .withPassportData(passportData)         // Optional: "AA1234567" or PINFL
    .withBirthDate("31.12.2000")            // Optional: "dd.MM.yyyy"
    .withExternalId(UUID.randomUUID().toString())
    .withThreshold(0.55f)                   // Face match confidence (0.50-0.99)
    .withBuildMode(MyIdBuildMode.PRODUCTION) // or DEBUG for testing
    .withEntryType(MyIdEntryType.AUTH)       // AUTH for identity verification
    .withLocale(Locale("en"))               // en, ru, or uz
    .withCameraShape(MyIdCameraShape.CIRCLE)
    .build()

// Start SDK
MyIdSdk.start(activity, myIdConfig, object : MyIdResultListener {
    override fun onSuccess(result: MyIdResult) {
        val code = result.code  // Authorization code to send to backend
        sendToBackend(code)
    }

    override fun onError(e: MyIdException) {
        Log.e("MyID", "Error ${e.code}: ${e.message}")
    }

    override fun onUserExited() {
        // User cancelled verification
    }
})
```

**Android Requirements:**
- minSdkVersion 21, targetSdkVersion 33+
- Kotlin 1.5+
- Permissions: `INTERNET`, `CAMERA`

#### iOS (Swift)

```swift
// Add via CocoaPods or SPM

MyIdSdk.start(withConfigureOptions: { options in
    options?.clientId = sdkConfig.clientId
    options?.passportData = "AA1234567"     // Optional
    options?.dateOfBirth = "31.12.2000"     // Optional: "dd.MM.yyyy"
    options?.externalId = UUID().uuidString
    options?.threshold = 0.55
    options?.buildMode = .production        // or .debug
    options?.entryType = .auth
    options?.locale = .en
}, withDelegate: self)

// Delegate methods
extension ViewController: MyIdSdkDelegate {
    func myidOnSuccess(result: MyIdResult) {
        let code = result.code  // Authorization code to send to backend
        sendToBackend(code)
    }

    func myidOnError(exception: MyIdException) {
        print("Error \(exception.code): \(exception.message)")
    }

    func myidOnUserExited() {
        // User cancelled
    }
}
```

**iOS Requirements:**
- iOS 11+, Xcode 14.1+, Swift 5.7.1+
- Camera permission in Info.plist

### Step 3: Send Code to Backend

After receiving the `code` from SDK, send it to the backend:

**For existing users (link verification to account):**
```http
POST /api/myid/verify
Content-Type: application/json
Authorization: Bearer {user_token}

{
  "code": "authorization_code_from_sdk"
}
```

**For new users (register + verify in one step):**
```http
POST /api/myid/register
Content-Type: application/json

{
  "code": "authorization_code_from_sdk",
  "phone": "998901234567"
}
```

**Or use verify with register flag:**
```http
POST /api/myid/verify
Content-Type: application/json

{
  "code": "authorization_code_from_sdk",
  "register": true
}
```

### Step 4: Handle Response

All endpoints return the same structure:
```json
{
  "message": "Verification successful",
  "error_code": 0,
  "data": {
    "user": {
      "id": 123,
      "token": "bearer_token_for_future_requests",
      "name": "John",
      "lastname": "Doe",
      "middlename": "Smith",
      "phone": "998901234567",
      "myid_verified": 1
    },
    "verification": { ... },
    "is_new_user": false
  }
}
```

Store the `user.token` for subsequent API requests.

### Check Verification Status

```http
GET /api/myid/status
Authorization: Bearer {user_token}
```

---

## Response Format

All responses follow the standard API format:

**Success:**
```json
{
  "message": "Success",
  "error_code": 0,
  "data": { ... }
}
```

**Error:**
```json
{
  "message": "Error description",
  "error_code": -31
}
```

---

## Error Codes

| Code | Constant | HTTP | Description |
|------|----------|------|-------------|
| 0 | SUCCESS | 200 | Success |
| -30 | ERROR_MYID_NOT_CONFIGURED | 500 | MyID credentials not set on server |
| -31 | ERROR_MYID_CODE_REQUIRED | 422 | Authorization code not provided |
| -32 | ERROR_MYID_TOKEN_EXCHANGE_FAILED | 502 | Failed to exchange code with MyID |
| -33 | ERROR_MYID_USER_DATA_FAILED | 502 | Failed to get user data from MyID |
| -34 | ERROR_MYID_PINFL_MISSING | 502 | MyID response missing PINFL |
| -35 | ERROR_MYID_PINFL_LINKED | 409 | PINFL already linked to another user |
| -36 | ERROR_MYID_SESSION_FAILED | 502 | Failed to create/query MyID session |
| -37 | ERROR_MYID_VERIFICATION_FAILED | 500 | General verification failure |
| -13 | ERROR_USER_NOT_FOUND | 404 | No user linked to PINFL |
| -12 | ERROR_USER_EXISTS | 422 | Phone number already registered |

---

## Configuration

Backend configuration in `config/params.php`:

```php
'myid' => [
    'client_id' => 'YOUR_CLIENT_ID',         // From MyID dashboard
    'client_secret' => 'YOUR_CLIENT_SECRET',  // From MyID dashboard (never expose to frontend!)
    'redirect_uri' => 'https://yoursite.com/api/myid/callback',
    'sandbox' => false,                        // true for testing
    'base_url' => 'https://identity.example.com',          // API URL
    'web_url' => 'https://web.identity.example.com',       // WebSDK URL
],
```

**Sandbox/Development URLs:**
- API: `https://devidentity.example.com`
- WebSDK: `https://web.devid.example.com`

**Production URLs:**
- API: `https://identity.example.com`
- WebSDK: `https://web.identity.example.com`

### Important Security Notes

- **Never expose `client_secret`** to frontend or mobile apps
- Authorization codes have a **5-minute lifetime** and are **single-use**
- All token exchange must happen on the **backend only**
- The mobile SDK does not check for root/emulator - implement your own checks if needed
- MyID implements rate limiting: 5 failed attempts within 1 hour triggers a progressive block
