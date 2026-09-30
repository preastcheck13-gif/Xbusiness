<?php
/**
 * X Business Grant - Nigerian Business Grant Application Portal
 * Main Landing Page
 */

session_start();

$isLoggedIn = isset($_SESSION['user_logged_in']) && $_SESSION['user_logged_in'] === true;
$userName = $_SESSION['user_name'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>X Business Grant - Nigerian Business Grant Portal</title>
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
        .hero-gradient {
            background: linear-gradient(135deg, #1e40af 0%, #1e3a8a 50%, #172554 100%);
            position: relative;
            overflow: hidden;
        }
        .hero-gradient::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%23ffffff' fill-opacity='0.03'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E");
            opacity: 0.5;
        }
        .float-animation {
            animation: float 6s ease-in-out infinite;
        }
        @keyframes float {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-20px); }
        }
        .pulse-glow {
            animation: pulse-glow 3s ease-in-out infinite;
        }
        @keyframes pulse-glow {
            0%, 100% { box-shadow: 0 0 20px rgba(245, 158, 11, 0.3); }
            50% { box-shadow: 0 0 40px rgba(245, 158, 11, 0.6); }
        }
        .counter {
            transition: all 0.3s ease;
        }
    </style>
</head>
<body class="bg-gray-50">
    <!-- Navigation -->
    <nav class="bg-white/95 backdrop-blur-md shadow-lg sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex items-center">
                    <a href="/" class="flex items-center space-x-2">
                        <div class="w-10 h-10 bg-primary rounded-lg flex items-center justify-center">
                            <span class="text-white font-bold text-xl">X</span>
                        </div>
                        <span class="text-xl font-bold text-gray-800">Business Grant</span>
                    </a>
                </div>
                <!-- Desktop Navigation -->
                <div class="hidden md:flex items-center space-x-8">
                    <a href="#about" class="text-gray-600 hover:text-primary transition">About</a>
                    <a href="#eligibility" class="text-gray-600 hover:text-primary transition">Eligibility</a>
                    <a href="apply.php" class="text-gray-600 hover:text-primary transition">Apply</a>
                    <a href="status.php" class="text-gray-600 hover:text-primary transition">Check Status</a>
                    <a href="#contact" class="text-gray-600 hover:text-primary transition">Contact</a>
                    <?php if ($isLoggedIn): ?>
                    <div class="flex items-center space-x-4">
                        <a href="dashboard.php" class="flex items-center space-x-2 text-primary hover:text-blue-700 transition">
                            <div class="w-8 h-8 bg-primary rounded-full flex items-center justify-center">
                                <span class="text-white font-bold text-sm"><?php echo strtoupper(substr($userName, 0, 1)); ?></span>
                            </div>
                            <span class="font-medium"><?php echo htmlspecialchars($userName); ?></span>
                        </a>
                        <a href="logout.php" class="text-gray-500 hover:text-red-500 transition" title="Logout">
                            <i class="fas fa-sign-out-alt"></i>
                        </a>
                    </div>
                    <?php else: ?>
                    <a href="login.php" class="text-gray-600 hover:text-primary transition">
                        <i class="fas fa-user mr-1"></i>Login
                    </a>
                    <?php endif; ?>
                    <a href="login.php" class="bg-primary text-white px-6 py-2 rounded-lg hover:bg-blue-700 transition font-medium">
                        Apply Now
                    </a>
                </div>
                
                <!-- Mobile Navigation Button -->
                <button id="mobile-menu-btn" class="md:hidden text-gray-600 hover:text-primary">
                    <i class="fas fa-bars text-xl"></i>
                </button>
            </div>
            
            <!-- Mobile Navigation Menu -->
            <div id="mobile-menu" class="hidden md:hidden pb-4">
                <div class="flex flex-col space-y-3 pt-4 border-t border-gray-100">
                    <a href="#about" class="text-gray-600 hover:text-primary transition py-2">About</a>
                    <a href="#eligibility" class="text-gray-600 hover:text-primary transition py-2">Eligibility</a>
                    <a href="apply.php" class="text-gray-600 hover:text-primary transition py-2">Apply</a>
                    <a href="status.php" class="text-gray-600 hover:text-primary transition py-2">Check Status</a>
                    <a href="#contact" class="text-gray-600 hover:text-primary transition py-2">Contact</a>
                    <?php if ($isLoggedIn): ?>
                    <a href="dashboard.php" class="flex items-center space-x-2 text-primary py-2">
                        <div class="w-8 h-8 bg-primary rounded-full flex items-center justify-center">
                            <span class="text-white font-bold text-sm"><?php echo strtoupper(substr($userName, 0, 1)); ?></span>
                        </div>
                        <span class="font-medium"><?php echo htmlspecialchars($userName); ?></span>
                    </a>
                    <a href="logout.php" class="text-red-500 hover:text-red-700 transition py-2">
                        <i class="fas fa-sign-out-alt mr-2"></i>Logout
                    </a>
                    <?php else: ?>
                    <a href="login.php" class="flex items-center space-x-2 text-primary hover:text-blue-700 transition py-2">
                        <i class="fas fa-user mr-2"></i>Login
                    </a>
                    <?php endif; ?>
                    <a href="login.php" class="bg-primary text-white px-6 py-3 rounded-lg hover:bg-blue-700 transition font-medium text-center">
                        Apply Now
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero-gradient text-white py-24 md:py-32 relative">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
            <div class="grid md:grid-cols-2 gap-12 items-center">
                <div>
                    <div class="inline-flex items-center px-4 py-2 bg-white/10 rounded-full text-sm mb-6 backdrop-blur-sm">
                        <span class="w-2 h-2 bg-green-400 rounded-full mr-2 animate-pulse"></span>
                        Applications Now Open
                    </div>
                    <h1 class="text-4xl md:text-5xl lg:text-6xl font-extrabold mb-6 leading-tight">
                        Fuel Your Business
                        <span class="text-transparent bg-clip-text bg-gradient-to-r from-amber-400 to-amber-200">
                            Growth
                        </span>
                        With Grants
                    </h1>
                    <p class="text-lg md:text-xl text-blue-100 mb-8 leading-relaxed">
                        Get up to <span class="font-bold text-white">₦5,000,000</span> in grant funding for your Nigerian business. 
                        No repayment required. Simple application process.
                    </p>
                    <div class="flex flex-col sm:flex-row gap-4">
                        <?php if ($isLoggedIn): ?>
                        <a href="dashboard.php" class="bg-amber-500 hover:bg-amber-600 text-gray-900 px-8 py-4 rounded-xl font-bold text-lg transition-all transform hover:scale-105 flex items-center justify-center">
                            <i class="fas fa-tachometer-alt mr-2"></i>Go to Dashboard
                        </a>
                        <?php else: ?>
                        <a href="login.php" class="bg-amber-500 hover:bg-amber-600 text-gray-900 px-8 py-4 rounded-xl font-bold text-lg transition-all transform hover:scale-105 flex items-center justify-center pulse-glow">
                            <i class="fas fa-rocket mr-2"></i>Apply Now
                        </a>
                        <?php endif; ?>
                        <a href="#how-it-works" class="bg-white/10 hover:bg-white/20 backdrop-blur-sm px-8 py-4 rounded-xl font-semibold text-lg transition flex items-center justify-center border border-white/20">
                            <i class="fas fa-play-circle mr-2"></i>See How It Works
                        </a>
                    </div>
                    <div class="flex items-center gap-6 mt-8 pt-8 border-t border-white/10">
                        <div>
                            <p class="text-3xl font-bold">2,500+</p>
                            <p class="text-blue-200 text-sm">Businesses Funded</p>
                        </div>
                        <div class="w-px h-12 bg-white/20"></div>
                        <div>
                            <p class="text-3xl font-bold">₦8.5B</p>
                            <p class="text-blue-200 text-sm">Total Disbursed</p>
                        </div>
                        <div class="w-px h-12 bg-white/20"></div>
                        <div>
                            <p class="text-3xl font-bold">36</p>
                            <p class="text-blue-200 text-sm">States Covered</p>
                        </div>
                    </div>
                </div>
                <div class="hidden md:block relative">
                    <div class="absolute inset-0 bg-gradient-to-r from-amber-400 to-amber-600 rounded-3xl blur-3xl opacity-20"></div>
                    <div class="relative bg-white/10 backdrop-blur-lg rounded-3xl p-8 border border-white/20">
                        <div class="text-center mb-6">
                            <div class="inline-block p-4 bg-amber-500 rounded-2xl mb-4 float-animation">
                                <i class="fas fa-naira-sign text-4xl text-gray-900"></i>
                            </div>
                            <h3 class="text-2xl font-bold">Quick Grant Calculator</h3>
                        </div>
                        <div class="space-y-4">
                            <div class="bg-white/10 rounded-xl p-4">
                                <div class="flex justify-between mb-2">
                                    <span class="text-blue-200">Grant Amount</span>
                                    <span class="font-bold">₦2,500,000</span>
                                </div>
                                <div class="w-full bg-white/20 rounded-full h-2">
                                    <div class="bg-amber-400 h-2 rounded-full" style="width: 50%"></div>
                                </div>
                            </div>
                            <div class="grid grid-cols-2 gap-4">
                                <div class="bg-white/10 rounded-xl p-4 text-center">
                                    <p class="text-3xl font-bold text-amber-400">0%</p>
                                    <p class="text-blue-200 text-sm">Interest Rate</p>
                                </div>
                                <div class="bg-white/10 rounded-xl p-4 text-center">
                                    <p class="text-3xl font-bold text-amber-400">14</p>
                                    <p class="text-blue-200 text-sm">Days Review</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- Wave Divider -->
        <div class="absolute bottom-0 left-0 right-0">
            <svg viewBox="0 0 1440 120" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M0 120L60 110C120 100 240 80 360 70C480 60 600 60 720 65C840 70 960 80 1080 85C1200 90 1320 90 1380 90L1440 90V120H1380C1320 120 1200 120 1080 120C960 120 840 120 720 120C600 120 480 120 360 120C240 120 120 120 60 120H0Z" fill="#f9fafb"/>
            </svg>
        </div>
    </section>

    <!-- Stats Section -->
    <section class="py-12 bg-gray-50 -mt-1">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-2 md:grid-cols-4 gap-8 text-center">
                <div class="group">
                    <div class="text-4xl md:text-5xl font-bold text-primary mb-2 counter">₦5M</div>
                    <div class="text-gray-600 font-medium">Maximum Grant</div>
                </div>
                <div>
                    <div class="text-4xl md:text-5xl font-bold text-primary mb-2">₦500K</div>
                    <div class="text-gray-600 font-medium">Minimum Grant</div>
                </div>
                <div>
                    <div class="text-4xl md:text-5xl font-bold text-primary mb-2">100%</div>
                    <div class="text-gray-600 font-medium">Free Application</div>
                </div>
                <div>
                    <div class="text-4xl md:text-5xl font-bold text-primary mb-2">14</div>
                    <div class="text-gray-600 font-medium">Days Average Review</div>
                </div>
            </div>
        </div>
    </section>

    <!-- About Section -->
    <section id="about" class="py-20 bg-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-16">
                <span class="inline-block px-4 py-1 bg-blue-100 text-primary rounded-full text-sm font-semibold mb-4">About Us</span>
                <h2 class="text-3xl md:text-4xl font-bold text-gray-800 mb-4">About X Business Grant</h2>
                <p class="text-gray-600 max-w-2xl mx-auto text-lg">
                    X Business Grant is a Nigerian government-supported initiative to foster economic growth 
                    by providing financial assistance to registered businesses across all 36 states.
                </p>
            </div>
            <div class="grid md:grid-cols-3 gap-8">
                <div class="bg-gray-50 p-8 rounded-2xl hover:shadow-xl transition duration-300 border border-gray-100">
                    <div class="w-14 h-14 bg-blue-100 rounded-2xl flex items-center justify-center mb-6">
                        <i class="fas fa-hand-holding-usd text-primary text-2xl"></i>
                    </div>
                    <h3 class="text-xl font-bold mb-3">Direct Funding</h3>
                    <p class="text-gray-600 leading-relaxed">Receive grant funds directly to your business account upon approval. No repayment required. Keep 100% of the funds.</p>
                </div>
                <div class="bg-gray-50 p-8 rounded-2xl hover:shadow-xl transition duration-300 border border-gray-100">
                    <div class="w-14 h-14 bg-green-100 rounded-2xl flex items-center justify-center mb-6">
                        <i class="fas fa-shield-alt text-green-600 text-2xl"></i>
                    </div>
                    <h3 class="text-xl font-bold mb-3">Secure Process</h3>
                    <p class="text-gray-600 leading-relaxed">Your application and documents are handled with complete confidentiality and enterprise-grade security.</p>
                </div>
                <div class="bg-gray-50 p-8 rounded-2xl hover:shadow-xl transition duration-300 border border-gray-100">
                    <div class="w-14 h-14 bg-purple-100 rounded-2xl flex items-center justify-center mb-6">
                        <i class="fas fa-clock text-purple-600 text-2xl"></i>
                    </div>
                    <h3 class="text-xl font-bold mb-3">Quick Review</h3>
                    <p class="text-gray-600 leading-relaxed">Our streamlined process ensures your application is reviewed within 14 business days maximum.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Eligibility Section -->
    <section id="eligibility" class="py-20 bg-gray-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-16">
                <span class="inline-block px-4 py-1 bg-green-100 text-green-700 rounded-full text-sm font-semibold mb-4">Requirements</span>
                <h2 class="text-3xl md:text-4xl font-bold text-gray-800 mb-4">Eligibility Requirements</h2>
                <p class="text-gray-600 max-w-2xl mx-auto text-lg">
                    To qualify for X Business Grant, your business must meet the following criteria
                </p>
            </div>
            <div class="grid md:grid-cols-2 gap-8">
                <div class="space-y-4">
                    <div class="flex items-start space-x-4 bg-white p-5 rounded-xl shadow-sm hover:shadow-md transition">
                        <div class="w-10 h-10 bg-green-100 rounded-xl flex items-center justify-center flex-shrink-0">
                            <i class="fas fa-check text-green-600"></i>
                        </div>
                        <div>
                            <h4 class="font-bold text-gray-800">Valid CAC Registration</h4>
                            <p class="text-gray-600 text-sm">Business must have a valid Corporate Affairs Commission registration</p>
                        </div>
                    </div>
                    <div class="flex items-start space-x-4 bg-white p-5 rounded-xl shadow-sm hover:shadow-md transition">
                        <div class="w-10 h-10 bg-green-100 rounded-xl flex items-center justify-center flex-shrink-0">
                            <i class="fas fa-check text-green-600"></i>
                        </div>
                        <div>
                            <h4 class="font-bold text-gray-800">Nigerian Operations</h4>
                            <p class="text-gray-600 text-sm">Business must be actively operating within Nigeria</p>
                        </div>
                    </div>
                    <div class="flex items-start space-x-4 bg-white p-5 rounded-xl shadow-sm hover:shadow-md transition">
                        <div class="w-10 h-10 bg-green-100 rounded-xl flex items-center justify-center flex-shrink-0">
                            <i class="fas fa-check text-green-600"></i>
                        </div>
                        <div>
                            <h4 class="font-bold text-gray-800">Minimum 1 Year Operations</h4>
                            <p class="text-gray-600 text-sm">Business must have been operating for at least one year</p>
                        </div>
                    </div>
                    <div class="flex items-start space-x-4 bg-white p-5 rounded-xl shadow-sm hover:shadow-md transition">
                        <div class="w-10 h-10 bg-green-100 rounded-xl flex items-center justify-center flex-shrink-0">
                            <i class="fas fa-check text-green-600"></i>
                        </div>
                        <div>
                            <h4 class="font-bold text-gray-800">Valid Bank Account</h4>
                            <p class="text-gray-600 text-sm">Business must have a functional Nigerian bank account</p>
                        </div>
                    </div>
                </div>
                <div class="space-y-4">
                    <div class="flex items-start space-x-4 bg-white p-5 rounded-xl shadow-sm hover:shadow-md transition">
                        <div class="w-10 h-10 bg-green-100 rounded-xl flex items-center justify-center flex-shrink-0">
                            <i class="fas fa-check text-green-600"></i>
                        </div>
                        <div>
                            <h4 class="font-bold text-gray-800">Tax Compliance</h4>
                            <p class="text-gray-600 text-sm">Business must have tax identification and comply with tax obligations</p>
                        </div>
                    </div>
                    <div class="flex items-start space-x-4 bg-white p-5 rounded-xl shadow-sm hover:shadow-md transition">
                        <div class="w-10 h-10 bg-green-100 rounded-xl flex items-center justify-center flex-shrink-0">
                            <i class="fas fa-check text-green-600"></i>
                        </div>
                        <div>
                            <h4 class="font-bold text-gray-800">Clear Business Plan</h4>
                            <p class="text-gray-600 text-sm">Must have a documented business plan with clear objectives</p>
                        </div>
                    </div>
                    <div class="flex items-start space-x-4 bg-white p-5 rounded-xl shadow-sm hover:shadow-md transition">
                        <div class="w-10 h-10 bg-green-100 rounded-xl flex items-center justify-center flex-shrink-0">
                            <i class="fas fa-check text-green-600"></i>
                        </div>
                        <div>
                            <h4 class="font-bold text-gray-800">No Criminal Record</h4>
                            <p class="text-gray-600 text-sm">Business owner must have no criminal convictions</p>
                        </div>
                    </div>
                    <div class="flex items-start space-x-4 bg-white p-5 rounded-xl shadow-sm hover:shadow-md transition">
                        <div class="w-10 h-10 bg-green-100 rounded-xl flex items-center justify-center flex-shrink-0">
                            <i class="fas fa-check text-green-600"></i>
                        </div>
                        <div>
                            <h4 class="font-bold text-gray-800">Not in Bankruptcy</h4>
                            <p class="text-gray-600 text-sm">Business must not be under any insolvency proceedings</p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="text-center mt-12">
                <a href="login.php" class="inline-flex items-center bg-primary text-white px-8 py-4 rounded-xl font-bold text-lg hover:bg-blue-700 transition transform hover:scale-105">
                    <i class="fas fa-paper-plane mr-2"></i>Apply Now
                </a>
            </div>
        </div>
    </section>

    <!-- How It Works Section -->
    <section id="how-it-works" class="py-20 bg-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-16">
                <span class="inline-block px-4 py-1 bg-purple-100 text-purple-700 rounded-full text-sm font-semibold mb-4">Process</span>
                <h2 class="text-3xl md:text-4xl font-bold text-gray-800 mb-4">How It Works</h2>
                <p class="text-gray-600 max-w-2xl mx-auto text-lg">
                    Simple 4-step process to get your business grant approved
                </p>
            </div>
            <div class="grid md:grid-cols-4 gap-8 relative">
                <!-- Connecting Line -->
                <div class="hidden md:block absolute top-16 left-1/4 right-1/4 h-1 bg-gradient-to-r from-primary via-secondary to-amber-500 rounded-full"></div>
                <div class="text-center relative">
                    <div class="w-16 h-16 bg-primary rounded-2xl flex items-center justify-center mx-auto mb-6 relative z-10 shadow-lg">
                        <span class="text-white text-2xl font-bold">1</span>
                    </div>
                    <h3 class="text-xl font-bold mb-2">Check Eligibility</h3>
                    <p class="text-gray-600">Review the eligibility criteria to ensure you qualify for the grant</p>
                </div>
                <div class="text-center relative">
                    <div class="w-16 h-16 bg-secondary rounded-2xl flex items-center justify-center mx-auto mb-6 relative z-10 shadow-lg">
                        <span class="text-white text-2xl font-bold">2</span>
                    </div>
                    <h3 class="text-xl font-bold mb-2">Submit Application</h3>
                    <p class="text-gray-600">Complete the online form and upload required documents</p>
                </div>
                <div class="text-center relative">
                    <div class="w-16 h-16 bg-purple-600 rounded-2xl flex items-center justify-center mx-auto mb-6 relative z-10 shadow-lg">
                        <span class="text-white text-2xl font-bold">3</span>
                    </div>
                    <h3 class="text-xl font-bold mb-2">Review Process</h3>
                    <p class="text-gray-600">Our team reviews your application within 14 business days</p>
                </div>
                <div class="text-center relative">
                    <div class="w-16 h-16 bg-amber-500 rounded-2xl flex items-center justify-center mx-auto mb-6 relative z-10 shadow-lg">
                        <span class="text-gray-900 text-2xl font-bold">4</span>
                    </div>
                    <h3 class="text-xl font-bold mb-2">Receive Funds</h3>
                    <p class="text-gray-600">Get your grant funds deposited directly to your account</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Testimonials Section -->
    <section class="py-20 bg-gradient-to-br from-primary to-blue-800 text-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-16">
                <span class="inline-block px-4 py-1 bg-white/20 rounded-full text-sm font-semibold mb-4">Testimonials</span>
                <h2 class="text-3xl md:text-4xl font-bold mb-4">What Business Owners Say</h2>
                <p class="text-blue-100 max-w-2xl mx-auto text-lg">
                    Real stories from Nigerian entrepreneurs who received grants
                </p>
            </div>
            <div class="grid md:grid-cols-3 gap-8">
                <div class="bg-white/10 backdrop-blur-sm p-8 rounded-2xl border border-white/20">
                    <div class="flex items-center mb-4">
                        <div class="flex text-amber-400">
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                        </div>
                    </div>
                    <p class="text-blue-100 mb-6 leading-relaxed italic">
                        "X Business Grant helped me expand my poultry farm from 500 to 2000 birds. The application was straightforward and the funds arrived within 2 weeks of approval."
                    </p>
                    <div class="flex items-center">
                        <div class="w-12 h-12 bg-amber-500 rounded-full flex items-center justify-center text-gray-900 font-bold text-lg mr-4">
                            AA
                        </div>
                        <div>
                            <p class="font-bold">Adaeze Amadi</p>
                            <p class="text-blue-200 text-sm">Poultry Farmer, Lagos</p>
                        </div>
                    </div>
                </div>
                <div class="bg-white/10 backdrop-blur-sm p-8 rounded-2xl border border-white/20">
                    <div class="flex items-center mb-4">
                        <div class="flex text-amber-400">
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                        </div>
                    </div>
                    <p class="text-blue-100 mb-6 leading-relaxed italic">
                        "As a tech startup, we used the grant to hire 3 more developers and launch our app. The process was transparent and the team was very supportive throughout."
                    </p>
                    <div class="flex items-center">
                        <div class="w-12 h-12 bg-blue-400 rounded-full flex items-center justify-center text-white font-bold text-lg mr-4">
                            KO
                        </div>
                        <div>
                            <p class="font-bold">Kayode Okonkwo</p>
                            <p class="text-blue-200 text-sm">Tech Founder, Abuja</p>
                        </div>
                    </div>
                </div>
                <div class="bg-white/10 backdrop-blur-sm p-8 rounded-2xl border border-white/20">
                    <div class="flex items-center mb-4">
                        <div class="flex text-amber-400">
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star-half-alt"></i>
                        </div>
                    </div>
                    <p class="text-blue-100 mb-6 leading-relaxed italic">
                        "The ₦3 million grant enabled us to purchase a delivery van and expand our restaurant to a second location. Best decision I made for my business."
                    </p>
                    <div class="flex items-center">
                        <div class="w-12 h-12 bg-green-500 rounded-full flex items-center justify-center text-white font-bold text-lg mr-4">
                            CN
                        </div>
                        <div>
                            <p class="font-bold">Chidinma Nwankwo</p>
                            <p class="text-blue-200 text-sm">Restaurant Owner, Enugu</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="py-20 bg-amber-500">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
            <h2 class="text-3xl md:text-4xl font-bold text-gray-900 mb-4">Ready to Grow Your Business?</h2>
            <p class="text-gray-800 text-lg mb-8 max-w-2xl mx-auto">
                Join thousands of Nigerian businesses that have received funding through X Business Grant. Your grant application takes just 15 minutes to complete.
            </p>
            <div class="flex flex-col sm:flex-row gap-4 justify-center">
                <?php if ($isLoggedIn): ?>
                <a href="dashboard.php" class="bg-gray-900 text-white px-8 py-4 rounded-xl font-bold text-lg hover:bg-gray-800 transition transform hover:scale-105 inline-flex items-center justify-center">
                    <i class="fas fa-tachometer-alt mr-2"></i>Go to Dashboard
                </a>
                <?php else: ?>
                <a href="login.php" class="bg-gray-900 text-white px-8 py-4 rounded-xl font-bold text-lg hover:bg-gray-800 transition transform hover:scale-105 inline-flex items-center justify-center">
                    <i class="fas fa-rocket mr-2"></i>Start Your Application
                </a>
                <?php endif; ?>
                <a href="status.php" class="bg-white text-gray-900 px-8 py-4 rounded-xl font-bold text-lg hover:bg-gray-100 transition inline-flex items-center justify-center">
                    <i class="fas fa-search mr-2"></i>Check Application Status
                </a>
            </div>
        </div>
    </section>

    <!-- Contact Section -->
    <section id="contact" class="py-16 bg-gray-900 text-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid md:grid-cols-3 gap-8">
                <div>
                    <div class="flex items-center space-x-2 mb-4">
                        <div class="w-10 h-10 bg-primary rounded-lg flex items-center justify-center">
                            <span class="text-white font-bold text-xl">X</span>
                        </div>
                        <span class="text-xl font-bold">Business Grant</span>
                    </div>
                    <p class="text-gray-400 mb-4">
                        Empowering Nigerian businesses with financial support to grow and succeed.
                    </p>
                    <div class="flex space-x-4">
                        <a href="#" class="w-10 h-10 bg-white/10 rounded-lg flex items-center justify-center hover:bg-primary transition">
                            <i class="fab fa-facebook-f"></i>
                        </a>
                        <a href="#" class="w-10 h-10 bg-white/10 rounded-lg flex items-center justify-center hover:bg-primary transition">
                            <i class="fab fa-twitter"></i>
                        </a>
                        <a href="#" class="w-10 h-10 bg-white/10 rounded-lg flex items-center justify-center hover:bg-primary transition">
                            <i class="fab fa-instagram"></i>
                        </a>
                        <a href="#" class="w-10 h-10 bg-white/10 rounded-lg flex items-center justify-center hover:bg-primary transition">
                            <i class="fab fa-linkedin-in"></i>
                        </a>
                    </div>
                </div>
                <div>
                    <h4 class="font-bold mb-4">Contact Us</h4>
                    <div class="space-y-3 text-gray-400">
                        <p><i class="fas fa-envelope mr-3 text-primary"></i>support@xbusinessgrant.ng</p>
                        <p><i class="fas fa-phone mr-3 text-primary"></i>0800-924-4357</p>
                        <p><i class="fas fa-map-marker-alt mr-3 text-primary"></i>Plot 256, Ademola Adetokunbo Crescent, Abuja</p>
                    </div>
                </div>
                <div>
                    <h4 class="font-bold mb-4">Quick Links</h4>
                    <div class="space-y-2">
                        <a href="#about" class="block text-gray-400 hover:text-white transition">About Us</a>
                        <a href="#eligibility" class="block text-gray-400 hover:text-white transition">Eligibility</a>
                        <a href="login.php" class="block text-gray-400 hover:text-white transition">Apply Now</a>
                        <a href="status.php" class="block text-gray-400 hover:text-white transition">Check Status</a>
                        <a href="#" class="block text-gray-400 hover:text-white transition">FAQ</a>
                    </div>
                </div>
            </div>
            <div class="border-t border-gray-800 mt-12 pt-8 text-center text-gray-500">
                <p>&copy; <?php echo date('Y'); ?> X Business Grant. All rights reserved. | A Federal Government Initiative</p>
            </div>
        </div>
    </section>

    <script>
        // Mobile menu toggle
        const mobileMenuBtn = document.getElementById('mobile-menu-btn');
        const mobileMenu = document.getElementById('mobile-menu');
        
        mobileMenuBtn.addEventListener('click', function() {
            mobileMenu.classList.toggle('hidden');
            const icon = mobileMenuBtn.querySelector('i');
            if (mobileMenu.classList.contains('hidden')) {
                icon.classList.remove('fa-times');
                icon.classList.add('fa-bars');
            } else {
                icon.classList.remove('fa-bars');
                icon.classList.add('fa-times');
            }
        });

        // Smooth scroll for anchor links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({ behavior: 'smooth' });
                    // Close mobile menu if open
                    mobileMenu.classList.add('hidden');
                    const icon = mobileMenuBtn.querySelector('i');
                    icon.classList.remove('fa-times');
                    icon.classList.add('fa-bars');
                }
            });
        });

        // Simple counter animation
        const counters = document.querySelectorAll('.counter');
        counters.forEach(counter => {
            counter.style.opacity = '1';
        });
    </script>
</body>
</html>
