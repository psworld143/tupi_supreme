# Admin Console Installation Guide

## Quick Start

### Step 1: Import Database
1. Open phpMyAdmin or MySQL command line
2. Import the `database.sql` file:
   ```sql
   mysql -u root -p < database.sql
   ```
   Or use phpMyAdmin: Select database → Import → Choose `database.sql`

### Step 2: Configure Database
Edit `config.php` and update these lines if needed:
```php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'tsaci_cms');
```

### Step 3: Set Permissions
Create upload directories (if they don't exist):
```bash
mkdir -p ../uploads/images
mkdir -p ../uploads/documents
chmod 755 ../uploads
chmod 755 ../uploads/images
chmod 755 ../uploads/documents
```

### Step 4: Access Admin Console
Navigate to: `http://localhost/tupi_supreme/tsaci/admin/login.php`

**Default Login:**
- Username: `admin`
- Password: `admin123`

⚠️ **IMPORTANT:** Change the default password immediately after first login!

## Features Overview

### Content Management Pages
- **Pages** (`pages.php`) - Manage dynamic content sections for all website pages
- **Products** (`products.php`) - Full product catalog management
- **Services** (`services.php`) - Service offerings management
- **Case Studies** (`case-studies.php`) - Client success stories
- **Gallery** (`gallery.php`) - Image gallery management
- **Resources** (`resources.php`) - Document and file management
- **Certifications** (`certifications.php`) - Certifications and compliance tracking
- **Messages** (`messages.php`) - Contact form submissions

### Dashboard Features
- Statistics overview
- Quick actions
- Recent activity log
- Unread messages counter

## Database Tables

The system uses the following tables:
- `admin_users` - Admin accounts
- `page_content` - Dynamic page content
- `products` - Product catalog
- `services` - Service offerings
- `case_studies` - Case studies
- `gallery_images` - Gallery images
- `resources` - Downloadable resources
- `certifications` - Certifications
- `contact_messages` - Contact form messages
- `activity_logs` - Admin activity tracking

## Security Features

- Password hashing (bcrypt)
- Session-based authentication
- SQL injection prevention (prepared statements)
- XSS protection (input sanitization)
- Activity logging
- Role-based access control

## Troubleshooting

### Can't login?
- Check database connection in `config.php`
- Verify database was imported correctly
- Check if admin user exists: `SELECT * FROM admin_users;`

### Images not uploading?
- Check upload directory permissions
- Verify `UPLOAD_DIR` path in `config.php`
- Check PHP upload settings in `php.ini`

### Database errors?
- Verify database credentials in `config.php`
- Check if database `tsaci_cms` exists
- Ensure all tables were created

## Next Steps

1. Change default admin password
2. Add your first page content
3. Upload products and services
4. Configure gallery images
5. Set up resources and certifications

For detailed documentation, see `README.md`

