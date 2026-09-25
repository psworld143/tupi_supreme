# TSACI Admin Console

Complete admin module for managing dynamic content across all website pages.

## Installation

1. **Import Database Schema**
   ```bash
   mysql -u root -p < database.sql
   ```
   Or use phpMyAdmin to import `database.sql`

2. **Configure Database**
   Edit `config.php` and update database credentials:
   ```php
   define('DB_HOST', 'localhost');
   define('DB_USER', 'your_username');
   define('DB_PASS', 'your_password');
   define('DB_NAME', 'tsaci_cms');
   ```

3. **Set Permissions**
   Ensure the uploads directory is writable:
   ```bash
   chmod 755 uploads/
   chmod 755 uploads/images/
   chmod 755 uploads/documents/
   ```

## Default Login Credentials

- **Username:** admin
- **Password:** admin123

⚠️ **IMPORTANT:** Change the default password immediately after first login!

## Features

### Content Management
- **Page Content:** Manage dynamic content sections for all pages
- **Products:** Full CRUD for products with specifications, features, and applications
- **Services:** Manage service offerings with descriptions and benefits
- **Case Studies:** Create and manage client success stories
- **Gallery:** Upload and organize images by category
- **Resources:** Manage downloadable documents and files
- **Certifications:** Track certifications and compliance documents

### Additional Features
- **Contact Messages:** View and manage contact form submissions
- **Activity Logs:** Track all admin actions
- **User Management:** Manage admin users (for super admins)
- **Dashboard:** Overview statistics and quick actions

## File Structure

```
admin/
├── config.php              # Database and system configuration
├── database.sql            # Database schema
├── login.php               # Admin login page
├── logout.php              # Logout handler
├── index.php               # Admin dashboard
├── pages.php               # Page content management
├── products.php            # Products management
├── services.php            # Services management
├── case-studies.php        # Case studies management
├── gallery.php             # Gallery management
├── resources.php           # Resources management
├── certifications.php      # Certifications management
├── messages.php            # Contact messages
├── includes/
│   └── navbar.php         # Navigation bar component
└── api/
    └── get_stats.php       # API endpoint for statistics
```

## Usage

### Accessing Admin Console
Navigate to: `http://localhost/tupi_supreme/tsaci/admin/login.php`

### Managing Page Content
1. Go to **Pages** in the admin menu
2. Click **Add New Content**
3. Select the page and enter section name
4. Choose content type (text, HTML, JSON, or image URL)
5. Enter content and save

### Managing Products
1. Go to **Products** in the admin menu
2. Click **Add New Product**
3. Fill in product details:
   - Name (required)
   - Description
   - Specifications
   - Features
   - Applications
   - Image URL
   - Display order
   - Featured status
   - Active status

### Managing Gallery
1. Go to **Gallery** in the admin menu
2. Click **Add New Image**
3. Upload image or provide image URL
4. Select category (facilities, products, process, installations, team)
5. Add title and description

## Security Features

- Password hashing using PHP's `password_hash()`
- Session-based authentication
- SQL injection prevention with prepared statements
- XSS protection with input sanitization
- Activity logging for audit trail
- Role-based access control (super_admin, admin, editor)

## Database Tables

- `admin_users` - Admin user accounts
- `page_content` - Dynamic page content sections
- `products` - Product catalog
- `services` - Service offerings
- `case_studies` - Client case studies
- `gallery_images` - Gallery images
- `resources` - Downloadable resources
- `certifications` - Certifications and compliance
- `contact_messages` - Contact form submissions
- `activity_logs` - Admin activity tracking

## API Endpoints

### Get Statistics
- **URL:** `/admin/api/get_stats.php`
- **Method:** GET
- **Auth:** Required
- **Response:** JSON with statistics

## Notes

- All content is stored in the database
- Images can be uploaded or linked via URL
- Content supports HTML formatting using CKEditor
- Display order controls the sequence of items
- Active/inactive status controls visibility

## Support

For issues or questions, refer to the main project documentation or contact the development team.

