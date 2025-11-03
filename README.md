# PhoneMonitor - Family Device Helper

> **Consent-based family device monitoring with transparency and ethics at its core.**

PhoneMonitor is a simple, transparent system for families to share basic device status information. It consists of:
- **Web Dashboard** (PHP 8.0+ + MySQL 8) for viewing device status with modern, responsive UI
- **Android App** (Kotlin) for sharing device information

🌐 **Live Demo:** [https://phone-monitor.defecttracker.uk](https://phone-monitor.defecttracker.uk)

## 🚀 Quick Navigation

- Dashboard: [`/dashboard.php`](./dashboard.php)
- Devices: [`/devices.php`](./devices.php)
- Device Details: [`/device_view.php?id=<uuid>`](./device_view.php)
- Analytics: [`/analytics.php`](./analytics.php)
- Alert Rules: [`/alert_rules.php`](./alert_rules.php)
- Setup & Help: [`/setup.php`](./setup.php)

Tip: Start at Setup to configure `.env`, SMTP, Google Maps API key, and generate favicons.

## ✨ Features

### Dashboard
- 📊 **Modern UI** - Dark gradient cards with glassmorphism effects
- 📱 **Device Overview** - Real-time status of all registered devices
- 🗺️ **Location Tracking** - Google Maps integration for location history
- 📈 **Statistics Cards** - Total devices, online status, consent tracking
- 🎨 **UK Date Formats** - All dates displayed in DD/MM/YYYY format
- 🔐 **Secure Authentication** - Session-based login with CSRF protection

### Phase 3 – Advanced Features
- 📈 **Analytics Dashboard** (`/analytics.php`) – Battery trends, location activity, status distribution, geofence activity, device comparison
- 📤 **CSV/PDF Export** – Devices CSV, Locations CSV (mph), Battery CSV, and text-based Device Reports
- 🤖 **Bot Alerts** – Telegram/Discord notifications via bot/webhook configuration
- 🔔 **Custom Alert Rules** – Battery, Speed (mph), Offline, Storage, and Custom JSON conditions with cooldowns
- ✉️ **Email Notifications** – Native SMTP (SSL/TLS 465) with fallback to PHP mail; weekly digest supported
- 🧠 **Caching** – Analytics cache for performance with manual “Clear Cache” on Analytics page
- 🧭 **Speed in mph** – All speed analytics and exports show mph; threshold configurable via `.env`

### API Endpoints
- `POST /api/register.php` - Register new devices
- `POST /api/ping.php` - Device heartbeat with status updates  
- `POST /api/unregister.php` - Device unregistration
- ✅ **CORS Enabled** - Cross-origin support for mobile apps
- 🛡️ **Rate Limiting** - Built-in protection against abuse

## 🎯 Purpose & Ethics

This is NOT a stealth monitoring tool. PhoneMonitor is designed for **consensual family use only**:

✅ **What it does:**
- Shares basic device status (battery, storage, optional location)
- Shows an **always-visible notification** when active
- Requires **explicit consent** before activation
- Allows users to **uninstall or stop sharing** at any time

❌ **What it does NOT do:**
- No stealth or hidden features
- No remote control capabilities
- No keylogging, screenshots, or surveillance capabilities
- No camera or microphone access
- No access to personal data, messages, calls, or media

### Uninstallation
Users can stop sharing at any time by:
1. Opening the app → Settings → "Stop Sharing & Unregister"
2. Or simply uninstalling the app

---

## 🧰 Setup & Help Wizard

Visit `/setup.php` after deployment for a guided setup. The page provides:

- ✅ System status checks (database, .env, SMTP, geofences, notifications, icons)
- ⚙️ Configuration editor for `.env` (Site URL, Google Maps API key, Admin email, Asset version)
- ✉️ SMTP configuration and a “Send Test Email” button with diagnostics
- 🖼️ “Generate Favicons” button to rasterize `assets/icons/favicon.svg` into PNG fallbacks (16/32/180/192/512)
- 📚 Feature guides for Analytics, Exports, Alerts, and Rules
- 🔒 CSRF-protected forms and immediate `.env` reload after save

Tip: The status grid shows green ✓ when icons are generated and SMTP is configured.

---

## 📊 Analytics Dashboard

Open `/analytics.php` to view:

- Battery trends (7 days), location updates (last 24h), device status distribution
- Geofence activity (30 days) and per-device comparison (battery, storage, last seen, updates/events)
- Buttons for Devices CSV, Battery CSV, and “Clear Cache”

Dark mode is supported and persisted. Charts auto-refresh every 5 minutes.

---

## 📤 CSV/PDF Export

Exports are handled by `/export.php`:

- `type=devices_csv` – All devices with status, battery, storage
- `type=locations_csv&device_id=<uuid>&days=<n>` – Location history with speed in mph and Google Maps links
- `type=battery_csv[&device_id=<uuid>]` – Battery snapshots
- `type=report_pdf&device_id=<uuid>` – Text-based device report (last 30 days)

Export activity is logged in `export_history`. CSVs include UTF‑8 BOM for Excel compatibility.

---

## 🔔 Custom Alert Rules & Bot Alerts

Create and manage rules at `/alert_rules.php`:

- Rule types: `battery`, `speed` (mph), `offline`, `storage`, `location`, `custom`
- JSON-based conditions with AND/OR, value comparisons, and cooldown minutes
- Actions: email, Telegram bot, Discord webhook
- Recent triggers table with timestamps and reasons

Configure bots in the “Bot Config” section. Telegram uses a bot token + chat ID; Discord uses a webhook URL.

---

## ✉️ Email (SMTP)

Native SMTP is built in (SSL/TLS 465) with AUTH LOGIN and plaintext fallback to `mail()` if not configured.

`.env` keys:

```env
SMTP_HOST=smtp.gmail.com
SMTP_PORT=465
SMTP_SECURE=ssl
SMTP_USERNAME=your_email@gmail.com
SMTP_PASSWORD=your_app_password
SMTP_FROM_EMAIL=noreply@yoursite.com
SMTP_FROM_NAME=PhoneMonitor
```

Use `/setup.php` → “Send Test Email” to validate delivery and review diagnostics.

Weekly digest emails can be enabled via cron (see below).

---

## 🎨 Favicon & App Icons

- Primary icon: `assets/icons/favicon.svg` (used directly as SVG in modern browsers)
- Generate PNG fallbacks via `/setup.php` → “Generate Favicons”
  - Creates: `favicon-16x16.png`, `favicon-32x32.png`, `apple-touch-icon.png` (180x180), `android-chrome-192x192.png`, `android-chrome-512x512.png`
- Web manifest: `assets/icons/site.webmanifest`
- Android adaptive icons included under `AndroidStudioProject/PhoneMonitor/app/src/main/res/`
- iOS icon export guide: `mobile-icons/ios/README.md`

Server requirements for generation: GD or Imagick (SVG → PNG). Both are supported.

---

## 🗄️ Database & Migrations

You can bootstrap the schema in two ways:

1) Full install:

