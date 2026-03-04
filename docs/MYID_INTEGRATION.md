# MyID Integration Guide

Backend API for identity verification via [MyID](https://identity.example.com).

**Official MyID docs:**
- SDK New Flow (recommended): https://docs.identity.example.com/#/en/sdknew
- WebSDK (browser): https://docs.identity.example.com/#/en/websdk

---

## Table of Contents

1. [Architecture Overview](#architecture-overview)
2. [API Endpoints](#api-endpoints)
3. [Mobile SDK New Flow (Primary)](#mobile-sdk-new-flow-primary)
4. [Secondary Flow (Re-verification with reuid)](#secondary-flow-re-verification-with-reuid)
5. [Web Integration (WebSDK)](#web-integration-websdk)
6. [Legacy Mobile SDK Flow](#legacy-mobile-sdk-flow)
7. [Response Format](#response-format)
8. [Error Codes](#error-codes)
9. [MyID SDK Error Codes](#myid-sdk-error-codes)
10. [Configuration](#configuration)

---

## Architecture Overview

All sensitive operations (access tokens, user data retrieval) happen **backend-to-backend**. The mobile app never touches `client_secret`.

```
┌──────────────┐     ┌──────────────────┐     ┌──────────────┐
│  Mobile App  │     │  app Backend   │     │   MyID API   │
│              │     │                  │     │              │
│ 1. Request   │────>│ 2. Get token     │────>│              │
│    session   │     │ 3. Create session│────>│              │
│              │<────│ 4. Return        │<────│ session_id   │
│              │     │    session_id    │     │              │
│ 5. Init SDK  │─────────────────────────────>│              │
│    with      │     │                  │     │ 6. Biometric │
│    session_id│     │                  │     │    check     │
│              │<─────────────────────────────│ 7. Return    │
│ 8. Send code │────>│ 9. Get user data │────>│    code      │
│    to backend│     │    with code     │     │              │
│              │<────│10. Save & return │<────│ user profile │
│              │     │    verification  │     │              │
└──────────────┘     └──────────────────┘     └──────────────┘
```

**Key security rules:**
- `client_secret` is NEVER sent to mobile — stored only on backend
- The `code` returned by SDK is **one-time use** and expires in **5 minutes**
- Sessions expire after **10 minutes**
- Mobile app MUST implement root/emulator detection (MyID SDK does not)

---

## API Endpoints

| Method | Endpoint | Auth | Flow | Description |
|--------|----------|------|------|-------------|
| `POST` | `/api/myid/create-session` | Optional | SDK New | Create session for mobile SDK |
| `POST` | `/api/myid/verify` | Optional | SDK New + Legacy | Submit code after SDK verification |
| `GET` | `/api/myid/session-status` | No | SDK New | Session recovery (if code lost) |
| `POST` | `/api/myid/register` | No | Any | Register new user via MyID |
| `GET` | `/api/myid/status` | Required | Any | Get user's verification status |
| `GET` | `/api/myid/sdk-config` | No | Legacy | Get SDK config (legacy flow only) |
| `POST` | `/api/myid/init-web` | Optional | WebSDK | Create WebSDK session |
| `POST` | `/api/myid/callback` | Optional | WebSDK | Submit WebSDK auth_code |
| `GET` | `/api/myid/session-result` | No | WebSDK | Poll WebSDK session status |

**Authentication:** Pass `Authorization: Bearer {user_token}` header when available. This links the verification to the authenticated user and enables auto-population of passport data.

---

## Mobile SDK New Flow (Primary)

This is the **recommended** flow for mobile apps. Uses session-based verification.

### Two Variants

| Variant | When to Use | What Happens |
|---------|-------------|--------------|
| **With passport data** | Initial KYC, bank-level verification | Backend sends PINFL/passport to MyID. SDK skips passport entry, goes straight to face capture. Returns full profile data. |
| **Empty session** | User without existing data, guest verification | SDK shows passport scanning page first, then face capture. Returns full profile data. |

### Step 1: Create Session

```http
POST /api/myid/create-session
Content-Type: application/json
Authorization: Bearer {user_token}  (optional but recommended)
```

**For authenticated users** — backend auto-populates passport data from DB:
```json
{
  "threshold": 0.7
}
```
> The backend will automatically use the user's PINFL, passport, birth_date from their existing `user_myid` record. Mobile only needs to send optional `threshold`.

**For authenticated users with reuid (secondary/re-verification):**
```json
{
  "use_reuid": true
}
```

**For unauthenticated users (with passport data):**
```json
{
  "pinfl": "12345678901234",
  "pass_data": "AA1234567",
  "phone_number": "998901234567",
  "birth_date": "1990-01-15",
  "is_resident": true,
  "threshold": 0.7
}
```

**For unauthenticated users (empty session):**
```json
{}
```

**All fields are optional.** The more data you provide, the faster the SDK flow (skips passport entry).

**Success Response:**
```json
{
  "message": "Success",
  "error_code": 0,
  "data": {
    "session_id": "550e8400-e29b-41d4-a716-446655440000"
  }
}
```

**Error Response (MyID unavailable):**
```json
{
  "message": "MyID access_token failed: HTTP 401: Invalid credentials",
  "error_code": -36
}
```

### Step 2: Initialize MyID SDK with session_id

#### Android (Kotlin)

```kotlin
// build.gradle
implementation("uz.myid.sdk.v2:myid-sdk-v2:latest")

// Initialize with session_id from Step 1
val myIdConfig = MyIdConfig.builder(sessionId = response.data.session_id)
    .withLocale(Locale("en"))               // en, ru, uz
    .withCameraShape(MyIdCameraShape.CIRCLE)
    .withBuildMode(MyIdBuildMode.PRODUCTION) // or DEBUG for sandbox
    .build()

// Launch SDK
MyIdSdk.start(activity, myIdConfig, object : MyIdResultListener {
    override fun onSuccess(result: MyIdResult) {
        // result.code — authorization code to send to backend
        verifyWithBackend(result.code)
    }

    override fun onError(e: MyIdException) {
        // e.code — error code (see SDK Error Codes section)
        // e.message — human-readable error description
        handleError(e.code, e.message)
    }

    override fun onUserExited() {
        // User pressed back / cancelled
    }
})
```

**Android Requirements:**
- minSdkVersion 21, targetSdkVersion 34+
- Kotlin 1.8+
- Permissions: `INTERNET`, `CAMERA`
- Add root/emulator detection in your app

#### iOS (Swift)

```swift
// Podfile: pod 'MyIdSDK', '~> 2.0'
// or SPM: https://github.com/anthropics/myid-sdk-ios

import MyIdSDK

let config = MyIdConfig(sessionId: response.data.sessionId)
config.locale = .en          // .en, .ru, .uz
config.buildMode = .production // or .debug for sandbox

MyIdSdk.start(with: config, from: self, delegate: self)

// MARK: - MyIdSdkDelegate
extension ViewController: MyIdSdkDelegate {
    func myidOnSuccess(result: MyIdResult) {
        // result.code — authorization code to send to backend
        verifyWithBackend(result.code)
    }

    func myidOnError(exception: MyIdException) {
        // exception.code — error code
        // exception.message — human-readable description
        handleError(exception.code, exception.message)
    }

    func myidOnUserExited() {
        // User cancelled
    }
}
```

**iOS Requirements:**
- iOS 13+, Xcode 15+, Swift 5.9+
- Camera permission in Info.plist:
```xml
<key>NSCameraUsageDescription</key>
<string>Camera is required for identity verification</string>
```

### Step 3: Send Code to Backend

After SDK returns `code`, send it to the backend **within 5 minutes**:

**For existing authenticated users (link verification to account):**
```http
POST /api/myid/verify
Content-Type: application/json
Authorization: Bearer {user_token}

{
  "code": "authorization_code_from_sdk",
  "flow": "sdk_new"
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
  "flow": "sdk_new",
  "register": true
}
```

### Step 4: Handle Response

**Success:**
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
    "verification": {
      "id": 1,
      "user_id": 123,
      "pinfl": "12345678901234",
      "full_name": "Doe John Smith",
      "full_name_en": "Doe John",
      "first_name": "John",
      "last_name": "Doe",
      "middle_name": "Smith",
      "birth_date": "1990-01-15",
      "gender": 1,
      "gender_label": "Male",
      "nationality": "UZBEK",
      "passport": "AA1234567",
      "comparison_value": 0.92,
      "verification_status": 1,
      "verification_status_label": "Verified",
      "verified_at": "2026-03-04 10:30:00",
      "has_reuid": true
    },
    "is_new_user": false
  }
}
```

**Error (face comparison too low):**
```json
{
  "message": "Face comparison score too low (0.421). Minimum required: 0.5",
  "error_code": -37
}
```

**Error (PINFL already linked):**
```json
{
  "message": "This PINFL is already linked to another account",
  "error_code": -35
}
```

### Session Recovery (if code was lost)

If the mobile app loses communication before receiving the code, wait up to 10 minutes and then check:

```http
GET /api/myid/session-status?session_id=550e8400-e29b-41d4-a716-446655440000
```

**Response:**
```json
{
  "error_code": 0,
  "data": {
    "code": "recovered_authorization_code",
    "status": "closed",
    "attempts": [
      {
        "job_id": "uuid4",
        "timestamp": "2026-03-04T12:34:56Z",
        "reason": null,
        "reason_code": null
      }
    ]
  }
}
```

**Status values:**
- `in_progress` — session still active, user hasn't completed verification
- `closed` — verification completed or 10 minutes elapsed. Check `code` field.
- `expired` — session expired (after 1 hour)

---

## Secondary Flow (Re-verification with reuid)

Use the secondary flow for:
- Password recovery
- Access from a different device
- Periodic liveness checks (without re-entering passport data)

**Prerequisites:** User must have a previous successful primary verification with a valid `reuid`.

### Flow

1. **Create session with reuid:**
```http
POST /api/myid/create-session
Content-Type: application/json
Authorization: Bearer {user_token}

{
  "use_reuid": true
}
```

2. **Initialize SDK** with `session_id` (same as primary flow)

3. **Send code to backend** (same as primary flow)

**Key difference:** The secondary flow response does NOT include full profile data — only `comparison_value`, `pass_data`, and `job_id`. The `profile` field is `null`.

**reuid validity:** Expires at end of month or year depending on your MyID contract. Check `has_reuid` in verification status response.

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
   |<-- redirect with auth_code --------------------------|
   |                            |                          |
   |-- POST /api/myid/callback -->                         |
   |                            |-- exchange auth_code --->|
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
  "pinfl": "12345678901234",
  "birth_date": "2000-12-31",
  "lang": "en"
}
```

**Response:**
```json
{
  "error_code": 0,
  "data": {
    "session_id": "abc-123-def",
    "external_id": "550e8400-e29b-41d4-a716-446655440000",
    "web_url": "https://web.identity.example.com/?session_id=abc-123-def&redirect_uri=..."
  }
}
```

### Step 2: Redirect User

```javascript
window.location.href = response.data.web_url;
```

### Step 3: Handle Callback

After redirect to `redirect_uri?auth_code=XXXX&session_id=YYYY`:

```http
POST /api/myid/callback
Content-Type: application/json
Authorization: Bearer {user_token}  (optional)

{
  "code": "auth_code_from_redirect",
  "session_id": "session_id"
}
```

### Poll Session Status (for iframe)

```http
GET /api/myid/session-result?session_id=abc-123-def
```

---

## Legacy Mobile SDK Flow

For apps still using the legacy SDK (OAuth code exchange):

1. `GET /api/myid/sdk-config` — get `client_id`, `sdk_hash`, `timestamp`
2. Initialize SDK with `client_id` and `sdk_hash`
3. SDK returns authorization `code`
4. `POST /api/myid/verify` with `{"code": "xxx"}` (no `flow` field = legacy)

---

## Check Verification Status

```http
GET /api/myid/status
Authorization: Bearer {user_token}
```

**Verified user:**
```json
{
  "error_code": 0,
  "data": {
    "verified": true,
    "verified_at": "2026-03-04 10:30:00",
    "pinfl": "12345678901234",
    "full_name": "Doe John Smith",
    "verification": {
      "id": 1,
      "user_id": 123,
      "pinfl": "12345678901234",
      "full_name": "Doe John Smith",
      "full_name_en": "Doe John",
      "first_name": "John",
      "last_name": "Doe",
      "middle_name": "Smith",
      "birth_date": "1990-01-15",
      "gender": 1,
      "gender_label": "Male",
      "nationality": "UZBEK",
      "passport": "AA1234567",
      "comparison_value": 0.92,
      "verification_status": 1,
      "verification_status_label": "Verified",
      "verified_at": "2026-03-04 10:30:00",
      "has_reuid": true
    }
  }
}
```

**Unverified user:**
```json
{
  "error_code": 0,
  "data": {
    "verified": false
  },
  "message": "User not verified with MyID"
}
```

---

## Response Format

All responses follow:

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

### Backend Error Codes

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
| -37 | ERROR_MYID_VERIFICATION_FAILED | 500 | Verification failed (face score too low, save error) |
| -13 | ERROR_USER_NOT_FOUND | 404 | No user linked to PINFL |
| -12 | ERROR_USER_EXISTS | 422 | Phone number already registered |

---

## MyID SDK Error Codes

These codes come from the MyID SDK on the mobile side. Handle them in `onError`:

| Code | Description | Recommended Action |
|------|-------------|-------------------|
| 2 | Passport data entered incorrectly | Ask user to re-enter |
| 3 | Failed to confirm liveness | Ask user to retry face scan |
| 4 | Failed to recognize | Ask user to retry with better lighting |
| 5 | External service unavailable | Show "try again later" message |
| 6 | The requested user has passed away | Contact support |
| 7 | Photo from resources not received | Retry |
| 8 | MyID internal error | Retry or contact support |
| 9 | Task expired | Create new session and retry |
| 10 | Queue task timed out | Retry |
| 11-13 | MyID service cannot process | Retry later |
| 14 | Failed liveness — incorrect photo | Ask for proper selfie |
| 15-16 | MyID service cannot process | Retry later |
| 17 | Failed to recognize — incorrect photo | Better lighting/angle |
| 18 | Liveness check service unavailable | Retry later |
| 19 | Recognition service unavailable | Retry later |
| 20 | Blurry photo | Ask for steady camera |
| 21 | Face not fully shown | Ask to center face |
| 22 | Multiple faces found | Only one person in frame |
| 23 | Grayscale image, color required | Check camera settings |
| 24 | Darkened glasses detected | Remove glasses |
| 25 | Photo type not supported | Use standard camera |
| 26 | Eyes closed or not visible | Open eyes, remove glasses |
| 27 | Head rotation detected | Face camera directly |
| 28 | Could not detect face | Center face in frame |
| 29 | Light artifact/finger/reflection detected | Avoid glare |
| 30 | Occlusion detected | Remove face coverings |
| 31 | Central face is not biggest face | Move closer |
| 32 | Nose/mouth not detected | Show full face |
| 33 | Infrared image not sent | Device issue |
| 34 | Expired passport data | Update passport |
| 101 | Error in MyID SDK | Retry or update SDK |
| 102 | Camera access denied | Request camera permission |
| 103 | Universal error — check message | See error message for details |
| 122 | User banned | Contact support |

---

## Configuration

Backend configuration in `config/params.php`:

```php
'myid' => [
    'client_id' => 'YOUR_CLIENT_ID',
    'client_secret' => 'YOUR_CLIENT_SECRET',  // NEVER expose to frontend/mobile!
    'client_hash_id' => 'YOUR_HASH_ID',       // For legacy SDK only
    'redirect_uri' => 'https://yoursite.com/api/myid/callback',
    'sandbox' => false,
    'base_url' => 'https://identity.example.com',
    'web_url' => 'https://web.identity.example.com',
],
```

**Environment URLs:**

| Environment | API URL | WebSDK URL |
|-------------|---------|------------|
| Sandbox | `https://api.devid.example.com` | `https://web.devid.example.com` |
| Production | `https://identity.example.com` | `https://web.identity.example.com` |

### Security Checklist

- [ ] `client_secret` stored only on backend (never in mobile app)
- [ ] Root/emulator detection implemented in mobile app
- [ ] Authorization codes used within 5-minute window
- [ ] Sessions not reused after 10-minute expiry
- [ ] `comparison_value >= 0.5` enforced (backend does this automatically)
- [ ] PINFL uniqueness validated (backend does this automatically)
- [ ] Bearer token passed for authenticated users to enable auto-population
