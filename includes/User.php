<?php
/**
 * User Model for X Business Grant
 */

namespace App;

class User
{
    private $db;
    private $table = 'users';

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Find user by email
     */
    public function findByEmail($email)
    {
        return $this->db->fetchOne(
            "SELECT * FROM {$this->table} WHERE email = ?",
            [strtolower(trim($email))]
        );
    }

    /**
     * Find user by ID
     */
    public function findById($id)
    {
        return $this->db->fetchOne(
            "SELECT * FROM {$this->table} WHERE id = ?",
            [(int)$id]
        );
    }

    /**
     * Check if email exists
     */
    public function emailExists($email)
    {
        $result = $this->db->fetchOne(
            "SELECT COUNT(*) as count FROM {$this->table} WHERE email = ?",
            [strtolower(trim($email))]
        );
        return $result['count'] > 0;
    }

    /**
     * Create new user
     */
    public function create($data)
    {
        $passwordHash = password_hash($data['password'], PASSWORD_DEFAULT);
        
        $userData = [
            'full_name' => $data['full_name'],
            'email' => strtolower(trim($data['email'])),
            'phone' => $data['phone'],
            'business_registration_number' => $data['business_registration_number'] ?? null,
            'password_hash' => $passwordHash,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ];

        return $this->db->insert($this->table, $userData);
    }

    /**
     * Verify password
     */
    public function verifyPassword($email, $password)
    {
        $user = $this->findByEmail($email);
        
        if (!$user) {
            return false;
        }

        return password_verify($password, $user['password_hash']);
    }

    /**
     * Update last login
     */
    public function updateLastLogin($id)
    {
        $this->db->query(
            "UPDATE {$this->table} SET last_login = NOW() WHERE id = ?",
            [(int)$id]
        );
    }

    /**
     * Get user applications
     */
    public function getApplications($userId)
    {
        return $this->db->fetchAll(
            "SELECT * FROM applications WHERE email = (SELECT email FROM {$this->table} WHERE id = ?) ORDER BY created_at DESC",
            [(int)$userId]
        );
    }

    /**
     * Get user profile with applications count
     */
    public function getProfile($id)
    {
        $user = $this->findById($id);
        
        if (!$user) {
            return null;
        }

        unset($user['password_hash']);
        
        $applications = $this->db->fetchAll(
            "SELECT COUNT(*) as count, 
                    SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved,
                    SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending
             FROM applications WHERE email = ?",
            [$user['email']]
        );

        $user['applications_count'] = $applications[0]['count'] ?? 0;
        $user['approved_count'] = $applications[0]['approved'] ?? 0;
        $user['pending_count'] = $applications[0]['pending'] ?? 0;

        return $user;
    }

    /**
     * Check if user email is verified
     */
    public function isEmailVerified($id)
    {
        $user = $this->findById($id);
        return $user && $user['email_verified'] == 1;
    }

    /**
     * Update user's email verification status
     */
    public function setEmailVerified($id, $verified = true)
    {
        $this->db->query(
            "UPDATE {$this->table} SET email_verified = ?, updated_at = NOW() WHERE id = ?",
            [$verified ? 1 : 0, (int)$id]
        );
    }
}