```bash
mysql -u user -p phone_monitor < install.sql
```

2) Versioned migrations (recommended for upgrades):

```bash
mysql -u user -p phone_monitor < database/migrations/003_geofences.sql
mysql -u user -p phone_monitor < database/migrations/004_phase3_features.sql
mysql -u user -p phone_monitor < database/migrations/005_fix_collations.sql   # optional uniform collation
```

Notes:
- Migrations use version-safe conditional `ALTER` logic for MySQL 5.7+/8.0 compatibility
- Where legacy collations exist, the runtime applies `COLLATE utf8mb4_unicode_ci` on JOINs; `005_fix_collations.sql` standardizes schema permanently
- Device identifiers are UUIDs; compatibility columns exist for older records

---

## 🏎️ Speed (mph) & UK Formats

- All speed analytics and exports use mph (km/h × 0.621371)
- Configurable threshold for highlighting averages:

```env
SPEED_AVG_THRESHOLD_MPH=75
```

- Frontend dates use UK format (DD/MM/YYYY HH:MM). Internal logs/exports use ISO `Y-m-d H:i:s`.

---

## ⏱️ Cron Jobs

Add these to your crontab (`crontab -e`) to enable background processing:

```bash
*/5 * * * * /usr/bin/php /path/to/PhoneMonitor/cron_notifications.php >> /var/log/pm_notifications.log 2>&1
*/5 * * * * /usr/bin/php /path/to/PhoneMonitor/cron_alert_rules.php >> /var/log/pm_alerts.log 2>&1
0 9 * * 0 /usr/bin/php /path/to/PhoneMonitor/cron_weekly_report.php >> /var/log/pm_weekly.log 2>&1
```

