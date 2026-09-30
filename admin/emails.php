<?php
/**
 * X Business Grant - Email Management
 * Create templates, send bulk emails, manage email campaigns
 */

session_start();

require_once __DIR__ . '/../includes/Database.php';
require_once __DIR__ . '/../includes/Settings.php';
require_once __DIR__ . '/../includes/Email.php';

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

$db = \App\Database::getInstance();
$message = '';
$messageType = '';

// Get current SMTP settings
$smtpHost = \App\Settings::getSMTPHost();
$smtpPort = \App\Settings::getSMTPPort();
$smtpUsername = \App\Settings::getSMTPUsername();
$smtpPassword = \App\Settings::getSMTPPassword();
$smtpEncryption = \App\Settings::getSMTPEncryption();
$smtpFromEmail = \App\Settings::getSMTPFromEmail();
$smtpFromName = \App\Settings::getSMTPFromName();

// Handle template actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        // Save SMTP settings
        if ($_POST['action'] === 'save_smtp') {
            $host = trim($_POST['smtp_host'] ?? '');
            $port = trim($_POST['smtp_port'] ?? '587');
            $username = trim($_POST['smtp_username'] ?? '');
            $password = $_POST['smtp_password'] ?? '';
            $encryption = trim($_POST['smtp_encryption'] ?? 'tls');
            $fromEmail = trim($_POST['smtp_from_email'] ?? '');
            $fromName = trim($_POST['smtp_from_name'] ?? '');
            
            // Check if password should be changed
            $changePassword = isset($_POST['change_password']) && $_POST['change_password'] === '1';
            
            // Only update password if change is checked AND password is provided
            if ($changePassword && !empty($password)) {
                // Password will be updated
            } else {
                // Keep existing password - don't use the field value at all
                $password = \App\Settings::getSMTPPassword();
            }
            
            \App\Settings::setSMTPSettings($host, $port, $username, $password, $encryption, $fromEmail, $fromName);
            
            $message = 'SMTP settings saved successfully!';
            $messageType = 'success';
            
            // Refresh values
            $smtpHost = $host;
            $smtpPort = $port;
            $smtpUsername = $username;
            $smtpPassword = $password;
            $smtpEncryption = $encryption;
            $smtpFromEmail = $fromEmail;
            $smtpFromName = $fromName;
        }
        
        // Save/Update template
        if ($_POST['action'] === 'save_template') {
            $name = trim($_POST['template_name'] ?? '');
            $subject = trim($_POST['template_subject'] ?? '');
            $body = $_POST['template_body'] ?? '';
            $templateId = (int)($_POST['template_id'] ?? 0);
            
            if (empty($name) || empty($subject) || empty($body)) {
                $message = 'All fields are required';
                $messageType = 'error';
            } else {
                if ($templateId > 0) {
                    $db->query(
                        "UPDATE email_templates SET name = ?, subject = ?, body = ?, updated_at = NOW() WHERE id = ?",
                        [$name, $subject, $body, $templateId]
                    );
                    $message = 'Template updated successfully!';
                } else {
                    $db->query(
                        "INSERT INTO email_templates (name, subject, body) VALUES (?, ?, ?)",
                        [$name, $subject, $body]
                    );
                    $message = 'Template created successfully!';
                }
                $messageType = 'success';
            }
        }
        
        // Delete template
        if ($_POST['action'] === 'delete_template') {
            $templateId = (int)($_POST['template_id'] ?? 0);
            if ($templateId > 0) {
                $db->query("DELETE FROM email_templates WHERE id = ?", [$templateId]);
                $message = 'Template deleted successfully!';
                $messageType = 'success';
            }
        }
    }
}

// Ensure email_templates table exists
try {
    $tables = $db->fetchAll("SHOW TABLES LIKE 'email_templates'");
    if (empty($tables)) {
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
    }
} catch (Exception $e) {
    error_log("Email templates table error: " . $e->getMessage());
}

