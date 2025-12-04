<?php
/**
 * Sync Additional Services Section Content to Database
 * This script ensures all Additional Services Section content from index.html is in the database
 * Access via: http://localhost/MiHi-Entertainment/sync_additional_services.php
 */

header('Content-Type: text/html; charset=utf-8');
require_once 'config.php';

// Simple authentication check (session already started in config.php)
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    die('Access denied. Please <a href="login.php">login</a> first.');
}

$conn = getDBConnection();

// Define all Additional Services Section content elements from index.html (lines 1420-1591)
$additional_services_elements = [
    [
        'element_id' => 'additional-services-heading',
        'element_type' => 'h4',
        'content' => 'Complete Your Experience',
        'section' => 'additional-services'
    ],
    [
        'element_id' => 'additional-services-paragraph',
        'element_type' => 'p',
        'content' => 'Professional services to elevate every moment',
        'section' => 'additional-services'
    ],
    [
        'element_id' => 'additional-services-photo-badge',
        'element_type' => 'span',
        'content' => '📸 Professional Coverage',
        'section' => 'additional-services'
    ],
    [
        'element_id' => 'additional-services-photo-heading',
        'element_type' => 'h3',
        'content' => 'Event Photography',
        'section' => 'additional-services'
    ],
    [
        'element_id' => 'additional-services-photo-desc',
        'element_type' => 'p',
        'content' => 'Capture every precious moment with our professional photographers who blend seamlessly into your event',
        'section' => 'additional-services'
    ],
    [
        'element_id' => 'additional-services-photo-button',
        'element_type' => 'span',
        'content' => 'View Portfolio',
        'section' => 'additional-services'
    ],
    [
        'element_id' => 'additional-services-sketchbot-badge',
        'element_type' => 'span',
        'content' => '🤖 AI Artist',
        'section' => 'additional-services'
    ],
    [
        'element_id' => 'additional-services-sketchbot-heading',
        'element_type' => 'h3',
        'content' => 'SketchBot',
        'section' => 'additional-services'
    ],
    [
        'element_id' => 'additional-services-sketchbot-desc',
        'element_type' => 'p',
        'content' => 'Watch our robot artist create unique sketches of your guests in real-time',
        'section' => 'additional-services'
    ],
    [
        'element_id' => 'additional-services-sketchbot-button',
        'element_type' => 'span',
        'content' => 'See Magic',
        'section' => 'additional-services'
    ],
    [
        'element_id' => 'additional-services-cookie-heading',
        'element_type' => 'h4',
        'content' => 'Cookie Printer',
        'section' => 'additional-services'
    ],
    [
        'element_id' => 'additional-services-cookie-desc',
        'element_type' => 'p',
        'content' => 'Edible memories! Print guest photos directly onto delicious cookies',
        'section' => 'additional-services'
    ],
    [
        'element_id' => 'additional-services-cookie-button',
        'element_type' => 'span',
        'content' => 'Taste This',
        'section' => 'additional-services'
    ],
    [
        'element_id' => 'additional-services-pose-heading',
        'element_type' => 'h4',
        'content' => 'Signature Pose Cards',
        'section' => 'additional-services'
    ],
    [
        'element_id' => 'additional-services-pose-desc',
        'element_type' => 'p',
        'content' => 'Never run out of pose ideas with our fun flashcard collection',
        'section' => 'additional-services'
    ],
    [
        'element_id' => 'additional-services-pose-button',
        'element_type' => 'span',
        'content' => 'Strike a Pose',
        'section' => 'additional-services'
    ],
    [
        'element_id' => 'additional-services-lux-heading',
        'element_type' => 'h4',
        'content' => 'Lux Photography',
        'section' => 'additional-services'
    ],
    [
        'element_id' => 'additional-services-lux-desc',
        'element_type' => 'p',
        'content' => 'Elevated photography booth experience with luxury lighting and premium finishes',
        'section' => 'additional-services'
    ],
    [
        'element_id' => 'additional-services-lux-button',
        'element_type' => 'span',
        'content' => 'Discover Lux',
        'section' => 'additional-services'
    ]
];

?>
<!DOCTYPE html>
<html>
<head>
    <title>Sync Additional Services Section Content</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; background: #f5f5f5; }
        .container { max-width: 800px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; }
        h1 { color: #333; }
        pre { background: #f0f0f0; padding: 10px; border-radius: 4px; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Syncing Additional Services Section Content to Database</h1>
        <pre>
<?php

$success_count = 0;
$error_count = 0;

echo "Syncing Additional Services Section content...\n\n";

foreach ($additional_services_elements as $element) {
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
echo "Additional Services Section content sync completed!\n";
echo "Success: {$success_count} | Errors: {$error_count}\n";
echo "All 19 Additional Services Section elements are now in the database with section='additional-services'.\n";
?>
        </pre>
        <p><a href="admin.php?section=additional-services">View Additional Services Section in Admin Panel</a> | <a href="admin.php">Back to Admin Dashboard</a></p>
    </div>
</body>
</html>
<?php
$conn->close();
?>

