# 1st Time Runners — PHP/MySQL Edition
### Converted from Flask/SQLite → Pure PHP + MySQL (Hostinger Ready)

---

## 📁 Project Structure

```
1st_time_runners_php/
├── .htaccess                  ← Root Apache router (REQUIRED on Hostinger)
├── index.php                  ← Serves public frontend (templates/index.html)
├── admin.php                  ← Serves admin panel  (templates/admin.html)
├── config.php                 ← DB credentials + shared helpers ← EDIT THIS
├── setup.sql                  ← MySQL CREATE TABLE + seed data  ← RUN ONCE
│
├── api/
│   ├── .htaccess              ← API sub-router
│   └── index.php              ← All 27 API endpoints (replaces all Flask routes)
│
├── templates/
│   ├── index.html             ← Public frontend  (UNCHANGED from Flask)
│   └── admin.html             ← Admin portal     (UNCHANGED from Flask)
│
└── static/
    └── uploads/
        ├── .htaccess          ← Blocks PHP execution in uploads (security)
        └── [proof images]
```

---

## 🚀 Hostinger Deployment (Step by Step)

### STEP 1 — Create MySQL Database

1. Login to **Hostinger hPanel**
2. Go to **Hosting → Manage → MySQL Databases**
3. Create a new database (e.g. `u123456789_runners`)
4. Create a database user and set a strong password
5. Assign the user to the database with **All Privileges**
6. Note down: DB name, DB username, DB password

---

### STEP 2 — Run the SQL Script

1. In hPanel → go to **phpMyAdmin**
2. Select your database from the left panel
3. Click the **SQL** tab at the top
4. Open `setup.sql` from this project, copy the **entire contents**
5. Paste into the SQL box and click **Go**
6. You should see green success messages for all tables + seed data

---

### STEP 3 — Edit config.php

Open `config.php` and fill in your credentials:

```php
define('DB_HOST', 'localhost');              // Always localhost on Hostinger
define('DB_NAME', 'u123456789_runners');     // Your database name
define('DB_USER', 'u123456789_admin');       // Your database username
define('DB_PASS', 'YourStrongPassword123');  // Your database password
```

You can also change the admin login here:
```php
define('ADMIN_USER', 'admin');
define('ADMIN_PASS', 'runners2025');
```

---

### STEP 4 — Upload Files to Hostinger

1. In hPanel → **File Manager** (or use FTP/SFTP)
2. Navigate to `public_html/` (or your subdirectory)
3. Upload **ALL files**, keeping the exact folder structure:
   ```
   public_html/
   ├── .htaccess         ← MUST be uploaded (hidden file — enable "Show hidden files" in File Manager)
   ├── index.php
   ├── admin.php
   ├── config.php
   ├── setup.sql
   ├── api/
   │   ├── .htaccess
   │   └── index.php
   ├── templates/
   │   ├── index.html
   │   └── admin.html
   └── static/
       └── uploads/
           └── .htaccess
   ```

   > ⚠️ **IMPORTANT**: `.htaccess` files are hidden by default. In Hostinger File Manager, click **Settings → Show Hidden Files** before uploading.

---

### STEP 5 — Set Folder Permissions

In **File Manager**, right-click the `static/uploads/` folder:
- Set permissions to **755** (or **0755**)
- This allows PHP to write uploaded proof images into it

---

### STEP 6 — Enable mod_rewrite (if needed)

