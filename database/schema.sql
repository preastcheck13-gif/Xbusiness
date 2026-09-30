-- X Business Grant Database Schema
-- Nigerian Business Grant Application Portal

-- Create database
CREATE DATABASE IF NOT EXISTS xbusiness_grants CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE xbusiness_grants;

-- Applications table
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
    -- Bank account details
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Documents table
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Users table (for applicants)
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Admin users table
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Application status history table
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Settings table for storing key-value configuration
CREATE TABLE IF NOT EXISTS settings (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) NOT NULL UNIQUE,
    setting_value TEXT,
    description VARCHAR(255),
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    INDEX idx_key (setting_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- User activity log for tracking user actions
CREATE TABLE IF NOT EXISTS activity_log (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED,
    user_type ENUM('user', 'admin') DEFAULT 'user',
    action VARCHAR(100) NOT NULL,
    description TEXT,
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at DATETIME NOT NULL,
    INDEX idx_user (user_id),
    INDEX idx_action (action),
    INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert default admin user (password: admin123)
INSERT INTO admin_users (username, email, password_hash, full_name, role) VALUES
('admin', 'admin@xbusinessgrant.ng', '$2y$10$8K1p/a0dR1xqM8K3hQv0aOQZQZQZQZQZQZQZQZQZQZQZQZQZQZQZQ', 'System Administrator', 'super_admin');

-- Insert default settings
INSERT INTO settings (setting_key, setting_value, description, created_at, updated_at) VALUES
('paystack_secret_key', '', 'Paystack Secret Key for API calls', NOW(), NOW()),
('paystack_public_key', '', 'Paystack Public Key for frontend', NOW(), NOW()),
('site_name', 'X Business Grant', 'Website name', NOW(), NOW()),
('site_email', 'support@xbusinessgrant.ng', 'Support email address', NOW(), NOW()),
('grant_min_amount', '100000', 'Minimum grant amount in Naira', NOW(), NOW()),
('grant_max_amount', '5000000', 'Maximum grant amount in Naira', NOW(), NOW());

-- Sample data for testing (optional)
-- INSERT INTO applications (reference_no, business_name, cac_number, business_type, business_sector, business_address, city, state, phone, email, years_in_business, employee_count, grant_amount_requested, purpose_of_grant, business_description, status, created_at, updated_at) VALUES
-- ('XBG-2026-A1B2C3D4', 'TechStart Nigeria Ltd', 'RC123456', 'Private Limited Company', 'Technology & IT', '15 Admiralty Way, Lekki Phase 1', 'Lagos', 'Lagos', '08012345678', 'info@techstart.ng', 3, 15, 2500000, 'Purchase of equipment and hiring of developers', 'TechStart Nigeria provides web and mobile development services to SMEs across Nigeria', 'pending', NOW(), NOW());
