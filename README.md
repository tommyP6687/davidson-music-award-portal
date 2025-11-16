# Music Awards Portal (LAMP/cPanel)
Students accept/decline music award letters and upload signed PDFs; admins upload a roster CSV and download a status-enriched CSV.

## Tech
PHP (PDO), MySQL, Apache/cPanel, HTML/CSS/JS.

## Features
- Student flow: accept/decline, required checkboxes, PDF upload + preview
- Admin flow: upload roster CSV, live status preview, export CSV with status/date
- Matching: First/Last Name in columns A/B (header auto-scan)
- Security: prepared statements, MIME/extension checks, locked uploads, sessions

## Getting Started (local or server)
1. Copy `config.example.php` → `config.php` and fill DB/paths.
2. Create DB and tables (see `db/001_schema.sql`).
3. Ensure folders exist and are writable:
   - `uploads/signed/`, `uploads/awards/`, `uploads/sheets/` (`0755` or `0775`)
4. Visit `/login.php`.

## Database Schema
See `db/001_schema.sql` (users, submissions, admin_sheet, student_awards).

## Folder Structure
* assets/ # CSS, images
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
* middleware.php
* authenticate.php
* utils.php
* config.example.php

## Deployment
- Never commit `config.php` or real files in `uploads/`.
- Use cPanel File Manager or SFTP to upload, then `git pull` if you have SSH.
