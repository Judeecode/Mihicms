<?php
/**
 * Sync What We Offer Section - Part A Content to Database
 * This script ensures all What We Offer Section - Part A content from index.html is in the database
 * Access via: http://localhost/MiHi-Entertainment/sync_what_we_offer_part_a.php
 */

header('Content-Type: text/html; charset=utf-8');
require_once 'config.php';

// Simple authentication check (session already started in config.php)
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    die('Access denied. Please <a href="login.php">login</a> first.');
}

$conn = getDBConnection();

// Define all What We Offer Section - Part A content elements from index.html (lines 626-737)
$what_we_offer_elements = [
    [
        'element_id' => 'products-badge',
        'element_type' => 'span',
        'content' => 'What We Offer',
        'section' => 'what-we-offer-part-a'
    ],
    [
        'element_id' => 'products-heading',
        'element_type' => 'h2',
        'content' => 'Transform Your Event',
        'section' => 'what-we-offer-part-a'
    ],
    [
        'element_id' => 'products-paragraph',
        'element_type' => 'p',
        'content' => 'From AI-powered experiences to classic elegance, we bring the perfect entertainment to every celebration',
        'section' => 'what-we-offer-part-a'
    ],
    [
        'element_id' => 'products-ai-badge',
        'element_type' => 'span',
        'content' => '🔥 Most Popular',
        'section' => 'what-we-offer-part-a'
    ],
    [
        'element_id' => 'ai-booth-heading',
        'element_type' => 'h3',
        'content' => 'AI Photo Booth',
        'section' => 'what-we-offer-part-a'
    ],
    [
        'element_id' => 'ai-booth-paragraph',
        'element_type' => 'p',
        'content' => 'Transform into anyone or anything with cutting-edge AI technology. Your guests will become superheroes, celebrities, or fantasy characters in seconds.',
        'section' => 'what-we-offer-part-a'
    ],
    [
        'element_id' => 'products-ai-feature-1-title',
        'element_type' => 'p',
        'content' => 'Instant AI Generation',
        'section' => 'what-we-offer-part-a'
    ],
    [
        'element_id' => 'products-ai-feature-1-desc',
        'element_type' => 'p',
        'content' => 'Results in under 30 seconds',
        'section' => 'what-we-offer-part-a'
    ],
    [
        'element_id' => 'products-ai-feature-2-title',
        'element_type' => 'p',
        'content' => 'Unlimited Styles',
        'section' => 'what-we-offer-part-a'
    ],
    [
        'element_id' => 'products-ai-feature-2-desc',
        'element_type' => 'p',
        'content' => '50+ character themes available',
        'section' => 'what-we-offer-part-a'
    ],
    [
        'element_id' => 'products-ai-feature-3-title',
        'element_type' => 'p',
        'content' => 'Share Instantly',
        'section' => 'what-we-offer-part-a'
    ],
    [
        'element_id' => 'products-ai-feature-3-desc',
        'element_type' => 'p',
        'content' => 'Text, email, or QR code delivery',
        'section' => 'what-we-offer-part-a'
    ],
    [
        'element_id' => 'products-ai-button-1',
        'element_type' => 'span',
        'content' => 'Learn More',
        'section' => 'what-we-offer-part-a'
    ],
    [
        'element_id' => 'products-ai-button-2',
        'element_type' => 'span',
        'content' => 'Get Your Free Quote',
        'section' => 'what-we-offer-part-a'
    ],
    [
        'element_id' => 'products-ai-stat-number',
        'element_type' => 'span',
        'content' => '10k+',
        'section' => 'what-we-offer-part-a'
    ],
    [
        'element_id' => 'products-ai-stat-label',
        'element_type' => 'span',
        'content' => 'AI Photos Created',
        'section' => 'what-we-offer-part-a'
    ]
];

?>
<!DOCTYPE html>
<html>
<head>
    <title>Sync What We Offer Section - Part A Content</title>
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
        <h1>Syncing What We Offer Section - Part A Content to Database</h1>
        <pre>
<?php

$results = [];
$success_count = 0;
$error_count = 0;

echo "Syncing What We Offer Section - Part A content...\n\n";

foreach ($what_we_offer_elements as $element) {
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
echo "What We Offer Section - Part A content sync completed!\n";
echo "Success: {$success_count} | Errors: {$error_count}\n";
echo "All 16 What We Offer Section - Part A elements are now in the database with section='what-we-offer-part-a'.\n";
?>
        </pre>
        <p><a href="admin.php?section=what-we-offer-part-a">View What We Offer Section - Part A in Admin Panel</a> | <a href="admin.php">Back to Admin Dashboard</a></p>
    </div>
</body>
</html>
<?php
$conn->close();
?>

