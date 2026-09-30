<?php
/**
 * X Business Grant - Email Utility
 * Handles SMTP email sending for verification codes
 */

namespace App;

use App\Database;
use App\Settings;

class Email
{
    private $smtpHost;
    private $smtpPort;
    private $smtpUsername;
    private $smtpPassword;
    private $smtpEncryption;
    private $fromEmail;
    private $fromName;
    
    public function __construct()
    {
        $this->smtpHost = Settings::get('smtp_host', '');
        $this->smtpPort = Settings::get('smtp_port', '587');
        $this->smtpUsername = Settings::get('smtp_username', '');
        $this->smtpPassword = Settings::get('smtp_password', '');
        $this->smtpEncryption = Settings::get('smtp_encryption', 'tls');
        $this->fromEmail = Settings::get('smtp_from_email', 'noreply@xbusinessgrant.ng');
        $this->fromName = Settings::get('smtp_from_name', 'X Business Grant');
    }
    
    /**
     * Check if SMTP is configured
     */
    public function isConfigured()
    {
        return !empty($this->smtpHost) && !empty($this->smtpUsername) && !empty($this->smtpPassword);
    }
    
    /**
     * Generate a 6-digit verification code
     */
    public static function generateVerificationCode()
    {
        return str_pad((string)random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    }
    
    /**
     * Create email verification code for user
     */
    public function createVerificationCode($userId)
    {
        $db = Database::getInstance();
        
        // Delete any existing codes for this user
        $db->query("DELETE FROM email_verification_codes WHERE user_id = ?", [(int)$userId]);
        
        // Generate new code
        $code = self::generateVerificationCode();
        $expiresAt = date('Y-m-d H:i:s', strtotime('+30 minutes')); // 30 minutes expiry
        
        // Store in database
        $db->insert('email_verification_codes', [
            'user_id' => (int)$userId,
            'code' => $code,
            'expires_at' => $expiresAt,
            'created_at' => date('Y-m-d H:i:s')
        ]);
        
        // Debug log
        error_log("Created verification code for user $userId: $code (expires: $expiresAt)");
        
        return $code;
    }
    
    /**
     * Verify a code for a user
     */
    public function verifyCode($userId, $code)
    {
        $db = Database::getInstance();
        
        // Trim and ensure code is 6 digits
        $code = preg_replace('/[^0-9]/', '', trim($code));
        
        // Debug: log what we're searching for
        error_log("verifyCode looking for user_id=$userId, code=$code");
        
        // Check session backup first
        $sessionCode = $_SESSION['verification_code'] ?? null;
        $sessionUserId = $_SESSION['verification_user_id'] ?? null;
        $sessionExpires = $_SESSION['verification_expires'] ?? 0;
        
        error_log("Session check - stored user: $sessionUserId, stored code: $sessionCode, expires: $sessionExpires, current time: " . time());
        
        // First try database verification
        $result = $db->fetchOne(
            "SELECT * FROM email_verification_codes 
             WHERE user_id = ? AND code = ? AND verified_at IS NULL AND expires_at > NOW()",
            [(int)$userId, $code]
        );
        
        if ($result) {
            // Mark as verified
            $db->query(
                "UPDATE email_verification_codes SET verified_at = NOW() WHERE id = ?",
                [$result['id']]
            );
            
            // Update user's email_verified status
            $db->query(
                "UPDATE users SET email_verified = 1, updated_at = NOW() WHERE id = ?",
                [(int)$userId]
            );
            
            // Clear session
            unset($_SESSION['verification_code']);
            unset($_SESSION['verification_user_id']);
            unset($_SESSION['verification_expires']);
            
            return true;
        }
        
        // Fallback to session verification
        if ($sessionCode === $code && $sessionUserId == $userId && time() < $sessionExpires) {
            // Update user's email_verified status
            $db->query(
                "UPDATE users SET email_verified = 1, updated_at = NOW() WHERE id = ?",
                [(int)$userId]
            );
            
            // Clear session
            unset($_SESSION['verification_code']);
            unset($_SESSION['verification_user_id']);
            unset($_SESSION['verification_expires']);
            
            return true;
        }
        
        return false;
    }
    
    /**
     * Resend verification code
     */
    public function resendCode($userId)
    {
        // Delete existing codes
        $db = Database::getInstance();
        $db->query("DELETE FROM email_verification_codes WHERE user_id = ?", [(int)$userId]);
        
        // Create new code
        $code = $this->createVerificationCode($userId);
        
        // Update session backup
        $_SESSION['verification_code'] = $code;
        $_SESSION['verification_user_id'] = $userId;
        $_SESSION['verification_expires'] = time() + 1800; // 30 minutes
        
        return $code;
    }
    
    /**
     * Check if user has a valid pending code
     */
    public function hasPendingCode($userId)
    {
        $db = Database::getInstance();
        
        $result = $db->fetchOne(
            "SELECT * FROM email_verification_codes 
             WHERE user_id = ? AND verified_at IS NULL AND expires_at > NOW()",
            [(int)$userId]
        );
        
        return $result !== null;
    }
    
    /**
     * Get remaining time for current code (in minutes)
     */
    public function getCodeRemainingTime($userId)
    {
        $db = Database::getInstance();
        
        // Debug: log the query
        error_log("getCodeRemainingTime for user_id: $userId");
        
        $result = $db->fetchOne(
            "SELECT TIMESTAMPDIFF(MINUTE, NOW(), expires_at) as remaining_minutes, 
                    code, expires_at, verified_at 
             FROM email_verification_codes 
             WHERE user_id = ? AND verified_at IS NULL AND expires_at > NOW()",
            [(int)$userId]
        );
        
        // Debug: log result
        error_log("getCodeRemainingTime result: " . json_encode($result));
        
        if ($result) {
            return max(0, (int)$result['remaining_minutes']);
        }
        
        // Check session fallback
        $sessionExpires = $_SESSION['verification_expires'] ?? 0;
        if ($sessionExpires > time()) {
            return (int)ceil(($sessionExpires - time()) / 60);
        }
        
        return 0;
    }
    
    /**
     * Send verification email
     */
    public function sendVerificationEmail($userEmail, $userName, $code)
    {
        $subject = "Verify Your Email - X Business Grant";
        
        $message = "
        <div style=\"font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;\">
            <div style=\"background: linear-gradient(135deg, #1e40af 0%, #1e3a8a 100%); padding: 30px; text-align: center;\">
                <h1 style=\"color: white; margin: 0; font-size: 24px;\">X Business Grant</h1>
            </div>
            <div style=\"padding: 40px 30px; background: #ffffff;\">
                <h2 style=\"color: #1e40af; margin-top: 0;\">Email Verification</h2>
                <p>Hello {$userName},</p>
                <p>Thank you for registering with X Business Grant. Please use the verification code below to verify your email address:</p>
                <div style=\"background: #f3f4f6; padding: 20px; text-align: center; margin: 30px 0; border-radius: 8px;\">
                    <span style=\"font-size: 36px; font-weight: bold; letter-spacing: 8px; color: #1e40af;\">{$code}</span>
                </div>
                <p style=\"color: #6b7280; font-size: 14px;\">This code will expire in <strong>30 minutes</strong>.</p>
                <p>If you did not create an account with X Business Grant, please ignore this email.</p>
            </div>
            <div style=\"padding: 20px 30px; background: #f9fafb; text-align: center; color: #6b7280; font-size: 12px;\">
                <p>&copy; " . date('Y') . " X Business Grant. All rights reserved.</p>
            </div>
        </div>
        ";
        
        return $this->send($userEmail, $subject, $message);
    }
    
    /**
     * Send email using SMTP
     */
    public function send($to, $subject, $htmlBody)
    {
        if (!$this->isConfigured()) {
            return [
                'success' => false,
                'message' => 'SMTP is not configured'
            ];
        }
        
        // Simple email sending using PHP's built-in mail function as fallback
        // For production, consider using PHPMailer or similar library
        
        $headers = [
            'MIME-Version: 1.0',
            'Content-type: text/html; charset=UTF-8',
            'From: ' . $this->fromName . ' <' . $this->fromEmail . '>',
            'Reply-To: ' . $this->fromEmail,
            'X-Mailer: PHP/' . phpversion()
        ];
        
        // Try using SMTP directly via fsockopen for better reliability
        try {
            $result = $this->sendViaSMTP($to, $subject, $htmlBody);
            return $result;
        } catch (\Exception $e) {
            // Fallback to mail() function
            $result = @mail($to, $subject, $htmlBody, implode("\r\n", $headers));
            
            if ($result) {
                return [
                    'success' => true,
                    'message' => 'Email sent successfully'
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'Failed to send email: ' . $e->getMessage()
                ];
            }
        }
    }
    
    /**
     * Send email via SMTP directly using fsockopen
     */
    private function sendViaSMTP($to, $subject, $htmlBody)
    {
        $host = $this->smtpHost;
        $port = (int)$this->smtpPort;
        $username = $this->smtpUsername;
        $password = $this->smtpPassword;
        $encryption = strtolower($this->smtpEncryption);
        
        // Determine socket type
        $socketType = ($encryption === 'ssl') ? 'ssl://' : '';
        
        // Connect to SMTP server
        $context = stream_context_create([
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false
            ]
        ]);
        
        $socket = @stream_socket_client(
            $socketType . $host . ':' . $port,
            $errno,
            $errstr,
            30,
            STREAM_CLIENT_CONNECT,
            $context
        );
        
        if (!$socket) {
            throw new \Exception("Could not connect to SMTP server: $errstr ($errno)");
        }
        
        // Set read timeout
        stream_set_timeout($socket, 30);
        
        // Read greeting
        $response = $this->readResponse($socket);
        if (substr($response, 0, 3) !== '220') {
            fclose($socket);
            throw new \Exception("SMTP greeting failed: " . trim($response));
        }
        
        // Send EHLO
        $hello = $this->getEHLOHostname();
        fputs($socket, "EHLO $hello\r\n");
        $response = $this->readResponse($socket);
        
        // If we got a negative response, try HELO
        if (substr($response, 0, 3) !== '250') {
            fputs($socket, "HELO $hello\r\n");
            $response = $this->readResponse($socket);
        }
        
        // Start TLS if needed
        if ($encryption === 'tls') {
            fputs($socket, "STARTTLS\r\n");
            $response = $this->readResponse($socket);
            
            if (substr($response, 0, 3) !== '220') {
                fclose($socket);
                throw new \Exception("STARTTLS failed: " . trim($response));
            }
            
            // Enable crypto
            stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
            
            // Re-send EHLO after TLS
            fputs($socket, "EHLO $hello\r\n");
            $response = $this->readResponse($socket);
        }
        
        // Authenticate
        fputs($socket, "AUTH LOGIN\r\n");
        $response = $this->readResponse($socket);
        
        if (substr($response, 0, 3) !== '334') {
            fclose($socket);
            throw new \Exception("AUTH LOGIN failed: " . trim($response));
        }
        
        fputs($socket, base64_encode($username) . "\r\n");
        $response = $this->readResponse($socket);
        
        if (substr($response, 0, 3) !== '334') {
            fclose($socket);
            throw new \Exception("Username auth failed: " . trim($response));
        }
        
        fputs($socket, base64_encode($password) . "\r\n");
        $response = $this->readResponse($socket);
        
        if (substr($response, 0, 3) !== '235') {
            fclose($socket);
            throw new \Exception("Password auth failed: " . trim($response));
        }
        
        // Send MAIL FROM
        fputs($socket, "MAIL FROM: <{$this->fromEmail}>\r\n");
        $response = $this->readResponse($socket);
        
        if (substr($response, 0, 3) !== '250') {
            fclose($socket);
            throw new \Exception("MAIL FROM failed: " . trim($response));
        }
        
        // Send RCPT TO
        fputs($socket, "RCPT TO: <{$to}>\r\n");
        $response = $this->readResponse($socket);
        
        if (substr($response, 0, 3) !== '250') {
            fclose($socket);
            throw new \Exception("RCPT TO failed: " . trim($response));
        }
        
        // Send DATA
        fputs($socket, "DATA\r\n");
        $response = $this->readResponse($socket);
        
        if (substr($response, 0, 3) !== '354') {
            fclose($socket);
            throw new \Exception("DATA failed: " . trim($response));
        }
        
        // Build email headers
        $headers = [
            'MIME-Version: 1.0',
            'Content-Type: text/html; charset=UTF-8',
            'From: ' . $this->fromName . ' <' . $this->fromEmail . '>',
            'Reply-To: ' . $this->fromEmail,
            'X-Mailer: PHP/' . phpversion(),
            'To: ' . $to
        ];
        
        // Build and send headers
        fputs($socket, "Subject: $subject\r\n");
        foreach ($headers as $header) {
            fputs($socket, $header . "\r\n");
        }
        fputs($socket, "\r\n");
        
        // Send HTML body - normalize line endings
        $normalizedBody = str_replace(["\r\n", "\r", "\n"], "\r\n", $htmlBody);
        $bodyLines = explode("\r\n", $normalizedBody);
        
        // Send each line - escape dots at start of line (dot stuffing per RFC 5321)
        foreach ($bodyLines as $line) {
            // Escape lines starting with a period
            if (strlen($line) > 0 && substr($line, 0, 1) === '.') {
                $line = '.' . $line;
            }
            fputs($socket, $line . "\r\n");
        }
        
        // Send end of message indicator - CRLF then dot
        fputs($socket, "\r\n.\r\n");
        
        // Read response - should be 250 OK
        $response = fgets($socket, 515);
        
        // Check for 250 response (success)
        if (substr(trim($response), 0, 3) !== '250') {
            fclose($socket);
            throw new \Exception("Message send failed: " . trim($response));
        }
        
        // Quit
        fputs($socket, "QUIT\r\n");
        fgets($socket, 515);
        fclose($socket);
        
        return [
            'success' => true,
            'message' => 'Email sent successfully via SMTP'
        ];
    }
    
