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
`assets/ # CSS, images <br>
uploads/ <br>
   signed/ # student PDFs (gitignored) <br>
   sheets/ # admin CSVs (gitignored) <br> 
db/ <br>
   001_schema.sql # create tables <br> 
admin_dashboard.php <br> 
admin_login.php <br>
admin_upload_handler.php <br>
student_dashboard.php <br>
student_submit.php <br>
login.php <br>
middleware.php <br>
authenticate.php <br>
utils.php <br>
config.example.php`

## Deployment
- Never commit `config.php` or real files in `uploads/`.
- Use cPanel File Manager or SFTP to upload, then `git pull` if you have SSH.