Hostinger shared hosting has `mod_rewrite` enabled by default.  
If you get **404 errors on /api/*** routes, check:
- hPanel → **Advanced → .htaccess Editor** — make sure it's not overriding your file
- Contact Hostinger support to confirm `AllowOverride All` is set

---

### STEP 7 — Test the Site

| URL | Expected |
|-----|----------|
| `https://yourdomain.com/` | Public homepage loads |
| `https://yourdomain.com/admin` | Admin login screen |
| `https://yourdomain.com/api/stats` | JSON `{"total_runners":6,...}` |
| `https://yourdomain.com/api/events` | JSON array of 9 events |
| `https://yourdomain.com/api/runners` | JSON array of runners with `monthly_kms` |

---

## 🔐 Login Credentials

| Role | Username/Phone | Password |
|------|---------------|----------|
| Admin | `admin` | `configured via environment` |
| Runner (after approval) | Their phone number | PIN they set during registration |

---

## 📡 Complete API Reference

All endpoints under `/api/` — same responses as the original Flask app.

### Public Endpoints (no auth)
| Method | Endpoint | Description |
|--------|----------|-------------|
| `GET` | `/api/stats` | Total runners, KMs, events, pending regs |
| `GET` | `/api/events` | All events sorted by date |
| `GET` | `/api/runners` | All active runners + monthly_kms array |
| `GET` | `/api/winners` | Hall of fame winners |
| `POST` | `/api/registrations` | Submit join request |
| `POST` | `/api/runner/login` | Runner login (phone + password) |
| `POST` | `/api/runner/logout` | Runner logout |
| `GET` | `/api/runner/status` | Check runner session |
| `POST` | `/api/runner/submit-km` | Upload run proof (multipart) |
| `GET` | `/api/runners/{id}/km/log` | Runner's own KM history |

### Admin Endpoints (require admin session)
| Method | Endpoint | Description |
|--------|----------|-------------|
| `POST` | `/api/admin/login` | Admin login |
| `POST` | `/api/admin/logout` | Admin logout |
| `GET` | `/api/admin/status` | Check admin session |
| `GET` | `/api/admin/pending-logs` | Pending KM submissions |
| `PATCH` | `/api/admin/verify-km/{id}` | Approve/Reject KM proof |
| `POST` | `/api/admin/approve-runner/{id}` | Approve/Reject registration |
| `POST` | `/api/runners` | Add runner manually |
| `DELETE` | `/api/admin/runners/{id}` | Delete runner |
| `PATCH` | `/api/admin/runners/{id}/km` | Override runner's KM total |
| `POST` | `/api/events` | Create event |
| `DELETE` | `/api/events/{id}` | Delete event |
| `PUT` | `/api/events/{id}` | Update event |
| `GET` | `/api/registrations` | View all join requests |
| `PATCH` | `/api/registrations/{id}/status` | Update reg status |
| `GET` | `/api/admin/winners/manage` | List winners |
| `POST` | `/api/admin/winners/manage` | Award winner title |
| `DELETE` | `/api/admin/winners/delete/{id}` | Remove winner |

---

## 🗄️ Database Schema Overview

| Table | Purpose |
|-------|---------|
| `runners` | All runner profiles (name, phone, km, level, password, status) |
| `events` | Race events (name, date, venue, distances, reg_link) |
| `registrations` | Join requests awaiting admin approval |
| `km_log` | Individual run submissions with proof image + verify status |
| `winners` | Annual award records |

---

## ⚠️ Common Issues & Fixes

**404 on /api/ routes**
→ Check `.htaccess` was uploaded (it's a hidden file). Enable "Show Hidden Files" in File Manager.

**File upload fails (proof images)**
→ Set `static/uploads/` folder permission to `755` in File Manager.

**Database connection error**
→ Double-check `config.php` credentials. Hostinger DB host is always `localhost`.

**Admin login says "Route not found"**
→ Make sure `/api/.htaccess` was uploaded inside the `api/` subfolder.

**Images not showing in admin panel**
→ Flask served from `/static/uploads/filename`. PHP serves the same path because the `static/` folder is in `public_html/` directly accessible by Apache.

---

## 📌 What Was Converted (Flask → PHP)

| Flask | PHP |
|-------|-----|
| `app.py` Flask routes | `api/index.php` — all 27 routes |
| `render_template("index.html")` | `index.php` reads `templates/index.html` |
| `render_template("admin.html")` | `admin.php` reads `templates/admin.html` |
| `session["admin"]` | `$_SESSION['admin']` |
| `session["runner_id"]` | `$_SESSION['runner_id']` |
| `sqlite3` | MySQL via PDO |
| `send_from_directory(UPLOAD_FOLDER)` | Apache serves `static/uploads/` directly |
| `secure_filename()` | `preg_replace()` sanitize + timestamp prefix |
| All JSON `jsonify()` | `json_out()` helper in `config.php` |




http://localhost:8080/1st_time_runners/admin.php

http://localhost:8080/1st_time_runners/
