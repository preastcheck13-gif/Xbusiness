<?php
/**
 * Helper Functions for X Business Grant
 */

namespace App;

class Helpers
{
    /**
     * Sanitize input data
     */
    public static function sanitize($input)
    {
        if (is_array($input)) {
            return array_map([self::class, 'sanitize'], $input);
        }
        return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
    }

    /**
     * Validate Nigerian phone number
     */
    public static function validatePhone($phone)
    {
        $phone = preg_replace('/[^0-9+]/', '', $phone);
        return preg_match('/^(\+234[789][01]\d{8}|0[789][01]\d{8})$/', $phone);
    }

    /**
     * Validate Nigerian CAC registration number
     */
    public static function validateCACNumber($cacNumber)
    {
        $cacNumber = strtoupper(trim($cacNumber));
        return preg_match('/^(RC|BN)\d{5,10}$/', $cacNumber);
    }

    /**
     * Format Nigerian Naira
     */
    public static function formatNaira($amount)
    {
        return '₦' . number_format($amount, 2);
    }

    /**
     * Generate unique application reference
     */
    public static function generateApplicationRef()
    {
        return 'XBG-' . date('Y') . '-' . strtoupper(substr(uniqid(), -8));
    }

    /**
     * Upload file and return path
     */
    public static function uploadFile($file, $category)
    {
        require_once __DIR__ . '/config.php';
        
        if ($file['error'] !== UPLOAD_ERR_OK) {
            throw new \Exception('File upload error');
        }

        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        
        if (!in_array($extension, ALLOWED_FILE_TYPES)) {
            throw new \Exception('Invalid file type. Allowed: ' . implode(', ', ALLOWED_FILE_TYPES));
        }

        if ($file['size'] > MAX_FILE_SIZE) {
            throw new \Exception('File size exceeds maximum limit of 10MB');
        }

        $uploadDir = UPLOAD_DIR . $category . '/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $filename = $category . '_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $extension;
        $destination = $uploadDir . $filename;

        if (!move_uploaded_file($file['tmp_name'], $destination)) {
            throw new \Exception('Failed to move uploaded file');
        }

        return $category . '/' . $filename;
    }

    /**
     * Get status badge class
     */
    public static function getStatusClass($status)
    {
        $classes = [
            'pending' => 'bg-yellow-100 text-yellow-800',
            'under_review' => 'bg-blue-100 text-blue-800',
            'approved' => 'bg-green-100 text-green-800',
            'rejected' => 'bg-red-100 text-red-800'
        ];
        return $classes[$status] ?? 'bg-gray-100 text-gray-800';
    }

    /**
     * Get Nigerian states list
     */
    public static function getNigerianStates()
    {
        return [
            'Abia', 'Adamawa', 'Akwa Ibom', 'Anambra', 'Bauchi', 'Bayelsa',
            'Benue', 'Borno', 'Cross River', 'Delta', 'Ebonyi', 'Edo',
            'Ekiti', 'Enugu', 'FCT Abuja', 'Gombe', 'Imo', 'Jigawa',
            'Kaduna', 'Kano', 'Katsina', 'Kebbi', 'Kogi', 'Kwara',
            'Lagos', 'Nasarawa', 'Niger', 'Ogun', 'Ondo', 'Osun',
            'Oyo', 'Plateau', 'Rivers', 'Sokoto', 'Taraba', 'Yobe', 'Zamfara'
        ];
    }

    /**
     * Get business sectors
     */
    public static function getBusinessSectors()
    {
        return [
            'Agriculture & Agribusiness',
            'Technology & IT',
            'Manufacturing',
            'Healthcare',
            'Education & Training',
            'Retail & E-commerce',
            'Transportation & Logistics',
            'Construction & Real Estate',
            'Energy & Renewable Energy',
            'Tourism & Hospitality',
            'Fashion & Textiles',
            'Food & Beverage',
            'Creative Arts & Entertainment',
            'Financial Services',
            'Other'
        ];
    }

    /**
     * Get business types
     */
    public static function getBusinessTypes()
    {
        return [
            'Sole Proprietorship',
            'Partnership',
            'Private Limited Company',
            'Public Limited Company',
            'Limited Liability Partnership',
            'Non-Governmental Organization'
        ];
    }
}
