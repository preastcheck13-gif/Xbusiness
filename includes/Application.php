<?php
/**
 * Grant Application Model
 */

namespace App;

use App\Database;
use App\Helpers;

class Application
{
    private $db;
    private $table = 'applications';

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Create new grant application
     */
    public function create($data, $documents = [])
    {
        $applicationData = [
            'reference_no' => Helpers::generateApplicationRef(),
            'business_name' => $data['business_name'],
            'cac_number' => $data['cac_number'] ?? 'PENDING',
            'business_type' => $data['business_type'],
            'business_sector' => $data['business_sector'] ?? 'General',
            'business_address' => $data['business_address'] ?? 'PENDING',
            'city' => $data['city'] ?? 'PENDING',
            'state' => $data['state'] ?? 'PENDING',
            'lga' => $data['lga'] ?? null,
            'phone' => $data['phone'],
            'email' => $data['email'],
            'website' => $data['website'] ?? null,
            'years_in_business' => (int)($data['years_in_business'] ?? 1),
            'employee_count' => (int)($data['employee_count'] ?? 1),
            'grant_amount_requested' => (float)($data['grant_amount'] ?? 500000),
            'purpose_of_grant' => $data['purpose'] ?? 'Business growth',
            'business_description' => $data['business_description'] ?? '',
            'status' => 'pending',
            'bank_name' => $data['bank_name'] ?? null,
            'bank_code' => $data['bank_code'] ?? null,
            'bank_account_number' => $data['bank_account'] ?? null,
            'verified_account_name' => $data['verified_account_name'] ?? null,
            'bank_verified' => !empty($data['verified_account_name']) ? 1 : 0,
            'owner_name' => $data['owner_name'] ?? null,
            'date_of_birth' => $data['date_of_birth'] ?? null,
            'bvn' => $data['bvn'] ?? null,
            'business_registration_number' => $data['business_registration_number'] ?? null,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ];

        $applicationId = $this->db->insert($this->table, $applicationData);

        // Handle document uploads
        if (!empty($documents)) {
            $this->saveDocuments($applicationId, $documents);
        }

        return $applicationData['reference_no'];
    }

    /**
     * Save uploaded documents
     */
    private function saveDocuments($applicationId, $documents)
    {
        require_once __DIR__ . '/config.php';
        
        foreach ($documents as $docType => $file) {
            if ($file && $file['size'] > 0) {
                try {
                    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                    
                    // Validate extension
                    if (!in_array($extension, ALLOWED_FILE_TYPES)) {
                        throw new \Exception('Invalid file type');
                    }
                    
                    // Validate size
                    if ($file['size'] > MAX_FILE_SIZE) {
                        throw new \Exception('File size exceeds limit');
                    }
                    
                    $uploadDir = UPLOAD_DIR . $docType . '/';
                    if (!is_dir($uploadDir)) {
                        mkdir($uploadDir, 0755, true);
                    }
                    
                    $filename = $docType . '_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $extension;
                    $destination = $uploadDir . $filename;
                    
                    // Use copy for files already in temp directory, move_uploaded_file for actual uploads
                    $sourceFile = $file['tmp_name'];
                    if (is_uploaded_file($sourceFile)) {
                        move_uploaded_file($sourceFile, $destination);
                    } else {
                        copy($sourceFile, $destination);
                    }
                    
                    $path = $docType . '/' . $filename;
                    
                    $this->db->insert('documents', [
                        'application_id' => $applicationId,
                        'document_type' => $docType,
                        'file_path' => $path,
                        'original_name' => $file['name'],
                        'created_at' => date('Y-m-d H:i:s')
                    ]);
                } catch (\Exception $e) {
                    // Log error but continue
                    error_log("Document upload failed for {$docType}: " . $e->getMessage());
                }
            }
        }
    }

    /**
     * Get application by reference number
     */
    public function getByReference($referenceNo)
    {
        return $this->db->fetchOne(
            "SELECT * FROM {$this->table} WHERE reference_no = ?",
            [$referenceNo]
        );
    }

    /**
     * Get application by ID
     */
    public function getById($id)
    {
        return $this->db->fetchOne(
            "SELECT * FROM {$this->table} WHERE id = ?",
            [$id]
        );
    }

    /**
     * Get documents for application
     */
    public function getDocuments($applicationId)
    {
        return $this->db->fetchAll(
            "SELECT * FROM documents WHERE application_id = ?",
            [$applicationId]
        );
    }

