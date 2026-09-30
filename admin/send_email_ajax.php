<?php
/**
 * AJAX handler for sending emails
 */

session_start();

require_once __DIR__ . '/../includes/Database.php';
require_once __DIR__ . '/../includes/Settings.php';
require_once __DIR__ . '/../includes/Email.php';

header('Content-Type: application/json');

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

// Clear settings cache to ensure fresh data
\App\Settings::clearCache();

// Get JSON input
$rawInput = file_get_contents('php://input');
$data = json_decode($rawInput, true);

if (!$data) {
    // Try to parse as form data
    $data = $_POST;
}

if (empty($data['to']) || empty($data['subject']) || empty($data['body'])) {
    logEmailResult($data['to'] ?? null, $data['name'] ?? null, $data['subject'] ?? 'No Subject', $data['body'] ?? '', 'failed', 'Missing required fields: to, subject, or body');
    echo json_encode(['success' => false, 'message' => 'Missing required fields: to, subject, or body']);
    exit;
}

$toEmail = $data['to'];
$toName = $data['name'] ?? '';
$subject = $data['subject'];
$body = $data['body'];

error_log("=== EMAIL SEND DEBUG ===");
error_log("To: $toEmail");
error_log("Subject: $subject");
error_log("Body length: " . strlen($body));

try {
    // Debug: Check if Email class can be loaded
    error_log("Creating Email instance...");
    $email = new \App\Email();
    error_log("Email instance created");
    
    // Debug: Check SMTP settings directly
    $smtpHost = \App\Settings::getSMTPHost();
    $smtpUsername = \App\Settings::getSMTPUsername();
    $smtpPassword = \App\Settings::getSMTPPassword();
    error_log("SMTP Host from settings: '$smtpHost'");
    error_log("SMTP Username from settings: '$smtpUsername'");
    error_log("SMTP Password length from settings: " . strlen($smtpPassword));
    
    // Check if SMTP is configured
    $isConfigured = $email->isConfigured();
    error_log("isConfigured() returned: " . ($isConfigured ? 'true' : 'false'));
    
    if (!$isConfigured) {
        error_log("SMTP not configured - failing");
        logEmailResult($toEmail, $toName, $subject, $body, 'failed', 'SMTP not configured. Please configure SMTP settings in admin panel.');
        
        echo json_encode([
            'success' => false,
            'message' => 'SMTP not configured. Please go to SMTP Settings and configure your email provider.'
        ]);
        exit;
    }

    error_log("SMTP is configured, attempting to send...");
    
    // Try to send the email
    $result = $email->send($toEmail, $subject, $body);

    error_log("Send result: " . json_encode($result));

    // Log to database regardless of result
    $errorMessage = $result['success'] ? null : ($result['message'] ?? 'Unknown error');
    $status = $result['success'] ? 'sent' : 'failed';

    logEmailResult($toEmail, $toName, $subject, $body, $status, $errorMessage);

    // Return result
    if ($result['success']) {
        echo json_encode([
            'success' => true,
            'message' => 'Email sent successfully to ' . $toEmail
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Failed to send email: ' . ($result['message'] ?? 'Unknown error')
        ]);
    }
} catch (Exception $e) {
    // Log the error
    error_log("Email send exception: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    logEmailResult($toEmail, $toName, $subject, $body, 'failed', 'Exception: ' . $e->getMessage());
    
    echo json_encode([
        'success' => false,
        'message' => 'Error sending email: ' . $e->getMessage()
    ]);
}
error_log("=== END EMAIL SEND DEBUG ===");

/**
 * Helper function to log email results to database
 */
function logEmailResult($toEmail, $toName, $subject, $body, $status, $errorMessage) {
    try {
        $db = \App\Database::getInstance();
        
        // Ensure email_logs table exists
        $tables = $db->fetchAll("SHOW TABLES LIKE 'email_logs'");
        if (empty($tables)) {
            // Create the table if it doesn't exist
            $db->query("
                CREATE TABLE IF NOT EXISTS email_logs (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    template_id INT NULL,
                    recipient_email VARCHAR(255) NOT NULL,
                    recipient_name VARCHAR(255) NULL,
                    subject VARCHAR(500) NOT NULL,
                    body TEXT NOT NULL,
                    status ENUM('pending', 'sent', 'failed') DEFAULT 'pending',
                    error_message TEXT NULL,
                    sent_at DATETIME NULL,
                    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
            ");
        }
        
        $db->insert('email_logs', [
            'recipient_email' => $toEmail ?? 'unknown',
            'recipient_name' => $toName,
            'subject' => $subject,
            'body' => $body,
            'status' => $status,
            'error_message' => $errorMessage,
            'sent_at' => $status === 'sent' ? date('Y-m-d H:i:s') : null,
            'created_at' => date('Y-m-d H:i:s')
        ]);
    } catch (Exception $e) {
        // If database logging fails, log to error log
        error_log("Failed to log email result: " . $e->getMessage());
    }
}
