<?php
/**
 * X Business Grant - Admin WhatsApp Manager
 * Allows admin to configure their WhatsApp number and message users
 */

session_start();

require_once __DIR__ . '/../includes/Database.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/Settings.php';

use App\Settings;
use App\Helpers;

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

$message = '';
$messageType = '';

// Initialize database
$db = \App\Database::getInstance();

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'update_whatsapp') {
        $whatsappNumber = trim($_POST['whatsapp_number'] ?? '');
        
        // Basic validation
        $cleanNumber = preg_replace('/\D/', '', $whatsappNumber);
        if (strlen($cleanNumber) < 10) {
            $message = 'Please enter a valid phone number with at least 10 digits.';
            $messageType = 'error';
        } else {
            Settings::setWhatsAppAdminNumber($whatsappNumber);
            $message = 'WhatsApp number updated successfully!';
            $messageType = 'success';
        }
    } elseif ($_POST['action'] === 'send_message' && isset($_POST['user_id'])) {
        $userId = (int)$_POST['user_id'];
        $customMessage = trim($_POST['custom_message'] ?? '');
        
        // Get user's phone number
        $user = $db->fetchOne("SELECT phone FROM users WHERE id = ?", [$userId]);
        if ($user && !empty($user['phone'])) {
            $whatsappLink = Settings::getWhatsAppLink($user['phone'], $customMessage);
            // Redirect to WhatsApp
            header('Location: ' . $whatsappLink);
            exit;
        } else {
            $message = 'User phone number not found.';
            $messageType = 'error';
        }
    } elseif ($_POST['action'] === 'mark_message_sent') {
        $userId = (int)($_POST['user_id'] ?? 0);
        $messageText = trim($_POST['message_text'] ?? '');
        
        if ($userId > 0 && !empty($messageText)) {
            $db->query(
                "INSERT INTO whatsapp_messages (user_id, message, sent_at) VALUES (?, ?, NOW())",
                [$userId, $messageText]
            );
            $message = 'Message logged successfully!';
            $messageType = 'success';
        }
    }
}

// Get WhatsApp settings
$whatsappAdminNumber = Settings::getWhatsAppAdminNumber();
$isWhatsAppConfigured = Settings::isWhatsAppConfigured();

