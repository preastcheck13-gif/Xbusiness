<?php
/**
 * X Business Grant - Admin Reports
 * View analytics and reports on grant applications
 */

session_start();

require_once __DIR__ . '/../includes/Database.php';
require_once __DIR__ . '/../includes/helpers.php';

use App\Helpers;

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

$db = \App\Database::getInstance();

// Get statistics
$stats = [];

// Total applications
$stats['total'] = $db->fetchOne("SELECT COUNT(*) as count FROM applications")['count'];

// Status breakdown
$statusQuery = $db->fetchAll("SELECT status, COUNT(*) as count FROM applications GROUP BY status");
$statusBreakdown = [];
foreach ($statusQuery as $row) {
    $statusBreakdown[$row['status']] = $row['count'];
}
$stats['pending'] = $statusBreakdown['pending'] ?? 0;
$stats['under_review'] = $statusBreakdown['under_review'] ?? 0;
$stats['approved'] = $statusBreakdown['approved'] ?? 0;
$stats['rejected'] = $statusBreakdown['rejected'] ?? 0;

// Applications by sector
$sectorQuery = $db->fetchAll("SELECT business_sector, COUNT(*) as count FROM applications GROUP BY business_sector ORDER BY count DESC LIMIT 10");

// Applications by state
$stateQuery = $db->fetchAll("SELECT state, COUNT(*) as count FROM applications GROUP BY state ORDER BY count DESC LIMIT 10");

