<?php
/**
 * Sync Brochure Section Content to Database
 * This script ensures all Brochure Section content from index.html is in the database
 * Access via: http://localhost/MiHi-Entertainment/sync_brochure_content.php
 */

header('Content-Type: text/html; charset=utf-8');
require_once 'config.php';

// Simple authentication check (session already started in config.php)
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    die('Access denied. Please <a href="login.php">login</a> first.');
}

$conn = getDBConnection();

// Define all Brochure Section content elements from index.html (lines 474-606)
// These are the ACTUAL Brochure Section elements (Interactive flipbook section)
$brochure_elements = [
    [
        'element_id' => 'brochure-lookbook-badge',
        'element_type' => 'span',
        'content' => 'Lookbook',
        'section' => 'online-brochure'
    ],
    [
        'element_id' => 'brochure-badge-text',
        'element_type' => 'span',
        'content' => 'Photo Booth Story Lab',
        'section' => 'online-brochure'
    ],
    [
        'element_id' => 'brochure-subheading',
        'element_type' => 'h2',
        'content' => 'See How Our Booths Bring Events to Life',
        'section' => 'online-brochure'
    ],
    [
        'element_id' => 'brochure-paragraph',
        'element_type' => 'p',
        'content' => 'Step into a digital strip of our favorite captures—light trails, confetti pops, branded neon, and the spontaneous reactions that only happen in front of a booth. Use the lookbook to spark a custom scene, backdrop, or prop story for your own gathering.',
        'section' => 'online-brochure'
    ],
    [
        'element_id' => 'brochure-feature-1-title',
        'element_type' => 'p',
        'content' => 'Signature Booth Moments',
        'section' => 'online-brochure'
    ],
    [
        'element_id' => 'brochure-feature-1-desc',
        'element_type' => 'p',
        'content' => 'Nightclub GIF bars, glam filters, slow-mo confetti cannons, and campaign-ready photo strips.',
        'section' => 'online-brochure'
    ],
    [
        'element_id' => 'brochure-feature-2-title',
        'element_type' => 'p',
        'content' => 'Backdrop & Lighting Recipes',
        'section' => 'online-brochure'
    ],
    [
        'element_id' => 'brochure-feature-2-desc',
        'element_type' => 'p',
        'content' => 'Mix-and-match scene ideas, lighting diagrams, and overlay concepts to match your brand personality.',
        'section' => 'online-brochure'
    ],
    [
        'element_id' => 'brochure-feature-3-title',
        'element_type' => 'p',
        'content' => 'Guest Experience Playbooks',
        'section' => 'online-brochure'
    ],
    [
        'element_id' => 'brochure-feature-3-desc',
        'element_type' => 'p',
        'content' => 'From queue engagement to share stations, see how we choreograph laughter and share-worthy content.',
        'section' => 'online-brochure'
    ],
    [
        'element_id' => 'brochure-button-1-text',
        'element_type' => 'span',
        'content' => 'Flip Through the Booth Book',
        'section' => 'online-brochure'
    ],
    [
        'element_id' => 'brochure-button-2-text',
        'element_type' => 'span',
        'content' => 'Plan Your Booth',
        'section' => 'online-brochure'
    ],
    [
        'element_id' => 'brochure-tag-1',
        'element_type' => 'span',
        'content' => 'GIF Bars',
        'section' => 'online-brochure'
    ],
    [
        'element_id' => 'brochure-tag-2',
        'element_type' => 'span',
        'content' => 'Glam Portraits',
        'section' => 'online-brochure'
    ],
    [
        'element_id' => 'brochure-tag-3',
        'element_type' => 'span',
        'content' => 'Photo Strips',
        'section' => 'online-brochure'
    ],
    [
        'element_id' => 'brochure-tag-4',
        'element_type' => 'span',
        'content' => 'Slow Motion Video',
        'section' => 'online-brochure'
    ]
];

// Old/incorrect element IDs that should be removed or moved
$old_brochure_ids = ['hero-badge-text', 'hero-subheading', 'hero-paragraph', 'hero-feature-1-title', 'hero-feature-1-desc', 'hero-feature-2-title', 'hero-feature-2-desc', 'hero-feature-3-title', 'hero-feature-3-desc', 'hero-button-1-text', 'hero-button-2-text', 'hero-tag-1', 'hero-tag-2', 'hero-tag-3', 'hero-tag-4'];

?>
<!DOCTYPE html>
<html>
<head>
    <title>Sync Brochure Section Content</title>
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
        <h1>Syncing Brochure Section Content to Database</h1>
        <pre>
<?php

$results = [];
$success_count = 0;
$error_count = 0;

// First, handle old/incorrect entries
echo "Checking for old/incorrect Brochure Section entries...\n";
foreach ($old_brochure_ids as $old_id) {
    $check_old = $conn->prepare("SELECT id, element_id FROM content_elements WHERE element_id = ? AND page = 'index'");
    $check_old->bind_param("s", $old_id);
    $check_old->execute();
    $old_result = $check_old->get_result();
    
    if ($old_result->num_rows > 0) {
        // Delete old incorrect entry (these were incorrectly named with hero- prefix)
        $delete_old = $conn->prepare("DELETE FROM content_elements WHERE element_id = ? AND page = 'index'");
        $delete_old->bind_param("s", $old_id);
        if ($delete_old->execute()) {
            echo "→ Deleted old entry: {$old_id}\n";
        } else {
            echo "✗ Error deleting {$old_id}: " . $delete_old->error . "\n";
        }
        $delete_old->close();
    }
    $check_old->close();
}

echo "\nSyncing Brochure Section content...\n\n";

foreach ($brochure_elements as $element) {
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
echo "Brochure Section content sync completed!\n";
echo "Success: {$success_count} | Errors: {$error_count}\n";
echo "All 16 Brochure Section elements are now in the database with section='online-brochure'.\n";
?>
        </pre>
        <p><a href="admin.php?section=online-brochure">View Brochure Section in Admin Panel</a> | <a href="admin.php">Back to Admin Dashboard</a></p>
    </div>
</body>
</html>
<?php
$conn->close();
?>

