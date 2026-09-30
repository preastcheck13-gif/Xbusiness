<?php
/**
 * X Business Grant - Admin Dashboard
 * Application Management Portal
 */

session_start();

require_once __DIR__ . '/../includes/Database.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/Application.php';

use App\Helpers;
use App\Application;

// Simple auth check (replace with proper authentication in production)
$isLoggedIn = isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;
$currentPage = basename($_SERVER['PHP_SELF'], '.php');

if (!$isLoggedIn && $currentPage !== 'login') {
    header('Location: login.php');
    exit;
}

$app = new Application();
$stats = $app->getStats();

// Handle status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_status') {
    $id = (int)$_POST['application_id'];
    $status = $_POST['status'];
    $notes = $_POST['admin_notes'] ?? null;
    
    $app->updateStatus($id, $status, $notes);
    header('Location: index.php?success=1');
    exit;
}

// Get applications with pagination
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$filters = [];

if (isset($_GET['status']) && !empty($_GET['status'])) {
    $filters['status'] = $_GET['status'];
}
if (isset($_GET['state']) && !empty($_GET['state'])) {
    $filters['state'] = $_GET['state'];
}
if (isset($_GET['sector']) && !empty($_GET['sector'])) {
    $filters['sector'] = $_GET['sector'];
}
if (isset($_GET['search']) && !empty($_GET['search'])) {
    $filters['search'] = $_GET['search'];
}