// Monthly applications (last 12 months)
$monthlyQuery = $db->fetchAll("
    SELECT DATE_FORMAT(created_at, '%Y-%m') as month, COUNT(*) as count 
    FROM applications 
    WHERE created_at >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
    GROUP BY DATE_FORMAT(created_at, '%Y-%m')
    ORDER BY month ASC
");

// Grant amounts
$stats['total_requested'] = $db->fetchOne("SELECT COALESCE(SUM(grant_amount_requested), 0) as total FROM applications")['total'];
$stats['total_approved'] = $db->fetchOne("SELECT COALESCE(SUM(grant_amount_requested), 0) as total FROM applications WHERE status = 'approved'")['total'];
$stats['avg_requested'] = $db->fetchOne("SELECT COALESCE(AVG(grant_amount_requested), 0) as avg FROM applications")['avg'];

// Business types
$businessTypeQuery = $db->fetchAll("SELECT business_type, COUNT(*) as count FROM applications GROUP BY business_type ORDER BY count DESC");

// Recent activity
$recentApplications = $db->fetchAll("SELECT * FROM applications ORDER BY created_at DESC LIMIT 10");

// Calculate percentages
$approvalRate = $stats['total'] > 0 ? round(($stats['approved'] / $stats['total']) * 100, 1) : 0;
$rejectionRate = $stats['total'] > 0 ? round(($stats['rejected'] / $stats['total']) * 100, 1) : 0;
$pendingRate = $stats['total'] > 0 ? round(($stats['pending'] / $stats['total']) * 100, 1) : 0;

$currentPage = basename($_SERVER['PHP_SELF'], '.php');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports - X Business Grant Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
                    <a href="reports.php" class="flex items-center space-x-3 px-4 py-3 rounded-lg bg-primary transition">
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
                    <h1 class="text-2xl font-bold text-gray-800">Reports & Analytics</h1>
                    <p class="text-gray-600">View statistics and insights on grant applications</p>
                </div>
                <div class="flex items-center space-x-4">
                    <span class="text-gray-500"><i class="fas fa-calendar mr-2"></i><?php echo date('F d, Y'); ?></span>
                    <button onclick="window.print()" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition">
                        <i class="fas fa-print mr-2"></i>Print Report
                    </button>
                </div>
            </div>

            <!-- Overview Cards -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
                <div class="bg-white rounded-xl shadow-sm p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-gray-500 text-sm">Total Applications</p>
                            <p class="text-3xl font-bold text-gray-800"><?php echo number_format($stats['total']); ?></p>
                        </div>
                        <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center">
                            <i class="fas fa-file-alt text-blue-600 text-xl"></i>
                        </div>
                    </div>
                </div>
                <div class="bg-white rounded-xl shadow-sm p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-gray-500 text-sm">Total Approved</p>
                            <p class="text-3xl font-bold text-green-600"><?php echo number_format($stats['approved']); ?></p>
                            <p class="text-xs text-gray-500"><?php echo $approvalRate; ?>% approval rate</p>
                        </div>
                        <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center">
                            <i class="fas fa-check-circle text-green-600 text-xl"></i>
                        </div>
                    </div>
                </div>
                <div class="bg-white rounded-xl shadow-sm p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-gray-500 text-sm">Pending Review</p>
                            <p class="text-3xl font-bold text-yellow-600"><?php echo number_format($stats['pending']); ?></p>
                            <p class="text-xs text-gray-500"><?php echo $pendingRate; ?>% pending</p>
                        </div>
                        <div class="w-12 h-12 bg-yellow-100 rounded-lg flex items-center justify-center">
                            <i class="fas fa-clock text-yellow-600 text-xl"></i>
                        </div>
                    </div>
                </div>
                <div class="bg-white rounded-xl shadow-sm p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-gray-500 text-sm">Total Rejected</p>
                            <p class="text-3xl font-bold text-red-600"><?php echo number_format($stats['rejected']); ?></p>
                            <p class="text-xs text-gray-500"><?php echo $rejectionRate; ?>% rejection rate</p>
                        </div>
                        <div class="w-12 h-12 bg-red-100 rounded-lg flex items-center justify-center">
                            <i class="fas fa-times-circle text-red-600 text-xl"></i>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Financial Overview -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                <div class="bg-gradient-to-r from-green-500 to-green-600 rounded-xl shadow-lg p-6 text-white">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-green-100 text-sm">Total Approved Amount</p>
                            <p class="text-3xl font-bold"><?php echo Helpers::formatNaira($stats['total_approved']); ?></p>
                        </div>
                        <div class="w-16 h-16 bg-white/20 rounded-lg flex items-center justify-center">
                            <i class="fas fa-naira-sign text-3xl"></i>
                        </div>
                    </div>
                </div>
                <div class="bg-gradient-to-r from-blue-500 to-blue-600 rounded-xl shadow-lg p-6 text-white">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-blue-100 text-sm">Total Requested Amount</p>
                            <p class="text-3xl font-bold"><?php echo Helpers::formatNaira($stats['total_requested']); ?></p>
                        </div>
                        <div class="w-16 h-16 bg-white/20 rounded-lg flex items-center justify-center">
                            <i class="fas fa-coins text-3xl"></i>
                        </div>
                    </div>
                </div>
                <div class="bg-gradient-to-r from-purple-500 to-purple-600 rounded-xl shadow-lg p-6 text-white">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-purple-100 text-sm">Average Request</p>
                            <p class="text-3xl font-bold"><?php echo Helpers::formatNaira($stats['avg_requested']); ?></p>
                        </div>
                        <div class="w-16 h-16 bg-white/20 rounded-lg flex items-center justify-center">
                            <i class="fas fa-chart-line text-3xl"></i>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Charts Row -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
                <!-- Status Distribution -->
                <div class="bg-white rounded-xl shadow-sm p-6">
                    <h3 class="text-lg font-bold text-gray-800 mb-4">Application Status Distribution</h3>
                    <div class="h-64 flex items-center justify-center">
                        <canvas id="statusChart"></canvas>
                    </div>
                </div>

                <!-- Business Sectors -->
                <div class="bg-white rounded-xl shadow-sm p-6">
                    <h3 class="text-lg font-bold text-gray-800 mb-4">Top Business Sectors</h3>
                    <div class="h-64 overflow-y-auto">
                        <div class="space-y-3">
                            <?php foreach ($sectorQuery as $sector): ?>
                            <?php 
                                $percentage = $stats['total'] > 0 ? ($sector['count'] / $stats['total']) * 100 : 0;
                            ?>
                            <div>
                                <div class="flex justify-between text-sm mb-1">
                                    <span class="font-medium"><?php echo htmlspecialchars($sector['business_sector']); ?></span>
                                    <span class="text-gray-500"><?php echo $sector['count']; ?> (<?php echo round($percentage, 1); ?>%)</span>
                                </div>
                                <div class="w-full bg-gray-200 rounded-full h-2">
                                    <div class="bg-primary h-2 rounded-full" style="width: <?php echo $percentage; ?>%"></div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                            <?php if (empty($sectorQuery)): ?>
                            <p class="text-gray-500 text-center py-4">No data available</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Geographic Distribution -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
                <!-- By State -->
                <div class="bg-white rounded-xl shadow-sm p-6">
                    <h3 class="text-lg font-bold text-gray-800 mb-4">Applications by State</h3>
                    <div class="space-y-3">
                        <?php foreach ($stateQuery as $state): ?>
                        <?php 
                            $percentage = $stats['total'] > 0 ? ($state['count'] / $stats['total']) * 100 : 0;
                        ?>
                        <div class="flex items-center">
                            <div class="w-24 text-sm font-medium truncate"><?php echo htmlspecialchars($state['state']); ?></div>
                            <div class="flex-1 mx-4">
                                <div class="w-full bg-gray-200 rounded-full h-2">
                                    <div class="bg-green-500 h-2 rounded-full" style="width: <?php echo $percentage; ?>%"></div>
                                </div>
                            </div>
                            <div class="w-12 text-right text-sm text-gray-500"><?php echo $state['count']; ?></div>
                        </div>
                        <?php endforeach; ?>
                        <?php if (empty($stateQuery)): ?>
                        <p class="text-gray-500 text-center py-4">No data available</p>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- By Business Type -->
                <div class="bg-white rounded-xl shadow-sm p-6">
                    <h3 class="text-lg font-bold text-gray-800 mb-4">By Business Type</h3>
                    <div class="h-64 flex items-center justify-center">
                        <canvas id="businessTypeChart"></canvas>
                    </div>
                </div>
            </div>

            <!-- Recent Applications Table -->
            <div class="bg-white rounded-xl shadow-sm p-6">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-lg font-bold text-gray-800">Recent Applications</h3>
                    <a href="applications.php" class="text-primary hover:underline text-sm">View All</a>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead>
                            <tr class="border-b border-gray-200">
                                <th class="text-left py-3 px-4 font-semibold text-gray-600">Reference</th>
                                <th class="text-left py-3 px-4 font-semibold text-gray-600">Business</th>
                                <th class="text-left py-3 px-4 font-semibold text-gray-600">Sector</th>
                                <th class="text-left py-3 px-4 font-semibold text-gray-600">Amount</th>
                                <th class="text-left py-3 px-4 font-semibold text-gray-600">Status</th>
                                <th class="text-left py-3 px-4 font-semibold text-gray-600">Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recentApplications as $app): ?>
                            <tr class="border-b border-gray-100 hover:bg-gray-50">
                                <td class="py-3 px-4 font-mono text-sm"><?php echo htmlspecialchars($app['reference_no']); ?></td>
                                <td class="py-3 px-4">
                                    <div class="font-medium"><?php echo htmlspecialchars($app['business_name']); ?></div>
                                </td>
                                <td class="py-3 px-4 text-sm"><?php echo htmlspecialchars($app['business_sector']); ?></td>
                                <td class="py-3 px-4 font-medium"><?php echo Helpers::formatNaira($app['grant_amount_requested']); ?></td>
                                <td class="py-3 px-4">
                                    <span class="px-3 py-1 rounded-full text-xs font-medium <?php echo Helpers::getStatusClass($app['status']); ?>">
                                        <?php echo ucfirst(str_replace('_', ' ', $app['status'])); ?>
                                    </span>
                                </td>
                                <td class="py-3 px-4 text-sm text-gray-500">
                                    <?php echo date('M d, Y', strtotime($app['created_at'])); ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if (empty($recentApplications)): ?>
                            <tr>
                                <td colspan="6" class="py-8 text-center text-gray-500">No applications yet</td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>

    <script>
        // Status Pie Chart
        const statusCtx = document.getElementById('statusChart').getContext('2d');
        new Chart(statusCtx, {
            type: 'doughnut',
            data: {
                labels: ['Pending', 'Under Review', 'Approved', 'Rejected'],
                datasets: [{
                    data: [
                        <?php echo $stats['pending']; ?>,
                        <?php echo $stats['under_review']; ?>,
                        <?php echo $stats['approved']; ?>,
                        <?php echo $stats['rejected']; ?>
                    ],
                    backgroundColor: ['#f59e0b', '#3b82f6', '#10b981', '#ef4444']
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'right'
                    }
                }
            }
        });

        // Business Type Bar Chart
        const businessTypeCtx = document.getElementById('businessTypeChart').getContext('2d');
        const businessTypes = <?php echo json_encode(array_column($businessTypeQuery, 'business_type')); ?>;
        const businessTypeCounts = <?php echo json_encode(array_column($businessTypeQuery, 'count')); ?>;
        
        new Chart(businessTypeCtx, {
            type: 'bar',
            data: {
                labels: businessTypes.slice(0, 5),
                datasets: [{
                    label: 'Applications',
                    data: businessTypeCounts.slice(0, 5),
                    backgroundColor: '#1e40af'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                indexAxis: 'y',
                plugins: {
                    legend: {
                        display: false
                    }
                }
            }
        });
    </script>
</body>
</html>
