<?php
/**
 * X Business Grant - Admin SMTP Settings
 * Manage SMTP credentials, test connection, and email verification toggle
 */

session_start();

require_once __DIR__ . '/../includes/Database.php';
require_once __DIR__ . '/../includes/Settings.php';
require_once __DIR__ . '/../includes/Email.php';

use App\Settings;
use App\Email;

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

$message = '';
$messageType = '';

// Get current SMTP settings
$smtpHost = Settings::getSMTPHost();
$smtpPort = Settings::getSMTPPort();
$smtpUsername = Settings::getSMTPUsername();
$smtpPassword = Settings::getSMTPPassword();
$smtpEncryption = Settings::getSMTPEncryption();
$smtpFromEmail = Settings::getSMTPFromEmail();
$smtpFromName = Settings::getSMTPFromName();
$emailVerificationEnabled = Settings::isEmailVerificationEnabled();

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'save_smtp') {
        // Save SMTP settings
        $host = trim($_POST['smtp_host'] ?? '');
        $port = trim($_POST['smtp_port'] ?? '587');
        $username = trim($_POST['smtp_username'] ?? '');
        $password = trim($_POST['smtp_password'] ?? '');
        $encryption = trim($_POST['smtp_encryption'] ?? 'tls');
        $fromEmail = trim($_POST['smtp_from_email'] ?? '');
        $fromName = trim($_POST['smtp_from_name'] ?? '');
        
        Settings::setSMTPSettings($host, $port, $username, $password, $encryption, $fromEmail, $fromName);
        
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
        
    } elseif ($_POST['action'] === 'test_connection') {
        // Test SMTP connection
        $host = trim($_POST['test_smtp_host'] ?? '');
        $port = trim($_POST['test_smtp_port'] ?? '587');
        $username = trim($_POST['test_smtp_username'] ?? '');
        $password = trim($_POST['test_smtp_password'] ?? '');
        $encryption = trim($_POST['test_smtp_encryption'] ?? 'tls');
        
        if (empty($host) || empty($username) || empty($password)) {
            $message = 'Please fill in all fields to test connection';
            $messageType = 'error';
        } else {
            $result = Email::testSMTPConnection($host, $port, $username, $password, $encryption);
            if ($result['success']) {
                $message = '✓ ' . $result['message'];
                $messageType = 'success';
            } else {
                $message = '✗ ' . $result['message'];
                $messageType = 'error';
            }
        }
        
    } elseif ($_POST['action'] === 'send_test_email') {
        // Send test email
        $host = trim($_POST['test_email_host'] ?? '');
        $port = trim($_POST['test_email_port'] ?? '587');
        $username = trim($_POST['test_email_username'] ?? '');
        $password = trim($_POST['test_email_password'] ?? '');
        $encryption = trim($_POST['test_email_encryption'] ?? 'tls');
        $fromEmail = trim($_POST['test_email_from_email'] ?? '');
        $fromName = trim($_POST['test_email_from_name'] ?? '');
        $testEmail = trim($_POST['test_email_address'] ?? '');
        
        if (empty($host) || empty($username) || empty($password) || empty($testEmail)) {
            $message = 'Please fill in all fields to send test email';
            $messageType = 'error';
        } else {
            $result = Email::sendTestEmail($host, $port, $username, $password, $encryption, $fromEmail, $fromName, $testEmail);
            if ($result['success']) {
                $message = '✓ Test email sent successfully to ' . htmlspecialchars($testEmail) . '!';
                $messageType = 'success';
            } else {
                $message = '✗ Failed to send test email: ' . $result['message'];
                $messageType = 'error';
            }
        }
        
    } elseif ($_POST['action'] === 'toggle_verification') {
        // Toggle email verification
        $enabled = isset($_POST['email_verification_enabled']) ? true : false;
        Settings::setEmailVerificationEnabled($enabled);
        $emailVerificationEnabled = $enabled;
        $message = 'Email verification ' . ($enabled ? 'enabled' : 'disabled') . ' successfully!';
        $messageType = 'success';
    }
}