    /**
     * Read SMTP response, handling multi-line responses
     */
    private function readResponse($socket)
    {
        $response = '';
        while (true) {
            $line = fgets($socket, 515);
            if ($line === false) {
                break;
            }
            $response .= $line;
            // Check if this is the last line (no more '-' in the 4th character position)
            if (isset($line[3]) && $line[3] !== '-') {
                break;
            }
        }
        return $response;
    }
    
    /**
     * Get hostname for EHLO/HELO
     */
    private function getEHLOHostname()
    {
        if (isset($_SERVER['SERVER_NAME'])) {
            return $_SERVER['SERVER_NAME'];
        }
        return 'localhost';
    }
    
    /**
     * Test SMTP connection
     */
    public static function testSMTPConnection($host, $port, $username, $password, $encryption)
    {
        $encryption = strtolower($encryption);
        $socketType = ($encryption === 'ssl') ? 'ssl://' : '';
        
        $context = stream_context_create([
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false
            ]
        ]);
        
        $socket = @stream_socket_client(
            $socketType . $host . ':' . $port,
            $errno,
            $errstr,
            30,
            STREAM_CLIENT_CONNECT,
            $context
        );
        
        if (!$socket) {
            return [
                'success' => false,
                'message' => "Could not connect to SMTP server: $errstr ($errno)"
            ];
        }
        
