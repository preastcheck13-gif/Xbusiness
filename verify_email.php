<?php
/**
 * X Business Grant - Email Verification Page
 * Users enter the 6-digit code sent to their email
 */

session_start();

require_once 'includes/Database.php';
require_once 'includes/User.php';
require_once 'includes/Settings.php';
require_once 'includes/Email.php';

use App\User;
use App\Settings;
use App\Email;

// Check if user is logged in
if (!isset($_SESSION['user_logged_in']) || $_SESSION['user_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

$userId = $_SESSION['user_id'] ?? 0;
$userEmail = $_SESSION['user_email'] ?? '';
$userName = $_SESSION['user_name'] ?? '';

$message = '';
$messageType = '';
$success = false;

$user = new User();
$emailUtil = new Email();

// Check if email verification is enabled
$verificationEnabled = Settings::isEmailVerificationEnabled();

// If verification is disabled, redirect to dashboard
if (!$verificationEnabled) {
    header('Location: dashboard.php');
    exit;
}

// Check if user is already verified
if ($user->isEmailVerified($userId)) {
    header('Location: dashboard.php');
    exit;
}

// Get remaining time for current code
$remainingMinutes = $emailUtil->getCodeRemainingTime($userId);

// Debug info
$debugInfo = "User ID: $userId | Email: $userEmail | Minutes remaining: $remainingMinutes";

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'verify') {
        // Verify the code - strip any non-numeric characters
        $code = preg_replace('/[^0-9]/', '', trim($_POST['code'] ?? ''));
        
        if (empty($code)) {
            $message = 'Please enter the verification code';
            $messageType = 'error';
        } elseif (strlen($code) !== 6) {
            $message = 'Verification code must be 6 digits';
            $messageType = 'error';
        } else {
            // Debug: log the received code
            error_log("Verification attempt - User ID: $userId, Code received: $code");
            
            if ($emailUtil->verifyCode($userId, $code)) {
                $success = true;
                $message = 'Email verified successfully! Redirecting to dashboard...';
                $messageType = 'success';
                
                // Redirect after short delay
                header('Refresh: 2; URL=dashboard.php');
            } else {
                $message = 'Invalid or expired verification code. Please try again or request a new code.';
                $messageType = 'error';
            }
        }
    } elseif ($_POST['action'] === 'resend') {
        // Resend verification code
        $code = $emailUtil->resendCode($userId);
        $result = $emailUtil->sendVerificationEmail($userEmail, $userName, $code);
        
        if ($result['success']) {
            $message = 'Verification code sent! Please check your email.';
            $messageType = 'success';
            $remainingMinutes = 30; // Reset to 30 minutes
        } else {
            $message = 'Failed to send verification email: ' . $result['message'];
            $messageType = 'error';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify Email - X Business Grant</title>
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
        .code-input {
            font-size: 2rem;
            font-weight: bold;
            letter-spacing: 0.5rem;
            text-align: center;
        }
    </style>
</head>
<body class="bg-gray-50 min-h-screen flex items-center justify-center py-12 px-4">
    <div class="max-w-md w-full">
        <!-- Logo -->
        <div class="text-center mb-8">
            <a href="index.php" class="inline-flex items-center space-x-2">
                <div class="w-14 h-14 bg-primary rounded-xl flex items-center justify-center">
                    <span class="text-white font-bold text-2xl">X</span>
                </div>
            </a>
            <h1 class="text-2xl font-bold text-gray-800 mt-4">X Business Grant</h1>
        </div>

        <div class="bg-white rounded-2xl shadow-xl p-8">
            <div class="text-center mb-6">
                <div class="w-20 h-20 bg-primary/10 rounded-full flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-envelope text-primary text-3xl"></i>
                </div>
                <h2 class="text-xl font-bold text-gray-800 mb-2">Verify Your Email</h2>
                <p class="text-gray-500 text-sm">
                    We've sent a 6-digit code to<br>
                    <strong class="text-primary"><?php echo htmlspecialchars($userEmail); ?></strong>
                </p>
                <!-- Debug info -->
                <p class="text-xs text-gray-400 mt-2 font-mono">Debug: <?php echo $debugInfo; ?></p>
            </div>

            <?php if ($message): ?>
            <div class="mb-6 p-4 rounded-xl <?php echo $messageType === 'success' ? 'bg-green-50 border-green-200 text-green-800' : 'bg-red-50 border-red-200 text-red-800'; ?> border">
                <div class="flex items-center">
                    <i class="fas fa-<?php echo $messageType === 'success' ? 'check-circle' : 'exclamation-circle'; ?> mr-3"></i>
                    <p class="text-sm"><?php echo htmlspecialchars($message); ?></p>
                </div>
            </div>
            <?php elseif (isset($_SESSION['email_send_error'])): ?>
            <div class="mb-6 p-4 rounded-xl bg-red-50 border border-red-200 text-red-800">
                <div class="flex items-center">
                    <i class="fas fa-exclamation-circle mr-3"></i>
                    <p class="text-sm">Failed to send email: <?php echo htmlspecialchars($_SESSION['email_send_error']); unset($_SESSION['email_send_error']); ?></p>
                </div>
            </div>
            <?php else: ?>
            <!-- Auto-sent notification -->
            <div class="mb-6 p-4 rounded-xl bg-blue-50 border border-blue-200 text-blue-800">
                <div class="flex items-center">
                    <i class="fas fa-info-circle mr-3"></i>
                    <p class="text-sm">A verification code was just sent to your email. Please check your inbox (and spam folder).</p>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- Email deliverability notice -->
            <div class="mb-6 p-4 bg-amber-50 border border-amber-200 rounded-xl">
                <div class="flex items-start">
                    <i class="fas fa-exclamation-triangle text-amber-500 mr-3 mt-0.5"></i>
                    <div>
                        <p class="text-sm text-amber-800 font-medium">Check your Spam/Junk folder</p>
                        <p class="text-xs text-amber-700 mt-1">If you don't see the email in your inbox, please check your Spam or Junk folder. Sometimes verification emails can end up there.</p>
                    </div>
                </div>
            </div>

            <?php if ($success): ?>
            <div class="text-center py-8">
                <div class="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-check text-green-600 text-2xl"></i>
                </div>
                <p class="text-green-600 font-medium">Verification Successful!</p>
                <p class="text-gray-500 text-sm mt-2">Redirecting to dashboard...</p>
            </div>
            <?php else: ?>
            
            <!-- Timer Display -->
            <div class="text-center mb-6">
                <p class="text-sm text-gray-500">
                    Code expires in: <span id="timer" class="font-bold text-primary"><?php echo $remainingMinutes; ?> min</span>
                </p>
            </div>

            <!-- Verification Form -->
            <form method="POST" class="space-y-6">
                <input type="hidden" name="action" value="verify">
                
                <div>
                    <label class="block text-gray-700 font-medium mb-2" for="code">
                        Enter 6-Digit Code
                    </label>
                    <input type="text" id="code" name="code" required maxlength="6" pattern="[0-9]{6}"
                        class="code-input w-full px-4 py-4 border border-gray-300 rounded-xl focus:ring-2 focus:ring-primary focus:border-transparent"
                        placeholder="000000" autocomplete="off" autofocus>
                    <p class="text-xs text-gray-500 mt-2 text-center">Enter the code sent to your email</p>
                </div>

                <button type="submit" 
                    class="w-full py-4 bg-primary text-white rounded-xl hover:bg-blue-700 transition font-semibold text-lg">
                    <i class="fas fa-check mr-2"></i>Verify Email
                </button>
            </form>

            <div class="mt-6 text-center space-y-3">
                <p class="text-gray-500 text-sm">
                    Didn't receive the code?
                </p>
                <form method="POST">
                    <input type="hidden" name="action" value="resend">
                    <button type="submit" class="text-primary hover:text-blue-700 font-medium text-sm">
                        <i class="fas fa-redo mr-1"></i>Resend Code
                    </button>
                </form>
            </div>

            <div class="mt-6 pt-6 border-t border-gray-100">
                <a href="dashboard.php" class="block text-center text-gray-500 hover:text-gray-700 text-sm">
                    <i class="fas fa-arrow-left mr-1"></i>Back to Dashboard
                </a>
            </div>
            <?php endif; ?>
        </div>

        <div class="mt-6 text-center">
            <a href="logout.php" class="text-gray-500 hover:text-red-500 transition text-sm">
                <i class="fas fa-sign-out-alt mr-1"></i>Logout
            </a>
        </div>
    </div>

    <script>
        // Auto-format code input to only allow numbers
        document.getElementById('code').addEventListener('input', function(e) {
            this.value = this.value.replace(/[^0-9]/g, '');
        });

        // Countdown timer
        let minutes = <?php echo $remainingMinutes; ?>;
        const timerElement = document.getElementById('timer');
        
        if (minutes > 0) {
            setInterval(function() {
                if (minutes > 0) {
                    minutes--;
                    timerElement.textContent = minutes + ' min';
                    if (minutes === 0) {
                        timerElement.textContent = 'Expired';
                        timerElement.classList.remove('text-primary');
                        timerElement.classList.add('text-red-500');
                    }
                }
            }, 60000); // Update every minute
        }
    </script>
</body>
</html>
