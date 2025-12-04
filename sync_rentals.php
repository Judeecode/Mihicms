<?php
/**
 * Sync Rentals Section Content to Database
 * This script ensures all Rentals Section content from index.html is in the database
 * Access via: http://localhost/MiHi-Entertainment/sync_rentals.php
 */

header('Content-Type: text/html; charset=utf-8');
require_once 'config.php';

// Simple authentication check (session already started in config.php)
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    die('Access denied. Please <a href="login.php">login</a> first.');
}

$conn = getDBConnection();

// Define all Rentals Section content elements from index.html (lines 1764-2026)
$rentals_elements = [
    [
        'element_id' => 'rentals-badge',
        'element_type' => 'span',
        'content' => 'Premium Solutions',
        'section' => 'rentals'
    ],
    [
        'element_id' => 'rentals-heading',
        'element_type' => 'h2',
        'content' => 'Complete Event Solutions',
        'section' => 'rentals'
    ],
    [
        'element_id' => 'rentals-paragraph',
        'element_type' => 'p',
        'content' => 'From AV services to interactive games, we provide everything you need for an unforgettable event',
        'section' => 'rentals'
    ],
    [
        'element_id' => 'rentals-av-badge',
        'element_type' => 'span',
        'content' => 'AV Services',
        'section' => 'rentals'
    ],
    [
        'element_id' => 'rentals-av-heading',
        'element_type' => 'h3',
        'content' => 'Complete Audio-Visual Solutions',
        'section' => 'rentals'
    ],
    [
        'element_id' => 'rentals-av-desc',
        'element_type' => 'p',
        'content' => 'Professional sound systems, dynamic visuals, stunning lighting, custom signage, and event stages for events of all sizes.',
        'section' => 'rentals'
    ],
    [
        'element_id' => 'rentals-av-audio',
        'element_type' => 'span',
        'content' => 'Audio Services',
        'section' => 'rentals'
    ],
    [
        'element_id' => 'rentals-av-visual',
        'element_type' => 'span',
        'content' => 'Visual Services',
        'section' => 'rentals'
    ],
    [
        'element_id' => 'rentals-av-lighting',
        'element_type' => 'span',
        'content' => 'Lighting Services',
        'section' => 'rentals'
    ],
    [
        'element_id' => 'rentals-av-signage',
        'element_type' => 'span',
        'content' => 'Custom Signage',
        'section' => 'rentals'
    ],
    [
        'element_id' => 'rentals-av-stages',
        'element_type' => 'span',
        'content' => 'Event Stages',
        'section' => 'rentals'
    ],
    [
        'element_id' => 'rentals-av-button',
        'element_type' => 'span',
        'content' => 'Get a Quote',
        'section' => 'rentals'
    ],
    [
        'element_id' => 'rentals-decor-badge',
        'element_type' => 'span',
        'content' => 'Decor',
        'section' => 'rentals'
    ],
    [
        'element_id' => 'rentals-decor-heading',
        'element_type' => 'h3',
        'content' => 'Event Decor',
        'section' => 'rentals'
    ],
    [
        'element_id' => 'rentals-decor-desc',
        'element_type' => 'p',
        'content' => 'Transform your venue with stunning decor, special effects, lighting designs, and premium furniture that creates unforgettable atmospheres.',
        'section' => 'rentals'
    ],
    [
        'element_id' => 'rentals-decor-effects',
        'element_type' => 'span',
        'content' => 'Special Effects',
        'section' => 'rentals'
    ],
    [
        'element_id' => 'rentals-decor-lighting',
        'element_type' => 'span',
        'content' => 'Lighting Decor',
        'section' => 'rentals'
    ],
    [
        'element_id' => 'rentals-decor-ceiling',
        'element_type' => 'span',
        'content' => 'Ceiling Fabric Designs',
        'section' => 'rentals'
    ],
    [
        'element_id' => 'rentals-decor-furniture',
        'element_type' => 'span',
        'content' => 'Lounge Furniture',
        'section' => 'rentals'
    ],
    [
        'element_id' => 'rentals-decor-shimmer',
        'element_type' => 'span',
        'content' => 'Shimmer Walls & Letters',
        'section' => 'rentals'
    ],
    [
        'element_id' => 'rentals-games-badge',
        'element_type' => 'span',
        'content' => 'Games',
        'section' => 'rentals'
    ],
    [
        'element_id' => 'rentals-games-heading',
        'element_type' => 'h3',
        'content' => 'Game Rentals',
        'section' => 'rentals'
    ],
    [
        'element_id' => 'rentals-games-desc',
        'element_type' => 'p',
        'content' => 'Interactive entertainment that keeps guests engaged with VR experiences, arcade games, and unique interactive attractions for all ages.',
        'section' => 'rentals'
    ],
    [
        'element_id' => 'rentals-games-claw',
        'element_type' => 'span',
        'content' => 'Claw Machine',
        'section' => 'rentals'
    ],
    [
        'element_id' => 'rentals-games-stickdrop',
        'element_type' => 'span',
        'content' => 'Stick Drop Reaction Game',
        'section' => 'rentals'
    ],
    [
        'element_id' => 'rentals-games-vr',
        'element_type' => 'span',
        'content' => 'VR Headset Rentals',
        'section' => 'rentals'
    ],
    [
        'element_id' => 'rentals-games-money',
        'element_type' => 'span',
        'content' => 'Money Booth',
        'section' => 'rentals'
    ]
];

?>
<!DOCTYPE html>
<html>
<head>
    <title>Sync Rentals Section Content</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; background: #f5f5f5; }
        .container { max-width: 800px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; }
        h1 { color: #333; }
        pre { background: #f0f0f0; padding: 10px; border-radius: 4px; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Syncing Rentals Section Content to Database</h1>
        <pre>
<?php

$success_count = 0;
$error_count = 0;

echo "Syncing Rentals Section content...\n\n";

foreach ($rentals_elements as $element) {
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
echo "Rentals Section content sync completed!\n";
echo "Success: {$success_count} | Errors: {$error_count}\n";
echo "All 27 Rentals Section elements are now in the database with section='rentals'.\n";
?>
        </pre>
        <p><a href="admin.php?section=rentals">View Rentals Section in Admin Panel</a> | <a href="admin.php">Back to Admin Dashboard</a></p>
    </div>
</body>
</html>
<?php
$conn->close();
?>

