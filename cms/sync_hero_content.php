<?php
/**
 * Sync Hero Section Content to Database
 * This script ensures all Hero Section content from index.html is in the database
 * Access via: http://localhost/MiHi-Entertainment/sync_hero_content.php
 */

header('Content-Type: text/html; charset=utf-8');
require_once 'config.php';

// Simple authentication check (session already started in config.php)
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    die('Access denied. Please <a href="login.php">login</a> first.');
}

$conn = getDBConnection();

// Define all Hero Section content elements from index.html (lines 416-470)
// These are the ACTUAL Hero Section elements (video background section)
$hero_elements = [
    [
        'element_id' => 'hero-video-fallback',
        'element_type' => 'p',
        'content' => 'Your browser does not support the video tag.',
        'section' => 'hero'
    ],
    [
        'element_id' => 'hero-title-line1',
        'element_type' => 'h1',
        'content' => 'TRANSFORM YOUR EVENT<br class="sm:hidden"> WITH OUR TOP-TIER',
        'section' => 'hero'
    ],
    [
        'element_id' => 'hero-title-main',
        'element_type' => 'h1',
        'content' => 'PHOTO BOOTH RENTALS',
        'section' => 'hero'
    ],
    [
        'element_id' => 'hero-subtitle',
        'element_type' => 'p',
        'content' => 'We offer unforgettable Photo Booth Rentals along with a wide-range of Event Entertainment options for any celebration or event nationally.',
        'section' => 'hero'
    ],
    [
        'element_id' => 'hero-button-cta',
        'element_type' => 'p',
        'content' => 'Get Your Free Quote',
        'section' => 'hero'
    ],
    [
        'element_id' => 'hero-call-button-text',
        'element_type' => 'p',
        'content' => 'Call Us',
        'section' => 'hero'
    ]
];

// Old/incorrect element IDs that should be removed or updated
$old_hero_ids = ['hero-heading', 'hero-subheading', 'hero-paragraph'];

?>
<!DOCTYPE html>
<html>
<head>
    <title>Sync Hero Section Content</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; background: #f5f5f5; }
        .container { max-width: 800px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; }
        .success { color: green; }
        .error { color: red; }
        .info { color: blue; }
        h1 { color: #333; }
        pre { background: #f0f0f0; padding: 10px; border-radius: 4px; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Syncing Hero Section Content to Database</h1>
        <pre>
<?php

$results = [];
$success_count = 0;
$error_count = 0;

// First, handle old/incorrect entries
echo "Checking for old/incorrect Hero Section entries...\n";
foreach ($old_hero_ids as $old_id) {
    $check_old = $conn->prepare("SELECT id, element_id FROM content_elements WHERE element_id = ? AND page = 'index'");
    $check_old->bind_param("s", $old_id);
    $check_old->execute();
    $old_result = $check_old->get_result();
    
    if ($old_result->num_rows > 0) {
        // Check if this is actually in the online-brochure section (hero-subheading, hero-paragraph are there)
        if ($old_id === 'hero-subheading' || $old_id === 'hero-paragraph') {
            // These belong to online-brochure section, update their section
            $update_section = $conn->prepare("UPDATE content_elements SET section = 'online-brochure' WHERE element_id = ? AND page = 'index'");
            $update_section->bind_param("s", $old_id);
            if ($update_section->execute()) {
                echo "→ Moved {$old_id} to 'online-brochure' section\n";
            }
            $update_section->close();
        } else {
            // Delete old incorrect entry
            $delete_old = $conn->prepare("DELETE FROM content_elements WHERE element_id = ? AND page = 'index'");
            $delete_old->bind_param("s", $old_id);
            if ($delete_old->execute()) {
                echo "→ Deleted old entry: {$old_id}\n";
            }
            $delete_old->close();
        }
    }
    $check_old->close();
}

echo "\nSyncing Hero Section content...\n\n";

foreach ($hero_elements as $element) {
    // Check if element exists
    $check_stmt = $conn->prepare("SELECT id FROM content_elements WHERE element_id = ? AND page = 'index'");
    $check_stmt->bind_param("s", $element['element_id']);
    $check_stmt->execute();
    $result = $check_stmt->get_result();
    
    if ($result->num_rows > 0) {
        // Update existing entry
        $row = $result->fetch_assoc();
        $update_stmt = $conn->prepare("UPDATE content_elements SET element_type = ?, content = ?, section = ?, updated_at = NOW() WHERE id = ?");
        $update_stmt->bind_param("sssi", $element['element_type'], $element['content'], $element['section'], $row['id']);
        
        if ($update_stmt->execute()) {
            echo "✓ Updated: {$element['element_id']}\n";
            $results[] = ['status' => 'updated', 'id' => $element['element_id']];
            $success_count++;
        } else {
            echo "✗ Error updating {$element['element_id']}: " . $update_stmt->error . "\n";
            $results[] = ['status' => 'error', 'id' => $element['element_id'], 'error' => $update_stmt->error];
            $error_count++;
        }
        $update_stmt->close();
    } else {
        // Insert new entry
        $insert_stmt = $conn->prepare("INSERT INTO content_elements (element_id, element_type, content, page, section, updated_at) VALUES (?, ?, ?, 'index', ?, NOW())");
        $insert_stmt->bind_param("ssss", $element['element_id'], $element['element_type'], $element['content'], $element['section']);
        
        if ($insert_stmt->execute()) {
            echo "✓ Added: {$element['element_id']}\n";
            $results[] = ['status' => 'added', 'id' => $element['element_id']];
            $success_count++;
        } else {
            echo "✗ Error adding {$element['element_id']}: " . $insert_stmt->error . "\n";
            $results[] = ['status' => 'error', 'id' => $element['element_id'], 'error' => $insert_stmt->error];
            $error_count++;
        }
        $insert_stmt->close();
    }
    
    $check_stmt->close();
}

echo "\n";
echo "========================================\n";
echo "Hero Section content sync completed!\n";
echo "Success: {$success_count} | Errors: {$error_count}\n";
echo "All 6 Hero Section elements are now in the database with section='hero'.\n";
?>
        </pre>
        <p><a href="admin.php?section=hero">View Hero Section in Admin Panel</a> | <a href="admin.php">Back to Admin Dashboard</a></p>
    </div>
</body>
</html>
<?php
$conn->close();
?>

