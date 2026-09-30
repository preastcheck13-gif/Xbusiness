<?php
/**
 * X Business Grant - Admin Documents
 * View all uploaded documents organized by business/application
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

// Handle search and filters
$search = trim($_GET['search'] ?? '');
$docType = trim($_GET['doc_type'] ?? '');

// Build query
$whereClause = "WHERE a.id = d.application_id";
$params = [];

if (!empty($search)) {
    $whereClause .= " AND (a.business_name LIKE ? OR a.reference_no LIKE ? OR a.cac_number LIKE ?)";
    $searchParam = "%{$search}%";
    $params[] = $searchParam;
    $params[] = $searchParam;
    $params[] = $searchParam;
}

if (!empty($docType)) {
    $whereClause .= " AND d.document_type = ?";
    $params[] = $docType;
}

// Get all documents with application info
$query = "
    SELECT d.*, a.business_name, a.reference_no, a.status, a.cac_number
    FROM documents d
    JOIN applications a ON d.application_id = a.id
    {$whereClause}
    ORDER BY d.created_at DESC
";

$documents = $db->fetchAll($query, $params);

// Group documents by application
$groupedDocs = [];
foreach ($documents as $doc) {
    $appId = $doc['application_id'];
    if (!isset($groupedDocs[$appId])) {
        $groupedDocs[$appId] = [
            'reference_no' => $doc['reference_no'],
            'business_name' => $doc['business_name'],
            'cac_number' => $doc['cac_number'],
            'status' => $doc['status'],
            'documents' => []
        ];
    }
    $groupedDocs[$appId]['documents'][] = $doc;
}

// Get document type counts for filters
$docTypeCounts = $db->fetchAll("
    SELECT document_type, COUNT(*) as count 
    FROM documents 
    GROUP BY document_type
");

// Total documents
$totalDocs = $db->fetchOne("SELECT COUNT(*) as count FROM documents")['count'];

$currentPage = basename($_SERVER['PHP_SELF'], '.php');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Documents - X Business Grant Admin</title>
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
                    <a href="reports.php" class="flex items-center space-x-3 px-4 py-3 rounded-lg hover:bg-gray-800 transition">
                        <i class="fas fa-chart-bar"></i>
                        <span>Reports</span>
                    </a>
                    <a href="documents.php" class="flex items-center space-x-3 px-4 py-3 rounded-lg bg-primary transition">
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
                    <h1 class="text-2xl font-bold text-gray-800">Documents</h1>
                    <p class="text-gray-600">View all uploaded documents organized by application</p>
                </div>
                <div class="text-gray-500">
                    <i class="fas fa-file mr-2"></i><?php echo number_format($totalDocs); ?> documents
                </div>
            </div>

            <!-- Filters -->
            <div class="bg-white rounded-xl shadow-sm p-4 mb-6">
                <form method="GET" class="flex flex-wrap gap-4 items-end">
                    <div class="flex-1 min-w-64">
                        <label class="block text-gray-600 text-sm mb-1">Search</label>
                        <input type="text" name="search" placeholder="Business name, reference, CAC..."
                            value="<?php echo htmlspecialchars($search); ?>"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
                    </div>
                    <div>
                        <label class="block text-gray-600 text-sm mb-1">Document Type</label>
                        <select name="doc_type" class="px-4 py-2 border border-gray-300 rounded-lg">
                            <option value="">All Types</option>
                            <?php foreach ($docTypeCounts as $type): ?>
                                <option value="<?php echo $type['document_type']; ?>" <?php echo $docType === $type['document_type'] ? 'selected' : ''; ?>>
                                    <?php echo ucfirst(str_replace('_', ' ', $type['document_type'])); ?> (<?php echo $type['count']; ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <button type="submit" class="px-6 py-2 bg-primary text-white rounded-lg hover:bg-blue-700 transition">
                        <i class="fas fa-search mr-2"></i>Search
                    </button>
                    <a href="documents.php" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition">
                        <i class="fas fa-times"></i>
                    </a>
                </form>
            </div>

            <!-- Documents List -->
            <?php if (!empty($groupedDocs)): ?>
                <div class="space-y-6">
                    <?php foreach ($groupedDocs as $appId => $app): ?>
                    <div class="bg-white rounded-xl shadow-sm overflow-hidden">
                        <!-- Application Header -->
                        <div class="bg-gray-50 px-6 py-4 border-b border-gray-200">
                            <div class="flex justify-between items-start">
                                <div>
                                    <div class="flex items-center space-x-3">
                                        <h3 class="text-lg font-bold text-gray-800"><?php echo htmlspecialchars($app['business_name']); ?></h3>
                                        <span class="px-3 py-1 rounded-full text-xs font-medium <?php echo Helpers::getStatusClass($app['status']); ?>">
                                            <?php echo ucfirst(str_replace('_', ' ', $app['status'])); ?>
                                        </span>
                                    </div>
                                    <div class="flex items-center space-x-4 mt-1 text-sm text-gray-500">
                                        <span><i class="fas fa-hashtag mr-1"></i><?php echo htmlspecialchars($app['reference_no']); ?></span>
                                        <span><i class="fas fa-building mr-1"></i><?php echo htmlspecialchars($app['cac_number']); ?></span>
                                        <span><i class="fas fa-file mr-1"></i><?php echo count($app['documents']); ?> documents</span>
                                    </div>
                                </div>
                                <a href="get_application.php?id=<?php echo $appId; ?>" target="_blank" 
                                    class="px-4 py-2 bg-primary text-white rounded-lg hover:bg-blue-700 transition text-sm">
                                    <i class="fas fa-external-link-alt mr-1"></i>View Application
                                </a>
                            </div>
                        </div>
                        
                        <!-- Documents Grid -->
                        <div class="p-6">
                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                                <?php 
                                $docIcons = [
                                    'cac_certificate' => 'fa-file-alt text-red-500',
                                    'id_card' => 'fa-id-card text-blue-500',
                                    'utility_bill' => 'fa-file-invoice text-green-500',
                                    'business_plan' => 'fa-chart-line text-purple-500'
                                ];
                                $docLabels = [
                                    'cac_certificate' => 'CAC Certificate',
                                    'id_card' => "Director's ID Card",
                                    'utility_bill' => 'Utility Bill',
                                    'business_plan' => 'Business Plan'
                                ];
                                ?>
                                
                                <?php foreach ($app['documents'] as $doc): ?>
                                <?php
                                    $iconClass = $docIcons[$doc['document_type']] ?? 'fa-file text-gray-500';
                                    $label = $docLabels[$doc['document_type']] ?? ucfirst(str_replace('_', ' ', $doc['document_type']));
                                    $fileExtension = pathinfo($doc['original_name'], PATHINFO_EXTENSION);
                                ?>
                                <div class="border border-gray-200 rounded-lg p-4 hover:border-primary transition">
                                    <div class="flex items-start justify-between mb-3">
                                        <div class="flex items-center">
                                            <div class="w-10 h-10 bg-gray-100 rounded-lg flex items-center justify-center mr-3">
                                                <i class="fas <?php echo $iconClass; ?>"></i>
                                            </div>
                                            <div>
                                                <p class="font-medium text-gray-800 text-sm"><?php echo $label; ?></p>
                                                <p class="text-xs text-gray-500"><?php echo strtoupper($fileExtension); ?></p>
                                            </div>
                                        </div>
                                    </div>
                                    <p class="text-xs text-gray-500 mb-3 truncate" title="<?php echo htmlspecialchars($doc['original_name']); ?>">
                                        <?php echo htmlspecialchars($doc['original_name']); ?>
                                    </p>
                                    <div class="flex space-x-2">
                                        <a href="../uploads/<?php echo htmlspecialchars($doc['file_path']); ?>" 
                                            target="_blank"
                                            class="flex-1 px-3 py-2 bg-primary/10 text-primary text-center rounded-lg hover:bg-primary/20 transition text-sm">
                                            <i class="fas fa-eye mr-1"></i>View
                                        </a>
                                        <a href="../uploads/<?php echo htmlspecialchars($doc['file_path']); ?>" 
                                            download="<?php echo htmlspecialchars($doc['original_name']); ?>"
                                            class="flex-1 px-3 py-2 bg-gray-100 text-gray-700 text-center rounded-lg hover:bg-gray-200 transition text-sm">
                                            <i class="fas fa-download mr-1"></i>Download
                                        </a>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="bg-white rounded-xl shadow-sm p-12 text-center">
                    <div class="w-20 h-20 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                        <i class="fas fa-folder-open text-gray-400 text-3xl"></i>
                    </div>
                    <h3 class="text-lg font-medium text-gray-800 mb-2">No documents found</h3>
                    <p class="text-gray-500 mb-4">
                        <?php if (!empty($search) || !empty($docType)): ?>
                            Try adjusting your search or filters
                        <?php else: ?>
                            Documents will appear here when applications are submitted
                        <?php endif; ?>
                    </p>
                    <?php if (!empty($search) || !empty($docType)): ?>
                    <a href="documents.php" class="inline-block px-4 py-2 bg-primary text-white rounded-lg hover:bg-blue-700 transition">
                        Clear Filters
                    </a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </main>
    </div>
</body>
</html>
