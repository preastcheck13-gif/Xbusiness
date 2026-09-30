<?php
/**
 * X Business Grant - Paystack Account Verification API
 * Uses Paystack API to verify Nigerian bank account details
 *
 * Endpoints:
 * - GET ?action=banks - Returns list of Nigerian banks with Paystack codes
 * - POST /verify - Verifies bank account number
 */

require_once __DIR__ . '/includes/Database.php';
require_once __DIR__ . '/includes/Settings.php';

// Set response header
header('Content-Type: application/json');

// Get Paystack secret key from database settings
$paystackSecretKey = \App\Settings::getPaystackSecretKey();

// For testing, use a demo key if not configured (REMOVE IN PRODUCTION)
if (empty($paystackSecretKey)) {
    $paystackSecretKey = 'sk_test_demo'; // Demo key for testing
}

// Fallback bank list (commonly used Nigerian banks)
$fallbackBanks = [
    ['code' => '044', 'name' => 'Access Bank', 'type' => 'bank'],
    ['code' => '014', 'name' => 'Afribank', 'type' => 'bank'],
    ['code' => '023', 'name' => 'Citibank', 'type' => 'bank'],
    ['code' => '063', 'name' => 'Diamond Bank', 'type' => 'bank'],
    ['code' => '050', 'name' => 'EcoBank', 'type' => 'bank'],
    ['code' => '011', 'name' => 'First Bank of Nigeria', 'type' => 'bank'],
    ['code' => '214', 'name' => 'First City Monument Bank', 'type' => 'bank'],
    ['code' => '058', 'name' => 'Guaranty Trust Bank', 'type' => 'bank'],
    ['code' => '301', 'name' => 'Heritage Bank', 'type' => 'bank'],
    ['code' => '030', 'name' => 'Heritage Bank', 'type' => 'bank'],
    ['code' => '082', 'name' => 'Keystone Bank', 'type' => 'bank'],
    ['code' => '076', 'name' => 'Skye Bank', 'type' => 'bank'],
    ['code' => '221', 'name' => 'Stanbic IBTC Bank', 'type' => 'bank'],
    ['code' => '068', 'name' => 'Standard Chartered Bank', 'type' => 'bank'],
    ['code' => '232', 'name' => 'Sterling Bank', 'type' => 'bank'],
    ['code' => '100', 'name' => 'Suntrust Bank', 'type' => 'bank'],
    ['code' => '033', 'name' => 'United Bank for Africa', 'type' => 'bank'],
    ['code' => '215', 'name' => 'Unity Bank', 'type' => 'bank'],
    ['code' => '035', 'name' => 'Wema Bank', 'type' => 'bank'],
    ['code' => '057', 'name' => 'Zenith Bank', 'type' => 'bank'],
];

// Check if requesting bank list (GET request)
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'banks') {
    $url = "https://api.paystack.co/bank";
    
    $ch = curl_init();
    
    // Set CA certificate path for SSL verification
    $caCertPath = __DIR__ . '/cacert.pem';
    $sslOptions = [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            "Authorization: Bearer {$paystackSecretKey}",
            "Content-Type: application/json"
        ],
        CURLOPT_TIMEOUT => 30,
        CURLOPT_SSL_VERIFYPEER => true
    ];
    
    // Use bundled CA certs if available, otherwise disable verification
    if (file_exists($caCertPath)) {
        $sslOptions[CURLOPT_CAINFO] = $caCertPath;
    } else {
        $sslOptions[CURLOPT_SSL_VERIFYPEER] = false;
        $sslOptions[CURLOPT_SSL_VERIFYHOST] = 0;
    }
    
    curl_setopt_array($ch, $sslOptions);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    if ($error || empty($response)) {
        // Return fallback banks with warning
        http_response_code(200);
        echo json_encode([
            'status' => true,
            'data' => $fallbackBanks,
            'warning' => 'Using fallback bank list. cURL error: ' . ($error ?: 'Empty response'),
            'debug' => ['curl_error' => $error, 'http_code' => $httpCode, 'empty_response' => empty($response)]
        ]);
        exit;
    }
    
    $responseData = json_decode($response, true);
    
    // Handle JSON decode failure
    if ($responseData === null && json_last_error() !== JSON_ERROR_NONE) {
        http_response_code(200);
        echo json_encode([
            'status' => true,
            'data' => $fallbackBanks,
            'warning' => 'Using fallback bank list. JSON decode error: ' . json_last_error_msg(),
            'debug' => ['json_error' => json_last_error_msg(), 'response_preview' => substr($response, 0, 200)]
        ]);
        exit;
    }
    
    // Log response for debugging
    error_log("Paystack bank list response: HTTP $httpCode - " . substr($response, 0, 500));
    
    if ($httpCode === 200 && isset($responseData['status']) && $responseData['status'] === true) {
        // Filter to only Nigerian banks and format for dropdown
        $banks = [];
        if (isset($responseData['data']) && is_array($responseData['data'])) {
            foreach ($responseData['data'] as $bank) {
                // Check both country_code and country fields for compatibility
                $country = $bank['country_code'] ?? $bank['country'] ?? '';
                if ($country === 'NG' || strtolower($country) === 'nigeria') {
                    $banks[] = [
                        'code' => $bank['code'],
                        'name' => $bank['name'],
                        'type' => $bank['type'] ?? 'bank'
                    ];
                }
            }
        }
        
        // Sort alphabetically
        usort($banks, function($a, $b) {
            return strcmp($a['name'], $b['name']);
        });
        
        // If no banks from Paystack API (empty response), use fallback list
        if (empty($banks)) {
            http_response_code(200);
            echo json_encode([
                'status' => true,
                'data' => $fallbackBanks,
                'warning' => 'Using fallback bank list. Paystack returned no banks (check API key).'
            ]);
        } else {
            http_response_code(200);
            echo json_encode([
                'status' => true,
                'data' => $banks
            ]);
        }
    } else {
        // Return fallback banks with warning
        http_response_code(200);
        echo json_encode([
            'status' => true,
            'data' => $fallbackBanks,
            'warning' => 'Using fallback bank list. Paystack error: ' . ($responseData['message'] ?? 'Unknown error'),
            'debug' => ['http_code' => $httpCode, 'response_preview' => substr($response, 0, 200)]
        ]);
    }
    exit;
}

