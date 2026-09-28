# Tupi Supreme — Architecture Index

A structured map of the `tupi_supreme` codebase. Generated for orientation and navigation.

**Project root:** `C:\wamp64\www\tupi_supreme` (the site IS the repo root — the former `tsaci/` subfolder was flattened away)
**Platform:** Windows / WAMP64 (Apache + MariaDB + PHP 8.x)
**Stack:** Pure PHP (no framework), Tailwind CSS via CDN, Font Awesome, MySQL/MariaDB (`tsaci_cms`)

---

## 1. High-Level Layout

```
tupi_supreme/                       # Repo root == web root for TSACI
├── README.md                       # Project guide (STALE — still shows old tsaci/ layout)
├── AGENTS.md                       # THIS FILE — architecture index
├── MASTER_DOCUMENTATION.md         # Consolidated TSACI doc (project → compliance)
├── .htaccess                       # Security headers; denies .sql/.zip/.ini/.log/.sh/sess_*
├── .gitignore                      # Ignores config.php, db_credentials.php,
│                                   #   mail_config.php, mail.ini, sessions/*, *.zip
├── .github/workflows/deploy.yml    # GitHub Actions: php -l lint + rsync deploy on push to main
├── *.php                           # Public-facing website pages
├── includes/                       # Shared frontend config + partials (+ vendored PHPMailer)
├── admin/                          # Admin console (CMS back-office)
├── database/                       # SQL schema dump (tsaci_cms.sql)
├── sessions/                       # PHP session save path (deny-all .htaccess; contents gitignored)
├── uploads/                        # User uploads — images/ + documents/ (.htaccess blocks execution)
└── documentation_backup/           # Archived TSACI docs (read-only reference)
```

This repo is **TSACI-only** now — Tupi Supreme Activated Carbon, Inc. (activated carbon / municipal water treatment). The sister company TSUCOVI (coconut food products) and all shared/TSUCOVI docs were removed; separation notes survive inside `MASTER_DOCUMENTATION.md`.

**Secret/config files are gitignored** (`config.php`, `db_credentials.php`, `mail_config.php`, `mail.ini`) — they exist on localhost/server only; search/glob tools will not list them.

---

## 2. TSACI Public Website (repo root)

Pure-PHP pages that pull dynamic content from the `tsaci_cms` database via `includes/config.php`.

### 2.1 Page Controllers (root)
| File | Purpose |
|------|---------|
| `index.php` | Homepage — hero, carousel, features, stats, featured products, CTAs |
| `about.php` | Company story, mission/vision, timeline, values, team |
| `products.php` | Product catalog organized into dynamic tabs (`product_tabs` table; keyword-matched via `getProductsForTab`) |
| `services.php` | Service offerings, process steps, testimonials |
| `case-studies.php` | Municipal water-treatment success stories |
| `resources.php` | Downloadable data sheets, catalogs, application guides, FAQs |
| `certifications.php` | ISO certs, quality systems, testing/compliance |
| `gallery.php` | Image gallery by category |
| `contact.php` | Contact form → `contact_messages` table. Hardened: CSRF token, `website` honeypot, 5/hr per-session rate limit (`CONTACT_MAX_PER_HOUR`), SMTP notification via `sendContactNotification` (fails soft) |
| `upload_diag.php` | TEMPORARY diagnostic — reports whether PHP can write to `uploads/`. Not part of the site; delete before/instead of deploying |

Removed since last index: `check_address.php`, `index_dynamic.php`, `index_static_backup.php`.

### 2.2 Shared Includes (`includes/`)
| File | Role |
|------|------|
| `config.php` | DB singleton (`Database`), `getDB()`, ~25 content fetchers (`getSiteSetting`, `getPageContent`, `getHomepageFeatures`, `getStatistics`, `getApplications`, `getServiceItems`, `getProducts`, `getProductTabs`, `filterProductsByKeywords`, `getProductsForTab`, `getServices`, `getCaseStudies`, `getCertifications`, `getResources`, `getOtherResources`, `getFAQs`, `getGalleryImages`, `getAboutContent`, `getCompanyValues`, `getTimelineEvents`, `getSocialMedia`, `getFooterLinks`, `saveContactMessage`, `getContactInfo`, `getOfficeHours`, `getContactSubjectOptions`, `getCarouselSlides`, `sendContactNotification`), escape helpers, and frontend CSRF helpers (`csrfTokenField`, `verifyCsrfToken`). **GITIGNORED** — requires `db_credentials.php`. |
| `db_credentials.php` | `DB_HOST/DB_USER/DB_PASS/DB_NAME` constants. **GITIGNORED**; template is `db_credentials.example.php` |
| `navbar.php` | Top nav + mobile menu (active-link highlighting via `$current_page`) |
| `footer.php` | Footer with dynamic links, social media, contact info |
| `logo_pulse_loader.php` | Reusable brand loading animation — fullscreen overlay or inline (`logo_pulse_inline()`); configurable via `$logo_pulse_*` vars set before include |
| `PHPMailer/` | Vendored PHPMailer v6.10.0 (`PHPMailer.php`, `SMTP.php`, `Exception.php`), no composer — used by admin mailer and contact-form notification |

