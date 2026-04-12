# Didox Automatic Token Refresh

This document describes the automatic token refresh system for Didox API authentication.

## Overview

The system automatically refreshes the Didox authentication token every 3 hours using the configured PFX key and E-IMZO signer service. This eliminates the need for manual login when creating/signing documents.

## Architecture

```
┌─────────────┐     ┌─────────────────┐     ┌────────────────┐
│   CRON      │────▶│ Console Command │────▶│ DidoxService   │
│ (3 hours)   │     │ didox-token/refresh│    │                │
└─────────────┘     └─────────────────┘     └────────┬───────┘
                                                       │
                              ┌────────────────────────┘
                              │
                    ┌─────────▼─────────┐
                    │  E-IMZO Signer     │
                    │  (Docker service)  │
                    └─────────┬─────────┘
                              │
                    ┌─────────▼─────────┐
                    │   DIDOX API        │
                    │ /v1/auth/{token}   │
                    └─────────┬─────────┘
                              │
                    ┌─────────▼─────────┐
                    │ Settings Table    │
                    └────────────────────┘
```

## Configuration

### 1. PFX Key Setup

1. Go to **Admin Panel → Settings → Didox → Automated Signing**
2. Upload your PFX key file
3. Enter PFX password
4. Set Signer Service URL (e.g., `http://eimzo-signer:8080/generate`)

### 2. Seller Information

In **General Settings** tab:
- Seller INN - Must match the INN in your PFX certificate
- Other seller details for document generation

### 3. CRON Setup

Add to your crontab:

```bash
# Edit crontab
crontab -e

# Add this line (every 3 hours)
0 */3 * * * cd /var/www/project && php yii didox-token/refresh >> /var/log/didox-cron.log 2>&1
```

Or run manually:
```bash
# Check status
php yii didox-token/status

# Refresh token
php yii didox-token/refresh

# Force refresh (even if not expired)
php yii didox-token/refresh --force

# Verbose output
php yii didox-token/refresh --verbose

# Test PFX authentication (without saving)
php yii didox-token/test

# Enable/disable auto-refresh
php yii didox-token/enable
php yii didox-token/disable
```

## Queue-based Alternative

Instead of CRON, you can use Yii Queue:

```php
// Push token refresh job
Yii::$app->queue->push(new \app\jobs\DidoxTokenRefreshJob());

// Or with delay (e.g., 2 hours 50 minutes)
Yii::$app->queue->delay(10200)->push(new \app\jobs\DidoxTokenRefreshJob());
```

## Monitoring

### Admin Panel

Go to **Admin Panel → Settings → Didox → Token Status** to see:
- Current token status (Active/Expiring Soon/Expired)
- Time remaining
- Auto-refresh toggle
- Manual refresh button
- Last error message
- Cron setup instructions

### Telegram Notifications

If Telegram component is configured:
- Success notification on token refresh
- Failure alerts when refresh fails

### Log Files

```bash
# Application logs
tail -f /var/log/didox-cron.log

# Yii logs
tail -f /var/www/project/runtime/logs/app.log | grep didox
```

## Database Schema

Settings used by the system:

| Setting Key | Description |
|-------------|-------------|
| `didox_eimzo_token` | Current API token |
| `didox_eimzo_tax_id` | Tax ID associated with token |
| `didox_token_expires_at` | Token expiration timestamp |
| `didox_auto_refresh_status` | `active`, `disabled`, `failed`, `manual` |
| `didox_auto_refresh_error` | Last error message |
| `didox_auto_refresh_last_attempt` | Last refresh attempt timestamp |
| `didox_pfx_path` | Path to PFX key file |
| `didox_pfx_password` | PFX key password |
| `didox_signer_url` | E-IMZO signer service URL |
| `didox_seller_inn` | Seller INN/TIN |

## Troubleshooting

### Token refresh fails

1. Check PFX configuration:
   ```bash
   php yii didox-token/test
   ```

2. Verify E-IMZO signer service:
   ```bash
   curl http://eimzo-signer:8080/
   ```

3. Check logs:
   ```bash
   tail -100 /var/www/project/runtime/logs/app.log | grep Didox
   ```

4. Verify seller INN matches PFX certificate

### Token not auto-refreshing

1. Check auto-refresh status:
   ```bash
   php yii didox-token/status
   ```

2. Ensure CRON is running:
   ```bash
   grep didox /var/log/syslog
   ```

3. Check if status is `disabled` or `failed`

### "PFX file not configured"

- Upload PFX in admin panel
- Verify file exists in `/app/keys/`
- Check file permissions

## Security Notes

- PFX passwords are stored in database (consider encryption)
- Tokens are valid for ~3 hours
- Failed refresh attempts trigger retry with exponential backoff
- Max 3 retries, then manual intervention required

## Migration

If upgrading from manual token management:

```bash
# Run migration
php yii migrate --migrationPath=@app/migrations

# Initial token refresh
php yii didox-token/refresh

# Enable auto-refresh
php yii didox-token/enable
```

## API Reference

### DidoxService Methods

```php
$service = new \app\services\DidoxService();

// Refresh and store token
$result = $service->refreshAndStoreToken();
// Returns: ['success' => true, 'token' => '...', 'expires_at' => '...']

// Check token status
$status = $service->getTokenStatus();
// Returns: ['has_token' => true, 'is_expired' => false, 'expires_in' => '2h 30m', ...]

// Check if expiring soon (within 10 minutes)
$expiring = $service->isTokenExpiringSoon();

// Get token from PFX (without saving)
$result = $service->getAuthTokenFromPfx();
```

## Console Commands

| Command | Description |
|---------|-------------|
| `didox-token/refresh` | Refresh token if expired or expiring |
| `didox-token/refresh --force` | Force refresh regardless of status |
| `didox-token/refresh --verbose` | Verbose output |
| `didox-token/status` | Show current token status |
| `didox-token/enable` | Enable auto-refresh |
| `didox-token/disable` | Disable auto-refresh |
| `didox-token/test` | Test PFX authentication |
| `didox-token/manual` | Refresh with verbose output |

## Exit Codes

- `0` - Success / Token valid
- `1` - Error / Token expired
- `2` - Warning / Token expiring soon (for status command)

## Related Files

- `services/DidoxService.php` - Main service class
- `commands/DidoxTokenController.php` - Console commands
- `jobs/DidoxTokenRefreshJob.php` - Queue job
- `modules/admin/controllers/SettingsController.php` - Admin actions
- `modules/admin/views/settings/didox.php` - Admin UI
- `migrations/m260402_000000_add_didox_auto_token_fields.php` - Database migration

---

For questions or issues, contact the development team.
