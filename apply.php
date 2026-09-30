<?php
/**
 * X Business Grant - Simplified Application Form
 * Two-step form: Business Info then Owner Info
 */

session_start();

require_once 'includes/Database.php';
require_once 'includes/helpers.php';
require_once 'includes/User.php';
require_once 'includes/Application.php';

use App\User;
use App\Application;
use App\Helpers;

// Helper function to check if account name matches business name
function nameMatchesBusiness($businessName, $accountName) {
    $businessLower = strtolower(trim($businessName));
    $accountLower = strtolower(trim($accountName));
    
    // Check if business name is contained in account name
    if (strpos($accountLower, $businessLower) !== false) {
        return true;
    }
    
    // Check if account name contains the business name
    if (strpos($businessLower, $accountLower) !== false) {
        return true;
    }
    
    // Check for key words match - business name words should appear in account name
    $businessWords = array_filter(explode(' ', $businessLower));
    $accountWords = array_filter(explode(' ', $accountLower));
    
    // Remove common words
    $commonWords = ['business', 'company', 'limited', 'ltd', 'enterprise', 'ventures', 'services', ' nigeria', 'ng'];
    $businessWords = array_diff($businessWords, $commonWords);
    $accountWords = array_diff($accountWords, $commonWords);
    
    // For business names with 2+ words, at least half should match
    if (count($businessWords) >= 2) {
        $matchCount = 0;
        foreach ($businessWords as $word) {
            if (in_array($word, $accountWords) || in_array($word, ['limited', 'ltd', 'limited'])) {
                $matchCount++;
            }
        }
        // Check if first significant word matches
        $firstWord = reset($businessWords);
        if (in_array($firstWord, $accountWords)) {
            return true;
        }
    }
    
    // For short business names (1 word), check if it's a significant part of account name
    if (count($businessWords) === 1) {
        $firstWord = reset($businessWords);
        if (strlen($firstWord) >= 4 && strpos($accountLower, $firstWord) !== false) {
            return true;
        }
    }
    
    return false;
}

