# WP User Feedback (Under Development 🚧)

Lightweight WordPress plugin to collect and manage user feedback via **shortcodes**, **Gutenberg blocks**, and a **Vue 3 powered admin UI**.  
⚠️ **Note:** This plugin is still in **active development**. APIs and blocks may change before stable release.

---

## Features (Current)

- Frontend feedback form (shortcode + block).
- Results list for admins (shortcode + block).
- Custom DB table with safe CRUD operations.
- Admin UI (Vue 3) with pagination, edit & delete.
- REST API endpoints with nonce + capability checks.
- PHPCS-compliant (WordPress Coding Standards).

---

## TODO / Roadmap

- [ ] Gutenberg block editor improvements:
  - Add **edit.js** customization (Inspector controls, attributes, settings).
  - Support block-level customization (title, placeholders, per-page options).
- [ ] Add block variations (different layouts for results table).
- [ ] Extend admin UI with filters (search by name/email/subject).
- [ ] Add export functionality (CSV/JSON).
- [ ] Improve caching for DB queries.
- [ ] New Upcoming feature will introduce like Custom email notifications, Captcha, Integrations with 3rd party CRM etc.
- [ ] Testing suite (PHPUnit + Playwright for e2e).
- [ ] Documentation for developers and contributors.

---

## Requirements

- WordPress 6.0+
- PHP 7.4+ (8.x recommended)
- Node 18+ (for building from source)
- Composer (for release build if vendor needed)

---

## Installation (Prebuilt ZIP)

1. Download `dist/wp-user-feedback.zip`.
2. Upload via **Plugins → Add New → Upload Plugin**.
3. Activate the plugin.

---

## Shortcodes

- `[wp_user_feedback_form]` – renders the feedback form.
- `[wp_user_feedback_results]` – renders results list (admins only).

---

## Gutenberg Blocks

- **Feedback Form**
- **Feedback Result**

Search by the above names in the block inserter.

---

## Admin App (Vue 3)

An admin page is added under **User Feedback**:

- **All Feedback** → list, edit, delete; paginated via REST.
- **Info** → usage notes and available shortcodes.

---

## Developer Setup

Clone the repo & install deps:

```bash
composer install          # optional (dev)
pnpm install               # or npm install
pnpm run build             # build blocks + admin