<?php
/**
 * X Business Grant - Applications List
 */

session_start();

require_once __DIR__ . '/../includes/Database.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/Application.php';

use App\Helpers;
use App\Application;

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

$app = new Application();

// Handle single delete
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_single'])) {
    $id = (int)($_POST['application_id'] ?? 0);
    if ($id > 0) {
        $app->delete($id);
        header('Location: applications.php?delete_success=1');
        exit;
    }
}

// Handle bulk actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['bulk_action'])) {
    $ids = $_POST['selected_ids'] ?? [];
    $status = $_POST['bulk_status'] ?? '';
    $bulk_delete = $_POST['bulk_delete'] ?? '';
    
    if ($bulk_delete === '1' && !empty($ids)) {
        // Bulk delete
        $count = $app->bulkDelete($ids);
        header('Location: applications.php?bulk_delete_success=' . $count);
        exit;
    } elseif (!empty($ids) && !empty($status)) {
        // Bulk status update
        foreach ($ids as $id) {
            $app->updateStatus((int)$id, $status);
        }
        header('Location: applications.php?bulk_success=1');
        exit;
    }
}

// Get applications with filters
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

$result = $app->getAll($filters, $page, 50);
$stats = $app->getStats();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Applications - X Business Grant Admin</title>
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
                    <a href="applications.php" class="flex items-center space-x-3 px-4 py-3 rounded-lg bg-primary transition">
                        <i class="fas fa-file-alt"></i>
                        <span>Applications</span>
                    </a>
                    <a href="reports.php" class="flex items-center space-x-3 px-4 py-3 rounded-lg hover:bg-gray-800 transition">
                        <i class="fas fa-chart-bar"></i>
                        <span>Reports</span>
                    </a>
                    <a href="documents.php" class="flex items-center space-x-3 px-4 py-3 rounded-lg hover:bg-gray-800 transition">
                        <i class="fas fa-folder-open"></i>
                        <span>Documents</span>
                    </a>
                    <a href="settings.php" class="flex items-center space-x-3 px-4 py-3 rounded-lg hover:bg-gray-800 transition">
                        <i class="fas fa-cog"></i>
                        <span>Settings</span>
                    </a>
                    <a href="emails.php" class="flex items-center space-x-3 px-4 py-3 rounded-lg hover:bg-gray-800 transition">
                        <i class="fas fa-paper-plane"></i>
                        <span>Email Management</span>
                    </a>
                    <a href="users.php" class="flex items-center space-x-3 px-4 py-3 rounded-lg hover:bg-gray-800 transition">
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
            <div class="flex justify-between items-center mb-8">
                <div>
                    <h1 class="text-2xl font-bold text-gray-800">Applications</h1>
                    <p class="text-gray-600">Manage all grant applications</p>
                </div>
                <div class="flex items-center space-x-4">
                    <span class="text-gray-500"><i class="fas fa-calendar mr-2"></i><?php echo date('F d, Y'); ?></span>
                </div>
            </div>

            <?php if (isset($_GET['bulk_success'])): ?>
            <div class="bg-green-50 border border-green-200 rounded-lg p-4 mb-6">
                <p class="text-green-700"><i class="fas fa-check-circle mr-2"></i>Bulk status updated successfully!</p>
            </div>
            <?php endif; ?>

            <?php if (isset($_GET['delete_success'])): ?>
            <div class="bg-green-50 border border-green-200 rounded-lg p-4 mb-6">
                <p class="text-green-700"><i class="fas fa-check-circle mr-2"></i>Application deleted successfully!</p>
            </div>
            <?php endif; ?>

            <?php if (isset($_GET['bulk_delete_success'])): ?>
            <div class="bg-green-50 border border-green-200 rounded-lg p-4 mb-6">
                <p class="text-green-700"><i class="fas fa-check-circle mr-2"></i><?php echo (int)$_GET['bulk_delete_success']; ?> application(s) deleted successfully!</p>
            </div>
            <?php endif; ?>

            <!-- Quick Stats -->
            <div class="grid grid-cols-4 gap-4 mb-8">
                <div class="bg-white rounded-lg p-4 border-l-4 border-blue-500">
                    <p class="text-gray-500 text-sm">Total</p>
                    <p class="text-2xl font-bold"><?php echo $stats['total']; ?></p>
                </div>
                <div class="bg-white rounded-lg p-4 border-l-4 border-yellow-500">
                    <p class="text-gray-500 text-sm">Pending</p>
                    <p class="text-2xl font-bold text-yellow-600"><?php echo $stats['pending']; ?></p>
                </div>
                <div class="bg-white rounded-lg p-4 border-l-4 border-green-500">
                    <p class="text-gray-500 text-sm">Approved</p>
                    <p class="text-2xl font-bold text-green-600"><?php echo $stats['approved']; ?></p>
                </div>
                <div class="bg-white rounded-lg p-4 border-l-4 border-red-500">
                    <p class="text-gray-500 text-sm">Rejected</p>
                    <p class="text-2xl font-bold text-red-600"><?php echo $stats['rejected']; ?></p>
                </div>
            </div>

            <!-- Filters -->
            <div class="bg-white rounded-xl shadow-sm p-6 mb-6">
                <form method="GET" class="grid grid-cols-5 gap-4">
                    <div>
                        <label class="block text-gray-600 text-sm mb-1">Search</label>
                        <input type="text" name="search" placeholder="Ref, business, CAC..." 
                            value="<?php echo htmlspecialchars($_GET['search'] ?? ''); ?>"
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                    </div>
                    <div>
                        <label class="block text-gray-600 text-sm mb-1">Status</label>
                        <select name="status" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                            <option value="">All</option>
                            <option value="pending" <?php echo ($_GET['status'] ?? '') === 'pending' ? 'selected' : ''; ?>>Pending</option>
                            <option value="under_review" <?php echo ($_GET['status'] ?? '') === 'under_review' ? 'selected' : ''; ?>>Under Review</option>
                            <option value="approved" <?php echo ($_GET['status'] ?? '') === 'approved' ? 'selected' : ''; ?>>Approved</option>
                            <option value="rejected" <?php echo ($_GET['status'] ?? '') === 'rejected' ? 'selected' : ''; ?>>Rejected</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-gray-600 text-sm mb-1">State</label>
                        <select name="state" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                            <option value="">All States</option>
                            <?php foreach (Helpers::getNigerianStates() as $state): ?>
                                <option value="<?php echo $state; ?>" <?php echo ($_GET['state'] ?? '') === $state ? 'selected' : ''; ?>>
                                    <?php echo $state; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="block text-gray-600 text-sm mb-1">Sector</label>
                        <select name="sector" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                            <option value="">All Sectors</option>
                            <?php foreach (Helpers::getBusinessSectors() as $sector): ?>
                                <option value="<?php echo $sector; ?>" <?php echo ($_GET['sector'] ?? '') === $sector ? 'selected' : ''; ?>>
                                    <?php echo $sector; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="flex items-end gap-2">
                        <button type="submit" class="px-4 py-2 bg-primary text-white rounded-lg text-sm hover:bg-blue-700">
                            <i class="fas fa-filter mr-1"></i>Filter
                        </button>
                        <a href="applications.php" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg text-sm hover:bg-gray-300">
                            <i class="fas fa-times"></i>
                        </a>
                    </div>
                </form>
            </div>

            <!-- Bulk Actions -->
            <form method="POST" id="bulkForm">
                <div class="bg-white rounded-xl shadow-sm p-4 mb-6 flex justify-between items-center">
                    <div class="flex items-center space-x-4">
                        <input type="checkbox" id="selectAll" class="w-5 h-5 rounded">
                        <label for="selectAll" class="text-gray-600">Select All</label>
                        <select name="bulk_status" class="px-3 py-2 border border-gray-300 rounded-lg text-sm">
                            <option value="">Change Status...</option>
                            <option value="pending">Pending</option>
                            <option value="under_review">Under Review</option>
                            <option value="approved">Approved</option>
                            <option value="rejected">Rejected</option>
                        </select>
                        <button type="submit" name="bulk_action" value="update" class="px-4 py-2 bg-gray-800 text-white rounded-lg text-sm hover:bg-gray-700">
                            Apply
                        </button>
                        <span class="text-gray-300">|</span>
                        <button type="button" onclick="confirmBulkDelete()" class="px-4 py-2 bg-red-600 text-white rounded-lg text-sm hover:bg-red-700">
                            <i class="fas fa-trash mr-1"></i>Delete Selected
                        </button>
                        <input type="hidden" name="bulk_delete" id="bulk_delete" value="">
                    </div>
                    <div class="text-gray-500 text-sm">
                        Showing <?php echo count($result['data']); ?> of <?php echo $result['total']; ?> applications
                    </div>
                </div>

                <div class="bg-white rounded-xl shadow-sm overflow-hidden">
                    <table class="w-full">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="w-12"></th>
                                <th class="text-left py-3 px-4 font-semibold text-gray-600">Reference</th>
                                <th class="text-left py-3 px-4 font-semibold text-gray-600">Business</th>
                                <th class="text-left py-3 px-4 font-semibold text-gray-600">Sector</th>
                                <th class="text-left py-3 px-4 font-semibold text-gray-600">Location</th>
                                <th class="text-left py-3 px-4 font-semibold text-gray-600">Amount</th>
                                <th class="text-left py-3 px-4 font-semibold text-gray-600">Status</th>
                                <th class="text-left py-3 px-4 font-semibold text-gray-600">Date</th>
                                <th class="text-left py-3 px-4 font-semibold text-gray-600">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($result['data'] as $application): ?>
                            <tr class="border-b border-gray-100 hover:bg-gray-50">
                                <td class="py-3 px-4">
                                    <input type="checkbox" name="selected_ids[]" value="<?php echo $application['id']; ?>" class="row-checkbox w-5 h-5 rounded">
                                </td>
                                <td class="py-3 px-4 font-mono text-sm"><?php echo htmlspecialchars($application['reference_no']); ?></td>
                                <td class="py-3 px-4">
                                    <div class="font-medium"><?php echo htmlspecialchars($application['business_name']); ?></div>
                                    <div class="text-xs text-gray-500"><?php echo htmlspecialchars($application['cac_number']); ?></div>
                                </td>
                                <td class="py-3 px-4 text-sm"><?php echo htmlspecialchars($application['business_sector']); ?></td>
                                <td class="py-3 px-4">
                                    <div class="text-sm"><?php echo htmlspecialchars($application['city']); ?></div>
                                    <div class="text-xs text-gray-500"><?php echo htmlspecialchars($application['state']); ?></div>
                                </td>
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
                                        <button type="button" onclick="viewApplication(<?php echo $application['id']; ?>)"
                                            class="p-2 text-blue-600 hover:bg-blue-50 rounded-lg" title="View">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        <a href="edit_application.php?id=<?php echo $application['id']; ?>"
                                            class="p-2 text-green-600 hover:bg-green-50 rounded-lg" title="Edit">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <button type="button" onclick="confirmDelete(<?php echo $application['id']; ?>, '<?php echo htmlspecialchars($application['reference_no']); ?>')"
                                            class="p-2 text-red-600 hover:bg-red-50 rounded-lg" title="Delete">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if (empty($result['data'])): ?>
                            <tr>
                                <td colspan="9" class="py-12 text-center text-gray-500">No applications found</td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </form>

            <!-- Pagination -->
            <?php if ($result['total_pages'] > 1): ?>
            <div class="flex justify-center mt-6 space-x-2">
                <?php for ($i = 1; $i <= $result['total_pages']; $i++): ?>
                    <a href="?page=<?php echo $i; ?><?php echo isset($_GET['status']) ? '&status=' . htmlspecialchars($_GET['status']) : ''; ?><?php echo isset($_GET['search']) ? '&search=' . htmlspecialchars($_GET['search']) : ''; ?>" 
                        class="px-4 py-2 rounded-lg <?php echo $i === $page ? 'bg-primary text-white' : 'bg-white text-gray-600 hover:bg-gray-100'; ?> transition">
                        <?php echo $i; ?>
                    </a>
                <?php endfor; ?>
            </div>
            <?php endif; ?>
        </main>
    </div>

    <!-- View Modal -->
    <div id="viewModal" class="fixed inset-0 bg-black/50 hidden items-center justify-center z-50 overflow-y-auto">
        <div class="bg-white rounded-xl p-6 w-full max-w-2xl mx-4 my-8">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-xl font-bold text-gray-800">Application Details</h3>
                <button onclick="closeViewModal()" class="text-gray-500 hover:text-gray-700">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div id="viewModalContent"></div>
        </div>
    </div>

    <script>
        // Select all functionality
        document.getElementById('selectAll').addEventListener('change', function() {
            document.querySelectorAll('.row-checkbox').forEach(cb => cb.checked = this.checked);
        });

        function viewApplication(id) {
            document.getElementById('viewModalContent').innerHTML = '<div class="text-center py-8"><i class="fas fa-spinner fa-spin text-2xl text-primary"></i></div>';
            document.getElementById('viewModal').classList.remove('hidden');
            document.getElementById('viewModal').classList.add('flex');
            
            fetch('get_application.php?id=' + id)
                .then(response => response.text())
                .then(html => {
                    document.getElementById('viewModalContent').innerHTML = html;
                });
        }

        function closeViewModal() {
            document.getElementById('viewModal').classList.add('hidden');
            document.getElementById('viewModal').classList.remove('flex');
        }

        function confirmDelete(id, ref) {
            if (confirm('Are you sure you want to delete application ' + ref + '? This action cannot be undone.')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = '<input type="hidden" name="delete_single" value="1"><input type="hidden" name="application_id" value="' + id + '">';
                document.body.appendChild(form);
                form.submit();
            }
        }

        function confirmBulkDelete() {
            const selected = document.querySelectorAll('.row-checkbox:checked');
            if (selected.length === 0) {
                alert('Please select at least one application to delete.');
                return;
            }
            if (confirm('Are you sure you want to delete ' + selected.length + ' selected application(s)? This action cannot be undone.')) {
                document.getElementById('bulk_delete').value = '1';
                document.getElementById('bulkForm').submit();
            }
        }
    </script>
</body>
</html>
