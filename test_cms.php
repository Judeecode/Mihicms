<?php
/**
 * Quick CMS Test Script
 * Run this to test if your CMS setup is working
 * Access via: http://localhost/Mihicms/test_cms.php
 */

echo "<!DOCTYPE html><html><head><title>CMS Test</title>";
echo "<style>body{font-family:Arial,sans-serif;max-width:800px;margin:50px auto;padding:20px;}";
echo ".pass{color:green;font-weight:bold;} .fail{color:red;font-weight:bold;}";
echo "h1{color:#333;} .test{margin:10px 0;padding:10px;border-left:3px solid #ddd;}";
echo "</style></head><body>";
echo "<h1>🧪 MiHi CMS Test Script</h1>";

$tests_passed = 0;
$tests_failed = 0;

// Test 1: PHP Version
echo "<div class='test'><strong>Test 1:</strong> PHP Version ";
$php_version = phpversion();
if (version_compare($php_version, '5.4.0', '>=')) {
    echo "<span class='pass'>✓ PASS</span> (Version: $php_version)";
    $tests_passed++;
} else {
    echo "<span class='fail'>✗ FAIL</span> (Version: $php_version - Need 5.4+)";
    $tests_failed++;
}
echo "</div>";

// Test 2: Config File
echo "<div class='test'><strong>Test 2:</strong> Config File Exists ";
if (file_exists(__DIR__ . '/cms/config.php')) {
    echo "<span class='pass'>✓ PASS</span>";
    $tests_passed++;
    require_once __DIR__ . '/cms/config.php';
} else {
    echo "<span class='fail'>✗ FAIL</span> (config.php not found)";
    $tests_failed++;
}
echo "</div>";

// Test 3: Database Connection
if (function_exists('getDBConnection')) {
    echo "<div class='test'><strong>Test 3:</strong> Database Connection ";
    try {
        $conn = getDBConnection();
        if ($conn && !$conn->connect_error) {
            echo "<span class='pass'>✓ PASS</span>";
            $tests_passed++;
            
            // Test 4: Database Tables
            echo "<div class='test'><strong>Test 4:</strong> Database Tables ";
            $tables = ['admin_users', 'content_elements'];
            $all_tables_exist = true;
            foreach ($tables as $table) {
                $result = $conn->query("SHOW TABLES LIKE '$table'");
                if ($result->num_rows == 0) {
                    $all_tables_exist = false;
                    break;
                }
            }
            if ($all_tables_exist) {
                echo "<span class='pass'>✓ PASS</span> (admin_users, content_elements exist)";
                $tests_passed++;
            } else {
                echo "<span class='fail'>✗ FAIL</span> (Tables missing - run database.sql)";
                $tests_failed++;
            }
            echo "</div>";
            
            // Test 5: Admin User
            echo "<div class='test'><strong>Test 5:</strong> Admin User Exists ";
            $result = $conn->query("SELECT COUNT(*) as count FROM admin_users WHERE username = 'admin'");
            $row = $result->fetch_assoc();
            if ($row['count'] > 0) {
                echo "<span class='pass'>✓ PASS</span>";
                $tests_passed++;
            } else {
                echo "<span class='fail'>✗ FAIL</span> (Admin user not found)";
                $tests_failed++;
            }
            echo "</div>";
            
            $conn->close();
        } else {
            echo "<span class='fail'>✗ FAIL</span> (" . ($conn ? $conn->connect_error : 'Connection failed') . ")";
            $tests_failed++;
        }
    } catch (Exception $e) {
        echo "<span class='fail'>✗ FAIL</span> (" . $e->getMessage() . ")";
        $tests_failed++;
    }
    echo "</div>";
} else {
    echo "<div class='test'><strong>Test 3:</strong> Database Connection ";
    echo "<span class='fail'>✗ FAIL</span> (getDBConnection function not found)";
    $tests_failed++;
    echo "</div>";
}

// Test 6: Required Files
echo "<div class='test'><strong>Test 6:</strong> Required Files ";
$required_files = [
    'cms/login.php',
    'cms/admin.php',
    'cms/logout.php',
    'api/get_content.php'
];
$all_files_exist = true;
foreach ($required_files as $file) {
    if (!file_exists(__DIR__ . '/' . $file)) {
        $all_files_exist = false;
        break;
    }
}
if ($all_files_exist) {
    echo "<span class='pass'>✓ PASS</span>";
    $tests_passed++;
} else {
    echo "<span class='fail'>✗ FAIL</span> (Some files missing)";
    $tests_failed++;
}
echo "</div>";

// Test 7: Session Support
echo "<div class='test'><strong>Test 7:</strong> PHP Sessions ";
if (function_exists('session_start')) {
    echo "<span class='pass'>✓ PASS</span>";
    $tests_passed++;
} else {
    echo "<span class='fail'>✗ FAIL</span>";
    $tests_failed++;
}
echo "</div>";

// Summary
echo "<hr>";
echo "<h2>Test Summary</h2>";
echo "<p><strong>Passed:</strong> <span class='pass'>$tests_passed</span> | ";
echo "<strong>Failed:</strong> <span class='fail'>$tests_failed</span></p>";

if ($tests_failed == 0) {
    echo "<div style='background:#d4edda;padding:15px;border-radius:5px;margin:20px 0;'>";
    echo "<strong>✓ All tests passed! Your CMS should be working.</strong><br>";
    echo "<a href='cms/login.php' style='display:inline-block;margin-top:10px;padding:10px 20px;background:#0050ff;color:white;text-decoration:none;border-radius:5px;'>Go to Login Page →</a>";
    echo "</div>";
} else {
    echo "<div style='background:#f8d7da;padding:15px;border-radius:5px;margin:20px 0;'>";
    echo "<strong>✗ Some tests failed. Please check the errors above.</strong><br>";
    echo "Common fixes:<br>";
    echo "• Make sure MySQL is running in XAMPP<br>";
    echo "• Run database.sql in phpMyAdmin<br>";
    echo "• Check cms/config.php database credentials";
    echo "</div>";
}

echo "</body></html>";
?>

