# Plesk Deployment Checklist for PhoneMonitor

## Pre-Deployment Preparation

### 1. Server Requirements Verification
- [ ] Plesk Panel installed and accessible
- [ ] PHP 8.3 available (check in Tools & Settings → Updates)
- [ ] MySQL 8.0+ available
- [ ] SSL/TLS certificate available (Let's Encrypt or purchased)
- [ ] Domain configured and pointing to server

### 2. Download Files
- [ ] Download all repository files or clone from Git
- [ ] Verify file integrity
- [ ] Review `README.md` and `SECURITY.md`

---

## Database Setup

### 3. Create Database
- [ ] Log into Plesk Panel
- [ ] Navigate to **Databases** section
- [ ] Click **Add Database**
  - Database name: `phone_monitor`
  - Database server: Select your MySQL 8.0 instance
- [ ] Create database user:
  - Username: `pm_user` (or your choice)
  - Password: Generate strong password (20+ characters)
  - ✅ Save credentials securely
- [ ] Grant user permissions:
  - [x] SELECT
  - [x] INSERT
  - [x] UPDATE
  - [x] DELETE
  - [ ] DROP (not needed for production)
  - [ ] CREATE (not needed after setup)

### 4. Import Schema
- [ ] Open **phpMyAdmin** from Plesk Databases section
- [ ] Select `phone_monitor` database
- [ ] Click **Import** tab
- [ ] Choose file: `install.sql`
- [ ] Character set: `utf8mb4`
- [ ] Click **Go** to execute
- [ ] Verify tables created:
  - [ ] users
  - [ ] devices
  - [ ] device_locations
  - [ ] audit_log

---

## File Upload & Configuration

### 5. Upload Files
- [ ] Navigate to **Files** in Plesk
- [ ] Go to `httpdocs/` (or `public_html/`)
- [ ] Upload all PHP files maintaining directory structure:
  ```
  httpdocs/
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
  └── .env.sample
  ```

### 6. Configure Environment
- [ ] Copy `.env.sample` to `.env` via File Manager
- [ ] Edit `.env` file with correct values:

```env
APP_ENV=production
SITE_URL=https://your-domain.com
DB_HOST=localhost
DB_NAME=phone_monitor
DB_USER=pm_user
DB_PASS=your_secure_password
SESSION_NAME=pm_session
CSRF_KEY=your_generated_csrf_key_here
REQUIRE_CONSENT=true
GOOGLE_MAPS_API_KEY=your_maps_api_key_here
```

- [ ] Generate CSRF key (32+ characters):
  ```bash
  # SSH into server or use Terminal in Plesk
  openssl rand -hex 32
  ```
- [ ] Paste generated key into `.env` as `CSRF_KEY`

### 7. Set File Permissions
- [ ] In File Manager, set permissions:
  - `.env`: **600** (owner read/write only)
  - `*.php`: **644** (owner write, all read)
  - Directories: **755** (owner write, all read/execute)

### 8. Protect Sensitive Files
- [ ] Create or edit `.htaccess` in `httpdocs/`:
  ```apache
  # Protect .env file
  <Files ".env">
      Require all denied
  </Files>
  
  # Protect install.sql
  <Files "install.sql">
      Require all denied
  </Files>
  ```
- [ ] Save and verify protection by trying to access:
  - https://your-domain.com/.env (should show 403 Forbidden)

---

## PHP Configuration

### 9. Configure PHP Settings
- [ ] Navigate to **Hosting Settings** in Plesk
- [ ] PHP version: Select **8.3.x**
- [ ] PHP settings (click **PHP Settings**):
  ```ini
  display_errors = Off
  log_errors = On
  error_log = /var/www/vhosts/your-domain/logs/php_error.log
  session.cookie_httponly = On
  session.cookie_secure = On
  session.use_strict_mode = On
  expose_php = Off
  ```
- [ ] Ensure required extensions enabled:
  - [x] pdo
  - [x] pdo_mysql
  - [x] mbstring
  - [x] json
  - [x] openssl
  - [x] session

---

## SSL/HTTPS Setup

### 10. Install SSL Certificate
- [ ] Navigate to **SSL/TLS Certificates**
- [ ] Option A - Let's Encrypt (recommended):
  - Click **Install** next to Let's Encrypt
  - Select domain and www subdomain
  - Agree to terms
  - Click **Get it free**
- [ ] Option B - Upload purchased certificate:
  - Upload certificate, private key, and CA bundle
  - Assign to domain

### 11. Force HTTPS
- [ ] In **Hosting Settings**:
  - [x] **Permanent SEO-safe 301 redirect from HTTP to HTTPS**
- [ ] Add HSTS header (in Apache & nginx Settings → Additional directives):
  ```apache
  Header always set Strict-Transport-Security "max-age=31536000; includeSubDomains"
  ```

---

## Security Hardening

### 12. Change Default Password
- [ ] Navigate to `https://your-domain.com/login.php`
- [ ] Log in with default credentials:
  - Username: `admin`
  - Password: `changeme123`
- [ ] Generate new password hash:
  ```bash
  # SSH or Plesk Terminal
  php -r "echo password_hash('YourNewSecurePassword', PASSWORD_DEFAULT);"
  ```
- [ ] Update database:
  - Open phpMyAdmin
  - Select `phone_monitor` database
  - Go to `users` table
  - Edit `admin` row
  - Replace `password_hash` with new hash
  - Save
- [ ] Test login with new password

### 13. Configure Firewall
- [ ] In Plesk **Tools & Settings** → **Firewall**:
  - Allow: HTTP (80), HTTPS (443), SSH (22)
  - Block all other incoming by default
- [ ] Consider fail2ban for brute force protection

### 14. Set Up Monitoring
- [ ] Enable **Log Browser** in Plesk
- [ ] Check error logs location:
  - PHP errors: `/var/www/vhosts/your-domain/logs/php_error.log`
  - Access log: `/var/www/vhosts/your-domain/logs/access_log`
- [ ] Set up log rotation if not already configured

---

## Google Maps API (Optional)

### 15. Configure Maps API
- [ ] Go to [Google Cloud Console](https://console.cloud.google.com/)
- [ ] Create new project or select existing
- [ ] Enable **Maps JavaScript API**
- [ ] Create API Key:
  - Navigate to **Credentials**
  - Click **Create Credentials** → **API Key**
- [ ] Restrict API Key:
  - **Application restrictions**: HTTP referrers
  - Add: `https://your-domain.com/*`
  - **API restrictions**: Maps JavaScript API only
- [ ] Copy API key to `.env`:
  ```env
  GOOGLE_MAPS_API_KEY=AIzaSy...your_key_here
  ```
- [ ] Save and restart PHP-FPM

---

## Testing

### 16. Test Web Dashboard
- [ ] Access `https://your-domain.com/login.php`
- [ ] Verify:
  - [ ] Page loads without errors
  - [ ] CSS styles applied correctly
  - [ ] Login form visible
  - [ ] CSRF token present in form
- [ ] Log in with admin credentials
- [ ] Verify:
  - [ ] Redirect to dashboard
  - [ ] Dashboard loads
  - [ ] No devices shown initially
  - [ ] Logout works

### 17. Test API Endpoints
Using curl or Postman:

```bash
# Test registration
curl -X POST https://your-domain.com/api/register.php \
  -H "Content-Type: application/json" \
  -d '{
    "device_uuid": "test-uuid-12345",
    "display_name": "Test Device",
    "owner_name": "Test User",
    "consent": true
  }'

# Expected response: {"success":true,"message":"Device registered",...}

# Test ping
curl -X POST https://your-domain.com/api/ping.php \
  -H "Content-Type: application/json" \
  -d '{
    "device_uuid": "test-uuid-12345",
    "battery": 85,
    "free_storage": 32.5
  }'

# Expected response: {"success":true,"message":"Ping received",...}
```

- [ ] Verify test device appears in dashboard
- [ ] Verify data is correct

---

## Android App Configuration

### 18. Build Android App
- [ ] On development machine:
  - Open Android Studio
  - Load `android/` directory
- [ ] Edit `DevicePreferences.kt`:
  ```kotlin
  private const val DEFAULT_SERVER_URL = "https://your-domain.com"
  ```
- [ ] Build APK:
  ```bash
  cd android
  ./gradlew assembleDebug
  ```
- [ ] Locate APK:
  - `android/app/build/outputs/apk/debug/app-debug.apk`

### 19. Test Android App
- [ ] Install APK on test device
- [ ] Launch app
- [ ] Verify consent screen displays
- [ ] Fill in:
  - Server URL: `https://your-domain.com`
  - Device Name: "Test Phone"
  - Owner Name: "Your Name"
- [ ] Check consent checkbox
- [ ] Tap "I Accept"
- [ ] Verify:
  - [ ] Registration succeeds
  - [ ] Main screen displays
  - [ ] Notification appears in status bar
- [ ] Check dashboard:
  - [ ] Device appears in list
  - [ ] Battery and storage data visible
  - [ ] Last seen updated

---

## Post-Deployment

### 20. Backups
- [ ] Set up automated database backups:
  - Plesk → **Backup Manager**
  - Schedule: Daily
  - Retention: 7 days minimum
  - Include: Database only (files rarely change)
- [ ] Test backup restoration procedure

### 21. Monitoring & Maintenance
- [ ] Schedule regular tasks:
  - [ ] Weekly: Review audit logs
  - [ ] Monthly: Rotate CSRF key
  - [ ] Monthly: Review and clean old location data
  - [ ] Quarterly: Update passwords
  - [ ] As needed: PHP/MySQL updates

### 22. Documentation
- [ ] Document your specific configuration:
  - Database credentials (in password manager)
  - CSRF key (in password manager)
  - Admin credentials (in password manager)
  - Google Maps API key (in password manager)
  - Backup schedule and retention policy

---

## Cleanup

### 23. Remove Installation Files
- [ ] Delete or move `install.sql` outside web root
- [ ] Delete `README.md` from web root (keep locally)
- [ ] Delete `.git` directory if present
- [ ] Verify `.env.sample` cannot be accessed via web

---

## Final Verification

### 24. Security Audit
- [ ] Run security scan (e.g., Sucuri SiteCheck)
- [ ] Verify HTTPS working: https://www.ssllabs.com/ssltest/
- [ ] Test forms for CSRF protection
- [ ] Verify `.env` not accessible
- [ ] Check file permissions one final time

### 25. Launch Checklist
- [ ] HTTPS enforced ✅
- [ ] Default password changed ✅
- [ ] API endpoints tested ✅
- [ ] Android app tested ✅
- [ ] Backups configured ✅
- [ ] Monitoring enabled ✅
- [ ] Documentation complete ✅

---

## Support Contacts

- **Plesk Support**: https://support.plesk.com/
- **Let's Encrypt**: https://letsencrypt.org/docs/
- **Google Maps API**: https://developers.google.com/maps/documentation

---

## Troubleshooting

### Common Issues

**Database connection failed**
- Check `.env` credentials match database
- Verify MySQL is running: `systemctl status mysql`
- Check database user permissions

**White screen (no errors)**
- Enable PHP error display temporarily
- Check PHP error log: `/var/www/vhosts/your-domain/logs/php_error.log`
- Verify PHP 8.3 is active

**API returns 500 error**
- Check PHP-FPM is running
- Review error logs
- Verify file permissions
- Test with simpler endpoint first

**Android app cannot connect**
- Verify HTTPS certificate is valid
- Check server URL in app (include `https://`)
- Test API with curl from same network
- Check firewall allows HTTPS (443)

---

**Deployment Date**: _____________

**Deployed By**: _____________

**Server IP**: _____________

**Domain**: _____________

**Notes**:
_____________________________________
_____________________________________
_____________________________________
