# Tupi Supreme — Architecture Index

A structured map of the `tupi_supreme` codebase. Generated for orientation and navigation.

**Project root:** `C:\wamp64\www\tupi_supreme`
**Platform:** Windows / WAMP64 (Apache + MariaDB + PHP 8.x)
**Stack:** Pure PHP (no framework), Tailwind CSS via CDN, Font Awesome, MySQL/MariaDB (`tsaci_cms`)

---

## 1. High-Level Layout

```
tupi_supreme/
├── README.md                       # Top-level project guide (TSACI + TSUCOVI overview)
├── AGENTS.md                       # THIS FILE — architecture index
├── documentation_backup/           # Shared + TSUCOVI docs (read-only reference)
└── tsaci/                          # TSACI website + CMS (the active codebase)
    ├── *.php                       # Public-facing website pages
    ├── includes/                   # Shared frontend config + partials (+ vendored PHPMailer)
    ├── admin/                      # Admin console (CMS back-office)
    ├── database/                   # SQL schema dump
    ├── sessions/                   # Fallback PHP session save path (htaccess-protected)
    ├── uploads/                    # User-uploaded images & documents
    └── documentation_backup/       # TSACI-specific docs (reference)
```

Two sister companies share this repo:
- **TSACI** — Tupi Supreme Activated Carbon, Inc. (activated carbon / municipal water treatment) — implemented in `tsaci/`.
- **TSUCOVI** — Tupi Supreme Coco Ventures Inc. (coconut food products) — documentation only, no website yet.

---

## 2. TSACI Public Website (`tsaci/`)

Pure-PHP pages that pull dynamic content from the `tsaci_cms` database via `includes/config.php`.

### 2.1 Page Controllers (root of `tsaci/`)
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
| `contact.php` | Contact form (saves to `contact_messages`), office hours, map |
| `check_address.php` | Address lookup helper used by contact form |
| `index_dynamic.php` | Alternate dynamic homepage variant |
| `index_static_backup.php` | Pre-CMS static homepage backup |

### 2.2 Shared Includes (`tsaci/includes/`)
| File | Role |
|------|------|
| `config.php` | DB singleton (`Database`), `getDB()`, and ~25 content fetchers: `getSiteSetting`, `getPageContent`, `getHomepageFeatures`, `getStatistics`, `getApplications`, `getServiceItems`, `getProducts`, `getProductTabs`, `filterProductsByKeywords`, `getProductsForTab`, `getServices`, `getCaseStudies`, `getCertifications`, `getResources`, `getOtherResources`, `getFAQs`, `getGalleryImages`, `getAboutContent`, `getCompanyValues`, `getTimelineEvents`, `getSocialMedia`, `getFooterLinks`, `saveContactMessage`, `getContactInfo`, `getOfficeHours`, `getContactSubjectOptions`, `getCarouselSlides`. Also escape helpers `htmlspecialchars_safe` / `nl2br_safe`. |
| `navbar.php` | Top nav + mobile menu (active-link highlighting via `$current_page`) |
| `footer.php` | Footer with dynamic links, social media, contact info |
| `logo_pulse_loader.php` | Reusable brand loading animation — fullscreen overlay or inline (`logo_pulse_inline()`); configurable via `$logo_pulse_*` vars set before include |
| `PHPMailer/` | Vendored PHPMailer v6.10.0 (`PHPMailer.php`, `SMTP.php`, `Exception.php`), no composer — used by admin mailer |

**Frontend config pattern:** pages `require_once 'includes/config.php'`, set `$current_page`, fetch dynamic content with defaults, then render HTML. Tailwind theme colors: `primary #2c5530`, `secondary #4a7c59`, `accent #8bc34a`, `dark #1a1a1a`, `light #f8f9fa`.

---

## 3. TSACI Admin Console (`tsaci/admin/`)