These send pending emails, evaluate alert rules, and deliver a weekly digest.

---

## 📚 Mobile Build Guide

See `MOBILE_BUILD_GUIDE.md` for Android build instructions and packaging. Update your server URL in the app before building.

---

## ✅ Production Checklist

For a concise, step-by-step launch checklist, see `PRODUCTION_CHECKLIST.md`.

## 📋 Requirements

### Web Backend
- PHP 8.0 or higher (tested with PHP 8.0.30)
- MySQL 8.0 or higher
- Apache/Nginx with mod_rewrite (or Plesk)
- HTTPS (strongly recommended)
- Modern browser with ES6+ support

### Android App
- Android 6.0 (API 23) or higher
- Internet connection
- Optional: Location services (user must explicitly enable)

---

## 🚀 Plesk Deployment Guide

### Step 1: Create Database

1. Log into Plesk
2. Go to **Databases** → **Add Database**
3. Database name: `phone_monitor`
4. Create a database user with full permissions
5. Note down the database credentials

### Step 2: Upload Files

1. Upload all PHP files to your domain's document root (e.g., `/httpdocs/`)
2. Ensure the following structure:
   ```
   /httpdocs/
   ├── api/
   │   ├── register.php
   │   ├── ping.php
   │   └── unregister.php
   ├── assets/
   │   ├── css/site.css
   │   └── js/site.js
   ├── config.php
   ├── db.php
   ├── auth.php
   ├── csrf.php
   ├── login.php
   ├── logout.php
   ├── dashboard.php
   ├── devices.php
   ├── device_view.php
   └── .env
   ```

### Step 3: Import Database Schema

1. In Plesk, go to **Databases** → **phpMyAdmin**
2. Select your `phone_monitor` database
3. Click **Import** tab
4. Choose `install.sql` file
5. Click **Go** to execute

### Step 4: Configure Environment

1. Copy `.env.sample` to `.env`:
   ```bash
   cp .env.sample .env
   ```

2. Edit `.env` with your settings:
   ```env
   APP_ENV=production
   SITE_URL=https://your-domain.com
   DB_HOST=localhost
   DB_NAME=phone_monitor
   DB_USER=your_db_user
   DB_PASS=your_db_password
   SESSION_NAME=pm_session
   CSRF_KEY=generate_a_long_random_key_here_minimum_32_chars
   REQUIRE_CONSENT=true
   GOOGLE_MAPS_API_KEY=your_google_maps_api_key_here
   ```

3. Generate a secure CSRF key:
   ```bash
   openssl rand -hex 32
   ```

### Step 5: Set Permissions

```bash
chmod 600 .env
chmod 644 *.php
chmod 755 api/
```

### Step 6: First Login

1. Navigate to `https://your-domain.com/login.php`
2. Default credentials:
   - Username: `admin`
   - Password: `changeme123`
3. **IMPORTANT:** Change the password immediately!

**Or add a custom admin user:**
```bash
# Create add_user.php in your document root
php -r "echo password_hash('YourSecurePassword', PASSWORD_DEFAULT);"
# Copy the output hash

# Then run SQL in phpMyAdmin:
INSERT INTO users (username, password_hash, name) VALUES 
('yourusername', 'paste_hash_here', 'Your Name');
```