// Keep the admin library useful on existing installations too. These are only
// inserted when missing, so an administrator's edited templates are preserved.
$adminTemplateDefaults = [
    [
        'name' => 'Document Upload Reminder',
        'subject' => 'Reminder: documents required for {{reference}}',
        'body' => '<div style="font-family:Arial,sans-serif;max-width:600px;margin:0 auto"><div style="background:#d97706;padding:28px;text-align:center"><h1 style="color:#fff;margin:0">X Business Grant</h1></div><div style="padding:32px;background:#fff"><h2 style="color:#b45309">Your documents are still needed</h2><p>Dear {{name}},</p><p>Please upload the following documents so we can continue reviewing application <strong>{{reference}}</strong>:</p><div style="background:#fffbeb;border-left:4px solid #f59e0b;padding:16px;margin:20px 0"><ul style="margin:0;padding-left:20px">{{documents}}</ul></div><p>Please submit them as soon as possible to avoid a delay.</p><div style="text-align:center;margin:28px 0"><a href="{{upload_url}}" style="display:inline-block;background:#d97706;color:#fff;padding:12px 26px;text-decoration:none;border-radius:6px;font-weight:bold">Upload Documents</a></div><p>Best regards,<br>The X Business Grant Team</p></div></div>'
    ],
    [
        'name' => 'Document Correction Required',
        'subject' => 'Action needed: correct documents for {{reference}}',
        'body' => '<div style="font-family:Arial,sans-serif;max-width:600px;margin:0 auto"><div style="background:#b91c1c;padding:28px;text-align:center"><h1 style="color:#fff;margin:0">X Business Grant</h1></div><div style="padding:32px;background:#fff"><h2 style="color:#b91c1c">Please correct and resubmit documents</h2><p>Dear {{name}},</p><p>We need corrected copies of the following documents for application <strong>{{reference}}</strong>:</p><div style="background:#fef2f2;border-left:4px solid #dc2626;padding:16px;margin:20px 0"><ul style="margin:0;padding-left:20px">{{documents}}</ul></div><p>Use the secure portal button below to upload the replacements.</p><div style="text-align:center;margin:28px 0"><a href="{{upload_url}}" style="display:inline-block;background:#b91c1c;color:#fff;padding:12px 26px;text-decoration:none;border-radius:6px;font-weight:bold">Resubmit Documents</a></div><p>Best regards,<br>The X Business Grant Team</p></div></div>'
    ],
    [
        'name' => 'Application Decision Available',
        'subject' => 'Your application decision is available - {{reference}}',
        'body' => '<div style="font-family:Arial,sans-serif;max-width:600px;margin:0 auto"><div style="background:#1e40af;padding:28px;text-align:center"><h1 style="color:#fff;margin:0">X Business Grant</h1></div><div style="padding:32px;background:#fff"><h2 style="color:#1e40af">Application update</h2><p>Dear {{name}},</p><p>An update is available for your grant application <strong>{{reference}}</strong>. Sign in to view the decision and any next steps.</p><div style="text-align:center;margin:28px 0"><a href="{{dashboard_url}}" style="display:inline-block;background:#1e40af;color:#fff;padding:12px 26px;text-decoration:none;border-radius:6px;font-weight:bold">View Application Update</a></div><p>Best regards,<br>The X Business Grant Team</p></div></div>'
    ],
    [
        'name' => 'Funding Agreement Ready',
        'subject' => 'Your funding agreement is ready - {{reference}}',
        'body' => '<div style="font-family:Arial,sans-serif;max-width:600px;margin:0 auto"><div style="background:#047857;padding:28px;text-align:center"><h1 style="color:#fff;margin:0">X Business Grant</h1></div><div style="padding:32px;background:#fff"><h2 style="color:#047857">Funding agreement ready</h2><p>Dear {{name}},</p><p>Your funding agreement for application <strong>{{reference}}</strong> is ready for review. Please log in to read and complete the required next steps.</p><div style="text-align:center;margin:28px 0"><a href="{{dashboard_url}}" style="display:inline-block;background:#047857;color:#fff;padding:12px 26px;text-decoration:none;border-radius:6px;font-weight:bold">Review Agreement</a></div><p>Best regards,<br>The X Business Grant Team</p></div></div>'
    ],
    [
        'name' => 'Support Follow-up',
        'subject' => 'We are following up on your X Business Grant request',
        'body' => '<div style="font-family:Arial,sans-serif;max-width:600px;margin:0 auto"><div style="background:#1e40af;padding:28px;text-align:center"><h1 style="color:#fff;margin:0">X Business Grant</h1></div><div style="padding:32px;background:#fff"><h2 style="color:#1e40af">How can we help?</h2><p>Dear {{name}},</p><p>{{message}}</p><p>You can view your application and available actions securely from your dashboard.</p><div style="text-align:center;margin:28px 0"><a href="{{dashboard_url}}" style="display:inline-block;background:#1e40af;color:#fff;padding:12px 26px;text-decoration:none;border-radius:6px;font-weight:bold">Open Dashboard</a></div><p>Best regards,<br>The X Business Grant Team</p></div></div>'
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
        'body' => '<div style="font-family:Arial,sans-serif;max-width:600px;margin:0 auto"><div style="background:#dc2626;padding:28px;text-align:center"><h1 style="color:#fff;margin:0">X Business Grant</h1></div><div style="padding:32px;background:#fff"><h2 style="color:#dc2626">Application Update</h2><p>Dear {{name}},</p><p>After careful review of your grant application (Reference: <strong>{{reference}}</strong>), we regret to inform you that we are unable to approve your request at this time.</p><p>This decision does not reflect on your business potential. We encourage you to:</p><ul style="padding-left:20px"><li>Review and strengthen your business plan</li><li>Build your credit history</li><li>Gather additional supporting documents</li></ul><p>You are welcome to reapply in the future. We wish you success in your business journey.</p><div style="text-align:center;margin:28px 0"><a href="{{apply_url}}" style="display:inline-block;background:#dc2626;color:#fff;padding:12px 26px;text-decoration:none;border-radius:6px;font-weight:bold">Reapply Now</a></div><p>Best regards,<br>The X Business Grant Team</p></div></div>'
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

foreach ($adminTemplateDefaults as $defaultTemplate) {
    $exists = $db->fetchOne("SELECT id FROM email_templates WHERE name = ?", [$defaultTemplate['name']]);
    if (!$exists) {
        $db->query(
            "INSERT INTO email_templates (name, subject, body) VALUES (?, ?, ?)",
            [$defaultTemplate['name'], $defaultTemplate['subject'], $defaultTemplate['body']]
        );
    }
}

// Get templates
$templates = $db->fetchAll("SELECT * FROM email_templates ORDER BY name ASC");

// Get users for sending emails
$users = $db->fetchAll("SELECT id, email, full_name FROM users ORDER BY created_at DESC");

// Get email logs - with error handling for missing table
try {
    // First check if table exists
    $tables = $db->fetchAll("SHOW TABLES LIKE 'email_logs'");
    if (!empty($tables)) {
        $emailLogs = $db->fetchAll("SELECT * FROM email_logs ORDER BY created_at DESC LIMIT 100");
        $pendingResult = $db->fetchOne("SELECT COUNT(*) as count FROM email_logs WHERE status = 'pending'");
        $sentResult = $db->fetchOne("SELECT COUNT(*) as count FROM email_logs WHERE status = 'sent'");
        $failedResult = $db->fetchOne("SELECT COUNT(*) as count FROM email_logs WHERE status = 'failed'");
        $pendingCount = $pendingResult['count'] ?? 0;
        $sentCount = $sentResult['count'] ?? 0;
        $failedCount = $failedResult['count'] ?? 0;
    } else {
        // Table doesn't exist - create it
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
        $emailLogs = [];
        $pendingCount = 0;
        $sentCount = 0;
        $failedCount = 0;
    }
} catch (Exception $e) {
    // If there's any error, set defaults
    $emailLogs = [];
    $pendingCount = 0;
    $sentCount = 0;
    $failedCount = 0;
    error_log("Email logs error: " . $e->getMessage());
}

$currentPage = basename($_SERVER['PHP_SELF'], '.php');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Email Management - X Business Grant Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '#1e40af',
                        secondary: '#3b82f6',
                        accent: '#f59e0b'
                    }
                }
            }
        }
    </script>
    <style>
        .progress-ring {
            transform: rotate(-90deg);
        }
        .progress-ring-circle {
            transition: stroke-dashoffset 0.35s;
            transform-origin: 50% 50%;
        }
    </style>