    /**
     * Update application status
     */
    public function updateStatus($id, $status, $adminNotes = null)
    {
        $data = [
            'status' => $status,
            'updated_at' => date('Y-m-d H:i:s')
        ];

        if ($adminNotes !== null) {
            $data['admin_notes'] = $adminNotes;
        }

        $this->db->update($this->table, $data, 'id = :id', ['id' => $id]);
    }

    /**
     * Get all applications with optional filters
     */
    public function getAll($filters = [], $page = 1, $perPage = 20)
    {
        $where = ['1=1'];
        $params = [];

        if (!empty($filters['status'])) {
            $where[] = 'status = ?';
            $params[] = $filters['status'];
        }

        if (!empty($filters['state'])) {
            $where[] = 'state = ?';
            $params[] = $filters['state'];
        }

        if (!empty($filters['sector'])) {
            $where[] = 'business_sector = ?';
            $params[] = $filters['sector'];
        }

        if (!empty($filters['email'])) {
            $where[] = 'email = ?';
            $params[] = $filters['email'];
        }

        if (!empty($filters['search'])) {
            $searchTerm = '%' . $filters['search'] . '%';
            $where[] = '(reference_no LIKE ? OR business_name LIKE ? OR cac_number LIKE ?)';
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $params[] = $searchTerm;
        }

        $whereClause = implode(' AND ', $where);

        // Count total
        $totalSql = "SELECT COUNT(*) as count FROM {$this->table} WHERE {$whereClause}";
        $total = $this->db->fetchOne($totalSql, $params)['count'];

        // Get paginated results
        $offset = (int)(($page - 1) * $perPage);
        $limit = (int)$perPage;
        
        $applicationsSql = "SELECT * FROM {$this->table} WHERE {$whereClause} ORDER BY created_at DESC LIMIT {$offset}, {$limit}";
        $applications = $this->db->fetchAll($applicationsSql, $params);

        return [
            'data' => $applications,
            'total' => $total,
            'page' => $page,
            'per_page' => $perPage,
            'total_pages' => ceil($total / $perPage)
        ];
    }

    /**
     * Get application statistics
     */
    public function getStats()
    {
        $stats = [];

        $stats['total'] = $this->db->fetchOne(
            "SELECT COUNT(*) as count FROM {$this->table}"
        )['count'];

        $stats['pending'] = $this->db->fetchOne(
            "SELECT COUNT(*) as count FROM {$this->table} WHERE status = 'pending'"
        )['count'];

        $stats['under_review'] = $this->db->fetchOne(
            "SELECT COUNT(*) as count FROM {$this->table} WHERE status = 'under_review'"
        )['count'];

        $stats['approved'] = $this->db->fetchOne(
            "SELECT COUNT(*) as count FROM {$this->table} WHERE status = 'approved'"
        )['count'];

        $stats['rejected'] = $this->db->fetchOne(
            "SELECT COUNT(*) as count FROM {$this->table} WHERE status = 'rejected'"
        )['count'];

        $stats['total_grant_amount'] = $this->db->fetchOne(
            "SELECT SUM(grant_amount_requested) as total FROM {$this->table} WHERE status = 'approved'"
        )['total'] ?? 0;

        return $stats;
    }

    /**
     * Check if CAC number already exists
     */
    public function cacExists($cacNumber, $excludeId = null)
    {
        $sql = "SELECT COUNT(*) as count FROM {$this->table} WHERE cac_number = ?";
        $params = [$cacNumber];

        if ($excludeId) {
            $sql .= " AND id != ?";
            $params[] = $excludeId;
        }

        return $this->db->fetchOne($sql, $params)['count'] > 0;
    }

    /**
     * Delete application and associated documents
     */
    public function delete($id)
    {
        // Get documents to delete files
        $documents = $this->getDocuments($id);
        
        // Delete physical files
        foreach ($documents as $doc) {
            $filePath = UPLOAD_DIR . $doc['file_path'];
            if (file_exists($filePath)) {
                unlink($filePath);
            }
        }
        
        // Delete document records
        $this->db->delete('documents', 'application_id = :id', ['id' => $id]);
        
        // Delete application
        $this->db->delete($this->table, 'id = :id', ['id' => $id]);
        
        return true;
    }

    /**
     * Bulk delete applications
     */
    public function bulkDelete($ids)
    {
        if (empty($ids)) {
            return 0;
        }
        
        $count = 0;
        foreach ($ids as $id) {
            if ($this->delete((int)$id)) {
                $count++;
            }
        }
        
        return $count;
    }
}
