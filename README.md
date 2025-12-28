# Flight Ticket Monitor

[English README](README.md) | [中文文档](README.zh_Hans.md)

A minimal PHP service that monitors flight prices on `flights.ctrip.com` and emails subscribers with sorted price results.

## Short summary
- Users subscribe to a one-way route (IATA codes) and date via a simple web UI (`index.php`).
- An hourly worker fetches rendered search pages (optionally via Puppeteer), extracts prices, sorts them, and emails results using PHPMailer.
- Emails include a secure unsubscribe link protected by an HMAC token.

## Features
- Web UI to create subscriptions: route (from → to), date, and recipient email (`index.php`).
- Subscriptions stored in a local SQLite database (`subscriptions.db`).
- Worker (`worker.php`) that renders pages using optional Node/Puppeteer (`fetcher.js`) and extracts prices with fallback parsing.
- Email delivery via PHPMailer + SMTP (configurable via environment variables).
- Unsubscribe endpoint (`unsubscribe.php`) with HMAC-signed token to prevent abuse.

## Quick prerequisites
- PHP 7.4+ with `pdo_sqlite` extension
- Composer (for PHPMailer)
- Node.js (optional, for `fetcher.js` + Puppeteer rendering)

## Quick start
1. Clone the repo and change into the project directory.
2. Install PHP dependencies:

```bash
composer install
```

3. (Optional) Install Node dependencies for the fetcher:

```bash
npm init -y
npm install puppeteer
```

4. Initialize the database (run once):

```bash
php db_init.php
```

5. Open `index.php` in a browser to create subscriptions.

6. Run the worker (hourly by cron or manually):

```bash
php worker.php
```

## Configuration (.env)
Create a `.env` file in the project root (see `.env.example`) and fill SMTP credentials and other options. Example values:

```
# SMTP server
SMTP_HOST=smtp.example.com
SMTP_PORT=587
SMTP_USER=your_smtp_user@example.com
SMTP_PASS=your_smtp_password
SMTP_SECURE=tls
SMTP_FROM=monitor@example.com
SMTP_FROM_NAME="Flight Monitor"

# Optional: node/fetcher
NODE_PATH=/usr/local/bin/node
FETCHER_SCRIPT=fetcher.js

# SQLite DB file
DB_FILE=subscriptions.db

# Unsubscribe HMAC secret (use a strong random string)
UNSUBSCRIBE_SECRET=replace_this_with_a_strong_random_value
```

## Security notes
- Do NOT commit `.env` or `.unsubscribe_secret` to git. The project includes a `.gitignore` entry for `.unsubscribe_secret` and you should add `.env` as well.
- You can either set `UNSUBSCRIBE_SECRET` in `.env` or let the app persist an auto-generated secret into `.unsubscribe_secret` (created on first run).

## Testing locally (fast checklist)
- Install Composer and PHP deps (`composer install`).
- Initialize DB (`php db_init.php`).
- Insert a sample subscription for testing:

```bash
php scripts/insert_test.php
```

- Generate an unsubscribe URL/token for a subscription:

```bash
php scripts/gen_token.php 1
```

- Test the unsubscribe handler locally via the CLI wrapper:

```bash
php scripts/run_unsubscribe.php 1 test@example.com <token>
php scripts/list_subs.php  # confirm deletion
```

- To exercise `worker.php` without delivering emails, either configure a local SMTP server or inspect the generated email body. (You can modify `worker.php` to write `$body` to a file for dry-run testing.)

## Important scripts
- `scripts/insert_test.php` — insert a sample subscription
- `scripts/gen_token.php` — print unsubscribe token and URL for a subscription
- `scripts/run_unsubscribe.php` — call `unsubscribe.php` from CLI for testing
- `scripts/list_subs.php` — list subscriptions from the DB

## Files overview
- `index.php` — subscription UI
- `subscribe.php` — save subscriptions
- `db_init.php` — create SQLite DB
- `worker.php` — hourly worker and email sender
- `unsubscribe.php` — secure unsubscribe endpoint
- `lib/unsubscribe.php` — token generation & verification helpers
- `fetcher.js` — optional Puppeteer renderer
- `subscriptions.db` — SQLite DB file (created by `db_init.php`)

## Running hourly
Add to crontab (example):

```cron
0 * * * * /usr/bin/php /full/path/to/worker.php >> /full/path/to/worker.log 2>&1
```

## Troubleshooting
- PHPMailer missing: run `composer install` in project root to install dependencies.
- Node/Puppeteer issues: ensure Chromium and required libs are installed per Puppeteer documentation.
- If prices fail to parse: capture raw HTML (from `fetcher.js`) and adjust `parse_prices()` in `worker.php`.

## Contributing
- Improve parsing rules or add time-limited unsubscribe tokens if desired.
- Please avoid committing secrets; use `.env` for environment-specific values.

## License
- MIT.