// Get all WhatsApp templates
$whatsappTemplates = $db->fetchAll("
    SELECT * FROM whatsapp_templates
    WHERE is_active = 1
    ORDER BY category, name
");

// Get all users with their phone numbers
$users = $db->fetchAll("
    SELECT u.id, u.full_name, u.email, u.phone, u.created_at,
           (SELECT COUNT(*) FROM applications WHERE email = u.email) as app_count
    FROM users u
    WHERE u.phone IS NOT NULL AND u.phone != ''
    ORDER BY u.created_at DESC
");

// Group templates by category
$templatesByCategory = [];
foreach ($whatsappTemplates as $template) {
    $category = $template['category'];
    if (!isset($templatesByCategory[$category])) {
        $templatesByCategory[$category] = [];
    }
    $templatesByCategory[$category][] = $template;
}

// Get recent WhatsApp messages
$recentMessages = $db->fetchAll("
    SELECT wm.*, u.full_name, u.phone
    FROM whatsapp_messages wm
    JOIN users u ON wm.user_id = u.id
    ORDER BY wm.sent_at DESC
    LIMIT 20
");

$currentPage = basename($_SERVER['PHP_SELF'], '.php');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>WhatsApp Manager - X Business Grant Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '#1e40af',
                        secondary: '#3b82f6',
                        accent: '#f59e0b',
                        whatsapp: '#25D366'
                    }
                }
            }
        }
    </script>
    <style>
        .whatsapp-green { background-color: #25D366; }
        .whatsapp-green:hover { background-color: #20BD5A; }
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
                    <a href="smtp_settings.php" class="flex items-center space-x-3 px-4 py-3 rounded-lg hover:bg-gray-800 transition">
                        <i class="fas fa-envelope"></i>
                        <span>SMTP Settings</span>
                    </a>
                    <a href="whatsapp.php" class="flex items-center space-x-3 px-4 py-3 rounded-lg bg-gray-800 transition">
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
                    <h1 class="text-2xl font-bold text-gray-800">
                        <i class="fab fa-whatsapp text-whatsapp text-3xl mr-2"></i>
                        WhatsApp Manager
                    </h1>
                    <p class="text-gray-600">Message users directly via WhatsApp</p>
                </div>
            </div>

            <?php if ($message): ?>
            <div class="mb-6 p-4 rounded-lg <?php echo $messageType === 'success' ? 'bg-green-50 border-green-200 text-green-800' : 'bg-red-50 border-red-200 text-red-800'; ?> border">
                <p><i class="fas fa-<?php echo $messageType === 'success' ? 'check-circle' : 'exclamation-circle'; ?> mr-2"></i><?php echo htmlspecialchars($message); ?></p>
            </div>
            <?php endif; ?>

            <!-- Configuration Status -->
            <div class="mb-6">
                <?php if ($isWhatsAppConfigured): ?>
                <div class="bg-green-50 border border-green-200 rounded-xl p-4 flex items-center">
                    <div class="w-10 h-10 bg-whatsapp rounded-full flex items-center justify-center mr-4">
                        <i class="fab fa-whatsapp text-white text-xl"></i>
                    </div>
                    <div>
                        <h3 class="font-bold text-green-800">WhatsApp Connected</h3>
                        <p class="text-green-600 text-sm">Your WhatsApp number: <?php echo htmlspecialchars($whatsappAdminNumber); ?></p>
                    </div>
                </div>
                <?php else: ?>
                <div class="bg-yellow-50 border border-yellow-200 rounded-xl p-4 flex items-center">
                    <div class="w-10 h-10 bg-yellow-500 rounded-full flex items-center justify-center mr-4">
                        <i class="fas fa-exclamation-triangle text-white text-xl"></i>
                    </div>
                    <div>
                        <h3 class="font-bold text-yellow-800">Setup Required</h3>
                        <p class="text-yellow-600 text-sm">Please configure your WhatsApp number below to start messaging users</p>
                    </div>
                </div>
                <?php endif; ?>
            </div>

            <!-- Bulk Message Section -->
            <div class="bg-white rounded-xl shadow-sm p-6 mb-6">
                <h3 class="text-lg font-bold text-gray-800 mb-4">
                    <i class="fas fa-users text-primary mr-2"></i>
                    Send Message to Multiple Users
                </h3>
                <p class="text-gray-500 text-sm mb-6">
                    Select users and a template to send a message to multiple users at once
                </p>
                
                <form method="GET" class="space-y-4">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-gray-700 font-medium mb-2">Filter by State</label>
                            <select name="filter_state" onchange="this.form.submit()" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-whatsapp focus:border-transparent">
                                <option value="">All States</option>
                                <?php
                                $states = $db->fetchAll("SELECT DISTINCT state FROM applications ORDER BY state");
                                $currentState = $_GET['filter_state'] ?? '';
                                foreach ($states as $state): ?>
                                    <option value="<?php echo htmlspecialchars($state['state']); ?>" <?php echo $currentState === $state['state'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($state['state']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label class="block text-gray-700 font-medium mb-2">Filter by Status</label>
                            <select name="filter_status" onchange="this.form.submit()" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-whatsapp focus:border-transparent">
                                <option value="">All Statuses</option>
                                <option value="pending" <?php echo ($_GET['filter_status'] ?? '') === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                <option value="under_review" <?php echo ($_GET['filter_status'] ?? '') === 'under_review' ? 'selected' : ''; ?>>Under Review</option>
                                <option value="approved" <?php echo ($_GET['filter_status'] ?? '') === 'approved' ? 'selected' : ''; ?>>Approved</option>
                                <option value="rejected" <?php echo ($_GET['filter_status'] ?? '') === 'rejected' ? 'selected' : ''; ?>>Rejected</option>
                            </select>
                        </div>
                    </div>
                </form>
                
                <?php
                // Build filtered query for bulk messaging
                $whereClause = "u.phone IS NOT NULL AND u.phone != ''";
                $params = [];
                
                if (!empty($_GET['filter_state'])) {
                    $whereClause .= " AND a.state = ?";
                    $params[] = $_GET['filter_state'];
                }
                
                if (!empty($_GET['filter_status'])) {
                    $whereClause .= " AND a.status = ?";
                    $params[] = $_GET['filter_status'];
                }
                
                $bulkUsers = $db->fetchAll("
                    SELECT DISTINCT u.id, u.full_name, u.email, u.phone, a.state, a.status
                    FROM users u
                    LEFT JOIN applications a ON u.email = a.email
                    WHERE $whereClause
                    ORDER BY u.full_name
                ", $params);
                ?>
                
                <form method="POST" id="bulkForm" class="mt-6">
                    <input type="hidden" name="action" value="bulk_send">
                    
                    <div class="mb-4">
                        <div class="flex items-center mb-2">
                            <input type="checkbox" id="selectAll" onchange="toggleAllUsers(this)"
                                class="w-4 h-4 text-whatsapp border-gray-300 rounded focus:ring-whatsapp">
                            <label for="selectAll" class="ml-2 text-gray-700 font-medium">
                                Select All (<span id="selectedCount">0</span> selected)
                            </label>
                        </div>
                        <p class="text-sm text-gray-500">Select users from the list below to include in bulk message</p>
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3 max-h-64 overflow-y-auto mb-4 p-4 bg-gray-50 rounded-lg">
                        <?php foreach ($bulkUsers as $buser): ?>
                        <label class="flex items-center p-2 bg-white rounded border hover:bg-gray-50 cursor-pointer">
                            <input type="checkbox" name="bulk_user_ids[]" value="<?php echo $buser['id']; ?>"
                                   onchange="updateSelectedCount()"
                                   data-phone="<?php echo htmlspecialchars($buser['phone']); ?>"
                                   data-name="<?php echo htmlspecialchars($buser['full_name']); ?>"
                                   class="user-checkbox w-4 h-4 text-whatsapp border-gray-300 rounded focus:ring-whatsapp">
                            <div class="ml-3">
                                <span class="block text-sm font-medium text-gray-700"><?php echo htmlspecialchars($buser['full_name']); ?></span>
                                <span class="block text-xs text-gray-500"><?php echo htmlspecialchars($buser['phone']); ?></span>
                            </div>
                        </label>
                        <?php endforeach; ?>
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-gray-700 font-medium mb-2">Select Template</label>
                            <select name="bulk_template_id" id="bulkTemplateSelect" onchange="fillBulkTemplate()"
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-whatsapp focus:border-transparent">
                                <option value="">-- Select a template --</option>
                                <?php foreach ($templatesByCategory as $category => $templates): ?>
                                    <optgroup label="<?php echo ucfirst(htmlspecialchars($category)); ?>">
                                        <?php foreach ($templates as $template): ?>
                                            <option value="<?php echo $template['id']; ?>"
                                                    data-message="<?php echo htmlspecialchars($template['message']); ?>">
                                                <?php echo htmlspecialchars($template['name']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </optgroup>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label class="block text-gray-700 font-medium mb-2">Message Preview</label>
                            <textarea id="bulkMessagePreview" rows="3" readonly
                                class="w-full px-4 py-3 border border-gray-200 rounded-lg bg-gray-50 text-gray-600 text-sm"
                                placeholder="Select a template to preview message..."></textarea>
                        </div>
                    </div>
                    
                    <div class="mt-4 flex gap-3">
                        <button type="button" onclick="openBulkPreviewModal()"
                            class="px-6 py-3 bg-whatsapp text-white rounded-lg hover:bg-green-600 transition font-semibold">
                            <i class="fab fa-whatsapp mr-2"></i>
                            Preview & Send Bulk Message
                        </button>
                    </div>
                </form>
            </div>

            <!-- Bulk Preview Modal -->
            <div id="bulkPreviewModal" class="fixed inset-0 bg-black/50 hidden items-center justify-center z-50">
                <div class="bg-white rounded-xl shadow-xl max-w-4xl w-full mx-4 max-h-[90vh] overflow-hidden">
                    <div class="bg-whatsapp p-4 text-white">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center">
                                <i class="fab fa-whatsapp text-2xl mr-3"></i>
                                <div>
                                    <h3 class="font-bold">Bulk Message Preview</h3>
                                    <p class="text-sm opacity-90"><span id="bulkSelectedCount">0</span> recipients selected</p>
                                </div>
                            </div>
                            <button onclick="closeBulkPreviewModal()" class="text-white hover:bg-green-600 rounded-full p-2">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                    </div>
                    <div class="p-6 overflow-y-auto max-h-[60vh]">
                        <div class="mb-4 p-4 bg-gray-50 rounded-lg">
                            <h4 class="font-medium text-gray-700 mb-2">Selected Recipients:</h4>
                            <div id="bulkRecipientsList" class="text-sm text-gray-600"></div>
                        </div>
                        <div class="mb-4">
                            <h4 class="font-medium text-gray-700 mb-2">Message to be sent:</h4>
                            <div id="bulkMessageContent" class="p-4 bg-white border rounded-lg whitespace-pre-wrap text-sm"></div>
                        </div>
                        <p class="text-sm text-gray-500">
                            <i class="fas fa-info-circle mr-1"></i>
                            Each recipient will receive a personalized WhatsApp link. You will be redirected to WhatsApp with the first recipient's chat.
                        </p>
                    </div>
                    <div class="p-4 border-t flex gap-3">
                        <button type="button" onclick="closeBulkPreviewModal()"
                            class="flex-1 py-3 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition font-medium">
                            Cancel
                        </button>
                        <button type="button" onclick="sendBulkMessages()"
                            class="flex-1 py-3 bg-whatsapp text-white rounded-lg hover:bg-green-600 transition font-semibold">
                            <i class="fab fa-whatsapp mr-2"></i>
                            Open WhatsApp for First Recipient
                        </button>
                    </div>
                </div>
            </div>

            <!-- WhatsApp Configuration Card -->
            <div class="bg-white rounded-xl shadow-sm p-6 mb-6 max-w-2xl">
                <h3 class="text-lg font-bold text-gray-800 mb-4">
                    <i class="fas fa-mobile-alt text-primary mr-2"></i>
                    Your WhatsApp Number
                </h3>
                <p class="text-gray-500 text-sm mb-6">
                    Enter the WhatsApp number that will be used to send messages. 
                    Include country code (e.g., 234 for Nigeria).
                </p>
                
                <form method="POST" class="space-y-4">
                    <input type="hidden" name="action" value="update_whatsapp">
                    
                    <div>
                        <label class="block text-gray-700 font-medium mb-2">
                            WhatsApp Number <span class="text-red-500">*</span>
                        </label>
                        <div class="flex gap-3">
                            <input type="tel" name="whatsapp_number" required
                                class="flex-1 px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-whatsapp focus:border-transparent"
                                placeholder="e.g., 2348012345678"
                                value="<?php echo htmlspecialchars($whatsappAdminNumber); ?>">
                            <button type="submit" class="px-6 py-3 bg-whatsapp text-white rounded-lg hover:bg-green-600 transition font-semibold">
                                <i class="fas fa-save mr-2"></i>Save
                            </button>
                        </div>
                        <p class="text-xs text-gray-500 mt-2">
                            Include country code without the + sign. Example: 2348012345678 for Nigerian numbers.
                        </p>
                    </div>
                </form>
            </div>

            <!-- Users with Phone Numbers -->
            <div class="bg-white rounded-xl shadow-sm overflow-hidden mb-6">
                <div class="p-6 border-b border-gray-200">
                    <h3 class="text-lg font-bold text-gray-800">
                        <i class="fas fa-users text-primary mr-2"></i>
                        Users with Phone Numbers
                    </h3>
                    <p class="text-gray-500 text-sm mt-1">
                        Click on any user to open WhatsApp and send them a message
                    </p>
                </div>
                
                <?php if (!empty($users)): ?>
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="text-left py-3 px-4 font-semibold text-gray-600">User</th>
                                <th class="text-left py-3 px-4 font-semibold text-gray-600">Phone</th>
                                <th class="text-left py-3 px-4 font-semibold text-gray-600">Applications</th>
                                <th class="text-left py-3 px-4 font-semibold text-gray-600">Joined</th>
                                <th class="text-left py-3 px-4 font-semibold text-gray-600">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($users as $user): ?>
                            <tr class="border-b border-gray-100 hover:bg-gray-50">
                                <td class="py-3 px-4">
                                    <div class="flex items-center">
                                        <div class="w-8 h-8 bg-primary rounded-full flex items-center justify-center mr-3">
                                            <span class="text-white font-bold text-sm"><?php echo strtoupper(substr($user['full_name'], 0, 1)); ?></span>
                                        </div>
                                        <div>
                                            <span class="font-medium"><?php echo htmlspecialchars($user['full_name']); ?></span>
                                            <p class="text-xs text-gray-500"><?php echo htmlspecialchars($user['email']); ?></p>
                                        </div>
                                    </div>
                                </td>
                                <td class="py-3 px-4">
                                    <span class="text-sm"><?php echo htmlspecialchars($user['phone']); ?></span>
                                </td>
                                <td class="py-3 px-4">
                                    <span class="px-2 py-1 bg-primary/10 text-primary rounded text-sm font-medium">
                                        <?php echo $user['app_count']; ?> application<?php echo $user['app_count'] !== 1 ? 's' : ''; ?>
                                    </span>
                                </td>
                                <td class="py-3 px-4 text-sm text-gray-500">
                                    <?php echo date('M d, Y', strtotime($user['created_at'])); ?>
                                </td>
                                <td class="py-3 px-4">
                                    <button type="button" onclick="openWhatsAppModal(<?php echo $user['id']; ?>, '<?php echo htmlspecialchars(addslashes($user['full_name'])); ?>', '<?php echo htmlspecialchars($user['phone']); ?>')"
                                        class="inline-flex items-center px-3 py-2 bg-whatsapp text-white rounded-lg hover:bg-green-600 transition text-sm">
                                        <i class="fab fa-whatsapp mr-2"></i>
                                        Message
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                <div class="p-12 text-center text-gray-500">
                    <i class="fas fa-users text-4xl mb-4 text-gray-300"></i>
                    <p>No users with phone numbers found</p>
                </div>
                <?php endif; ?>
            </div>

            <!-- Recent Messages -->
            <?php if (!empty($recentMessages)): ?>
            <div class="bg-white rounded-xl shadow-sm overflow-hidden">
                <div class="p-6 border-b border-gray-200">
                    <h3 class="text-lg font-bold text-gray-800">
                        <i class="fas fa-history text-primary mr-2"></i>
                        Recent Messages Sent
                    </h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="text-left py-3 px-4 font-semibold text-gray-600">User</th>
                                <th class="text-left py-3 px-4 font-semibold text-gray-600">Phone</th>
                                <th class="text-left py-3 px-4 font-semibold text-gray-600">Message</th>
                                <th class="text-left py-3 px-4 font-semibold text-gray-600">Sent At</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recentMessages as $msg): ?>
                            <tr class="border-b border-gray-100">
                                <td class="py-3 px-4">
                                    <span class="font-medium"><?php echo htmlspecialchars($msg['full_name']); ?></span>
                                </td>
                                <td class="py-3 px-4 text-sm"><?php echo htmlspecialchars($msg['phone']); ?></td>
                                <td class="py-3 px-4 text-sm max-w-xs truncate"><?php echo htmlspecialchars($msg['message']); ?></td>
                                <td class="py-3 px-4 text-sm text-gray-500">
                                    <?php echo date('M d, Y H:i', strtotime($msg['sent_at'])); ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php endif; ?>
        </main>
    </div>

    <!-- WhatsApp Message Modal -->
    <div id="whatsappModal" class="fixed inset-0 bg-black/50 hidden items-center justify-center z-50">
        <div class="bg-white rounded-xl shadow-xl max-w-md w-full mx-4 overflow-hidden">
            <div class="bg-whatsapp p-4 text-white">
                <div class="flex items-center justify-between">
                    <div class="flex items-center">
                        <i class="fab fa-whatsapp text-2xl mr-3"></i>
                        <div>
                            <h3 class="font-bold">Send WhatsApp Message</h3>
                            <p class="text-sm opacity-90">to: <span id="modalUserName"></span></p>
                        </div>
                    </div>
                    <button onclick="closeWhatsAppModal()" class="text-white hover:bg-green-600 rounded-full p-2">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            </div>
            <form method="POST" class="p-6 space-y-4">
                <input type="hidden" name="action" value="send_message">
                <input type="hidden" name="user_id" id="modalUserId">
                
                <div>
                    <label class="block text-gray-700 font-medium mb-2">Phone Number</label>
                    <input type="text" id="modalUserPhone" readonly
                        class="w-full px-4 py-3 border border-gray-200 rounded-lg bg-gray-50 text-gray-600">
                </div>
                
                <div>
                    <label class="block text-gray-700 font-medium mb-2">Select Template (Optional)</label>
                    <select id="templateSelect" onchange="fillTemplate()"
                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-whatsapp focus:border-transparent">
                        <option value="">-- Select a template --</option>
                        <?php foreach ($templatesByCategory as $category => $templates): ?>
                            <optgroup label="<?php echo ucfirst(htmlspecialchars($category)); ?>">
                                <?php foreach ($templates as $template): ?>
                                    <option value="<?php echo $template['id']; ?>"
                                            data-message="<?php echo htmlspecialchars($template['message']); ?>"
                                            data-variables="<?php echo htmlspecialchars($template['variables'] ?? ''); ?>">
                                        <?php echo htmlspecialchars($template['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </optgroup>
                        <?php endforeach; ?>
                    </select>
                    <?php if (!empty($whatsappTemplates)): ?>
                    <p class="text-xs text-gray-500 mt-1">
                        <?php echo count($whatsappTemplates); ?> template(s) available
                    </p>
                    <?php endif; ?>
                </div>
                
                <div>
                    <label class="block text-gray-700 font-medium mb-2">
                        Message <span class="text-red-500">*</span>
                        <span id="variableHint" class="text-xs text-gray-500 font-normal ml-2"></span>
                    </label>
                    <textarea name="custom_message" id="modalMessage" rows="6" required
                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-whatsapp focus:border-transparent"
                        placeholder="Type your message here or select a template above..."></textarea>
                    <p class="text-xs text-gray-500 mt-2">
                        Use variables like {{name}}, {{reference}}, {{amount}} in your message.
                    </p>
                </div>
                
                <div class="flex gap-3">
                    <button type="button" onclick="closeWhatsAppModal()"
                        class="flex-1 py-3 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition font-medium">
                        Cancel
                    </button>
                    <button type="submit"
                        class="flex-1 py-3 bg-whatsapp text-white rounded-lg hover:bg-green-600 transition font-semibold">
                        <i class="fab fa-whatsapp mr-2"></i>
                        Open WhatsApp
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Store user data for template variable replacement
        let currentUserName = '';
        
        function openWhatsAppModal(userId, userName, userPhone) {
            currentUserName = userName;
            document.getElementById('modalUserId').value = userId;
            document.getElementById('modalUserName').textContent = userName;
            document.getElementById('modalUserPhone').value = userPhone;
            document.getElementById('templateSelect').value = '';
            document.getElementById('modalMessage').value = '';
            document.getElementById('variableHint').textContent = '';
            document.getElementById('whatsappModal').classList.remove('hidden');
            document.getElementById('whatsappModal').classList.add('flex');
            document.getElementById('modalMessage').focus();
        }

        function closeWhatsAppModal() {
            document.getElementById('whatsappModal').classList.add('hidden');
            document.getElementById('whatsappModal').classList.remove('flex');
            document.getElementById('templateSelect').value = '';
            document.getElementById('variableHint').textContent = '';
        }

        function fillTemplate() {
            const select = document.getElementById('templateSelect');
            const messageArea = document.getElementById('modalMessage');
            const variableHint = document.getElementById('variableHint');
            
            if (select.value === '') {
                messageArea.value = '';
                variableHint.textContent = '';
                return;
            }
            
            const option = select.options[select.selectedIndex];
            let message = option.dataset.message;
            const variables = option.dataset.variables;
            
            // Replace {{name}} with the current user's name
            message = message.replace(/\{\{name\}\}/gi, currentUserName);
            
            messageArea.value = message;
            
            // Show variable hint if there are other variables
            if (variables) {
                const varsList = variables.split(',').map(v => '{{' + v.trim() + '}}').filter(v => v.toLowerCase() !== '{{name}}');
                if (varsList.length > 0) {
                    variableHint.textContent = 'Variables: ' + varsList.join(', ');
                } else {
                    variableHint.textContent = '';
                }
            }
        }

        // Bulk messaging functions
        let selectedBulkUsers = [];
        
        function toggleAllUsers(checkbox) {
            const checkboxes = document.querySelectorAll('.user-checkbox');
            checkboxes.forEach(cb => {
                cb.checked = checkbox.checked;
            });
            updateSelectedCount();
        }

        function updateSelectedCount() {
            const checkboxes = document.querySelectorAll('.user-checkbox:checked');
            document.getElementById('selectedCount').textContent = checkboxes.length;
            
            // Collect selected users
            selectedBulkUsers = [];
            checkboxes.forEach(cb => {
                selectedBulkUsers.push({
                    id: cb.value,
                    name: cb.dataset.name,
                    phone: cb.dataset.phone
                });
            });
        }

        function fillBulkTemplate() {
            const select = document.getElementById('bulkTemplateSelect');
            const preview = document.getElementById('bulkMessagePreview');
            
            if (select.value === '') {
                preview.value = '';
                return;
            }
            
            const option = select.options[select.selectedIndex];
            preview.value = option.dataset.message;
        }

        function openBulkPreviewModal() {
            if (selectedBulkUsers.length === 0) {
                alert('Please select at least one user to send a message to.');
                return;
            }
            
            const templateSelect = document.getElementById('bulkTemplateSelect');
            if (templateSelect.value === '') {
                alert('Please select a template.');
                return;
            }
            
            const option = templateSelect.options[templateSelect.selectedIndex];
            const message = option.dataset.message;
            
            // Update modal content
            document.getElementById('bulkSelectedCount').textContent = selectedBulkUsers.length;
            
            // Show recipients
            const recipientsList = document.getElementById('bulkRecipientsList');
            recipientsList.innerHTML = selectedBulkUsers.map(u =>
                `<span class="inline-block bg-white px-2 py-1 rounded border mr-2 mb-1">${u.name} (${u.phone})</span>`
            ).join('');
            
            // Show message
            document.getElementById('bulkMessageContent').textContent = message;
            
            // Show modal
            document.getElementById('bulkPreviewModal').classList.remove('hidden');
            document.getElementById('bulkPreviewModal').classList.add('flex');
        }

        function closeBulkPreviewModal() {
            document.getElementById('bulkPreviewModal').classList.add('hidden');
            document.getElementById('bulkPreviewModal').classList.remove('flex');
        }

        function sendBulkMessages() {
            if (selectedBulkUsers.length === 0) {
                alert('No users selected.');
                return;
            }
            
            const templateSelect = document.getElementById('bulkTemplateSelect');
            const option = templateSelect.options[templateSelect.selectedIndex];
            const message = option.dataset.message;
            
            // Get the first user's phone number
            const firstUser = selectedBulkUsers[0];
            
            // Create WhatsApp link for first user
            const encodedMessage = encodeURIComponent(message.replace(/\{\{name\}\}/gi, firstUser.name));
            const whatsappLink = `https://wa.me/${firstUser.phone}?text=${encodedMessage}`;
            
            // Open WhatsApp for first user
            window.open(whatsappLink, '_blank');
            
            // Show info about remaining users
            if (selectedBulkUsers.length > 1) {
                alert(`${selectedBulkUsers.length} recipients selected. WhatsApp has been opened for ${firstUser.name}.\n\nAfter sending that message, you can continue with the remaining ${selectedBulkUsers.length - 1} recipients.`);
                
                // Log remaining recipients to console for admin reference
                const remainingUsers = selectedBulkUsers.slice(1);
                const nextUsersList = remainingUsers.map(u => `${u.name}: ${u.phone}`).join('\n');
                console.log('Remaining recipients:\n' + nextUsersList);
            }
            
            closeBulkPreviewModal();
        }

        // Close modal on escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeWhatsAppModal();
                closeBulkPreviewModal();
            }
        });

        // Close modal on background click
        document.getElementById('whatsappModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeWhatsAppModal();
            }
        });

        document.getElementById('bulkPreviewModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeBulkPreviewModal();
            }
        });
    </script>
</body>
</html>
