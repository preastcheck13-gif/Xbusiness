<?php
/**
 * X Business Grant - Complete Database Seeder
 * Run this script to create all tables and populate with sample data
 * 
 * Usage: php database/seed.php
 */

require_once __DIR__ . '/../includes/Database.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/User.php';
require_once __DIR__ . '/../includes/Application.php';

use App\Database;
use App\User;
use App\Application;
use App\Helpers;

echo "========================================\n";
echo "X Business Grant - Complete Database Seeder\n";
echo "========================================\n\n";

try {
    $db = Database::getInstance();
    
    echo "[1/15] Creating database schema...\n";
    
    // Create users table
    $db->query("
        CREATE TABLE IF NOT EXISTS users (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            full_name VARCHAR(255) NOT NULL,
            email VARCHAR(255) NOT NULL UNIQUE,
            phone VARCHAR(20) NOT NULL,
            business_registration_number VARCHAR(100),
            password_hash VARCHAR(255) NOT NULL,
            email_verified TINYINT(1) DEFAULT 0,
            last_login DATETIME,
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL,
            INDEX idx_email (email),
            INDEX idx_phone (phone),
            INDEX idx_business_reg (business_registration_number)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo "  - Users table created/verified\n";
    
    // Create admin_users table
    $db->query("
        CREATE TABLE IF NOT EXISTS admin_users (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            username VARCHAR(100) NOT NULL UNIQUE,
            email VARCHAR(255) NOT NULL UNIQUE,
            password_hash VARCHAR(255) NOT NULL,
            full_name VARCHAR(255) NOT NULL,
            role ENUM('admin', 'reviewer', 'super_admin') DEFAULT 'reviewer',
            is_active TINYINT(1) DEFAULT 1,
            last_login DATETIME,
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL,
            INDEX idx_username (username),
            INDEX idx_email (email)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo "  - Admin users table created/verified\n";
    
    // Create applications table (with all fields including user_id, owner_name, date_of_birth, bvn, business_registration_number)
    $db->query("
        CREATE TABLE IF NOT EXISTS applications (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            user_id INT UNSIGNED DEFAULT NULL,
            reference_no VARCHAR(50) NOT NULL UNIQUE,
            business_name VARCHAR(255) NOT NULL,
            cac_number VARCHAR(50) NOT NULL,
            business_type VARCHAR(100) NOT NULL,
            business_sector VARCHAR(100) NOT NULL,
            business_address TEXT NOT NULL,
            city VARCHAR(100) NOT NULL,
            state VARCHAR(100) NOT NULL,
            lga VARCHAR(100),
            phone VARCHAR(20) NOT NULL,
            email VARCHAR(255) NOT NULL,
            website VARCHAR(255),
            years_in_business INT NOT NULL,
            employee_count INT NOT NULL,
            grant_amount_requested DECIMAL(15,2) NOT NULL,
            purpose_of_grant TEXT NOT NULL,
            business_description TEXT NOT NULL,
            status ENUM('pending', 'under_review', 'approved', 'rejected') DEFAULT 'pending',
            admin_notes TEXT,
            bank_name VARCHAR(100),
            bank_code VARCHAR(10),
            bank_account_number VARCHAR(10),
            verified_account_name VARCHAR(255),
            bank_verified TINYINT(1) DEFAULT 0,
            owner_name VARCHAR(255),
            date_of_birth DATE,
            bvn VARCHAR(11),
            business_registration_number VARCHAR(100),
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL,
            INDEX idx_reference (reference_no),
            INDEX idx_user_id (user_id),
            INDEX idx_cac (cac_number),
            INDEX idx_status (status),
            INDEX idx_state (state),
            INDEX idx_sector (business_sector),
            INDEX idx_created (created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo "  - Applications table created/verified\n";
    
    // Create documents table
    $db->query("
        CREATE TABLE IF NOT EXISTS documents (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            application_id INT UNSIGNED NOT NULL,
            document_type VARCHAR(50) NOT NULL,
            file_path VARCHAR(500) NOT NULL,
            original_name VARCHAR(255) NOT NULL,
            created_at DATETIME NOT NULL,
            FOREIGN KEY (application_id) REFERENCES applications(id) ON DELETE CASCADE,
            INDEX idx_app_id (application_id),
            INDEX idx_doc_type (document_type)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo "  - Documents table created/verified\n";
    
    // Create status_history table
    $db->query("
        CREATE TABLE IF NOT EXISTS status_history (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            application_id INT UNSIGNED NOT NULL,
            old_status VARCHAR(20),
            new_status VARCHAR(20) NOT NULL,
            changed_by INT UNSIGNED,
            notes TEXT,
            created_at DATETIME NOT NULL,
            FOREIGN KEY (application_id) REFERENCES applications(id) ON DELETE CASCADE,
            FOREIGN KEY (changed_by) REFERENCES admin_users(id) ON DELETE SET NULL,
            INDEX idx_app_id (application_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo "  - Status history table created/verified\n";
    
    // Create settings table
    $db->query("
        CREATE TABLE IF NOT EXISTS settings (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            setting_key VARCHAR(100) NOT NULL UNIQUE,
            setting_value TEXT,
            description VARCHAR(255),
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL,
            INDEX idx_key (setting_key)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo "  - Settings table created/verified\n";
    
    // Create activity_log table
    $db->query("
        CREATE TABLE IF NOT EXISTS activity_log (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            user_id INT UNSIGNED DEFAULT NULL,
            user_type ENUM('user', 'admin') DEFAULT 'user',
            action VARCHAR(100) NOT NULL,
            description TEXT,
            ip_address VARCHAR(45),
            user_agent TEXT,
            created_at DATETIME NOT NULL,
            INDEX idx_user (user_id),
            INDEX idx_action (action),
            INDEX idx_created (created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo "  - Activity log table created/verified\n";
    
    // Create email_verification_codes table
    $db->query("
        CREATE TABLE IF NOT EXISTS email_verification_codes (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            user_id INT UNSIGNED NOT NULL,
            code VARCHAR(6) NOT NULL,
            expires_at DATETIME NOT NULL,
            verified_at DATETIME DEFAULT NULL,
            created_at DATETIME NOT NULL,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            INDEX idx_user_id (user_id),
            INDEX idx_code (code),
            INDEX idx_expires (expires_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo "  - Email verification codes table created/verified\n";
    
    // Create email_templates table
    $db->query("
        CREATE TABLE IF NOT EXISTS email_templates (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(255) NOT NULL,
            subject VARCHAR(500) NOT NULL,
            body TEXT NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
    echo "  - Email templates table created/verified\n";
    
    // Create email_logs table
    $db->query("
        CREATE TABLE IF NOT EXISTS email_logs (
            id INT AUTO_INCREMENT PRIMARY KEY,
            template_id INT DEFAULT NULL,
            recipient_email VARCHAR(255) NOT NULL,
            recipient_name VARCHAR(255) DEFAULT NULL,
            subject VARCHAR(500) NOT NULL,
            body TEXT NOT NULL,
            status ENUM('pending', 'sent', 'failed') DEFAULT 'pending',
            error_message TEXT DEFAULT NULL,
            sent_at DATETIME DEFAULT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (template_id) REFERENCES email_templates(id) ON DELETE SET NULL,
            INDEX idx_template_id (template_id),
            INDEX idx_recipient (recipient_email),
            INDEX idx_status (status)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
    echo "  - Email logs table created/verified\n";
    
    // Create whatsapp_templates table
    $db->query("
        CREATE TABLE IF NOT EXISTS whatsapp_templates (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(255) NOT NULL,
            category VARCHAR(100) NOT NULL DEFAULT 'general',
            message TEXT NOT NULL,
            variables VARCHAR(500) DEFAULT NULL COMMENT 'Comma-separated list of variable names',
            is_active TINYINT(1) DEFAULT 1,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_category (category),
            INDEX idx_active (is_active)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo "  - WhatsApp templates table created/verified\n";
    
    echo "  Schema setup complete!\n\n";
    
    echo "[2/15] Inserting default settings...\n";
    
    // Complete list of default settings
    $defaultSettings = [
        ['site_name', 'X Business Grant', 'Website name'],
        ['site_email', 'support@xbusinessgrant.ng', 'Support email address'],
        ['site_url', 'http://localhost/X Business Grants', 'Website URL'],
        ['grant_min_amount', '100000', 'Minimum grant amount in Naira'],
        ['grant_max_amount', '5000000', 'Maximum grant amount in Naira'],
        ['paystack_secret_key', '', 'Paystack Secret Key for API calls'],
        ['paystack_public_key', '', 'Paystack Public Key for frontend'],
        ['smtp_host', '', 'SMTP server hostname'],
        ['smtp_port', '587', 'SMTP server port (587 for TLS, 465 for SSL)'],
        ['smtp_username', '', 'SMTP username/email'],
        ['smtp_password', '', 'SMTP password'],
        ['smtp_encryption', 'tls', 'SMTP encryption type (tls, ssl, or none)'],
        ['smtp_from_email', 'noreply@xbusinessgrant.ng', 'From email address for outgoing emails'],
        ['smtp_from_name', 'X Business Grant', 'From name for outgoing emails'],
        ['email_verification_enabled', '1', 'Enable email verification for new users (1=enabled, 0=disabled)'],
        ['application_open', '1', 'Allow new applications (1=open, 0=closed)'],
        ['maintenance_mode', '0', 'Enable maintenance mode (1=enabled, 0=disabled)']
    ];
    
    $settingsCreated = 0;
    foreach ($defaultSettings as $setting) {
        $existing = $db->fetchOne("SELECT id FROM settings WHERE setting_key = ?", [$setting[0]]);
        if (!$existing) {
            $db->query(
                "INSERT INTO settings (setting_key, setting_value, description, created_at, updated_at) VALUES (?, ?, ?, NOW(), NOW())",
                $setting
            );
            $settingsCreated++;
            echo "  - Created setting: {$setting[0]}\n";
        } else {
            echo "  - Setting exists: {$setting[0]}\n";
        }
    }
    echo "  Done: {$settingsCreated} new settings created\n\n";
    
    echo "[3/15] Creating admin user...\n";
    $adminPassword = password_hash('admin123', PASSWORD_DEFAULT);
    $existingAdmin = $db->fetchOne("SELECT id FROM admin_users WHERE username = 'admin'");
    if (!$existingAdmin) {
        $db->query(
            "INSERT INTO admin_users (username, email, password_hash, full_name, role, created_at, updated_at) VALUES (?, ?, ?, ?, ?, NOW(), NOW())",
            ['admin', 'admin@xbusinessgrant.ng', $adminPassword, 'System Administrator', 'super_admin']
        );
        echo "  - Admin user created: admin / admin123\n";
    } else {
        $db->query(
            "UPDATE admin_users SET password_hash = ? WHERE username = 'admin'",
            [$adminPassword]
        );
        echo "  - Admin user updated: admin / admin123\n";
    }
    
    // Create reviewer admin
    $existingReviewer = $db->fetchOne("SELECT id FROM admin_users WHERE username = 'reviewer'");
    if (!$existingReviewer) {
        $reviewerPassword = password_hash('reviewer123', PASSWORD_DEFAULT);
        $db->query(
            "INSERT INTO admin_users (username, email, password_hash, full_name, role, created_at, updated_at) VALUES (?, ?, ?, ?, ?, NOW(), NOW())",
            ['reviewer', 'reviewer@xbusinessgrant.ng', $reviewerPassword, 'Application Reviewer', 'reviewer']
        );
        echo "  - Reviewer user created: reviewer / reviewer123\n";
    }
    echo "\n";
    
    $userModel = new User();
    
    echo "[4/15] Creating sample users...\n";
    
    $users = [
        [
            'full_name' => 'Emeka Nwankwo',
            'email' => 'emeka.nwankwo@example.com',
            'phone' => '08031234567',
            'business_registration_number' => 'RC123456',
            'password' => 'password123'
        ],
        [
            'full_name' => 'Funke Adeleke',
            'email' => 'funke.adeleke@example.com',
            'phone' => '08021234567',
            'business_registration_number' => 'RC234567',
            'password' => 'password123'
        ],
        [
            'full_name' => 'Ibrahim Musa',
            'email' => 'ibrahim.musa@example.com',
            'phone' => '08051234567',
            'business_registration_number' => 'RC345678',
            'password' => 'password123'
        ],
        [
            'full_name' => 'Chioma Okafor',
            'email' => 'chioma.okafor@example.com',
            'phone' => '08061234567',
            'business_registration_number' => 'RC456789',
            'password' => 'password123'
        ],
        [
            'full_name' => 'Olumide Adeyemi',
            'email' => 'olumide.adeyemi@example.com',
            'phone' => '08071234567',
            'business_registration_number' => 'BN567890',
            'password' => 'password123'
        ]
    ];
    
    $createdUsers = [];
    foreach ($users as $userData) {
        if (!$userModel->emailExists($userData['email'])) {
            $userId = $userModel->create($userData);
            $createdUsers[] = $userData['email'];
            echo "  - Created user: {$userData['full_name']} ({$userData['email']})\n";
        } else {
            echo "  - User already exists: {$userData['email']}\n";
        }
    }
    $usersCreated = count($createdUsers);
    echo "  Done: {$usersCreated} users created\n\n";
    
    echo "[5/15] Creating sample applications...\n";
    
    $businesses = [
        [
            'business_name' => 'TechStart Nigeria Ltd',
            'cac_number' => 'RC123456',
            'business_registration_number' => 'RC123456',
            'business_type' => 'Private Limited Company',
            'business_sector' => 'Technology & IT',
            'business_address' => '15 Admiralty Way, Lekki Phase 1',
            'city' => 'Lagos',
            'state' => 'Lagos',
            'lga' => 'Eti-Osa',
            'phone' => '08031234567',
            'email' => 'emeka.nwankwo@example.com',
            'website' => 'https://techstart.ng',
            'years_in_business' => 3,
            'employee_count' => 12,
            'grant_amount' => 2500000,
            'purpose' => 'Purchase of equipment, hire additional developers, and expand our cloud infrastructure to serve more clients across Nigeria.',
            'business_description' => 'TechStart Nigeria provides web development, mobile app development, and cloud solutions to SMEs and startups across Nigeria. We have served over 50 clients and have a team of skilled developers.',
            'status' => 'approved',
            'owner_name' => 'Emeka Nwankwo',
            'bank_name' => 'First Bank of Nigeria',
            'bank_account_number' => '3084567890'
        ],
        [
            'business_name' => 'Green Leaf Farms',
            'cac_number' => 'RC234567',
            'business_registration_number' => 'RC234567',
            'business_type' => 'Private Limited Company',
            'business_sector' => 'Agriculture & Agribusiness',
            'business_address' => ' KM 15, Ibadan-Ilorin Road',
            'city' => 'Ibadan',
            'state' => 'Oyo',
            'lga' => 'Akinyele',
            'phone' => '08021234567',
            'email' => 'funke.adeleke@example.com',
            'website' => '',
            'years_in_business' => 5,
            'employee_count' => 25,
            'grant_amount' => 3500000,
            'purpose' => 'Expand poultry farm from 2000 to 5000 birds, construct new housing units, and install automated feeding systems.',
            'business_description' => 'Green Leaf Farms is a diversified agricultural enterprise specializing in poultry farming, egg production, and crop cultivation. We supply major markets in Ibadan and Lagos.',
            'status' => 'under_review',
            'owner_name' => 'Funke Adeleke',
            'bank_name' => 'Zenith Bank',
            'bank_account_number' => '2087654321'
        ],
        [
            'business_name' => 'Northern Textiles Co.',
            'cac_number' => 'RC345678',
            'business_registration_number' => 'RC345678',
            'business_type' => 'Partnership',
            'business_sector' => 'Fashion & Textiles',
            'business_address' => '45 Bompai Road, Kano Central',
            'city' => 'Kano',
            'state' => 'Kano',
            'lga' => 'Kano Municipal',
            'phone' => '08051234567',
            'email' => 'ibrahim.musa@example.com',
            'website' => '',
            'years_in_business' => 8,
            'employee_count' => 45,
            'grant_amount' => 4500000,
            'purpose' => 'Purchase new weaving looms, expand production capacity, and train additional staff on modern textile techniques.',
            'business_description' => 'Northern Textiles Co. is a traditional textile manufacturing company producing high-quality African prints and fabrics. We employ traditional weavers and blend heritage with modern designs.',
            'status' => 'pending',
            'owner_name' => 'Ibrahim Musa',
            'bank_name' => 'Guaranty Trust Bank',
            'bank_account_number' => '0145678901'
        ],
        [
            'business_name' => 'ChiMed Healthcare Services',
            'cac_number' => 'RC456789',
            'business_registration_number' => 'RC456789',
            'business_type' => 'Private Limited Company',
            'business_sector' => 'Healthcare',
            'business_address' => '78 Owerri Road, Independence Layout',
            'city' => 'Owerri',
            'state' => 'Imo',
            'lga' => 'Owerri Municipal',
            'phone' => '08061234567',
            'email' => 'chioma.okafor@example.com',
            'website' => 'https://chimedhealthcare.com',
            'years_in_business' => 4,
            'employee_count' => 18,
            'grant_amount' => 3000000,
            'purpose' => 'Open a second clinic location, purchase medical equipment, and hire additional medical staff.',
            'business_description' => 'ChiMed Healthcare Services provides primary healthcare, laboratory services, and maternal health services in Imo State. We are committed to affordable healthcare for all.',
            'status' => 'approved',
            'owner_name' => 'Chioma Okafor',
            'bank_name' => 'Access Bank',
            'bank_account_number' => '0789012345'
        ],
        [
            'business_name' => 'Lagos Creative Studios',
            'cac_number' => 'BN567890',
            'business_registration_number' => 'BN567890',
            'business_type' => 'Sole Proprietorship',
            'business_sector' => 'Creative Arts & Entertainment',
            'business_address' => '23 Ojuelegba Road, Surulere',
            'city' => 'Lagos',
            'state' => 'Lagos',
            'lga' => 'Surulere',
            'phone' => '08071234567',
            'email' => 'olumide.adeyemi@example.com',
            'website' => 'https://lagoscreativestudios.com',
            'years_in_business' => 2,
            'employee_count' => 6,
            'grant_amount' => 1500000,
            'purpose' => 'Purchase professional video equipment, set up a small recording studio, and market our services to corporate clients.',
            'business_description' => 'Lagos Creative Studios provides video production, photography, and content creation services. We specialize in corporate videos, events coverage, and social media content.',
            'status' => 'rejected',
            'owner_name' => 'Olumide Adeyemi',
            'bank_name' => 'United Bank for Africa',
            'bank_account_number' => '2012345678'
        ],
        [
            'business_name' => 'Abuja Logistics Ltd',
            'cac_number' => 'RC678901',
            'business_registration_number' => 'RC678901',
            'business_type' => 'Private Limited Company',
            'business_sector' => 'Transportation & Logistics',
            'business_address' => 'Plot 12, Wuse Zone 5',
            'city' => 'Abuja',
            'state' => 'FCT Abuja',
            'lga' => 'Wuse',
            'phone' => '08091234567',
            'email' => 'info@abujalogistics.ng',
            'website' => '',
            'years_in_business' => 6,
            'employee_count' => 35,
            'grant_amount' => 5000000,
            'purpose' => 'Purchase 3 delivery vans, expand our fleet, and develop a mobile app for tracking deliveries.',
            'business_description' => 'Abuja Logistics Ltd provides last-mile delivery and courier services across the FCT and surrounding states. We serve e-commerce businesses, corporate clients, and individuals.',
            'status' => 'pending',
            'owner_name' => 'Ahmad Bello',
            'bank_name' => 'Ecobank Nigeria',
            'bank_account_number' => '3187654321'
        ],
        [
            'business_name' => 'Port Harcourt Food Processing',
            'cac_number' => 'RC789012',
            'business_registration_number' => 'RC789012',
            'business_type' => 'Private Limited Company',
            'business_sector' => 'Food & Beverage',
            'business_address' => '17 Eagle Island Road',
            'city' => 'Port Harcourt',
            'state' => 'Rivers',
            'lga' => 'Port Harcourt',
            'phone' => '08081234567',
            'email' => 'contact@phfoodprocessing.com',
            'website' => '',
            'years_in_business' => 4,
            'employee_count' => 22,
            'grant_amount' => 2800000,
            'purpose' => 'Upgrade processing equipment, obtain NAFDAC certification, and expand distribution network.',
            'business_description' => 'Port Harcourt Food Processing processes and packages indigenous Nigerian food products including palm oil, groundnut oil, and locally sourced snacks for retail distribution.',
            'status' => 'under_review',
            'owner_name' => 'Chidi Okonkwo',
            'bank_name' => 'Fidelity Bank',
            'bank_account_number' => '6234567890'
        ],
        [
            'business_name' => 'Ibadan Fashion House',
            'cac_number' => 'RC890123',
            'business_registration_number' => 'RC890123',
            'business_type' => 'Partnership',
            'business_sector' => 'Fashion & Textiles',
            'business_address' => '68 Ring Road',
            'city' => 'Ibadan',
            'state' => 'Oyo',
            'lga' => 'Ibadan North',
            'phone' => '08011234567',
            'email' => 'info@ibadanfashionhouse.com',
            'website' => '',
            'years_in_business' => 7,
            'employee_count' => 30,
            'grant_amount' => 3200000,
            'purpose' => 'Open a second boutique, train additional tailors, and invest in modern sewing equipment.',
            'business_description' => 'Ibadan Fashion House is a premier fashion house creating contemporary African wear. We specialize in wedding dresses, corporate attire, and traditional Yoruba garments.',
            'status' => 'approved',
            'owner_name' => 'Adaeze Nnamdi',
            'bank_name' => 'Sterling Bank',
            'bank_account_number' => '0076543219'
        ]
    ];
    
    $app = new Application();
    $createdApplications = 0;
    
    foreach ($businesses as $business) {
        if (!$app->cacExists($business['cac_number'])) {
            $data = [
                'business_name' => $business['business_name'],
                'cac_number' => $business['cac_number'],
                'business_type' => $business['business_type'],
                'business_sector' => $business['business_sector'],
                'business_address' => $business['business_address'],
                'city' => $business['city'],
                'state' => $business['state'],
                'lga' => $business['lga'],
                'phone' => $business['phone'],
                'email' => $business['email'],
                'website' => $business['website'] ?? '',
                'years_in_business' => $business['years_in_business'],
                'employee_count' => $business['employee_count'],
                'grant_amount' => $business['grant_amount'],
                'purpose' => $business['purpose'],
                'business_description' => $business['business_description']
            ];
            
            $ref = $app->create($data);
            
            if ($business['status'] !== 'pending') {
                $appData = $app->getByReference($ref);
                $app->updateStatus($appData['id'], $business['status'], 'Sample data - status set during seeding');
            }
            
            $createdApplications++;
            echo "  - Created: {$business['business_name']} (Ref: {$ref})\n";
        } else {
            echo "  - Already exists: {$business['business_name']}\n";
        }
    }
    echo "  Done: {$createdApplications} applications created\n\n";
    
    echo "[6/15] Seeding email templates...\n";
    
    $emailTemplates = [
        [
            'name' => 'Welcome Email',
            'subject' => 'Welcome to X Business Grant!',
            'body' => '<div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;"><div style="background: linear-gradient(135deg, #1e40af 0%, #1e3a8a 100%); padding: 30px; text-align: center;"><h1 style="color: white; margin: 0; font-size: 24px;">X Business Grant</h1></div><div style="padding: 40px 30px; background: #ffffff;"><h2 style="color: #1e40af; margin-top: 0;">Welcome, {{name}}!</h2><p>Thank you for registering with X Business Grant. We are excited to have you on board!</p><p>With X Business Grant, you can:</p><ul><li>Apply for business grants up to ₦5,000,000</li><li>Track your application status online</li><li>Get expert guidance on your business growth</li></ul><p>Get started by completing your profile and submitting your first grant application.</p><div style="text-align: center; margin: 30px 0;"><a href="{{dashboard_url}}" style="display: inline-block; background: #1e40af; color: white; padding: 12px 30px; text-decoration: none; border-radius: 8px; font-weight: bold;">Go to Dashboard</a></div><p style="margin-top: 30px;">Best regards,<br>The X Business Grant Team</p></div><div style="padding: 20px 30px; background: #f9fafb; text-align: center; color: #6b7280; font-size: 12px;"><p>© {{year}} X Business Grant. All rights reserved.</p></div></div>'
        ],
        [
            'name' => 'Application Received',
            'subject' => 'We Received Your Grant Application - {{reference}}',
            'body' => '<div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;"><div style="background: linear-gradient(135deg, #1e40af 0%, #1e3a8a 100%); padding: 30px; text-align: center;"><h1 style="color: white; margin: 0; font-size: 24px;">X Business Grant</h1></div><div style="padding: 40px 30px; background: #ffffff;"><h2 style="color: #1e40af; margin-top: 0;">Application Received!</h2><p>Dear {{name}},</p><p>We have received your grant application. Your application reference number is: <strong>{{reference}}</strong></p><p>Our team will review your application and get back to you within 5-7 business days.</p><div style="text-align: center; margin: 30px 0;"><a href="{{dashboard_url}}" style="display: inline-block; background: #1e40af; color: white; padding: 12px 30px; text-decoration: none; border-radius: 8px; font-weight: bold;">Track Application</a></div><p style="margin-top: 30px;">Best regards,<br>The X Business Grant Team</p></div><div style="padding: 20px 30px; background: #f9fafb; text-align: center; color: #6b7280; font-size: 12px;"><p>© {{year}} X Business Grant. All rights reserved.</p></div></div>'
        ],
        [
            'name' => 'Application Approved',
            'subject' => 'Congratulations! Your Grant is Approved!',
            'body' => '<div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;"><div style="background: linear-gradient(135deg, #059669 0%, #047857 100%); padding: 30px; text-align: center;"><h1 style="color: white; margin: 0; font-size: 24px;">X Business Grant - Approved!</h1></div><div style="padding: 40px 30px; background: #ffffff;"><h2 style="color: #059669; margin-top: 0;">Congratulations, {{name}}!</h2><p>We are thrilled to inform you that your grant application (Reference: <strong>{{reference}}</strong>) has been approved!</p><div style="background: #ecfdf5; padding: 20px; border-radius: 8px; margin: 20px 0;"><p style="margin: 0;"><strong>Approved Amount:</strong> {{amount}}</p></div><p>Our team will contact you shortly with details on how to receive your grant funds.</p><div style="text-align: center; margin: 30px 0;"><a href="{{dashboard_url}}" style="display: inline-block; background: #059669; color: white; padding: 12px 30px; text-decoration: none; border-radius: 8px; font-weight: bold;">View Details</a></div><p style="margin-top: 30px;">Best regards,<br>The X Business Grant Team</p></div><div style="padding: 20px 30px; background: #f9fafb; text-align: center; color: #6b7280; font-size: 12px;"><p>© {{year}} X Business Grant. All rights reserved.</p></div></div>'
        ],
        [
            'name' => 'Application Rejected',
            'subject' => 'Update on Your Grant Application',
            'body' => '<div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;"><div style="background: linear-gradient(135deg, #dc2626 0%, #b91c1c 100%); padding: 30px; text-align: center;"><h1 style="color: white; margin: 0; font-size: 24px;">X Business Grant</h1></div><div style="padding: 40px 30px; background: #ffffff;"><h2 style="color: #dc2626; margin-top: 0;">Application Update</h2><p>Dear {{name}},</p><p>Thank you for your interest in X Business Grant. After careful review of your application (Reference: <strong>{{reference}}</strong>), we regret to inform you that we are unable to approve your grant request at this time.</p><p>This decision does not reflect on you personally. We encourage you to:</p><ul><li>Review your business plan and strengthen your proposal</li><li>Build your credit history</li><li>Gain more business experience</li></ul><p>You are welcome to reapply in the future. We wish you all the best in your business journey.</p><div style="text-align: center; margin: 30px 0;"><a href="{{apply_url}}" style="display: inline-block; background: #dc2626; color: white; padding: 12px 30px; text-decoration: none; border-radius: 8px; font-weight: bold;">Reapply Now</a></div><p style="margin-top: 30px;">Best regards,<br>The X Business Grant Team</p></div><div style="padding: 20px 30px; background: #f9fafb; text-align: center; color: #6b7280; font-size: 12px;"><p>© {{year}} X Business Grant. All rights reserved.</p></div></div>'
        ],
        [
            'name' => 'General Announcement',
            'subject' => 'Important Update from X Business Grant',
            'body' => '<div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;"><div style="background: linear-gradient(135deg, #1e40af 0%, #1e3a8a 100%); padding: 30px; text-align: center;"><h1 style="color: white; margin: 0; font-size: 24px;">X Business Grant</h1></div><div style="padding: 40px 30px; background: #ffffff;"><h2 style="color: #1e40af; margin-top: 0;">{{subject}}</h2><p>Dear {{name}},</p>{{message}}<div style="text-align: center; margin: 30px 0;"><a href="{{dashboard_url}}" style="display: inline-block; background: #1e40af; color: white; padding: 12px 30px; text-decoration: none; border-radius: 8px; font-weight: bold;">Visit Dashboard</a></div><p style="margin-top: 30px;">Best regards,<br>The X Business Grant Team</p></div><div style="padding: 20px 30px; background: #f9fafb; text-align: center; color: #6b7280; font-size: 12px;"><p>© {{year}} X Business Grant. All rights reserved.</p></div></div>'
        ],
        [
            'name' => 'Application Under Review',
            'subject' => 'Your Application is Under Review - {{reference}}',
            'body' => '<div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;"><div style="background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%); padding: 30px; text-align: center;"><h1 style="color: white; margin: 0; font-size: 24px;">X Business Grant - Under Review</h1></div><div style="padding: 40px 30px; background: #ffffff;"><h2 style="color: #f59e0b; margin-top: 0;">Application Under Review</h2><p>Dear {{name}},</p><p>We wanted to let you know that your grant application (Reference: <strong>{{reference}}</strong>) is now under review by our team.</p><p>This is great news! Our review team is carefully evaluating all applications to ensure fair and thorough assessment.</p><p>Expected timeline: 5-7 business days</p><p>You will receive an email notification once the review is complete.</p><div style="text-align: center; margin: 30px 0;"><a href="{{dashboard_url}}" style="display: inline-block; background: #f59e0b; color: white; padding: 12px 30px; text-decoration: none; border-radius: 8px; font-weight: bold;">View Dashboard</a></div><p style="margin-top: 30px;">Best regards,<br>The X Business Grant Team</p></div><div style="padding: 20px 30px; background: #f9fafb; text-align: center; color: #6b7280; font-size: 12px;"><p>© {{year}} X Business Grant. All rights reserved.</p></div></div>'
        ],
        [
            'name' => 'Payment Processing',
            'subject' => 'Your Grant Payment is Being Processed',
            'body' => '<div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;"><div style="background: linear-gradient(135deg, #059669 0%, #047857 100%); padding: 30px; text-align: center;"><h1 style="color: white; margin: 0; font-size: 24px;">X Business Grant - Payment</h1></div><div style="padding: 40px 30px; background: #ffffff;"><h2 style="color: #059669; margin-top: 0;">Payment Processing</h2><p>Dear {{name}},</p><p>Great news! Your grant payment for application <strong>{{reference}}</strong> is now being processed.</p><div style="background: #ecfdf5; padding: 20px; border-radius: 8px; margin: 20px 0;"><p style="margin: 0;"><strong>Amount:</strong> {{amount}}</p><p style="margin: 10px 0 0 0;"><strong>Status:</strong> Payment Processing</p></div><p>Please allow 3-5 business days for the funds to reflect in your account.</p><p>If you have any questions, please contact our support team.</p><div style="text-align: center; margin: 30px 0;"><a href="{{dashboard_url}}" style="display: inline-block; background: #059669; color: white; padding: 12px 30px; text-decoration: none; border-radius: 8px; font-weight: bold;">View Dashboard</a></div><p style="margin-top: 30px;">Best regards,<br>The X Business Grant Team</p></div><div style="padding: 20px 30px; background: #f9fafb; text-align: center; color: #6b7280; font-size: 12px;"><p>© {{year}} X Business Grant. All rights reserved.</p></div></div>'
        ],
        [
            'name' => 'Payment Completed',
            'subject' => 'Grant Payment Completed Successfully!',
            'body' => '<div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;"><div style="background: linear-gradient(135deg, #059669 0%, #047857 100%); padding: 30px; text-align: center;"><h1 style="color: white; margin: 0; font-size: 24px;">X Business Grant - Payment Complete</h1></div><div style="padding: 40px 30px; background: #ffffff;"><h2 style="color: #059669; margin-top: 0;">Payment Complete!</h2><p>Dear {{name}},</p><p>We are pleased to confirm that your grant payment has been successfully transferred!</p><div style="background: #ecfdf5; padding: 20px; border-radius: 8px; margin: 20px 0;"><p style="margin: 0;"><strong>Reference:</strong> {{reference}}</p><p style="margin: 10px 0 0 0;"><strong>Amount Received:</strong> {{amount}}</p><p style="margin: 10px 0 0 0;"><strong>Status:</strong> Completed</p></div><p>Congratulations on receiving your grant! We encourage you to use these funds wisely to grow your business.</p><div style="text-align: center; margin: 30px 0;"><a href="{{dashboard_url}}" style="display: inline-block; background: #059669; color: white; padding: 12px 30px; text-decoration: none; border-radius: 8px; font-weight: bold;">View Dashboard</a></div><p style="margin-top: 30px;">Best regards,<br>The X Business Grant Team</p></div><div style="padding: 20px 30px; background: #f9fafb; text-align: center; color: #6b7280; font-size: 12px;"><p>© {{year}} X Business Grant. All rights reserved.</p></div></div>'
        ],
        [
            'name' => 'Document Request',
            'subject' => 'Additional Documents Required - {{reference}}',
            'body' => '<div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;"><div style="background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%); padding: 30px; text-align: center;"><h1 style="color: white; margin: 0; font-size: 24px;">X Business Grant</h1></div><div style="padding: 40px 30px; background: #ffffff;"><h2 style="color: #f59e0b; margin-top: 0;">Additional Documents Required</h2><p>Dear {{name}},</p><p>We are reviewing your grant application (Reference: <strong>{{reference}}</strong>) and need some additional documents to complete the process.</p><p>Please upload the following documents:</p><div style="background: #f3f4f6; padding: 20px; border-radius: 8px; margin: 20px 0;"><ul style="margin: 0; padding-left: 20px;">{{documents}}</ul></div><p>Once we receive these documents, we will continue with the review process.</p><div style="text-align: center; margin: 30px 0;"><a href="{{upload_url}}" style="display: inline-block; background: #f59e0b; color: white; padding: 12px 30px; text-decoration: none; border-radius: 8px; font-weight: bold;">Upload Documents</a></div><p style="margin-top: 30px;">Best regards,<br>The X Business Grant Team</p></div><div style="padding: 20px 30px; background: #f9fafb; text-align: center; color: #6b7280; font-size: 12px;"><p>© {{year}} X Business Grant. All rights reserved.</p></div></div>'
        ],
        [
            'name' => 'Account Verification',
            'subject' => 'Please Verify Your Account',
            'body' => '<div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;"><div style="background: linear-gradient(135deg, #1e40af 0%, #1e3a8a 100%); padding: 30px; text-align: center;"><h1 style="color: white; margin: 0; font-size: 24px;">X Business Grant</h1></div><div style="padding: 40px 30px; background: #ffffff;"><h2 style="color: #1e40af; margin-top: 0;">Account Verification Required</h2><p>Dear {{name}},</p><p>Thank you for registering with X Business Grant!</p><p>To ensure the security of your account and comply with regulations, we need to verify your identity.</p><div style="background: #f3f4f6; padding: 20px; border-radius: 8px; margin: 20px 0; text-align: center;"><p style="font-size: 18px; margin: 0;">Your verification code:</p><p style="font-size: 28px; font-weight: bold; color: #1e40af; margin: 10px 0;">{{code}}</p><p style="font-size: 12px; color: #6b7280; margin: 0;">This code expires in 30 minutes</p></div><div style="text-align: center; margin: 30px 0;"><a href="{{verify_url}}" style="display: inline-block; background: #1e40af; color: white; padding: 12px 30px; text-decoration: none; border-radius: 8px; font-weight: bold;">Verify Account</a></div><p style="margin-top: 30px;">Best regards,<br>The X Business Grant Team</p></div><div style="padding: 20px 30px; background: #f9fafb; text-align: center; color: #6b7280; font-size: 12px;"><p>© {{year}} X Business Grant. All rights reserved.</p></div></div>'
        ],
        [
            'name' => 'Password Reset',
            'subject' => 'Reset Your Password - X Business Grant',
            'body' => '<div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;"><div style="background: linear-gradient(135deg, #1e40af 0%, #1e3a8a 100%); padding: 30px; text-align: center;"><h1 style="color: white; margin: 0; font-size: 24px;">X Business Grant</h1></div><div style="padding: 40px 30px; background: #ffffff;"><h2 style="color: #1e40af; margin-top: 0;">Password Reset Request</h2><p>Dear {{name}},</p><p>We received a request to reset your password. Click the button below to set a new password:</p><div style="text-align: center; margin: 30px 0;"><a href="{{reset_link}}" style="display: inline-block; background: #1e40af; color: white; padding: 12px 30px; text-decoration: none; border-radius: 8px; font-weight: bold;">Reset Password</a></div><p>Or copy this link: {{reset_link}}</p><p style="color: #dc2626;"><strong>Note:</strong> This link expires in 1 hour. If you did not request a password reset, please ignore this email.</p><p style="margin-top: 30px;">Best regards,<br>The X Business Grant Team</p></div><div style="padding: 20px 30px; background: #f9fafb; text-align: center; color: #6b7280; font-size: 12px;"><p>© {{year}} X Business Grant. All rights reserved.</p></div></div>'
        ],
        [
            'name' => 'Newsletter',
            'subject' => 'X Business Grant Newsletter - {{subject}}',
            'body' => '<div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;"><div style="background: linear-gradient(135deg, #1e40af 0%, #1e3a8a 100%); padding: 30px; text-align: center;"><h1 style="color: white; margin: 0; font-size: 24px;">X Business Grant</h1></div><div style="padding: 40px 30px; background: #ffffff;"><h2 style="color: #1e40af; margin-top: 0;">{{subject}}</h2><p>Dear {{name}},</p>{{message}}<div style="margin-top: 30px; padding-top: 20px; border-top: 1px solid #e5e7eb;"><h3 style="color: #1e40af;">Quick Links</h3><p><a href="{{dashboard_url}}" style="color: #1e40af;">Dashboard</a> | <a href="{{apply_url}}" style="color: #1e40af;">Apply for a Grant</a> | <a href="{{contact_url}}" style="color: #1e40af;">Contact Support</a></p></div><div style="text-align: center; margin: 30px 0;"><a href="{{dashboard_url}}" style="display: inline-block; background: #1e40af; color: white; padding: 12px 30px; text-decoration: none; border-radius: 8px; font-weight: bold;">Visit Dashboard</a></div><p style="margin-top: 30px;">Best regards,<br>The X Business Grant Team</p></div><div style="padding: 20px 30px; background: #f9fafb; text-align: center; color: #6b7280; font-size: 12px;"><p>© {{year}} X Business Grant. All rights reserved.</p><p>You received this email because you registered on our platform.</p><p><a href="{{unsubscribe_url}}" style="color: #6b7280;">Unsubscribe</a> | <a href="{{view_in_browser_url}}" style="color: #6b7280;">View in browser</a></p></div></div>'
        ],
        [
            'name' => 'Survey Request',
            'subject' => 'We Value Your Feedback - X Business Grant',
            'body' => '<div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;"><div style="background: linear-gradient(135deg, #1e40af 0%, #1e3a8a 100%); padding: 30px; text-align: center;"><h1 style="color: white; margin: 0; font-size: 24px;">X Business Grant</h1></div><div style="padding: 40px 30px; background: #ffffff;"><h2 style="color: #1e40af; margin-top: 0;">We Need Your Feedback!</h2><p>Dear {{name}},</p><p>Thank you for being part of the X Business Grant community. Your opinion matters to us!</p><p>We would love to hear about your experience with our platform. Please take a few minutes to complete our survey:</p><div style="text-align: center; margin: 30px 0;"><a href="{{survey_link}}" style="display: inline-block; background: #1e40af; color: white; padding: 12px 30px; text-decoration: none; border-radius: 8px; font-weight: bold;">Take Survey</a></div><p>Your feedback will help us improve our services and better serve entrepreneurs like you.</p><p style="margin-top: 30px;">Best regards,<br>The X Business Grant Team</p></div><div style="padding: 20px 30px; background: #f9fafb; text-align: center; color: #6b7280; font-size: 12px;"><p>© {{year}} X Business Grant. All rights reserved.</p></div></div>'
        ],
        [
            'name' => 'Referral Program',
            'subject' => 'Invite Friends to X Business Grant - Earn Rewards!',
            'body' => '<div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;"><div style="background: linear-gradient(135deg, #059669 0%, #047857 100%); padding: 30px; text-align: center;"><h1 style="color: white; margin: 0; font-size: 24px;">X Business Grant - Referral Program</h1></div><div style="padding: 40px 30px; background: #ffffff;"><h2 style="color: #059669; margin-top: 0;">Invite Friends & Earn Rewards!</h2><p>Dear {{name}},</p><p>Share the opportunity with friends and colleagues. When they successfully receive a grant, you both get rewarded!</p><div style="background: #ecfdf5; padding: 20px; border-radius: 8px; margin: 20px 0; text-align: center;"><p style="margin: 0; font-size: 18px;"><strong>Your Referral Code:</strong></p><p style="margin: 10px 0 0 0; font-size: 24px; font-weight: bold; color: #059669; letter-spacing: 4px;">{{referral_code}}</p></div><div style="text-align: center; margin: 30px 0;"><a href="{{referral_link}}" style="display: inline-block; background: #059669; color: white; padding: 12px 30px; text-decoration: none; border-radius: 8px; font-weight: bold;">Share Now</a></div><p>How it works:</p><ul><li>Share your unique referral link/code with friends</li><li>Friends apply and get approved for a grant</li><li>You both receive a reward bonus!</li></ul><p style="margin-top: 30px;">Best regards,<br>The X Business Grant Team</p></div><div style="padding: 20px 30px; background: #f9fafb; text-align: center; color: #6b7280; font-size: 12px;"><p>© {{year}} X Business Grant. All rights reserved.</p></div></div>'
        ],
        [
            'name' => 'Interview Scheduled',
            'subject' => 'Interview Scheduled - {{reference}}',
            'body' => '<div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;"><div style="background: linear-gradient(135deg, #7c3aed 0%, #6d28d9 100%); padding: 30px; text-align: center;"><h1 style="color: white; margin: 0; font-size: 24px;">X Business Grant</h1></div><div style="padding: 40px 30px; background: #ffffff;"><h2 style="color: #7c3aed; margin-top: 0;">Interview Scheduled</h2><p>Dear {{name}},</p><p>Congratulations! You have been selected for an interview regarding your grant application.</p><div style="background: #f5f3ff; padding: 20px; border-radius: 8px; margin: 20px 0;"><p style="margin: 0;"><strong>Application Reference:</strong> {{reference}}</p><p style="margin: 10px 0 0 0;"><strong>Date:</strong> {{date}}</p><p style="margin: 10px 0 0 0;"><strong>Time:</strong> {{time}}</p><p style="margin: 10px 0 0 0;"><strong>Location:</strong> {{location}}</p></div><p>Please confirm your attendance by clicking the button below.</p><div style="text-align: center; margin: 30px 0;"><a href="{{continue_url}}" style="display: inline-block; background: #7c3aed; color: white; padding: 12px 30px; text-decoration: none; border-radius: 8px; font-weight: bold;">Confirm Attendance</a></div><p style="margin-top: 30px;">Best regards,<br>The X Business Grant Team</p></div><div style="padding: 20px 30px; background: #f9fafb; text-align: center; color: #6b7280; font-size: 12px;"><p>© {{year}} X Business Grant. All rights reserved.</p></div></div>'
        ],
        [
            'name' => 'Interview Reminder',
            'subject' => 'Interview Reminder - {{reference}}',
            'body' => '<div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;"><div style="background: linear-gradient(135deg, #7c3aed 0%, #6d28d9 100%); padding: 30px; text-align: center;"><h1 style="color: white; margin: 0; font-size: 24px;">X Business Grant</h1></div><div style="padding: 40px 30px; background: #ffffff;"><h2 style="color: #7c3aed; margin-top: 0;">Interview Reminder</h2><p>Dear {{name}},</p><p>This is a friendly reminder about your upcoming interview for your grant application.</p><div style="background: #f5f3ff; padding: 20px; border-radius: 8px; margin: 20px 0;"><p style="margin: 0;"><strong>Application Reference:</strong> {{reference}}</p><p style="margin: 10px 0 0 0;"><strong>Date:</strong> {{date}}</p><p style="margin: 10px 0 0 0;"><strong>Time:</strong> {{time}}</p><p style="margin: 10px 0 0 0;"><strong>Location:</strong> {{location}}</p></div><p>Please arrive 15 minutes early and bring all required documents.</p><div style="text-align: center; margin: 30px 0;"><a href="{{dashboard_url}}" style="display: inline-block; background: #7c3aed; color: white; padding: 12px 30px; text-decoration: none; border-radius: 8px; font-weight: bold;">View Details</a></div><p style="margin-top: 30px;">Best regards,<br>The X Business Grant Team</p></div><div style="padding: 20px 30px; background: #f9fafb; text-align: center; color: #6b7280; font-size: 12px;"><p>© {{year}} X Business Grant. All rights reserved.</p></div></div>'
        ],
        [
            'name' => 'Deadline Reminder',
            'subject' => 'Deadline Reminder - {{reference}}',
            'body' => '<div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;"><div style="background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%); padding: 30px; text-align: center;"><h1 style="color: white; margin: 0; font-size: 24px;">X Business Grant</h1></div><div style="padding: 40px 30px; background: #ffffff;"><h2 style="color: #f59e0b; margin-top: 0;">Deadline Reminder</h2><p>Dear {{name}},</p><p>This is a reminder that your application (Reference: <strong>{{reference}}</strong>) has an upcoming deadline.</p><div style="background: #fffbeb; padding: 20px; border-radius: 8px; margin: 20px 0; border-left: 4px solid #f59e0b;"><p style="margin: 0; font-size: 18px;"><strong>Deadline:</strong> {{new_deadline}}</p></div><p>Please complete all required actions before the deadline to avoid delays in your application.</p><div style="text-align: center; margin: 30px 0;"><a href="{{continue_url}}" style="display: inline-block; background: #f59e0b; color: white; padding: 12px 30px; text-decoration: none; border-radius: 8px; font-weight: bold;">Take Action</a></div><p style="margin-top: 30px;">Best regards,<br>The X Business Grant Team</p></div><div style="padding: 20px 30px; background: #f9fafb; text-align: center; color: #6b7280; font-size: 12px;"><p>© {{year}} X Business Grant. All rights reserved.</p></div></div>'
        ],
        [
            'name' => 'Incomplete Application Reminder',
            'subject' => 'Complete Your Application - {{reference}}',
            'body' => '<div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;"><div style="background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%); padding: 30px; text-align: center;"><h1 style="color: white; margin: 0; font-size: 24px;">X Business Grant</h1></div><div style="padding: 40px 30px; background: #ffffff;"><h2 style="color: #f59e0b; margin-top: 0;">Complete Your Application</h2><p>Dear {{name}},</p><p>We noticed you started but haven\'t completed your grant application yet.</p><p>Your saved application (Reference: <strong>{{reference}}</strong>) is still pending submission.</p><div style="background: #fffbeb; padding: 20px; border-radius: 8px; margin: 20px 0;"><p style="margin: 0;"><strong>Required Actions:</strong></p><ul style="margin: 10px 0 0 0; padding-left: 20px;"><li>Complete your business details</li><li>Upload required documents</li><li>Submit your application</li></ul></div><p>Complete your application now to be considered for the grant.</p><div style="text-align: center; margin: 30px 0;"><a href="{{continue_url}}" style="display: inline-block; background: #f59e0b; color: white; padding: 12px 30px; text-decoration: none; border-radius: 8px; font-weight: bold;">Continue Application</a></div><p style="margin-top: 30px;">Best regards,<br>The X Business Grant Team</p></div><div style="padding: 20px 30px; background: #f9fafb; text-align: center; color: #6b7280; font-size: 12px;"><p>© {{year}} X Business Grant. All rights reserved.</p></div></div>'
        ],
        [
            'name' => 'Document Approved',
            'subject' => 'Documents Approved - {{reference}}',
            'body' => '<div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;"><div style="background: linear-gradient(135deg, #059669 0%, #047857 100%); padding: 30px; text-align: center;"><h1 style="color: white; margin: 0; font-size: 24px;">X Business Grant</h1></div><div style="padding: 40px 30px; background: #ffffff;"><h2 style="color: #059669; margin-top: 0;">Documents Approved</h2><p>Dear {{name}},</p><p>We are pleased to inform you that the documents submitted for your application (Reference: <strong>{{reference}}</strong>) have been approved!</p><p>Your application will now proceed to the next stage of review.</p><div style="text-align: center; margin: 30px 0;"><a href="{{dashboard_url}}" style="display: inline-block; background: #059669; color: white; padding: 12px 30px; text-decoration: none; border-radius: 8px; font-weight: bold;">View Status</a></div><p style="margin-top: 30px;">Best regards,<br>The X Business Grant Team</p></div><div style="padding: 20px 30px; background: #f9fafb; text-align: center; color: #6b7280; font-size: 12px;"><p>© {{year}} X Business Grant. All rights reserved.</p></div></div>'
        ],
        [
            'name' => 'Document Rejected',
            'subject' => 'Documents Need Revision - {{reference}}',
            'body' => '<div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;"><div style="background: linear-gradient(135deg, #dc2626 0%, #b91c1c 100%); padding: 30px; text-align: center;"><h1 style="color: white; margin: 0; font-size: 24px;">X Business Grant</h1></div><div style="padding: 40px 30px; background: #ffffff;"><h2 style="color: #dc2626; margin-top: 0;">Documents Need Revision</h2><p>Dear {{name}},</p><p>After reviewing the documents for your application (Reference: <strong>{{reference}}</strong>), we need you to resubmit the following documents:</p><div style="background: #fef2f2; padding: 20px; border-radius: 8px; margin: 20px 0; border-left: 4px solid #dc2626;"><ul style="margin: 0; padding-left: 20px;">{{documents}}</ul></div><p>Please review the requirements and upload the corrected documents using the secure portal below.</p><div style="text-align: center; margin: 30px 0;"><a href="{{upload_url}}" style="display: inline-block; background: #dc2626; color: white; padding: 12px 30px; text-decoration: none; border-radius: 8px; font-weight: bold;">Reupload Documents</a></div><p style="margin-top: 30px;">Best regards,<br>The X Business Grant Team</p></div><div style="padding: 20px 30px; background: #f9fafb; text-align: center; color: #6b7280; font-size: 12px;"><p>© {{year}} X Business Grant. All rights reserved.</p></div></div>'
        ],
        [
            'name' => 'Application Status Update',
            'subject' => 'Application Status Update - {{reference}}',
            'body' => '<div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;"><div style="background: linear-gradient(135deg, #1e40af 0%, #1e3a8a 100%); padding: 30px; text-align: center;"><h1 style="color: white; margin: 0; font-size: 24px;">X Business Grant</h1></div><div style="padding: 40px 30px; background: #ffffff;"><h2 style="color: #1e40af; margin-top: 0;">Application Status Update</h2><p>Dear {{name}},</p><p>There has been an update to your grant application (Reference: <strong>{{reference}}</strong>).</p><div style="background: #f3f4f6; padding: 20px; border-radius: 8px; margin: 20px 0; text-align: center;"><p style="margin: 0; font-size: 18px;"><strong>Current Status:</strong> {{status}}</p></div><p>Please log in to your dashboard to view more details.</p><div style="text-align: center; margin: 30px 0;"><a href="{{dashboard_url}}" style="display: inline-block; background: #1e40af; color: white; padding: 12px 30px; text-decoration: none; border-radius: 8px; font-weight: bold;">View Dashboard</a></div><p style="margin-top: 30px;">Best regards,<br>The X Business Grant Team</p></div><div style="padding: 20px 30px; background: #f9fafb; text-align: center; color: #6b7280; font-size: 12px;"><p>© {{year}} X Business Grant. All rights reserved.</p></div></div>'
        ],
        [
            'name' => 'Document Upload Reminder',
            'subject' => 'Reminder: documents required for {{reference}}',
            'body' => '<div style="font-family:Arial,sans-serif;max-width:600px;margin:0 auto"><div style="background:#d97706;padding:28px;text-align:center"><h1 style="color:#fff;margin:0">X Business Grant</h1></div><div style="padding:32px;background:#fff"><h2 style="color:#b45309">Your documents are still needed</h2><p>Dear {{name}},</p><p>Please upload these documents for application <strong>{{reference}}</strong>:</p><ul>{{documents}}</ul><div style="text-align:center;margin:28px 0"><a href="{{upload_url}}" style="display:inline-block;background:#d97706;color:#fff;padding:12px 26px;text-decoration:none;border-radius:6px;font-weight:bold">Upload Documents</a></div></div></div>'
        ],
        [
            'name' => 'Document Correction Required',
            'subject' => 'Action needed: correct documents for {{reference}}',
            'body' => '<div style="font-family:Arial,sans-serif;max-width:600px;margin:0 auto"><div style="background:#b91c1c;padding:28px;text-align:center"><h1 style="color:#fff;margin:0">X Business Grant</h1></div><div style="padding:32px;background:#fff"><h2 style="color:#b91c1c">Please correct and resubmit documents</h2><p>Dear {{name}},</p><p>Please correct these documents for application <strong>{{reference}}</strong>:</p><ul>{{documents}}</ul><div style="text-align:center;margin:28px 0"><a href="{{upload_url}}" style="display:inline-block;background:#b91c1c;color:#fff;padding:12px 26px;text-decoration:none;border-radius:6px;font-weight:bold">Resubmit Documents</a></div></div></div>'
        ],
        [
            'name' => 'Funding Agreement Ready',
            'subject' => 'Your funding agreement is ready - {{reference}}',
            'body' => '<div style="font-family:Arial,sans-serif;max-width:600px;margin:0 auto"><div style="background:#047857;padding:28px;text-align:center"><h1 style="color:#fff;margin:0">X Business Grant</h1></div><div style="padding:32px;background:#fff"><h2 style="color:#047857">Funding agreement ready</h2><p>Dear {{name}},</p><p>Your funding agreement for <strong>{{reference}}</strong> is ready for review.</p><div style="text-align:center;margin:28px 0"><a href="{{dashboard_url}}" style="display:inline-block;background:#047857;color:#fff;padding:12px 26px;text-decoration:none;border-radius:6px;font-weight:bold">Review Agreement</a></div></div></div>'
        ],
        [
            'name' => 'Document Request - Initial Submission',
            'subject' => 'Action Required: Submit Documents for {{reference}}',
            'body' => '<div style="font-family:Arial,sans-serif;max-width:600px;margin:0 auto"><div style="background:#f59e0b;padding:28px;text-align:center"><h1 style="color:#fff;margin:0">X Business Grant</h1></div><div style="padding:32px;background:#fff"><h2 style="color:#b45309">Documents Required</h2><p>Dear {{name}},</p><p>Thank you for your grant application (Reference: <strong>{{reference}}</strong>). To proceed with the review, we need you to submit the following required documents:</p><div style="background:#fffbeb;border-left:4px solid #f59e0b;padding:16px;margin:20px 0"><ul style="margin:0;padding-left:20px">{{documents}}</ul></div><p>Please upload all documents using the secure portal below. Make sure each file is clear and legible.</p><div style="text-align:center;margin:28px 0"><a href="{{upload_url}}" style="display:inline-block;background:#f59e0b;color:#fff;padding:12px 26px;text-decoration:none;border-radius:6px;font-weight:bold">Submit Documents</a></div><p>If you have any questions, contact us at <a href="mailto:support@xbusinessgrant.ng" style="color:#1e40af">support@xbusinessgrant.ng</a>.</p><p>Best regards,<br>The X Business Grant Team</p></div></div>'
        ],
        [
            'name' => 'Document Request - Follow-up',
            'subject' => 'Follow-up: Documents Still Required for {{reference}}',
            'body' => '<div style="font-family:Arial,sans-serif;max-width:600px;margin:0 auto"><div style="background:#d97706;padding:28px;text-align:center"><h1 style="color:#fff;margin:0">X Business Grant</h1></div><div style="padding:32px;background:#fff"><h2 style="color:#b45309">Documents Still Required</h2><p>Dear {{name}},</p><p>This is a follow-up regarding your grant application (Reference: <strong>{{reference}}</strong>). We are still waiting for the following documents:</p><div style="background:#fffbeb;border-left:4px solid #f59e0b;padding:16px;margin:20px 0"><ul style="margin:0;padding-left:20px">{{documents}}</ul></div><p>Please submit these documents as soon as possible to avoid delays in processing your application.</p><div style="text-align:center;margin:28px 0"><a href="{{upload_url}}" style="display:inline-block;background:#d97706;color:#fff;padding:12px 26px;text-decoration:none;border-radius:6px;font-weight:bold">Upload Remaining Documents</a></div><p>Need help? Visit your <a href="{{dashboard_url}}" style="color:#1e40af">dashboard</a> or contact support.</p><p>Best regards,<br>The X Business Grant Team</p></div></div>'
        ],
        [
            'name' => 'Document Request - Final Reminder',
            'subject' => 'Final Reminder: Documents Due for {{reference}}',
            'body' => '<div style="font-family:Arial,sans-serif;max-width:600px;margin:0 auto"><div style="background:#b91c1c;padding:28px;text-align:center"><h1 style="color:#fff;margin:0">X Business Grant</h1></div><div style="padding:32px;background:#fff"><h2 style="color:#b91c1c">Final Document Reminder</h2><p>Dear {{name}},</p><p>This is your final reminder. Your grant application (Reference: <strong>{{reference}}</strong>) requires the following documents, and the deadline is <strong>{{new_deadline}}</strong>:</p><div style="background:#fef2f2;border-left:4px solid #dc2626;padding:16px;margin:20px 0"><ul style="margin:0;padding-left:20px">{{documents}}</ul></div><p>If we do not receive these documents by the deadline, your application may be closed.</p><div style="text-align:center;margin:28px 0"><a href="{{upload_url}}" style="display:inline-block;background:#b91c1c;color:#fff;padding:12px 26px;text-decoration:none;border-radius:6px;font-weight:bold">Submit Before Deadline</a></div><p>Best regards,<br>The X Business Grant Team</p></div></div>'
        ],
        [
            'name' => 'Document Request - Partial Submission',
            'subject' => 'Partial Documents Received - More Needed for {{reference}}',
            'body' => '<div style="font-family:Arial,sans-serif;max-width:600px;margin:0 auto"><div style="background:#f59e0b;padding:28px;text-align:center"><h1 style="color:#fff;margin:0">X Business Grant</h1></div><div style="padding:32px;background:#fff"><h2 style="color:#b45309">Additional Documents Needed</h2><p>Dear {{name}},</p><p>Thank you for submitting some documents for your application (Reference: <strong>{{reference}}</strong>). We have received part of your submission, but the following documents are still missing:</p><div style="background:#fffbeb;border-left:4px solid #f59e0b;padding:16px;margin:20px 0"><ul style="margin:0;padding-left:20px">{{documents}}</ul></div><p>Please upload the remaining documents using the secure portal below.</p><div style="text-align:center;margin:28px 0"><a href="{{upload_url}}" style="display:inline-block;background:#f59e0b;color:#fff;padding:12px 26px;text-decoration:none;border-radius:6px;font-weight:bold">Upload Missing Documents</a></div><p>Best regards,<br>The X Business Grant Team</p></div></div>'
        ],
        [
            'name' => 'Document Request - Expired Link',
            'subject' => 'Upload Link Expired - Resubmit Documents for {{reference}}',
            'body' => '<div style="font-family:Arial,sans-serif;max-width:600px;margin:0 auto"><div style="background:#d97706;padding:28px;text-align:center"><h1 style="color:#fff;margin:0">X Business Grant</h1></div><div style="padding:32px;background:#fff"><h2 style="color:#b45309">Upload Link Expired</h2><p>Dear {{name}},</p><p>Your previous document upload link for application (Reference: <strong>{{reference}}</strong>) has expired. Please request a new upload link to submit the following documents:</p><div style="background:#fffbeb;border-left:4px solid #f59e0b;padding:16px;margin:20px 0"><ul style="margin:0;padding-left:20px">{{documents}}</ul></div><div style="text-align:center;margin:28px 0"><a href="{{dashboard_url}}" style="display:inline-block;background:#d97706;color:#fff;padding:12px 26px;text-decoration:none;border-radius:6px;font-weight:bold">Request New Upload Link</a></div><p>You can also visit your dashboard to generate a new upload link at any time.</p><p>Best regards,<br>The X Business Grant Team</p></div></div>'
        ],
        [
            'name' => 'Application Submitted',
            'subject' => 'Application Submitted Successfully - {{reference}}',
            'body' => '<div style="font-family:Arial,sans-serif;max-width:600px;margin:0 auto"><div style="background:#059669;padding:28px;text-align:center"><h1 style="color:#fff;margin:0">X Business Grant</h1></div><div style="padding:32px;background:#fff"><h2 style="color:#047857">Application Submitted!</h2><p>Dear {{name}},</p><p>Your grant application has been successfully submitted. Your reference number is: <strong>{{reference}}</strong></p><p>Our team will review your application and contact you within 5-7 business days. You will receive email updates on your application status.</p><div style="background:#ecfdf5;padding:20px;border-radius:8px;margin:20px 0"><p style="margin:0"><strong>Status:</strong> Submitted</p><p style="margin:5px 0 0 0"><strong>Expected Review:</strong> 5-7 business days</p></div><div style="text-align:center;margin:28px 0"><a href="{{dashboard_url}}" style="display:inline-block;background:#059669;color:#fff;padding:12px 26px;text-decoration:none;border-radius:6px;font-weight:bold">View Application</a></div><p>Best regards,<br>The X Business Grant Team</p></div></div>'
        ],
        [
            'name' => 'Application In Review',
            'subject' => 'Your Application is Now in Review - {{reference}}',
            'body' => '<div style="font-family:Arial,sans-serif;max-width:600px;margin:0 auto"><div style="background:#1e40af;padding:28px;text-align:center"><h1 style="color:#fff;margin:0">X Business Grant</h1></div><div style="padding:32px;background:#fff"><h2 style="color:#1e40af">Application In Review</h2><p>Dear {{name}},</p><p>Good news! Your grant application (Reference: <strong>{{reference}}</strong>) is now under review by our evaluation team.</p><p>Our reviewers are carefully assessing your submission. This process typically takes 5-7 business days.</p><div style="background:#eff6ff;padding:20px;border-radius:8px;margin:20px 0"><p style="margin:0"><strong>Current Status:</strong> {{status}}</p><p style="margin:5px 0 0 0"><strong>Review Deadline:</strong> {{new_deadline}}</p></div><div style="text-align:center;margin:28px 0"><a href="{{dashboard_url}}" style="display:inline-block;background:#1e40af;color:#fff;padding:12px 26px;text-decoration:none;border-radius:6px;font-weight:bold">Track Progress</a></div><p>You will receive another email once the review is complete.</p><p>Best regards,<br>The X Business Grant Team</p></div></div>'
        ],
        [
            'name' => 'Application Approved',
            'subject' => 'Congratulations! Your Grant is Approved - {{reference}}',
            'body' => '<div style="font-family:Arial,sans-serif;max-width:600px;margin:0 auto"><div style="background:#059669;padding:28px;text-align:center"><h1 style="color:#fff;margin:0">X Business Grant - Approved!</h1></div><div style="padding:32px;background:#fff"><h2 style="color:#059669">Congratulations, {{name}}!</h2><p>We are delighted to inform you that your grant application (Reference: <strong>{{reference}}</strong>) has been approved!</p><div style="background:#ecfdf5;padding:20px;border-radius:8px;margin:20px 0"><p style="margin:0"><strong>Approved Amount:</strong> {{amount}}</p></div><p>Our team will contact you shortly with details on how to receive your grant funds.</p><div style="text-align:center;margin:28px 0"><a href="{{dashboard_url}}" style="display:inline-block;background:#059669;color:#fff;padding:12px 26px;text-decoration:none;border-radius:6px;font-weight:bold">View Approval Details</a></div><p>Best regards,<br>The X Business Grant Team</p></div></div>'
        ],
        [
            'name' => 'Application Rejected',
            'subject' => 'Application Update - {{reference}}',
            'body' => '<div style="font-family:Arial,sans-serif;max-width:600px;margin:0 auto"><div style="background:#b91c1c;padding:28px;text-align:center"><h1 style="color:#fff;margin:0">X Business Grant</h1></div><div style="padding:32px;background:#fff"><h2 style="color:#b91c1c">Application Update</h2><p>Dear {{name}},</p><p>After careful review of your grant application (Reference: <strong>{{reference}}</strong>), we regret to inform you that we are unable to approve your request at this time.</p><p>This decision does not reflect on your business potential. We encourage you to:</p><ul style="padding-left:20px"><li>Review and strengthen your business plan</li><li>Build your credit history</li><li>Gather additional supporting documents</li></ul><p>You are welcome to reapply in the future. We wish you success in your business journey.</p><div style="text-align:center;margin:28px 0"><a href="{{apply_url}}" style="display:inline-block;background:#b91c1c;color:#fff;padding:12px 26px;text-decoration:none;border-radius:6px;font-weight:bold">Reapply Now</a></div><p>Best regards,<br>The X Business Grant Team</p></div></div>'
        ],
        [
            'name' => 'Payment Initiated',
            'subject' => 'Payment Initiated for {{reference}}',
            'body' => '<div style="font-family:Arial,sans-serif;max-width:600px;margin:0 auto"><div style="background:#059669;padding:28px;text-align:center"><h1 style="color:#fff;margin:0">X Business Grant - Payment</h1></div><div style="padding:32px;background:#fff"><h2 style="color:#047857">Payment Initiated</h2><p>Dear {{name}},</p><p>Great news! Your grant payment for application (Reference: <strong>{{reference}}</strong>) has been initiated.</p><div style="background:#ecfdf5;padding:20px;border-radius:8px;margin:20px 0"><p style="margin:0"><strong>Amount:</strong> {{amount}}</p><p style="margin:5px 0 0 0"><strong>Status:</strong> Payment Processing</p></div><p>Please allow 3-5 business days for the funds to reflect in your account.</p><div style="text-align:center;margin:28px 0"><a href="{{dashboard_url}}" style="display:inline-block;background:#059669;color:#fff;padding:12px 26px;text-decoration:none;border-radius:6px;font-weight:bold">View Payment Details</a></div><p>Best regards,<br>The X Business Grant Team</p></div></div>'
        ],
        [
            'name' => 'Payment Received',
            'subject' => 'Payment Received - {{reference}}',
            'body' => '<div style="font-family:Arial,sans-serif;max-width:600px;margin:0 auto"><div style="background:#059669;padding:28px;text-align:center"><h1 style="color:#fff;margin:0">X Business Grant - Payment Complete</h1></div><div style="padding:32px;background:#fff"><h2 style="color:#047857">Payment Received!</h2><p>Dear {{name}},</p><p>We are pleased to confirm that your grant payment has been successfully transferred to your account.</p><div style="background:#ecfdf5;padding:20px;border-radius:8px;margin:20px 0"><p style="margin:0"><strong>Reference:</strong> {{reference}}</p><p style="margin:5px 0 0 0"><strong>Amount Received:</strong> {{amount}}</p><p style="margin:5px 0 0 0"><strong>Status:</strong> Completed</p></div><p>Congratulations on receiving your grant! We encourage you to use these funds wisely to grow your business.</p><div style="text-align:center;margin:28px 0"><a href="{{dashboard_url}}" style="display:inline-block;background:#059669;color:#fff;padding:12px 26px;text-decoration:none;border-radius:6px;font-weight:bold">View Receipt</a></div><p>Best regards,<br>The X Business Grant Team</p></div></div>'
        ],
        [
            'name' => 'Interview Invitation',
            'subject' => 'Interview Invitation - {{reference}}',
            'body' => '<div style="font-family:Arial,sans-serif;max-width:600px;margin:0 auto"><div style="background:#7c3aed;padding:28px;text-align:center"><h1 style="color:#fff;margin:0">X Business Grant</h1></div><div style="padding:32px;background:#fff"><h2 style="color:#7c3aed">Interview Invitation</h2><p>Dear {{name}},</p><p>Congratulations! You have been selected for an interview regarding your grant application (Reference: <strong>{{reference}}</strong>).</p><div style="background:#f5f3ff;padding:20px;border-radius:8px;margin:20px 0"><p style="margin:0"><strong>Date:</strong> {{date}}</p><p style="margin:5px 0 0 0"><strong>Time:</strong> {{time}}</p><p style="margin:5px 0 0 0"><strong>Location:</strong> {{location}}</p></div><p>Please confirm your attendance by clicking the button below.</p><div style="text-align:center;margin:28px 0"><a href="{{continue_url}}" style="display:inline-block;background:#7c3aed;color:#fff;padding:12px 26px;text-decoration:none;border-radius:6px;font-weight:bold">Confirm Interview</a></div><p>Please arrive 15 minutes early and bring all required documents.</p><p>Best regards,<br>The X Business Grant Team</p></div></div>'
        ],
        [
            'name' => 'Interview Confirmation',
            'subject' => 'Interview Confirmed - {{reference}}',
            'body' => '<div style="font-family:Arial,sans-serif;max-width:600px;margin:0 auto"><div style="background:#059669;padding:28px;text-align:center"><h1 style="color:#fff;margin:0">X Business Grant</h1></div><div style="padding:32px;background:#fff"><h2 style="color:#047857">Interview Confirmed</h2><p>Dear {{name}},</p><p>Your interview for grant application (Reference: <strong>{{reference}}</strong>) has been confirmed.</p><div style="background:#ecfdf5;padding:20px;border-radius:8px;margin:20px 0"><p style="margin:0"><strong>Date:</strong> {{date}}</p><p style="margin:5px 0 0 0"><strong>Time:</strong> {{time}}</p><p style="margin:5px 0 0 0"><strong>Location:</strong> {{location}}</p></div><p>Please remember to bring all required documents and arrive 15 minutes early.</p><div style="text-align:center;margin:28px 0"><a href="{{dashboard_url}}" style="display:inline-block;background:#059669;color:#fff;padding:12px 26px;text-decoration:none;border-radius:6px;font-weight:bold">View Interview Details</a></div><p>Best regards,<br>The X Business Grant Team</p></div></div>'
        ],
        [
            'name' => 'Deadline Extension',
            'subject' => 'Deadline Extended - {{reference}}',
            'body' => '<div style="font-family:Arial,sans-serif;max-width:600px;margin:0 auto"><div style="background:#1e40af;padding:28px;text-align:center"><h1 style="color:#fff;margin:0">X Business Grant</h1></div><div style="padding:32px;background:#fff"><h2 style="color:#1e40af">Deadline Extended</h2><p>Dear {{name}},</p><p>Good news! The deadline for your grant application (Reference: <strong>{{reference}}</strong>) has been extended.</p><div style="background:#eff6ff;padding:20px;border-radius:8px;margin:20px 0;text-align:center"><p style="margin:0;font-size:18px"><strong>New Deadline:</strong> {{new_deadline}}</p></div><p>This extension gives you more time to complete the required actions. Please use the link below to continue your application.</p><div style="text-align:center;margin:28px 0"><a href="{{continue_url}}" style="display:inline-block;background:#1e40af;color:#fff;padding:12px 26px;text-decoration:none;border-radius:6px;font-weight:bold">Continue Application</a></div><p>Best regards,<br>The X Business Grant Team</p></div></div>'
        ],
        [
            'name' => 'Account Created',
            'subject' => 'Welcome! Your Account is Ready - {{name}}',
            'body' => '<div style="font-family:Arial,sans-serif;max-width:600px;margin:0 auto"><div style="background:#059669;padding:28px;text-align:center"><h1 style="color:#fff;margin:0">X Business Grant</h1></div><div style="padding:32px;background:#fff"><h2 style="color:#047857">Welcome, {{name}}!</h2><p>Your account has been successfully created with X Business Grant.</p><p>You can now apply for grants, track your applications, and manage your profile all from your dashboard.</p><div style="text-align:center;margin:28px 0"><a href="{{dashboard_url}}" style="display:inline-block;background:#059669;color:#fff;padding:12px 26px;text-decoration:none;border-radius:6px;font-weight:bold">Complete Your Profile</a></div><p>To get started, we recommend completing your profile and submitting your first grant application.</p><p>Best regards,<br>The X Business Grant Team</p></div></div>'
        ],
        [
            'name' => 'Profile Update Required',
            'subject' => 'Profile Update Required - {{name}}',
            'body' => '<div style="font-family:Arial,sans-serif;max-width:600px;margin:0 auto"><div style="background:#f59e0b;padding:28px;text-align:center"><h1 style="color:#fff;margin:0">X Business Grant</h1></div><div style="padding:32px;background:#fff"><h2 style="color:#b45309">Profile Update Required</h2><p>Dear {{name}},</p><p>To continue processing your grant application, we need you to update your profile with the following information:</p><div style="background:#fffbeb;border-left:4px solid #f59e0b;padding:16px;margin:20px 0"><ul style="margin:0;padding-left:20px">{{documents}}</ul></div><p>Please update your profile as soon as possible to avoid delays.</p><div style="text-align:center;margin:28px 0"><a href="{{dashboard_url}}" style="display:inline-block;background:#f59e0b;color:#fff;padding:12px 26px;text-decoration:none;border-radius:6px;font-weight:bold">Update Profile</a></div><p>Best regards,<br>The X Business Grant Team</p></div></div>'
        ],
        [
            'name' => 'Document Verification in Progress',
            'subject' => 'Document Verification in Progress - {{reference}}',
            'body' => '<div style="font-family:Arial,sans-serif;max-width:600px;margin:0 auto"><div style="background:#1e40af;padding:28px;text-align:center"><h1 style="color:#fff;margin:0">X Business Grant</h1></div><div style="padding:32px;background:#fff"><h2 style="color:#1e40af">Verification in Progress</h2><p>Dear {{name}},</p><p>We are currently verifying the documents submitted for your application (Reference: <strong>{{reference}}</strong>).</p><p>The following documents are being reviewed:</p><div style="background:#eff6ff;border-left:4px solid #1e40af;padding:16px;margin:20px 0"><ul style="margin:0;padding-left:20px">{{documents}}</ul></div><p>This process typically takes 2-3 business days. You will receive another email once verification is complete.</p><div style="text-align:center;margin:28px 0"><a href="{{dashboard_url}}" style="display:inline-block;background:#1e40af;color:#fff;padding:12px 26px;text-decoration:none;border-radius:6px;font-weight:bold">View Status</a></div><p>Best regards,<br>The X Business Grant Team</p></div></div>'
        ],
        [
            'name' => 'Grant Disbursement',
            'subject' => 'Grant Disbursement Initiated - {{reference}}',
            'body' => '<div style="font-family:Arial,sans-serif;max-width:600px;margin:0 auto"><div style="background:#059669;padding:28px;text-align:center"><h1 style="color:#fff;margin:0">X Business Grant - Disbursement</h1></div><div style="padding:32px;background:#fff"><h2 style="color:#047857">Grant Disbursement Initiated</h2><p>Dear {{name}},</p><p>Your grant payment for application (Reference: <strong>{{reference}}</strong>) is being processed for disbursement.</p><div style="background:#ecfdf5;padding:20px;border-radius:8px;margin:20px 0"><p style="margin:0"><strong>Amount:</strong> {{amount}}</p><p style="margin:5px 0 0 0"><strong>Status:</strong> Disbursement in Progress</p></div><p>The funds will be transferred to your designated account. Please allow 3-5 business days for the transaction to complete.</p><div style="text-align:center;margin:28px 0"><a href="{{dashboard_url}}" style="display:inline-block;background:#059669;color:#fff;padding:12px 26px;text-decoration:none;border-radius:6px;font-weight:bold">View Disbursement Details</a></div><p>Best regards,<br>The X Business Grant Team</p></div></div>'
        ]
    ];
    
    $templatesCreated = 0;
    foreach ($emailTemplates as $template) {
        $existing = $db->fetchOne("SELECT id FROM email_templates WHERE name = ?", [$template['name']]);
        if (!$existing) {
            $db->query(
                "INSERT INTO email_templates (name, subject, body) VALUES (?, ?, ?)",
                [$template['name'], $template['subject'], $template['body']]
            );
            $templatesCreated++;
            echo "  - Created template: {$template['name']}\n";
        } else {
            echo "  - Template exists: {$template['name']}\n";
        }
    }
    echo "  Done: {$templatesCreated} email templates created\n\n";
    
    echo "[7/15] Seeding WhatsApp templates...\n";
    
    $whatsappTemplates = [
        [
            'name' => 'Welcome Message',
            'category' => 'onboarding',
            'message' => "Welcome to X Business Grant! 🎉\n\nDear {{name}},\n\nThank you for registering with X Business Grant. We're excited to have you on board!\n\nWith X Business Grant, you can apply for business grants up to ₦5,000,000.\n\nTo get started:\n1. Complete your profile\n2. Submit your grant application\n3. Track your application status\n\nNeed help? Reply to this message and we'll assist you.\n\nBest regards,\nX Business Grant Team",
            'variables' => 'name'
        ],
        [
            'name' => 'Application Received',
            'category' => 'application',
            'message' => "X Business Grant - Application Received ✅\n\nDear {{name}},\n\nWe have received your grant application!\n\n📋 Reference Number: {{reference}}\n\nOur team will review your application and get back to you within 5-7 business days.\n\nYou can track your application status on your dashboard.\n\nBest regards,\nX Business Grant Team",
            'variables' => 'name,reference'
        ],
        [
            'name' => 'Application Under Review',
            'category' => 'application',
            'message' => "X Business Grant - Application Update 📋\n\nDear {{name}},\n\nYour application ({{reference}}) is now under review.\n\nOur team is carefully reviewing all documents and information provided.\n\n⏱️ Expected Timeline: 5-7 business days\n\nWe'll notify you once a decision has been made.\n\nBest regards,\nX Business Grant Team",
            'variables' => 'name,reference'
        ],
        [
            'name' => 'Application Approved',
            'category' => 'application',
            'message' => "🎉 Congratulations, {{name}}!\n\nYour grant application ({{reference}}) has been APPROVED!\n\n💰 Approved Amount: {{amount}}\n\nOur team will contact you shortly with details on how to receive your grant funds.\n\nThank you for choosing X Business Grant!\n\nBest regards,\nX Business Grant Team",
            'variables' => 'name,reference,amount'
        ],
        [
            'name' => 'Application Rejected',
            'category' => 'application',
            'message' => "X Business Grant - Application Update\n\nDear {{name}},\n\nThank you for your interest in X Business Grant.\n\nAfter careful review, we regret to inform you that we are unable to approve your grant request at this time.\n\nReference: {{reference}}\n\n💡 You are welcome to reapply in the future with updated business information.\n\nBest regards,\nX Business Grant Team",
            'variables' => 'name,reference'
        ],
        [
            'name' => 'Document Request',
            'category' => 'application',
            'message' => "X Business Grant - Additional Documents Required 📄\n\nDear {{name}},\n\nWe are reviewing your application ({{reference}}) and need additional documents.\n\nPlease upload the following:\n{{documents}}\n\n⚠️ Please submit within 7 days to avoid delays.\n\nBest regards,\nX Business Grant Team",
            'variables' => 'name,reference,documents'
        ],
        [
            'name' => 'Payment Processing',
            'category' => 'payment',
            'message' => "X Business Grant - Payment Update 💰\n\nDear {{name}},\n\nGreat news! Your grant payment is being processed.\n\n📋 Reference: {{reference}}\n💵 Amount: {{amount}}\n\n⏱️ Please allow 3-5 business days for funds to reflect in your account.\n\nBest regards,\nX Business Grant Team",
            'variables' => 'name,reference,amount'
        ],
        [
            'name' => 'Payment Completed',
            'category' => 'payment',
            'message' => "🎊 Payment Successful!\n\nDear {{name}},\n\nWe are pleased to confirm that your grant payment has been transferred!\n\n📋 Reference: {{reference}}\n💵 Amount Received: {{amount}}\n\n✅ Transaction Complete\n\nCongratulations on receiving your grant! We wish you the best in growing your business.\n\nBest regards,\nX Business Grant Team",
            'variables' => 'name,reference,amount'
        ],
        [
            'name' => 'Account Verification',
            'category' => 'verification',
            'message' => "X Business Grant - Verify Your Account 🔐\n\nDear {{name}},\n\nYour verification code is: {{code}}\n\n⏱️ This code expires in 30 minutes.\n\nEnter this code to verify your account.\n\nBest regards,\nX Business Grant Team",
            'variables' => 'name,code'
        ],
        [
            'name' => 'Password Reset',
            'category' => 'security',
            'message' => "X Business Grant - Password Reset 🔑\n\nDear {{name}},\n\nWe received a request to reset your password.\n\nClick the link below to set a new password:\n{{reset_link}}\n\n⏱️ This link expires in 1 hour.\n\nIf you didn't request this, please ignore this message.\n\nBest regards,\nX Business Grant Team",
            'variables' => 'name,reset_link'
        ],
        [
            'name' => 'Reminder - Incomplete Application',
            'category' => 'reminder',
            'message' => "X Business Grant - Application Reminder ⏰\n\nDear {{name}},\n\nWe noticed you started but haven't completed your grant application.\n\nYour saved application ({{reference}}) is still pending submission.\n\n📋 Required Actions:\n{{actions}}\n\nComplete your application now to be considered for the grant.\n\nBest regards,\nX Business Grant Team",
            'variables' => 'name,reference,actions'
        ],
        [
            'name' => 'Start Application',
            'category' => 'application',
            'message' => "X Business Grant - Start Your Application 🚀\n\nDear {{name}},\n\nWe're excited to let you know that the X Business Grant application portal is now open!\n\nYou can apply for business grants up to ₦5,000,000 to grow your business.\n\n📋 How to Apply:\n1. Visit: https://xbusinessgrant.ng/apply.php\n2. Fill in your business details\n3. Upload required documents\n4. Submit your application\n\n💰 Grant Amount: ₦100,000 - ₦5,000,000\n\nDon't miss this opportunity to grow your business!\n\nBest regards,\nX Business Grant Team",
            'variables' => 'name'
        ],
        [
            'name' => 'Deadline Reminder',
            'category' => 'reminder',
            'message' => "⏰ X Business Grant - Deadline Reminder\n\nDear {{name}},\n\nThis is a reminder that your application ({{reference}}) requires attention.\n\n📅 Deadline: {{deadline}}\n\n⚠️ Please complete the required actions before the deadline:\n{{actions}}\n\nBest regards,\nX Business Grant Team",
            'variables' => 'name,reference,deadline,actions'
        ],
        [
            'name' => 'Interview Scheduled',
            'category' => 'interview',
            'message' => "X Business Grant - Interview Scheduled 📅\n\nDear {{name}},\n\nCongratulations! You have been selected for an interview.\n\n📋 Application: {{reference}}\n📅 Date: {{date}}\n🕐 Time: {{time}}\n📍 Location: {{location}}\n\nPlease confirm your attendance by replying to this message.\n\nBest regards,\nX Business Grant Team",
            'variables' => 'name,reference,date,time,location'
        ],
        [
            'name' => 'Interview Reminder',
            'category' => 'interview',
            'message' => "🔔 X Business Grant - Interview Reminder\n\nDear {{name}},\n\nThis is a reminder of your upcoming interview:\n\n📋 Application: {{reference}}\n📅 Date: {{date}}\n🕐 Time: {{time}}\n📍 Location: {{location}}\n\nPlease arrive 15 minutes early with all required documents.\n\nBest regards,\nX Business Grant Team",
            'variables' => 'name,reference,date,time,location'
        ],
        [
            'name' => 'General Inquiry Response',
            'category' => 'support',
            'message' => "X Business Grant - Response to Your Inquiry 💬\n\nDear {{name}},\n\nThank you for reaching out to us.\n\n{{response}}\n\nIf you have any further questions, please don't hesitate to contact us.\n\nBest regards,\nX Business Grant Team",
            'variables' => 'name,response'
        ]
    ];
    
    $whatsappTemplatesCreated = 0;
    foreach ($whatsappTemplates as $template) {
        $existing = $db->fetchOne("SELECT id FROM whatsapp_templates WHERE name = ?", [$template['name']]);
        if (!$existing) {
            $db->query(
                "INSERT INTO whatsapp_templates (name, category, message, variables) VALUES (?, ?, ?, ?)",
                [$template['name'], $template['category'], $template['message'], $template['variables']]
            );
            $whatsappTemplatesCreated++;
            echo "  - Created template: {$template['name']}\n";
        } else {
            echo "  - Template exists: {$template['name']}\n";
        }
    }
    echo "  Done: {$whatsappTemplatesCreated} WhatsApp templates created\n\n";
    
    echo "[8/15] Creating sample activity log entries...\n";
    $logEntries = [
        ['admin', 'admin', 'login', 'Admin user logged in', '127.0.0.1'],
        ['admin', 'admin', 'view_applications', 'Admin viewed applications list', '127.0.0.1'],
        ['user', 'emeka.nwankwo@example.com', 'register', 'New user registered', '197.210.76.45'],
        ['user', 'funke.adeleke@example.com', 'submit_application', 'Application submitted', '102.89.34.21']
    ];
    
    $logsCreated = 0;
    foreach ($logEntries as $log) {
        $db->query(
            "INSERT INTO activity_log (user_type, user_id, action, description, ip_address, created_at) VALUES (?, ?, ?, ?, ?, NOW())",
            [$log[0], $log[1] === 'admin' ? 1 : 0, $log[2], $log[3], $log[4]]
        );
        $logsCreated++;
    }
    echo "  - Created {$logsCreated} activity log entries\n\n";
    
    echo "[9/15] Creating sample activity log entries...\n";
    $stats = $app->getStats();
    echo "  - Total applications: {$stats['total']}\n";
    echo "  - Pending: {$stats['pending']}\n";
    echo "  - Under review: {$stats['under_review']}\n";
    echo "  - Approved: {$stats['approved']}\n";
    echo "  - Rejected: {$stats['rejected']}\n";
    echo "  - Total approved amount: " . Helpers::formatNaira($stats['total_grant_amount']) . "\n\n";
    
    echo "========================================\n";
    echo "Seeding completed successfully!\n";
    echo "========================================\n";
    echo "Schema setup: Complete (11 tables)\n";
    echo "Settings created: {$settingsCreated}\n";
    echo "Users created: {$usersCreated}\n";
    echo "Applications created: {$createdApplications}\n";
    echo "Email templates created: {$templatesCreated}\n";
    echo "WhatsApp templates created: {$whatsappTemplatesCreated}\n\n";
    echo "Admin Login:\n";
    echo "  URL: http://localhost/X%20Business%20Grants/admin/login.php\n";
    echo "  Username: admin\n";
    echo "  Password: admin123\n\n";
    echo "Reviewer Login:\n";
    echo "  Username: reviewer\n";
    echo "  Password: reviewer123\n";
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
    exit(1);
}