        // Set read timeout
        stream_set_timeout($socket, 30);
        
        // Read greeting
        $response = self::readResponseStatic($socket);
        if (substr($response, 0, 3) !== '220') {
            fclose($socket);
            return [
                'success' => false,
                'message' => "SMTP greeting failed: " . trim($response)
            ];
        }
        
        // Send EHLO
        $hostname = isset($_SERVER['SERVER_NAME']) ? $_SERVER['SERVER_NAME'] : 'localhost';
        fputs($socket, "EHLO $hostname\r\n");
        $response = self::readResponseStatic($socket);
        
        if (substr($response, 0, 3) !== '250') {
            fclose($socket);
            return [
                'success' => false,
                'message' => "EHLO failed: " . trim($response)
            ];
        }
        
        // Start TLS if needed
        if ($encryption === 'tls') {
            fputs($socket, "STARTTLS\r\n");
            $response = self::readResponseStatic($socket);
            
            if (substr($response, 0, 3) !== '220') {
                fclose($socket);
                return [
                    'success' => false,
                    'message' => "STARTTLS failed: " . trim($response)
                ];
            }
            
            stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
            
            // Re-EHLO after TLS
            fputs($socket, "EHLO $hostname\r\n");
            $response = self::readResponseStatic($socket);
        }
        
