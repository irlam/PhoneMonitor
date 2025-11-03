# Security Policy

## Overview

PhoneMonitor is designed with security and privacy as core principles. This document outlines our security practices and how to report vulnerabilities.

## Security Principles

### 1. Transparency First
- All functionality is clearly documented
- No hidden or stealth features
- Always-visible notification when active
- Users can view exactly what data is shared

### 2. Consent-Based
- Explicit user consent required before any data collection
- Users can revoke consent at any time
- Clear uninstallation process
- No forced enrollment

### 3. Data Minimization
- Only essential data is collected:
  - Battery level
  - Free storage space
  - Optional location (requires separate permission)
  - Device status updates
- No access to personal data, messages, calls, or media
- No keylogging, screenshots, or surveillance capabilities

### 4. Secure by Design
- PDO prepared statements prevent SQL injection
- password_hash() / password_verify() for authentication
- CSRF tokens on all POST forms
- Input validation and output escaping
- HTTPS enforcement recommended
- Rate limiting on API endpoints

---

## Security Measures

### Authentication & Authorization

**Password Storage:**
- Passwords hashed using `password_hash()` with bcrypt (cost factor 10)
- Never stored in plaintext
- Default password must be changed on first use

**Session Management:**
- Secure session cookies (HttpOnly, SameSite=Strict)
- Session ID regeneration on login
- Automatic session timeout
- Secure flag enabled when using HTTPS

**CSRF Protection:**
- All state-changing requests require valid CSRF token
- Tokens are cryptographically random (32 bytes)
- Token validation uses timing-safe comparison

### Database Security

**PDO Prepared Statements:**
```php
// Example from code
$stmt = $pdo->prepare("SELECT * FROM devices WHERE device_uuid = ?");
$stmt->execute([$deviceUuid]);
```
- Prevents SQL injection attacks
- All user input properly escaped

**Database Permissions:**
- Use dedicated database user with minimal required permissions
- No DROP, CREATE, or ALTER permissions needed in production
- Read/write access only to application tables

**Data Encryption:**
- Sensitive configuration in `.env` file (600 permissions)
- Database connection over localhost only (recommended)
- Consider database encryption at rest for sensitive deployments

### API Security

**Rate Limiting:**
- Registration endpoint: 1 request per minute per IP
- Ping endpoint: 1 request per 10 seconds per IP
- Simple file-based implementation (can be enhanced)

**Input Validation:**
- UUID format validation (regex)
- Coordinate bounds checking (-90 to 90, -180 to 180)
- String length limits enforced
- Type checking on all inputs

**Authentication:**
- Device UUID acts as bearer token
- Revoked devices receive 403 responses
- No authentication for API endpoints (device UUID is credential)

### Android App Security

**Permissions:**
- Minimal permissions requested
- Location permission is optional
- Runtime permission requests with clear explanations
- Background location only if strictly necessary

**Network Security:**
- HTTPS enforced (Retrofit)
- Certificate pinning recommended for production
- Request/response logging only in debug builds

**Data Storage:**
- SharedPreferences for non-sensitive config
- Device UUID generated locally (UUIDv4)
- No hardcoded secrets in code

### Web Security

**Headers:**
Recommended security headers (configure in Apache/Nginx):
```apache
Header always set X-Content-Type-Options "nosniff"
Header always set X-Frame-Options "DENY"
Header always set X-XSS-Protection "1; mode=block"
Header always set Referrer-Policy "strict-origin-when-cross-origin"
Header always set Strict-Transport-Security "max-age=31536000; includeSubDomains"
```

**XSS Prevention:**
- All output escaped with `htmlspecialchars()`
- JSON responses use `json_encode()`
- Content-Type headers set correctly

**File Upload:**
- No file upload functionality (attack surface minimized)

---

## Audit & Monitoring

### Audit Log

All significant actions logged to `audit_log` table:
- User logins
- Device registration/unregistration
- Device revocation
- System events

Query audit log:
```sql
SELECT 
    al.created_at,
    al.action,
    u.username,
    d.display_name,
    al.meta
FROM audit_log al
LEFT JOIN users u ON al.user_id = u.id
LEFT JOIN devices d ON al.device_id = d.id
ORDER BY al.created_at DESC
LIMIT 100;
```

### Location Privacy

**Append-Only Logs:**
- `device_locations` table is append-only
- Maintains history for audit purposes
- Implement retention policy (e.g., 30 days)

**Access Control:**
- Location data only visible to authenticated admin users
- No public API endpoints expose location data

