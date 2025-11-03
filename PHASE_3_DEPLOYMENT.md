# Phase 3 Deployment Checklist

## 📋 Overview
Phase 3 adds 4 major features:
- ✅ Advanced Analytics Dashboard with Chart.js visualizations
- ✅ CSV/PDF Export for devices, locations, battery data, and reports
- ✅ Telegram & Discord Bot Alerts for instant notifications
- ✅ Custom Alert Rules with condition builder and multi-channel actions

**Status:** All features complete and ready for deployment!

---

## 📦 Files to Upload (15 Total)

### New Backend Services (5 files)
Upload these to your root directory:

1. **AlertRuleService.php** - Alert rule evaluation engine
2. **AnalyticsService.php** - Statistical analysis with caching
3. **DiscordBot.php** - Discord webhook integration
4. **ExportService.php** - CSV/PDF export generation
5. **TelegramBot.php** - Telegram Bot API integration

### New Frontend Pages (2 files)
Upload these to your root directory:

6. **analytics.php** - Analytics dashboard with charts
7. **alert_rules.php** - Alert rules management interface

### New Controllers/Cron (2 files)
Upload these to your root directory:

8. **export.php** - Export controller/router
9. **cron_alert_rules.php** - Scheduled alert rule evaluation

### Database Migration (1 file)
Upload to `/database/migrations/` directory:

10. **004_phase3_features.sql** - Creates 5 new tables

### Modified Frontend Files (5 files)
**IMPORTANT:** These files have been updated with navigation links and export buttons:

11. **dashboard.php** - Added export button and analytics link
12. **device_view.php** - Added export buttons for locations and reports
13. **devices.php** - Updated navigation menu
14. **geofences.php** - Updated navigation menu
15. **setup.php** - Added Phase 3 documentation (Step 5 + 4 feature guides)

---

## 🗄️ Database Setup

### Step 1: Run Migration
Execute the SQL migration to create 5 new tables:

**Via phpMyAdmin:**
1. Open phpMyAdmin
2. Select `phone_monitor` database
3. Click SQL tab
4. Copy contents of `database/migrations/004_phase3_features.sql`
5. Click "Go"

**Via Terminal:**
```bash
mysql -u your_username -p phone_monitor < database/migrations/004_phase3_features.sql
```

### Step 2: Verify Tables Created
Check that these 5 tables exist:
- ✅ `alert_rules` - Custom alert rule definitions
- ✅ `bot_config` - Telegram/Discord configuration
- ✅ `export_history` - Export activity log
- ✅ `analytics_cache` - Performance caching
- ✅ `alert_rule_triggers` - Alert trigger audit log

The migration also adds:
- ✅ `last_speed` column to `devices` table
- ✅ `speed` column to `device_locations` table
- ✅ 4 sample alert rules (battery alerts, offline alert, speed alert)

---

## 🚀 Features Ready Immediately

### 📊 CSV/PDF Export
**No configuration needed!** Works immediately after upload.

**Usage:**
- Dashboard: Click "Export Devices CSV" button
- Device View: Click "Export Locations CSV" or "Generate Report"
- URLs:
  - Devices CSV: `/export.php?type=devices_csv`
  - Locations CSV: `/export.php?type=locations_csv&device_id=X&days=7`
  - Battery CSV: `/export.php?type=battery_csv&days=30`
  - Device Report: `/export.php?type=report_pdf&device_id=X`

**Features:**
- UTF-8 BOM for Excel compatibility
- Date range filtering
- Google Maps links in location exports
- Activity logging in `export_history` table

### 📈 Analytics Dashboard
**No configuration needed!** Works immediately after upload.

**Access:** `/analytics.php`

**Features:**
- 6 KPI stat cards (total devices, online, offline, avg battery, low battery, locations today)
- Battery trends line chart (7 days, multi-device)
- Location updates bar chart (last 24 hours)
- Device status pie chart (online/offline/revoked)
- Geofence activity chart (30 days, entries/exits)
- Device comparison table (battery, storage, updates, events)
- Alert rule statistics table
- Auto-refresh every 5 minutes
- Data cached for 15-60 minutes for performance

---

## ⚙️ Optional Configuration

### 💬 Telegram Bot Setup (Optional)

