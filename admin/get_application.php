<?php
/**
 * X Business Grant - Get Application Details (AJAX)
 */

require_once __DIR__ . '/../includes/Database.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/Application.php';

use App\Helpers;
use App\Application;

header('Content-Type: text/html; charset=utf-8');

$id = (int)($_GET['id'] ?? 0);

if (!$id) {
    echo '<p class="text-red-500">Invalid application ID</p>';
    exit;
}

$app = new Application();
$application = $app->getById($id);

if (!$application) {
    echo '<p class="text-red-500">Application not found</p>';
    exit;
}

$documents = $app->getDocuments($id);
?>

<div class="space-y-6">
    <!-- Reference and Status -->
    <div class="flex justify-between items-start">
        <div>
            <p class="text-sm text-gray-500">Reference Number</p>
            <p class="text-2xl font-bold font-mono text-primary"><?php echo htmlspecialchars($application['reference_no']); ?></p>
        </div>
        <span class="px-4 py-2 rounded-full text-sm font-medium <?php echo Helpers::getStatusClass($application['status']); ?>">
            <?php echo ucfirst(str_replace('_', ' ', $application['status'])); ?>
        </span>
    </div>

    <!-- Business Information -->
    <div class="border-b border-gray-200 pb-4">
        <h4 class="font-semibold text-gray-800 mb-3 flex items-center">
            <i class="fas fa-building text-primary mr-2"></i>Business Information
        </h4>
        <div class="grid grid-cols-2 gap-4 text-sm">
            <div>
                <p class="text-gray-500">Business Name</p>
                <p class="font-medium"><?php echo htmlspecialchars($application['business_name']); ?></p>
            </div>
            <div>
                <p class="text-gray-500">CAC Number</p>
                <p class="font-medium"><?php echo htmlspecialchars($application['cac_number']); ?></p>
            </div>
            <div>
                <p class="text-gray-500">Business Registration Number</p>
                <p class="font-medium"><?php echo htmlspecialchars($application['business_registration_number'] ?? 'N/A'); ?></p>
            </div>
            <div>
                <p class="text-gray-500">Business Type</p>
                <p class="font-medium"><?php echo htmlspecialchars($application['business_type']); ?></p>
            </div>
            <div>
                <p class="text-gray-500">Business Sector</p>
                <p class="font-medium"><?php echo htmlspecialchars($application['business_sector']); ?></p>
            </div>
        </div>
    </div>

    <!-- Address -->
    <div class="border-b border-gray-200 pb-4">
        <h4 class="font-semibold text-gray-800 mb-3 flex items-center">
            <i class="fas fa-map-marker-alt text-primary mr-2"></i>Business Address
        </h4>
        <p class="text-sm">
            <?php echo htmlspecialchars($application['business_address']); ?><br>
            <?php echo htmlspecialchars($application['city']); ?>, <?php echo htmlspecialchars($application['state']); ?>
            <?php if ($application['lga']): ?>
                <br>LGA: <?php echo htmlspecialchars($application['lga']); ?>
            <?php endif; ?>
        </p>
    </div>

    <!-- Contact -->
    <div class="border-b border-gray-200 pb-4">
        <h4 class="font-semibold text-gray-800 mb-3 flex items-center">
            <i class="fas fa-phone-alt text-primary mr-2"></i>Contact Information
        </h4>
        <div class="grid grid-cols-2 gap-4 text-sm">
            <div>
                <p class="text-gray-500">Phone</p>
                <p class="font-medium"><?php echo htmlspecialchars($application['phone']); ?></p>
            </div>
            <div>
                <p class="text-gray-500">Email</p>
                <p class="font-medium"><?php echo htmlspecialchars($application['email']); ?></p>
            </div>
            <?php if ($application['website']): ?>
            <div class="col-span-2">
                <p class="text-gray-500">Website</p>
                <p class="font-medium"><?php echo htmlspecialchars($application['website']); ?></p>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Business Details -->
    <div class="border-b border-gray-200 pb-4">
        <h4 class="font-semibold text-gray-800 mb-3 flex items-center">
            <i class="fas fa-chart-line text-primary mr-2"></i>Business Details
        </h4>
        <div class="grid grid-cols-2 gap-4 text-sm">
            <div>
                <p class="text-gray-500">Years in Business</p>
                <p class="font-medium"><?php echo (int)$application['years_in_business']; ?> years</p>
            </div>
            <div>
                <p class="text-gray-500">Employees</p>
                <p class="font-medium"><?php echo (int)$application['employee_count']; ?></p>
            </div>
            <div class="col-span-2">
                <p class="text-gray-500">Grant Amount Requested</p>
                <p class="text-xl font-bold text-green-600"><?php echo Helpers::formatNaira($application['grant_amount_requested']); ?></p>
            </div>
        </div>
    </div>

    <!-- Bank Details -->
    <div class="border-b border-gray-200 pb-4">
        <h4 class="font-semibold text-gray-800 mb-3 flex items-center">
            <i class="fas fa-university text-primary mr-2"></i>Bank Details
        </h4>
        <div class="grid grid-cols-2 gap-4 text-sm">
            <div>
                <p class="text-gray-500">Bank Name</p>
                <p class="font-medium"><?php echo htmlspecialchars($application['bank_name'] ?? 'N/A'); ?></p>
            </div>
            <div>
                <p class="text-gray-500">Bank Code</p>
                <p class="font-medium"><?php echo htmlspecialchars($application['bank_code'] ?? 'N/A'); ?></p>
            </div>
            <div class="col-span-2">
                <p class="text-gray-500">Account Number</p>
                <p class="font-medium"><?php echo htmlspecialchars($application['bank_account_number'] ?? 'N/A'); ?></p>
            </div>
            <div class="col-span-2">
                <p class="text-gray-500">Verified Account Name</p>
                <p class="font-medium">
                    <?php echo htmlspecialchars($application['verified_account_name'] ?? 'N/A'); ?>
                    <?php if (!empty($application['bank_verified'])): ?>
                        <span class="ml-2 px-2 py-1 bg-green-100 text-green-700 text-xs rounded-full">
                            <i class="fas fa-check-circle mr-1"></i>Verified
                        </span>
                    <?php else: ?>
                        <span class="ml-2 px-2 py-1 bg-yellow-100 text-yellow-700 text-xs rounded-full">
                            <i class="fas fa-exclamation-circle mr-1"></i>Not Verified
                        </span>
                    <?php endif; ?>
                </p>
            </div>
        </div>
    </div>

    <!-- Purpose -->
    <div class="border-b border-gray-200 pb-4">
        <h4 class="font-semibold text-gray-800 mb-3 flex items-center">
            <i class="fas fa-flag text-primary mr-2"></i>Purpose of Grant
        </h4>
        <p class="text-sm text-gray-700"><?php echo nl2br(htmlspecialchars($application['purpose_of_grant'])); ?></p>
    </div>

    <!-- Business Description -->
    <div class="border-b border-gray-200 pb-4">
        <h4 class="font-semibold text-gray-800 mb-3 flex items-center">
            <i class="fas fa-file-alt text-primary mr-2"></i>Business Description
        </h4>
        <p class="text-sm text-gray-700"><?php echo nl2br(htmlspecialchars($application['business_description'])); ?></p>
    </div>

    <!-- Documents -->
    <div class="border-b border-gray-200 pb-4">
        <h4 class="font-semibold text-gray-800 mb-3 flex items-center">
            <i class="fas fa-paperclip text-primary mr-2"></i>Uploaded Documents
        </h4>
        <?php if (!empty($documents)): ?>
        <div class="space-y-2">
            <?php foreach ($documents as $doc): ?>
            <div class="flex items-center justify-between bg-gray-50 p-3 rounded-lg">
                <div class="flex items-center">
                    <i class="fas fa-file text-gray-400 mr-3"></i>
                    <div>
                        <p class="text-sm font-medium"><?php echo htmlspecialchars(ucfirst(str_replace('_', ' ', $doc['document_type']))); ?></p>
                        <p class="text-xs text-gray-500"><?php echo htmlspecialchars($doc['original_name']); ?></p>
                    </div>
                </div>
                <a href="../uploads/<?php echo htmlspecialchars($doc['file_path']); ?>" target="_blank" 
                    class="text-primary hover:underline text-sm">
                    <i class="fas fa-download mr-1"></i>View
                </a>
            </div>
            <?php endforeach; ?>
        </div>
        <?php else: ?>
        <p class="text-sm text-gray-500">No documents uploaded</p>
        <?php endif; ?>
    </div>

    <!-- Admin Notes -->
    <?php if ($application['admin_notes']): ?>
    <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
        <h4 class="font-semibold text-yellow-800 mb-2 flex items-center">
            <i class="fas fa-sticky-note mr-2"></i>Admin Notes
        </h4>
        <p class="text-sm text-yellow-700"><?php echo nl2br(htmlspecialchars($application['admin_notes'])); ?></p>
    </div>
    <?php endif; ?>

    <!-- Timeline -->
    <div class="text-sm text-gray-500 flex justify-between">
        <span>Created: <?php echo date('M d, Y H:i', strtotime($application['created_at'])); ?></span>
        <span>Updated: <?php echo date('M d, Y H:i', strtotime($application['updated_at'])); ?></span>
    </div>
</div>