**Deletion:**
```sql
-- Delete location data older than 30 days
DELETE FROM device_locations 
WHERE created_at < DATE_SUB(NOW(), INTERVAL 30 DAY);
```

---

## Deployment Security

### HTTPS/TLS

**Required for Production:**
- Obtain SSL/TLS certificate (Let's Encrypt recommended)
- Configure HTTPS in web server
- Enable HSTS header
- Redirect HTTP to HTTPS

### Environment Configuration

**Secure .env File:**
```bash
# Set restrictive permissions
chmod 600 .env
chown www-data:www-data .env

# Prevent web access (in .htaccess)
<Files ".env">
    Require all denied
</Files>
```

**Secret Generation:**
```bash
# Generate strong CSRF key
openssl rand -hex 32

# Generate strong password
openssl rand -base64 32
```

### Server Hardening

**PHP Configuration:**
```ini
# php.ini recommendations
expose_php = Off
display_errors = Off
log_errors = On
session.cookie_httponly = 1
session.cookie_secure = 1
session.use_strict_mode = 1
```

**File Permissions:**
```bash
# PHP files
chmod 644 *.php

# Directories
chmod 755 api/
chmod 755 assets/

# Environment file
chmod 600 .env
```

---

## Vulnerability Reporting

### Responsible Disclosure

If you discover a security vulnerability, please report it responsibly:

**DO:**
- Email security details to the repository maintainer
- Provide detailed reproduction steps
- Allow reasonable time for fix before public disclosure
- Coordinate on disclosure timeline

**DON'T:**
- Publicly disclose before patch is available
- Exploit vulnerability maliciously
- Test on production systems without permission

### What to Report

We're interested in hearing about:
- SQL injection vulnerabilities
- Cross-site scripting (XSS)
- Cross-site request forgery (CSRF) bypasses
- Authentication/authorization bypass
- Insecure cryptographic practices
- Information disclosure
- Remote code execution
- Privilege escalation

### Response Timeline

- **24 hours:** Initial acknowledgment
- **7 days:** Preliminary assessment
- **30 days:** Fix development and testing
- **60 days:** Patch release and coordinated disclosure

---

## Security Checklist

### Before Deployment

- [ ] HTTPS/SSL certificate installed
- [ ] HSTS header enabled
- [ ] Default admin password changed
- [ ] Strong CSRF key generated (32+ chars)
- [ ] `.env` file permissions set to 600
- [ ] Database user has minimal permissions
- [ ] Database accessible only from localhost
- [ ] Error reporting disabled in production
- [ ] Security headers configured
- [ ] Rate limiting tested
- [ ] Audit logging verified

### Regular Maintenance

- [ ] Review audit logs weekly
- [ ] Rotate CSRF key monthly
- [ ] Update admin passwords quarterly
- [ ] Delete old location data (retention policy)
- [ ] Review and revoke unused devices
- [ ] Check for SQL/PHP/library updates
- [ ] Verify backup integrity
- [ ] Test disaster recovery procedure

### Android App

- [ ] Server URL uses HTTPS only
- [ ] API key not hardcoded in app
- [ ] Location permission is optional
- [ ] Consent screen displayed on first run
- [ ] Notification always visible when active
- [ ] ProGuard rules configured for release
- [ ] Debug logging disabled in release builds

---

## Known Limitations

### Rate Limiting

Current implementation uses simple file-based IP tracking. For production at scale:
- Consider Redis/Memcached for distributed rate limiting
- Implement per-user rate limits in addition to IP-based
- Use fail2ban or ModSecurity for automated blocking

### Authentication

Device UUID serves as bearer token:
- Secure if kept private (stored in app only)
- Risk if device is rooted and UUID extracted
- Consider implementing rotating tokens for enhanced security

### Location Privacy

Location data is sensitive:
- Only collect if user explicitly enables
- Implement strict retention policy
- Consider additional encryption at rest
- Provide clear deletion mechanism

---

## Compliance

### GDPR Considerations

If deploying in EU or handling EU users' data:
- Obtain explicit consent (✓ implemented)
- Provide data export capability
- Implement data deletion on request
- Maintain data processing records
- Designate data protection officer if required

### Family Educational Rights and Privacy Act (FERPA)

If used in educational settings:
- Ensure parental consent for minors
- Limit data collection to minimum necessary
- Implement secure data storage and access controls

---

## Updates

This security policy is reviewed quarterly and updated as needed.

**Last Updated:** 2024-11-03

---

**Remember:** Security is an ongoing process, not a one-time setup. Regular audits, updates, and monitoring are essential for maintaining a secure system.