Standalone CMS back-office. Separate `config.php` (defines `ADMIN_ACCESS`, starts its own session `TSACI_ADMIN_SESSION`, 8h cookie lifetime / 30-day GC for "Remember me"). `requireLogin()` also enforces a 20-minute idle timeout (`SESSION_IDLE_TIMEOUT`) via `$_SESSION['last_activity']`; timed-out users are redirected to `login.php?timeout=1`. Auth gates via `requireLogin()` (returns 401 JSON for `/api/` requests); actions logged via `logActivity()` to `activity_logs`. Opt-in CSRF helpers: `generateCsrfToken()`, `csrfTokenField()`, `verifyCsrfToken()`.

**Session storage:** admin config sets a custom `session.save_path` — `tsaci_sessions/` next to the docroot when possible, else `tsaci/sessions/` (auto-created with deny-all `.htaccess`) — to avoid `ps_files_cleanup_dir` permission notices on hosts like lsphp.

### 3.1 Admin Pages
| File | Purpose |
|------|---------|
| `config.php` | Admin DB config, session handling, security constants, `Database` class, helpers (`isLoggedIn`, `requireLogin`, `getCurrentUser`, `logActivity`, `sanitizeInput`, `generateSlug`, `formatDate`, `redirect`, `jsonResponse`, CSRF helpers). Auto-creates `uploads/{images,documents}` and the session dir. |
| `login.php` / `logout.php` | Auth handlers (default creds `admin` / `admin123`); login page supports admin-configurable background |
| `index.php` | Dashboard — stats overview, quick actions |
| `pages.php` | CRUD for `page_content` sections |
| `site-settings.php` | CRUD for `site_settings` key/value pairs |
| `settings.php` | Current-user profile settings (incl. `profile_picture` auto-migration on `admin_users`) |
| `login-background.php` | Login-page appearance settings (bg image, overlay, colors — stored in `site_settings`) |
| `homepage-features.php` | CRUD for `homepage_features` |
| `carousel.php` | Manages `carousel_slides` |
| `statistics.php` | Manages `statistics` rows |
| `applications.php` | CRUD for `applications` (products-page Applications grid; `is_primary` = featured dark card) |
| `products.php` | CRUD for `products` |
| `product-tabs.php` | CRUD for `product_tabs` (auto-creates table; `is_system` rows protected) |
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
| `mail_config.php` | Gmail SMTP credentials & sender identity for admin replies |
| `setup_admin.php` | Bootstrap/seed admin user |
| `seed_map_url.php` | One-off seeder for contact map URL |

CRUD pages follow a common pattern: `requireLogin()`, `$action = $_GET['action'] ?? 'list'`, PRG redirects with `?status=saved` flash messages.

### 3.2 Admin Subfolders
- `admin/includes/` — `header.php`, `navbar.php`, `sidebar.php` (admin chrome), `pagination.php` (list pagination), `mailer.php` (`sendMessageReply()` via vendored PHPMailer)
- `admin/api/get_stats.php` — JSON stats endpoint (auth required)
- `admin/api/upload_image.php`, `admin/api/upload_document.php` — upload endpoints

### 3.3 Admin SQL
| File | Purpose |
|------|---------|
| `database.sql` | Admin schema (subset) |
| `database_schema_update.sql` | Schema migration deltas — creates `applications` and `service_items` |
| `seed_data.sql` | Master seed (largest) |
| `seed_about_data.sql`, `seed_gallery_data.sql`, `seed_contact_map_data.sql` | Targeted seeders |
| `fix_bullet_encoding.sql` | One-off encoding fix for bullet characters |

### 3.4 Admin Docs
`README.md`, `INSTALLATION.md`, `MIGRATION_GUIDE.md`, `DYNAMIC_CONTENT_SUMMARY.md`

---

## 4. Database Schema (`tsaci_cms`)

Main dump `tsaci/database/tsaci_cms.sql` (~1,470 lines) defines 26 tables; `applications` and `service_items` are added by `admin/database_schema_update.sql` (also auto-created by their admin pages). 28 logical tables total:

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

**Connection:** MySQLi, singleton `Database` class, `utf8mb4`. Frontend config tolerates a missing DB (returns defaults / empty arrays); admin config dies on failure.

---