**Frontend config pattern:** pages `require_once 'includes/config.php'`, set `$current_page`, fetch dynamic content with defaults, then render HTML. Tailwind theme colors: `primary #2c5530`, `secondary #4a7c59`, `accent #8bc34a`, `dark #1a1a1a`, `light #f8f9fa`.

---

## 3. TSACI Admin Console (`admin/`)

Standalone CMS back-office. Separate `config.php` (**gitignored**; defines `ADMIN_ACCESS`, `MAX_LOGIN_ATTEMPTS`, session `TSACI_ADMIN_SESSION`, 8h cookie lifetime / 30-day GC for "Remember me"). `requireLogin()` also enforces a 20-minute idle timeout (`SESSION_IDLE_TIMEOUT`) via `$_SESSION['last_activity']`; timed-out users are redirected to `login.php?timeout=1`. Auth gates via `requireLogin()` (returns 401 JSON for `/api/` requests); actions logged via `logActivity()` to `activity_logs`. CSRF helpers: `generateCsrfToken()`, `csrfTokenField()`, `verifyCsrfToken()` — wired into login and admin forms.

**Session storage:** admin config sets a custom `session.save_path` — `tsaci_sessions/` next to the docroot when possible, else `sessions/` inside the site (auto-created with deny-all `.htaccess`) — to avoid `ps_files_cleanup_dir` permission notices on hosts like lsphp.

**Login hardening** (`login.php` + `includes/session.php`): progressive per-IP lockout backed by the auto-created `login_attempts` table — `MAX_LOGIN_ATTEMPTS` consecutive failures escalate 60s → 300s → 900s lockout; CSRF check on POST; `session_regenerate_id(true)` on success; remember-me reissues the session cookie with `secure`/`httponly`/`samesite=Lax` for 30 days.

### 3.1 Admin Pages
| File | Purpose |
|------|---------|
| `config.php` | Admin DB config, session handling, security constants, `Database` class, helpers (`isLoggedIn`, `requireLogin`, `getCurrentUser`, `logActivity`, `sanitizeInput`, `generateSlug`, `formatDate`, `redirect`, `jsonResponse`, CSRF helpers). Auto-creates `uploads/{images,documents}` and the session dir. **GITIGNORED** |
| `login.php` / `logout.php` | Auth handlers (default creds `admin` / `admin123`); lockout + CSRF + remember-me; login page supports admin-configurable background |
| `index.php` | Dashboard — stats overview, quick actions |
| `pages.php` | CRUD for `page_content` sections |
| `site-settings.php` | CRUD for `site_settings` key/value pairs |
| `settings.php` | Current-user profile settings (incl. `profile_picture` upload — validated via `includes/uploads.php`) |
| `login-background.php` | Login-page appearance settings (bg image, overlay, colors — stored in `site_settings`) |
| `homepage-features.php` | CRUD for `homepage_features` |
| `carousel.php` | Manages `carousel_slides` |
| `statistics.php` | Manages `statistics` rows |
| `applications.php` | CRUD for `applications` (products-page Applications grid; `is_primary` = featured dark card) |
| `products.php` | CRUD for `products` |
| `product-tabs.php` | CRUD for `product_tabs` (`is_system` rows protected) |
| `services.php` | CRUD for `services` |
| `service-items.php` | CRUD for `service_items` (services-page process steps + feature rows; `section` = `process`/`features`) |
| `case-studies.php` | CRUD for `case_studies` |
| `resources.php` | CRUD for `resources` |
| `certifications.php` | CRUD for `certifications` |
| `faqs.php` | CRUD for `faqs` |
| `gallery.php` | CRUD for `gallery_images` (upload/URL) |
| `about.php` | Manages `about_content` sections |
| `company-values.php` | CRUD for `company_values` |
| `timeline.php` | Manages `timeline_events` |
| `team-members.php` | CRUD for `team_members` |
| `testimonials.php` | CRUD for `testimonials` |
| `contact-info.php` | CRUD for `contact_info` |
| `office-hours.php` | CRUD for `office_hours` |
| `subject-options.php` | CRUD for `contact_subject_options` |
| `footer-links.php` | CRUD for `footer_links` |
| `social-media.php` | CRUD for `social_media` |
| `messages.php` | Views `contact_messages`; can send email replies via Gmail SMTP |
| `mail_config.php` | Defines `SMTP_*` constants for admin replies (loads credentials from `mail.ini`). **GITIGNORED** |
| `mail.ini` / `mail.ini.example` | Gmail SMTP credentials (`username`/`password` app password). `mail.ini` is **GITIGNORED**; copy the example and fill in |

