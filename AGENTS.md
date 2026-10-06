# AGENTS.md — Prince Full (Base44 dev environment)

## What this is
PHP 8 + MySQL/MariaDB + Apache MVC app (Arabic RTL). Internet card sales management system ("Prince Full"). No JS build step — vanilla JS, CSS, and PHP views served directly by Apache.

## Stack
- **Backend:** PHP 8.2+ with PDO, custom MVC framework in `app/` (Router, Controller, Model, Session, Request, Response).
- **Frontend:** HTML5 + CSS3 + vanilla JS, RTL, mobile-first. No bundler.
- **Server:** Apache with `.htaccess` (mod_rewrite) front-controller routing → `index.php`.
- **DB:** MySQL/MariaDB. Schema in `database/schema.sql`.

## Running locally (Base44)
```
docker compose -f docker-compose.base44.yml up -d --build
```
- **web** (PHP-Apache) on host port 3000 → container port 80.
- **db** (MariaDB 11) with persistent volume `db_data`.
- **migrate** (one-shot) loads `database/schema.sql` and sets the admin password to `admin123`.

Source is bind-mounted at `/var/www/html`, so PHP edits are live on the next request — no rebuild needed for code changes. Rebuild only when the `Dockerfile` changes (new PHP extensions, etc.).

## Default admin login
- **Email:** `ibrabra651@gmail.com`
- **Password:** `admin123` (set by the migrate service; the schema's original hash is overwritten)

## Key config
- `config/database.php` — DB connection, env-driven (`DB_HOST`, `DB_NAME`, `DB_USER`, `DB_PASS`, `DB_PORT`). Compose sets these to point at the local MariaDB service.
- `config/routes.php` — route map (GET/POST → controller/action).
- `config/app.php` — app name, locale (ar), direction (rtl).

## No external credentials needed
All infrastructure (DB) runs locally in compose. No third-party API keys required.
