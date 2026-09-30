<?php
/**
 * X Business Grant - User Dashboard
 */

session_start();

require_once 'includes/Database.php';
require_once 'includes/helpers.php';
require_once 'includes/User.php';
require_once 'includes/Application.php';
require_once 'includes/Settings.php';

use App\User;
use App\Application;
use App\Settings;

if (!isset($_SESSION['user_logged_in']) || $_SESSION['user_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

// Check if email verification is required and user is not verified
if (Settings::isEmailVerificationEnabled()) {
    $user = new User();
    $userId = $_SESSION['user_id'] ?? 0;
    if ($userId > 0 && !$user->isEmailVerified($userId)) {
        header('Location: verify_email.php');
        exit;
    }
}

$user = new User();
$app = new Application();

$userProfile = $user->getProfile($_SESSION['user_id']);
$applications = $app->getAll(['email' => $_SESSION['user_email']], 1, 10);

$stats = [
    'total' => count($applications['data']),
    'pending' => 0,
    'approved' => 0,
    'rejected' => 0,
    'under_review' => 0
];

foreach ($applications['data'] as $a) {
    if ($a['status'] === 'pending') $stats['pending']++;
    if ($a['status'] === 'approved') $stats['approved']++;
    if ($a['status'] === 'rejected') $stats['rejected']++;
    if ($a['status'] === 'under_review') $stats['under_review']++;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - X Business Grant</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '#1e40af',
                        secondary: '#3b82f6',
                        accent: '#f59e0b'
                    },
                    fontFamily: {
                        sans: ['"Plus Jakarta Sans"', '-apple-system', 'BlinkMacSystemFont', 'Segoe UI', 'Roboto', 'sans-serif']
                    }
                }
            }
        }
    </script>
    <style>
        body {
            font-family: "Plus Jakarta Sans", -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
        }
    </style>