</head>
<body class="bg-gray-100">
    <div class="flex">
        <!-- Sidebar -->
        <aside class="fixed left-0 top-0 h-screen w-64 bg-gray-900 text-white">
            <div class="p-6">
                <div class="flex items-center space-x-2 mb-8">
                    <div class="w-10 h-10 bg-primary rounded-lg flex items-center justify-center">
                        <span class="text-white font-bold text-xl">X</span>
                    </div>
                    <span class="text-xl font-bold">Business Grant</span>
                </div>
                <nav class="space-y-2">
                    <a href="index.php" class="flex items-center space-x-3 px-4 py-3 rounded-lg hover:bg-gray-800 transition">
                        <i class="fas fa-tachometer-alt"></i>
                        <span>Dashboard</span>
                    </a>
                    <a href="applications.php" class="flex items-center space-x-3 px-4 py-3 rounded-lg hover:bg-gray-800 transition">
                        <i class="fas fa-file-alt"></i>
                        <span>Applications</span>
                    </a>
                    <a href="reports.php" class="flex items-center space-x-3 px-4 py-3 rounded-lg hover:bg-gray-800 transition">
                        <i class="fas fa-chart-bar"></i>
                        <span>Reports</span>
                    </a>
                    <a href="settings.php" class="flex items-center space-x-3 px-4 py-3 rounded-lg hover:bg-gray-800 transition">
                        <i class="fas fa-cog"></i>
                        <span>Settings</span>
                    </a>
                    <a href="emails.php" class="flex items-center space-x-3 px-4 py-3 rounded-lg bg-primary transition">
                        <i class="fas fa-envelope"></i>
                        <span>Email Management</span>
                    </a>
                    <div class="border-t border-gray-700 my-4"></div>
                    <a href="logout.php" class="flex items-center space-x-3 px-4 py-3 rounded-lg hover:bg-gray-800 transition text-red-400">
                        <i class="fas fa-sign-out-alt"></i>
                        <span>Logout</span>
                    </a>
                </nav>
            </div>
        </aside>

        <!-- Main Content -->
        <main class="ml-64 flex-1 p-8">
            <div class="flex justify-between items-center mb-8">
                <div>
                    <h1 class="text-2xl font-bold text-gray-800">Email Management</h1>
                    <p class="text-gray-600">Create templates and send emails to users</p>
                </div>
            </div>

            <?php if ($message): ?>
            <div class="mb-6 p-4 rounded-lg <?php echo $messageType === 'success' ? 'bg-green-50 border-green-200 text-green-800' : 'bg-red-50 border-red-200 text-red-800'; ?> border">
                <p><i class="fas fa-<?php echo $messageType === 'success' ? 'check-circle' : 'exclamation-circle'; ?> mr-2"></i><?php echo htmlspecialchars($message); ?></p>
            </div>
            <?php endif; ?>

            <!-- Stats -->
            <?php
            // Check SMTP configuration using the static method
            $smtpConfigured = \App\Settings::isSMTPConfigured();
            ?>
            
            <?php if (!$smtpConfigured): ?>
            <div class="mb-6 p-4 bg-red-50 border border-red-200 rounded-lg">
                <div class="flex items-center">
                    <i class="fas fa-exclamation-triangle text-red-500 mr-3"></i>
                    <div>
                        <p class="font-medium text-red-800">SMTP Not Configured</p>
                        <p class="text-sm text-red-600">Email sending will not work. Please configure SMTP settings.</p>
                    </div>
                    <a href="smtp_settings.php" class="ml-auto px-4 py-2 bg-red-600 text-white rounded-lg text-sm hover:bg-red-700">
                        <i class="fas fa-cog mr-1"></i>Configure SMTP
                    </a>
                </div>
            </div>
            <?php endif; ?>
            
            <div class="grid grid-cols-4 gap-4 mb-6">
                <div class="bg-white rounded-lg p-4 border-l-4 border-blue-500">
                    <p class="text-gray-500 text-sm">Templates</p>
                    <p class="text-2xl font-bold"><?php echo count($templates); ?></p>
                </div>
                <div class="bg-white rounded-lg p-4 border-l-4 border-yellow-500">
                    <p class="text-gray-500 text-sm">Pending</p>
                    <p class="text-2xl font-bold text-yellow-600"><?php echo $pendingCount; ?></p>
                </div>
                <div class="bg-white rounded-lg p-4 border-l-4 border-green-500">
                    <p class="text-gray-500 text-sm">Sent</p>
                    <p class="text-2xl font-bold text-green-600"><?php echo $sentCount; ?></p>
                </div>
                <div class="bg-white rounded-lg p-4 border-l-4 border-red-500">
                    <p class="text-gray-500 text-sm">Failed</p>
                    <p class="text-2xl font-bold text-red-600"><?php echo $failedCount; ?></p>
                </div>
            </div>

            <!-- Tab Navigation -->
            <div class="flex space-x-4 mb-6 border-b border-gray-200">
                <button onclick="showTab('templates')" id="tab-templates" class="pb-4 px-2 font-medium text-primary border-b-2 border-primary">
                    <i class="fas fa-file-alt mr-2"></i>Email Templates
                </button>
                <button onclick="showTab('compose')" id="tab-compose" class="pb-4 px-2 font-medium text-gray-500 hover:text-gray-700">
                    <i class="fas fa-paper-plane mr-2"></i>Compose & Send
                </button>
                <button onclick="showTab('smtp')" id="tab-smtp" class="pb-4 px-2 font-medium text-gray-500 hover:text-gray-700">
                    <i class="fas fa-server mr-2"></i>SMTP Settings
                </button>
                <button onclick="showTab('logs')" id="tab-logs" class="pb-4 px-2 font-medium text-gray-500 hover:text-gray-700">
                    <i class="fas fa-list mr-2"></i>Email Logs
                </button>
            </div>

            <!-- Templates Tab -->
            <div id="content-templates" class="tab-content">
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    <!-- Template List -->
                    <div class="lg:col-span-1">
                        <div class="bg-white rounded-xl shadow-sm p-6">
                            <div class="flex justify-between items-center mb-4">
                                <h3 class="text-lg font-bold text-gray-800">Templates</h3>
                                <button onclick="openNewTemplate()" class="px-3 py-1 bg-primary text-white rounded-lg text-sm hover:bg-blue-700">
                                    <i class="fas fa-plus mr-1"></i>New
                                </button>
                            </div>
                            
                            <div class="space-y-3">
                                <?php foreach ($templates as $template): ?>
                                <div class="p-4 border border-gray-200 rounded-lg hover:border-primary cursor-pointer template-item" 
                                     onclick="loadTemplate(<?php echo $template['id']; ?>, '<?php echo htmlspecialchars($template['name']); ?>', '<?php echo htmlspecialchars($template['subject']); ?>', `<?php echo str_replace(['`', "\r\n", "\n"], ["\\`", "\\n", "\\n"], htmlspecialchars($template['body'])); ?>`)">
                                    <div class="flex justify-between items-start">
                                        <div>
                                            <p class="font-medium text-gray-800"><?php echo htmlspecialchars($template['name']); ?></p>
                                            <p class="text-sm text-gray-500 truncate"><?php echo htmlspecialchars($template['subject']); ?></p>
                                        </div>
                                        <div class="flex space-x-1">
                                            <button onclick="event.stopPropagation(); loadTemplate(<?php echo $template['id']; ?>, '<?php echo htmlspecialchars(addslashes($template['name'])); ?>', '<?php echo htmlspecialchars(addslashes($template['subject'])); ?>', `<?php echo str_replace(['`', "\r\n", "\n"], ["\\`", "\\n", "\\n"], htmlspecialchars(addslashes($template['body']))); ?>`)" 
                                                class="p-1 text-blue-600 hover:bg-blue-50 rounded" title="Edit">
                                                <i class="fas fa-edit text-sm"></i>
                                            </button>
                                            <form method="POST" class="inline" onsubmit="return confirm('Delete this template?')">
                                                <input type="hidden" name="action" value="delete_template">
                                                <input type="hidden" name="template_id" value="<?php echo $template['id']; ?>">
                                                <button type="submit" class="p-1 text-red-600 hover:bg-red-50 rounded" title="Delete">
                                                    <i class="fas fa-trash text-sm"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                                <?php if (empty($templates)): ?>
                                <p class="text-gray-500 text-center py-4">No templates yet. Create one!</p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Template Editor -->
                    <div class="lg:col-span-2">
                        <div class="bg-white rounded-xl shadow-sm p-6">
                            <h3 class="text-lg font-bold text-gray-800 mb-4">
                                <i class="fas fa-edit mr-2"></i><span id="editorTitle">New Template</span>
                            </h3>
                            
                            <form method="POST" class="space-y-4">
                                <input type="hidden" name="action" value="save_template">
                                <input type="hidden" name="template_id" id="template_id" value="0">
                                
                                <div>
                                    <label class="block text-gray-700 font-medium mb-2">Template Name *</label>
                                    <input type="text" name="template_name" id="template_name" required
                                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent"
                                        placeholder="e.g., Welcome Email">
                                </div>
                                
                                <div>
                                    <label class="block text-gray-700 font-medium mb-2">Subject Line *</label>
                                    <input type="text" name="template_subject" id="template_subject" required
                                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent"
                                        placeholder="e.g., Welcome to X Business Grant!">
                                </div>
                                
                                <div>
                                    <label class="block text-gray-700 font-medium mb-2">Body *</label>
                                    <p class="text-xs text-gray-500 mb-2">Use variables: {{name}}, {{email}}, {{reference}}, {{amount}}, {{year}}, {{message}}, {{documents}}, {{status}}, {{date}}, {{time}}, {{location}}, {{dashboard_url}}, {{apply_url}}, {{reset_link}}, {{verify_url}}, {{upload_url}}, {{continue_url}}, {{contact_url}}, {{survey_link}}, {{referral_link}}, {{referral_code}}, {{code}}, {{unsubscribe_url}}, {{view_in_browser_url}}, {{rating_url}}, {{new_deadline}}</p>
                                    <textarea name="template_body" id="template_body" required rows="15"
                                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent font-mono text-sm"></textarea>
                                </div>
                                
                                <div class="flex justify-end space-x-3">
                                    <button type="button" onclick="clearEditor()" class="px-4 py-2 border border-gray-300 rounded-lg text-gray-600 hover:bg-gray-50">
                                        Clear
                                    </button>
                                    <button type="submit" class="px-6 py-2 bg-primary text-white rounded-lg hover:bg-blue-700">
                                        <i class="fas fa-save mr-2"></i>Save Template
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Compose & Send Tab -->
            <div id="content-compose" class="tab-content hidden">
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    <!-- Recipients Selection -->
                    <div class="lg:col-span-1">
                        <div class="bg-white rounded-xl shadow-sm p-6">
                            <h3 class="text-lg font-bold text-gray-800 mb-4">
                                <i class="fas fa-users mr-2"></i>Select Recipients
                            </h3>
                            
                            <div class="mb-4">
                                <div class="flex items-center justify-between mb-2">
                                    <label class="text-sm font-medium text-gray-700">Users (<?php echo count($users); ?>)</label>
                                    <button type="button" onclick="toggleAllUsers()" class="text-xs text-primary hover:underline" id="selectAllBtn">Select All</button>
                                </div>
                                <select id="recipients" multiple class="w-full h-64 px-3 py-2 border border-gray-300 rounded-lg text-sm">
                                    <?php foreach ($users as $user): ?>
                                    <option value="<?php echo $user['id']; ?>" data-email="<?php echo htmlspecialchars($user['email']); ?>" data-name="<?php echo htmlspecialchars($user['full_name']); ?>">
                                        <?php echo htmlspecialchars($user['full_name'] . ' (' . $user['email'] . ')'); ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                                <p class="text-xs text-gray-500 mt-1">Hold Ctrl/Cmd to select multiple</p>
                            </div>
                            
                            <!-- Quick Filters -->
                            <div class="mb-4">
                                <label class="block text-sm font-medium text-gray-700 mb-2">Filter by Status</label>
                                <select id="filterStatus" onchange="filterByStatus()" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                                    <option value="">All Users</option>
                                    <option value="has_application">Has Applications</option>
                                    <option value="no_application">No Applications</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- Email Composer -->
                    <div class="lg:col-span-2">
                        <div class="bg-white rounded-xl shadow-sm p-6">
                            <h3 class="text-lg font-bold text-gray-800 mb-4">
                                <i class="fas fa-edit mr-2"></i>Compose Email
                            </h3>
                            
                            <form id="emailForm" method="POST">
                                <input type="hidden" name="action" value="send_email">
                                <input type="hidden" name="recipient_ids" id="recipient_ids">
                                
                                <div class="grid grid-cols-2 gap-4 mb-4">
                                    <div>
                                        <label class="block text-gray-700 font-medium mb-2">Template (optional)</label>
                                        <select id="templateSelect" onchange="applyTemplate()" class="w-full px-4 py-2 border border-gray-300 rounded-lg">
                                            <option value="">-- Select Template --</option>
                                            <?php foreach ($templates as $template): ?>
                                            <option value="<?php echo $template['id']; ?>" data-subject="<?php echo htmlspecialchars($template['subject']); ?>" data-body="<?php echo htmlspecialchars($template['body']); ?>">
                                                <?php echo htmlspecialchars($template['name']); ?>
                                            </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="block text-gray-700 font-medium mb-2">Sending Speed</label>
                                        <select name="speed" id="sendSpeed" class="w-full px-4 py-2 border border-gray-300 rounded-lg">
                                            <option value="fast">Fast (may trigger spam)</option>
                                            <option value="normal" selected>Normal (recommended)</option>
                                            <option value="slow">Slow (safer)</option>
                                        </select>
                                    </div>
                                </div>
                                
                                <div class="mb-4">
                                    <label class="block text-gray-700 font-medium mb-2">Subject *</label>
                                    <input type="text" name="email_subject" id="email_subject" required
                                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent"
                                        placeholder="Email subject line">
                                </div>
                                
                                <div class="mb-4">
                                    <label class="block text-gray-700 font-medium mb-2">Message *</label>
                                    <p class="text-xs text-gray-500 mb-2">Use variables: {{name}}, {{email}}, {{reference}}, {{amount}}, {{year}}, {{message}}, {{documents}}, {{status}}, {{date}}, {{time}}, {{location}}, {{dashboard_url}}, {{apply_url}}, {{reset_link}}, {{verify_url}}, {{upload_url}}, {{continue_url}}, {{contact_url}}, {{survey_link}}, {{referral_link}}, {{referral_code}}, {{code}}, {{unsubscribe_url}}, {{view_in_browser_url}}, {{rating_url}}, {{new_deadline}}</p>
                                    <textarea name="email_body" id="email_body" required rows="12"
                                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent font-mono text-sm"
                                        placeholder="Email body content..."></textarea>
                                </div>

                                <div class="mb-4">
                                    <label class="block text-gray-700 font-medium mb-2">Requested documents</label>
                                    <p class="text-xs text-gray-500 mb-2">For document templates, enter one document per line. These are inserted as a bulleted list wherever <code>{{documents}}</code> appears.</p>
                                    <textarea id="requested_documents" rows="4"
                                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent"
                                        placeholder="CAC Certificate&#10;Valid government-issued ID&#10;Recent utility bill"></textarea>
                                </div>

                                <div class="mb-4">
                                    <label class="block text-gray-700 font-medium mb-2">Additional template message</label>
                                    <p class="text-xs text-gray-500 mb-2">Used by templates containing <code>{{message}}</code>.</p>
                                    <textarea id="template_message" rows="3"
                                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent"
                                        placeholder="Add any extra information for the recipient..."></textarea>
                                </div>

                                <!-- Progress Section -->
                                <div id="progressSection" class="hidden mb-4">
                                    <div class="bg-gray-50 rounded-lg p-4">
                                        <div class="flex justify-between items-center mb-2">
                                            <span class="text-sm font-medium">Sending Progress</span>
                                            <span class="text-sm text-gray-600" id="progressText">0 / 0</span>
                                        </div>
                                        <div class="w-full bg-gray-200 rounded-full h-3 mb-3">
                                            <div id="progressBar" class="bg-primary h-3 rounded-full transition-all duration-300" style="width: 0%"></div>
                                        </div>
                                        <div class="flex items-center justify-between">
                                            <div class="flex items-center">
                                                <span id="sendingStatus" class="text-sm text-gray-600">
                                                    <i class="fas fa-spinner fa-spin mr-1"></i>Preparing...
                                                </span>
                                            </div>
                                            <div class="flex space-x-2" id="controlButtons">
                                                <button type="button" id="pauseBtn" onclick="pauseSending()" class="px-3 py-1 bg-yellow-500 text-white rounded text-sm hover:bg-yellow-600 hidden">
                                                    <i class="fas fa-pause mr-1"></i>Pause
                                                </button>
                                                <button type="button" id="stopBtn" onclick="stopSending()" class="px-3 py-1 bg-red-500 text-white rounded text-sm hover:bg-red-600 hidden">
                                                    <i class="fas fa-stop mr-1"></i>Stop
                                                </button>
                                            </div>
                                        </div>
                                        <div id="statsSection" class="mt-2 text-xs text-gray-500 hidden">
                                            <span class="text-green-600">Sent: <span id="sentCount">0</span></span> | 
                                            <span class="text-red-600">Failed: <span id="failedCount">0</span></span> | 
                                            <span class="text-yellow-600">Remaining: <span id="remainingCount">0</span></span>
                                        </div>
                                    </div>
                                </div>

                                <!-- Result Message Area -->
                                <div id="resultMessage" class="hidden mb-4 p-4 rounded-lg border"></div>

                                <div class="flex justify-end space-x-3">
                                    <button type="button" onclick="previewEmail()" class="px-4 py-2 border border-gray-300 rounded-lg text-gray-600 hover:bg-gray-50">
                                        <i class="fas fa-eye mr-2"></i>Preview
                                    </button>
                                    <button type="button" id="sendBtn" onclick="startSending()" class="px-6 py-2 bg-primary text-white rounded-lg hover:bg-blue-700">
                                        <i class="fas fa-paper-plane mr-2"></i>Send Emails
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            <!-- SMTP Settings Tab -->
            <div id="content-smtp" class="tab-content hidden">
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <!-- SMTP Configuration Form -->
                    <div class="bg-white rounded-xl shadow-sm p-6">
                        <h3 class="text-lg font-bold text-gray-800 mb-4">
                            <i class="fas fa-server text-primary mr-2"></i>SMTP Configuration
                        </h3>
                        <p class="text-gray-500 text-sm mb-6">Configure your SMTP server settings for sending emails.</p>
                        
                        <form method="POST" class="space-y-4">
                            <input type="hidden" name="action" value="save_smtp">
                            
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-gray-700 font-medium mb-2">SMTP Host</label>
                                    <input type="text" name="smtp_host" required
                                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent"
                                        placeholder="smtp.gmail.com"
                                        value="<?php echo htmlspecialchars($smtpHost); ?>">
                                </div>
                                <div>
                                    <label class="block text-gray-700 font-medium mb-2">Port</label>
                                    <select name="smtp_port" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
                                        <option value="587" <?php echo $smtpPort === '587' ? 'selected' : ''; ?>>587 (TLS)</option>
                                        <option value="465" <?php echo $smtpPort === '465' ? 'selected' : ''; ?>>465 (SSL)</option>
                                        <option value="25" <?php echo $smtpPort === '25' ? 'selected' : ''; ?>>25 (No encryption)</option>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-gray-700 font-medium mb-2">Username</label>
                                    <input type="text" name="smtp_username"
                                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent"
                                        placeholder="your@email.com"
                                        value="<?php echo htmlspecialchars($smtpUsername); ?>">
                                </div>
                                <div>
                                    <label class="block text-gray-700 font-medium mb-2">Password</label>
                                    <div class="relative">
                                        <input type="password" name="smtp_password" id="smtp_password"
                                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent"
                                            placeholder="Enter new password to change"
                                            value="">
                                        <button type="button" onclick="togglePassword('smtp_password')"
                                            class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600">
                                            <i class="fas fa-eye" id="smtp_password_icon"></i>
                                        </button>
                                    </div>
                                    <label class="flex items-center mt-2">
                                        <input type="checkbox" name="change_password" value="1" class="mr-2">
                                        <span class="text-xs text-gray-500">Check to change password</span>
                                    </label>
                                </div>
                            </div>
                            
                            <div>
                                <label class="block text-gray-700 font-medium mb-2">Encryption</label>
                                <select name="smtp_encryption" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
                                    <option value="tls" <?php echo $smtpEncryption === 'tls' ? 'selected' : ''; ?>>TLS</option>
                                    <option value="ssl" <?php echo $smtpEncryption === 'ssl' ? 'selected' : ''; ?>>SSL</option>
                                    <option value="none" <?php echo $smtpEncryption === 'none' ? 'selected' : ''; ?>>None</option>
                                </select>
                            </div>
                            
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-gray-700 font-medium mb-2">From Email</label>
                                    <input type="email" name="smtp_from_email"
                                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent"
                                        placeholder="noreply@example.com"
                                        value="<?php echo htmlspecialchars($smtpFromEmail); ?>">
                                </div>
                                <div>
                                    <label class="block text-gray-700 font-medium mb-2">From Name</label>
                                    <input type="text" name="smtp_from_name"
                                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent"
                                        placeholder="X Business Grant"
                                        value="<?php echo htmlspecialchars($smtpFromName); ?>">
                                </div>
                            </div>
                            
                            <div class="pt-4">
                                <button type="submit" class="w-full py-3 bg-primary text-white rounded-lg hover:bg-blue-700 transition font-semibold">
                                    <i class="fas fa-save mr-2"></i>Save SMTP Settings
                                </button>
                            </div>
                        </form>
                    </div>

                    <!-- Test Connection & Info -->
                    <div class="space-y-6">
                        <!-- Connection Status -->
                        <div class="bg-white rounded-xl shadow-sm p-6">
                            <h3 class="text-lg font-bold text-gray-800 mb-4">
                                <i class="fas fa-plug text-green-600 mr-2"></i>Connection Status
                            </h3>
                            <?php if (\App\Settings::isSMTPConfigured()): ?>
                            <div class="flex items-center text-green-600">
                                <i class="fas fa-check-circle text-2xl mr-3"></i>
                                <div>
                                    <p class="font-medium text-green-800">SMTP is Configured</p>
                                    <p class="text-sm text-green-600"><?php echo htmlspecialchars($smtpHost); ?>:<?php echo htmlspecialchars($smtpPort); ?></p>
                                </div>
                            </div>
                            <?php else: ?>
                            <div class="flex items-center text-red-600">
                                <i class="fas fa-times-circle text-2xl mr-3"></i>
                                <div>
                                    <p class="font-medium text-red-800">SMTP Not Configured</p>
                                    <p class="text-sm text-red-600">Please configure SMTP settings above</p>
                                </div>
                            </div>
                            <?php endif; ?>
                        </div>

                        <!-- SMTP Provider Info -->
                        <div class="bg-white rounded-xl shadow-sm p-6">
                            <h3 class="text-lg font-bold text-gray-800 mb-4">
                                <i class="fas fa-info-circle text-blue-600 mr-2"></i>Common SMTP Providers
                            </h3>
                            <div class="space-y-4 text-sm text-gray-600">
                                <div>
                                    <p class="font-medium text-gray-800">Gmail / Google Workspace:</p>
                                    <p>Host: smtp.gmail.com</p>
                                    <p>Port: 587 (TLS) or 465 (SSL)</p>
                                    <p class="text-xs text-gray-500">Note: Requires App Password (not regular password)</p>
                                </div>
                                <div>
                                    <p class="font-medium text-gray-800">Outlook / Office 365:</p>
                                    <p>Host: smtp.office365.com</p>
                                    <p>Port: 587 (TLS)</p>
                                </div>
                                <div>
                                    <p class="font-medium text-gray-800">Mailgun:</p>
                                    <p>Host: smtp.mailgun.org</p>
                                    <p>Port: 587 (TLS) or 465 (SSL)</p>
                                </div>
                                <div>
                                    <p class="font-medium text-gray-800">SendGrid:</p>
                                    <p>Host: smtp.sendgrid.net</p>
                                    <p>Port: 587 (TLS)</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Email Logs Tab -->
            <div id="content-logs" class="tab-content hidden">
                <div class="bg-white rounded-xl shadow-sm overflow-hidden">
                    <div class="flex justify-between items-center p-4 border-b border-gray-200">
                        <h3 class="text-lg font-bold text-gray-800">Email History</h3>
                        <button onclick="refreshLogs()" class="px-4 py-2 bg-primary text-white rounded-lg text-sm hover:bg-blue-700">
                            <i class="fas fa-sync-alt mr-1"></i>Refresh Logs
                        </button>
                    </div>
                    <table class="w-full">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="text-left py-3 px-4 font-semibold text-gray-600">Recipient</th>
                                <th class="text-left py-3 px-4 font-semibold text-gray-600">Subject</th>
                                <th class="text-left py-3 px-4 font-semibold text-gray-600">Status</th>
                                <th class="text-left py-3 px-4 font-semibold text-gray-600">Date</th>
                            </tr>
                        </thead>
                        <tbody id="emailLogsBody">
                            <?php foreach ($emailLogs as $log): ?>
                            <tr class="border-b border-gray-100 hover:bg-gray-50">
                                <td class="py-3 px-4">
                                    <div class="font-medium"><?php echo htmlspecialchars($log['recipient_name'] ?? 'N/A'); ?></div>
                                    <div class="text-xs text-gray-500"><?php echo htmlspecialchars($log['recipient_email']); ?></div>
                                </td>
                                <td class="py-3 px-4 text-sm"><?php echo htmlspecialchars($log['subject']); ?></td>
                                <td class="py-3 px-4">
                                    <?php
                                        $statusClass = $log['status'] === 'sent' ? 'bg-green-100 text-green-800' : ($log['status'] === 'failed' ? 'bg-red-100 text-red-800' : 'bg-yellow-100 text-yellow-800');
                                    ?>
                                    <span class="px-2 py-1 rounded text-xs font-medium <?php echo $statusClass; ?>">
                                        <?php echo ucfirst($log['status']); ?>
                                    </span>
                                    <?php if ($log['error_message']): ?>
                                    <p class="text-xs text-red-500 mt-1"><?php echo htmlspecialchars($log['error_message']); ?></p>
                                    <?php endif; ?>
                                </td>
                                <td class="py-3 px-4 text-sm text-gray-500">
                                    <?php echo $log['sent_at'] ? date('M d, Y H:i', strtotime($log['sent_at'])) : date('M d, Y H:i', strtotime($log['created_at'])); ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if (empty($emailLogs)): ?>
                            <tr>
                                <td colspan="4" class="py-12 text-center text-gray-500">
                                    <i class="fas fa-envelope text-4xl mb-4"></i>
                                    <p>No emails sent yet</p>
                                </td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>

    <!-- Preview Modal -->
    <div id="previewModal" class="fixed inset-0 bg-black/50 hidden items-center justify-center z-50">
        <div class="bg-white rounded-xl p-6 w-full max-w-2xl mx-4 max-h-[90vh] overflow-y-auto">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-xl font-bold text-gray-800">Email Preview</h3>
                <button onclick="closePreview()" class="text-gray-500 hover:text-gray-700">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div id="previewContent" class="border border-gray-200 rounded-lg p-4 bg-gray-50"></div>
        </div>
    </div>

    <script>
        // Tab functionality
        function showTab(tabName) {
            document.querySelectorAll('.tab-content').forEach(el => el.classList.add('hidden'));
            document.getElementById('content-' + tabName).classList.remove('hidden');
            
            document.querySelectorAll('[id^="tab-"]').forEach(el => {
                el.classList.remove('text-primary', 'border-b-2', 'border-primary');
                el.classList.add('text-gray-500');
            });
            
            document.getElementById('tab-' + tabName).classList.remove('text-gray-500');
            document.getElementById('tab-' + tabName).classList.add('text-primary', 'border-b-2', 'border-primary');
        }

        // Template editor
        function openNewTemplate() {
            clearEditor();
            document.getElementById('editorTitle').textContent = 'New Template';
        }

        function loadTemplate(id, name, subject, body) {
            document.getElementById('template_id').value = id;
            document.getElementById('template_name').value = name;
            document.getElementById('template_subject').value = subject;
            document.getElementById('template_body').value = body.replace(/\\`/g, '`').replace(/\\n/g, '\n');
            document.getElementById('editorTitle').textContent = 'Edit Template: ' + name;
        }

        function clearEditor() {
            document.getElementById('template_id').value = '0';
            document.getElementById('template_name').value = '';
            document.getElementById('template_subject').value = '';
            document.getElementById('template_body').value = '';
            document.getElementById('editorTitle').textContent = 'New Template';
        }

        // Apply template to composer
        function applyTemplate() {
            const select = document.getElementById('templateSelect');
            const option = select.options[select.selectedIndex];
            if (option.value) {
                document.getElementById('email_subject').value = option.dataset.subject;
                document.getElementById('email_body').value = option.dataset.body;
            }
        }

        // Select all users
        function toggleAllUsers() {
            const select = document.getElementById('recipients');
            const btn = document.getElementById('selectAllBtn');
            if (select.options[0].selected) {
                for (let i = 0; i < select.options.length; i++) {
                    select.options[i].selected = false;
                }
                btn.textContent = 'Select All';
            } else {
                for (let i = 0; i < select.options.length; i++) {
                    select.options[i].selected = true;
                }
                btn.textContent = 'Deselect All';
            }
        }

        // Filter users
        function filterByStatus() {
            // This would require AJAX in production - for now just show all
        }

        // Preview email
        function escapeTemplateHtml(value) {
            const div = document.createElement('div');
            div.textContent = value || '';
            return div.innerHTML;
        }

        function getTemplateVariables(recipient = {}) {
            const dashboardUrl = new URL('../dashboard.php', window.location.href).href;
            const applyUrl = new URL('../apply.php', window.location.href).href;
            const requestedDocuments = document.getElementById('requested_documents').value
                .split(/\r?\n/)
                .map(item => item.trim())
                .filter(Boolean);
            const documents = requestedDocuments.length
                ? requestedDocuments.map(item => `<li>${escapeTemplateHtml(item)}</li>`).join('')
                : '<li>Please see your dashboard for the document requirements.</li>';
            const message = document.getElementById('template_message').value.trim();

            return {
                name: recipient.name || 'Applicant',
                email: recipient.email || 'applicant@example.com',
                reference: 'XBG-123456',
                amount: '₦500,000',
                year: new Date().getFullYear(),
                message: message ? `<p>${escapeTemplateHtml(message).replace(/\n/g, '<br>')}</p>` : '<p>Please log in to your dashboard for more information.</p>',
                documents,
                status: 'Under Review',
                date: 'October 15, 2026',
                time: '10:00 AM',
                location: 'Lagos Office',
                dashboard_url: dashboardUrl,
                apply_url: applyUrl,
                reset_link: new URL('../reset_password.php?token=sample', window.location.href).href,
                verify_url: new URL('../verify_email.php?code=sample', window.location.href).href,
                upload_url: `${dashboardUrl}#documents`,
                continue_url: `${applyUrl}?continue=1`,
                contact_url: new URL('../contact.php', window.location.href).href,
                survey_link: new URL('../survey.php', window.location.href).href,
                referral_link: new URL('../referral.php', window.location.href).href,
                referral_code: 'REF-XXXXXX',
                code: '123456',
                unsubscribe_url: new URL('../unsubscribe.php', window.location.href).href,
                view_in_browser_url: window.location.href,
                rating_url: new URL('../rate.php', window.location.href).href,
                new_deadline: 'December 31, 2026'
            };
        }

        function renderTemplate(content, recipient = {}, addFallbackButton = true) {
            const variables = getTemplateVariables(recipient);
            const rendered = Object.entries(variables).reduce(
                (rendered, [key, value]) => rendered.replace(new RegExp(`\\{\\{${key}\\}\\}`, 'g'), value),
                content
            );

            // Guarantee every email offers the recipient a clear action, even
            // for older or manually created templates without a link/button.
            if (addFallbackButton && !/<a\s[^>]*href=/i.test(rendered)) {
                return `${rendered}<div style="text-align:center;margin:30px 0"><a href="${variables.dashboard_url}" style="display:inline-block;background:#1e40af;color:#ffffff;padding:12px 26px;text-decoration:none;border-radius:6px;font-weight:bold">Open Dashboard</a></div>`;
            }

            return rendered;
        }

        function previewEmail() {
            const subject = renderTemplate(document.getElementById('email_subject').value || 'No Subject', { name: 'John Doe', email: 'john@example.com' }, false);
            const body = document.getElementById('email_body').value || 'No Content';
            const previewBody = renderTemplate(body, { name: 'John Doe', email: 'john@example.com' });
            
            document.getElementById('previewContent').innerHTML = `
                <h2 class="text-lg font-bold mb-2">Subject: ${subject}</h2>
                <hr class="my-3">
                <div class="prose">${previewBody}</div>
            `;
            document.getElementById('previewModal').classList.remove('hidden');
            document.getElementById('previewModal').classList.add('flex');
        }

        function closePreview() {
            document.getElementById('previewModal').classList.add('hidden');
            document.getElementById('previewModal').classList.remove('flex');
        }

        // Email sending state
        let isSending = false;
        let isPaused = false;
        let shouldStop = false;
        let currentRecipients = [];
        let currentIndex = 0;

        function startSending() {
            const select = document.getElementById('recipients');
            const selected = Array.from(select.options).filter(o => o.selected);
            
            if (selected.length === 0) {
                alert('Please select at least one recipient.');
                return;
            }

            const subject = document.getElementById('email_subject').value;
            const body = document.getElementById('email_body').value;
            
            if (!subject || !body) {
                alert('Please enter subject and message.');
                return;
            }

            currentRecipients = selected.map(o => ({
                id: o.value,
                email: o.dataset.email,
                name: o.dataset.name
            }));
            
            currentIndex = 0;
            shouldStop = false;
            isPaused = false;
            isSending = true;

            document.getElementById('progressSection').classList.remove('hidden');
            document.getElementById('statsSection').classList.remove('hidden');
            document.getElementById('pauseBtn').classList.remove('hidden');
            document.getElementById('stopBtn').classList.remove('hidden');
            document.getElementById('sendBtn').disabled = true;
            document.getElementById('sendBtn').innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Sending...';

            document.getElementById('sendingStatus').innerHTML = '<i class="fas fa-spinner fa-spin mr-1"></i>Sending...';
            updateProgress();

            sendNextEmail(subject, body);
        }

        function sendNextEmail(subject, body) {
            if (shouldStop) {
                finishSending();
                return;
            }

            if (isPaused || currentIndex >= currentRecipients.length) {
                if (currentIndex >= currentRecipients.length) {
                    finishSending();
                }
                return;
            }

            const recipient = currentRecipients[currentIndex];
            
            const personalizedBody = renderTemplate(body, recipient);
            const personalizedSubject = renderTemplate(subject, recipient, false).replace(/<[^>]*>/g, '');

            // Simulate sending (in production, this would be an AJAX call)
            fetch('send_email_ajax.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    to: recipient.email,
                    name: recipient.name,
                    subject: personalizedSubject,
                    body: personalizedBody
                })
            })
            .then(r => {
                if (!r.ok) {
                    throw new Error('HTTP error: ' + r.status);
                }
                return r.json();
            })
            .then(data => {
                console.log('Email send result:', data);
                if (data.success) {
                    document.getElementById('sentCount').textContent = parseInt(document.getElementById('sentCount').textContent) + 1;
                } else {
                    document.getElementById('failedCount').textContent = parseInt(document.getElementById('failedCount').textContent) + 1;
                    document.getElementById('sendingStatus').innerHTML = '<i class="fas fa-exclamation-circle text-red-600 mr-1"></i>Failed: ' + escapeHtml(data.message || 'Unknown error');
                }
                currentIndex++;
                updateProgress();
                setTimeout(() => sendNextEmail(subject, body), getDelay());
            })
            .catch(err => {
                console.error('Email send error:', err);
                document.getElementById('failedCount').textContent = parseInt(document.getElementById('failedCount').textContent) + 1;
                document.getElementById('sendingStatus').innerHTML = '<i class="fas fa-exclamation-circle text-red-600 mr-1"></i>Error: ' + escapeHtml(err.message);
                currentIndex++;
                updateProgress();
                setTimeout(() => sendNextEmail(subject, body), getDelay());
            });
        }

        function getDelay() {
            const speed = document.getElementById('sendSpeed').value;
            if (speed === 'fast') return 500;
            if (speed === 'slow') return 2000;
            return 1000;
        }

        function updateProgress() {
            const total = currentRecipients.length;
            const percent = total > 0 ? Math.round((currentIndex / total) * 100) : 0;
            document.getElementById('progressBar').style.width = percent + '%';
            document.getElementById('progressText').textContent = `${currentIndex} / ${total}`;
            document.getElementById('remainingCount').textContent = total - currentIndex;
        }

        function pauseSending() {
            isPaused = !isPaused;
            document.getElementById('pauseBtn').innerHTML = isPaused ? 
                '<i class="fas fa-play mr-1"></i>Resume' : 
                '<i class="fas fa-pause mr-1"></i>Pause';
            document.getElementById('sendingStatus').innerHTML = isPaused ? 
                '<i class="fas fa-pause mr-1 text-yellow-600"></i>Paused' : 
                '<i class="fas fa-spinner fa-spin mr-1"></i>Sending...';
        }

        function stopSending() {
            if (confirm('Stop sending emails? Already sent emails will remain sent.')) {
                shouldStop = true;
                document.getElementById('sendingStatus').innerHTML = '<i class="fas fa-stop mr-1 text-red-600"></i>Stopping...';
            }
        }

        function finishSending() {
            isSending = false;
            const sentCount = parseInt(document.getElementById('sentCount').textContent);
            const failedCount = parseInt(document.getElementById('failedCount').textContent);
            
            let statusHtml = '';
            let resultClass = '';
            let resultIcon = '';
            
            if (failedCount === 0 && sentCount > 0) {
                statusHtml = 'All emails sent successfully!';
                resultClass = 'bg-green-50 border-green-200 text-green-800';
                resultIcon = 'check-circle';
            } else if (sentCount > 0 && failedCount > 0) {
                statusHtml = 'Completed: ' + sentCount + ' sent, ' + failedCount + ' failed';
                resultClass = 'bg-yellow-50 border-yellow-200 text-yellow-800';
                resultIcon = 'exclamation-triangle';
            } else if (sentCount === 0 && failedCount > 0) {
                statusHtml = 'All ' + failedCount + ' emails failed to send. Check SMTP settings.';
                resultClass = 'bg-red-50 border-red-200 text-red-800';
                resultIcon = 'times-circle';
            } else {
                statusHtml = 'No emails were processed.';
                resultClass = 'bg-gray-50 border-gray-200 text-gray-800';
                resultIcon = 'info-circle';
            }
            
            document.getElementById('sendingStatus').innerHTML = '<i class="fas fa-' + resultIcon + ' mr-1"></i>' + statusHtml;
            
            // Show result message
            const resultDiv = document.getElementById('resultMessage');
            resultDiv.className = 'mb-4 p-4 rounded-lg border ' + resultClass;
            resultDiv.innerHTML = '<p><i class="fas fa-' + resultIcon + ' mr-2"></i>' + statusHtml + '</p>';
            resultDiv.classList.remove('hidden');
            
            document.getElementById('sendBtn').disabled = false;
            document.getElementById('sendBtn').innerHTML = '<i class="fas fa-paper-plane mr-2"></i>Send Emails';
            document.getElementById('pauseBtn').classList.add('hidden');
            document.getElementById('stopBtn').classList.add('hidden');
            
            // Refresh the logs after sending completes
            setTimeout(() => {
                refreshLogs();
            }, 1000);
        }
        
        // Refresh email logs via AJAX
        function refreshLogs() {
            fetch('get_email_logs_ajax.php', {
                method: 'GET',
                headers: { 'Content-Type': 'application/json' }
            })
            .then(r => r.json())
            .then(data => {
                if (data.success && data.logs) {
                    updateLogsTable(data.logs);
                }
            })
            .catch(err => {
                console.error('Failed to refresh logs:', err);
                // Reload the page to show updated logs
                location.reload();
            });
        }
        
        function updateLogsTable(logs) {
            const tbody = document.getElementById('emailLogsBody');
            if (!tbody) return;
            
            if (logs.length === 0) {
                tbody.innerHTML = `
                    <tr>
                        <td colspan="4" class="py-12 text-center text-gray-500">
                            <i class="fas fa-envelope text-4xl mb-4"></i>
                            <p>No emails sent yet</p>
                        </td>
                    </tr>
                `;
                return;
            }
            
            let html = '';
            logs.forEach(log => {
                const statusClass = log.status === 'sent' ? 'bg-green-100 text-green-800' : 
                                   (log.status === 'failed' ? 'bg-red-100 text-red-800' : 'bg-yellow-100 text-yellow-800');
                const date = log.sent_at || log.created_at;
                const formattedDate = new Date(date).toLocaleString('en-US', { 
                    month: 'short', day: 'numeric', year: 'numeric', hour: '2-digit', minute: '2-digit' 
                });
                
                html += `
                    <tr class="border-b border-gray-100 hover:bg-gray-50">
                        <td class="py-3 px-4">
                            <div class="font-medium">${escapeHtml(log.recipient_name || 'N/A')}</div>
                            <div class="text-xs text-gray-500">${escapeHtml(log.recipient_email)}</div>
                        </td>
                        <td class="py-3 px-4 text-sm">${escapeHtml(log.subject)}</td>
                        <td class="py-3 px-4">
                            <span class="px-2 py-1 rounded text-xs font-medium ${statusClass}">${log.status.charAt(0).toUpperCase() + log.status.slice(1)}</span>
                            ${log.error_message ? `<p class="text-xs text-red-500 mt-1" title="${escapeHtml(log.error_message)}"><i class="fas fa-info-circle"></i> ${escapeHtml(log.error_message.substring(0, 50))}${log.error_message.length > 50 ? '...' : ''}</p>` : ''}
                        </td>
                        <td class="py-3 px-4 text-sm text-gray-500">${formattedDate}</td>
                    </tr>
                `;
            });
            
            tbody.innerHTML = html;
        }
        
        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        // Close modal on outside click
        document.getElementById('previewModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closePreview();
            }
        });

        // Toggle password visibility
        function togglePassword(inputId) {
            const input = document.getElementById(inputId);
            const icon = document.getElementById(inputId + '_icon');
            
            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                input.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        }
    </script>
</body>
</html>