if (!isset($_SESSION['user_logged_in']) || $_SESSION['user_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

$error = '';
$success = '';

// Store form data in session for step 2
if (!isset($_SESSION['application_step1'])) {
    $_SESSION['application_step1'] = [];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['step']) && $_POST['step'] === '1') {
        // Step 1: Business Information
        $businessName = trim($_POST['business_name'] ?? '');
        $businessType = trim($_POST['business_type'] ?? '');
        $loanAmount = trim($_POST['loan_amount'] ?? '');
        $bankName = trim($_POST['bank_name'] ?? '');
        $bankAccount = trim($_POST['bank_account'] ?? '');
        $verifiedAccountName = trim($_POST['verified_account_name'] ?? '');
        
        // Validate step 1
        if (empty($loanAmount)) {
            $error = 'Please select loan amount';
        } elseif (empty($businessName)) {
            $error = 'Please enter your business name';
        } elseif (empty($businessType)) {
            $error = 'Please select your business type';
        } elseif (empty($bankName)) {
            $error = 'Please select your bank';
        } elseif (empty($bankAccount)) {
            $error = 'Please enter your account number';
        } elseif (empty($verifiedAccountName)) {
            $error = 'Please verify your bank account first';
        } elseif (!nameMatchesBusiness($businessName, $verifiedAccountName)) {
            $error = 'Bank account name must match your business name. Please enter a business bank account.';
        } else {
            // Check if business documents are uploaded
            $missingDocs = [];
            
            if ($businessType === 'Sole Proprietorship') {
                $requiredDocs = ['cac_certificate', 'cac_status_report', 'utility_bill'];
            } else {
                $requiredDocs = ['cac_certificate', 'cac_memart', 'cac_status_report', 'utility_bill'];
            }
            
            foreach ($requiredDocs as $docType) {
                if (!isset($_FILES[$docType]) || $_FILES[$docType]['size'] <= 0) {
                    $missingDocs[] = $docType;
                }
            }
            
            if (!empty($missingDocs)) {
                $requiredDocsList = $businessType === 'Sole Proprietorship'
                    ? 'CAC Certificate, CAC Status Report, Utility Bill'
                    : 'CAC Certificate, CAC MEMART, CAC Status Report, Utility Bill';
                $error = 'Please upload all required documents: ' . $requiredDocsList;
            } else {
                // Create temp directory for this session
                $tempDir = 'uploads/temp_' . session_id();
                if (!is_dir($tempDir)) {
                    mkdir($tempDir, 0755, true);
                }
                
                // Store step 1 data in session
                $_SESSION['application_step1'] = [
                    'business_name' => $businessName,
                    'business_type' => $businessType,
                    'loan_amount' => $loanAmount,
                    'bank_name' => $bankName,
                    'bank_account' => $bankAccount,
                    'verified_account_name' => $verifiedAccountName,
                    'temp_dir' => $tempDir
                ];
                
                // Move uploaded files to temp directory
                foreach ($requiredDocs as $docType) {
                    if (isset($_FILES[$docType]) && $_FILES[$docType]['size'] > 0) {
                        $tmpFile = $_FILES[$docType]['tmp_name'];
                        $newFileName = $docType . '_' . time() . '_' . $_FILES[$docType]['name'];
                        $newPath = $tempDir . '/' . $newFileName;
                        move_uploaded_file($tmpFile, $newPath);
                        $_SESSION['application_step1']['files'][$docType] = [
                            'name' => $_FILES[$docType]['name'],
                            'type' => $_FILES[$docType]['type'],
                            'size' => $_FILES[$docType]['size'],
                            'path' => $newPath
                        ];
                    }
                }
                
                // Redirect to step 2
                header('Location: apply.php?step=2');
                exit;
            }
        }
        
        // If validation failed, repopulate form
        $step1_data = [
            'loan_amount' => $loanAmount,
            'business_name' => $businessName,
            'business_type' => $businessType,
            'bank_name' => $bankName,
            'bank_account' => $bankAccount,
            'verified_account_name' => $verifiedAccountName
        ];
    } elseif (isset($_POST['step']) && $_POST['step'] === '2') {
        // Step 2: Owner Information
        $ownerName = trim($_POST['owner_name'] ?? '');
        $dateOfBirth = trim($_POST['date_of_birth'] ?? '');
        $bvn = trim($_POST['bvn'] ?? '');
        
        // Validate step 2
        if (empty($ownerName)) {
            $error = 'Please enter your full name';
        } elseif (empty($dateOfBirth)) {
            $error = 'Please enter your date of birth';
        } elseif (empty($bvn)) {
            $error = 'Please enter your BVN';
        } elseif (strlen($bvn) !== 11) {
            $error = 'BVN must be 11 digits';
        } else {
            // Check for owner documents
            $hasOwnerDocs = false;
            $ownerDocs = ['passport_photo', 'owner_id'];
            
            foreach ($ownerDocs as $docType) {
                if (isset($_FILES[$docType]) && $_FILES[$docType]['size'] > 0) {
                    $hasOwnerDocs = true;
                }
            }
            
            if (!$hasOwnerDocs) {
                $error = 'Please upload both Passport Photo and Owner/Director ID';
            } else {
                // Get step 1 data from session
                $step1 = $_SESSION['application_step1'] ?? [];
                
                if (empty($step1)) {
                    $error = 'Session expired. Please start again.';
                } else {
                    try {
                        $app = new Application();
                        
                        $data = [
                            'business_name' => $step1['business_name'],
                            'business_type' => $step1['business_type'],
                            'cac_number' => 'PENDING',
                            'business_sector' => 'General',
                            'business_address' => 'PENDING',
                            'city' => 'PENDING',
                            'state' => 'PENDING',
                            'lga' => 'PENDING',
                            'phone' => $_SESSION['user_email'] ?? '',
                            'email' => $_SESSION['user_email'] ?? '',
                            'website' => '',
                            'years_in_business' => 1,
                            'employee_count' => 1,
                            'grant_amount' => $step1['loan_amount'] ?? 500000,
                            'purpose' => 'Business growth and expansion',
                            'business_description' => 'Documents will provide full business details',
                            'bank_name' => $step1['bank_name'],
                            'bank_code' => $step1['bank_name'],
                            'bank_account' => $step1['bank_account'],
                            'verified_account_name' => $step1['verified_account_name'],
                            'owner_name' => $ownerName,
                            'date_of_birth' => $dateOfBirth,
                            'bvn' => $bvn,
                            'business_registration_number' => $_SESSION['user_business_reg_number'] ?? 'PENDING'
                        ];
                        
                        // Get files from step 1 (already moved to temp)
                        $documents = [];
                        foreach ($step1['files'] ?? [] as $docType => $fileInfo) {
                            $documents[$docType] = [
                                'name' => $fileInfo['name'],
                                'type' => $fileInfo['type'],
                                'size' => $fileInfo['size'],
                                'tmp_name' => $fileInfo['path'],
                                'error' => 0
                            ];
                        }
                        
                        // Handle owner documents - move to final location
                        $tempDir = $step1['temp_dir'] ?? 'uploads/temp_' . session_id();
                        foreach ($ownerDocs as $docType) {
                            if (isset($_FILES[$docType]) && $_FILES[$docType]['size'] > 0) {
                                $tmpFile = $_FILES[$docType]['tmp_name'];
                                $newFileName = $docType . '_' . time() . '_' . $_FILES[$docType]['name'];
                                $newPath = $tempDir . '/' . $newFileName;
                                move_uploaded_file($tmpFile, $newPath);
                                $documents[$docType] = [
                                    'name' => $_FILES[$docType]['name'],
                                    'type' => $_FILES[$docType]['type'],
                                    'size' => $_FILES[$docType]['size'],
                                    'tmp_name' => $newPath,
                                    'error' => 0
                                ];
                            }
                        }
                        
                        $ref = $app->create($data, $documents);
                        
                        // Clean up temp directory
                        if (is_dir($tempDir)) {
                            array_map('unlink', glob("$tempDir/*"));
                            rmdir($tempDir);
                        }
                        
                        // Clear session
                        unset($_SESSION['application_step1']);
                        
                        $success = "Application submitted successfully! Your reference number is: " . $ref;
                        
                    } catch (Exception $e) {
                        error_log("Application submission error: " . $e->getMessage());
                        $error = 'Failed to submit application. Please try again.';
                    }
                }
            }
        }
        
        $step2_data = [
            'owner_name' => $ownerName,
            'date_of_birth' => $dateOfBirth,
            'bvn' => $bvn
        ];
    }
}

// Handle clearing session when going back to step 1
if (isset($_GET['clear']) && $_GET['clear'] === '1' && isset($_SESSION['application_step1']['temp_dir'])) {
    $tempDir = $_SESSION['application_step1']['temp_dir'];
    if (is_dir($tempDir)) {
        array_map('unlink', glob("$tempDir/*"));
        rmdir($tempDir);
    }
    unset($_SESSION['application_step1']);
    header('Location: apply.php?step=1');
    exit;
}

