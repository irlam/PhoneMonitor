# Family Device Helper — One-Click Scaffold (Plesk PHP + Android, with Location)

**Purpose:**  
A consent-based family helper: an Android app shares basic device status (battery, free space, note, last-seen) and *optional* location with a private PHP/MySQL dashboard.  
No stealth, no remote control. Location sharing is optional, requires consent, and always shows an active notification.

---

## Deliverables — generate EVERYTHING

### 1️⃣ Web Backend (PHP 8.3 + MySQL 8, Plesk-ready, no framework)

**Files**
config.php
db.php
auth.php
csrf.php
login.php
logout.php
dashboard.php
devices.php
device_view.php
api/register.php
api/ping.php
api/unregister.php
assets/css/site.css
assets/js/site.js
install.sql
.env.sample
README.md
SECURITY.md

pgsql
Copy code

**Security**
- PDO prepared statements  
- `password_hash()` / `password_verify()`  
- CSRF tokens for POST forms  
- Input validation & output escaping  
- Basic rate-limiting

**Features**
- Admin login & session  
- Device list (owner, last-seen, consent flag, online/offline badge)  
- Revoke device button  
- Audit log table  

**Location (Web)**
- `install.sql` includes a `device_locations` table (append-only) and keeps the latest point in `devices.last_payload`.  
- `device_view.php` shows a **Google Map** (using Maps JS API key from `.env`) with a marker for the last known location and a short recent-points table.  
- If the key is missing, show a clear “Map unavailable – add GOOGLE_MAPS_API_KEY” message.

---

### 2️⃣ Android Client (Kotlin 1.9 / AGP 8 / Gradle 8)

**Targets**
- `minSdk 23`, `targetSdk 34`, Java 11 toolchain  
- AndroidX, Material 3, WorkManager, Retrofit 2 + OkHttp 3 + Gson

**Screens & Components**
- **ConsentActivity** – first-run explicit opt-in explaining what is shared  
- **DeviceId** – UUID v4 stored in `SharedPreferences`  
- **ApiService** – Retrofit interface for `/api/register.php`, `/api/ping.php`, `/api/unregister.php`  
- **HeartbeatWorker** – WorkManager periodic job (default 30 min) with visible **foreground notification** “Family sharing active”  
- **SettingsActivity** – server URL, name/owner, toggle “Share location”

**Location (App)**
- Optional; disabled by default.  
- Requests `ACCESS_FINE_LOCATION` (and background only if strictly required).  
- Uses **FusedLocationProviderClient**; attaches last known fix to each ping.  
- Fields added to ping JSON:  
  `{lat, lon, accuracy, provider, loc_ts}` (milliseconds since epoch).  

**API Calls**
- `POST /api/register.php` → `{device_uuid, display_name, owner_name, consent:true}`  
- `POST /api/ping.php` → `{device_uuid, battery, free_storage, note, lat?, lon?, accuracy?, provider?, loc_ts?}`  
- `POST /api/unregister.php` → `{device_uuid}` → marks device revoked

---

### 3️⃣ Database Schema (install.sql)

**Tables**
- `users(id, username UNIQUE, password_hash, name, created_at)`
- `devices(id, device_uuid UNIQUE, display_name, owner_name, registered_at, last_seen, last_payload JSON, consent_given TINYINT, revoked TINYINT)`
- `audit_log(id, device_id FK, user_id FK, action, meta JSON, created_at)`
- `device_locations`:
  ```sql
  id INT AUTO_INCREMENT PRIMARY KEY,
  device_id INT NOT NULL,
  lat DECIMAL(9,6) NOT NULL,
  lon DECIMAL(9,6) NOT NULL,
  accuracy FLOAT NULL,
  provider VARCHAR(32) NULL,
  loc_ts TIMESTAMP NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX (device_id, created_at),
  FOREIGN KEY (device_id) REFERENCES devices(id) ON DELETE CASCADE
Seed admin user (username=admin, placeholder password hash); README shows how to generate a real hash with PHP.

4️⃣ .env.sample
ini
Copy code
APP_ENV=development
SITE_URL=https://your-domain.example
DB_HOST=localhost
DB_NAME=phone_monitor
DB_USER=changeme
DB_PASS=changeme
SESSION_NAME=pm_session
CSRF_KEY=generate_a_long_random_key
REQUIRE_CONSENT=true
GOOGLE_MAPS_API_KEY=put_your_browser_key_here
5️⃣ Dashboard Mapping
Load Maps JS using the key from .env.

Show a centered marker for the latest location; include “View on Google Maps” link.

Display the 10 most recent location rows below the map.

Style: Tabler / AdminLTE, green accent #22bb66.

6️⃣ GitHub Actions
php-ci.yml – setup-php 8.3 → Composer install → PHPStan lvl 5 → PHPUnit (stub) → CodeQL (php)

android-build.yml – JDK 11 → Gradle cache → assembleDebug → upload APK artifact → CodeQL (kotlin)

7️⃣ Codespaces (dev only)
.devcontainer/devcontainer.json with PHP 8.3 + Composer and MySQL 8 service

Forwards ports 8080 (web) + 3306 (db)

postCreateCommand: composer install || true

8️⃣ README .md
Must include:

Purpose & ethics (family-only, consent, visible notification, uninstall steps)

Plesk deploy guide: create DB → upload files → import install.sql → set .env → open /login.php

Getting a Google Maps API key & adding it to .env

Android build: set base URL → build → install APK

Security hardening: HTTPS/HSTS, strong admin password, rotate CSRF key, logs retention

Troubleshooting tips (PHP version, DB charset, Maps key errors)

9️⃣ Guardrails
No stealth/hidden features.

No keylogging, camera, microphone, or remote shell.

Always show consent screen and visible “Sharing active” notification.

Location optional, toggleable, and clearly indicated.

🔟 Acceptance Criteria
Works on Plesk PHP 8.3 (no Docker).

Devices can register / ping / unregister.

Map shows last known location when available.

Revoked devices get 403 / {error:"revoked"}.

CI workflows succeed; APK artifact downloadable.

1️⃣1️⃣ Scaffolding Tasks (perform in order)
Create PHP files + folders per structure above.

Generate install.sql (incl. device_locations).

Implement .env.sample loader → PDO helper.

Add csrf.php and use in POST forms.

Build login/logout/auth and protect routes.

Build dashboard (+ map + recent points table).

Build API endpoints (register/ping/unregister, with location).

Write README + SECURITY docs.

Add Android modules (consent screen, settings, WorkManager, location toggle).

Add GitHub Actions + CodeQL and a devcontainer for Codespaces (dev only).

Focus: consent-based monitoring only — no stealth, no control, no covert logging.
Produce fully self-contained PHP and Kotlin sources, ready for Plesk deploy and Android Studio build.
