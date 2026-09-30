<?php
/**
 * X Business Grant - User Profile Page
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
$userProfile = $user->getProfile($_SESSION['user_id']);

$stats = [
    'total' => $userProfile['applications_count'] ?? 0,
    'pending' => $userProfile['pending_count'] ?? 0,
    'approved' => $userProfile['approved_count'] ?? 0,
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile - X Business Grant</title>
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
    <!-- Navigation -->
    <nav class="bg-white shadow-lg sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-4">
            <div class="flex justify-between h-16">
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
                    <a href="dashboard.php" class="px-4 py-2 text-gray-600 hover:text-primary hover:bg-gray-100 rounded-lg transition">Dashboard</a>
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

    <main class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- Header -->
        <div class="mb-8">
            <h1 class="text-2xl font-bold text-gray-800">My Profile</h1>
            <p class="text-gray-500">Manage your account details</p>
        </div>

        <!-- Profile Card -->
        <div class="bg-white rounded-xl shadow-sm p-6 mb-6">
            <div class="flex items-center mb-6">
                <div class="w-20 h-20 bg-primary rounded-full flex items-center justify-center mr-6">
                    <span class="text-white font-bold text-3xl">
                        <?php echo strtoupper(substr($userProfile['full_name'], 0, 1)); ?>
                    </span>
                </div>
                <div>
                    <h2 class="text-2xl font-bold text-gray-800"><?php echo htmlspecialchars($userProfile['full_name']); ?></h2>
                    <p class="text-gray-500">Account Holder</p>
                </div>
            </div>
            
            <div class="grid md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-gray-500 text-sm mb-1">Full Name</label>
                    <div class="flex items-center bg-gray-50 rounded-lg px-4 py-3">
                        <i class="fas fa-user text-gray-400 mr-3"></i>
                        <span class="text-gray-800"><?php echo htmlspecialchars($userProfile['full_name']); ?></span>
                    </div>
                </div>
                
                <div>
                    <label class="block text-gray-500 text-sm mb-1">Email Address</label>
                    <div class="flex items-center bg-gray-50 rounded-lg px-4 py-3">
                        <i class="fas fa-envelope text-gray-400 mr-3"></i>
                        <span class="text-gray-800"><?php echo htmlspecialchars($userProfile['email']); ?></span>
                    </div>
                </div>
                
                <div>
                    <label class="block text-gray-500 text-sm mb-1">Phone Number</label>
                    <div class="flex items-center bg-gray-50 rounded-lg px-4 py-3">
                        <i class="fas fa-phone text-gray-400 mr-3"></i>
                        <span class="text-gray-800"><?php echo htmlspecialchars($userProfile['phone']); ?></span>
                    </div>
                </div>
                
                <div>
                    <label class="block text-gray-500 text-sm mb-1">Member Since</label>
                    <div class="flex items-center bg-gray-50 rounded-lg px-4 py-3">
                        <i class="fas fa-calendar text-gray-400 mr-3"></i>
                        <span class="text-gray-800"><?php echo date('F d, Y', strtotime($userProfile['created_at'])); ?></span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Stats Cards -->
        <div class="grid grid-cols-3 gap-4 mb-6">
            <div class="bg-white rounded-xl shadow-sm p-6 text-center">
                <div class="w-12 h-12 bg-blue-100 rounded-xl flex items-center justify-center mx-auto mb-3">
                    <i class="fas fa-file-alt text-blue-600 text-xl"></i>
                </div>
                <p class="text-3xl font-bold text-gray-800"><?php echo $stats['total']; ?></p>
                <p class="text-gray-500 text-sm">Total Applications</p>
            </div>
            <div class="bg-white rounded-xl shadow-sm p-6 text-center">
                <div class="w-12 h-12 bg-yellow-100 rounded-xl flex items-center justify-center mx-auto mb-3">
                    <i class="fas fa-clock text-yellow-600 text-xl"></i>
                </div>
                <p class="text-3xl font-bold text-yellow-600"><?php echo $stats['pending']; ?></p>
                <p class="text-gray-500 text-sm">Pending</p>
            </div>
            <div class="bg-white rounded-xl shadow-sm p-6 text-center">
                <div class="w-12 h-12 bg-green-100 rounded-xl flex items-center justify-center mx-auto mb-3">
                    <i class="fas fa-check-circle text-green-600 text-xl"></i>
                </div>
                <p class="text-3xl font-bold text-green-600"><?php echo $stats['approved']; ?></p>
                <p class="text-gray-500 text-sm">Approved</p>
            </div>
        </div>

        <!-- Account Actions -->
        <div class="bg-white rounded-xl shadow-sm p-6">
            <h3 class="text-lg font-bold text-gray-800 mb-4">Account Actions</h3>
            <div class="space-y-3">
                <a href="dashboard.php" class="flex items-center p-4 bg-gray-50 rounded-lg hover:bg-gray-100 transition">
                    <div class="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center mr-4">
                        <i class="fas fa-tachometer-alt text-blue-600"></i>
                    </div>
                    <div class="flex-1">
                        <p class="font-medium text-gray-800">Go to Dashboard</p>
                        <p class="text-gray-500 text-sm">View your applications and stats</p>
                    </div>
                    <i class="fas fa-chevron-right text-gray-400"></i>
                </a>
                <a href="apply.php" class="flex items-center p-4 bg-gray-50 rounded-lg hover:bg-gray-100 transition">
                    <div class="w-10 h-10 bg-green-100 rounded-lg flex items-center justify-center mr-4">
                        <i class="fas fa-plus-circle text-green-600"></i>
                    </div>
                    <div class="flex-1">
                        <p class="font-medium text-gray-800">New Application</p>
                        <p class="text-gray-500 text-sm">Apply for a new grant</p>
                    </div>
                    <i class="fas fa-chevron-right text-gray-400"></i>
                </a>
                <a href="status.php" class="flex items-center p-4 bg-gray-50 rounded-lg hover:bg-gray-100 transition">
                    <div class="w-10 h-10 bg-amber-100 rounded-lg flex items-center justify-center mr-4">
                        <i class="fas fa-search text-amber-600"></i>
                    </div>
                    <div class="flex-1">
                        <p class="font-medium text-gray-800">Track Status</p>
                        <p class="text-gray-500 text-sm">Check application status</p>
                    </div>
                    <i class="fas fa-chevron-right text-gray-400"></i>
                </a>
            </div>
        </div>
    </main>

    <!-- Mobile Bottom Tab Bar -->
    <div class="md:hidden fixed bottom-0 left-0 right-0 bg-white border-t border-gray-200 px-2 py-2 z-50">
        <div class="flex justify-around items-center">
            <a href="dashboard.php" class="flex flex-col items-center py-2 px-4 text-gray-500 hover:text-primary transition">
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