</head>
<body class="bg-gray-50 pb-20 md:pb-0">
    <!-- Mobile Navigation -->
    <nav class="bg-white shadow-lg sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-4">
            <div class="flex justify-between h-16">
                <!-- Logo -->
                <div class="flex items-center">
                    <a href="index.php" class="flex items-center space-x-2">
                        <div class="w-10 h-10 bg-primary rounded-lg flex items-center justify-center">
                            <span class="text-white font-bold text-xl">X</span>
                        </div>
                        <span class="text-xl font-bold text-gray-800">Business</span>
                    </a>
                </div>
                
                <!-- Desktop Navigation -->
                <div class="hidden md:flex items-center space-x-4">
                    <span class="text-gray-600 text-sm">Welcome, <strong><?php echo htmlspecialchars($_SESSION['user_name']); ?></strong></span>
                    <a href="dashboard.php" class="px-4 py-2 text-primary font-medium bg-primary/10 rounded-lg">Dashboard</a>
                    <a href="apply.php" class="px-4 py-2 text-gray-600 hover:text-primary hover:bg-gray-100 rounded-lg transition">New Application</a>
                    <a href="status.php" class="px-4 py-2 text-gray-600 hover:text-primary hover:bg-gray-100 rounded-lg transition">Track Status</a>
                    <div class="flex items-center space-x-2 ml-4 pl-4 border-l border-gray-200">
                        <div class="w-10 h-10 bg-primary rounded-full flex items-center justify-center">
                            <span class="text-white font-bold">
                                <?php echo strtoupper(substr($_SESSION['user_name'], 0, 1)); ?>
                            </span>
                        </div>
                        <a href="logout.php" class="p-2 text-gray-500 hover:text-red-500 hover:bg-red-50 rounded-lg transition" title="Logout">
                            <i class="fas fa-sign-out-alt"></i>
                        </a>
                    </div>
                </div>
                
            </div>
        </div>
    </nav>

    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- Header -->
        <div class="mb-8">
            <p class="text-gray-500">Manage your applications</p>
        </div>

        <!-- Stats Cards -->
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
            <div class="bg-white rounded-xl shadow-sm p-6 border-l-4 border-blue-500">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-500 text-sm">Total Applications</p>
                        <p class="text-3xl font-bold text-gray-800"><?php echo $stats['total']; ?></p>
                    </div>
                    <div class="w-12 h-12 bg-blue-100 rounded-xl flex items-center justify-center">
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
                    <div class="w-12 h-12 bg-yellow-100 rounded-xl flex items-center justify-center">
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
                    <div class="w-12 h-12 bg-blue-100 rounded-xl flex items-center justify-center">
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
                    <div class="w-12 h-12 bg-green-100 rounded-xl flex items-center justify-center">
                        <i class="fas fa-check-circle text-green-600 text-xl"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="grid md:grid-cols-2 gap-6 mb-8">
            <a href="apply.php" class="bg-gradient-to-r from-primary to-blue-600 text-white rounded-xl p-6 hover:shadow-lg transition group">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-lg font-bold mb-1">New Application</h3>
                        <p class="text-blue-100 text-sm">Apply for grant funding</p>
                    </div>
                    <div class="w-12 h-12 bg-white/20 rounded-xl flex items-center justify-center group-hover:bg-white/30 transition">
                        <i class="fas fa-plus text-xl"></i>
                    </div>
                </div>
            </a>
            <a href="status.php" class="bg-gradient-to-r from-amber-500 to-amber-600 text-white rounded-xl p-6 hover:shadow-lg transition group">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-lg font-bold mb-1">Track Application</h3>
                        <p class="text-amber-100 text-sm">Check your application status</p>
                    </div>
                    <div class="w-12 h-12 bg-white/20 rounded-xl flex items-center justify-center group-hover:bg-white/30 transition">
                        <i class="fas fa-search text-xl"></i>
                    </div>
                </div>
            </a>
        </div>

        <!-- My Applications Section -->
        <?php if (!empty($applications['data'])): ?>
        <div class="bg-white rounded-xl shadow-sm p-6 mb-8">
            <div class="flex items-center justify-between mb-6">
                <h2 class="text-xl font-bold text-gray-800">My Applications</h2>
                <a href="status.php" class="text-primary hover:underline text-sm">View All</a>
            </div>
            
            <div class="space-y-4">
                <?php foreach ($applications['data'] as $appData): ?>
                <div class="border border-gray-200 rounded-xl p-4 hover:border-primary/50 transition cursor-pointer" onclick="showAppDetails(<?php echo htmlspecialchars(json_encode($appData)); ?>)">
                    <div class="flex items-center justify-between">
                        <div class="flex-1">
                            <div class="flex items-center gap-3 mb-2">
                                <span class="font-semibold text-gray-800"><?php echo htmlspecialchars($appData['business_name']); ?></span>
                                <span class="px-3 py-1 text-xs font-medium rounded-full
                                    <?php
                                        switch($appData['status']) {
                                            case 'approved': echo 'bg-green-100 text-green-700'; break;
                                            case 'rejected': echo 'bg-red-100 text-red-700'; break;
                                            case 'under_review': echo 'bg-blue-100 text-blue-700'; break;
                                            default: echo 'bg-yellow-100 text-yellow-700';
                                        }
                                    ?>">
                                    <?php echo ucfirst(str_replace('_', ' ', $appData['status'])); ?>
                                </span>
                            </div>
                            <div class="flex flex-wrap gap-4 text-sm text-gray-500">
                                <span><i class="fas fa-hashtag mr-1"></i><?php echo htmlspecialchars($appData['reference_no']); ?></span>
                                <span><i class="fas fa-naira-sign mr-1"></i><?php echo number_format($appData['grant_amount_requested']); ?></span>
                                <span><i class="fas fa-calendar mr-1"></i><?php echo date('M d, Y', strtotime($appData['created_at'])); ?></span>
                            </div>
                        </div>
                        <div class="ml-4">
                            <i class="fas fa-chevron-right text-gray-400"></i>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Application Details Modal -->
        <div id="appDetailsModal" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 hidden">
            <div class="bg-white rounded-2xl p-6 max-w-lg w-full mx-4 max-h-[80vh] overflow-y-auto">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-xl font-bold text-gray-800">Application Details</h3>
                    <button onclick="closeAppDetails()" class="text-gray-400 hover:text-gray-600">
                        <i class="fas fa-times text-xl"></i>
                    </button>
                </div>
                <div id="appDetailsContent">
                    <div class="text-center py-8">
                        <i class="fas fa-spinner fa-spin text-primary text-2xl"></i>
                        <p class="text-gray-500 mt-2">Loading...</p>
                    </div>
                </div>
            </div>
        </div>

        <script>
            function showAppDetails(appData) {
                const modal = document.getElementById('appDetailsModal');
                const content = document.getElementById('appDetailsContent');
                modal.classList.remove('hidden');
                
                const statusClass = {
                    'pending': 'bg-yellow-100 text-yellow-700',
                    'under_review': 'bg-blue-100 text-blue-700',
                    'approved': 'bg-green-100 text-green-700',
                    'rejected': 'bg-red-100 text-red-700'
                };
                
                const statusLabels = {
                    'pending': 'Pending',
                    'under_review': 'Under Review',
                    'approved': 'Approved',
                    'rejected': 'Rejected'
                };
                
                let statusInfo = '';
                if (appData.status === 'pending') {
                    statusInfo = '<div class="bg-yellow-50 rounded-lg p-4"><h4 class="font-semibold text-yellow-800 mb-2"><i class="fas fa-info-circle mr-2"></i>What\'s Next?</h4><p class="text-yellow-700 text-sm">Your application has been received and is in the queue for review. Our team will begin processing it shortly.</p></div>';
                } else if (appData.status === 'under_review') {
                    statusInfo = '<div class="bg-blue-50 rounded-lg p-4"><h4 class="font-semibold text-blue-800 mb-2"><i class="fas fa-info-circle mr-2"></i>What\'s happening?</h4><p class="text-blue-700 text-sm">Your application is currently being reviewed by our team. This process typically takes 7-14 business days.</p></div>';
                } else if (appData.status === 'approved') {
                    statusInfo = '<div class="bg-green-50 rounded-lg p-4"><h4 class="font-semibold text-green-800 mb-2"><i class="fas fa-check-circle mr-2"></i>Congratulations!</h4><p class="text-green-700 text-sm">Your application has been approved! You will receive an email shortly with details on how to receive your grant funds.</p></div>';
                } else if (appData.status === 'rejected') {
                    statusInfo = '<div class="bg-red-50 rounded-lg p-4"><h4 class="font-semibold text-red-800 mb-2"><i class="fas fa-times-circle mr-2"></i>Not Approved</h4><p class="text-red-700 text-sm">Unfortunately, your application was not approved at this time. Please contact our support team for more information.</p></div>';
                }
                
                const statusIcon = {
                    'pending': 'clock',
                    'under_review': 'search',
                    'approved': 'check-circle',
                    'rejected': 'times-circle'
                };
                const statusColor = {
                    'pending': 'yellow',
                    'under_review': 'blue',
                    'approved': 'green',
                    'rejected': 'red'
                };
                
                content.innerHTML = `
                    <div class="text-center mb-6">
                        <div class="w-16 h-16 bg-${statusColor[appData.status]}-100 rounded-full flex items-center justify-center mx-auto mb-4">
                            <i class="fas fa-${statusIcon[appData.status]} text-${statusColor[appData.status]}-600 text-2xl"></i>
                        </div>
                        <span class="px-4 py-2 rounded-full text-sm font-medium ${statusClass[appData.status]}">
                            ${statusLabels[appData.status]}
                        </span>
                    </div>
                    
                    <div class="border-t border-gray-200 pt-6">
                        <h4 class="font-semibold text-gray-800 mb-4">Application Details</h4>
                        <div class="grid grid-cols-2 gap-4 text-sm">
                            <div>
                                <p class="text-gray-500">Reference Number</p>
                                <p class="font-medium font-mono">${appData.reference_no || 'N/A'}</p>
                            </div>
                            <div>
                                <p class="text-gray-500">Business Name</p>
                                <p class="font-medium">${appData.business_name || 'N/A'}</p>
                            </div>
                            <div>
                                <p class="text-gray-500">Grant Amount</p>
                                <p class="font-medium text-green-600">₦${Number(appData.grant_amount_requested || 0).toLocaleString()}</p>
                            </div>
                            <div>
                                <p class="text-gray-500">Business Type</p>
                                <p class="font-medium">${appData.business_type || 'N/A'}</p>
                            </div>
                            <div>
                                <p class="text-gray-500">Submitted</p>
                                <p class="font-medium">${appData.created_at ? new Date(appData.created_at).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' }) : 'N/A'}</p>
                            </div>
                            <div>
                                <p class="text-gray-500">Last Updated</p>
                                <p class="font-medium">${appData.updated_at ? new Date(appData.updated_at).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' }) : 'N/A'}</p>
                            </div>
                        </div>
                    </div>
                    
                    ${statusInfo}
                    
                    ${appData.admin_notes ? `
                    <div class="border-t border-gray-200 pt-6 mt-6">
                        <h4 class="font-semibold text-gray-800 mb-2">Notes from Review Team</h4>
                        <p class="text-gray-600 text-sm bg-gray-50 rounded-lg p-4">${appData.admin_notes}</p>
                    </div>
                    ` : ''}
                `;
            }
            
            function closeAppDetails() {
                document.getElementById('appDetailsModal').classList.add('hidden');
            }
            
            // Close modal on outside click
            document.getElementById('appDetailsModal').addEventListener('click', function(e) {
                if (e.target === this) {
                    closeAppDetails();
                }
            });
        </script>
        <?php else: ?>
        <div class="bg-white rounded-xl shadow-sm p-8 text-center">
            <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                <i class="fas fa-file-alt text-gray-400 text-2xl"></i>
            </div>
            <h3 class="text-lg font-semibold text-gray-800 mb-2">No Applications Yet</h3>
            <p class="text-gray-500 mb-4">You haven't submitted any applications yet.</p>
            <a href="apply.php" class="inline-block px-6 py-3 bg-primary text-white rounded-xl hover:bg-blue-700 transition font-medium">
                Start Your Application
            </a>
        </div>
        <?php endif; ?>
    </main>
    
    <!-- Mobile Bottom Tab Bar -->
    <div class="md:hidden fixed bottom-0 left-0 right-0 bg-white border-t border-gray-200 px-2 py-2 z-50">
        <div class="flex justify-around items-center">
            <a href="dashboard.php" class="flex flex-col items-center py-2 px-4 text-primary">
                <i class="fas fa-home text-xl mb-1"></i>
                <span class="text-xs font-medium">Home</span>
            </a>
            <a href="apply.php" class="flex flex-col items-center py-2 px-4 text-gray-500 hover:text-primary transition">
                <i class="fas fa-plus-circle text-xl mb-1"></i>
                <span class="text-xs font-medium">Apply</span>
            </a>
            <a href="profile.php" class="flex flex-col items-center py-2 px-4 text-primary">
                <i class="fas fa-user text-xl mb-1"></i>
                <span class="text-xs font-medium">Profile</span>
            </a>
            <a href="logout.php" class="flex flex-col items-center py-2 px-4 text-gray-500 hover:text-red-500 transition">
                <i class="fas fa-sign-out-alt text-xl mb-1"></i>
                <span class="text-xs font-medium">Logout</span>
            </a>
        </div>
    </div>
</body>
</html>
