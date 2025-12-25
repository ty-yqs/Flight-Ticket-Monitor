# Flight Ticket Monitor (PHP)

A minimal PHP service to monitor flight prices on flights.ctrip.com.
Users subscribe to a one-way route (IATA codes) and date; an hourly worker fetches rendered pages, extracts prices, sorts them, and emails results.

Features
- Web UI to subscribe to a route, date and recipient email (`index.php`).
- Subscriptions stored in a local SQLite database (`subscriptions.db`).
- Hourly worker (`worker.php`) that builds search URLs and fetches rendered HTML via an optional Node/Puppeteer `fetcher.js`.
- Price extraction with fallbacks and ascending sorting.
- Email delivery via PHPMailer + SMTP (required).
- Includes `airports_cn_prov.json` (China airports grouped by province) to improve the UI.

Requirements
- PHP 7.4+ with `pdo_sqlite`.
- Composer (for PHPMailer): run `composer install`.
- Node.js (optional) for `fetcher.js` + Puppeteer rendering.
- If using Puppeteer, ensure Chromium and system libraries are installed.

Quick start
1. Install PHP dependencies:

```bash
composer install
```

2. (Optional) Install Node dependencies for the fetcher:

```bash
npm init -y
npm install puppeteer
```

3. Initialize the database (run once):

```bash
php db_init.php
```

4. Open `index.php` in a browser and create subscriptions.

5. Test the worker (it will call `fetcher.js` if configured and send emails via SMTP):

```bash
php worker.php
```

Configuration
- Copy `.env.example` to `.env` and fill SMTP and optional settings.
- Important environment variables:
  - `SMTP_HOST`, `SMTP_PORT`, `SMTP_USER`, `SMTP_PASS`, `SMTP_SECURE`, `SMTP_FROM`, `SMTP_FROM_NAME`
  - `NODE_PATH`, `FETCHER_SCRIPT`, `DB_FILE`

Example `.env` snippet:

```
SMTP_HOST=smtp.example.com
SMTP_PORT=587
SMTP_USER=your_smtp_user
SMTP_PASS=your_smtp_password
SMTP_SECURE=tls
SMTP_FROM=monitor@example.com
SMTP_FROM_NAME="Flight Monitor"
```

Running hourly

Add to crontab (example):

```cron
0 * * * * /usr/bin/php /full/path/to/worker.php >> /full/path/to/worker.log 2>&1
```

Notes & troubleshooting
- PHPMailer is required. `worker.php` will log an error and return false if `vendor/autoload.php` is missing — run `composer install`.
- The Ctrip site is JS-heavy and may use anti-bot measures; use `fetcher.js` (Puppeteer) to render pages.
- If prices are not parsed, inspect raw HTML from `fetcher.js` and adjust `parse_prices()` in `worker.php`.
- Puppeteer may require extra system libraries on Linux (libnss, libatk, etc.). See Puppeteer docs.

Files
- `index.php` — subscription UI
- `subscribe.php` — save subscriptions to SQLite
- `db_init.php` — create SQLite DB (run once)
- `worker.php` — hourly worker and email sender
- `fetcher.js` — optional Node Puppeteer fetcher
- `airports_cn_prov.json` — China airports grouped by province
- `composer.json` — PHP dependency manifest (PHPMailer)

Next steps I can do for you
- Add a ready `.env` template with masked values, or
- Add a systemd unit / improved cron wrapper and logging.