## 5. Documentation (`documentation_backup/`)

Read-only reference material, not part of the runtime.

### 5.1 Root `documentation_backup/` (shared + TSUCOVI)
- `BUSINESS_CONTEXT.md`, `COMPANY_SEPARATION_ANALYSIS.md`, `ANALYSIS_SUMMARY.md`, `ORGANIZATION_SUMMARY.md`
- `WEBSITE_REQUIREMENTS_COMPARISON.md`, `WEBSITE_REQUIREMENTS_MASTER.md`
- `TSUCOVI_PROFILE.md`, `TSUCOVI_WEBSITE_REQUIREMENTS.md`

### 5.2 `tsaci/documentation_backup/` (TSACI-specific)
- `TSACI_PROFILE.md`, `TSACI_WEBSITE_REQUIREMENTS.md`, `SYSTEM_ANALYSIS.md`
- `IMPLEMENTATION_STATUS.md`, `IMPLEMENTATION_PROGRESS_REPORT.md`, `IMPLEMENTATION_COMPLETE.md`, `FINAL_IMPLEMENTATION_STATUS.md`
- `REQUIREMENTS_VS_IMPLEMENTATION.md`, `COMPLIANCE_SUMMARY.md`, `README.md`

### 5.3 Master doc
- `tsaci/MASTER_DOCUMENTATION.md` — consolidated single-source-of-truth for TSACI (project overview, business profile, requirements, system analysis, implementation status, compliance, mobile-responsiveness notes). ~650+ lines.

---

## 6. Conventions & Patterns

- **No framework / no composer.** Plain PHP pages with inline HTML + Tailwind CDN + Font Awesome CDN.
- **Two parallel `Database` singletons:** `tsaci/includes/config.php` (frontend, fail-soft) and `tsaci/admin/config.php` (admin, fail-hard). Both define `DB_HOST/USER/PASS/NAME` for `tsaci_cms`. Frontend guards with `if (!defined(...))` and `if (!class_exists(...))` so it can be included alongside admin config.
- **Content fetchers** in frontend `config.php` use prepared statements for single-row lookups and `real_escape_string` for dynamic `WHERE`/`LIMIT` in list queries.
- **Active state** in nav: pages set `$current_page` before including `navbar.php`.
- **Admin CRUD pattern:** `?action=list|add|edit|delete`, PRG redirects, `?status=saved` flashes, shared `pagination.php`.
- **Lightweight auto-migrations:** several admin pages `CREATE TABLE IF NOT EXISTS` / `ALTER TABLE` idempotently on load (e.g. `product-tabs.php`, `settings.php`).
- **Uploads** live in `tsaci/uploads/{images,documents}`, auto-created by admin config. Max 10 MB; allowed MIME types defined in admin `config.php`.
- **Timezone:** `Asia/Manila` (admin config).
- **Roles:** `super_admin`, `admin`, `editor` (RBAC referenced in admin README).
- `tsaci.zip` in `tsaci/` is a stray archive, not part of the runtime.

---

## 7. Known Gaps

- Contact form still missing CSRF, CAPTCHA, honeypot, rate limiting; frontend email uses raw `mail()`. (CSRF helpers exist in admin config but are opt-in and not wired to the public form.)
- TSUCOVI website not yet implemented (docs only).
- Resolved since last index: `products.php` was restructured around dynamic `product_tabs`.

---

## 8. Quick Navigation

- Public site URL (local): `http://localhost/tupi_supreme/tsaci/`
- Admin URL (local): `http://localhost/tupi_supreme/tsaci/admin/login.php`
- Frontend entry config: <ref_file file="C:\wamp64\www\tupi_supreme\tsaci\includes\config.php" />
- Admin entry config: <ref_file file="C:\wamp64\www\tupi_supreme\tsaci\admin\config.php" />
- DB schema: <ref_file file="C:\wamp64\www\tupi_supreme\tsaci\database\tsaci_cms.sql" />
- Master docs: <ref_file file="C:\wamp64\www\tupi_supreme\tsaci\MASTER_DOCUMENTATION.md" />