        // Try to authenticate
        fputs($socket, "AUTH LOGIN\r\n");
        $response = self::readResponseStatic($socket);
        
        if (substr($response, 0, 3) !== '334') {
            fclose($socket);
            return [
                'success' => false,
                'message' => "AUTH LOGIN not supported: " . trim($response)
            ];
        }
        
        fputs($socket, base64_encode($username) . "\r\n");
        $response = self::readResponseStatic($socket);
        
        if (substr($response, 0, 3) !== '334') {
            fclose($socket);
            return [
                'success' => false,
                'message' => "Username rejected: " . trim($response)
            ];
        }
        
        fputs($socket, base64_encode($password) . "\r\n");
        $response = self::readResponseStatic($socket);
        
        fclose($socket);
        
        if (substr($response, 0, 3) !== '235') {
            return [
                'success' => false,
                'message' => "Authentication failed: " . trim($response)
            ];
        }
        
        return [
            'success' => true,
            'message' => 'SMTP connection successful! Authentication verified.'
        ];
    }
    
    /**
     * Static version of readResponse for use in static methods
     */
    private static function readResponseStatic($socket)
    {
        $response = '';
        while (true) {
            $line = fgets($socket, 515);
            if ($line === false) {
                break;
            }
            $response .= $line;
            // Check if this is the last line (no more '-' in the 4th character position)
            if (isset($line[3]) && $line[3] !== '-') {
                break;
            }
        }
        return $response;
    }
    
    /**
     * Send a test email to verify SMTP settings
     */
    public static function sendTestEmail($host, $port, $username, $password, $encryption, $fromEmail, $fromName, $testEmail)
    {
        $email = new self();
        
        // Temporarily set the SMTP settings for testing
        Settings::set('smtp_host', $host);
        Settings::set('smtp_port', $port);
        Settings::set('smtp_username', $username);
        Settings::set('smtp_password', $password);
        Settings::set('smtp_encryption', $encryption);
        Settings::set('smtp_from_email', $fromEmail);
        Settings::set('smtp_from_name', $fromName);
        
        // Clear cached settings by recreating the object
        $email = new self();
        
        $subject = "Test Email - X Business Grant";
        $htmlBody = "
        <div style=\"font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;\">
            <div style=\"background: linear-gradient(135deg, #1e40af 0%, #1e3a8a 100%); padding: 30px; text-align: center;\">
                <h1 style=\"color: white; margin: 0; font-size: 24px;\">X Business Grant</h1>
            </div>
            <div style=\"padding: 40px 30px; background: #ffffff;\">
                <h2 style=\"color: #1e40af; margin-top: 0;\">Test Email</h2>
                <p>This is a test email to verify your SMTP settings are configured correctly.</p>
                <p>If you received this email, your email settings are working properly!</p>
            </div>
            <div style=\"padding: 20px 30px; background: #f9fafb; text-align: center; color: #6b7280; font-size: 12px;\">
                <p>&copy; " . date('Y') . " X Business Grant. All rights reserved.</p>
            </div>
        </div>
        ";
        
        $result = $email->send($testEmail, $subject, $htmlBody);
        
        // Restore original settings
        Settings::clearCache();
        
        return $result;
    }
}
