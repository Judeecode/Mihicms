<?php
/**
 * Sync Contact/CTA Section Content to Database
 * This script ensures all Contact/CTA Section content from index.html is in the database
 * Access via: http://localhost/MiHi-Entertainment/sync_contact.php
 */

header('Content-Type: text/html; charset=utf-8');
require_once 'config.php';

// Simple authentication check (session already started in config.php)
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    die('Access denied. Please <a href="login.php">login</a> first.');
}

$conn = getDBConnection();

// Define all Contact/CTA Section content elements from index.html (lines 3993-4104)
$contact_elements = [
    [
        'element_id' => 'contact-badge',
        'element_type' => 'span',
        'content' => 'Photo Booth Concierge',
        'section' => 'contact'
    ],
    [
        'element_id' => 'contact-heading',
        'element_type' => 'h2',
        'content' => 'Let\'s Capture Something Legendary',
        'section' => 'contact'
    ],
    [
        'element_id' => 'contact-paragraph',
        'element_type' => 'p',
        'content' => 'Tell us the vibe, location, and guest list. We\'ll design a photobooth experience with custom backdrops, live print stations, and share stations that become the buzz of the night.',
        'section' => 'contact'
    ],
    [
        'element_id' => 'contact-feature-1-badge',
        'element_type' => 'span',
        'content' => 'Props',
        'section' => 'contact'
    ],
    [
        'element_id' => 'contact-feature-1-heading',
        'element_type' => 'h3',
        'content' => 'Signature Prop Styling',
        'section' => 'contact'
    ],
    [
        'element_id' => 'contact-feature-1-desc',
        'element_type' => 'p',
        'content' => 'LED wands, branded speech bubbles, couture accessories, and seasonal kits curated to your theme.',
        'section' => 'contact'
    ],
    [
        'element_id' => 'contact-feature-2-badge',
        'element_type' => 'span',
        'content' => 'Lighting',
        'section' => 'contact'
    ],
    [
        'element_id' => 'contact-feature-2-heading',
        'element_type' => 'h3',
        'content' => 'Studio Lighting Recipes',
        'section' => 'contact'
    ],
    [
        'element_id' => 'contact-feature-2-desc',
        'element_type' => 'p',
        'content' => 'Glam beauty lighting, saturated nightlife strobes, or cinematic key/fill setups for polished captures.',
        'section' => 'contact'
    ],
    [
        'element_id' => 'contact-feature-3-badge',
        'element_type' => 'span',
        'content' => 'Sharing',
        'section' => 'contact'
    ],
    [
        'element_id' => 'contact-feature-3-heading',
        'element_type' => 'h3',
        'content' => 'Instant Share Stations',
        'section' => 'contact'
    ],
    [
        'element_id' => 'contact-feature-3-desc',
        'element_type' => 'p',
        'content' => 'Custom microsites, immediate QR downloads, and live social walls to keep the excitement going online.',
        'section' => 'contact'
    ],
    [
        'element_id' => 'contact-button-1-text',
        'element_type' => 'span',
        'content' => 'Design My Booth Experience',
        'section' => 'contact'
    ],
    [
        'element_id' => 'contact-button-2-text',
        'element_type' => 'span',
        'content' => 'Call Us',
        'section' => 'contact'
    ],
    [
        'element_id' => 'contact-trust-1',
        'element_type' => 'span',
        'content' => 'Instant Prints & Digital Galleries',
        'section' => 'contact'
    ],
    [
        'element_id' => 'contact-trust-2',
        'element_type' => 'span',
        'content' => 'Onsite Booth Attendants',
        'section' => 'contact'
    ],
    [
        'element_id' => 'contact-trust-3',
        'element_type' => 'span',
        'content' => 'Custom Themes & Props',
        'section' => 'contact'
    ],
    [
        'element_id' => 'contact-trust-4',
        'element_type' => 'span',
        'content' => 'Nationwide Coverage',
        'section' => 'contact'
    ]
];

?>
<!DOCTYPE html>
<html>
<head>
    <title>Sync Contact/CTA Section Content</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; background: #f5f5f5; }
        .container { max-width: 800px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; }
        h1 { color: #333; }
        pre { background: #f0f0f0; padding: 10px; border-radius: 4px; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Syncing Contact/CTA Section Content to Database</h1>
        <pre>
<?php

$success_count = 0;
$error_count = 0;

echo "Syncing Contact/CTA Section content...\n\n";

foreach ($contact_elements as $element) {
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
echo "Contact/CTA Section content sync completed!\n";
echo "Success: {$success_count} | Errors: {$error_count}\n";
echo "All 18 Contact/CTA Section elements are now in the database with section='contact'.\n";
?>
        </pre>
        <p><a href="admin.php?section=contact">View Contact/CTA Section in Admin Panel</a> | <a href="admin.php">Back to Admin Dashboard</a></p>
    </div>
</body>
</html>
<?php
$conn->close();
?>

