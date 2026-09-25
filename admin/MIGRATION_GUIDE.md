# Dynamic Content Migration Guide

## Overview
All pages have been migrated to pull content from the database instead of hardcoded values. The database schema has been updated and seed data has been populated.

## Database Setup

### Step 1: Run Schema Updates
```bash
mysql -u root tsaci_cms < admin/database_schema_update.sql
```

### Step 2: Seed Initial Data
```bash
mysql -u root tsaci_cms < admin/seed_data.sql
```

## Files Created

### 1. Database Schema (`database_schema_update.sql`)
- `site_settings` - Company info, contact details, meta tags
- `homepage_features` - Homepage feature cards
- `statistics` - Statistics/numbers displayed on homepage
- `about_content` - About page sections
- `company_values` - Company values/vision
- `timeline_events` - Company timeline
- `team_members` - Team member profiles
- `social_media` - Social media links
- `footer_links` - Footer navigation links

### 2. Frontend Config (`includes/config.php`)
Provides helper functions:
- `getSiteSetting($key, $default)` - Get site settings
- `getPageContent($page, $section, $default)` - Get page content
- `getHomepageFeatures()` - Get homepage features
- `getStatistics()` - Get statistics
- `getProducts($limit, $featured_only)` - Get products
- `getServices($limit)` - Get services
- `getCaseStudies($limit, $featured_only)` - Get case studies
- `getCertifications($limit)` - Get certifications
- `getResources($category, $limit)` - Get resources
- `getGalleryImages($category, $limit)` - Get gallery images
- `getAboutContent($section)` - Get about page content
- `getCompanyValues()` - Get company values
- `getTimelineEvents()` - Get timeline events
- `getSocialMedia()` - Get social media links
- `getFooterLinks($category)` - Get footer links
- `saveContactMessage(...)` - Save contact form messages

### 3. Shared Includes
- `includes/navbar.php` - Dynamic navigation bar
- `includes/footer.php` - Dynamic footer

## Page Update Pattern

Each page should follow this pattern:

```php
<?php
require_once 'includes/config.php';

$current_page = 'page-name'; // for navigation highlighting

// Get dynamic content
$page_title = getPageContent('page-name', 'section_name', 'Default Value');
$data = getProducts(); // or getServices(), etc.

// Include shared components
include 'includes/navbar.php';
// ... page content ...
include 'includes/footer.php';
?>
```

## Pages Status

### ✅ Completed
- `index.php` - Fully dynamic
- `contact.php` - Form saves to database, contact info from DB

### 🔄 To Update (Pattern Provided)
- `about.php` - Use `getAboutContent()`, `getCompanyValues()`, `getTimelineEvents()`
- `products.php` - Use `getProducts()`
- `services.php` - Use `getServices()`
- `case-studies.php` - Use `getCaseStudies()`
- `certifications.php` - Use `getCertifications()`
- `resources.php` - Use `getResources()`
- `gallery.php` - Use `getGalleryImages()`

## Admin Console Updates Needed

Add management pages for:
- Site Settings (`admin/site-settings.php`)
- Homepage Features (`admin/homepage-features.php`)
- Statistics (`admin/statistics.php`)
- About Content (`admin/about-content.php`)
- Company Values (`admin/company-values.php`)
- Timeline Events (`admin/timeline-events.php`)
- Social Media (`admin/social-media.php`)
- Footer Links (`admin/footer-links.php`)

## Testing Checklist

- [ ] All pages load without errors
- [ ] Content displays from database
- [ ] Navigation highlights current page
- [ ] Footer displays correct links
- [ ] Contact form saves to database
- [ ] Admin can edit all content types
- [ ] Images load correctly
- [ ] Mobile menu works
- [ ] All links work

## Notes

- Original static files backed up as `*_static_backup.php`
- Database connection fails gracefully if DB unavailable
- All content has fallback defaults
- HTML content is properly escaped for security

