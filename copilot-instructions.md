# Project: Family Device Helper (consent-based remote status app)

Build a complete, privacy-safe system for family members to share basic device status (battery, last-seen, note) with consent.

---

## 🌐 Web Backend (PHP 8.3 + MySQL, Plesk-ready)

**Goals**
- Plain PHP (no Docker, no framework) deployable on Plesk.
- Use PDO prepared statements, password_hash(), CSRF tokens, and rate-limiting.
- `.env.sample` for DB creds and site URL.
- Bootstrap 5 + Tabler or AdminLTE for UI.

**Core files**
- `/config.php` + `/db.php` (load `.env`, connect via PDO).
- `/auth.php`, `/login.php`, `/logout.php` (session login).
- `/dashboard.php`, `/device_view.php`, `/devices.php` (admin panel).
- `/api/register.php`, `/api/ping.php`, `/api/unregister.php`.
- `/install.sql` (tables: users, devices, audit_log).
- `/assets/css/` + `/assets/js/` (light branding: “DefectTracker Family Helper” green/grey).

**Features**
- Admin login dashboard listing devices, last-seen, consent flag.
- Revoke button sets `revoked=1`.
- Audit log for register/ping/revoke.
- Password hashing, input sanitization, CSRF tokens.
- Mobile-friendly responsive layout.
- Plesk-compatible: runs under `public_html` with no external deps.

---

## 📱 Android Client (Kotlin, Android Studio Giraffe+)

**Purpose**
- Foreground-notified background worker that pings the web API every 30 min.
- Explicit consent screen on first run (“Share basic device status with my family”).
- UUID v4 stored in `SharedPreferences` as `device_uuid`.
- Calls:
  - `POST /api/register.php` `{device_uuid, display_name, owner_name, consent:true}`
  - periodic `POST /api/ping.php` `{device_uuid, battery, free_storage, note}`
  - optional `POST /api/unregister.php` on “Stop sharing”.

**Stack**
- Kotlin 1.9, targetSdk 34, minSdk 23.
- WorkManager + Retrofit2 + OkHttp3 + Gson.
- ForegroundService notification: “Device sharing active”.
- App theme: light/dark; green accent to match web.

---

## ⚙️ Development Environment

**Codespaces**
- PHP 8.3 + Composer, MySQL 8 service.
- Forward ports 8080 (web) and 3306 (db).
- VS Code extensions: Intelephense, Prettier, Docker, PHPStan.
- Post-create: `composer install || true`.

**GitHub Actions**
- `php-ci.yml`: setup-php 8.3, run phpstan/phpunit.
- `android-build.yml`: JDK 11, Gradle 8, assembleDebug, upload APK.
- CodeQL scan for PHP + Kotlin.

**Security.md**
- States project is for private family use only.
- No stealth or remote-control features; full consent required.
- HTTPS mandatory for API traffic.

---

## 🚀 Deliverables

1. Fully working PHP + MySQL backend (upload directly to Plesk).
2. `/install.sql` ready to import via phpMyAdmin.
3. Android Studio project folder `/app/` with simple consent UI and periodic heartbeat.
4. GitHub Actions CI passing.
5. README.md explaining setup, `.env` example, privacy note.

---

## 💡 Branding (match DefectTracker style)
- Header logo: “Family Helper by DefectTracker”.
- Accent color #22bb66.
- Clean, flat dashboard with green badges for “Online” and grey for “Offline”.
- Use Bootstrap Icons or FontAwesome (no external CDN if offline build preferred).

---

## ✅ Acceptance

- Runs on Plesk (PHP 8.3 + MySQL 8) without Docker.
- Admin dashboard reachable at `/dashboard.php` after login.
- Devices register and ping successfully via API.
- All code passes PHPStan level 5 and Android build succeeds.

---

> **Focus:** consent-based monitoring only — no stealth, remote control, or hidden access.  
> Produce self-contained PHP and Kotlin sources ready for deployment and signing.

