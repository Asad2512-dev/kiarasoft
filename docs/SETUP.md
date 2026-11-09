# Setup Guide

Requirements: PHP 8.2+ (8.3 recommended) with `pdo_mysql, mbstring, openssl, fileinfo, gd`; Composer 2; MySQL 8; Node 18+ (only needed if you change CSS/JS — built assets are committed in `public/build`).

## 1. Get the code
```bash
git clone https://github.com/Asad2512-dev/kiarasoft.git
```
```bash
cd kiarasoft
```
```bash
composer install
```
```bash
cp .env.example .env
```
```bash
php artisan key:generate
```

## 2a. macOS with MAMP
1. Start MAMP → MySQL runs on port **8889**, user `root`, password `root` by default.
2. In phpMyAdmin (or MAMP's MySQL shell) create the database: `CREATE DATABASE kirasoft CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;`
3. Edit `.env`:
```
DB_HOST=127.0.0.1
DB_PORT=8889
DB_DATABASE=kirasoft
DB_USERNAME=root
DB_PASSWORD=root
MYSQLDUMP_PATH=/Applications/MAMP/Library/bin/mysqldump
MYSQL_CLIENT_PATH=/Applications/MAMP/Library/bin/mysql
```
4. Use MAMP's PHP if the system one is old: `export PATH=/Applications/MAMP/bin/php/php8.3.x/bin:$PATH`
5. Either point MAMP's document root to `kiarasoft/public` (then set `APP_URL=http://localhost:8888`), or simply run `php artisan serve`.

## 2b. Windows with XAMPP
1. Start Apache + MySQL in the XAMPP control panel (MySQL on **3306**, user `root`, empty password).
2. Create database `kirasoft` in phpMyAdmin (collation `utf8mb4_unicode_ci`).
3. `.env`: `DB_PORT=3306`, `DB_USERNAME=root`, `DB_PASSWORD=`, `MYSQLDUMP_PATH=C:\xampp\mysql\bin\mysqldump.exe`, `MYSQL_CLIENT_PATH=C:\xampp\mysql\bin\mysql.exe`.
4. Put the project in `C:\xampp\htdocs\kiarasoft` and open `http://localhost/kiarasoft/public`, or run `php artisan serve`.
> XAMPP ships MariaDB; KiraSoft works with it, but the project targets MySQL 8.

## 3. Database, demo data, storage link
Set a demo password in `.env` first (not committed): `KIRASOFT_DEMO_PASSWORD=YourDemoPass1`
```bash
php artisan migrate --seed
```
```bash
php artisan storage:link
```
```bash
php artisan serve
```
Open http://localhost:8000. Demo accounts are printed by the seeder (see `DEMO_GUIDE.md`). If `KIRASOFT_DEMO_PASSWORD` is empty, a random password is generated and printed once.

Check the installation:
```bash
php artisan kirasoft:health
```

## 4. Rebuild frontend (only after editing resources/)
```bash
npm install
```
```bash
npm run build
```

## 5. Running tests
Create a separate test database (tests wipe it):
```sql
CREATE DATABASE kirasoft_test CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE DATABASE kirasoft_restore_test;
```
`phpunit.xml` points tests at `kirasoft_test` using the same DB user as `.env`.
```bash
php artisan test
```
Browser checks (needs Playwright + a running server with seeded demo data):
```bash
node tests/Browser/smoke.cjs http://localhost:8000
```
```bash
node tests/Browser/pos-flow.cjs http://localhost:8000
```

## 6. Backup & restore
- **Create:** Super Admin → *Backups* → *Back up now*, or `php artisan kirasoft:backup`. Files: `storage/app/private/backups/*.sql` (never web-accessible) with SHA-256 checksum recorded.
- **Verify:** *Test restore* (or `php artisan kirasoft:backup --restore-test`) loads the file into the separate database `KIRASOFT_RESTORE_TEST_DB` (default `kirasoft_restore_test`) and counts rows. The live database is never touched.
- **Real restore (disaster recovery)** — done manually by the administrator:
  1. Put the site in maintenance: `php artisan down`
  2. Take a safety backup of the current DB: `php artisan kirasoft:backup`
  3. Restore: `mysql -u USER -p kirasoft < storage/app/private/backups/FILE.sql`
  4. `php artisan migrate --force` (applies any newer migrations), then `php artisan up`
  5. Run `php artisan kirasoft:health` and open *Inventory → Reconcile*.

## 7. Production checklist
`APP_ENV=production`, `APP_DEBUG=false`, HTTPS with `SESSION_SECURE_COOKIE=true`, a dedicated MySQL user with rights only on `kirasoft`, `php artisan config:cache route:cache view:cache`, real `MAIL_*` settings for password resets, regular off-site copies of the backup folder. Never run `db:seed` in production (the seeder asks for confirmation).
