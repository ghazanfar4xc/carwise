# AutoPulse — Car Information & Automotive Blog Website

A complete, production-ready automotive portal built with **pure PHP 8 + MySQL + vanilla JS** — no frameworks, no Composer, no Node.js. Deployable directly to **InfinityFree** (or any Apache shared hosting).

![Stack](https://img.shields.io/badge/stack-PHP%208%20%7C%20MySQL%20%7C%20Vanilla%20JS-red)

---

## Features

**Public site**
- Homepage with admin-manageable sections (order, visibility, custom headings)
- Car database: specifications, prices, features, pros/cons, galleries, FAQs
- Car detail pages with grouped spec tables, JSON-LD structured data
- Brands hub + brand pages (logo, description, models, related articles)
- Blog with categories, tags, TOC, reading time, share buttons, moderated comments
- Live AJAX search (debounced, keyboard-navigable suggestions) across cars/articles/brands
- Car comparison tool (up to 3 cars, differences auto-highlighted, mobile-friendly)
- CMS pages (About, Privacy, Terms, Disclaimer — editable)
- Contact form (AJAX, spam-protected, admin inbox)
- Dark/light mode, fully responsive (mobile-first), reduced-motion support

**Admin panel** (`/admin`)
- Dashboard with stats, recent activity, quick actions, system info
- Articles: create/edit/publish/schedule/duplicate/feature, tags, FAQ builder, SEO
- Cars: tabbed editor (General / Specs / Features / Images / SEO), galleries, categories
- Brands, categories & tags, CMS pages, navigation menus
- Media manager with multi-upload, validation, auto-resize, reuse & picker
- Comment moderation, contact inbox, newsletter list
- Ad slots (8 positions) — paste AdSense code once, toggle per slot
- SEO center: defaults, verification codes, redirects, dynamic sitemap/robots
- Site settings: logo/favicon/hero, contact, social, footer, custom CSS/JS, maintenance mode
- Users & roles (admin / editor), database backup (.sql export), cache controls

**Security**: PDO prepared statements everywhere, CSRF tokens on every form & API, password hashing, login rate limiting, session hardening, upload MIME/extension validation, XSS escaping, directory protection via `.htaccess`.

---

## Project structure

```
├── index.php              Front controller / router
├── .htaccess              Rewrites, security, compression, caching
├── robots.php             Dynamic robots.txt  (served as /robots.txt)
├── sitemap.php            Dynamic sitemap     (served as /sitemap.xml)
├── router-dev.php         Optional router for `php -S` local testing
├── config/                config.php · database.php   ← the ONLY place with DB credentials
├── includes/              functions · models · auth · seo · header/footer · cards · pagination · breadcrumbs · ads
├── pages/                 Front-end templates (home, cars, car, articles, article, brands, brand,
│                          category, search, compare, contact, page, 404, 503, 500)
├── api/                   JSON endpoints: search · cars · compare · contact · comments · newsletter
├── admin/                 Admin panel (18 screens + shared layout)
├── assets/                css/main.css · css/admin.css · js/main.js · js/admin.js · images/
├── uploads/               cars · articles · general · media  (PHP execution blocked)
├── cache/                 File cache (settings, menus, sitemap) — web access denied
└── sql/database.sql       Full schema + demo data
```

---

## Local setup (XAMPP / WAMP / Laragon)

1. Copy the project into your web root (e.g. `C:\xampp\htdocs\autopulse`).
2. Create a database (e.g. `autopulse`) in phpMyAdmin and import `sql/database.sql`.
3. Edit `config/config.php`:
   ```php
   define('DB_HOST', 'localhost');
   define('DB_NAME', 'autopulse');
   define('DB_USER', 'root');
   define('DB_PASS', '');
   ```
4. Open `http://localhost/autopulse`.

No Apache? Use PHP's built-in server from the project folder:

```bash
php -S localhost:8000 router-dev.php
```

## Default admin credentials ⚠️

| URL | Username | Password |
|---|---|---|
| `/admin/login.php` | `admin` | `Admin@123` |
| `/admin/login.php` | `editor` | `Editor@123` |

**Change these immediately after first login** (Admin → Users). Demo prices/content are sample data — replace with your own.

---

## Deployment

See **[DEPLOYMENT.md](DEPLOYMENT.md)** for the full InfinityFree step-by-step guide and the final production checklist.

## Notes

- Article/page content is admin-authored HTML (trusted) — everything user-generated (comments, contact) is escaped.
- Scheduled articles go live automatically when their publish time passes (no cron needed).
- The file cache (`/cache`) speeds up settings, menus, ads and the sitemap; clear it from Admin → Backup.
- Images larger than 1920 px are auto-downscaled on upload when PHP-GD is available (InfinityFree has it).
