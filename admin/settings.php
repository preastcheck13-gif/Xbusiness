<?php
/**
 * X Business Grant - Admin Settings
 * Manage Paystack API keys, site settings, and view users
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

// Handle Paystack settings update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'update_paystack') {
        $secretKey = trim($_POST['paystack_secret_key'] ?? '');
        $publicKey = trim($_POST['paystack_public_key'] ?? '');
        
        Settings::set('paystack_secret_key', $secretKey);
        Settings::set('paystack_public_key', $publicKey);
        
        $message = 'Paystack API keys updated successfully!';
        $messageType = 'success';
    } elseif ($_POST['action'] === 'test_paystack') {
        $testKey = trim($_POST['test_secret_key'] ?? '');
        if (empty($testKey)) {
            $testKey = Settings::getPaystackSecretKey();
        }
        
        $result = Settings::testPaystackConnection($testKey);
        if ($result['success']) {
            $message = '✓ ' . $result['message'] . ' (Found ' . $result['bank_count'] . ' banks)';
            $messageType = 'success';
        } else {
            $message = '✗ ' . $result['message'];
            $messageType = 'error';
        }
    } elseif ($_POST['action'] === 'update_site') {
        Settings::set('site_name', trim($_POST['site_name'] ?? ''));
        Settings::set('site_email', trim($_POST['site_email'] ?? ''));
        Settings::set('grant_min_amount', trim($_POST['grant_min_amount'] ?? '100000'));
        Settings::set('grant_max_amount', trim($_POST['grant_max_amount'] ?? '5000000'));
        
        $message = 'Site settings updated successfully!';
        $messageType = 'success';
    } elseif ($_POST['action'] === 'delete_user') {
        $userId = (int)($_POST['user_id'] ?? 0);
        if ($userId > 0) {
            // Get user email to delete their applications first
            $user = $db->fetchOne("SELECT email FROM users WHERE id = ?", [$userId]);
            if ($user) {
                // Delete user's applications and related documents
                $userApps = $db->fetchAll("SELECT id FROM applications WHERE email = ?", [$user['email']]);
                foreach ($userApps as $app) {
                    // Delete document files
                    $docs = $db->fetchAll("SELECT file_path FROM documents WHERE application_id = ?", [$app['id']]);
                    foreach ($docs as $doc) {
                        $filePath = __DIR__ . '/../uploads/' . $doc['file_path'];
                        if (file_exists($filePath)) {
                            unlink($filePath);
                        }
                    }
                    $db->query("DELETE FROM documents WHERE application_id = ?", [$app['id']]);
                }
                $db->query("DELETE FROM applications WHERE email = ?", [$user['email']]);
                // Delete user
                $db->query("DELETE FROM users WHERE id = ?", [$userId]);
                $message = 'User and associated applications deleted successfully!';
                $messageType = 'success';
            }
        }
    }
}

// Get current settings
$paystackSecretKey = Settings::getPaystackSecretKey();
$paystackPublicKey = Settings::getPaystackPublicKey();
$siteName = Settings::get('site_name', 'X Business Grant');
$siteEmail = Settings::get('site_email', 'support@xbusinessgrant.ng');
$grantMinAmount = Settings::get('grant_min_amount', '100000');
$grantMaxAmount = Settings::get('grant_max_amount', '5000000');

// Get all users (applicants)
$users = $db->fetchAll("SELECT * FROM users ORDER BY created_at DESC LIMIT 50");
$userCount = $db->fetchOne("SELECT COUNT(*) as count FROM users")['count'];

$currentPage = basename($_SERVER['PHP_SELF'], '.php');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings - X Business Grant Admin</title>
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
                    <a href="smtp_settings.php" class="flex items-center space-x-3 px-4 py-3 rounded-lg hover:bg-gray-800 transition">
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
                    <h1 class="text-2xl font-bold text-gray-800">Settings</h1>
                    <p class="text-gray-600">Manage Paystack API, site settings, and users</p>
                </div>
            </div>

            <?php if ($message): ?>
            <div class="mb-6 p-4 rounded-lg <?php echo $messageType === 'success' ? 'bg-green-50 border-green-200 text-green-800' : 'bg-red-50 border-red-200 text-red-800'; ?> border">
                <p><i class="fas fa-<?php echo $messageType === 'success' ? 'check-circle' : 'exclamation-circle'; ?> mr-2"></i><?php echo htmlspecialchars($message); ?></p>
            </div>
            <?php endif; ?>

            <!-- Tab Navigation -->
            <div class="flex space-x-4 mb-6 border-b border-gray-200">
                <button onclick="showTab('paystack')" id="tab-paystack" class="pb-4 px-2 font-medium text-primary border-b-2 border-primary">
                    <i class="fas fa-credit-card mr-2"></i>Paystack API
                </button>
                <button onclick="showTab('site')" id="tab-site" class="pb-4 px-2 font-medium text-gray-500 hover:text-gray-700">
                    <i class="fas fa-globe mr-2"></i>Site Settings
                </button>
                <button onclick="showTab('users')" id="tab-users" class="pb-4 px-2 font-medium text-gray-500 hover:text-gray-700">
                    <i class="fas fa-users mr-2"></i>Users (<?php echo $userCount; ?>)
                </button>
            </div>

            <!-- Paystack Tab -->
            <div id="content-paystack" class="tab-content">
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <!-- Paystack Configuration -->
                    <div class="bg-white rounded-xl shadow-sm p-6">
                        <h3 class="text-lg font-bold text-gray-800 mb-4">
                            <i class="fas fa-key text-primary mr-2"></i>Paystack API Configuration
                        </h3>
                        <p class="text-gray-500 text-sm mb-6">Enter your Paystack API keys. Get them from your Paystack dashboard.</p>
                        
                        <form method="POST" class="space-y-4">
                            <input type="hidden" name="action" value="update_paystack">
                            
                            <div>
                                <label class="block text-gray-700 font-medium mb-2">
                                    Secret Key <span class="text-red-500">*</span>
                                </label>
                                <div class="relative">
                                    <input type="password" name="paystack_secret_key" id="secret_key" required
                                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent pr-20"
                                        placeholder="sk_live_xxxxxxxxxxxxxxxxxxxxxxxx"
                                        value="<?php echo htmlspecialchars($paystackSecretKey); ?>">
                                    <button type="button" onclick="togglePassword('secret_key')"
                                        class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600">
                                        <i class="fas fa-eye" id="secret_key_icon"></i>
                                    </button>
                                </div>
                                <p class="text-xs text-gray-500 mt-1">Used for server-side API calls (bank verification)</p>
                            </div>
                            
                            <div>
                                <label class="block text-gray-700 font-medium mb-2">
                                    Public Key
                                </label>
                                <div class="relative">
                                    <input type="password" name="paystack_public_key" id="public_key"
                                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent pr-20"
                                        placeholder="pk_live_xxxxxxxxxxxxxxxxxxxxxxxx"
                                        value="<?php echo htmlspecialchars($paystackPublicKey); ?>">
                                    <button type="button" onclick="togglePassword('public_key')"
                                        class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600">
                                        <i class="fas fa-eye" id="public_key_icon"></i>
                                    </button>
                                </div>
                                <p class="text-xs text-gray-500 mt-1">Used for client-side JavaScript SDK (optional)</p>
                            </div>
                            
                            <div class="pt-4">
                                <button type="submit" class="w-full py-3 bg-primary text-white rounded-lg hover:bg-blue-700 transition font-semibold">
                                    <i class="fas fa-save mr-2"></i>Save Paystack Settings
                                </button>
                            </div>
                        </form>
                    </div>

                    <!-- Test Connection -->
                    <div class="bg-white rounded-xl shadow-sm p-6">
                        <h3 class="text-lg font-bold text-gray-800 mb-4">
                            <i class="fas fa-plug text-green-600 mr-2"></i>Test Connection
                        </h3>
                        <p class="text-gray-500 text-sm mb-6">Verify that your Paystack API key is working correctly.</p>
                        
                        <form method="POST" class="space-y-4">
                            <input type="hidden" name="action" value="test_paystack">
                            
                            <div>
                                <label class="block text-gray-700 font-medium mb-2">
                                    Test with Key (optional)
                                </label>
                                <input type="text" name="test_secret_key"
                                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent"
                                    placeholder="Leave empty to test saved key"
                                    value="">
                                <p class="text-xs text-gray-500 mt-1">Enter a different key to test, or leave empty to test the saved key</p>
                            </div>
                            
                            <div class="bg-gray-50 rounded-lg p-4">
                                <h4 class="font-medium text-gray-700 mb-2">What this test does:</h4>
                                <ul class="text-sm text-gray-600 space-y-1">
                                    <li><i class="fas fa-check text-green-500 mr-2"></i>Connects to Paystack API</li>
                                    <li><i class="fas fa-check text-green-500 mr-2"></i>Verifies authentication</li>
                                    <li><i class="fas fa-check text-green-500 mr-2"></i>Retrieves list of Nigerian banks</li>
                                </ul>
                            </div>
                            
                            <button type="submit" class="w-full py-3 bg-green-600 text-white rounded-lg hover:bg-green-700 transition font-semibold">
                                <i class="fas fa-satellite-dish mr-2"></i>Test Connection
                            </button>
                        </form>
                        
                        <!-- Connection Status -->
                        <div class="mt-6 border-t pt-4">
                            <h4 class="font-medium text-gray-700 mb-2">Current Status:</h4>
                            <?php if (!empty($paystackSecretKey)): ?>
                                <div class="flex items-center text-green-600">
                                    <i class="fas fa-check-circle mr-2"></i>
                                    <span>Secret Key is configured</span>
                                </div>
                                <p class="text-xs text-gray-500 mt-1">
                                    Key: <?php echo substr($paystackSecretKey, 0, 10) . '...' . substr($paystackSecretKey, -5); ?>
                                </p>
                            <?php else: ?>
                                <div class="flex items-center text-red-600">
                                    <i class="fas fa-exclamation-circle mr-2"></i>
                                    <span>Paystack not configured</span>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <!-- API Documentation Link -->
                <div class="mt-6 bg-blue-50 border border-blue-200 rounded-xl p-4">
                    <div class="flex items-start">
                        <i class="fas fa-info-circle text-blue-600 mt-1 mr-3"></i>
                        <div>
                            <h4 class="font-bold text-gray-800">Paystack API Documentation</h4>
                            <p class="text-gray-600 text-sm mt-1">
                                Need help? <a href="https://paystack.com/docs/api" target="_blank" class="text-primary hover:underline">View Paystack API Documentation</a>
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Site Settings Tab -->
            <div id="content-site" class="tab-content hidden">
                <div class="bg-white rounded-xl shadow-sm p-6 max-w-2xl">
                    <h3 class="text-lg font-bold text-gray-800 mb-4">
                        <i class="fas fa-globe text-primary mr-2"></i>Site Settings
                    </h3>
                    
                    <form method="POST" class="space-y-4">
                        <input type="hidden" name="action" value="update_site">
                        
                        <div>
                            <label class="block text-gray-700 font-medium mb-2">Site Name</label>
                            <input type="text" name="site_name" required
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent"
                                placeholder="X Business Grant"
                                value="<?php echo htmlspecialchars($siteName); ?>">
                        </div>
                        
                        <div>
                            <label class="block text-gray-700 font-medium mb-2">Support Email</label>
                            <input type="email" name="site_email" required
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent"
                                placeholder="support@example.com"
                                value="<?php echo htmlspecialchars($siteEmail); ?>">
                        </div>
                        
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-gray-700 font-medium mb-2">Minimum Grant Amount (₦)</label>
                                <input type="number" name="grant_min_amount" required
                                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent"
                                    placeholder="100000"
                                    value="<?php echo htmlspecialchars($grantMinAmount); ?>">
                            </div>
                            <div>
                                <label class="block text-gray-700 font-medium mb-2">Maximum Grant Amount (₦)</label>
                                <input type="number" name="grant_max_amount" required
                                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent"
                                    placeholder="5000000"
                                    value="<?php echo htmlspecialchars($grantMaxAmount); ?>">
                            </div>
                        </div>
                        
                        <div class="pt-4">
                            <button type="submit" class="px-6 py-3 bg-primary text-white rounded-lg hover:bg-blue-700 transition font-semibold">
                                <i class="fas fa-save mr-2"></i>Save Settings
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Users Tab -->
            <div id="content-users" class="tab-content hidden">
                <div class="bg-white rounded-xl shadow-sm overflow-hidden">
                    <div class="p-6 border-b border-gray-200">
                        <h3 class="text-lg font-bold text-gray-800">
                            <i class="fas fa-users text-primary mr-2"></i>Registered Users
                        </h3>
                        <p class="text-gray-500 text-sm mt-1">Users who have registered to apply for grants</p>
                    </div>
                    
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="text-left py-3 px-4 font-semibold text-gray-600">Name</th>
                                    <th class="text-left py-3 px-4 font-semibold text-gray-600">Email</th>
                                    <th class="text-left py-3 px-4 font-semibold text-gray-600">Phone</th>
                                    <th class="text-left py-3 px-4 font-semibold text-gray-600">Applications</th>
                                    <th class="text-left py-3 px-4 font-semibold text-gray-600">Joined</th>
                                    <th class="text-left py-3 px-4 font-semibold text-gray-600">Last Login</th>
                                    <th class="text-left py-3 px-4 font-semibold text-gray-600">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($users)): ?>
                                    <?php foreach ($users as $user): ?>
                                    <?php
                                        $appCount = $db->fetchOne(
                                            "SELECT COUNT(*) as count FROM applications WHERE email = ?",
                                            [$user['email']]
                                        )['count'];
                                    ?>
                                    <tr class="border-b border-gray-100 hover:bg-gray-50">
                                        <td class="py-3 px-4">
                                            <div class="flex items-center">
                                                <div class="w-8 h-8 bg-primary rounded-full flex items-center justify-center mr-3">
                                                    <span class="text-white font-bold text-sm"><?php echo strtoupper(substr($user['full_name'], 0, 1)); ?></span>
                                                </div>
                                                <span class="font-medium"><?php echo htmlspecialchars($user['full_name']); ?></span>
                                            </div>
                                        </td>
                                        <td class="py-3 px-4 text-sm"><?php echo htmlspecialchars($user['email']); ?></td>
                                        <td class="py-3 px-4 text-sm"><?php echo htmlspecialchars($user['phone']); ?></td>
                                        <td class="py-3 px-4">
                                            <span class="px-2 py-1 bg-primary/10 text-primary rounded text-sm font-medium">
                                                <?php echo $appCount; ?> application<?php echo $appCount !== 1 ? 's' : ''; ?>
                                            </span>
                                        </td>
                                        <td class="py-3 px-4 text-sm text-gray-500">
                                            <?php echo date('M d, Y', strtotime($user['created_at'])); ?>
                                        </td>
                                        <td class="py-3 px-4 text-sm text-gray-500">
                                            <?php echo $user['last_login'] ? date('M d, Y H:i', strtotime($user['last_login'])) : 'Never'; ?>
                                        </td>
                                        <td class="py-3 px-4">
                                            <button type="button" onclick="confirmDeleteUser(<?php echo $user['id']; ?>, '<?php echo htmlspecialchars($user['full_name']); ?>')"
                                                class="p-2 text-red-600 hover:bg-red-50 rounded-lg" title="Delete User">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="7" class="py-12 text-center text-gray-500">
                                            <i class="fas fa-users text-4xl mb-4"></i>
                                            <p>No users registered yet</p>
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script>
        function showTab(tabName) {
            // Hide all tab contents
            document.querySelectorAll('.tab-content').forEach(el => el.classList.add('hidden'));
            
            // Show selected tab content
            document.getElementById('content-' + tabName).classList.remove('hidden');
            
            // Update tab button styles
            document.querySelectorAll('[id^="tab-"]').forEach(el => {
                el.classList.remove('text-primary', 'border-b-2', 'border-primary');
                el.classList.add('text-gray-500');
            });
            
            document.getElementById('tab-' + tabName).classList.remove('text-gray-500');
            document.getElementById('tab-' + tabName).classList.add('text-primary', 'border-b-2', 'border-primary');
        }
        
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

        function confirmDeleteUser(userId, userName) {
            if (confirm('Are you sure you want to delete user "' + userName + '"? This will also delete all their applications and uploaded documents. This action cannot be undone.')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = '<input type="hidden" name="action" value="delete_user"><input type="hidden" name="user_id" value="' + userId + '">';
                document.body.appendChild(form);
                form.submit();
            }
        }
    </script>
</body>
</html>
