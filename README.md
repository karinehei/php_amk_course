# Final Task - Alko Product Catalog

## Overview

The **Alko Product Catalog** is a web-based application for filtering, searching, and browsing Alko products. Users can filter by product type, country, bottle size, price range, and energy range.

### Key Features
- **Dynamic Filtering**: Filter by type, country, bottle size, price range, energy range.
- **Responsive Pagination**: Navigate between pages.
- **Database Integration**: MySQL database with product data.
- **Automatic Price List Updates**: Fetches the latest Alko price list (sitemap discovery + manual fallback).

---

## Installation

### Prerequisites
- PHP 8.0 or higher
- Composer
- MySQL
- Apache or Nginx

### Setup

1. **Clone and install dependencies**
   ```bash
   composer install
   ```

2. **Database**
   - Create database and run `src/logic/db.sql`
   - Configure credentials in `src/logic/db_connection.php` or via env vars: `DB_HOST`, `DB_NAME`, `DB_USER`, `DB_PASS`

3. **Point document root** to the project directory (or `public_html` if applicable)

---

## CI/CD (GitHub Actions)

### CI (runs on push/PR)
- PHP 8.1, 8.2, 8.3
- `composer install`
- PHP syntax check
- `composer validate`

### Deploy (manual trigger)
- **Before first run**: Add GitHub secrets (Settings → Secrets and variables → Actions):
  - `SFTP_SERVER` – Hostinger SSH/SFTP IP (hPanel → Advanced → SSH Access)
  - `SFTP_USERNAME` – FTP/SSH username
  - `SFTP_PASSWORD` – FTP password
  - `SFTP_REMOTE_PATH` – e.g. `/home/u1234567/domains/yourdomain.com/public_html`
- **Trigger**: Actions tab → Deploy to Hostinger → Run workflow
- Hostinger uses SFTP on port 65002 (not FTP)

---

## Price List Update – How It Works

Alko has no official public API for the full price list. The app uses this strategy:

### 1. Sitemap Discovery (primary)
- Fetches `robots.txt` → parses sitemap URLs → searches for hinnasto/tekstitiedostona links
- Finds the current download URL even when Alko changes file paths
- Most durable approach; avoids hardcoding a single URL

### 2. Hardcoded Fallbacks
- If sitemap discovery finds nothing, tries known URLs
- Kept as backup; harmless if URLs are dead

### 3. Manual Upload (when download fails)
- Alko often blocks automated requests from servers
- **Workaround**: Download the price list in your browser from [alko.fi/valikoimat-ja-hinnasto](https://www.alko.fi/fi/valikoimat-ja-hinnasto), then upload the `.xlsx` file to:
  ```
  temp/alko_price_list_manual.xlsx
  ```
- The script will use this file on the next update run

### Update Schedule
- Runs automatically when cache expires (default: every 1 hour)
- On Hostinger: `exec()` is disabled, so the update runs inline on page load (first load after cache expiry may take 30–60 seconds)

---

## File Structure

### Project Root
- `index.php` – Main entry point
- `composer.json` – PHP dependencies

### `src/logic/`
- `update_price_list.php` – Downloads and imports Alko price list (sitemap discovery, sanitization, DB insert)
- `update_check.php` – Triggers update when cache expires (exec or inline)
- `data_fetch.php` – Fetches products from DB
- `data_preparation.php` – Pagination and data prep
- `db_connection.php` – Database connection
- `filter_logic.php` – Filter options from DB
- `filters.php` – Filter variables
- `config.example.php` – Example config (copy to `config.php` for local credentials)

### `src/partials/`
- `header.php`, `footer.php`, `filter_form.php`, `table_rows.php`, `pagination_logic.php`

### `scripts/`, `css/`
- `scripts/inputs.js` – Frontend interactions
- `css/styles.css` – Custom styles

### Database
- `src/logic/db.sql` – Schema for `alko_price_list` table

---

## License

Copyright (c) 2026 Karine Heinonen. All rights reserved.

See [LICENSE](LICENSE) for details.