Removed since last index: `setup_admin.php`, `seed_map_url.php`.

CRUD pages follow a common pattern: `requireLogin()`, `$action = $_GET['action'] ?? 'list'`, PRG redirects with `?status=saved` flash messages.

### 3.2 Admin Subfolders
- `admin/includes/` — `header.php`, `navbar.php`, `sidebar.php` (admin chrome; sidebar includes `view-toggle.php` globally), `pagination.php` (list pagination), `mailer.php` (`sendMessageReply()` via vendored PHPMailer + `mail_config.php`), **plus newer shared helpers:**
  - `session.php` — progressive login lockout (see §3); auto-creates `login_attempts` table keyed by IP
  - `uploads.php` — server-side upload validation: `finfo` real-mime sniffing (+ `getimagesize` for images), verified-mime → safe-extension whitelist (`jpg/png/gif/webp`, `pdf/doc/docx`), never trusts `$_FILES['type']` or the client filename
  - `view-toggle.php` — injected List/Grid card view for every `.min-w-full` admin table; cards are restyled `<tr>`s (no DOM cloning, inline forms keep working); preference in `localStorage`, grid default <768px
- `admin/api/get_stats.php` — JSON stats endpoint (auth required)
- `admin/api/upload_image.php`, `admin/api/upload_document.php` — upload endpoints (use `includes/uploads.php` validators)

### 3.3 Admin SQL
| File | Purpose |
|------|---------|
| `database.sql` | Admin schema (subset) |
| `database_schema_update.sql` | Standalone migration — `CREATE TABLE IF NOT EXISTS` for 12 tables incl. `applications`, `product_tabs`, `service_items` |
| `seed_data.sql` | Master seed (largest) |
| `seed_about_data.sql`, `seed_gallery_data.sql`, `seed_contact_map_data.sql` | Targeted seeders |
| `fix_bullet_encoding.sql` | One-off encoding fix for bullet characters |

### 3.4 Admin Docs
`README.md`, `INSTALLATION.md`, `MIGRATION_GUIDE.md`, `DYNAMIC_CONTENT_SUMMARY.md` (mentions deleted `index_static_backup.php` — stale)

---

## 4. Database Schema (`tsaci_cms`)

Main dump `database/tsaci_cms.sql` (~1,470 lines) defines 26 tables (now includes `product_tabs`); `applications` and `service_items` come from `admin/database_schema_update.sql` (also auto-created by their admin pages); `login_attempts` is auto-created by `admin/includes/session.php`. **29 logical tables total:**

| Table | Domain |
|-------|--------|
| `site_settings` | Global key/value settings (company name, meta, login-page bg, etc.) |
| `page_content` | Generic dynamic page sections (page_name + section_name) |
| `homepage_features` | Homepage feature cards |
| `carousel_slides` | Homepage carousel |
| `statistics` | Homepage stat counters |
| `product_tabs` | Products-page tab definitions (tab_key, label, icon, keywords, is_system) |
| `applications` | Products-page application cards (title, description, icon, is_primary, ordering) |
| `products` | Product catalog |
| `services` | Service offerings |
| `service_items` | Services-page process steps & feature rows |
| `case_studies` | Success stories |
| `certifications` | Certifications & compliance |
| `resources` | Downloadable docs |
| `faqs` | FAQ entries |
| `gallery_images` | Gallery images |
| `about_content` | About-page sections |
| `company_values` | Core values |
| `timeline_events` | Company timeline |
| `team_members` | Leadership team |
| `testimonials` | Client testimonials |
| `contact_info` | Contact details |
| `office_hours` | Office hours |
| `contact_subject_options` | Contact-form inquiry types |
| `contact_messages` | Contact form submissions |
| `footer_links` | Footer link groups |
| `social_media` | Social links |
| `admin_users` | Admin accounts (roles: super_admin, admin, editor; incl. `profile_picture`) |
| `activity_logs` | Admin audit trail |
| `login_attempts` | Per-IP login lockout state (auto-created; not in any dump) |

**Connection:** MySQLi, singleton `Database` class, `utf8mb4`. Credentials live in `includes/db_credentials.php` (frontend) / `admin/config.php` (admin). Frontend config tolerates a missing DB (returns defaults / empty arrays); admin config dies on failure.

---

## 5. Deployment & Server Files

