# Plesk Deployment Checklist

Use this checklist when deploying PhoneMonitor to a Plesk server.

## Pre-Deployment

- [ ] Server meets minimum requirements:
  - [ ] PHP 8.3 or higher installed
  - [ ] MySQL 8.0+ or MariaDB 10.5+ available
  - [ ] SSL certificate available (Let's Encrypt or commercial)
  - [ ] Sufficient disk space (minimum 500MB recommended)

## Database Setup

- [ ] Create new MySQL database
  - Database name: `phone_monitor`
  - Character set: `utf8mb4`
  - Collation: `utf8mb4_unicode_ci`

- [ ] Create database user
  - Username: (choose secure name)
  - Password: (generate strong password, min 16 chars)
  - Privileges: `SELECT`, `INSERT`, `UPDATE`, `DELETE` on `phone_monitor.*`

- [ ] Test database connection
  ```bash
  mysql -u username -p phone_monitor
  ```

## File Upload

- [ ] Upload web files to document root (e.g., `/httpdocs/`)
  - Use FTP, SFTP, or Plesk File Manager
  - Upload entire `web/` directory contents
  - Verify all files transferred successfully

- [ ] Verify directory structure
  ```
  /httpdocs/
  ├── api/
  ├── assets/
  │   ├── css/
  │   └── js/
  ├── includes/
  ├── .env.sample
  ├── *.php files
  └── install.sql
  ```

## Environment Configuration

- [ ] Copy `.env.sample` to `.env`
  ```bash
  cp .env.sample .env
  ```

- [ ] Edit `.env` file with your values:
  - [ ] `APP_ENV=production`
  - [ ] `SITE_URL=https://your-domain.com` (your actual domain)
  - [ ] `DB_HOST=localhost`
  - [ ] `DB_NAME=phone_monitor`
  - [ ] `DB_USER=your_db_user`
  - [ ] `DB_PASS=your_db_password`
  - [ ] `SESSION_NAME=pm_session` (or customize)
  - [ ] `CSRF_KEY=` (generate with: `php -r "echo bin2hex(random_bytes(32));"`)
  - [ ] `REQUIRE_CONSENT=true`
  - [ ] `GOOGLE_MAPS_API_KEY=` (get from Google Cloud Console)

- [ ] Protect `.env` file
  ```bash
  chmod 600 .env
  ```

## Database Import

- [ ] Import database schema using phpMyAdmin:
  1. Navigate to Databases → your database → phpMyAdmin
  2. Select `phone_monitor` database
  3. Go to Import tab
  4. Choose `install.sql` file
  5. Click "Go"
  6. Verify tables created: `users`, `devices`, `device_locations`, `audit_log`

- [ ] OR import via command line:
  ```bash
  mysql -u your_db_user -p phone_monitor < install.sql
  ```

- [ ] Verify default admin user created:
  ```sql
  SELECT username FROM users WHERE username = 'admin';
  ```

## File Permissions

- [ ] Set correct permissions:
  ```bash
  cd /httpdocs/
  
  # Files readable
  chmod 644 *.php
  chmod 600 .env
  
  # Directories executable
  chmod 755 api assets includes
  
  # Recursive for nested files
  find api -type f -exec chmod 644 {} \;
  find assets -type f -exec chmod 644 {} \;
  find includes -type f -exec chmod 644 {} \;
  ```

## PHP Configuration

- [ ] In Plesk → Websites & Domains → your domain → PHP Settings:
  - [ ] PHP version: **8.3** (or higher)
  - [ ] Enable extensions:
    - [ ] `pdo_mysql`
    - [ ] `mysqli`
    - [ ] `mbstring`
    - [ ] `json`
    - [ ] `session`
  - [ ] Set `memory_limit`: 128M minimum
  - [ ] Set `post_max_size`: 10M
  - [ ] Set `upload_max_filesize`: 10M
  - [ ] Disable `display_errors` (production)
  - [ ] Enable `log_errors`

## SSL/HTTPS Setup

- [ ] In Plesk → SSL/TLS Certificates:
  - [ ] Install SSL certificate (Let's Encrypt recommended):
    - Go to SSL/TLS Certificates
    - Click "Install" for Let's Encrypt
    - Enter email address
    - Select domain and www subdomain
    - Click "Get it free"
  
  - [ ] OR upload commercial certificate if you have one

- [ ] Configure HTTPS redirect:
  - [ ] Enable "Permanent SEO-safe 301 redirect from HTTP to HTTPS"

- [ ] Verify SSL working:
  - [ ] Visit `https://your-domain.com`
  - [ ] Check for padlock icon
  - [ ] Test at https://www.ssllabs.com/ssltest/

## Security Headers (Apache)

- [ ] Create/edit `.htaccess` in document root:
  ```apache
  # Disable directory listing
  Options -Indexes
  
  # Protect .env file
  <Files .env>
      Require all denied
  </Files>
  
  # Security headers
  <IfModule mod_headers.c>
      Header always set X-Frame-Options "DENY"
      Header always set X-Content-Type-Options "nosniff"
      Header always set X-XSS-Protection "1; mode=block"
      Header always set Referrer-Policy "strict-origin-when-cross-origin"
      Header always set Strict-Transport-Security "max-age=31536000; includeSubDomains"
  </IfModule>
  
  # Force HTTPS
  RewriteEngine On
  RewriteCond %{HTTPS} off
  RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]
  ```

## Google Maps API Key

- [ ] Get API key from Google Cloud Console:
  1. Go to https://console.cloud.google.com/
  2. Create project or select existing
  3. Enable Maps JavaScript API
  4. Create credentials → API Key
  5. Restrict key:
     - Application restrictions: HTTP referrers
     - Website restrictions: `https://your-domain.com/*`
     - API restrictions: Maps JavaScript API

- [ ] Add key to `.env`:
  ```
  GOOGLE_MAPS_API_KEY=AIza...your_key_here
  ```

## First Login & Password Change

- [ ] Test website access:
  - [ ] Visit `https://your-domain.com/login.php`
  - [ ] Should see login page

- [ ] Login with default credentials:
  - Username: `admin`
  - Password: `admin123`

- [ ] **IMMEDIATELY change password:**
  1. Generate new password hash:
     ```php
     php -r "echo password_hash('YourNewStrongPassword123!', PASSWORD_DEFAULT);"
     ```
  2. Update in database:
     ```sql
     UPDATE users SET password_hash = '$2y$10$...' WHERE username = 'admin';
     ```
  3. Test new login

## Functionality Testing

- [ ] Test login/logout functionality
- [ ] Test dashboard loads correctly
- [ ] Test database connection (check for errors)
- [ ] Test API endpoints (use curl or register test device)
  ```bash
  curl -X POST https://your-domain.com/api/register.php \
    -H "Content-Type: application/json" \
    -d '{"device_uuid":"test-uuid","display_name":"Test","owner_name":"Test","consent":true}'
  ```

## Android App Configuration

- [ ] Build Android APK:
  1. Edit `android/app/build.gradle`
  2. Set: `buildConfigField "String", "DEFAULT_SERVER_URL", '"https://your-domain.com"'`
  3. Build APK: `./gradlew assembleDebug`

- [ ] Distribute APK to family members
- [ ] Guide users through:
  - [ ] Install APK (enable Unknown Sources)
  - [ ] Accept consent screen
  - [ ] Configure settings (verify server URL)
  - [ ] Grant permissions (location optional)

## Monitoring & Maintenance

- [ ] Set up log monitoring:
  - [ ] PHP error log location: Check Plesk logs
  - [ ] MySQL slow query log (optional)

- [ ] Configure database backups:
  - [ ] In Plesk → Backup Manager
  - [ ] Schedule daily backups
  - [ ] Store offsite if possible

- [ ] Set up data retention:
  - [ ] Create cron job for cleanup:
    ```cron
    # Daily at 2 AM - delete old location data (90 days)
    0 2 * * * mysql -u user -p'pass' phone_monitor -e "DELETE FROM device_locations WHERE created_at < DATE_SUB(NOW(), INTERVAL 90 DAY);"
    ```

- [ ] Monitor disk usage
- [ ] Check for PHP/MySQL updates monthly

## Security Hardening

- [ ] Review SECURITY.md document
- [ ] Implement recommended security headers
- [ ] Set up Fail2Ban (if available)
- [ ] Configure firewall rules
- [ ] Disable unnecessary PHP functions in php.ini:
  ```ini
  disable_functions = exec,passthru,shell_exec,system,proc_open,popen
  ```

## Documentation

- [ ] Document your deployment:
  - [ ] Server details
  - [ ] Database credentials (securely!)
  - [ ] Domain name
  - [ ] Admin password location
  - [ ] Backup procedures
  - [ ] Contact information

## Post-Deployment Verification

- [ ] Verify all pages load without errors
- [ ] Test device registration from Android app
- [ ] Verify location appears on map (if enabled)
- [ ] Check audit log is recording actions
- [ ] Test device revocation
- [ ] Verify notifications work on Android
- [ ] Test unregistration from app

## Troubleshooting Common Issues

### "Database connection failed"
- Check `.env` credentials
- Verify MySQL service running
- Test database connection manually
- Check database user privileges

### "CSRF token validation failed"
- Verify PHP sessions working
- Check session save path writable
- Clear browser cookies
- Regenerate CSRF key

### "Map unavailable"
- Add Google Maps API key to `.env`
- Verify API key restrictions
- Check Maps JavaScript API enabled
- Check browser console for errors

### Android app "Connection failed"
- Verify server URL in app settings
- Check HTTPS certificate valid
- Test API endpoints with curl
- Check firewall not blocking

## Support Resources

- [ ] README.md - Full documentation
- [ ] SECURITY.md - Security guidelines
- [ ] GitHub Issues - Report problems
- [ ] PHP error logs - `/var/log/` or Plesk logs

---

## Final Checklist

Before going live:
- [ ] All items above completed
- [ ] Admin password changed
- [ ] SSL certificate installed and working
- [ ] Backups configured
- [ ] Tested with at least one Android device
- [ ] Security headers configured
- [ ] Documentation saved securely

**Date deployed:** _______________
**Deployed by:** _______________
**Domain:** _______________

---

✅ **Deployment complete!** Monitor the system for the first 24 hours and check logs regularly.