To change an existing password:
```bash
php -r "echo password_hash('your_new_password', PASSWORD_DEFAULT);"
```

Then update in database:
```sql
UPDATE users SET password_hash = 'your_generated_hash' WHERE username = 'admin';
```

---

## 🎨 UI Features

### Modern Dashboard Design
- **Glassmorphism effects** - Frosted glass cards with backdrop blur
- **Dark gradient backgrounds** - Professional blue-teal color scheme
- **Animated components** - Smooth hover effects and transitions
- **Responsive layout** - Mobile-friendly grid system
- **Status badges** - Color-coded device status (Online/Offline/Revoked)
- **Real-time updates** - "Just now", "X minutes ago" timestamps

### Card-Based Interface
Each stat card features:
- Icon box with gradient background
- Category badge (DEVICES, STATUS, CONSENT, REVOKED)
- Large number display
- Descriptive text
- Action button with hover effects

---

## 🌐 API Documentation

### POST /api/register.php

Register a new device or update existing registration.

**Request:**
```json
{
  "device_uuid": "550e8400-e29b-41d4-a716-446655440000",
  "display_name": "John's Phone",
  "owner_name": "John Smith",
  "consent": true
}
```

**Response (201 Created):**
```json
{
  "success": true,
  "message": "Device registered",
  "device_id": 1
}
```

**Response (200 OK - Update):**
```json
{
  "success": true,
  "message": "Device updated",
  "device_id": 1
}
```

**Errors:**
- `400` - Invalid JSON or missing required fields
- `403` - Consent required or device revoked
- `405` - Method not allowed (must use POST)
- `429` - Rate limit exceeded (60 seconds between requests)
- `500` - Internal server error

---

### POST /api/ping.php

Send device heartbeat with status update.

**Request:**
```json
{
  "device_uuid": "550e8400-e29b-41d4-a716-446655440000",
  "battery": 85,
  "free_storage": 25.5,
  "note": "Everything running smoothly",
  "lat": 51.5074,
  "lon": -0.1278,
  "accuracy": 10,
  "provider": "gps",
  "loc_ts": 1699012345000
}
```

**Required fields:**
- `device_uuid` - UUID of registered device

**Optional fields:**
- `battery` - Battery percentage (0-100)
- `free_storage` - Free storage in GB
- `note` - Status message
- `lat` - Latitude (-90 to 90)
- `lon` - Longitude (-180 to 180)
- `accuracy` - GPS accuracy in meters
- `provider` - Location provider (gps, network, fused)
- `loc_ts` - Location timestamp in milliseconds

**Response (200 OK):**
```json
{
  "success": true,
  "message": "Ping received",
  "timestamp": "2025-11-03T14:30:45+00:00"
}
```

**Errors:**
- `400` - Invalid JSON or device not found
- `403` - Device has been revoked
- `405` - Method not allowed (must use POST)
- `429` - Rate limit exceeded (10 seconds between pings)
- `500` - Internal server error

---

### POST /api/unregister.php

Unregister a device and stop sharing.

**Request:**
```json
{
  "device_uuid": "550e8400-e29b-41d4-a716-446655440000"
}
```

**Response (200 OK):**
```json
{
  "success": true,
  "message": "Device unregistered"
}
```

**Errors:**
- `400` - Invalid JSON or device not found
- `405` - Method not allowed (must use POST)
- `500` - Internal server error

---

## 🔧 Testing API Endpoints

### Using curl

**Register a device:**
```bash
curl -X POST https://your-domain.com/api/register.php \
  -H "Content-Type: application/json" \
  -d '{
    "device_uuid": "550e8400-e29b-41d4-a716-446655440000",
    "display_name": "Test Device",
    "owner_name": "Test User",
    "consent": true
  }'
```

