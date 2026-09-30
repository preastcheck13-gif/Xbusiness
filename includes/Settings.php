<?php
/**
 * X Business Grant - Settings Model
 * Handles application settings including Paystack API keys
 */

namespace App;

use App\Database;

class Settings
{
    private static $cache = null;
    
    /**
     * Get a setting value by key
     */
    public static function get($key, $default = null)
    {
        if (self::$cache === null) {
            self::loadSettings();
        }
        
        return self::$cache[$key] ?? $default;
    }
    
    /**
     * Set a setting value
     */
    public static function set($key, $value)
    {
        $db = Database::getInstance();
        
        // Check if setting exists
        $existing = $db->fetchOne(
            "SELECT id FROM settings WHERE setting_key = ?",
            [$key]
        );
        
        if ($existing) {
            // Update
            $db->query(
                "UPDATE settings SET setting_value = ?, updated_at = NOW() WHERE setting_key = ?",
                [$value, $key]
            );
        } else {
            // Insert
            $db->query(
                "INSERT INTO settings (setting_key, setting_value, created_at, updated_at) VALUES (?, ?, NOW(), NOW())",
                [$key, $value]
            );
        }
        
        // Clear cache
        self::$cache = null;
    }
    
    /**
     * Delete a setting
     */
    public static function delete($key)
    {
        $db = Database::getInstance();
        $db->query("DELETE FROM settings WHERE setting_key = ?", [$key]);
        self::$cache = null;
    }
    
    /**
     * Get all settings
     */
    public static function all()
    {
        if (self::$cache === null) {
            self::loadSettings();
        }
        return self::$cache;
    }
    
    /**
     * Load all settings into cache
     */
    private static function loadSettings()
    {
        $db = Database::getInstance();
        $rows = $db->fetchAll("SELECT setting_key, setting_value FROM settings");
        
        self::$cache = [];
        foreach ($rows as $row) {
            self::$cache[$row['setting_key']] = $row['setting_value'];
        }
    }
    
    /**
     * Clear cache
     */
    public static function clearCache()
    {
        self::$cache = null;
    }
    
    /**
     * Test Paystack API connection
     */
    public static function testPaystackConnection($secretKey)
    {
        $ch = curl_init();
        
        // Set CA certificate path for SSL verification
        $caCertPath = __DIR__ . '/../cacert.pem';
        $sslOptions = [
            CURLOPT_URL => "https://api.paystack.co/bank",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                "Authorization: Bearer " . $secretKey,
                "Content-Type: application/json"
            ],
            CURLOPT_TIMEOUT => 30,
            CURLOPT_SSL_VERIFYPEER => true
        ];
        
        // Use bundled CA certs if available, otherwise disable verification
        if (file_exists($caCertPath)) {
            $sslOptions[CURLOPT_CAINFO] = $caCertPath;
        } else {
            $sslOptions[CURLOPT_SSL_VERIFYPEER] = false;
            $sslOptions[CURLOPT_SSL_VERIFYHOST] = 0;
        }
        
        curl_setopt_array($ch, $sslOptions);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($error) {
            return [
                'success' => false,
                'message' => 'cURL Error: ' . $error
            ];
        }
        
        $data = json_decode($response, true);
        
