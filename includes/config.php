<?php
/**
 * Database Configuration for X Business Grant
 * Nigerian Business Grant Application Portal
 */

define('DB_HOST', 'localhost');
define('DB_NAME', 'xbusines');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

define('UPLOAD_DIR', __DIR__ . '/../uploads/');
define('MAX_FILE_SIZE', 10 * 1024 * 1024); // 10MB
define('ALLOWED_FILE_TYPES', ['pdf', 'jpg', 'jpeg', 'png', 'doc', 'docx']);

define('GRANT_AMOUNT_MIN', 500000);
define('GRANT_AMOUNT_MAX', 5000000);

define('SITE_NAME', 'X Business Grant');
define('SITE_URL', 'http://localhost/X Business Grants');
