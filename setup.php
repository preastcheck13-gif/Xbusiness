<?php
/**
 * X Business Grant - Database Setup Script
 * Run this to create the database and all tables
 * 
 * Usage: php setup.php
 */

require_once 'includes/Database.php';
require_once 'includes/helpers.php';
require_once 'includes/User.php';
require_once 'includes/Application.php';

use App\Database;
use App\User;
use App\Application;

echo "========================================\n";
echo "X Business Grant - Database Setup\n";
echo "========================================\n\n";

try {
    $db = Database::getInstance();
    
    echo "[1/5] Creating tables...\n";
    
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
    echo "  ✓ Users table created\n";
    
    // Create applications table
    $db->query("
        CREATE TABLE IF NOT EXISTS applications (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
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
            INDEX idx_cac (cac_number),
            INDEX idx_status (status),
            INDEX idx_state (state),
            INDEX idx_sector (business_sector),
            INDEX idx_created (created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo "  ✓ Applications table created\n";
    
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
    echo "  ✓ Documents table created\n";
    
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
    echo "  ✓ Admin users table created\n";
    
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
    echo "  ✓ Status history table created\n";
    
    echo "\n[2/5] Creating admin user...\n";
    $adminPassword = password_hash('admin123', PASSWORD_DEFAULT);
    $db->query(
        "INSERT IGNORE INTO admin_users (username, email, password_hash, full_name, role, created_at, updated_at) VALUES (?, ?, ?, ?, ?, NOW(), NOW())",
        ['admin', 'admin@xbusinessgrant.ng', $adminPassword, 'System Administrator', 'super_admin']
    );
    echo "  ✓ Admin user created (admin / admin123)\n";
    
    echo "\n[3/5] Verifying tables...\n";
    $tables = $db->fetchAll("SHOW TABLES");
    echo "  Found " . count($tables) . " tables:\n";
    foreach ($tables as $table) {
        $tableName = array_values($table)[0];
        echo "    - {$tableName}\n";
    }
    
    echo "\n[4/5] Verifying admin user...\n";
    $admin = $db->fetchOne("SELECT * FROM admin_users WHERE username = 'admin'");
    if ($admin) {
        echo "  ✓ Admin user exists\n";
        echo "  Username: admin\n";
        echo "  Password: admin123\n";
    } else {
        echo "  ⚠ Admin user not found (this shouldn't happen)\n";
    }
    
    echo "\n[5/5] Testing database queries...\n";
    $result = $db->fetchOne("SELECT COUNT(*) as cnt FROM applications");
    echo "  ✓ Database queries work\n";
    echo "  Current applications: {$result['cnt']}\n";
    
    echo "\n========================================\n";
    echo "Setup completed successfully!\n";
    echo "========================================\n\n";
    echo "Database: xbusiness_grants\n";
    echo "Tables: users, applications, documents, admin_users, status_history\n\n";
    echo "Admin Login:\n";
    echo "  URL: http://localhost/X%20Business%20Grants/admin/login.php\n";
    echo "  Username: admin\n";
    echo "  Password: admin123\n\n";
    echo "Next steps:\n";
    echo "  1. Run the seeder: php database/seed.php\n";
    echo "  2. Access the site: http://localhost/X%20Business%20Grants/\n";
    
} catch (Exception $e) {
    echo "\n❌ ERROR: " . $e->getMessage() . "\n";
    echo "\nMake sure your database configuration is correct in includes/config.php\n";
    exit(1);
}
