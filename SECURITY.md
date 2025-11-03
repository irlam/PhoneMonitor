# Security Policy

## Overview

PhoneMonitor is designed as a **consent-based family monitoring tool**. Security and privacy are paramount. This document outlines security best practices and how to report vulnerabilities.

## Ethical Use Requirements

This application is designed ONLY for:
- ✅ Transparent family device monitoring
- ✅ With explicit, informed consent
- ✅ With visible notifications at all times
- ✅ With optional, toggleable location sharing

**Never use this application for:**
- ❌ Covert surveillance
- ❌ Tracking without consent
- ❌ Any unauthorized monitoring
- ❌ Violating privacy laws or regulations

## Security Best Practices

### Server Security

#### 1. HTTPS/TLS
- **Required**: Always use HTTPS in production
- Never run without SSL certificate
- Use Let's Encrypt (free) or commercial certificate
- Enable HSTS (HTTP Strict Transport Security):
  ```apache
  Header always set Strict-Transport-Security "max-age=31536000; includeSubDomains"
  ```

#### 2. Database Security
- Use strong, unique database passwords (minimum 16 characters)
- Limit database user privileges to only what's needed:
  ```sql
  GRANT SELECT, INSERT, UPDATE, DELETE ON phone_monitor.* TO 'pm_user'@'localhost';
  ```
- Never expose database to public internet
- Use localhost connection when possible
- Regular backups with encryption

#### 3. PHP Configuration
- Set `display_errors = Off` in production
- Enable `log_errors = On`
- Set `expose_php = Off`
- Configure appropriate `memory_limit` and `upload_max_filesize`
- Keep PHP updated to latest 8.3.x version

#### 4. Authentication & Sessions
- **Change default admin password immediately**
- Use strong passwords (minimum 12 characters, mixed case, numbers, symbols)
- Generate password hash:
  ```php
  php -r "echo password_hash('YourStrongPassword123!', PASSWORD_DEFAULT);"
  ```
- Rotate session keys and CSRF keys regularly
- Set appropriate session timeout
- Use `session.cookie_secure = 1` (HTTPS only)
- Use `session.cookie_httponly = 1`

#### 5. CSRF Protection
- Generate strong CSRF key:
  ```bash
  php -r "echo bin2hex(random_bytes(32));"
  ```
- Rotate key periodically (every 90 days recommended)
- All POST forms must include CSRF token

#### 6. Input Validation & Output Escaping
- All user inputs are validated server-side
- SQL injection prevented via PDO prepared statements
- XSS prevented via proper output escaping
- API rate limiting enabled

#### 7. File Permissions
```bash
# Web files
chmod 644 *.php
chmod 600 .env
chmod 755 api assets includes

# Directories
chmod 755 web/
```

#### 8. Web Server Configuration

**Apache (.htaccess)**:
```apache
# Disable directory listing
Options -Indexes

# Protect .env file
<Files .env>
    Require all denied
</Files>

# Security headers
Header always set X-Frame-Options "DENY"
Header always set X-Content-Type-Options "nosniff"
Header always set X-XSS-Protection "1; mode=block"
Header always set Referrer-Policy "strict-origin-when-cross-origin"
Header always set Permissions-Policy "geolocation=(self), microphone=(), camera=()"
```

**Nginx**:
```nginx
# Deny access to .env
location ~ /\.env {
    deny all;
}

# Security headers
add_header X-Frame-Options "DENY" always;
add_header X-Content-Type-Options "nosniff" always;
add_header X-XSS-Protection "1; mode=block" always;
add_header Referrer-Policy "strict-origin-when-cross-origin" always;
```

### Android Security

#### 1. App Signing
- Use proper keystore for release builds
- Never commit keystore to repository
- Keep keystore password secure

#### 2. Network Security
- Use HTTPS for all API calls
- Implement certificate pinning for production (optional but recommended)
- Validate SSL certificates

#### 3. Data Storage
- Device UUID stored in SharedPreferences
- No sensitive data in app storage
- Use EncryptedSharedPreferences for sensitive settings (optional enhancement)

#### 4. Permissions
- Request only necessary permissions
- Request runtime permissions appropriately
- Explain permission usage clearly to users
- Background location only if location sharing enabled

#### 5. API Communication
- Use secure connection (HTTPS)
- Validate server responses
- Handle revoked device status (403 error)
- Implement exponential backoff for retries

### Data Privacy & Retention

#### 1. Minimize Data Collection
- Only collect necessary information
- Battery level, storage, device info, optional location
- No personal data beyond what user provides

#### 2. Data Retention
Set up automatic cleanup of old data:

```sql
-- Delete location history older than 90 days
DELETE FROM device_locations 
WHERE created_at < DATE_SUB(NOW(), INTERVAL 90 DAY);

-- Delete audit logs older than 1 year
DELETE FROM audit_log 
WHERE created_at < DATE_SUB(NOW(), INTERVAL 1 YEAR);
```