**Send a ping:**
```bash
curl -X POST https://your-domain.com/api/ping.php \
  -H "Content-Type: application/json" \
  -d '{
    "device_uuid": "550e8400-e29b-41d4-a716-446655440000",
    "battery": 85,
    "free_storage": 25.5,
    "note": "Test ping",
    "lat": 51.5074,
    "lon": -0.1278,
    "accuracy": 10,
    "provider": "gps"
  }'
```

**Unregister a device:**
```bash
curl -X POST https://your-domain.com/api/unregister.php \
  -H "Content-Type: application/json" \
  -d '{
    "device_uuid": "550e8400-e29b-41d4-a716-446655440000"
  }'
```

---

## 🗺️ Google Maps API Setup

To display device locations on a map:

1. Go to [Google Cloud Console](https://console.cloud.google.com/)
2. Create a new project or select existing one
3. Enable **Maps JavaScript API**
4. Go to **Credentials** → **Create Credentials** → **API Key**
5. Restrict the key:
   - Application restrictions: HTTP referrers
   - Add your domain (e.g., `https://your-domain.com/*`)
   - API restrictions: Maps JavaScript API
6. Copy the API key to your `.env` file:
   ```env
   GOOGLE_MAPS_API_KEY=AIza...your_key_here
   ```

If the Maps API key is not configured, the dashboard will show coordinates with a link to Google Maps instead of an embedded map.

---

## 📱 Android App Setup

### Build from Source

1. **Prerequisites:**
   - Android Studio (latest version)
   - JDK 11

2. **Configure Server URL:**
   - Open `android/app/src/main/java/com/phonemonitor/app/data/DevicePreferences.kt`
   - Update `DEFAULT_SERVER_URL` to your server URL:
     ```kotlin
     private const val DEFAULT_SERVER_URL = "https://your-domain.com"
     ```

3. **Build APK:**
   ```bash
   cd android
   ./gradlew assembleDebug
   ```
   
   Or for release:
   ```bash
   ./gradlew assembleRelease
   ```

4. **Locate APK:**
   - Debug: `android/app/build/outputs/apk/debug/app-debug.apk`
   - Release: `android/app/build/outputs/apk/release/app-release.apk`

### Install on Device

1. Enable **Developer Options** on Android device
2. Enable **USB Debugging**
3. Connect device via USB
4. Install APK:
   ```bash
   adb install app-debug.apk
   ```

Or transfer the APK to the device and install manually.

### First Run

1. Open PhoneMonitor app
2. Read the consent screen carefully
3. Enter:
   - Server URL (e.g., `https://your-domain.com`)
   - Device Name (e.g., "John's Phone")
   - Owner Name (e.g., "John Smith")
4. Check "I have read and agree to share my device information"
5. Tap "I Accept"

The app will register with the server and start sharing device status.

---

## 🔒 Security Hardening

### HTTPS/HSTS

**Critical:** Always use HTTPS for production deployments.

In Plesk:
1. Go to **Hosting Settings**
2. Enable **Permanent SEO-safe 301 redirect from HTTP to HTTPS**
3. Enable **HSTS**

### Strong Admin Password

1. Generate a strong password (20+ characters)
2. Use the password hash generator:
   ```bash
   php -r "echo password_hash('your_strong_password', PASSWORD_DEFAULT);"
   ```
3. Update database with the new hash

### Rotate CSRF Key

Generate a new CSRF key periodically:
```bash
openssl rand -hex 32
```

Update `.env` with the new key and restart PHP-FPM.

### Rate Limiting

The API includes basic rate limiting. For production:
- Use Plesk's **Fail2Ban** or **ModSecurity**
- Configure IP-based rate limiting in Apache/Nginx
- Monitor audit logs for suspicious activity

### Database Security

1. Use a dedicated database user with minimal permissions
2. Restrict database access to localhost only
3. Regularly backup the database
4. Review audit logs: `SELECT * FROM audit_log ORDER BY created_at DESC LIMIT 100;`

### Log Retention

Review and clean old logs periodically:
```sql
-- Delete audit logs older than 90 days
DELETE FROM audit_log WHERE created_at < DATE_SUB(NOW(), INTERVAL 90 DAY);

-- Delete location data older than 30 days
DELETE FROM device_locations WHERE created_at < DATE_SUB(NOW(), INTERVAL 30 DAY);
```

---

## 🐛 Troubleshooting

### "Database connection failed"

- Check `.env` database credentials
- Verify MySQL is running
- Check database exists and user has permissions
- Test connection:
  ```bash
  php -r "new PDO('mysql:host=localhost;dbname=phone_monitor', 'user', 'pass');"
  ```

### "Configuration Error: .env file not found"

- Ensure `.env` exists in the document root
- Copy from `.env.sample` if missing
- Check file permissions (should be readable by web server)

### "Map unavailable" message

- Verify `GOOGLE_MAPS_API_KEY` is set in `.env`
- Check API key restrictions in Google Cloud Console
- Ensure Maps JavaScript API is enabled
- Check browser console for errors

### PHP version issues

- Minimum required: PHP 8.0
- Check current version: `php -v`
- In Plesk: **PHP Settings** → Select PHP 8.0 or higher

### Database charset errors

- Ensure database uses `utf8mb4_unicode_ci`
- Check in phpMyAdmin: Database → Operations → Collation
- Recreate database if needed with correct charset

### Android app connection errors

- Verify server URL is correct (include `https://`)
- Check firewall allows HTTPS traffic
- Test API endpoints manually (see API Testing section above)
- Check server logs: `/var/log/apache2/error.log` or Plesk logs
- Verify CORS headers are being sent (check browser dev tools)
- Rate limiting may be in effect - wait and retry

### Location not working on Android

- Check location permissions granted in Android Settings
- Ensure Google Play Services is installed and updated
- Enable location services on device
- Check `locationEnabled` setting in app

---

## 📊 Database Schema

### Tables

- **users** - Admin users for dashboard access
- **devices** - Registered devices
- **device_locations** - Location history (append-only)
- **audit_log** - Action audit trail

See `install.sql` for complete schema.

---

## 🤝 Contributing

This is a family-focused, consent-based tool. Contributions should:
- Maintain transparency and ethical standards
- Never introduce stealth features
- Respect user privacy and consent
- Include clear documentation

---

## 📄 License

See [LICENSE](LICENSE) file for details.

---

## 🔐 Security

See [SECURITY.md](SECURITY.md) for security policy and reporting vulnerabilities.

---

## ✅ Checklist for Plesk Deployment

- [ ] Database created in Plesk
- [ ] Files uploaded to document root
- [ ] `install.sql` imported via phpMyAdmin
- [ ] `.env` configured with correct credentials
- [ ] CSRF key generated (minimum 32 characters)
- [ ] File permissions set (`.env` = 600)
- [ ] HTTPS/SSL certificate installed
- [ ] HSTS enabled in Plesk
- [ ] First login successful at `/login.php`
- [ ] Default admin password changed
- [ ] Google Maps API key configured (optional)
- [ ] Android app built with correct server URL
- [ ] Test device registered successfully
- [ ] Dashboard showing device data
- [ ] Location display working (if enabled)
- [ ] Audit log monitoring set up
- [ ] Regular database backups scheduled
- [ ] Security hardening completed

---

**Remember:** This tool is for consensual family use only. Always be transparent about what data is being collected and ensure users understand they can stop sharing at any time.

---

## 🖼️ Screenshots (optional)

You can include screenshots to help users understand the UI:

- Create a folder `docs/screenshots/`
- Suggested filenames:
  - `dashboard.png`
  - `device-view.png`
  - `analytics.png`
  - `alert-rules.png`
  - `setup.png`

Then embed them in this README, for example:

```markdown
![Dashboard](docs/screenshots/dashboard.png)
![Device View](docs/screenshots/device-view.png)
![Analytics](docs/screenshots/analytics.png)
![Alert Rules](docs/screenshots/alert-rules.png)
![Setup](docs/screenshots/setup.png)
```
