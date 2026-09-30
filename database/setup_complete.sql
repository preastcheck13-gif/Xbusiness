-- ============================================================================
-- X Business Grant - Complete Database Setup
-- Nigerian Business Grant Application Portal
-- Version: 1.0.0
-- ============================================================================
-- 
-- USAGE:
--   mysql -u root -p < database/setup_complete.sql
--   Or import through phpMyAdmin
--
-- ============================================================================

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";

-- ============================================================================
-- CREATE DATABASE
-- ============================================================================
CREATE DATABASE IF NOT EXISTS `xbusiness_grants` 
    DEFAULT CHARACTER SET utf8mb4 
    COLLATE utf8mb4_unicode_ci;

USE `xbusiness_grants`;

-- ============================================================================
-- TABLE: users (Applicant Users)
-- ============================================================================
CREATE TABLE IF NOT EXISTS `users` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `full_name` VARCHAR(255) NOT NULL,
    `email` VARCHAR(255) NOT NULL,
    `phone` VARCHAR(20) NOT NULL,
    `business_registration_number` VARCHAR(100) DEFAULT NULL,
    `password_hash` VARCHAR(255) NOT NULL,
    `email_verified` TINYINT(1) NOT NULL DEFAULT 0,
    `last_login` DATETIME DEFAULT NULL,
    `created_at` DATETIME NOT NULL,
    `updated_at` DATETIME NOT NULL,
    UNIQUE KEY `idx_email` (`email`),
    INDEX `idx_phone` (`phone`),
    INDEX `idx_business_reg` (`business_registration_number`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- TABLE: admin_users (Administrator Users)
-- ============================================================================
CREATE TABLE IF NOT EXISTS `admin_users` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `username` VARCHAR(100) NOT NULL,
    `email` VARCHAR(255) NOT NULL,
    `password_hash` VARCHAR(255) NOT NULL,
    `full_name` VARCHAR(255) NOT NULL,
    `role` ENUM('admin', 'reviewer', 'super_admin') NOT NULL DEFAULT 'reviewer',
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `last_login` DATETIME DEFAULT NULL,
    `created_at` DATETIME NOT NULL,
    `updated_at` DATETIME NOT NULL,
    UNIQUE KEY `idx_username` (`username`),
    UNIQUE KEY `idx_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- TABLE: applications (Grant Applications)
-- ============================================================================
CREATE TABLE IF NOT EXISTS `applications` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id` INT UNSIGNED DEFAULT NULL,
    `reference_no` VARCHAR(50) NOT NULL,
    `business_name` VARCHAR(255) NOT NULL,
    `cac_number` VARCHAR(50) NOT NULL,
    `business_type` VARCHAR(100) NOT NULL,
    `business_sector` VARCHAR(100) NOT NULL,
    `business_address` TEXT NOT NULL,
    `city` VARCHAR(100) NOT NULL,
    `state` VARCHAR(100) NOT NULL,
    `lga` VARCHAR(100) DEFAULT NULL,
    `phone` VARCHAR(20) NOT NULL,
    `email` VARCHAR(255) NOT NULL,
    `website` VARCHAR(255) DEFAULT NULL,
    `years_in_business` INT NOT NULL,
    `employee_count` INT NOT NULL,
    `grant_amount_requested` DECIMAL(15,2) NOT NULL,
    `purpose_of_grant` TEXT NOT NULL,
    `business_description` TEXT NOT NULL,
    `status` ENUM('pending', 'under_review', 'approved', 'rejected') NOT NULL DEFAULT 'pending',
    `admin_notes` TEXT DEFAULT NULL,
    `bank_name` VARCHAR(100) DEFAULT NULL,
    `bank_code` VARCHAR(10) DEFAULT NULL,
    `bank_account_number` VARCHAR(10) DEFAULT NULL,
    `verified_account_name` VARCHAR(255) DEFAULT NULL,
    `bank_verified` TINYINT(1) NOT NULL DEFAULT 0,
    `owner_name` VARCHAR(255) DEFAULT NULL,
    `date_of_birth` DATE DEFAULT NULL,
    `bvn` VARCHAR(11) DEFAULT NULL,
    `business_registration_number` VARCHAR(100) DEFAULT NULL,
    `created_at` DATETIME NOT NULL,
    `updated_at` DATETIME NOT NULL,
    UNIQUE KEY `idx_reference` (`reference_no`),
    INDEX `idx_user_id` (`user_id`),
    INDEX `idx_cac` (`cac_number`),
    INDEX `idx_status` (`status`),
    INDEX `idx_state` (`state`),
    INDEX `idx_sector` (`business_sector`),
    INDEX `idx_created` (`created_at`),
    CONSTRAINT `fk_applications_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- TABLE: documents (Uploaded Documents)
-- ============================================================================
CREATE TABLE IF NOT EXISTS `documents` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `application_id` INT UNSIGNED NOT NULL,
    `document_type` VARCHAR(50) NOT NULL,
    `file_path` VARCHAR(500) NOT NULL,
    `original_name` VARCHAR(255) NOT NULL,
    `created_at` DATETIME NOT NULL,
    INDEX `idx_app_id` (`application_id`),
    INDEX `idx_doc_type` (`document_type`),
    CONSTRAINT `fk_documents_application` FOREIGN KEY (`application_id`) REFERENCES `applications` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- TABLE: status_history (Application Status Changes)
-- ============================================================================
CREATE TABLE IF NOT EXISTS `status_history` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `application_id` INT UNSIGNED NOT NULL,
    `old_status` VARCHAR(20) DEFAULT NULL,
    `new_status` VARCHAR(20) NOT NULL,
    `changed_by` INT UNSIGNED DEFAULT NULL,
    `notes` TEXT DEFAULT NULL,
    `created_at` DATETIME NOT NULL,
    INDEX `idx_app_id` (`application_id`),
    CONSTRAINT `fk_status_history_application` FOREIGN KEY (`application_id`) REFERENCES `applications` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_status_history_admin` FOREIGN KEY (`changed_by`) REFERENCES `admin_users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- TABLE: settings (Key-Value Configuration)
-- ============================================================================
CREATE TABLE IF NOT EXISTS `settings` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `setting_key` VARCHAR(100) NOT NULL,
    `setting_value` TEXT DEFAULT NULL,
    `description` VARCHAR(255) DEFAULT NULL,
    `created_at` DATETIME NOT NULL,
    `updated_at` DATETIME NOT NULL,
    UNIQUE KEY `idx_setting_key` (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- TABLE: activity_log (User Activity Tracking)
-- ============================================================================
CREATE TABLE IF NOT EXISTS `activity_log` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id` INT UNSIGNED DEFAULT NULL,
    `user_type` ENUM('user', 'admin') NOT NULL DEFAULT 'user',
    `action` VARCHAR(100) NOT NULL,
    `description` TEXT DEFAULT NULL,
    `ip_address` VARCHAR(45) DEFAULT NULL,
    `user_agent` TEXT DEFAULT NULL,
    `created_at` DATETIME NOT NULL,
    INDEX `idx_user` (`user_id`),
    INDEX `idx_action` (`action`),
    INDEX `idx_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- TABLE: email_verification_codes (Email Verification)
-- ============================================================================
CREATE TABLE IF NOT EXISTS `email_verification_codes` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id` INT UNSIGNED NOT NULL,
    `code` VARCHAR(6) NOT NULL,
    `expires_at` DATETIME NOT NULL,
    `verified_at` DATETIME DEFAULT NULL,
    `created_at` DATETIME NOT NULL,
    INDEX `idx_user_id` (`user_id`),
    INDEX `idx_code` (`code`),
    INDEX `idx_expires` (`expires_at`),
    CONSTRAINT `fk_verification_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- TABLE: email_templates (Email Template Storage)
-- ============================================================================
CREATE TABLE IF NOT EXISTS `email_templates` (
    `id` INT NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(255) NOT NULL,
    `subject` VARCHAR(500) NOT NULL,
    `body` TEXT NOT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- TABLE: email_logs (Email Sending Logs)
-- ============================================================================
CREATE TABLE IF NOT EXISTS `email_logs` (
    `id` INT NOT NULL AUTO_INCREMENT,
    `template_id` INT DEFAULT NULL,
    `recipient_email` VARCHAR(255) NOT NULL,
    `recipient_name` VARCHAR(255) DEFAULT NULL,
    `subject` VARCHAR(500) NOT NULL,
    `body` TEXT NOT NULL,
    `status` ENUM('pending', 'sent', 'failed') NOT NULL DEFAULT 'pending',
    `error_message` TEXT DEFAULT NULL,
    `sent_at` DATETIME DEFAULT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_template_id` (`template_id`),
    INDEX `idx_recipient` (`recipient_email`),
    INDEX `idx_status` (`status`),
    CONSTRAINT `fk_email_logs_template` FOREIGN KEY (`template_id`) REFERENCES `email_templates` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- DEFAULT ADMIN USER
-- Password: admin123 (change immediately after first login)
-- ============================================================================
INSERT INTO `admin_users` (`username`, `email`, `password_hash`, `full_name`, `role`, `is_active`, `created_at`, `updated_at`) VALUES
('admin', 'admin@xbusinessgrant.ng', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'System Administrator', 'super_admin', 1, NOW(), NOW());

-- ============================================================================
-- DEFAULT SETTINGS
-- ============================================================================
INSERT INTO `settings` (`setting_key`, `setting_value`, `description`, `created_at`, `updated_at`) VALUES
('site_name', 'X Business Grant', 'Website name', NOW(), NOW()),
('site_email', 'support@xbusinessgrant.ng', 'Support email address', NOW(), NOW()),
('site_url', 'http://localhost/X Business Grants', 'Website URL', NOW(), NOW()),
('grant_min_amount', '100000', 'Minimum grant amount in Naira', NOW(), NOW()),
('grant_max_amount', '5000000', 'Maximum grant amount in Naira', NOW(), NOW()),
('paystack_secret_key', '', 'Paystack Secret Key for API calls', NOW(), NOW()),
('paystack_public_key', '', 'Paystack Public Key for frontend', NOW(), NOW()),
('smtp_host', '', 'SMTP server hostname', NOW(), NOW()),
('smtp_port', '587', 'SMTP server port (587 for TLS, 465 for SSL)', NOW(), NOW()),
('smtp_username', '', 'SMTP username/email', NOW(), NOW()),
('smtp_password', '', 'SMTP password', NOW(), NOW()),
('smtp_encryption', 'tls', 'SMTP encryption type (tls, ssl, or none)', NOW(), NOW()),
('smtp_from_email', 'noreply@xbusinessgrant.ng', 'From email address for outgoing emails', NOW(), NOW()),
('smtp_from_name', 'X Business Grant', 'From name for outgoing emails', NOW(), NOW()),
('email_verification_enabled', '1', 'Enable email verification for new users (1=enabled, 0=disabled)', NOW(), NOW()),
('application_open', '1', 'Allow new applications (1=open, 0=closed)', NOW(), NOW()),
('maintenance_mode', '0', 'Enable maintenance mode (1=enabled, 0=disabled)', NOW(), NOW());

-- ============================================================================
-- DEFAULT EMAIL TEMPLATES
-- ============================================================================
INSERT INTO `email_templates` (`name`, `subject`, `body`) VALUES
('Welcome Email', 'Welcome to X Business Grant!', '<div style=\"font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;\"><div style=\"background: linear-gradient(135deg, #1e40af 0%, #1e3a8a 100%); padding: 30px; text-align: center;\"><h1 style=\"color: white; margin: 0; font-size: 24px;\">X Business Grant</h1></div><div style=\"padding: 40px 30px; background: #ffffff;\"><h2 style=\"color: #1e40af; margin-top: 0;\">Welcome, {{name}}!</h2><p>Thank you for registering with X Business Grant. We are excited to have you on board!</p><p>With X Business Grant, you can:</p><ul><li>Apply for business grants up to ₦5,000,000</li><li>Track your application status online</li><li>Get expert guidance on your business growth</li></ul><p>Get started by completing your profile and submitting your first grant application.</p><p style=\"margin-top: 30px;\">Best regards,<br>The X Business Grant Team</p></div><div style=\"padding: 20px 30px; background: #f9fafb; text-align: center; color: #6b7280; font-size: 12px;\"><p>© {{year}} X Business Grant. All rights reserved.</p></div></div>'),
('Application Received', 'We Received Your Grant Application - {{reference}}', '<div style=\"font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;\"><div style=\"background: linear-gradient(135deg, #1e40af 0%, #1e3a8a 100%); padding: 30px; text-align: center;\"><h1 style=\"color: white; margin: 0; font-size: 24px;\">X Business Grant</h1></div><div style=\"padding: 40px 30px; background: #ffffff;\"><h2 style=\"color: #1e40af; margin-top: 0;\">Application Received!</h2><p>Dear {{name}},</p><p>We have received your grant application. Your application reference number is: <strong>{{reference}}</strong></p><p>Our team will review your application and get back to you within 5-7 business days.</p><p>You can track your application status using the reference number above.</p><p style=\"margin-top: 30px;\">Best regards,<br>The X Business Grant Team</p></div><div style=\"padding: 20px 30px; background: #f9fafb; text-align: center; color: #6b7280; font-size: 12px;\"><p>© {{year}} X Business Grant. All rights reserved.</p></div></div>'),
('Application Approved', 'Congratulations! Your Grant is Approved!', '<div style=\"font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;\"><div style=\"background: linear-gradient(135deg, #059669 0%, #047857 100%); padding: 30px; text-align: center;\"><h1 style=\"color: white; margin: 0; font-size: 24px;\">X Business Grant - Approved!</h1></div><div style=\"padding: 40px 30px; background: #ffffff;\"><h2 style=\"color: #059669; margin-top: 0;\">Congratulations, {{name}}!</h2><p>We are thrilled to inform you that your grant application (Reference: <strong>{{reference}}</strong>) has been approved!</p><div style=\"background: #ecfdf5; padding: 20px; border-radius: 8px; margin: 20px 0;\"><p style=\"margin: 0;\"><strong>Approved Amount:</strong> {{amount}}</p></div><p>Our team will contact you shortly with details on how to receive your grant funds.</p><p style=\"margin-top: 30px;\">Best regards,<br>The X Business Grant Team</p></div><div style=\"padding: 20px 30px; background: #f9fafb; text-align: center; color: #6b7280; font-size: 12px;\"><p>© {{year}} X Business Grant. All rights reserved.</p></div></div>'),
('Application Rejected', 'Update on Your Grant Application', '<div style=\"font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;\"><div style=\"background: linear-gradient(135deg, #dc2626 0%, #b91c1c 100%); padding: 30px; text-align: center;\"><h1 style=\"color: white; margin: 0; font-size: 24px;\">X Business Grant</h1></div><div style=\"padding: 40px 30px; background: #ffffff;\"><h2 style=\"color: #dc2626; margin-top: 0;\">Application Update</h2><p>Dear {{name}},</p><p>Thank you for your interest in X Business Grant. After careful review of your application (Reference: <strong>{{reference}}</strong>), we regret to inform you that we are unable to approve your grant request at this time.</p><p>This decision does not reflect on you personally. We encourage you to:</p><ul><li>Review your business plan and strengthen your proposal</li><li>Build your credit history</li><li>Gain more business experience</li></ul><p>You are welcome to reapply in the future. We wish you all the best in your business journey.</p><p style=\"margin-top: 30px;\">Best regards,<br>The X Business Grant Team</p></div><div style=\"padding: 20px 30px; background: #f9fafb; text-align: center; color: #6b7280; font-size: 12px;\"><p>© {{year}} X Business Grant. All rights reserved.</p></div></div>'),
('Application Under Review', 'Your Application is Under Review - {{reference}}', '<div style=\"font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;\"><div style=\"background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%); padding: 30px; text-align: center;\"><h1 style=\"color: white; margin: 0; font-size: 24px;\">X Business Grant - Under Review</h1></div><div style=\"padding: 40px 30px; background: #ffffff;\"><h2 style=\"color: #f59e0b; margin-top: 0;\">Application Under Review</h2><p>Dear {{name}},</p><p>We wanted to let you know that your grant application (Reference: <strong>{{reference}}</strong>) is now under review by our team.</p><p>This is great news! Our review team is carefully evaluating all applications to ensure fair and thorough assessment.</p><p>Expected timeline: 5-7 business days</p><p>You will receive an email notification once the review is complete.</p><p style=\"margin-top: 30px;\">Best regards,<br>The X Business Grant Team</p></div><div style=\"padding: 20px 30px; background: #f9fafb; text-align: center; color: #6b7280; font-size: 12px;\"><p>© {{year}} X Business Grant. All rights reserved.</p></div></div>'),
('Account Verification', 'Please Verify Your Account', '<div style=\"font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;\"><div style=\"background: linear-gradient(135deg, #1e40af 0%, #1e3a8a 100%); padding: 30px; text-align: center;\"><h1 style=\"color: white; margin: 0; font-size: 24px;\">X Business Grant</h1></div><div style=\"padding: 40px 30px; background: #ffffff;\"><h2 style=\"color: #1e40af; margin-top: 0;\">Account Verification Required</h2><p>Dear {{name}},</p><p>Thank you for registering with X Business Grant!</p><p>To ensure the security of your account and comply with regulations, we need to verify your identity.</p><div style=\"background: #f3f4f6; padding: 20px; border-radius: 8px; margin: 20px 0; text-align: center;\"><p style=\"font-size: 18px; margin: 0;\">Your verification code:</p><p style=\"font-size: 28px; font-weight: bold; color: #1e40af; margin: 10px 0;\">{{code}}</p><p style=\"font-size: 12px; color: #6b7280; margin: 0;\">This code expires in 30 minutes</p></div><p>Enter this code on the verification page to activate your account.</p><p style=\"margin-top: 30px;\">Best regards,<br>The X Business Grant Team</p></div><div style=\"padding: 20px 30px; background: #f9fafb; text-align: center; color: #6b7280; font-size: 12px;\"><p>© {{year}} X Business Grant. All rights reserved.</p></div></div>'),
('Password Reset', 'Reset Your Password - X Business Grant', '<div style=\"font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;\"><div style=\"background: linear-gradient(135deg, #1e40af 0%, #1e3a8a 100%); padding: 30px; text-align: center;\"><h1 style=\"color: white; margin: 0; font-size: 24px;\">X Business Grant</h1></div><div style=\"padding: 40px 30px; background: #ffffff;\"><h2 style=\"color: #1e40af; margin-top: 0;\">Password Reset Request</h2><p>Dear {{name}},</p><p>We received a request to reset your password. Click the button below to set a new password:</p><div style=\"text-align: center; margin: 30px 0;\"><a href=\"{{reset_link}}\" style=\"display: inline-block; background: #1e40af; color: white; padding: 12px 30px; text-decoration: none; border-radius: 8px; font-weight: bold;\">Reset Password</a></div><p>Or copy this link: {{reset_link}}</p><p style=\"color: #dc2626;\"><strong>Note:</strong> This link expires in 1 hour. If you did not request a password reset, please ignore this email.</p><p style=\"margin-top: 30px;\">Best regards,<br>The X Business Grant Team</p></div><div style=\"padding: 20px 30px; background: #f9fafb; text-align: center; color: #6b7280; font-size: 12px;\"><p>© {{year}} X Business Grant. All rights reserved.</p></div></div>'),
('Document Request', 'Additional Documents Required - {{reference}}', '<div style=\"font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;\"><div style=\"background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%); padding: 30px; text-align: center;\"><h1 style=\"color: white; margin: 0; font-size: 24px;\">X Business Grant</h1></div><div style=\"padding: 40px 30px; background: #ffffff;\"><h2 style=\"color: #f59e0b; margin-top: 0;\">Additional Documents Required</h2><p>Dear {{name}},</p><p>We are reviewing your grant application (Reference: <strong>{{reference}}</strong>) and need some additional documents to complete the process.</p><p>Please upload the following documents:</p><ul>{{message}}</ul><p>Once we receive these documents, we will continue with the review process.</p><p style=\"margin-top: 30px;\">Best regards,<br>The X Business Grant Team</p></div><div style=\"padding: 20px 30px; background: #f9fafb; text-align: center; color: #6b7280; font-size: 12px;\"><p>© {{year}} X Business Grant. All rights reserved.</p></div></div>'),
('Payment Processing', 'Your Grant Payment is Being Processed', '<div style=\"font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;\"><div style=\"background: linear-gradient(135deg, #059669 0%, #047857 100%); padding: 30px; text-align: center;\"><h1 style=\"color: white; margin: 0; font-size: 24px;\">X Business Grant - Payment</h1></div><div style=\"padding: 40px 30px; background: #ffffff;\"><h2 style=\"color: #059669; margin-top: 0;\">Payment Processing</h2><p>Dear {{name}},</p><p>Great news! Your grant payment for application <strong>{{reference}}</strong> is now being processed.</p><div style=\"background: #ecfdf5; padding: 20px; border-radius: 8px; margin: 20px 0;\"><p style=\"margin: 0;\"><strong>Amount:</strong> {{amount}}</p><p style=\"margin: 10px 0 0 0;\"><strong>Status:</strong> Payment Processing</p></div><p>Please allow 3-5 business days for the funds to reflect in your account.</p><p>If you have any questions, please contact our support team.</p><p style=\"margin-top: 30px;\">Best regards,<br>The X Business Grant Team</p></div><div style=\"padding: 20px 30px; background: #f9fafb; text-align: center; color: #6b7280; font-size: 12px;\"><p>© {{year}} X Business Grant. All rights reserved.</p></div></div>'),
('Payment Completed', 'Grant Payment Completed Successfully!', '<div style=\"font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;\"><div style=\"background: linear-gradient(135deg, #059669 0%, #047857 100%); padding: 30px; text-align: center;\"><h1 style=\"color: white; margin: 0; font-size: 24px;\">X Business Grant - Payment Complete</h1></div><div style=\"padding: 40px 30px; background: #ffffff;\"><h2 style=\"color: #059669; margin-top: 0;\">Payment Complete!</h2><p>Dear {{name}},</p><p>We are pleased to confirm that your grant payment has been successfully transferred!</p><div style=\"background: #ecfdf5; padding: 20px; border-radius: 8px; margin: 20px 0;\"><p style=\"margin: 0;\"><strong>Reference:</strong> {{reference}}</p><p style=\"margin: 10px 0 0 0;\"><strong>Amount Received:</strong> {{amount}}</p><p style=\"margin: 10px 0 0 0;\"><strong>Status:</strong> Completed</p></div><p>Congratulations on receiving your grant! We encourage you to use these funds wisely to grow your business.</p><p style=\"margin-top: 30px;\">Best regards,<br>The X Business Grant Team</p></div><div style=\"padding: 20px 30px; background: #f9fafb; text-align: center; color: #6b7280; font-size: 12px;\"><p>© {{year}} X Business Grant. All rights reserved.</p></div></div>');

-- ============================================================================
-- SUMMARY
-- ============================================================================
SELECT 'Database setup completed successfully!' AS status;
SELECT 'Tables created:' AS info;
SHOW TABLES;
SELECT 'Admin user: admin / admin123' AS credentials;
