<?php
/**
 * Sync Event Types Section Content to Database
 * This script ensures all Event Types Section content from index.html is in the database
 * Access via: http://localhost/MiHi-Entertainment/sync_event_types.php
 */

header('Content-Type: text/html; charset=utf-8');
require_once 'config.php';

// Simple authentication check (session already started in config.php)
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    die('Access denied. Please <a href="login.php">login</a> first.');
}

$conn = getDBConnection();

// Define all Event Types Section content elements from index.html (lines 1594-1762)
$event_types_elements = [
    [
        'element_id' => 'event-types-badge',
        'element_type' => 'span',
        'content' => 'Perfect for Every Occasion',
        'section' => 'event-types'
    ],
    [
        'element_id' => 'event-types-heading',
        'element_type' => 'h2',
        'content' => 'From Intimate to Grand',
        'section' => 'event-types'
    ],
    [
        'element_id' => 'event-types-paragraph',
        'element_type' => 'p',
        'content' => 'From intimate celebrations to grand corporate events, we tailor our services to your unique vision',
        'section' => 'event-types'
    ],
    [
        'element_id' => 'event-types-wedding-badge',
        'element_type' => 'span',
        'content' => '💒 Wedding Events',
        'section' => 'event-types'
    ],
    [
        'element_id' => 'event-types-wedding-heading',
        'element_type' => 'h3',
        'content' => 'Make Your Special Day Unforgettable',
        'section' => 'event-types'
    ],
    [
        'element_id' => 'event-types-wedding-desc',
        'element_type' => 'p',
        'content' => 'Elegant photo booth experiences that capture every precious moment.',
        'section' => 'event-types'
    ],
    [
        'element_id' => 'event-types-corporate-badge',
        'element_type' => 'span',
        'content' => '🏢 Corporate Events',
        'section' => 'event-types'
    ],
    [
        'element_id' => 'event-types-corporate-heading',
        'element_type' => 'h3',
        'content' => 'Fully Branded Experiences',
        'section' => 'event-types'
    ],
    [
        'element_id' => 'event-types-corporate-desc',
        'element_type' => 'p',
        'content' => 'Customized solutions that align perfectly with your brand identity.',
        'section' => 'event-types'
    ],
    [
        'element_id' => 'event-types-social-badge',
        'element_type' => 'span',
        'content' => '🎉 Social Events',
        'section' => 'event-types'
    ],
    [
        'element_id' => 'event-types-social-heading',
        'element_type' => 'h3',
        'content' => 'Birthday Parties & Celebrations',
        'section' => 'event-types'
    ],
    [
        'element_id' => 'event-types-social-desc',
        'element_type' => 'p',
        'content' => 'Mitzvahs, anniversaries, and more - we make every celebration special.',
        'section' => 'event-types'
    ],
    [
        'element_id' => 'event-types-tradeshow-badge',
        'element_type' => 'span',
        'content' => '🎪 Trade Shows',
        'section' => 'event-types'
    ],
    [
        'element_id' => 'event-types-tradeshow-heading',
        'element_type' => 'h3',
        'content' => 'Engage Your Audience',
        'section' => 'event-types'
    ],
    [
        'element_id' => 'event-types-tradeshow-desc',
        'element_type' => 'p',
        'content' => 'Interactive experiences that drive engagement and brand awareness.',
        'section' => 'event-types'
    ],
    [
        'element_id' => 'event-types-holiday-badge',
        'element_type' => 'span',
        'content' => '🎄 Holiday Parties',
        'section' => 'event-types'
    ],
    [
        'element_id' => 'event-types-holiday-heading',
        'element_type' => 'h3',
        'content' => 'Festive & Memorable',
        'section' => 'event-types'
    ],
    [
        'element_id' => 'event-types-holiday-desc',
        'element_type' => 'p',
        'content' => 'Curated holiday experiences that bring joy and create lasting memories.',
        'section' => 'event-types'
    ],
    [
        'element_id' => 'event-types-casino-badge',
        'element_type' => 'span',
        'content' => '🎰 Casino Parties',
        'section' => 'event-types'
    ],
    [
        'element_id' => 'event-types-casino-heading',
        'element_type' => 'h3',
        'content' => 'Full Casino Experience',
        'section' => 'event-types'
    ],
    [
        'element_id' => 'event-types-casino-desc',
        'element_type' => 'p',
        'content' => 'Professional tables, dealers, and authentic casino entertainment.',
        'section' => 'event-types'
    ]
];

?>
<!DOCTYPE html>
<html>
<head>
    <title>Sync Event Types Section Content</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; background: #f5f5f5; }
        .container { max-width: 800px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; }
        h1 { color: #333; }
        pre { background: #f0f0f0; padding: 10px; border-radius: 4px; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Syncing Event Types Section Content to Database</h1>
        <pre>
<?php

$success_count = 0;
$error_count = 0;

echo "Syncing Event Types Section content...\n\n";

foreach ($event_types_elements as $element) {
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
echo "Event Types Section content sync completed!\n";
echo "Success: {$success_count} | Errors: {$error_count}\n";
echo "All 21 Event Types Section elements are now in the database with section='event-types'.\n";
?>
        </pre>
        <p><a href="admin.php?section=event-types">View Event Types Section in Admin Panel</a> | <a href="admin.php">Back to Admin Dashboard</a></p>
    </div>
</body>
</html>
<?php
$conn->close();
?>

