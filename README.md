# PhoneMonitor - Family Device Helper

A consent-based family device monitoring system that allows family members to share device status and optional location information through a private web dashboard.

## ⚠️ Ethical Use Statement

**This application is designed ONLY for transparent family monitoring with explicit consent.**

- ✅ **Requires explicit user consent** through a clear consent screen
- ✅ **Always shows visible notification** when sharing is active
- ✅ **Location sharing is optional** and can be toggled on/off
- ✅ **No stealth features** - everything is transparent
- ✅ **Easy to uninstall** - users can stop sharing anytime

**Never use this application:**
- ❌ To monitor people without their knowledge
- ❌ For surveillance or tracking without consent
- ❌ To control or remotely access devices
- ❌ In any way that violates privacy or trust

## Features

### Web Dashboard (PHP 8.3 + MySQL 8)
- Secure admin login with password hashing
- Device list with online/offline status
- Individual device view with details
- Google Maps integration for location tracking (when enabled)
- Recent location history (last 10 points)
- Device revocation capability
- Audit logging

### Android Client (Kotlin)
- **Consent screen** - mandatory explicit opt-in
- **Periodic heartbeat** - sends updates every 30 minutes
- **Visible notification** - "Family sharing active" always shown
- **Optional location sharing** - disabled by default, requires permission
- **Device information** - battery level, free storage, last seen
- **Settings** - configure server, device name, owner, location toggle
- **Unregister** - stop sharing and remove device from dashboard

## Installation

### Requirements

- **Server:**
  - PHP 8.3+
  - MySQL 8.0+ or MariaDB 10.5+
  - Apache/Nginx with mod_rewrite
  - HTTPS/SSL certificate (required for production)
  - Plesk control panel (recommended) or standard LAMP stack

- **Android:**
  - Android 6.0+ (API 23+)
  - Google Play Services (for location)

### Plesk Deployment Guide

#### 1. Create Database

1. Log into Plesk control panel
2. Go to **Databases** → **Add Database**
3. Create database: `phone_monitor`
4. Create database user with full privileges
5. Note the database credentials

#### 2. Upload Files

1. Use **File Manager** or FTP to upload the `web/` directory contents to your domain's document root (e.g., `/httpdocs`)
2. Ensure the following structure:
   ```
   /httpdocs/
   ├── api/
   ├── assets/
   ├── includes/
   ├── .env.sample
   ├── config.php
   ├── db.php
   ├── auth.php
   ├── csrf.php
   ├── login.php
   ├── logout.php
   ├── dashboard.php
   ├── devices.php
   ├── device_view.php
   └── install.sql
   ```

#### 3. Configure Environment

1. Copy `.env.sample` to `.env`:
   ```bash
   cp .env.sample .env
   ```

2. Edit `.env` with your settings:
   ```ini
   APP_ENV=production
   SITE_URL=https://your-actual-domain.com
   DB_HOST=localhost
   DB_NAME=phone_monitor
   DB_USER=your_db_user
   DB_PASS=your_db_password
   SESSION_NAME=pm_session
   CSRF_KEY=your_generated_random_key_here
   REQUIRE_CONSENT=true
   GOOGLE_MAPS_API_KEY=your_google_maps_api_key
   ```

3. Generate a CSRF key:
   ```php
   php -r "echo bin2hex(random_bytes(32));"
   ```

#### 4. Import Database Schema

1. In Plesk, go to **Databases** → Select your database → **phpMyAdmin**
2. Select your database
3. Go to **Import** tab
4. Upload `install.sql`
5. Click **Go**

Or via command line:
```bash
mysql -u your_db_user -p phone_monitor < install.sql
```

#### 5. Set Permissions

Ensure PHP can read all files:
```bash
chmod 644 *.php .env
chmod 755 api assets includes
```

#### 6. Configure PHP

In Plesk → **PHP Settings**:
- Set PHP version to **8.3**
- Enable required extensions: `pdo_mysql`, `mysqli`, `mbstring`, `json`
- Set `memory_limit` to at least `128M`

#### 7. Set Up SSL

1. In Plesk → **SSL/TLS Certificates**
2. Either:
   - Use **Let's Encrypt** (free, recommended)
   - Upload your own SSL certificate
3. Enable **Permanent SEO-safe 301 redirect from HTTP to HTTPS**

#### 8. Test the Installation

1. Visit `https://your-domain.com/login.php`
2. Default credentials:
   - Username: `admin`
   - Password: `admin123`
3. **IMMEDIATELY change the password!**

To generate a new password hash:
```php
php -r "echo password_hash('your_new_password', PASSWORD_DEFAULT);"
```

Then update the database:
```sql
UPDATE users SET password_hash = 'your_generated_hash' WHERE username = 'admin';
```

### Google Maps API Key Setup

