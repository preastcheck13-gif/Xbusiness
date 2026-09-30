<?php
/**
 * AJAX handler for getting email logs
 */

session_start();

require_once __DIR__ . '/../includes/Database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

try {
    $db = \App\Database::getInstance();
    
    // Check if email_logs table exists
    $tables = $db->fetchAll("SHOW TABLES LIKE 'email_logs'");
    if (empty($tables)) {
        echo json_encode(['success' => true, 'logs' => []]);
        exit;
    }
    
    // Get email logs
    $logs = $db->fetchAll("SELECT * FROM email_logs ORDER BY created_at DESC LIMIT 100");
    
    echo json_encode(['success' => true, 'logs' => $logs]);
    
} catch (Exception $e) {
    error_log("Error fetching email logs: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Failed to fetch logs: ' . $e->getMessage()]);
}
