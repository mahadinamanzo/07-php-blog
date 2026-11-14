# SWD Blog CMS

A PHP 8 and MySQL blog CMS for `http://localhost/swdmm/07-php-blog/`.

## Features

- Public home page with newest-first published articles and six-post pagination
- Clean article and category URLs: `/post/{slug}` and `/category/{slug}`
- Article reading-time estimates and related stories from the same category
- MySQL FULLTEXT search with a `LIKE` fallback for short queries
- Admin dashboard with post and category totals, five latest posts, and post counts by category
- Admin post and category create, edit, list, and delete workflows
- Draft and published post states; drafts are not exposed publicly
- Cover image upload for JPEG, PNG, and WebP files (maximum 2 MB)
- Prepared database queries, escaped output, CSRF-protected admin forms, and hardened admin sessions
- Production mode requires HTTPS and sends the admin session cookie with the `Secure` attribute

## Requirements

- XAMPP with PHP 8.0+ and MySQL/MariaDB
- PHP extensions: PDO MySQL, Fileinfo, iconv, and mbstring
- Apache `mod_rewrite` and `.htaccess` support (`AllowOverride All`)

## Setup

1. Place this project folder at:

   ```text
   C:\xampp\htdocs\swdmm\07-php-blog
   ```

2. Start Apache and MySQL from the XAMPP Control Panel.
3. Import [`database.sql`](./database.sql) into MySQL using phpMyAdmin:
   - Open `http://localhost/phpmyadmin/`.
   - Choose **Import**, select `database.sql`, and run the import.
   - For the existing XAMPP `swd_blogs` schema, apply `migrations/001_align_existing_swd_blogs.sql` and then `migrations/002_add_admin_username.sql` once instead. The first migration preserves existing categories and requires existing post categories to be populated.
4. Create the first admin from PowerShell or Command Prompt:

   ```powershell
   C:\xampp\php\php.exe C:\xampp\htdocs\swdmm\07-php-blog\create-admin.php "Mahadi Yusuf" mahadinamanzo mahadinamanzo@gmail.com
   ```

   The command prompts for a password and confirmation; use at least 12 characters. The script only creates the first admin.
5. Optionally add 12 sample posts from a terminal:

   ```powershell
   C:\xampp\php\php.exe C:\xampp\htdocs\swdmm\07-php-blog\seed.php
   ```

   Run this after creating an admin. It creates any missing seed categories, publishes 12 sample posts, and skips existing seed posts if run again.
6. Open the public site at `http://localhost/swdmm/07-php-blog/` or sign in at `http://localhost/swdmm/07-php-blog/admin/login.php` using the admin username or email.

The app's base URL is defined in `app/bootstrap.php`; change it if the folder is installed at a different web path. Ensure `uploads/` is writable by Apache. Database connection defaults are host `127.0.0.1`, port `3306`, database `swd_blogs`, user `root`, and an empty password; override them with `DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USER`, and `DB_PASS` as needed.

For production, set `APP_ENV=production` in Apache's PHP environment and serve the site over HTTPS. The app rejects non-HTTPS production requests and sets the session cookie's `Secure` flag. Local XAMPP remains in development mode by default.

## Screenshots

Screenshots can be added here after capturing the running site:

- Public home page: `screenshots/homepage.png`
- Admin dashboard: `screenshots/admin-dashboard.png`
- Article page: `screenshots/article.png`

## Folder structure

```text
app/                    Shared bootstrap, database, auth, CSRF, and helpers (Apache denied)
admin/                  Admin login, dashboard, posts, and categories
  includes/             Shared admin layout (Apache denied)
assets/                 Public CSS
includes/               Shared public layout and post card (Apache denied)
migrations/             Upgrade scripts for the existing XAMPP database
uploads/                Validated cover images (script execution disabled)
category.php            Category listing handler
create-admin.php        CLI-only first-admin setup
database.sql            Fresh-install schema
index.php               Public post feed
post.php                Public article handler
search.php              Public search handler
seed.php                CLI-only sample data generator
```

## Exporting the database from phpMyAdmin

1. Open `http://localhost/phpmyadmin/` and select the `swd_blogs` database.
2. Choose **Export**, select **Custom** if you need to choose tables or options, and keep SQL as the export format.
3. Click **Export** to download the `.sql` file.
4. To replace the project schema file with a structure-and-data export, save the downloaded file as `database.sql` in the project folder. Exporting with data includes blog content and admin account hashes; keep that file private and do not commit it to a public repository.
