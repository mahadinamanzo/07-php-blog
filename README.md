# SWD Blog CMS

A lightweight PHP 8 and MySQL blog CMS for `http://localhost/swdmm/07-php-blog/`.

## Requirements

- PHP 8.0+ with PDO MySQL, Fileinfo, iconv, and mbstring extensions enabled
- MySQL 5.7+ or MariaDB with InnoDB and FULLTEXT support
- Apache with `mod_rewrite` not required; `.htaccess` support enabled with `AllowOverride All`

## Setup

1. For a fresh install, import `database.sql` into MySQL (for example with phpMyAdmin). If upgrading the pre-existing XAMPP `swd_blogs` schema, apply `migrations/001_align_existing_swd_blogs.sql` once, followed by `migrations/002_add_admin_username.sql`; the first migration preserves existing categories and requires existing post categories to be populated. Existing admins receive a temporary username of `admin<ID>` and can still sign in with email.
2. The default local connection is host `127.0.0.1`, port `3306`, database `swd_blogs`, user `root`, and an empty password. Override these with `DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USER`, and `DB_PASS` environment variables when needed.
3. Create the first admin from Command Prompt or PowerShell:

   ```powershell
   C:\xampp\php\php.exe create-admin.php "Mahadi Yusuf" mahadinamanzo mahadinamanzo@gmail.com
   ```

   The script prompts for a password and confirmation; use at least 12 characters. It refuses to create another admin once an account exists. Admins can sign in using either username or email.
4. Visit `http://localhost/swdmm/07-php-blog/` and sign in at `/admin/login.php`.

The app's base URL is defined in `app/bootstrap.php`. Update `BASE_URL` there if you install the project at a different path. Ensure the `uploads/` directory is writable by Apache; uploaded cover images are limited to 2 MB and accepted only when `finfo` identifies a valid JPEG, PNG, or WebP image.

## Features

- Public post feed, category pages, individual post pages, and MySQL FULLTEXT search
- Admin dashboard with post and category management
- Draft/published states; only published posts are public
- Prepared database statements, escaped output, CSRF-protected forms, hardened session cookies, session ID rotation, and idle/absolute admin session expiry
- `app/` is denied by Apache and PHP execution is disabled for files under `uploads/`
