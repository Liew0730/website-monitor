# 🌐 Website Monitoring System with Telegram Alerts (Admin Only)

A beginner-friendly, framework-free website monitoring system built with
**core PHP + MySQL + HTML/CSS**. Tracks uptime/downtime for a list of
websites, keeps a full history log, and sends **Telegram alerts** the
moment a website's status changes.

---

## 📁 Project Structure

```
website-monitor/
├── config/
│   └── config.php          # DB credentials + Telegram bot token/chat ID
├── includes/
│   ├── bootstrap.php       # session + config + db + functions loader
│   ├── db.php              # PDO database connection
│   ├── functions.php       # helper functions (telegram, http check, etc.)
│   ├── auth.php            # login guard for admin pages
│   ├── header.php          # shared page header/nav
│   └── footer.php          # shared page footer
├── cron/
│   └── monitor.php         # THE MONITORING ENGINE (run via cron)
├── assets/
│   └── style.css           # all styling, no frameworks
├── database/
│   └── schema.sql          # tables + seed admin account
├── login.php
├── logout.php
├── dashboard.php           # stats + live status table
├── websites.php            # manage websites (list/search/filter/delete)
├── website_form.php        # add / edit website
├── logs.php                # full monitoring history
└── README.md
```

---

## ⚙️ Requirements

- PHP 7.4+ (PHP 8.x recommended) with the **cURL** and **PDO MySQL**
  extensions enabled (both are on by default in XAMPP)
- MySQL / MariaDB
- XAMPP (or any local server stack) for local development

---

## 🚀 Setup Guide (XAMPP)

1. **Copy the project** into your XAMPP `htdocs` folder, e.g.:
   ```
   C:\xampp\htdocs\website-monitor\
   ```

2. **Create the database.**
   - Open phpMyAdmin (`http://localhost/phpmyadmin`)
   - Click **Import**, choose `database/schema.sql`, and run it.
   - This creates the `website_monitor` database with `admins`,
     `websites`, and `logs` tables, and seeds one default admin account.

3. **Configure the database connection.**
   Edit `config/config.php` and update the `db` section if your MySQL
   username/password differ from the XAMPP defaults (`root` / empty
   password is the default and usually needs no changes).

4. **Start Apache & MySQL** from the XAMPP control panel.

5. **Open the app** in your browser:
   ```
   http://localhost/website-monitor/login.php
   ```

6. **Login with the default admin account:**
   - Username: `admin`
   - Password: `admin123`

   ⚠️ **Change this password immediately.** You can generate a new hash
   with PHP:
   ```php
   <?php echo password_hash('your-new-password', PASSWORD_DEFAULT);
   ```
   Then update the `password` column for the `admin` row in the
   `admins` table via phpMyAdmin.

7. **Add websites to monitor** from the **Websites** page: name, URL,
   and how often (in minutes) it should be checked.

---

## 📡 Telegram Bot Setup

1. Open Telegram and message **[@BotFather](https://t.me/BotFather)**.
2. Send `/newbot` and follow the prompts to name your bot. BotFather
   will give you a **bot token** that looks like:
   ```
   123456789:AAExampleTokenxxxxxxxxxxxxxxxxxxxxx
   ```
3. Start a chat with your new bot (search for its username and press
   **Start**), or add it to a group where you want alerts sent.
4. Find your **chat ID**:
   - Send any message to the bot first.
   - Then visit this URL in your browser (replace `<TOKEN>`):
     ```
     https://api.telegram.org/bot<TOKEN>/getUpdates
     ```
   - Look for `"chat":{"id": 123456789, ...}` in the JSON response —
     that number is your `chat_id`. (For groups, the ID is usually
     negative, e.g. `-1001234567890`.)
5. Open `config/config.php` and fill in:
   ```php
   'telegram' => [
       'bot_token' => '123456789:AAExampleTokenxxxxxxxxxxxxxxxxxxxxx',
       'chat_id'   => '123456789',
   ],
   ```
6. That's it — the monitoring engine will now send real alerts.

**Message types sent:**
- 🔴 `ALERT: Website DOWN` — when a site goes from UP/unknown to DOWN
- 🟢 `RECOVERY: Website back UP` — when a site comes back UP
- 🟡 `WARNING: Slow response detected` — when response time exceeds
  the configured threshold (`slow_threshold_ms` in `config.php`,
  default 3000 ms)

Each message includes: website name, URL, status, response time, and
timestamp.

---

## ⏱️ Cron Job Setup (the monitoring engine)

The file `cron/monitor.php` is the automatic engine that:
1. Loops through all websites due for a check (based on each site's
   own `interval_minutes`)
2. Sends an HTTP request and measures response time
3. Marks the result UP or DOWN
4. Saves it to the `logs` table
5. Compares it to the previous status and sends a Telegram alert
   **only when the status actually changes** (no duplicate alerts)
6. Optionally sends a slow-response warning

Run it every minute so each website's own interval is respected:

### Linux / macOS (crontab)
```bash
crontab -e
```
Add:
```
* * * * * /usr/bin/php /full/path/to/website-monitor/cron/monitor.php >> /full/path/to/website-monitor/cron/cron.log 2>&1
```

### Windows (XAMPP) — Task Scheduler
1. Open **Task Scheduler** → **Create Task**.
2. **Trigger:** Daily, repeat every **1 minute**, indefinitely.
3. **Action:** Start a program:
   - Program/script: `C:\xampp\php\php.exe`
   - Arguments: `C:\xampp\htdocs\website-monitor\cron\monitor.php`
4. Save and enable the task.

You can also test it manually any time from a terminal:
```bash
php cron/monitor.php
```
(The script only runs from the command line for safety — it will
refuse to run if opened directly in a browser.)

---

## 🧠 How Status Logic Works

- **UP** — the site responded with an HTTP status code between 200–399
  within the timeout window (default 10 seconds).
- **DOWN** — the request timed out, failed to connect, or returned an
  error status code (400+).
- **PENDING** — a newly added website that hasn't been checked yet.
- The engine only sends a Telegram alert when the **new status differs
  from the last recorded status**, so you won't get spammed with
  repeat "still down" messages every minute.
- A separate, independent **slow-response warning** can fire any time
  a successful check exceeds `slow_threshold_ms`.

---

## 🔐 Security Notes

- Passwords are hashed with PHP's `password_hash()` / verified with
  `password_verify()` — never stored in plain text.
- All database queries use PDO **prepared statements** to prevent SQL
  injection.
- All admin pages require a valid session (`includes/auth.php`); the
  cron script is CLI-only and cannot be triggered from a browser.
- Change the default admin password before deploying anywhere
  publicly, and keep `config/config.php` (with your bot token) out of
  version control / public web access if you deploy live.

---

## ✅ Feature Checklist

- [x] Add / edit / delete monitored websites with custom interval
- [x] Live dashboard with color-coded UP/DOWN status
- [x] Full monitoring history log
- [x] Search by name/URL, filter by status or recency
- [x] Telegram alerts on DOWN, RECOVERY, and slow-response
- [x] Duplicate-alert prevention (only alerts on status change)
- [x] Cron-driven automatic monitoring engine
- [x] Session-based admin login with hashed passwords and show/hide
      password toggle
- [x] Clean table-based UI, no frameworks
