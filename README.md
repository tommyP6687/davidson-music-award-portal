# Music Awards Portal (LAMP / cPanel)
Students accept/decline music award letters and (if accepting) upload a signed PDF.  
Admins upload a single roster CSV and can download a **status-enriched CSV** that adds two columns:
- **Submission Date** (dd/mm/yyyy)
- **Letter Status** (`accepted`, `declined`, `none`)

Matching is done by **Last Name (col A)** and **First Name (col B)** found anywhere in the CSV (header auto-scan).  
Student uploads are previewed on their dashboard (cache-busted), and admins see a live table preview.

---

## Tech
**PHP (PDO), MySQL, Apache/cPanel, HTML/CSS/JS**

---

## Features
- **Student flow**
  - Email gate (`@davidson.edu`)
  - Accept/Decline with two required checkbox confirmations (lesson dropping penalties + ensemble & lesson registration)
  - PDF upload + in-page preview + download button
- **Admin flow**
  - Upload/replace the **roster CSV** (one active “current” sheet)
  - Live preview (first/last name + one adjacent column)
  - Download **modified CSV** (original + `submission date`, `letter status`)
- **Matching**
  - Uses **columns A/B** (Last/First) anywhere in the file (header scan)
  - Trims whitespace and normalizes case for name matching
- **Security**
  - Prepared statements, server-side validation, MIME/extension checks
  - Upload directories outside of Git, `.htaccess` download-only
  - Sessions + role middleware; no secrets committed

---

## Getting Started
1. **Copy config**  
   `config.example.php` → `config.php` and fill DB creds + paths/URLs.
2. **Database**  
   Import `db/001_schema.sql` into your MySQL database.
3. **Permissions**  
   Ensure these exist and are writable by PHP:
   - `uploads/signed/`  
   - `uploads/sheets/`
4. **Login pages**
   - Students: `student_login.php` → `student_dashboard.php`
   - Admins: `admin_login.php` → `admin_dashboard.php`

---

## Folder Structure
* assets/ # CSS, images
   * theme.css
* uploads/
   * signed/ # student PDFs (gitignored)
   * sheets/ # admin CSVs (gitignored)
* db/
   * 001_schema.sql # create tables
* admin_dashboard.php
* admin_download_csv.php 
* admin_login.php
* student_dashboard.php
* student_login.php
* student_submit.php
* login.php
* logout.php
* middleware.php
* authenticate.php
* utils.php
* config.example.php

---

## Database Schema (high level)
- **users**: admin accounts (plain password per your current config)
- **submissions**: student first/last/email, status, signed PDF path, timestamp
- **admin_sheet**: current CSV path + upload time

See `db/001_schema.sql` for exact definitions.

---

## CSV Rules
- Last Name = **Column A**, First Name = **Column B** (header can be anywhere; auto-scanned)
- Export adds **two columns to the left**: `submission date`, `letter status`
- Replacing the CSV preserves existing submissions; new export merges status by name

---

## Deployment Notes
- Never commit `config.php` or anything in `uploads/`
- Add a `.gitignore`:

## Example Site
Link to website: ```https://musicawards.tpham.dcreate.domains/login.php```
