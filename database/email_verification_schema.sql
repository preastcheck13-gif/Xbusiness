-- Email Verification Schema Update
-- Add email verification codes table and SMTP settings

-- Create email_verification_codes table
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert SMTP and email verification settings
INSERT INTO settings (setting_key, setting_value, description, created_at, updated_at) VALUES
('smtp_host', '', 'SMTP server hostname', NOW(), NOW()),
('smtp_port', '587', 'SMTP server port (587 for TLS, 465 for SSL)', NOW(), NOW()),
('smtp_username', '', 'SMTP username/email', NOW(), NOW()),
('smtp_password', '', 'SMTP password', NOW(), NOW()),
('smtp_encryption', 'tls', 'SMTP encryption type (tls, ssl, or none)', NOW(), NOW()),
('smtp_from_email', 'noreply@xbusinessgrant.ng', 'From email address for outgoing emails', NOW(), NOW()),
('smtp_from_name', 'X Business Grant', 'From name for outgoing emails', NOW(), NOW()),
('email_verification_enabled', '1', 'Enable email verification for new users (1 = enabled, 0 = disabled)', NOW(), NOW())
ON DUPLICATE KEY UPDATE updated_at = NOW();