// Account verification (POST request)
$input = json_decode(file_get_contents('php://input'), true);

if (!isset($input['bank_code']) || !isset($input['account_number'])) {
    http_response_code(400);
    echo json_encode([
        'status' => false,
        'message' => 'Missing required parameters: bank_code and account_number'
    ]);
    exit;
}

$bankCode = trim($input['bank_code']);
$accountNumber = trim($input['account_number']);

// Validate account number (should be 10 digits)
if (strlen($accountNumber) !== 10 || !is_numeric($accountNumber)) {
    http_response_code(400);
    echo json_encode([
        'status' => false,
        'message' => 'Account number must be 10 digits'
    ]);
    exit;
}

// Paystack API endpoint for account verification
$url = "https://api.paystack.co/bank/resolve?account_number={$accountNumber}&bank_code={$bankCode}";

$ch = curl_init();

// Set CA certificate path for SSL verification
$caCertPath = __DIR__ . '/cacert.pem';
$sslOptions = [
    CURLOPT_URL => $url,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => [
        "Authorization: Bearer {$paystackSecretKey}",
        "Content-Type: application/json"
    ],
    CURLOPT_TIMEOUT => 30,
    CURLOPT_SSL_VERIFYPEER => true
];

// Use bundled CA certs if available, otherwise disable verification
if (file_exists($caCertPath)) {
    $sslOptions[CURLOPT_CAINFO] = $caCertPath;
} else {
    $sslOptions[CURLOPT_SSL_VERIFYPEER] = false;
    $sslOptions[CURLOPT_SSL_VERIFYHOST] = 0;
}

curl_setopt_array($ch, $sslOptions);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

if ($error) {
    http_response_code(500);
    echo json_encode([
        'status' => false,
        'message' => 'cURL Error: ' . $error,
        'debug' => ['curl_error' => $error, 'http_code' => $httpCode]
    ]);
    exit;
}

$responseData = json_decode($response, true);

// Log for debugging
error_log("Paystack account resolve: URL=$url, HTTP=$httpCode, Response=" . substr($response, 0, 300));

if ($httpCode === 200 && isset($responseData['status']) && $responseData['status'] === true) {
    // Success - return account name
    http_response_code(200);
    echo json_encode([
        'status' => true,
        'message' => 'Account verified successfully',
        'data' => [
            'account_number' => $responseData['data']['account_number'],
            'account_name' => $responseData['data']['account_name'],
            'bank_code' => $bankCode
        ]
    ]);
} elseif ($httpCode === 404 || (isset($responseData['status']) && $responseData['status'] === false)) {
    // Account not found
    http_response_code(404);
    echo json_encode([
        'status' => false,
        'message' => $responseData['message'] ?? 'Account not found. Please check the account number and bank.'
    ]);
} else {
    // Other errors - include debug info
    http_response_code($httpCode ?: 500);
    echo json_encode([
        'status' => false,
        'message' => $responseData['message'] ?? 'Failed to verify account',
        'debug' => [
            'http_code' => $httpCode,
            'response' => $responseData,
            'raw_response' => substr($response, 0, 500)
        ]
    ]);
}