        if ($httpCode === 200 && isset($data['status']) && $data['status'] === true) {
            return [
                'success' => true,
                'message' => 'Connection successful! Paystack API is working.',
                'bank_count' => count($data['data'] ?? [])
            ];
        } else {
            return [
                'success' => false,
                'message' => $data['message'] ?? 'Failed to connect to Paystack API',
                'http_code' => $httpCode
            ];
        }
    }
    
    /**
     * Get Paystack secret key
     */
    public static function getPaystackSecretKey()
    {
        return self::get('paystack_secret_key', '');
    }
    
    /**
     * Get Paystack public key
     */
    public static function getPaystackPublicKey()
    {
        return self::get('paystack_public_key', '');
    }
    
    /**
     * Set Paystack keys
     */
    public static function setPaystackKeys($secretKey, $publicKey = '')
    {
        self::set('paystack_secret_key', $secretKey);
        self::set('paystack_public_key', $publicKey);
    }
    
    /**
     * Check if Paystack is configured
     */
    public static function isPaystackConfigured()
    {
        $secretKey = self::getPaystackSecretKey();
        return !empty($secretKey);
    }
    
    // ==================== SMTP Settings ====================
    
    /**
     * Get SMTP host
     */
    public static function getSMTPHost()
    {
        return self::get('smtp_host', '');
    }
    
    /**
     * Get SMTP port
     */
    public static function getSMTPPort()
    {
        return self::get('smtp_port', '587');
    }
    
    /**
     * Get SMTP username
     */
    public static function getSMTPUsername()
    {
        return self::get('smtp_username', '');
    }
    
    /**
     * Get SMTP password
     */
    public static function getSMTPPassword()
    {
        return self::get('smtp_password', '');
    }
    
    /**
     * Get SMTP encryption type
     */
    public static function getSMTPEncryption()
    {
        return self::get('smtp_encryption', 'tls');
    }
    
    /**
     * Get SMTP from email
     */
    public static function getSMTPFromEmail()
    {
        return self::get('smtp_from_email', 'noreply@xbusinessgrant.ng');
    }
    
    /**
     * Get SMTP from name
     */
    public static function getSMTPFromName()
    {
        return self::get('smtp_from_name', 'X Business Grant');
    }
    
    /**
     * Check if SMTP is configured
     */
    public static function isSMTPConfigured()
    {
        $host = self::getSMTPHost();
        $username = self::getSMTPUsername();
        $password = self::getSMTPPassword();
        return !empty($host) && !empty($username) && !empty($password);
    }
    
    // ==================== Email Verification Settings ====================
    
    /**
     * Check if email verification is enabled
     */
    public static function isEmailVerificationEnabled()
    {
        $value = self::get('email_verification_enabled', '1');
        return $value === '1' || $value === 'true' || $value === true;
    }
    
    /**
     * Set email verification enabled status
     */
    public static function setEmailVerificationEnabled($enabled)
    {
        self::set('email_verification_enabled', $enabled ? '1' : '0');
    }
    
    /**
     * Set SMTP settings
     */
    public static function setSMTPSettings($host, $port, $username, $password, $encryption, $fromEmail, $fromName)
    {
        self::set('smtp_host', $host);
        self::set('smtp_port', $port);
        self::set('smtp_username', $username);
        self::set('smtp_password', $password);
        self::set('smtp_encryption', $encryption);
        self::set('smtp_from_email', $fromEmail);
        self::set('smtp_from_name', $fromName);
    }
    
    // ==================== WhatsApp Settings ====================
    
    /**
     * Get WhatsApp admin number
     */
    public static function getWhatsAppAdminNumber()
    {
        return self::get('whatsapp_admin_number', '');
    }
    
    /**
     * Set WhatsApp admin number
     */
    public static function setWhatsAppAdminNumber($number)
    {
        // Remove any non-numeric characters except leading +
        $cleanNumber = preg_replace('/[^\d+]/', '', $number);
        self::set('whatsapp_admin_number', $cleanNumber);
    }
    
    /**
     * Check if WhatsApp is configured
     */
    public static function isWhatsAppConfigured()
    {
        $number = self::getWhatsAppAdminNumber();
        return !empty($number) && strlen(preg_replace('/\D/', '', $number)) >= 10;
    }
    
    /**
     * Format phone number for WhatsApp (international format without + sign)
     */
    public static function formatPhoneForWhatsApp($phone)
    {
        // Remove all non-digits
        $digits = preg_replace('/\D/', '', $phone);
        
        // If starts with country code (234 for Nigeria, 1 for US, etc.)
        if (strlen($digits) > 10) {
            // Already has country code, remove leading 0 if present
            if (substr($digits, 0, 1) === '0') {
                $digits = substr($digits, 1);
            }
        } elseif (strlen($digits) == 10) {
            // Nigerian number without country code - add 234
            $digits = '234' . $digits;
        }
        
        return $digits;
    }
    
    /**
     * Generate WhatsApp click-to-chat URL
     */
    public static function getWhatsAppLink($phone, $message = '')
    {
        $formattedPhone = self::formatPhoneForWhatsApp($phone);
        $url = 'https://wa.me/' . $formattedPhone;
        
        if (!empty($message)) {
            $url .= '?text=' . urlencode($message);
        }
        
        return $url;
    }
}