**Step 1: Create Telegram Bot**
1. Open Telegram and message **@BotFather**
2. Send `/newbot`
3. Choose a name: "PhoneMonitor Alerts"
4. Choose username: "yourname_phonemonitor_bot" (must end in _bot)
5. Copy the **Bot Token** (looks like: `123456789:ABCdefGHIjklMNOpqrsTUVwxyz`)

**Step 2: Get Your Chat ID**
1. Message **@userinfobot** on Telegram
2. It will reply with your User ID
3. Copy the **Chat ID** (looks like: `987654321`)

**Step 3: Configure in Database**
Run this SQL in phpMyAdmin or terminal:

```sql
INSERT INTO bot_config (bot_type, config, enabled, created_at)
VALUES (
    'telegram',
    JSON_OBJECT(
        'bot_token', 'YOUR_BOT_TOKEN_HERE',
        'chat_id', 'YOUR_CHAT_ID_HERE'
    ),
    TRUE,
    NOW()
);
```

**Step 4: Test**
- Go to `/alert_rules.php`
- Create a test rule or trigger an existing one
- Check your Telegram for alert message

---

### 💬 Discord Bot Setup (Optional)

**Step 1: Create Discord Webhook**
1. Open your Discord server
2. Go to **Server Settings** → **Integrations** → **Webhooks**
3. Click **New Webhook**
4. Choose channel (e.g., #phone-alerts)
5. Name it "PhoneMonitor"
6. Click **Copy Webhook URL** (looks like: `https://discord.com/api/webhooks/123456/abcdef...`)

**Step 2: Configure in Database**
Run this SQL in phpMyAdmin or terminal:

```sql
INSERT INTO bot_config (bot_type, config, enabled, created_at)
VALUES (
    'discord',
    JSON_OBJECT(
        'webhook_url', 'YOUR_WEBHOOK_URL_HERE'
    ),
    TRUE,
    NOW()
);
```

**Step 3: Test**
- Go to `/alert_rules.php`
- Create a test rule or trigger an existing one
- Check your Discord channel for alert message

---

### 🔔 Alert Rules Setup (Optional)

**Step 1: Access Interface**
Go to `/alert_rules.php`

**Step 2: Create Alert Rule**
The migration creates 4 sample rules automatically:
1. **Low Battery Alert** - Triggers when battery < 15%
2. **Critical Battery** - Triggers when battery < 5%
3. **Device Offline 24h** - Triggers when offline > 24 hours
4. **High Speed Alert** - Triggers when speed > 120 km/h

You can create custom rules with:
- **8 field types:** battery_level, storage_free_gb, offline_hours, offline_minutes, speed_kmh, is_charging, hour_of_day, day_of_week
- **6 operators:** <, <=, >, >=, ==, !=
- **AND/OR logic:** Match ALL conditions or ANY condition
- **3 action channels:** Email, Telegram, Discord (can select multiple)
- **Cooldown:** Prevent repeated alerts (default 60 minutes)

**Step 3: Enable Automated Checking (Cron Job)**

**Option A - Plesk Control Panel:**
1. Go to **Scheduled Tasks** in Plesk
2. Click **Add Task**
3. Task type: **Run a PHP script**
4. Schedule: Every 5 minutes (*/5 * * * *)
5. Command: `/path/to/PhoneMonitor/cron_alert_rules.php`

**Option B - Command Line:**
```bash
# Edit crontab
crontab -e

# Add this line:
*/5 * * * * php /path/to/PhoneMonitor/cron_alert_rules.php
```

**What the cron does:**
- Runs every 5 minutes
- Evaluates all enabled alert rules
- Checks conditions against device data
- Triggers actions (email/Telegram/Discord) when conditions match
- Respects cooldown period to prevent spam
- Logs all triggers to `alert_rule_triggers` table

---

## 🧪 Testing Guide

### Test Export System
1. Go to `/dashboard.php`
2. Click "Export Devices CSV"
3. Verify Excel opens the file correctly
4. Go to a device page
5. Click "Export Locations CSV"
6. Verify location data with Google Maps links

### Test Analytics Dashboard
1. Go to `/analytics.php`
2. Verify 6 KPI stat cards show correct numbers
3. Verify 4 charts render correctly
4. Verify device comparison table shows all devices
5. Wait 5 minutes and verify auto-refresh works

### Test Alert Rules (Without Bots)
1. Go to `/alert_rules.php`
2. Create new rule: "Test Battery Alert"
3. Condition: battery_level < 100 (will always trigger)
4. Actions: Email only
5. Enable rule
6. Wait for next cron run (5 minutes) OR manually run:
   ```bash
   php /path/to/PhoneMonitor/cron_alert_rules.php
   ```
7. Check admin email for alert
8. Check "Recent Triggers" section on alert_rules.php

### Test Telegram Bot (After Setup)
1. Configure Telegram bot (see above)
2. Go to `/alert_rules.php`
3. Edit existing rule or create new one
4. Check "Telegram" action
5. Trigger the rule
6. Check Telegram for message with emoji and formatted text

### Test Discord Bot (After Setup)
1. Configure Discord webhook (see above)
2. Go to `/alert_rules.php`
3. Edit existing rule or create new one
4. Check "Discord" action
5. Trigger the rule
6. Check Discord channel for rich embed message with color

---

## 📊 Feature Documentation

Full documentation is available in **setup.php** under:
- **Step 5:** Phase 3 Setup Guide (5 sub-steps)
- **User Guides:** 4 detailed feature cards

Access at: `/setup.php`

---

## 🔍 Troubleshooting

### Analytics Dashboard Not Loading
- Check browser console for JavaScript errors
- Verify Chart.js CDN is accessible: `https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js`
- Check PHP error logs for database issues

### Export Returns Empty File
- Verify export_history table exists
- Check database permissions for INSERT
- Verify devices have data to export

### Alert Rules Not Triggering
- Verify cron job is running: `grep CRON /var/log/syslog`
- Check alert_rule_triggers table for logs
- Verify conditions are actually matching device data
- Check cooldown period hasn't prevented trigger

### Telegram Bot Not Working
- Verify bot token is correct
- Verify chat ID is correct (try @userinfobot again)
- Test bot manually: `curl https://api.telegram.org/bot<TOKEN>/getMe`
- Check bot hasn't been blocked

### Discord Webhook Not Working
- Verify webhook URL is correct and complete
- Test webhook manually: `curl -X POST <WEBHOOK_URL> -H "Content-Type: application/json" -d '{"content":"test"}'`
- Verify webhook wasn't deleted in Discord

### "Undefined method 'validate'" Error
- Make sure you uploaded the FIXED version of alert_rules.php
- The correct methods are: `CSRF::require()` and `CSRF::field()`

---

## 📈 Performance Notes

### Caching Strategy
- **Analytics:** Cached for 15-60 minutes depending on data type
- **Cache storage:** MySQL `analytics_cache` table
- **Automatic cleanup:** Expired cache automatically skipped
- **Manual clear:** `DELETE FROM analytics_cache WHERE expires_at < NOW()`

### Database Indexes
All tables have proper indexes for performance:
- `alert_rules`: Indexed on device_id, enabled, rule_type
- `bot_config`: Unique constraint on bot_type
- `export_history`: Indexed on exported_at, device_id, export_type
- `analytics_cache`: Unique index on cache_key, indexed on expires_at
- `alert_rule_triggers`: Indexed on triggered_at, alert_rule_id

### Cron Job Impact
- Alert rules cron runs every 5 minutes
- Evaluates only enabled rules
- Respects cooldown to prevent excessive queries
- Typically completes in <1 second with 10 devices

---

## 🎉 Completion Summary

**Phase 3 Status:** ✅ **COMPLETE**

**Total Code:** ~2,500 new lines across 15 files

**Deployment Time Estimate:**
- File upload: 5 minutes
- Database migration: 2 minutes
- Basic testing: 10 minutes
- Bot setup (optional): 10 minutes each
- Cron setup (optional): 5 minutes

**Minimum Time to Production:** ~20 minutes  
**Full Setup with Bots:** ~45 minutes

**Next Steps:**
1. Upload all 15 files to production server
2. Run database migration SQL
3. Test export and analytics features (work immediately!)
4. Optional: Configure Telegram/Discord bots
5. Optional: Set up cron job for automated alerts
6. Enjoy your new advanced features! 🎊

---

**Need Help?**
- All feature guides are in `/setup.php`
- Check Recent Triggers table in `/alert_rules.php` for alert debugging
- Export history logged in `export_history` table
- Alert triggers logged in `alert_rule_triggers` table

**Support:** Contact your development team or refer to the comprehensive documentation in setup.php
