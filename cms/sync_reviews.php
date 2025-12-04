<?php
/**
 * Sync What Our Clients Say (Reviews) Section Content to Database
 * This script ensures all Reviews Section content from index.html is in the database
 * Access via: http://localhost/MiHi-Entertainment/sync_reviews.php
 */

header('Content-Type: text/html; charset=utf-8');
require_once 'config.php';

// Simple authentication check (session already started in config.php)
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    die('Access denied. Please <a href="login.php">login</a> first.');
}

$conn = getDBConnection();

// Define all Reviews Section content elements from index.html (lines 2720-2841)
$reviews_elements = array(
    array(
        'element_id' => 'reviews-badge-text',
        'element_type' => 'span',
        'content' => 'Google Reviews',
        'section' => 'reviews',
        'page' => 'index'
    ),
    array(
        'element_id' => 'reviews-heading',
        'element_type' => 'h2',
        'content' => 'What Our Clients Say',
        'section' => 'reviews',
        'page' => 'index'
    ),
    array(
        'element_id' => 'reviews-paragraph',
        'element_type' => 'p',
        'content' => 'Real experiences from our valued clients at MiHi Entertainment',
        'section' => 'reviews',
        'page' => 'index'
    ),
    array(
        'element_id' => 'reviews-rating-text',
        'element_type' => 'span',
        'content' => '4.9 Rating',
        'section' => 'reviews',
        'page' => 'index'
    ),
    array(
        'element_id' => 'reviews-button-text',
        'element_type' => 'span',
        'content' => 'Read More Reviews on Google',
        'section' => 'reviews',
        'page' => 'index'
    ),
    array(
        'element_id' => 'reviews-trust-1',
        'element_type' => 'span',
        'content' => 'Verified Reviews',
        'section' => 'reviews',
        'page' => 'index'
    ),
    array(
        'element_id' => 'reviews-trust-2',
        'element_type' => 'span',
        'content' => 'Real Experiences',
        'section' => 'reviews',
        'page' => 'index'
    ),
    array(
        'element_id' => 'reviews-trust-3',
        'element_type' => 'span',
        'content' => 'Client Testimonials',
        'section' => 'reviews',
        'page' => 'index'
    ),
    array(
        'element_id' => 'reviews-trust-4',
        'element_type' => 'span',
        'content' => 'Google Verified',
        'section' => 'reviews',
        'page' => 'index'
    )
);

echo '<!DOCTYPE html>
<html>
<head>
    <title>Sync Reviews Section</title>
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
        <h1>Sync What Our Clients Say (Reviews) Section</h1>';

$success_count = 0;
$error_count = 0;
$updated_count = 0;
$inserted_count = 0;

foreach ($reviews_elements as $element) {
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
echo '<div class="info">Total elements processed: ' . count($reviews_elements) . '</div>';
echo '<div class="info">Inserted: ' . $inserted_count . ' | Updated: ' . $updated_count . '</div>';
echo '<div class="info">Success: ' . $success_count . ' | Errors: ' . $error_count . '</div>';
echo '<p><a href="admin.php?section=reviews">View in Admin Panel</a> | <a href="index.html">View Site</a></p>';
echo '</div></body></html>';

$conn->close();
?>

