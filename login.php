<?php
/**
 * X Business Grant - User Login/Registration
 * Email-first authentication flow
 */

session_start();

require_once 'includes/Database.php';
require_once 'includes/helpers.php';
require_once 'includes/User.php';
require_once 'includes/Settings.php';
require_once 'includes/Email.php';

use App\User;
use App\Helpers;
use App\Settings;
use App\Email;

$error = '';
$step = 1; // 1 = email entry, 2 = registration details, 3 = password entry
$email = '';

// Check if already logged in
if (isset($_SESSION['user_logged_in']) && $_SESSION['user_logged_in'] === true) {
    // Check if email verification is required
    if (Settings::isEmailVerificationEnabled()) {
        $user = new User();
        $userId = $_SESSION['user_id'] ?? 0;
        if ($userId > 0 && !$user->isEmailVerified($userId)) {
            header('Location: verify_email.php');
            exit;
        }
    }
    header('Location: dashboard.php');
    exit;
}

$user = new User();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['step']) && $_POST['step'] === 'check_email') {
        // Step 1: Check if email exists
        $email = trim($_POST['email'] ?? '');
        
        if (empty($email)) {
            $error = 'Please enter your email address';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Please enter a valid email address';
        } else {
            $email = strtolower($email);
            
            if ($user->emailExists($email)) {
                // Email exists - show password entry (Step 3)
                $step = 3;
            } else {
                // Email doesn't exist - show registration form (Step 2)
                $step = 2;
            }
        }
    } elseif (isset($_POST['step']) && $_POST['step'] === 'register') {
        // Step 2: Register new user
        $email = trim($_POST['email'] ?? '');
        $fullName = trim($_POST['full_name'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $businessRegNumber = trim($_POST['business_registration_number'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';
        
        // Validations
        if (empty($fullName)) {
            $error = 'Please enter your full name';
        } elseif (strlen($fullName) < 3) {
            $error = 'Full name must be at least 3 characters';
        } elseif (empty($phone)) {
            $error = 'Please enter your phone number';
        } elseif (!Helpers::validatePhone($phone)) {
            $error = 'Please enter a valid Nigerian phone number';
        } elseif (empty($businessRegNumber)) {
            $error = 'Please enter your Business Registration Number (BN or RC)';
        } elseif (empty($password)) {
            $error = 'Please enter a password';
        } elseif (strlen($password) < 8) {
            $error = 'Password must be at least 8 characters';
        } elseif ($password !== $confirmPassword) {
            $error = 'Passwords do not match';
        } else {
            try {
                $user->create([
                    'full_name' => $fullName,
                    'email' => $email,
                    'phone' => $phone,
                    'business_registration_number' => $businessRegNumber,
                    'password' => $password
                ]);
                
                // Get the newly created user
                $newUser = $user->findByEmail($email);
                
                // Log the user in
                $_SESSION['user_logged_in'] = true;
                $_SESSION['user_id'] = $newUser['id'];
                $_SESSION['user_name'] = $fullName;
                $_SESSION['user_email'] = $email;
                $_SESSION['user_business_reg_number'] = $businessRegNumber;
                
                // Check if email verification is enabled
                if (Settings::isEmailVerificationEnabled()) {
                    // Create verification code and send email
                    $emailUtil = new Email();
                    $code = $emailUtil->createVerificationCode($newUser['id']);
                    
                    // Store code in session for backup verification
                    $_SESSION['verification_code'] = $code;
                    $_SESSION['verification_user_id'] = $newUser['id'];
                    $_SESSION['verification_expires'] = time() + 1800; // 30 minutes
                    
                    // Debug: log the new user and code
                    error_log("Registration - New user ID: " . $newUser['id'] . ", Code: $code");
                    
                    $result = $emailUtil->sendVerificationEmail($email, $fullName, $code);
                    
                    if (!$result['success']) {
                        // Log error but don't block registration
                        error_log("Failed to send verification email: " . $result['message']);
                        // Store error message for display on verify page
                        $_SESSION['email_send_error'] = $result['message'];
                    }
                    
                    // Redirect to email verification page
                    header('Location: verify_email.php');
                    exit;
                }
                
                header('Location: dashboard.php');
                exit;
            } catch (\Exception $e) {
                $error = 'Registration failed. Please try again.';
            }
        }
        
        // If validation failed, stay on step 2
        if ($error) {
            $step = 2;
        }
    } elseif (isset($_POST['step']) && $_POST['step'] === 'login') {
        // Step 3: Login with password
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        
        if (empty($password)) {
            $error = 'Please enter your password';
            $step = 3;
        } else {
            if ($user->verifyPassword($email, $password)) {
                $userData = $user->findByEmail($email);
                
                $_SESSION['user_logged_in'] = true;
                $_SESSION['user_id'] = $userData['id'];
                $_SESSION['user_name'] = $userData['full_name'];
                $_SESSION['user_email'] = $userData['email'];
                
                $user->updateLastLogin($userData['id']);
                
                // Check if email verification is required
                if (Settings::isEmailVerificationEnabled() && !$user->isEmailVerified($userData['id'])) {
                    // Send verification code if not already sent
                    $emailUtil = new Email();
                    if (!$emailUtil->hasPendingCode($userData['id'])) {
                        $code = $emailUtil->createVerificationCode($userData['id']);
                        
                        // Store in session backup
                        $_SESSION['verification_code'] = $code;
                        $_SESSION['verification_user_id'] = $userData['id'];
                        $_SESSION['verification_expires'] = time() + 1800;
                        
                        $emailUtil->sendVerificationEmail($userData['email'], $userData['full_name'], $code);
                    }
                    header('Location: verify_email.php');
                    exit;
                }
                
                header('Location: dashboard.php');
                exit;
            } else {
                $error = 'Incorrect password. Please try again.';
                $step = 3;
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - X Business Grant</title>
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
            <p class="text-gray-500 mt-1">Sign in to your account</p>
        </div>

        <div class="bg-white rounded-2xl shadow-xl p-8">
            <?php if ($error): ?>
            <div class="bg-red-50 border border-red-200 rounded-xl p-4 mb-6">
                <div class="flex items-center">
                    <i class="fas fa-exclamation-circle text-red-500 mr-3"></i>
                    <p class="text-red-700 text-sm"><?php echo htmlspecialchars($error); ?></p>
                </div>
            </div>
            <?php endif; ?>

            <!-- Step 1: Email Entry -->
            <?php if ($step === 1): ?>
            <form method="POST" class="space-y-6">
                <input type="hidden" name="step" value="check_email">
                
                <div>
                    <label class="block text-gray-700 font-medium mb-2" for="email">
                        Email Address
                    </label>
                    <div class="relative">
                        <span class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-400">
                            <i class="fas fa-envelope"></i>
                        </span>
                        <input type="email" id="email" name="email" required
                            class="w-full pl-12 pr-4 py-4 border border-gray-300 rounded-xl focus:ring-2 focus:ring-primary focus:border-transparent text-lg"
                            placeholder="Enter your email address"
                            value="<?php echo htmlspecialchars($email ?? ''); ?>">
                    </div>
                </div>

                <button type="submit" 
                    class="w-full py-4 bg-primary text-white rounded-xl hover:bg-blue-700 transition font-semibold text-lg">
                    Continue
                </button>
            </form>

            <div class="mt-6 text-center">
                <p class="text-gray-500 text-sm">
                    Don't have an account? 
                    <span class="text-primary font-medium">It will be created automatically</span>
                </p>
            </div>

            <?php endif; ?>

            <!-- Step 2: Registration Form -->
            <?php if ($step === 2): ?>
            <form method="POST" class="space-y-5">
                <input type="hidden" name="step" value="register">
                <input type="hidden" name="email" value="<?php echo htmlspecialchars($email); ?>">
                
                <div class="bg-green-50 border border-green-200 rounded-xl p-4 mb-6">
                    <div class="flex items-center">
                        <i class="fas fa-check-circle text-green-500 mr-3"></i>
                        <p class="text-green-700 text-sm">
                            New account will be created for <strong><?php echo htmlspecialchars($email); ?></strong>
                        </p>
                    </div>
                </div>

                <div>
                    <label class="block text-gray-700 font-medium mb-2" for="full_name">
                        Full Name
                    </label>
                    <div class="relative">
                        <span class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-400">
                            <i class="fas fa-user"></i>
                        </span>
                        <input type="text" id="full_name" name="full_name" required
                            class="w-full pl-12 pr-4 py-4 border border-gray-300 rounded-xl focus:ring-2 focus:ring-primary focus:border-transparent"
                            placeholder="Enter your full name"
                            value="<?php echo htmlspecialchars($_POST['full_name'] ?? ''); ?>">
                    </div>
                </div>

                <div>
                    <label class="block text-gray-700 font-medium mb-2" for="phone">
                        Phone Number
                    </label>
                    <div class="relative">
                        <span class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-400">
                            <i class="fas fa-phone"></i>
                        </span>
                        <input type="tel" id="phone" name="phone" required
                            class="w-full pl-12 pr-4 py-4 border border-gray-300 rounded-xl focus:ring-2 focus:ring-primary focus:border-transparent"
                            placeholder="e.g., 08012345678"
                            value="<?php echo htmlspecialchars($_POST['phone'] ?? ''); ?>">
                    </div>
                </div>

                <div>
                    <label class="block text-gray-700 font-medium mb-2" for="business_registration_number">
                        Business Registration Number (BN or RC) <span class="text-red-500">*</span>
                    </label>
                    <div class="relative">
                        <span class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-400">
                            <i class="fas fa-building"></i>
                        </span>
                        <input type="text" id="business_registration_number" name="business_registration_number" required
                            class="w-full pl-12 pr-4 py-4 border border-gray-300 rounded-xl focus:ring-2 focus:ring-primary focus:border-transparent"
                            placeholder="e.g., BN123456 or RC123456"
                            value="<?php echo htmlspecialchars($_POST['business_registration_number'] ?? ''); ?>">
                    </div>
                    <p class="text-gray-500 text-xs mt-1">Enter your Business Name (BN) or Registration Number (RC) from CAC</p>
                </div>

                <div>
                    <label class="block text-gray-700 font-medium mb-2" for="password">
                        Password
                    </label>
                    <div class="relative">
                        <span class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-400">
                            <i class="fas fa-lock"></i>
                        </span>
                        <input type="password" id="password" name="password" required
                            class="w-full pl-12 pr-4 py-4 border border-gray-300 rounded-xl focus:ring-2 focus:ring-primary focus:border-transparent"
                            placeholder="Create a password (min 8 characters)">
                    </div>
                </div>

                <div>
                    <label class="block text-gray-700 font-medium mb-2" for="confirm_password">
                        Confirm Password
                    </label>
                    <div class="relative">
                        <span class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-400">
                            <i class="fas fa-lock"></i>
                        </span>
                        <input type="password" id="confirm_password" name="confirm_password" required
                            class="w-full pl-12 pr-4 py-4 border border-gray-300 rounded-xl focus:ring-2 focus:ring-primary focus:border-transparent"
                            placeholder="Confirm your password">
                    </div>
                </div>

                <button type="submit" 
                    class="w-full py-4 bg-primary text-white rounded-xl hover:bg-blue-700 transition font-semibold text-lg">
                    Create Account
                </button>
            </form>

            <div class="mt-4 text-center">
                <button type="button" onclick="window.location.href='login.php'" 
                    class="text-gray-500 hover:text-gray-700 text-sm">
                    <i class="fas fa-arrow-left mr-1"></i> Use a different email
                </button>
            </div>
            <?php endif; ?>

            <!-- Step 3: Password Entry (for existing users) -->
            <?php if ($step === 3): ?>
            <form method="POST" class="space-y-5">
                <input type="hidden" name="step" value="login">
                <input type="hidden" name="email" value="<?php echo htmlspecialchars($email); ?>">
                
                <div class="text-center mb-6">
                    <div class="w-16 h-16 bg-primary/10 rounded-full flex items-center justify-center mx-auto mb-3">
                        <i class="fas fa-user text-primary text-2xl"></i>
                    </div>
                    <p class="text-gray-600">Welcome back!</p>
                    <p class="font-semibold text-gray-800"><?php echo htmlspecialchars($email); ?></p>
                </div>

                <div>
                    <label class="block text-gray-700 font-medium mb-2" for="password">
                        Password
                    </label>
                    <div class="relative">
                        <span class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-400">
                            <i class="fas fa-lock"></i>
                        </span>
                        <input type="password" id="password" name="password" required
                            class="w-full pl-12 pr-4 py-4 border border-gray-300 rounded-xl focus:ring-2 focus:ring-primary focus:border-transparent text-lg"
                            placeholder="Enter your password"
                            autofocus>
                    </div>
                </div>

                <button type="submit" 
                    class="w-full py-4 bg-primary text-white rounded-xl hover:bg-blue-700 transition font-semibold text-lg">
                    Sign In
                </button>
            </form>

            <div class="mt-6 flex justify-between items-center">
                <button type="button" onclick="window.location.href='login.php'" 
                    class="text-gray-500 hover:text-gray-700 text-sm">
                    <i class="fas fa-arrow-left mr-1"></i> Use a different email
                </button>
                <a href="forgot-password.php" class="text-primary hover:underline text-sm">
                    Forgot password?
                </a>
            </div>
            <?php endif; ?>
        </div>

        <div class="mt-6 text-center">
            <a href="index.php" class="text-gray-500 hover:text-primary transition text-sm">
                <i class="fas fa-home mr-1"></i> Back to Home
            </a>
        </div>

        <p class="text-center text-gray-400 text-sm mt-6">
            By continuing, you agree to our 
            <a href="#" class="text-primary hover:underline">Terms of Service</a> and 
            <a href="#" class="text-primary hover:underline">Privacy Policy</a>
        </p>
    </div>
</body>
</html>