$result = $app->getAll($filters, $page);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - X Business Grant</title>
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
    <!-- Sidebar -->
    <div class="flex">
        <aside class="fixed left-0 top-0 h-screen w-64 bg-gray-900 text-white">
            <div class="p-6">
                <div class="flex items-center space-x-2 mb-8">
                    <div class="w-10 h-10 bg-primary rounded-lg flex items-center justify-center">
                        <span class="text-white font-bold text-xl">X</span>
                    </div>
                    <span class="text-xl font-bold">Business Grant</span>
                </div>
                <nav class="space-y-2">
                    <a href="index.php" class="flex items-center space-x-3 px-4 py-3 rounded-lg <?php echo $currentPage === 'index' ? 'bg-primary' : 'hover:bg-gray-800'; ?> transition">
                        <i class="fas fa-tachometer-alt"></i>
                        <span>Dashboard</span>
                    </a>
                    <a href="applications.php" class="flex items-center space-x-3 px-4 py-3 rounded-lg <?php echo $currentPage === 'applications' ? 'bg-primary' : 'hover:bg-gray-800'; ?> transition">
                        <i class="fas fa-file-alt"></i>
                        <span>Applications</span>
                    </a>
                    <a href="reports.php" class="flex items-center space-x-3 px-4 py-3 rounded-lg <?php echo $currentPage === 'reports' ? 'bg-primary' : 'hover:bg-gray-800'; ?> transition">
                        <i class="fas fa-chart-bar"></i>
                        <span>Reports</span>
                    </a>
                    <a href="documents.php" class="flex items-center space-x-3 px-4 py-3 rounded-lg <?php echo $currentPage === 'documents' ? 'bg-primary' : 'hover:bg-gray-800'; ?> transition">
                        <i class="fas fa-folder-open"></i>
                        <span>Documents</span>
                    </a>
                    <a href="settings.php" class="flex items-center space-x-3 px-4 py-3 rounded-lg <?php echo $currentPage === 'settings' ? 'bg-primary' : 'hover:bg-gray-800'; ?> transition">
                        <i class="fas fa-cog"></i>
                        <span>Settings</span>
                    </a>
                    <a href="smtp_settings.php" class="flex items-center space-x-3 px-4 py-3 rounded-lg <?php echo $currentPage === 'smtp_settings' ? 'bg-primary' : 'hover:bg-gray-800'; ?> transition">
                        <i class="fas fa-envelope"></i>
                        <span>SMTP Settings</span>
                    </a>
                    <a href="whatsapp.php" class="flex items-center space-x-3 px-4 py-3 rounded-lg <?php echo $currentPage === 'whatsapp' ? 'bg-primary' : 'hover:bg-gray-800'; ?> transition">
                        <i class="fab fa-whatsapp"></i>
                        <span>WhatsApp</span>
                    </a>
                    <a href="emails.php" class="flex items-center space-x-3 px-4 py-3 rounded-lg <?php echo $currentPage === 'emails' ? 'bg-primary' : 'hover:bg-gray-800'; ?> transition">
                        <i class="fas fa-paper-plane"></i>
                        <span>Email Management</span>
                    </a>
                    <a href="users.php" class="flex items-center space-x-3 px-4 py-3 rounded-lg <?php echo $currentPage === 'users' ? 'bg-primary' : 'hover:bg-gray-800'; ?> transition">
                        <i class="fas fa-user-shield"></i>
                        <span>Admin Users</span>
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
            <!-- Header -->
            <div class="flex justify-between items-center mb-8">
                <div>
                    <h1 class="text-2xl font-bold text-gray-800">Dashboard</h1>
                    <p class="text-gray-600">Welcome back, Administrator</p>
                </div>
                <div class="flex items-center space-x-4">
                    <span class="text-gray-500"><i class="fas fa-calendar mr-2"></i><?php echo date('F d, Y'); ?></span>
                </div>
            </div>

            <?php if (isset($_GET['success'])): ?>
            <div class="bg-green-50 border border-green-200 rounded-lg p-4 mb-6">
                <p class="text-green-700"><i class="fas fa-check-circle mr-2"></i>Status updated successfully!</p>
            </div>
            <?php endif; ?>

            <!-- Stats Cards -->
            <div class="grid grid-cols-1 md:grid-cols-5 gap-6 mb-8">
                <div class="bg-white rounded-xl shadow-sm p-6 border-l-4 border-blue-500">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-gray-500 text-sm">Total Applications</p>
                            <p class="text-3xl font-bold text-gray-800"><?php echo $stats['total']; ?></p>
                        </div>
                        <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center">
                            <i class="fas fa-file-alt text-blue-600 text-xl"></i>
                        </div>
                    </div>
                </div>
                <div class="bg-white rounded-xl shadow-sm p-6 border-l-4 border-yellow-500">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-gray-500 text-sm">Pending</p>
                            <p class="text-3xl font-bold text-yellow-600"><?php echo $stats['pending']; ?></p>
                        </div>
                        <div class="w-12 h-12 bg-yellow-100 rounded-lg flex items-center justify-center">
                            <i class="fas fa-clock text-yellow-600 text-xl"></i>
                        </div>
                    </div>
                </div>
                <div class="bg-white rounded-xl shadow-sm p-6 border-l-4 border-blue-400">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-gray-500 text-sm">Under Review</p>
                            <p class="text-3xl font-bold text-blue-500"><?php echo $stats['under_review']; ?></p>
                        </div>
                        <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center">
                            <i class="fas fa-search text-blue-500 text-xl"></i>
                        </div>
                    </div>
                </div>
                <div class="bg-white rounded-xl shadow-sm p-6 border-l-4 border-green-500">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-gray-500 text-sm">Approved</p>
                            <p class="text-3xl font-bold text-green-600"><?php echo $stats['approved']; ?></p>
                        </div>
                        <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center">
                            <i class="fas fa-check-circle text-green-600 text-xl"></i>
                        </div>
                    </div>
                </div>
                <div class="bg-white rounded-xl shadow-sm p-6 border-l-4 border-red-500">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-gray-500 text-sm">Rejected</p>
                            <p class="text-3xl font-bold text-red-600"><?php echo $stats['rejected']; ?></p>
                        </div>
                        <div class="w-12 h-12 bg-red-100 rounded-lg flex items-center justify-center">
                            <i class="fas fa-times-circle text-red-600 text-xl"></i>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Approved Amount -->
            <div class="bg-gradient-to-r from-green-500 to-green-600 rounded-xl shadow-lg p-6 mb-8 text-white">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-green-100 text-sm">Total Approved Grant Amount</p>
                        <p class="text-4xl font-bold"><?php echo Helpers::formatNaira($stats['total_grant_amount']); ?></p>
                    </div>
                    <div class="w-16 h-16 bg-white/20 rounded-lg flex items-center justify-center">
                        <i class="fas fa-naira-sign text-3xl"></i>
                    </div>
                </div>
            </div>

            <!-- Recent Applications -->
            <div class="bg-white rounded-xl shadow-sm p-6">
                <div class="flex justify-between items-center mb-6">
                    <h2 class="text-xl font-bold text-gray-800">Recent Applications</h2>
                    <a href="applications.php" class="text-primary hover:underline">View All</a>
                </div>

                <!-- Filters -->
                <form method="GET" class="mb-6 flex flex-wrap gap-4">
                    <input type="text" name="search" placeholder="Search by ref, business name, CAC..." 
                        value="<?php echo htmlspecialchars($_GET['search'] ?? ''); ?>"
                        class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
                    <select name="status" class="px-4 py-2 border border-gray-300 rounded-lg">
                        <option value="">All Status</option>
                        <option value="pending" <?php echo ($_GET['status'] ?? '') === 'pending' ? 'selected' : ''; ?>>Pending</option>
                        <option value="under_review" <?php echo ($_GET['status'] ?? '') === 'under_review' ? 'selected' : ''; ?>>Under Review</option>
                        <option value="approved" <?php echo ($_GET['status'] ?? '') === 'approved' ? 'selected' : ''; ?>>Approved</option>
                        <option value="rejected" <?php echo ($_GET['status'] ?? '') === 'rejected' ? 'selected' : ''; ?>>Rejected</option>
                    </select>
                    <select name="state" class="px-4 py-2 border border-gray-300 rounded-lg">
                        <option value="">All States</option>
                        <?php foreach (Helpers::getNigerianStates() as $state): ?>
                            <option value="<?php echo $state; ?>" <?php echo ($_GET['state'] ?? '') === $state ? 'selected' : ''; ?>>
                                <?php echo $state; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <button type="submit" class="px-4 py-2 bg-primary text-white rounded-lg hover:bg-blue-700 transition">
                        <i class="fas fa-filter mr-2"></i>Filter
                    </button>
                </form>

                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead>
                            <tr class="border-b border-gray-200">
                                <th class="text-left py-3 px-4 font-semibold text-gray-600">Reference</th>
                                <th class="text-left py-3 px-4 font-semibold text-gray-600">Business Name</th>
                                <th class="text-left py-3 px-4 font-semibold text-gray-600">Sector</th>
                                <th class="text-left py-3 px-4 font-semibold text-gray-600">State</th>
                                <th class="text-left py-3 px-4 font-semibold text-gray-600">Amount</th>
                                <th class="text-left py-3 px-4 font-semibold text-gray-600">Status</th>
                                <th class="text-left py-3 px-4 font-semibold text-gray-600">Date</th>
                                <th class="text-left py-3 px-4 font-semibold text-gray-600">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($result['data'] as $application): ?>
                            <tr class="border-b border-gray-100 hover:bg-gray-50">
                                <td class="py-3 px-4 font-mono text-sm"><?php echo htmlspecialchars($application['reference_no']); ?></td>
                                <td class="py-3 px-4">
                                    <div class="font-medium text-gray-800"><?php echo htmlspecialchars($application['business_name']); ?></div>
                                    <div class="text-sm text-gray-500"><?php echo htmlspecialchars($application['cac_number']); ?></div>
                                </td>
                                <td class="py-3 px-4 text-sm"><?php echo htmlspecialchars($application['business_sector']); ?></td>
                                <td class="py-3 px-4 text-sm"><?php echo htmlspecialchars($application['state']); ?></td>
                                <td class="py-3 px-4 font-medium"><?php echo Helpers::formatNaira($application['grant_amount_requested']); ?></td>
                                <td class="py-3 px-4">
                                    <span class="px-3 py-1 rounded-full text-xs font-medium <?php echo Helpers::getStatusClass($application['status']); ?>">
                                        <?php echo ucfirst(str_replace('_', ' ', $application['status'])); ?>
                                    </span>
                                </td>
                                <td class="py-3 px-4 text-sm text-gray-500">
                                    <?php echo date('M d, Y', strtotime($application['created_at'])); ?>
                                </td>
                                <td class="py-3 px-4">
                                    <div class="flex space-x-2">
                                        <button onclick="viewApplication(<?php echo $application['id']; ?>)" 
                                            class="p-2 text-blue-600 hover:bg-blue-50 rounded-lg transition" title="View">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        <button onclick="updateStatus(<?php echo $application['id']; ?>, '<?php echo $application['status']; ?>')" 
                                            class="p-2 text-green-600 hover:bg-green-50 rounded-lg transition" title="Update Status">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if (empty($result['data'])): ?>
                            <tr>
                                <td colspan="8" class="py-8 text-center text-gray-500">No applications found</td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <?php if ($result['total_pages'] > 1): ?>
                <div class="flex justify-center mt-6 space-x-2">
                    <?php for ($i = 1; $i <= $result['total_pages']; $i++): ?>
                        <a href="?page=<?php echo $i; ?><?php echo isset($_GET['status']) ? '&status=' . htmlspecialchars($_GET['status']) : ''; ?><?php echo isset($_GET['search']) ? '&search=' . htmlspecialchars($_GET['search']) : ''; ?>" 
                            class="px-4 py-2 rounded-lg <?php echo $i === $page ? 'bg-primary text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200'; ?> transition">
                            <?php echo $i; ?>
                        </a>
                    <?php endfor; ?>
                </div>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <!-- Status Update Modal -->
    <div id="statusModal" class="fixed inset-0 bg-black/50 hidden items-center justify-center z-50">
        <div class="bg-white rounded-xl p-6 w-full max-w-md mx-4">
            <h3 class="text-xl font-bold text-gray-800 mb-4">Update Application Status</h3>
            <form method="POST">
                <input type="hidden" name="action" value="update_status">
                <input type="hidden" name="application_id" id="modal_app_id">
                <div class="mb-4">
                    <label class="block text-gray-700 font-medium mb-2">New Status</label>
                    <select name="status" id="modal_status" required
                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
                        <option value="pending">Pending</option>
                        <option value="under_review">Under Review</option>
                        <option value="approved">Approved</option>
                        <option value="rejected">Rejected</option>
                    </select>
                </div>
                <div class="mb-6">
                    <label class="block text-gray-700 font-medium mb-2">Admin Notes</label>
                    <textarea name="admin_notes" id="modal_notes" rows="3"
                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent"
                        placeholder="Add notes about this status change..."></textarea>
                </div>
                <div class="flex justify-end space-x-3">
                    <button type="button" onclick="closeModal()" 
                        class="px-6 py-2 border border-gray-300 rounded-lg text-gray-600 hover:bg-gray-50 transition">
                        Cancel
                    </button>
                    <button type="submit" 
                        class="px-6 py-2 bg-primary text-white rounded-lg hover:bg-blue-700 transition">
                        Update Status
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- View Application Modal -->
    <div id="viewModal" class="fixed inset-0 bg-black/50 hidden items-center justify-center z-50 overflow-y-auto">
        <div class="bg-white rounded-xl p-6 w-full max-w-2xl mx-4 my-8">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-xl font-bold text-gray-800">Application Details</h3>
                <button onclick="closeViewModal()" class="text-gray-500 hover:text-gray-700">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div id="viewModalContent">
                <!-- Content loaded via AJAX -->
            </div>
        </div>
    </div>

    <script>
        function updateStatus(id, currentStatus) {
            document.getElementById('modal_app_id').value = id;
            document.getElementById('modal_status').value = currentStatus;
            document.getElementById('statusModal').classList.remove('hidden');
            document.getElementById('statusModal').classList.add('flex');
        }

        function closeModal() {
            document.getElementById('statusModal').classList.add('hidden');
            document.getElementById('statusModal').classList.remove('flex');
        }

        function viewApplication(id) {
            document.getElementById('viewModalContent').innerHTML = '<div class="text-center py-8"><i class="fas fa-spinner fa-spin text-2xl text-primary"></i></div>';
            document.getElementById('viewModal').classList.remove('hidden');
            document.getElementById('viewModal').classList.add('flex');
            
            // In production, this would be an AJAX call
            fetch('get_application.php?id=' + id)
                .then(response => response.text())
                .then(html => {
                    document.getElementById('viewModalContent').innerHTML = html;
                })
                .catch(error => {
                    document.getElementById('viewModalContent').innerHTML = '<p class="text-red-500">Error loading application details</p>';
                });
        }

        function closeViewModal() {
            document.getElementById('viewModal').classList.add('hidden');
            document.getElementById('viewModal').classList.remove('flex');
        }

        // Close modals on outside click
        document.getElementById('statusModal').addEventListener('click', function(e) {
            if (e.target === this) closeModal();
        });
        document.getElementById('viewModal').addEventListener('click', function(e) {
            if (e.target === this) closeViewModal();
        });
    </script>
</body>
</html>