// Check current step
$current_step = isset($_GET['step']) ? (int)$_GET['step'] : 1;
if ($current_step !== 2) $current_step = 1;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Apply for Grant - X Business Grant</title>
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
                    <a href="apply.php" class="px-4 py-2 text-primary font-medium bg-primary/10 rounded-lg">Apply</a>
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

    <main class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- Header -->
        <div class="mb-8">
            <h1 class="text-2xl font-bold text-gray-800">Apply for Grant</h1>
            <p class="text-gray-500">Follow the steps to complete your application</p>
        </div>

        <!-- Progress Steps -->
        <div class="flex items-center justify-center mb-8">
            <div class="flex items-center">
                <div class="flex items-center justify-center w-10 h-10 rounded-full <?php echo $current_step >= 1 ? 'bg-primary text-white' : 'bg-gray-200 text-gray-500'; ?> font-semibold">
                    1
                </div>
                <div class="w-20 h-1 <?php echo $current_step >= 2 ? 'bg-primary' : 'bg-gray-200'; ?>"></div>
                <div class="flex items-center justify-center w-10 h-10 rounded-full <?php echo $current_step >= 2 ? 'bg-primary text-white' : 'bg-gray-200 text-gray-500'; ?> font-semibold">
                    2
                </div>
            </div>
        </div>
        <div class="flex justify-center mb-6 text-sm">
            <span class="<?php echo $current_step === 1 ? 'text-primary font-medium' : 'text-gray-500'; ?>">Business Information</span>
            <span class="mx-4 text-gray-300">|</span>
            <span class="<?php echo $current_step === 2 ? 'text-primary font-medium' : 'text-gray-500'; ?>">Owner Information</span>
        </div>

        <?php if ($error): ?>
        <div class="bg-red-50 border border-red-200 rounded-xl p-4 mb-6">
            <div class="flex items-center">
                <i class="fas fa-exclamation-circle text-red-500 mr-3"></i>
                <p class="text-red-700 text-sm"><?php echo htmlspecialchars($error); ?></p>
            </div>
        </div>
        <?php endif; ?>

        <?php if ($success): ?>
        <script>
            window.submissionSuccess = true;
            window.successMessage = <?php echo json_encode($success); ?>;
        </script>
        <?php endif; ?>

        <!-- Submission Modal -->
        <div id="submissionModal" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 hidden">
            <div class="bg-white rounded-2xl p-8 max-w-md w-full mx-4 text-center">
                <div class="w-20 h-20 mx-auto mb-6 bg-primary/10 rounded-full flex items-center justify-center">
                    <i class="fas fa-paper-plane text-primary text-3xl"></i>
                </div>
                <h3 class="text-xl font-bold text-gray-800 mb-2">Submitting Your Application</h3>
                <p class="text-gray-500 mb-6">Please wait while we process your application...</p>
                
                <!-- Progress Circle -->
                <div class="relative w-24 h-24 mx-auto mb-6">
                    <svg class="w-24 h-24 transform -rotate-90">
                        <circle cx="48" cy="48" r="44" stroke="#e5e7eb" stroke-width="8" fill="none"/>
                        <circle id="progressCircle" cx="48" cy="48" r="44" stroke="#1e40af" stroke-width="8" fill="none"
                            stroke-dasharray="276.32" stroke-dashoffset="276.32" stroke-linecap="round"/>
                    </svg>
                    <div class="absolute inset-0 flex items-center justify-center">
                        <span id="countdownNumber" class="text-2xl font-bold text-primary">10</span>
                    </div>
                </div>
                
                <p class="text-sm text-gray-500">Redirecting in <span id="countdownText">10</span> seconds...</p>
            </div>
        </div>

        <!-- Success Modal -->
        <div id="successModal" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 hidden">
            <div class="bg-white rounded-2xl p-8 max-w-md w-full mx-4 text-center">
                <div class="w-20 h-20 mx-auto mb-6 bg-green-100 rounded-full flex items-center justify-center">
                    <i class="fas fa-check text-green-500 text-3xl"></i>
                </div>
                <h3 class="text-xl font-bold text-gray-800 mb-2">Application Submitted!</h3>
                <p class="text-green-600 font-medium mb-2"><?php echo htmlspecialchars($success ?? ''); ?></p>
                <p class="text-gray-500 text-sm mb-4">Your application has been received and is being reviewed.</p>
                
                <div class="bg-amber-50 border border-amber-200 rounded-xl p-4 mb-6">
                    <div class="flex items-center justify-center mb-2">
                        <i class="fas fa-clock text-amber-500 mr-2"></i>
                        <span class="font-semibold text-amber-700">An update will be made soon</span>
                    </div>
                    <p class="text-amber-600 text-sm">Our team will review your application and contact you via email within 2-3 business days.</p>
                </div>
                
                <div class="relative w-24 h-24 mx-auto mb-6">
                    <svg class="w-24 h-24 transform -rotate-90">
                        <circle cx="48" cy="48" r="44" stroke="#e5e7eb" stroke-width="8" fill="none"/>
                        <circle id="successProgressCircle" cx="48" cy="48" r="44" stroke="#10b981" stroke-width="8" fill="none"
                            stroke-dasharray="276.32" stroke-dashoffset="276.32" stroke-linecap="round"/>
                    </svg>
                    <div class="absolute inset-0 flex items-center justify-center">
                        <span id="successCountdownNumber" class="text-2xl font-bold text-green-600">10</span>
                    </div>
                </div>
                
                <p class="text-sm text-gray-500">Redirecting to dashboard in <span id="successCountdownText">10</span> seconds...</p>
                <a href="dashboard.php" class="inline-block mt-4 px-6 py-3 bg-primary text-white rounded-xl hover:bg-blue-700 transition font-medium">
                    Go to Dashboard Now <i class="fas fa-arrow-right ml-2"></i>
                </a>
            </div>
        </div>

        <!-- Step 1: Business Information -->
        <?php if ($current_step === 1): ?>
        <div class="bg-white rounded-xl shadow-sm p-6">
            <form method="POST" enctype="multipart/form-data" class="space-y-6">
                <input type="hidden" name="step" value="1">
                
                <!-- Loan Amount -->
                <div>
                    <label class="block text-gray-700 font-medium mb-2" for="loan_amount">
                        Loan Amount Requested (₦) <span class="text-red-500">*</span>
                    </label>
                    <select id="loan_amount" name="loan_amount" required
                        class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-primary focus:border-transparent">
                        <option value="">Select loan amount</option>
                        <option value="50000" <?php echo ($step1_data['loan_amount'] ?? $_SESSION['application_step1']['loan_amount'] ?? '') === '50000' ? 'selected' : ''; ?>>₦50,000</option>
                        <option value="100000" <?php echo ($step1_data['loan_amount'] ?? $_SESSION['application_step1']['loan_amount'] ?? '') === '100000' ? 'selected' : ''; ?>>₦100,000</option>
                        <option value="250000" <?php echo ($step1_data['loan_amount'] ?? $_SESSION['application_step1']['loan_amount'] ?? '') === '250000' ? 'selected' : ''; ?>>₦250,000</option>
                        <option value="500000" <?php echo ($step1_data['loan_amount'] ?? $_SESSION['application_step1']['loan_amount'] ?? '') === '500000' ? 'selected' : ''; ?>>₦500,000</option>
                        <option value="1000000" <?php echo ($step1_data['loan_amount'] ?? $_SESSION['application_step1']['loan_amount'] ?? '') === '1000000' ? 'selected' : ''; ?>>₦1,000,000</option>
                        <option value="2500000" <?php echo ($step1_data['loan_amount'] ?? $_SESSION['application_step1']['loan_amount'] ?? '') === '2500000' ? 'selected' : ''; ?>>₦2,500,000</option>
                        <option value="5000000" <?php echo ($step1_data['loan_amount'] ?? $_SESSION['application_step1']['loan_amount'] ?? '') === '5000000' ? 'selected' : ''; ?>>₦5,000,000</option>
                    </select>
                    <p class="text-gray-500 text-xs mt-1">Select the amount of grant you are applying for</p>
                </div>

                <!-- Business Name -->
                <div>
                    <label class="block text-gray-700 font-medium mb-2" for="business_name">
                        Business Name <span class="text-red-500">*</span>
                    </label>
                    <input type="text" id="business_name" name="business_name" required
                        class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-primary focus:border-transparent"
                        placeholder="Enter your business name"
                        value="<?php echo htmlspecialchars($step1_data['business_name'] ?? $_SESSION['application_step1']['business_name'] ?? ''); ?>">
                </div>

                <!-- Business Type -->
                <div>
                    <label class="block text-gray-700 font-medium mb-2" for="business_type">
                        Business Type <span class="text-red-500">*</span>
                    </label>
                    <select id="business_type" name="business_type" required
                        class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-primary focus:border-transparent">
                        <option value="">Select business type</option>
                        <option value="Sole Proprietorship" <?php echo ($step1_data['business_type'] ?? $_SESSION['application_step1']['business_type'] ?? '') === 'Sole Proprietorship' ? 'selected' : ''; ?>>Sole Proprietorship</option>
                        <option value="Partnership" <?php echo ($step1_data['business_type'] ?? $_SESSION['application_step1']['business_type'] ?? '') === 'Partnership' ? 'selected' : ''; ?>>Partnership</option>
                        <option value="Private Limited Company" <?php echo ($step1_data['business_type'] ?? $_SESSION['application_step1']['business_type'] ?? '') === 'Private Limited Company' ? 'selected' : ''; ?>>Private Limited Company</option>
                        <option value="Public Limited Company" <?php echo ($step1_data['business_type'] ?? $_SESSION['application_step1']['business_type'] ?? '') === 'Public Limited Company' ? 'selected' : ''; ?>>Public Limited Company</option>
                        <option value="Non-Governmental Organization" <?php echo ($step1_data['business_type'] ?? $_SESSION['application_step1']['business_type'] ?? '') === 'Non-Governmental Organization' ? 'selected' : ''; ?>>Non-Governmental Organization</option>
                    </select>
                </div>

                <!-- Bank Account Section -->
                <div>
                    <h3 class="text-lg font-bold text-gray-800 mb-4">Business Bank Details</h3>
                    <p class="text-gray-500 text-sm mb-4">Enter your business bank account details for grant disbursement</p>
                    
                    <div class="grid md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-gray-700 font-medium mb-2" for="bank_name">
                                Bank Name <span class="text-red-500">*</span>
                            </label>
                            <select id="bank_name" name="bank_name" required
                                class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-primary focus:border-transparent">
                                <option value="">Loading banks...</option>
                            </select>
                        </div>
                        
                        <div>
                            <label class="block text-gray-700 font-medium mb-2" for="bank_account">
                                Account Number <span class="text-red-500">*</span>
                            </label>
                            <input type="text" id="bank_account" name="bank_account" required maxlength="10"
                                class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-primary focus:border-transparent"
                                placeholder="10-digit account number"
                                value="<?php echo htmlspecialchars($step1_data['bank_account'] ?? $_SESSION['application_step1']['bank_account'] ?? ''); ?>"
                                oninput="this.value = this.value.replace(/[^0-9]/g, '')">
                        </div>
                    </div>
                    
                    <!-- Hidden field for bank code -->
                    <input type="hidden" id="bank_code" name="bank_code" value="">
                    
                    <!-- Account Name Verification -->
                    <div class="mt-4">
                        <div id="verify_loading" class="hidden text-sm text-gray-500 mb-2">
                            <i class="fas fa-spinner fa-spin mr-1"></i>Verifying account...
                        </div>
                        <div id="account_verification" class="hidden bg-gray-50 rounded-xl p-4">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-sm text-gray-500">Account Name:</p>
                                    <p id="account_name" class="font-semibold text-gray-800"></p>
                                </div>
                                <div id="verification_status" class="flex items-center">
                                    <i class="fas fa-check-circle text-green-500 text-xl"></i>
                                </div>
                            </div>
                            <div id="verification_message" class="mt-2 text-sm"></div>
                        </div>
                    </div>
                    <input type="hidden" id="verified_account_name" name="verified_account_name" value="<?php echo htmlspecialchars($step1_data['verified_account_name'] ?? $_SESSION['application_step1']['verified_account_name'] ?? ''); ?>">
                </div>

                <!-- Business Documents -->
                <div>
                    <h3 class="text-lg font-bold text-gray-800 mb-4">Business Documents</h3>
                    <p class="text-gray-500 text-sm mb-4">Upload clear copies of the following documents (PDF, JPG, PNG accepted)</p>
                    
                    <div id="documents_sole_prop" class="space-y-4">
                        <!-- Sole Proprietorship Documents -->
                        <div class="border border-gray-200 rounded-xl p-4">
                            <label class="flex items-start cursor-pointer">
                                <div class="flex-1">
                                    <div class="flex items-center mb-1">
                                        <i class="fas fa-file-alt text-primary mr-2"></i>
                                        <span class="font-medium text-gray-800">CAC Certificate</span>
                                        <span class="text-red-500 ml-1">*</span>
                                    </div>
                                    <p class="text-gray-500 text-xs">Certificate of Incorporation or Business Registration</p>
                                </div>
                                <div class="ml-4">
                                    <input type="file" name="cac_certificate" accept=".pdf,.jpg,.jpeg,.png" class="hidden" id="cac_certificate_sp">
                                    <label for="cac_certificate_sp" class="cursor-pointer bg-gray-100 hover:bg-gray-200 px-4 py-2 rounded-lg text-sm font-medium text-gray-700 transition">
                                        <i class="fas fa-upload mr-1"></i>Upload
                                    </label>
                                </div>
                            </label>
                            <div id="cac_certificate_sp_name" class="mt-2 text-sm text-green-600 hidden"></div>
                        </div>

                        <div class="border border-gray-200 rounded-xl p-4">
                            <label class="flex items-start cursor-pointer">
                                <div class="flex-1">
                                    <div class="flex items-center mb-1">
                                        <i class="fas fa-file-circle-check text-primary mr-2"></i>
                                        <span class="font-medium text-gray-800">CAC Status Report</span>
                                        <span class="text-red-500 ml-1">*</span>
                                    </div>
                                    <p class="text-gray-500 text-xs">Current CAC Status Report of the business</p>
                                </div>
                                <div class="ml-4">
                                    <input type="file" name="cac_status_report" accept=".pdf,.jpg,.jpeg,.png" class="hidden" id="cac_status_report_sp">
                                    <label for="cac_status_report_sp" class="cursor-pointer bg-gray-100 hover:bg-gray-200 px-4 py-2 rounded-lg text-sm font-medium text-gray-700 transition">
                                        <i class="fas fa-upload mr-1"></i>Upload
                                    </label>
                                </div>
                            </label>
                            <div id="cac_status_report_sp_name" class="mt-2 text-sm text-green-600 hidden"></div>
                        </div>

                        <div class="border border-gray-200 rounded-xl p-4">
                            <label class="flex items-start cursor-pointer">
                                <div class="flex-1">
                                    <div class="flex items-center mb-1">
                                        <i class="fas fa-file-invoice text-primary mr-2"></i>
                                        <span class="font-medium text-gray-800">Utility Bill</span>
                                        <span class="text-red-500 ml-1">*</span>
                                    </div>
                                    <p class="text-gray-500 text-xs">Recent electricity or water bill (not older than 3 months)</p>
                                </div>
                                <div class="ml-4">
                                    <input type="file" name="utility_bill" accept=".pdf,.jpg,.jpeg,.png" class="hidden" id="utility_bill_sp">
                                    <label for="utility_bill_sp" class="cursor-pointer bg-gray-100 hover:bg-gray-200 px-4 py-2 rounded-lg text-sm font-medium text-gray-700 transition">
                                        <i class="fas fa-upload mr-1"></i>Upload
                                    </label>
                                </div>
                            </label>
                            <div id="utility_bill_sp_name" class="mt-2 text-sm text-green-600 hidden"></div>
                        </div>
                    </div>

                    <div id="documents_other" class="space-y-4 hidden">
                        <!-- Other Business Types Documents -->
                        <div class="border border-gray-200 rounded-xl p-4">
                            <label class="flex items-start cursor-pointer">
                                <div class="flex-1">
                                    <div class="flex items-center mb-1">
                                        <i class="fas fa-file-alt text-primary mr-2"></i>
                                        <span class="font-medium text-gray-800">CAC Certificate</span>
                                        <span class="text-red-500 ml-1">*</span>
                                    </div>
                                    <p class="text-gray-500 text-xs">Certificate of Incorporation</p>
                                </div>
                                <div class="ml-4">
                                    <input type="file" name="cac_certificate" accept=".pdf,.jpg,.jpeg,.png" class="hidden" id="cac_certificate_other">
                                    <label for="cac_certificate_other" class="cursor-pointer bg-gray-100 hover:bg-gray-200 px-4 py-2 rounded-lg text-sm font-medium text-gray-700 transition">
                                        <i class="fas fa-upload mr-1"></i>Upload
                                    </label>
                                </div>
                            </label>
                            <div id="cac_certificate_other_name" class="mt-2 text-sm text-green-600 hidden"></div>
                        </div>

                        <div class="border border-gray-200 rounded-xl p-4">
                            <label class="flex items-start cursor-pointer">
                                <div class="flex-1">
                                    <div class="flex items-center mb-1">
                                        <i class="fas fa-file-contract text-primary mr-2"></i>
                                        <span class="font-medium text-gray-800">CAC MEMART</span>
                                        <span class="text-red-500 ml-1">*</span>
                                    </div>
                                    <p class="text-gray-500 text-xs">CAC Memorandum and Articles of Association</p>
                                </div>
                                <div class="ml-4">
                                    <input type="file" name="cac_memart" accept=".pdf,.jpg,.jpeg,.png" class="hidden" id="cac_memart_other">
                                    <label for="cac_memart_other" class="cursor-pointer bg-gray-100 hover:bg-gray-200 px-4 py-2 rounded-lg text-sm font-medium text-gray-700 transition">
                                        <i class="fas fa-upload mr-1"></i>Upload
                                    </label>
                                </div>
                            </label>
                            <div id="cac_memart_other_name" class="mt-2 text-sm text-green-600 hidden"></div>
                        </div>

                        <div class="border border-gray-200 rounded-xl p-4">
                            <label class="flex items-start cursor-pointer">
                                <div class="flex-1">
                                    <div class="flex items-center mb-1">
                                        <i class="fas fa-file-circle-check text-primary mr-2"></i>
                                        <span class="font-medium text-gray-800">CAC Status Report</span>
                                        <span class="text-red-500 ml-1">*</span>
                                    </div>
                                    <p class="text-gray-500 text-xs">Current CAC Status Report</p>
                                </div>
                                <div class="ml-4">
                                    <input type="file" name="cac_status_report" accept=".pdf,.jpg,.jpeg,.png" class="hidden" id="cac_status_report_other">
                                    <label for="cac_status_report_other" class="cursor-pointer bg-gray-100 hover:bg-gray-200 px-4 py-2 rounded-lg text-sm font-medium text-gray-700 transition">
                                        <i class="fas fa-upload mr-1"></i>Upload
                                    </label>
                                </div>
                            </label>
                            <div id="cac_status_report_other_name" class="mt-2 text-sm text-green-600 hidden"></div>
                        </div>

                        <div class="border border-gray-200 rounded-xl p-4">
                            <label class="flex items-start cursor-pointer">
                                <div class="flex-1">
                                    <div class="flex items-center mb-1">
                                        <i class="fas fa-file-invoice text-primary mr-2"></i>
                                        <span class="font-medium text-gray-800">Utility Bill</span>
                                        <span class="text-red-500 ml-1">*</span>
                                    </div>
                                    <p class="text-gray-500 text-xs">Recent electricity or water bill (not older than 3 months)</p>
                                </div>
                                <div class="ml-4">
                                    <input type="file" name="utility_bill" accept=".pdf,.jpg,.jpeg,.png" class="hidden" id="utility_bill_other">
                                    <label for="utility_bill_other" class="cursor-pointer bg-gray-100 hover:bg-gray-200 px-4 py-2 rounded-lg text-sm font-medium text-gray-700 transition">
                                        <i class="fas fa-upload mr-1"></i>Upload
                                    </label>
                                </div>
                            </label>
                            <div id="utility_bill_other_name" class="mt-2 text-sm text-green-600 hidden"></div>
                        </div>
                    </div>
                </div>

                <!-- Submit Button -->
                <button type="submit" 
                    class="w-full py-4 bg-primary text-white rounded-xl hover:bg-blue-700 transition font-semibold text-lg">
                    Next Step <i class="fas fa-arrow-right ml-2"></i>
                </button>
            </form>
        </div>
        <?php endif; ?>

        <!-- Step 2: Owner Information -->
        <?php if ($current_step === 2): ?>
        <div class="bg-white rounded-xl shadow-sm p-6">
            <form method="POST" enctype="multipart/form-data" class="space-y-6">
                <input type="hidden" name="step" value="2">
                
                <!-- Business Summary -->
                <div class="bg-gray-50 rounded-xl p-4 mb-6">
                    <h4 class="font-bold text-gray-800 mb-2">Business Information</h4>
                    <p class="text-sm text-gray-600">
                        <strong>Loan Amount:</strong> ₦<?php echo number_format((int)($_SESSION['application_step1']['loan_amount'] ?? 0)); ?><br>
                        <strong>Business Name:</strong> <?php echo htmlspecialchars($_SESSION['application_step1']['business_name'] ?? 'N/A'); ?><br>
                        <strong>Business Type:</strong> <?php echo htmlspecialchars($_SESSION['application_step1']['business_type'] ?? 'N/A'); ?><br>
                        <strong>Registration No.:</strong> <?php echo htmlspecialchars($_SESSION['user_business_reg_number'] ?? 'N/A'); ?><br>
                        <strong>Bank Account:</strong> <?php echo htmlspecialchars($_SESSION['application_step1']['bank_account'] ?? 'N/A'); ?> (<?php echo htmlspecialchars($_SESSION['application_step1']['bank_name'] ?? 'N/A'); ?>)
                    </p>
                    <a href="apply.php?step=1&clear=1" class="text-primary text-sm hover:underline mt-2 inline-block">
                        <i class="fas fa-edit mr-1"></i>Edit Business Information
                    </a>
                </div>

                <!-- Owner Name -->
                <div>
                    <label class="block text-gray-700 font-medium mb-2" for="owner_name">
                        Full Name <span class="text-red-500">*</span>
                    </label>
                    <input type="text" id="owner_name" name="owner_name" required
                        class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-primary focus:border-transparent"
                        placeholder="Enter your full name as it appears on your ID"
                        value="<?php echo htmlspecialchars($step2_data['owner_name'] ?? ''); ?>">
                </div>

                <!-- Date of Birth -->
                <div>
                    <label class="block text-gray-700 font-medium mb-2" for="date_of_birth">
                        Date of Birth <span class="text-red-500">*</span>
                    </label>
                    <input type="date" id="date_of_birth" name="date_of_birth" required
                        class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-primary focus:border-transparent"
                        value="<?php echo htmlspecialchars($step2_data['date_of_birth'] ?? ''); ?>">
                </div>

                <!-- BVN -->
                <div>
                    <label class="block text-gray-700 font-medium mb-2" for="bvn">
                        BVN (Bank Verification Number) <span class="text-red-500">*</span>
                    </label>
                    <input type="text" id="bvn" name="bvn" required maxlength="11"
                        class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-primary focus:border-transparent"
                        placeholder="11-digit BVN"
                        value="<?php echo htmlspecialchars($step2_data['bvn'] ?? ''); ?>"
                        oninput="this.value = this.value.replace(/[^0-9]/g, '')">
                    <p class="text-gray-500 text-xs mt-1">Enter your 11-digit Bank Verification Number</p>
                </div>

                <!-- Owner Documents -->
                <div>
                    <h3 class="text-lg font-bold text-gray-800 mb-4">Owner/Director Documents</h3>
                    <p class="text-gray-500 text-sm mb-4">Upload the following documents for identity verification</p>
                    
                    <div class="space-y-4">
                        <div class="border border-gray-200 rounded-xl p-4">
                            <label class="flex items-start cursor-pointer">
                                <div class="flex-1">
                                    <div class="flex items-center mb-1">
                                        <i class="fas fa-camera text-primary mr-2"></i>
                                        <span class="font-medium text-gray-800">Passport Photo / Selfie</span>
                                        <span class="text-red-500 ml-1">*</span>
                                    </div>
                                    <p class="text-gray-500 text-xs">Clear passport photo or selfie with your face clearly visible</p>
                                </div>
                                <div class="ml-4">
                                    <input type="file" name="passport_photo" accept=".jpg,.jpeg,.png" class="hidden" id="passport_photo">
                                    <label for="passport_photo" class="cursor-pointer bg-gray-100 hover:bg-gray-200 px-4 py-2 rounded-lg text-sm font-medium text-gray-700 transition">
                                        <i class="fas fa-upload mr-1"></i>Upload
                                    </label>
                                </div>
                            </label>
                            <div id="passport_photo_name" class="mt-2 text-sm text-green-600 hidden"></div>
                        </div>

                        <div class="border border-gray-200 rounded-xl p-4">
                            <label class="flex items-start cursor-pointer">
                                <div class="flex-1">
                                    <div class="flex items-center mb-1">
                                        <i class="fas fa-id-card text-primary mr-2"></i>
                                        <span class="font-medium text-gray-800">Owner/Director ID (NIN Preferred)</span>
                                        <span class="text-red-500 ml-1">*</span>
                                    </div>
                                    <p class="text-gray-500 text-xs">National ID Card, Driver's License, or Passport</p>
                                </div>
                                <div class="ml-4">
                                    <input type="file" name="owner_id" accept=".pdf,.jpg,.jpeg,.png" class="hidden" id="owner_id">
                                    <label for="owner_id" class="cursor-pointer bg-gray-100 hover:bg-gray-200 px-4 py-2 rounded-lg text-sm font-medium text-gray-700 transition">
                                        <i class="fas fa-upload mr-1"></i>Upload
                                    </label>
                                </div>
                            </label>
                            <div id="owner_id_name" class="mt-2 text-sm text-green-600 hidden"></div>
                        </div>
                    </div>
                </div>

                <!-- Submit Button -->
                <button type="submit" 
                    class="w-full py-4 bg-primary text-white rounded-xl hover:bg-blue-700 transition font-semibold text-lg">
                    <i class="fas fa-paper-plane mr-2"></i>Submit Application
                </button>
            </form>
        </div>
        <?php endif; ?>

        <!-- Info Box -->
        <div class="mt-6 bg-blue-50 rounded-xl p-4 border border-blue-200">
            <div class="flex items-start">
                <div class="w-10 h-10 bg-blue-100 rounded-xl flex items-center justify-center flex-shrink-0">
                    <i class="fas fa-info-circle text-blue-600"></i>
                </div>
                <div class="ml-4">
                    <h4 class="font-bold text-gray-800 mb-1">What happens next?</h4>
                    <p class="text-gray-600 text-sm">Your documents will be reviewed by our team. You'll receive a reference number to track your application status.</p>
                </div>
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
            <a href="apply.php" class="flex flex-col items-center py-2 px-4 text-primary">
                <i class="fas fa-plus-circle text-xl mb-1"></i>
                <span class="text-xs font-medium">Apply</span>
            </a>
            <a href="profile.php" class="flex flex-col items-center py-2 px-4 text-gray-500 hover:text-primary transition">
                <i class="fas fa-user text-xl mb-1"></i>
                <span class="text-xs font-medium">Profile</span>
            </a>
            <a href="logout.php" class="flex flex-col items-center py-2 px-4 text-gray-500 hover:text-red-500 transition">
                <i class="fas fa-sign-out-alt text-xl mb-1"></i>
                <span class="text-xs font-medium">Logout</span>
            </a>
        </div>
    </div>

    <script>
        // Load banks from Paystack API
        const bankCodeInput = document.getElementById('bank_code');
        
        async function loadBanks() {
            const bankSelect = document.getElementById('bank_name');
            
            try {
                const response = await fetch('verify_account.php?action=banks');
                const data = await response.json();
                
                if (data.status && data.data && data.data.length > 0) {
                    bankSelect.innerHTML = '<option value="">Select bank</option>';
                    data.data.forEach(bank => {
                        const option = document.createElement('option');
                        option.value = bank.code;
                        option.textContent = bank.name;
                        bankSelect.appendChild(option);
                    });
                    
                    // Pre-select bank if stored
                    const storedBank = '<?php echo $_SESSION['application_step1']['bank_name'] ?? ''; ?>';
                    if (storedBank) {
                        bankSelect.value = storedBank;
                        bankCodeInput.value = storedBank;
                    }
                    
                    bankSelect.addEventListener('change', function() {
                        bankCodeInput.value = this.value;
                    });
                } else {
                    bankSelect.innerHTML = '<option value="">Failed to load banks</option>';
                }
            } catch (error) {
                console.error('Failed to load banks:', error);
                bankSelect.innerHTML = '<option value="">Failed to load banks (Network error)</option>';
            }
        }
        
        // Load banks on page load
        loadBanks();
        
        // Show filename when file is selected
        const fileInputs = ['cac_certificate_sp', 'cac_status_report_sp', 'utility_bill_sp', 'cac_certificate_other', 'cac_memart_other', 'cac_status_report_other', 'utility_bill_other', 'passport_photo', 'owner_id'];
        fileInputs.forEach(id => {
            const input = document.getElementById(id);
            const display = document.getElementById(id + '_name');
            if (input && display) {
                input.addEventListener('change', function() {
                    if (this.files.length > 0) {
                        display.textContent = 'Selected: ' + this.files[0].name;
                        display.classList.remove('hidden');
                    } else {
                        display.classList.add('hidden');
                    }
                });
            }
        });
        
        // Toggle documents based on business type
        const businessTypeSelect = document.getElementById('business_type');
        const docsSoleProp = document.getElementById('documents_sole_prop');
        const docsOther = document.getElementById('documents_other');
        
        function toggleDocuments() {
            const selectedType = businessTypeSelect.value;
            
            // Hide both first
            docsSoleProp.classList.add('hidden');
            docsOther.classList.add('hidden');
            
            // Disable all file inputs first
            docsSoleProp.querySelectorAll('input[type="file"]').forEach(input => {
                input.disabled = true;
            });
            docsOther.querySelectorAll('input[type="file"]').forEach(input => {
                input.disabled = true;
            });
            
            // Show appropriate section based on selection
            if (selectedType === 'Sole Proprietorship') {
                docsSoleProp.classList.remove('hidden');
                docsSoleProp.querySelectorAll('input[type="file"]').forEach(input => {
                    input.disabled = false;
                });
            } else if (selectedType !== '') {
                docsOther.classList.remove('hidden');
                docsOther.querySelectorAll('input[type="file"]').forEach(input => {
                    input.disabled = false;
                });
            }
        }
        
        if (businessTypeSelect) {
            businessTypeSelect.addEventListener('change', toggleDocuments);
            toggleDocuments(); // Initial state
        }
        
        // Paystack Account Verification
        const bankSelect = document.getElementById('bank_name');
        const accountInput = document.getElementById('bank_account');
        const verificationDiv = document.getElementById('account_verification');
        const accountNameEl = document.getElementById('account_name');
        const verificationMessage = document.getElementById('verification_message');
        const verificationStatus = document.getElementById('verification_status');
        const verifyLoading = document.getElementById('verify_loading');
        const verifiedAccountNameInput = document.getElementById('verified_account_name');
        const businessNameInput = document.getElementById('business_name');
        
        // Verify account function
        async function verifyAccount() {
            const bankCode = bankSelect.value;
            const accountNumber = accountInput.value;
            
            if (!bankCode || !accountNumber || accountNumber.length !== 10) {
                return;
            }
            
            verifyLoading.classList.remove('hidden');
            
            try {
                const response = await fetch('verify_account.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        bank_code: bankCode,
                        account_number: accountNumber
                    })
                });
                
                const data = await response.json();
                
                if (data.status && data.data.account_name) {
                    accountNameEl.textContent = data.data.account_name;
                    verifiedAccountNameInput.value = data.data.account_name;
                    verificationDiv.classList.remove('hidden');
                    
                    const businessName = businessNameInput.value.toLowerCase().trim();
                    const accountName = data.data.account_name.toLowerCase();
                    const nameMatches = accountName.includes(businessName) || businessName.includes(accountName.split(' ')[0]);
                    
                    if (nameMatches) {
                        verificationStatus.innerHTML = '<i class="fas fa-check-circle text-green-500 text-xl"></i>';
                        verificationMessage.innerHTML = '<span class="text-green-600">Account name matches your business name</span>';
                    } else {
                        verificationStatus.innerHTML = '<i class="fas fa-times-circle text-red-500 text-xl"></i>';
                        verificationMessage.innerHTML = '<span class="text-red-600 font-medium">ERROR: Account name does not match business name. Please enter a business bank account.</span>';
                        verifiedAccountNameInput.value = ''; // Clear the value so form can't be submitted
                    }
                } else {
                    verificationDiv.classList.remove('hidden');
                    accountNameEl.textContent = 'Not found';
                    verificationStatus.innerHTML = '<i class="fas fa-times-circle text-red-500 text-xl"></i>';
                    verificationMessage.innerHTML = '<span class="text-red-600">' + (data.message || 'Could not verify account') + '</span>';
                }
            } catch (error) {
                verificationDiv.classList.remove('hidden');
                accountNameEl.textContent = 'Error';
                verificationStatus.innerHTML = '<i class="fas fa-times-circle text-red-500 text-xl"></i>';
                verificationMessage.innerHTML = '<span class="text-red-600">Verification service unavailable</span>';
            } finally {
                verifyLoading.classList.add('hidden');
            }
        }
        
        // Auto-verify when account number is 10 digits and bank is selected
        if (accountInput) {
            accountInput.addEventListener('input', function() {
                if (this.value.length === 10 && bankSelect.value) {
                    verifyAccount();
                }
            });
        }
        
        if (bankSelect) {
            bankSelect.addEventListener('change', function() {
                if (accountInput.value.length === 10 && this.value) {
                    verifyAccount();
                }
            });
        }
        
        // Form submission validation - prevent submission if account name doesn't match business name
        const form = document.querySelector('form[method="POST"]');
        if (form) {
            form.addEventListener('submit', function(e) {
                const verifiedAccountName = verifiedAccountNameInput.value;
                const businessName = businessNameInput.value;
                
                // Check if account has been verified
                if (!verifiedAccountName) {
                    e.preventDefault();
                    alert('Please verify your bank account first.');
                    return false;
                }
                
                // Check if account name matches business name
                if (businessName && verifiedAccountName) {
                    const businessNameLower = businessName.toLowerCase().trim();
                    const accountNameLower = verifiedAccountName.toLowerCase();
                    
                    // Simple check: business name should be contained in account name or vice versa
                    const nameMatches = accountNameLower.includes(businessNameLower) ||
                                       businessNameLower.includes(accountNameLower.split(' ')[0]);
                    
                    if (!nameMatches) {
                        e.preventDefault();
                        alert('Bank account name must match your business name. Please enter a business bank account that matches: ' + businessName);
                        return false;
                    }
                }
            });
        }
        
        // Submission Modal Logic
        if (window.submissionSuccess) {
            const submissionModal = document.getElementById('submissionModal');
            const successModal = document.getElementById('successModal');
            const progressCircle = document.getElementById('progressCircle');
            const countdownNumber = document.getElementById('countdownNumber');
            const countdownText = document.getElementById('countdownText');
            const successProgressCircle = document.getElementById('successProgressCircle');
            const successCountdownNumber = document.getElementById('successCountdownNumber');
            const successCountdownText = document.getElementById('successCountdownText');
            
            const circumference = 2 * Math.PI * 44; // 276.32
            let totalSeconds = 10;
            let currentSecond = 0;
            
            // Show submission modal immediately
            submissionModal.classList.remove('hidden');
            
            // Animate submission progress (10 seconds)
            const submissionInterval = setInterval(() => {
                currentSecond++;
                const progress = currentSecond / totalSeconds;
                const dashOffset = circumference * (1 - progress);
                progressCircle.style.strokeDashoffset = dashOffset;
                countdownNumber.textContent = totalSeconds - currentSecond;
                countdownText.textContent = totalSeconds - currentSecond;
                
                if (currentSecond >= totalSeconds) {
                    clearInterval(submissionInterval);
                    // Hide submission modal, show success modal
                    submissionModal.classList.add('hidden');
                    successModal.classList.remove('hidden');
                    
                    // Animate success progress (10 seconds)
                    let successSecond = 0;
                    const successInterval = setInterval(() => {
                        successSecond++;
                        const progress = successSecond / totalSeconds;
                        const dashOffset = circumference * (1 - progress);
                        successProgressCircle.style.strokeDashoffset = dashOffset;
                        successCountdownNumber.textContent = totalSeconds - successSecond;
                        successCountdownText.textContent = totalSeconds - successSecond;
                        
                        if (successSecond >= totalSeconds) {
                            clearInterval(successInterval);
                            window.location.href = 'dashboard.php';
                        }
                    }, 1000);
                }
            }, 1000);
        }
    </script>
</body>
</html>
