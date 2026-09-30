# X Business Grant

Nigerian Business Grant Application Portal - A PHP application with Tailwind CSS frontend for managing business grant applications in Nigeria.

## Features

- **Public Portal**: Businesses can apply for grants (₦500,000 - ₦5,000,000)
- **Document Upload**: CAC Certificate, ID Card, Business Plan, Bank Statement, Tax Clearance
- **Nigerian Context**: All 36 states, CAC validation, Nigerian phone number format
- **Application Tracking**: Applicants can check their application status
- **Admin Dashboard**: Full application management with status updates and filters
- **Responsive Design**: Mobile-first with Tailwind CSS

## Requirements

- PHP 7.4 or higher
- MySQL 5.7+ or MariaDB 10.2+
- Apache/Nginx web server
- mod_rewrite (for clean URLs, optional)

## Installation

### 1. Clone the Project

```bash
git clone <repository-url> "X Business Grants"
cd "X Business Grants"
```

### 2. Setup Database

Create the database and import the schema:

```bash
mysql -u root -p < database/schema.sql
```

Or through phpMyAdmin:
- Create database named `xbusiness_grants`
- Import `database/schema.sql`

### 3. Configure Database Connection

Edit `includes/config.php`:

```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'xbusiness_grants');
define('DB_USER', 'your_username');
define('DB_PASS', 'your_password');
```

### 4. Create Upload Directory

```bash
mkdir -p uploads/cac_certificate uploads/id_card uploads/business_plan uploads/bank_statement uploads/tax_clearance
chmod 755 uploads
```

### 5. Access the Application

- **Website**: http://localhost/X%20Business%20Grants/
- **Admin**: http://localhost/X%20Business%20Grants/admin/
- **Status Check**: http://localhost/X%20Business%20Grants/status.php

### 6. Admin Login

Default credentials (change immediately in production):
- Username: `admin`
- Password: `admin123`

## Project Structure

```
├── admin/                  # Admin dashboard
│   ├── index.php          # Dashboard home
│   ├── applications.php   # Applications list
│   ├── get_application.php # AJAX application details
│   ├── login.php          # Admin login
│   └── logout.php         # Admin logout
├── database/
│   └── schema.sql         # Database schema
├── includes/
│   ├── config.php         # Configuration
│   ├── Database.php       # Database connection
│   ├── Application.php    # Application model
│   └── helpers.php        # Helper functions
├── uploads/               # Uploaded documents
├── index.php             # Public website
├── status.php            # Application status check
├── composer.json         # PHP dependencies
└── README.md            # This file
```

## Nigerian Business Requirements

### Required Documents
1. **CAC Certificate** - Corporate Affairs Commission registration
2. **Director's ID** - NIN, Voter's Card, or International Passport
3. **Business Plan** - Documented business plan with objectives

### Optional Documents (Recommended)
4. **Bank Statement** - 6 months bank statement
5. **Tax Clearance** - Current tax clearance certificate

### CAC Number Format
- Private Company: `RC123456`
- Business Name: `BN123456`

### Phone Number Format
- 080XXXXXXXX, 081XXXXXXXX, etc.
- Or +23480XXXXXXXX, +23481XXXXXXXX

## Application Status Flow

```
Pending → Under Review → Approved
                       → Rejected
```

## Security Features

- Password hashing with bcrypt
- SQL injection prevention via prepared statements
- File type validation
- File size limits (10MB max)
- Input sanitization

## Customization

### Grant Amount Range
Edit `includes/config.php`:

```php
define('GRANT_AMOUNT_MIN', 500000);
define('GRANT_AMOUNT_MAX', 5000000);
```

### Site Name
Edit `includes/config.php`:

```php
define('SITE_NAME', 'X Business Grant');
```

## Support

For issues or questions, contact:
- Email: support@xbusinessgrant.ng
- Phone: 0800-XBG-HELP

## License

© <?php echo date('Y'); ?> X Business Grant. All rights reserved.
