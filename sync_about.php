<?php
/**
 * Sync About MiHi Section Content to Database
 * This script ensures all About MiHi Section content from index.html is in the database
 * Access via: http://localhost/MiHi-Entertainment/sync_about.php
 */

header('Content-Type: text/html; charset=utf-8');
require_once 'config.php';

// Simple authentication check (session already started in config.php)
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    die('Access denied. Please <a href="login.php">login</a> first.');
}

$conn = getDBConnection();

// Define all About MiHi Section content elements from index.html (lines 2221-2515)
$about_elements = array(
    array(
        'element_id' => 'about-badge',
        'element_type' => 'div',
        'content' => '📸 Discover More',
        'section' => 'about',
        'page' => 'index'
    ),
    array(
        'element_id' => 'about-heading',
        'element_type' => 'h2',
        'content' => 'About MiHi',
        'section' => 'about',
        'page' => 'index'
    ),
    array(
        'element_id' => 'about-paragraph',
        'element_type' => 'p',
        'content' => 'Learn about our story, team, and commitment to creating',
        'section' => 'about',
        'page' => 'index'
    ),
    array(
        'element_id' => 'about-paragraph-highlight',
        'element_type' => 'span',
        'content' => 'unforgettable experiences',
        'section' => 'about',
        'page' => 'index'
    ),
    array(
        'element_id' => 'about-blog-heading',
        'element_type' => 'h3',
        'content' => 'Read our Blogs',
        'section' => 'about',
        'page' => 'index'
    ),
    array(
        'element_id' => 'about-blog-desc',
        'element_type' => 'p',
        'content' => 'Read about our events, activations, and more insights from our team.',
        'section' => 'about',
        'page' => 'index'
    ),
    array(
        'element_id' => 'about-blog-button',
        'element_type' => 'span',
        'content' => 'Read More',
        'section' => 'about',
        'page' => 'index'
    ),
    array(
        'element_id' => 'about-locations-heading',
        'element_type' => 'h3',
        'content' => 'Our Locations',
        'section' => 'about',
        'page' => 'index'
    ),
    array(
        'element_id' => 'about-locations-desc',
        'element_type' => 'p',
        'content' => 'View all of the locations we service nationwide.',
        'section' => 'about',
        'page' => 'index'
    ),
    array(
        'element_id' => 'about-locations-button',
        'element_type' => 'span',
        'content' => 'View Locations',
        'section' => 'about',
        'page' => 'index'
    ),
    array(
        'element_id' => 'about-cases-heading',
        'element_type' => 'h3',
        'content' => 'Case Studies',
        'section' => 'about',
        'page' => 'index'
    ),
    array(
        'element_id' => 'about-cases-desc',
        'element_type' => 'p',
        'content' => 'Learn how our activations have helped create memorable events.',
        'section' => 'about',
        'page' => 'index'
    ),
    array(
        'element_id' => 'about-cases-button',
        'element_type' => 'span',
        'content' => 'View Cases',
        'section' => 'about',
        'page' => 'index'
    ),
    array(
        'element_id' => 'about-faq-heading',
        'element_type' => 'h3',
        'content' => 'Frequently Asked Questions',
        'section' => 'about',
        'page' => 'index'
    ),
    array(
        'element_id' => 'about-faq-desc',
        'element_type' => 'p',
        'content' => 'View our most commonly asked questions and answers.',
        'section' => 'about',
        'page' => 'index'
    ),
    array(
        'element_id' => 'about-faq-button',
        'element_type' => 'span',
        'content' => 'View FAQ',
        'section' => 'about',
        'page' => 'index'
    ),
    array(
        'element_id' => 'about-mihi-heading',
        'element_type' => 'h3',
        'content' => 'About MiHi',
        'section' => 'about',
        'page' => 'index'
    ),
    array(
        'element_id' => 'about-mihi-desc',
        'element_type' => 'p',
        'content' => 'Learn about MiHi, see our hardworking team, and more.',
        'section' => 'about',
        'page' => 'index'
    ),
    array(
        'element_id' => 'about-mihi-button',
        'element_type' => 'span',
        'content' => 'Learn More',
        'section' => 'about',
        'page' => 'index'
    )
);

echo '<!DOCTYPE html>
<html>
<head>
    <title>Sync About MiHi Section</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; background: #f5f5f5; }
        .container { max-width: 800px; margin: 0 auto; background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        h1 { color: #333; }
        .success { color: #28a745; padding: 10px; background: #d4edda; border-radius: 4px; margin: 10px 0; }
        .error { color: #dc3545; padding: 10px; background: #f8d7da; border-radius: 4px; margin: 10px 0; }
        .info { color: #0c5460; padding: 10px; background: #d1ecf1; border-radius: 4px; margin: 10px 0; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Sync About MiHi Section</h1>';

$success_count = 0;
$error_count = 0;
$updated_count = 0;
$inserted_count = 0;

foreach ($about_elements as $element) {
    // Check if element already exists
    $check_stmt = $conn->prepare("SELECT id FROM content_elements WHERE element_id = ? AND page = ?");
    $check_stmt->bind_param("ss", $element['element_id'], $element['page']);
    $check_stmt->execute();
    $result = $check_stmt->get_result();
    $existing = $result->fetch_assoc();
    $check_stmt->close();
    
    if ($existing) {
        // Update existing
        $update_stmt = $conn->prepare("UPDATE content_elements SET element_type = ?, content = ?, section = ?, updated_at = NOW() WHERE id = ?");
        $update_stmt->bind_param("sssi", $element['element_type'], $element['content'], $element['section'], $existing['id']);
        if ($update_stmt->execute()) {
            $updated_count++;
            $success_count++;
        } else {
            echo '<div class="error">Error updating ' . htmlspecialchars($element['element_id']) . ': ' . $update_stmt->error . '</div>';
            $error_count++;
        }
        $update_stmt->close();
    } else {
        // Insert new
        $insert_stmt = $conn->prepare("INSERT INTO content_elements (element_id, element_type, content, page, section, updated_at) VALUES (?, ?, ?, ?, ?, NOW())");
        $insert_stmt->bind_param("sssss", $element['element_id'], $element['element_type'], $element['content'], $element['page'], $element['section']);
        if ($insert_stmt->execute()) {
            $inserted_count++;
            $success_count++;
        } else {
            echo '<div class="error">Error inserting ' . htmlspecialchars($element['element_id']) . ': ' . $insert_stmt->error . '</div>';
            $error_count++;
        }
        $insert_stmt->close();
    }
}

echo '<div class="success"><strong>Sync Complete!</strong></div>';
echo '<div class="info">Total elements processed: ' . count($about_elements) . '</div>';
echo '<div class="info">Inserted: ' . $inserted_count . ' | Updated: ' . $updated_count . '</div>';
echo '<div class="info">Success: ' . $success_count . ' | Errors: ' . $error_count . '</div>';
echo '<p><a href="admin.php?section=about">View in Admin Panel</a> | <a href="index.html">View Site</a></p>';
echo '</div></body></html>';

$conn->close();
?>

