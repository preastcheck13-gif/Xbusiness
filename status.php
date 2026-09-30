<?php
/**
 * X Business Grant - Check Application Status
 */

require_once 'includes/Database.php';
require_once 'includes/helpers.php';
require_once 'includes/Application.php';

use App\Helpers;
use App\Application;

$status = null;
$application = null;
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $reference = trim($_POST['reference'] ?? '');
    
    if (empty($reference)) {
        $error = 'Please enter your application reference number';
    } else {
        $app = new Application();
        $application = $app->getByReference($reference);
        
        if (!$application) {
            $error = 'No application found with this reference number. Please check and try again.';
        } else {
            $status = $application['status'];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Check Application Status - X Business Grant</title>
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
<body class="bg-gray-50 min-h-screen">
    <!-- Navigation -->
    <nav class="bg-white shadow-lg">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex items-center">
                    <a href="index.php" class="flex items-center space-x-2">
                        <div class="w-10 h-10 bg-primary rounded-lg flex items-center justify-center">
                            <span class="text-white font-bold text-xl">X</span>
                        </div>
                        <span class="text-xl font-bold text-gray-800">Business Grant</span>
                    </a>
                </div>
                <div class="flex items-center space-x-4">
                    <a href="index.php" class="text-gray-600 hover:text-primary">Home</a>
                    <a href="index.php#apply" class="bg-primary text-white px-4 py-2 rounded-lg hover:bg-blue-700">Apply Now</a>
                </div>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <main class="max-w-2xl mx-auto px-4 py-16">
        <div class="text-center mb-8">
            <h1 class="text-3xl md:text-4xl font-bold text-gray-800 mb-4">Check Application Status</h1>
            <p class="text-gray-600">Enter your application reference number to check your grant application status</p>
        </div>

        <!-- Search Form -->
        <div class="bg-white rounded-xl shadow-lg p-8 mb-8">
            <form method="POST">
                <div class="mb-4">
                    <label class="block text-gray-700 font-medium mb-2" for="reference">
                        Application Reference Number
                    </label>
                    <div class="relative">
                        <span class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-400">
                            <i class="fas fa-hashtag"></i>
                        </span>
                        <input type="text" id="reference" name="reference" required
                            class="w-full pl-12 pr-4 py-4 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent text-lg font-mono"
                            placeholder="e.g., XBG-2026-A1B2C3D4"
                            value="<?php echo htmlspecialchars($_POST['reference'] ?? ''); ?>">
                    </div>
                </div>
                <button type="submit" 
                    class="w-full py-4 bg-primary text-white rounded-lg hover:bg-blue-700 transition font-semibold text-lg">
                    <i class="fas fa-search mr-2"></i>Check Status
                </button>
            </form>
        </div>

        <?php if ($error): ?>
        <div class="bg-red-50 border border-red-200 rounded-lg p-4 mb-8">
            <p class="text-red-700"><i class="fas fa-exclamation-circle mr-2"></i><?php echo htmlspecialchars($error); ?></p>
        </div>
        <?php endif; ?>

        <?php if ($application): ?>
        <div class="bg-white rounded-xl shadow-lg p-8">
            <!-- Status Display -->
            <div class="text-center mb-8">
                <?php
                $statusIcon = [
                    'pending' => ['icon' => 'clock', 'color' => 'yellow'],
                    'under_review' => ['icon' => 'search', 'color' => 'blue'],
                    'approved' => ['icon' => 'check-circle', 'color' => 'green'],
                    'rejected' => ['icon' => 'times-circle', 'color' => 'red']
                ];
                $statusInfo = $statusIcon[$status] ?? ['icon' => 'question', 'color' => 'gray'];
                ?>
                <div class="w-20 h-20 bg-<?php echo $statusInfo['color']; ?>-100 rounded-full flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-<?php echo $statusInfo['icon']; ?> text-<?php echo $statusInfo['color']; ?>-600 text-3xl"></i>
                </div>
                <span class="px-4 py-2 rounded-full text-sm font-medium <?php echo Helpers::getStatusClass($status); ?>">
                    <?php echo ucfirst(str_replace('_', ' ', $status)); ?>
                </span>
            </div>

            <!-- Application Details -->
            <div class="border-t border-gray-200 pt-6">
                <h3 class="font-semibold text-gray-800 mb-4">Application Details</h3>
                <div class="grid grid-cols-2 gap-4 text-sm">
                    <div>
                        <p class="text-gray-500">Reference Number</p>
                        <p class="font-medium font-mono"><?php echo htmlspecialchars($application['reference_no']); ?></p>
                    </div>
                    <div>
                        <p class="text-gray-500">Business Name</p>
                        <p class="font-medium"><?php echo htmlspecialchars($application['business_name']); ?></p>
                    </div>
                    <div>
                        <p class="text-gray-500">CAC Number</p>
                        <p class="font-medium"><?php echo htmlspecialchars($application['cac_number']); ?></p>
                    </div>
                    <div>
                        <p class="text-gray-500">Grant Amount</p>
                        <p class="font-medium text-green-600"><?php echo Helpers::formatNaira($application['grant_amount_requested']); ?></p>
                    </div>
                    <div>
                        <p class="text-gray-500">Business Sector</p>
                        <p class="font-medium"><?php echo htmlspecialchars($application['business_sector']); ?></p>
                    </div>
                    <div>
                        <p class="text-gray-500">State</p>
                        <p class="font-medium"><?php echo htmlspecialchars($application['state']); ?></p>
                    </div>
                </div>
            </div>

            <!-- Status Messages -->
            <div class="border-t border-gray-200 pt-6 mt-6">
                <?php if ($status === 'pending'): ?>
                <div class="bg-yellow-50 rounded-lg p-4">
                    <h4 class="font-semibold text-yellow-800 mb-2"><i class="fas fa-info-circle mr-2"></i>What's Next?</h4>
                    <p class="text-yellow-700 text-sm">Your application has been received and is in the queue for review. Our team will begin processing it shortly. Please check back later for updates.</p>
                </div>
                <?php elseif ($status === 'under_review'): ?>
                <div class="bg-blue-50 rounded-lg p-4">
                    <h4 class="font-semibold text-blue-800 mb-2"><i class="fas fa-info-circle mr-2"></i>What's happening?</h4>
                    <p class="text-blue-700 text-sm">Your application is currently being reviewed by our team. This process typically takes 7-14 business days. We may contact you if additional information is needed.</p>
                </div>
                <?php elseif ($status === 'approved'): ?>
                <div class="bg-green-50 rounded-lg p-4">
                    <h4 class="font-semibold text-green-800 mb-2"><i class="fas fa-check-circle mr-2"></i>Congratulations!</h4>
                    <p class="text-green-700 text-sm">Your application has been approved! You will receive an email shortly with details on how to receive your grant funds. Please ensure your contact details are up to date.</p>
                </div>
                <?php elseif ($status === 'rejected'): ?>
                <div class="bg-red-50 rounded-lg p-4">
                    <h4 class="font-semibold text-red-800 mb-2"><i class="fas fa-times-circle mr-2"></i>Application Not Approved</h4>
                    <p class="text-red-700 text-sm">Unfortunately, your application was not approved at this time. This could be due to various factors including eligibility requirements or incomplete documentation. Please contact our support team for more information.</p>
                </div>
                <?php endif; ?>
            </div>

            <?php if ($application['admin_notes']): ?>
            <div class="border-t border-gray-200 pt-6 mt-6">
                <h4 class="font-semibold text-gray-800 mb-2">Notes from Review Team</h4>
                <p class="text-gray-600 text-sm bg-gray-50 rounded-lg p-4"><?php echo nl2br(htmlspecialchars($application['admin_notes'])); ?></p>
            </div>
            <?php endif; ?>

            <div class="border-t border-gray-200 pt-4 mt-6 text-center text-gray-500 text-sm">
                <p>Submitted: <?php echo date('F d, Y \a\t H:i', strtotime($application['created_at'])); ?></p>
                <p>Last Updated: <?php echo date('F d, Y \a\t H:i', strtotime($application['updated_at'])); ?></p>
            </div>
        </div>
        <?php endif; ?>

        <!-- Help Section -->
        <div class="mt-8 text-center">
            <p class="text-gray-600 mb-4">Need help finding your reference number?</p>
            <p class="text-gray-500 text-sm">Your reference number was provided when you submitted your application. It starts with "XBG-" and can also be found in your confirmation email.</p>
            <a href="index.php#apply" class="inline-block mt-4 text-primary hover:underline">
                <i class="fas fa-plus-circle mr-1"></i>Submit a New Application
            </a>
        </div>
    </main>

    <!-- Footer -->
    <footer class="bg-gray-800 text-white py-8 mt-16">
        <div class="max-w-7xl mx-auto px-4 text-center">
            <p>&copy; <?php echo date('Y'); ?> X Business Grant. All rights reserved.</p>
        </div>
    </footer>
</body>
</html>