1. Go to [Google Cloud Console](https://console.cloud.google.com/)
2. Create a new project or select existing
3. Enable **Maps JavaScript API**
4. Go to **Credentials** → **Create Credentials** → **API Key**
5. Restrict the key:
   - **Application restrictions**: HTTP referrers
   - **Website restrictions**: Add your domain `https://your-domain.com/*`
   - **API restrictions**: Maps JavaScript API
6. Copy the API key
7. Add to `.env`:
   ```ini
   GOOGLE_MAPS_API_KEY=AIza...your_key_here
   ```

If you skip this step, the map will show: "Map unavailable – add GOOGLE_MAPS_API_KEY"

### Android App Setup

#### 1. Configure Build

Edit `android/app/build.gradle`:
```gradle
buildConfigField "String", "DEFAULT_SERVER_URL", '"https://your-actual-domain.com"'
```

#### 2. Build APK

Option A: Using Android Studio
1. Open `android/` folder in Android Studio
2. Wait for Gradle sync
3. **Build** → **Build Bundle(s) / APK(s)** → **Build APK(s)**
4. APK will be in `android/app/build/outputs/apk/debug/app-debug.apk`

Option B: Using Command Line
```bash
cd android
./gradlew assembleDebug
```

APK location: `android/app/build/outputs/apk/debug/app-debug.apk`

#### 3. Install on Device

1. Enable **Settings** → **Security** → **Unknown sources** (Android < 8)
   Or per-app unknown sources (Android 8+)
2. Transfer `app-debug.apk` to device
3. Open and install
4. Grant necessary permissions when prompted

#### 4. Configure App

1. Open PhoneMonitor app
2. Read and accept the consent screen
3. Go to **Settings**
4. Configure:
   - **Server URL**: `https://your-domain.com`
   - **Device Name**: e.g., "John's Phone"
   - **Owner Name**: e.g., "John"
   - **Share Location**: Toggle on if desired (requires location permission)
5. Save settings

The app will now send updates every 30 minutes in the background.

## Security Hardening

See [SECURITY.md](SECURITY.md) for detailed security recommendations.

### Essential Security Steps

1. **Change default admin password immediately**
2. **Use HTTPS only** - Never run in production without SSL
3. **Enable HSTS** in your web server configuration
4. **Rotate CSRF key regularly**
5. **Keep software updated** - PHP, MySQL, Android dependencies
6. **Set strong database password**
7. **Limit database user privileges** - only what's needed
8. **Review audit logs regularly**
9. **Set up log retention policy** - don't keep location data forever
10. **Backup database regularly**

## Troubleshooting

### Web Dashboard Issues

**"Database connection failed"**
- Check `.env` database credentials
- Verify MySQL service is running
- Ensure database exists and user has access

**"CSRF token validation failed"**
- Ensure PHP sessions are working
- Check `session.save_path` is writable
- Verify CSRF_KEY is set in `.env`

**"Map unavailable"**
- Add valid Google Maps API key to `.env`
- Check API key restrictions in Google Cloud Console
- Ensure Maps JavaScript API is enabled

**PHP Version Error**
- Set PHP version to 8.3 in Plesk
- Check `php -v` matches expected version

### Android App Issues

**"Device not found" error**
- Check server URL in app settings
- Verify server is accessible from device
- Check device has internet connection

**Location not sharing**
- Ensure location toggle is ON in settings
- Grant location permissions (including background)
- Check device location services are enabled

**App not sending updates**
- Check notification shows "Family sharing active"
- Verify device has network connectivity
- Check battery optimization isn't killing app

**"Registration failed"**
- Verify server URL is correct (include https://)
- Check server is accessible
- Review server PHP error logs

## Development

### Running Locally with Codespaces

A `.devcontainer` configuration is provided for GitHub Codespaces:

1. Open repository in Codespaces
2. Wait for container build
3. Import `install.sql` to MySQL
4. Copy `.env.sample` to `.env` and configure
5. Access at `http://localhost:8080`

### Testing API Endpoints

Use curl to test API endpoints:

```bash
# Register device
curl -X POST https://your-domain.com/api/register.php \
  -H "Content-Type: application/json" \
  -d '{"device_uuid":"test-uuid","display_name":"Test Device","owner_name":"Test User","consent":true}'

# Send ping
curl -X POST https://your-domain.com/api/ping.php \
  -H "Content-Type: application/json" \
  -d '{"device_uuid":"test-uuid","battery":75,"free_storage":"5GB"}'

# Unregister
curl -X POST https://your-domain.com/api/unregister.php \
  -H "Content-Type: application/json" \
  -d '{"device_uuid":"test-uuid"}'
```

## Uninstalling

### To Stop Sharing from Android

1. Open PhoneMonitor app
2. Tap **Stop Sharing (Unregister)**
3. Confirm action
4. Uninstall app from device

### To Remove from Dashboard

1. Log into web dashboard
2. Click device → **View**
3. Click **Revoke Device**
4. Device will receive 403 error on next ping and stop trying

## License

GPL-3.0 License - See [LICENSE](LICENSE) for details.

## Support

For issues, questions, or contributions, please visit the GitHub repository.

---

**Remember: This tool is designed for transparent, consensual family monitoring only. Always respect privacy and obtain explicit consent.**
