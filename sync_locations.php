<?php
/**
 * Sync Locations Section Content to Database
 * This script ensures all Locations Section content from index.html is in the database
 * Access via: http://localhost/MiHi-Entertainment/sync_locations.php
 */

header('Content-Type: text/html; charset=utf-8');
require_once 'config.php';

// Simple authentication check (session already started in config.php)
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    die('Access denied. Please <a href="login.php">login</a> first.');
}

$conn = getDBConnection();

// Define all Locations Section content elements from index.html
// Note: Location names are dynamically generated, so we'll sync the main section elements
$locations_elements = [
    [
        'element_id' => 'locations-badge-text',
        'element_type' => 'span',
        'content' => 'Service Areas',
        'section' => 'locations'
    ],
    [
        'element_id' => 'locations-heading',
        'element_type' => 'h2',
        'content' => 'Where We Serve',
        'section' => 'locations'
    ],
    [
        'element_id' => 'locations-paragraph',
        'element_type' => 'p',
        'content' => 'Bringing legendary photo booth experiences across the nation',
        'section' => 'locations'
    ],
    [
        'element_id' => 'locations-cta-text',
        'element_type' => 'p',
        'content' => 'Want to see all our service locations?',
        'section' => 'locations'
    ],
    [
        'element_id' => 'locations-cta-button',
        'element_type' => 'span',
        'content' => 'View All Locations',
        'section' => 'locations'
    ]
];

?>
<!DOCTYPE html>
<html>
<head>
    <title>Sync Locations Section Content</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; background: #f5f5f5; }
        .container { max-width: 800px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; }
        h1 { color: #333; }
        pre { background: #f0f0f0; padding: 10px; border-radius: 4px; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Syncing Locations Section Content to Database</h1>
        <pre>
<?php

$success_count = 0;
$error_count = 0;

echo "Syncing Locations Section content...\n\n";

foreach ($locations_elements as $element) {
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
            $success_count++;
        } else {
            echo "✗ Error updating {$element['element_id']}: " . $update_stmt->error . "\n";
            $error_count++;
        }
        $update_stmt->close();
    } else {
        // Insert new entry
        $insert_stmt = $conn->prepare("INSERT INTO content_elements (element_id, element_type, content, page, section, updated_at) VALUES (?, ?, ?, 'index', ?, NOW())");
        $insert_stmt->bind_param("ssss", $element['element_id'], $element['element_type'], $element['content'], $element['section']);
        
        if ($insert_stmt->execute()) {
            echo "✓ Added: {$element['element_id']}\n";
            $success_count++;
        } else {
            echo "✗ Error adding {$element['element_id']}: " . $insert_stmt->error . "\n";
            $error_count++;
        }
        $insert_stmt->close();
    }
    
    $check_stmt->close();
}

echo "\n";
echo "========================================\n";
echo "Locations Section content sync completed!\n";
echo "Success: {$success_count} | Errors: {$error_count}\n";
echo "All 5 Locations Section elements are now in the database with section='locations'.\n";
?>
        </pre>
        <p><a href="admin.php?section=locations">View Locations Section in Admin Panel</a> | <a href="admin.php">Back to Admin Dashboard</a></p>
    </div>
</body>
</html>
<?php
$conn->close();
?>