| File | Purpose |
|------|---------|
| `.github/workflows/deploy.yml` | On push to `main`: `php -l` lint all PHP, then `rsync -avz --delete` over SSH to `DEPLOY_PATH`. Excludes `.git/`, `.github/`, `*.sql`, `*.zip`, `documentation_backup/`, and all secret files; `--filter 'P uploads/'` + `'P sessions/'` protect server-side uploads/sessions from `--delete`. Secrets needed: `SSH_HOST`, `SSH_PORT`, `SSH_USER`, `SSH_PRIVATE_KEY`, `DEPLOY_PATH`. ⚠️ STALE: verify step + two excludes still reference the removed `tsaci/` subdir |
| `.htaccess` (root) | `Options -Indexes`, security headers, deny web access to `.sql/.zip/.bak/.ini/.log/.sh/sess_*` |
| `uploads/.htaccess` | `Options -ExecCGI` + deny all script extensions — uploads served as static files only |
| `sessions/.htaccess` | Deny all — protects session files |

---

## 6. Documentation

- `MASTER_DOCUMENTATION.md` (root) — consolidated single-source-of-truth for TSACI (project overview, business profile, requirements, system analysis, implementation status, compliance, mobile-responsiveness notes). ~650+ lines.
- `documentation_backup/` — archived TSACI docs: `TSACI_PROFILE.md`, `TSACI_WEBSITE_REQUIREMENTS.md`, `SYSTEM_ANALYSIS.md`, `IMPLEMENTATION_STATUS.md`, `IMPLEMENTATION_PROGRESS_REPORT.md`, `IMPLEMENTATION_COMPLETE.md`, `FINAL_IMPLEMENTATION_STATUS.md`, `REQUIREMENTS_VS_IMPLEMENTATION.md`, `COMPLIANCE_SUMMARY.md`, `README.md`. Read-only reference; excluded from deploys.
- `admin/*.md` — admin install/migration/summary docs.
- `README.md` (root) — STALE: still describes the old `tsaci/` folder layout and ~65% status.

Removed since last index: the shared root `documentation_backup/` with all TSUCOVI docs (`BUSINESS_CONTEXT.md`, `COMPANY_SEPARATION_ANALYSIS.md`, `TSUCOVI_*`, `WEBSITE_REQUIREMENTS_*`).

---

## 7. Conventions & Patterns

- **No framework / no composer.** Plain PHP pages with inline HTML + Tailwind CDN + Font Awesome CDN.
- **Two parallel `Database` singletons:** `includes/config.php` (frontend, fail-soft) and `admin/config.php` (admin, fail-hard). Both consume `DB_*` constants; frontend guards with `if (!defined(...))` / `if (!class_exists(...))` so it can coexist with admin config.
- **Secrets are never committed:** `config.php`, `db_credentials.php`, `mail_config.php`, `mail.ini` are gitignored and exist per-environment; committed `*.example` files document the expected shape.
- **Content fetchers** in frontend `config.php` use prepared statements for single-row lookups and `real_escape_string` for dynamic `WHERE`/`LIMIT` in list queries.
- **Active state** in nav: pages set `$current_page` before including `navbar.php`.
- **Admin CRUD pattern:** `?action=list|add|edit|delete`, PRG redirects, `?status=saved` flashes, shared `pagination.php`, automatic List/Grid toggle via `view-toggle.php`.
- **Lightweight auto-migrations:** several admin pages/includes `CREATE TABLE IF NOT EXISTS` / `ALTER TABLE` idempotently on load (e.g. `product-tabs.php`, `settings.php`, `session.php`).
- **Uploads** live in `uploads/{images,documents}`, auto-created by admin config, hardened by `uploads/.htaccess` (no execution) + `includes/uploads.php` (real-mime validation). Max 10 MB.
- **Timezone:** `Asia/Manila` (admin config).
- **Roles:** `super_admin`, `admin`, `editor` (RBAC referenced in admin README).
- `upload_diag.php` (root) is a stray diagnostic — delete it; do not ship.

---

## 8. Known Gaps

- Contact form: no CAPTCHA (CSRF + honeypot + per-session rate limit now in place).
- `deploy.yml` still references the removed `tsaci/` subdirectory (verify step + two rsync excludes) — likely breaks post-deploy PHP check.
- `README.md` and `admin/DYNAMIC_CONTENT_SUMMARY.md` describe the pre-flatten `tsaci/` layout.
- TSUCOVI website not implemented; its docs were removed from the repo.

---

## 9. Quick Navigation

- Public site URL (local): `http://localhost/tupi_supreme/`
- Admin URL (local): `http://localhost/tupi_supreme/admin/login.php`
- Frontend entry config: `includes/config.php` (+ `includes/db_credentials.php`) — gitignored
- Admin entry config: `admin/config.php` — gitignored
- DB schema: <ref_file file="C:\wamp64\www\tupi_supreme\database\tsaci_cms.sql" />
- Master docs: <ref_file file="C:\wamp64\www\tupi_supreme\MASTER_DOCUMENTATION.md" />