Add to cron:
```bash
# Daily cleanup at 2 AM
0 2 * * * mysql -u user -p'password' phone_monitor -e "DELETE FROM device_locations WHERE created_at < DATE_SUB(NOW(), INTERVAL 90 DAY);"
```

#### 3. Right to Delete
Users can request complete data deletion:

```sql
-- Delete all data for specific device
DELETE FROM device_locations WHERE device_id = ?;
DELETE FROM audit_log WHERE device_id = ?;
DELETE FROM devices WHERE id = ?;
```

#### 4. Access Logs
- Review access logs regularly
- Monitor for suspicious activity
- Set up alerts for unusual patterns

### API Security

#### 1. Rate Limiting
- Implemented in API endpoints
- Register: 5 requests per 5 minutes per device
- Ping: 30 requests per 60 seconds per device
- Prevents abuse and DoS

#### 2. Input Validation
- All inputs validated and sanitized
- UUID format validation
- Coordinate bounds checking
- Type checking for all fields

#### 3. Authentication
- Device UUID acts as authentication token
- Consent requirement enforced
- Revoked devices receive 403 error

### Monitoring & Auditing

#### 1. Audit Logging
- All device registrations logged
- Device revocations logged
- Failed login attempts logged
- Review audit logs regularly

#### 2. Error Monitoring
- Monitor PHP error logs
- Set up alerts for critical errors
- Review server access logs

#### 3. Database Monitoring
- Monitor for unusual query patterns
- Check for failed login attempts
- Track device registration/unregistration

### Backup & Recovery

#### 1. Regular Backups
```bash
# Daily database backup
mysqldump -u user -p'password' phone_monitor > backup_$(date +%Y%m%d).sql

# Compress and encrypt
tar -czf backup_$(date +%Y%m%d).tar.gz backup_$(date +%Y%m%d).sql
openssl enc -aes-256-cbc -salt -in backup_$(date +%Y%m%d).tar.gz -out backup_$(date +%Y%m%d).tar.gz.enc
rm backup_$(date +%Y%m%d).sql backup_$(date +%Y%m%d).tar.gz
```

#### 2. Backup Retention
- Keep daily backups for 7 days
- Keep weekly backups for 4 weeks
- Keep monthly backups for 12 months

#### 3. Test Restores
- Test backup restoration quarterly
- Document recovery procedures
- Keep backup encryption keys secure

### Compliance Considerations

#### GDPR (if applicable)
- Obtain explicit consent
- Provide data access upon request
- Enable data deletion
- Maintain data processing records
- Notify users of data breaches

#### CCPA (if applicable)
- Disclose data collection practices
- Honor deletion requests
- Provide opt-out mechanisms

## Vulnerability Disclosure

### Reporting a Vulnerability

If you discover a security vulnerability, please report it responsibly:

1. **Do NOT** disclose publicly until fixed
2. Email details to: [your-security-email@example.com]
3. Include:
   - Description of vulnerability
   - Steps to reproduce
   - Potential impact
   - Suggested fix (if any)

### What to Expect

- Acknowledgment within 48 hours
- Assessment within 7 days
- Fix timeline communicated
- Credit given (if desired)
- Public disclosure after fix deployed

### Scope

**In scope:**
- Web dashboard vulnerabilities
- API security issues
- Android app security issues
- Authentication/authorization bypasses
- SQL injection
- XSS vulnerabilities
- CSRF issues

**Out of scope:**
- Social engineering attacks
- Physical attacks
- DDoS attacks
- Issues in third-party dependencies (report to them directly)

## Security Updates

### Update Schedule
- Security patches: As needed (immediately for critical)
- Dependency updates: Monthly
- PHP/MySQL updates: Follow vendor schedule

### Staying Updated
- Watch GitHub repository for security advisories
- Subscribe to PHP security announcements
- Monitor Android security bulletins
- Review Google Play Services updates

## Security Checklist

Use this checklist when deploying:

- [ ] HTTPS enabled with valid certificate
- [ ] Default admin password changed
- [ ] Strong database password set
- [ ] CSRF key generated and set
- [ ] `.env` file protected from web access
- [ ] Directory listing disabled
- [ ] PHP error display disabled in production
- [ ] Security headers configured
- [ ] Database user privileges minimized
- [ ] File permissions set correctly
- [ ] Backups configured and tested
- [ ] Audit logging enabled
- [ ] Data retention policy implemented
- [ ] Google Maps API key restricted
- [ ] Android app using HTTPS only
- [ ] All dependencies up to date

## Contact

For security concerns: [Create a GitHub Security Advisory]

---

**Security is everyone's responsibility. If you see something, say something.**
