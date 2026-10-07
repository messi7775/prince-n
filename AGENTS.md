# Base44 Dev Environment — Prince Full

## Stack
PHP 8.2 + Apache (mod_rewrite) + MariaDB 11. Vanilla PHP MVC, no build step.

## Running the app
```bash
docker compose -f docker-compose.base44.yml up -d --build
```
- Web (Apache+PHP) on host port **3000** → container 80
- MariaDB on internal port 3306 (not exposed to host)

## Database
- Schema auto-loads on first `db` init via `database/schema.sql` (mounted into `/docker-entrypoint-initdb.d/`).
- DB name is `if0_43097781_prince` (matches the `USE` statement in schema.sql). Connection settings come from `DB_*` env vars set in compose.
- The `prince` MySQL user is granted all privileges on this database.

## Dev admin credentials
The schema seeds an admin (`ibrabra651@gmail.com`). For local dev, the password is reset to **`prince123`** via a one-off SQL update (not persisted in the repo). If the DB volume is recreated, re-run:
```bash
docker compose -f docker-compose.base44.yml exec -T web php -r 'echo password_hash("prince123", PASSWORD_DEFAULT);'
# then UPDATE admins SET password_hash='<hash>' WHERE email='ibrabra651@gmail.com';
```

## Permissions note
The repo root must be world-traversable (`chmod 755 .`) so Apache (www-data) can read the bind-mounted source. If a fresh clone has `700`, fix it before starting.

## Live reload
PHP has no build step — Apache serves files directly from the bind mount, so edits appear immediately on the next request. No reload needed for PHP/HTML/CSS/JS changes.

## No external secrets required
All credentials (DB, admin) are local to the compose stack. No external service keys are needed.
