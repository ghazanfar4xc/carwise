# AutoPulse — Deployment Guide (InfinityFree + general Apache hosting)

## 1. Local development

1. Install XAMPP/WAMP/Laragon (or any Apache + PHP 8 + MySQL stack).
2. Copy this project folder into `htdocs` (e.g. `C:\xampp\htdocs\autopulse`).
3. Start Apache + MySQL, open phpMyAdmin → create database `autopulse` → import `sql/database.sql`.
4. Edit `config/config.php` with your local DB credentials.
5. Browse `http://localhost/autopulse` and log in at `/admin/login.php`.
   - **Change the default passwords immediately** (Admin → Users).

Quick alternative without Apache (from the project folder):

```bash
php -S localhost:8000 router-dev.php
```

## 2. Prepare the database

- `sql/database.sql` contains schema + demo data. The `CREATE DATABASE` line is commented out
  because most shared hosts (InfinityFree included) pre-create the database for you.
- Optionally strip the demo `INSERT` statements and keep only the schema.

## 3. Set up InfinityFree

1. Create a free account at infinityfree.com and add a domain/subdomain.
2. In the client area open **MySQL Databases**, create a database, and note:
   - MySQL Host Name (e.g. `sql123.infinityfree.com`)
   - Database Name (e.g. `if0_12345678_autopulse`)
   - Username (e.g. `if0_12345678`) and the password you set
3. Open **phpMyAdmin** from the client area, select your database, and import `sql/database.sql`.

## 4. Upload the files

1. Download/install **FileZilla**.
2. FTP settings (from InfinityFree client area → FTP Accounts):
   - Host: `ftpupload.net`  ·  Port: `21`  ·  Explicit FTP over TLS
   - Username: your `if0_…` account  ·  Password: your FTP password
3. Upload **everything inside this project folder** into `/htdocs`
   (upload the *contents*, not the folder itself, if the site should run on the domain root).
4. Make sure these folders exist and are uploaded: `uploads/*`, `cache/`, `assets/`.

## 5. Configure

Edit `config/config.php` on the server (or edit before uploading):

```php
define('APP_ENV', 'production');
define('BASE_URL', '');                    // '' when installed at htdocs root
define('DB_HOST', 'sqlXXX.infinityfree.com');
define('DB_NAME', 'if0_12345678_autopulse');
define('DB_USER', 'if0_12345678');
define('DB_PASS', 'your-db-password');
```

Then in the admin panel set **Settings → General** and **SEO → Canonical site URL**
(e.g. `https://yoursite.com`) so canonical tags, Open Graph and the sitemap use absolute URLs.

## 6. HTTPS

1. In the InfinityFree client area, enable the **free SSL certificate** for your domain
   (may take a few minutes to issue; for custom domains, point CNAME first).
2. After the certificate is active, uncomment the "Force HTTPS" block at the bottom of `.htaccess`.
   ⚠️ Do not enable it before the certificate works or visitors will get warnings.

## 7. Post-install checklist

- [ ] Log in at `https://yoursite.com/admin/login.php`
- [ ] **Change the admin & editor passwords** (Users)
- [ ] Settings → General: site name, tagline, logo, currency, hero image
- [ ] Settings → Contact & Social: email/phone/address/socials
- [ ] SEO: default meta description, canonical URL, Analytics ID, Search Console verification
- [ ] Ads: paste your AdSense code into the slots you plan to use
- [ ] Visit `https://yoursite.com/sitemap.xml` and `https://yoursite.com/robots.txt`
- [ ] Submit the sitemap in Google Search Console
- [ ] Test a car page, an article, the compare tool, search suggestions and the contact form
- [ ] Test the site on your phone (menu, compare table, cards)
- [ ] Delete any demo content you don't want (drafts are harmless, published demo articles are visible)
- [ ] Create a database backup (Admin → Backup) once your real content is in

## 8. Troubleshooting

| Symptom | Fix |
|---|---|
| "Database connection failed" | Wrong credentials in `config/config.php`; on InfinityFree use the exact host from the client area |
| 404 on all inner pages | `.htaccess` missing or `mod_rewrite` disabled — re-upload `.htaccess` (hidden file!) |
| Blank/500 pages | Set `APP_ENV` to `development` temporarily to see the error, then back to `production` |
| Uploads fail | File bigger than the host limit (10 MB on InfinityFree) or `uploads/` not uploaded |
| Stale menus/settings | Admin → Backup → Clear cache |
| Images not appearing | `uploads/` folder or files missing on the server |

## 9. Updating

1. Take a backup (Admin → Backup → download `.sql`).
2. Upload changed files over the existing ones (keep `config/config.php`, `uploads/` and `cache/`).
3. If `sql/database.sql` changed structurally, apply the new schema via phpMyAdmin
   (a fresh import into a new database is the safest path — then re-point `config.php`).