$currentPage = basename($_SERVER['PHP_SELF'], '.php');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SMTP Settings - X Business Grant Admin</title>
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
                    <a href="smtp_settings.php" class="flex items-center space-x-3 px-4 py-3 rounded-lg bg-primary transition">
                        <i class="fas fa-envelope"></i>
                        <span>SMTP Settings</span>
                    </a>
                    <a href="whatsapp.php" class="flex items-center space-x-3 px-4 py-3 rounded-lg hover:bg-gray-800 transition">
                        <i class="fab fa-whatsapp"></i>
                        <span>WhatsApp</span>
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
                    <h1 class="text-2xl font-bold text-gray-800">SMTP Settings</h1>
                    <p class="text-gray-600">Configure email settings and email verification</p>
                </div>
            </div>

            <?php if ($message): ?>
            <div class="mb-6 p-4 rounded-lg <?php echo $messageType === 'success' ? 'bg-green-50 border-green-200 text-green-800' : 'bg-red-50 border-red-200 text-red-800'; ?> border">
                <p><i class="fas fa-<?php echo $messageType === 'success' ? 'check-circle' : 'exclamation-circle'; ?> mr-2"></i><?php echo htmlspecialchars($message); ?></p>
            </div>
            <?php endif; ?>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- SMTP Configuration -->
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
                                        placeholder="Leave empty to keep current"
                                        value="">
                                    <button type="button" onclick="togglePassword('smtp_password')"
                                        class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600">
                                        <i class="fas fa-eye" id="smtp_password_icon"></i>
                                    </button>
                                </div>
                                <p class="text-xs text-gray-500 mt-1">Leave empty to keep current password</p>
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

                <!-- Test Connection & Email Verification Toggle -->
                <div class="space-y-6">
                    <!-- Test Connection -->
                    <div class="bg-white rounded-xl shadow-sm p-6">
                        <h3 class="text-lg font-bold text-gray-800 mb-4">
                            <i class="fas fa-plug text-green-600 mr-2"></i>Test Connection
                        </h3>
                        <p class="text-gray-500 text-sm mb-6">Verify SMTP connection with your server.</p>
                        
                        <form method="POST" class="space-y-4">
                            <input type="hidden" name="action" value="test_connection">
                            
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-gray-700 font-medium mb-2">Host</label>
                                    <input type="text" name="test_smtp_host"
                                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent text-sm"
                                        placeholder="smtp.gmail.com"
                                        value="<?php echo htmlspecialchars($smtpHost); ?>">
                                </div>
                                <div>
                                    <label class="block text-gray-700 font-medium mb-2">Port</label>
                                    <input type="text" name="test_smtp_port"
                                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent text-sm"
                                        placeholder="587"
                                        value="<?php echo htmlspecialchars($smtpPort); ?>">
                                </div>
                            </div>
                            
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-gray-700 font-medium mb-2">Username</label>
                                    <input type="text" name="test_smtp_username"
                                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent text-sm"
                                        placeholder="your@email.com"
                                        value="<?php echo htmlspecialchars($smtpUsername); ?>">
                                </div>
                                <div>
                                    <label class="block text-gray-700 font-medium mb-2">Password</label>
                                    <input type="password" name="test_smtp_password"
                                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent text-sm"
                                        placeholder="password">
                                </div>
                            </div>
                            
                            <div>
                                <label class="block text-gray-700 font-medium mb-2">Encryption</label>
                                <select name="test_smtp_encryption" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent text-sm">
                                    <option value="tls" <?php echo $smtpEncryption === 'tls' ? 'selected' : ''; ?>>TLS</option>
                                    <option value="ssl" <?php echo $smtpEncryption === 'ssl' ? 'selected' : ''; ?>>SSL</option>
                                    <option value="none" <?php echo $smtpEncryption === 'none' ? 'selected' : ''; ?>>None</option>
                                </select>
                            </div>
                            
                            <button type="submit" class="w-full py-3 bg-green-600 text-white rounded-lg hover:bg-green-700 transition font-semibold">
                                <i class="fas fa-satellite-dish mr-2"></i>Test Connection
                            </button>
                        </form>
                    </div>

                    <!-- Send Test Email -->
                    <div class="bg-white rounded-xl shadow-sm p-6">
                        <h3 class="text-lg font-bold text-gray-800 mb-4">
                            <i class="fas fa-paper-plane text-blue-600 mr-2"></i>Send Test Email
                        </h3>
                        <p class="text-gray-500 text-sm mb-6">Send a test email to verify your settings.</p>
                        
                        <form method="POST" class="space-y-4">
                            <input type="hidden" name="action" value="send_test_email">
                            <input type="hidden" name="test_email_host" value="<?php echo htmlspecialchars($smtpHost); ?>">
                            <input type="hidden" name="test_email_port" value="<?php echo htmlspecialchars($smtpPort); ?>">
                            <input type="hidden" name="test_email_username" value="<?php echo htmlspecialchars($smtpUsername); ?>">
                            <input type="hidden" name="test_email_password" value="<?php echo htmlspecialchars($smtpPassword); ?>">
                            <input type="hidden" name="test_email_encryption" value="<?php echo htmlspecialchars($smtpEncryption); ?>">
                            <input type="hidden" name="test_email_from_email" value="<?php echo htmlspecialchars($smtpFromEmail); ?>">
                            <input type="hidden" name="test_email_from_name" value="<?php echo htmlspecialchars($smtpFromName); ?>">
                            
                            <div>
                                <label class="block text-gray-700 font-medium mb-2">Test Email Address</label>
                                <input type="email" name="test_email_address" required
                                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent"
                                    placeholder="recipient@example.com">
                            </div>
                            
                            <button type="submit" class="w-full py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition font-semibold">
                                <i class="fas fa-paper-plane mr-2"></i>Send Test Email
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Email Verification Toggle -->
            <div class="mt-6 bg-white rounded-xl shadow-sm p-6">
                <div class="flex items-center justify-between">
                    <div class="flex items-center">
                        <div class="w-12 h-12 bg-primary/10 rounded-xl flex items-center justify-center mr-4">
                            <i class="fas fa-envelope-check text-primary text-xl"></i>
                        </div>
                        <div>
                            <h3 class="text-lg font-bold text-gray-800">Email Verification</h3>
                            <p class="text-gray-500 text-sm">Require new users to verify their email before accessing the dashboard</p>
                        </div>
                    </div>
                    <form method="POST" class="flex items-center">
                        <input type="hidden" name="action" value="toggle_verification">
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="checkbox" name="email_verification_enabled" value="1" 
                                class="sr-only peer" 
                                <?php echo $emailVerificationEnabled ? 'checked' : ''; ?>
                                onchange="this.form.submit()">
                            <div class="w-14 h-7 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-primary/20 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-6 after:w-6 after:transition-all peer-checked:bg-primary"></div>
                        </label>
                        <span class="ml-3 text-sm font-medium <?php echo $emailVerificationEnabled ? 'text-green-600' : 'text-gray-500'; ?>">
                            <?php echo $emailVerificationEnabled ? 'Enabled' : 'Disabled'; ?>
                        </span>
                    </form>
                </div>
                
                <div class="mt-4 p-4 bg-gray-50 rounded-lg">
                    <h4 class="font-medium text-gray-700 mb-2">How it works:</h4>
                    <ul class="text-sm text-gray-600 space-y-1">
                        <li><i class="fas fa-check text-green-500 mr-2"></i>New users receive a 6-digit code via email after registration</li>
                        <li><i class="fas fa-check text-green-500 mr-2"></i>Users must enter the code on the verification page</li>
                        <li><i class="fas fa-check text-green-500 mr-2"></i>Codes expire after 30 minutes</li>
                        <li><i class="fas fa-check text-green-500 mr-2"></i>Users can request a new code if needed</li>
                    </ul>
                </div>
            </div>

            <!-- SMTP Provider Info -->
            <div class="mt-6 bg-blue-50 border border-blue-200 rounded-xl p-4">
                <div class="flex items-start">
                    <i class="fas fa-info-circle text-blue-600 mt-1 mr-3"></i>
                    <div>
                        <h4 class="font-bold text-gray-800">Common SMTP Providers</h4>
                        <div class="grid grid-cols-2 gap-4 mt-3 text-sm text-gray-600">
                            <div>
                                <p class="font-medium">Gmail / Google Workspace:</p>
                                <p>Host: smtp.gmail.com</p>
                                <p>Port: 587 (TLS) or 465 (SSL)</p>
                                <p class="text-xs text-gray-500">Note: Requires "Less secure app access" or App Password</p>
                            </div>
                            <div>
                                <p class="font-medium">Outlook / Office 365:</p>
                                <p>Host: smtp.office365.com</p>
                                <p>Port: 587 (TLS)</p>
                            </div>
                            <div>
                                <p class="font-medium">Mailgun:</p>
                                <p>Host: smtp.mailgun.org</p>
                                <p>Port: 587 (TLS) or 465 (SSL)</p>
                            </div>
                            <div>
                                <p class="font-medium">SendGrid:</p>
                                <p>Host: smtp.sendgrid.net</p>
                                <p>Port: 587 (TLS)</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script>
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
