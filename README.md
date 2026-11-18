# Student Complaint and Concerns Management System

PHP and MySQL application for XAMPP. Bootstrap 5, Bootstrap Icons, and Figtree are loaded from CDNs, so those visual assets need an internet connection in the browser.

## Install on XAMPP

1. Copy this project into `C:\xampp\htdocs\system` (or a folder of your choice under `htdocs`).
2. Start Apache and MySQL in XAMPP.
3. Open `http://localhost/phpmyadmin`, create a database, select it, and import `schema.sql`.
4. If your MySQL connection differs from XAMPP defaults, set `DB_HOST`, `DB_NAME`, `DB_USER`, and `DB_PASS`, or edit `config/database.php`.
5. Open `http://localhost/system/auth/setup_admin.php` and create the first administrator. Setup is disabled automatically once an admin exists; no setup code is required.
6. Sign in at `http://localhost/system/auth/login.php`. Students can register at `http://localhost/system/auth/register.php`; administrators can provision staff and student accounts in their management pages.

## Registration roles

The registration page at `/auth/register.php` lets applicants choose Student or Staff. Student accounts are active immediately. Staff accounts remain pending until an administrator approves them under **Manage Staff**. Administrator accounts can only be created through the first-admin setup page, which closes automatically after an administrator is created.

## Project layout

- `auth/` — login, registration, logout, password recovery, and first-admin setup
- `admin/`, `staff/`, `student/` — role-specific application pages
- `config/` — database connection
- `includes/` — shared authentication, layout, and data helpers
- `assets/` — styles and JavaScript

## Implemented

- Session login/logout, student registration, role checks, hashed passwords, CSRF tokens, and escaped output.
- Student complaint submission, tracking IDs, complaint history, notifications, resolution visibility, reopen requests, and feedback.
- Admin complaint assignment, status/resolution/rejection management, internal notes, account/category/department management, activity logs, report filters, print, and CSV export.
- Staff assigned complaint access and complaint updates.
- MySQL schema with foreign keys and sample department/category records.

## Notes

The system does not yet include complaint attachment upload/download, email-based password recovery, chart rendering, or full edit/reactivation workflows for archived accounts and reference data. These are not represented as working controls. Reports are table-based. Ensure HTTPS and appropriate file/database backups before deploying outside a local demonstration.
