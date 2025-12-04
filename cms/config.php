<?php
// Database configuration
// Supports both local (XAMPP) and production (Ionos) environments
// 
// For Ionos deployment:
// 1. Get your database credentials from Ionos control panel
// 2. Either set environment variables or update the values below
// 3. Ionos typically provides: host, username, password, database name

// Check for environment variables first (for production/Ionos)
// If not set, use local XAMPP defaults
define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
define('DB_USER', getenv('DB_USER') ?: 'root');
define('DB_PASS', getenv('DB_PASS') ?: ''); // Default XAMPP password is empty
define('DB_NAME', getenv('DB_NAME') ?: 'mihi_cms');

// Alternative: Uncomment and update these for Ionos if environment variables aren't available
// define('DB_HOST', 'your-ionos-db-host'); // e.g., 'db123456789.db.1and1.com' or similar
// define('DB_USER', 'your-ionos-db-username');
// define('DB_PASS', 'your-ionos-db-password');
// define('DB_NAME', 'your-ionos-db-name'); // May be different from 'mihi_cms'

// Create database connection
function getDBConnection() {
    try {
        $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        
        if ($conn->connect_error) {
            // On local XAMPP, try to create database if it doesn't exist
            // On production (Ionos), database should already exist - just show error
            if (DB_HOST === 'localhost' || DB_HOST === '127.0.0.1') {
                // Local development: try to create database
                $conn = new mysqli(DB_HOST, DB_USER, DB_PASS);
                if ($conn->connect_error) {
                    die("Connection failed: " . $conn->connect_error);
                }
                
                // Create database
                $sql = "CREATE DATABASE IF NOT EXISTS " . DB_NAME . " CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci";
                if ($conn->query($sql) === TRUE) {
                    $conn->select_db(DB_NAME);
                    // Run the schema if database.sql exists
                    $schemaFile = __DIR__ . '/../database/database.sql';
                    if (file_exists($schemaFile)) {
                        $schema = file_get_contents($schemaFile);
                        // Execute schema (basic version)
                        $conn->multi_query($schema);
                        while ($conn->next_result()) {;} // Flush multi_query results
                    }
                } else {
                    die("Error creating database: " . $conn->error);
                }
            } else {
                // Production: database should already exist
                die("Database connection failed: " . $conn->connect_error . 
                    "<br>Please verify your database credentials in config.php");
            }
        }
        
        $conn->set_charset("utf8mb4");
        return $conn;
    } catch (Exception $e) {
        die("Database connection error: " . $e->getMessage());
    }
}

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;
}

// Require login
function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: login.php');
        exit();
    }
}

// PHP 5.4 compatible password functions (password_verify/password_hash require PHP 5.5+)
if (!defined('PASSWORD_DEFAULT')) {
    define('PASSWORD_DEFAULT', 1);
}

if (!function_exists('password_verify')) {
    /**
     * Verify a password against a hash (PHP 5.4 compatible)
     * @param string $password The plain text password
     * @param string $hash The hash to verify against
     * @return bool
     */
    function password_verify($password, $hash) {
        // Use crypt() which is available in PHP 5.4
        // crypt() returns the hash if password matches, or a different hash if it doesn't
        $test = crypt($password, $hash);
        // Use hash_equals if available (PHP 5.6+), otherwise use timing-safe comparison
        if (function_exists('hash_equals')) {
            return hash_equals($hash, $test);
        }
        // Timing-safe string comparison for PHP 5.4
        if (strlen($hash) !== strlen($test)) {
            return false;
        }
        $result = 0;
        for ($i = 0; $i < strlen($hash); $i++) {
            $result |= ord($hash[$i]) ^ ord($test[$i]);
        }
        return $result === 0;
    }
}

if (!function_exists('password_hash')) {
    /**
     * Hash a password (PHP 5.4 compatible)
     * @param string $password The plain text password
     * @param int $algo Algorithm constant (ignored, always uses bcrypt)
     * @return string|false The hashed password or false on failure
     */
    function password_hash($password, $algo = null) {
        // Generate a random salt for bcrypt
        $salt = '';
        $chars = './ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
        for ($i = 0; $i < 22; $i++) {
            $salt .= $chars[mt_rand(0, 63)];
        }
        // Use $2y$ format (bcrypt)
        $salt = '$2y$10$' . $salt;
        return crypt($password, $salt);
    }
}

?>

