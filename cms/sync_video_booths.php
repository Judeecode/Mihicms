<?php
/**
 * Sync Video Booths Section Content to Database
 * This script ensures all Video Booths Section content from index.html is in the database
 * Access via: http://localhost/MiHi-Entertainment/sync_video_booths.php
 */

header('Content-Type: text/html; charset=utf-8');
require_once 'config.php';

// Simple authentication check (session already started in config.php)
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    die('Access denied. Please <a href="login.php">login</a> first.');
}

$conn = getDBConnection();

// Define all Video Booths Section content elements from index.html (lines 1094-1342)
$video_booths_elements = [
    [
        'element_id' => 'video-booths-badge',
        'element_type' => 'span',
        'content' => '🎥 Video Experiences',
        'section' => 'video-booths'
    ],
    [
        'element_id' => 'video-booths-heading',
        'element_type' => 'h2',
        'content' => 'Video Booths',
        'section' => 'video-booths'
    ],
    [
        'element_id' => 'video-booths-paragraph',
        'element_type' => 'p',
        'content' => 'Create share-worthy videos that capture the energy and emotion of your event',
        'section' => 'video-booths'
    ],
    [
        'element_id' => 'video-booths-360-heading',
        'element_type' => 'h3',
        'content' => '360° Video Booth',
        'section' => 'video-booths'
    ],
    [
        'element_id' => 'video-booths-360-desc',
        'element_type' => 'p',
        'content' => 'Watch as the camera rotates around you in stunning slow motion, capturing epic moments from every angle',
        'section' => 'video-booths'
    ],
    [
        'element_id' => 'video-booths-360-button',
        'element_type' => 'span',
        'content' => 'Explore 360° Experience',
        'section' => 'video-booths'
    ],
    [
        'element_id' => 'video-booths-feature-1-badge',
        'element_type' => 'span',
        'content' => 'Rotation',
        'section' => 'video-booths'
    ],
    [
        'element_id' => 'video-booths-feature-1-title',
        'element_type' => 'h4',
        'content' => 'Full 360° Rotation',
        'section' => 'video-booths'
    ],
    [
        'element_id' => 'video-booths-feature-1-desc',
        'element_type' => 'p',
        'content' => 'Cameras capture you from all angles in cinematic slow motion',
        'section' => 'video-booths'
    ],
    [
        'element_id' => 'video-booths-feature-2-badge',
        'element_type' => 'span',
        'content' => 'Sharing',
        'section' => 'video-booths'
    ],
    [
        'element_id' => 'video-booths-feature-2-title',
        'element_type' => 'h4',
        'content' => 'Instant Sharing',
        'section' => 'video-booths'
    ],
    [
        'element_id' => 'video-booths-feature-2-desc',
        'element_type' => 'p',
        'content' => 'Videos delivered instantly via text, email, or QR code',
        'section' => 'video-booths'
    ],
    [
        'element_id' => 'video-booths-feature-3-badge',
        'element_type' => 'span',
        'content' => 'Branding',
        'section' => 'video-booths'
    ],
    [
        'element_id' => 'video-booths-feature-3-title',
        'element_type' => 'h4',
        'content' => 'Custom Branding',
        'section' => 'video-booths'
    ],
    [
        'element_id' => 'video-booths-feature-3-desc',
        'element_type' => 'p',
        'content' => 'Add your logo, music, and effects to every video',
        'section' => 'video-booths'
    ],
    [
        'element_id' => 'video-booths-bullet-heading',
        'element_type' => 'h3',
        'content' => 'Bullet-Time Array',
        'section' => 'video-booths'
    ],
    [
        'element_id' => 'video-booths-bullet-desc',
        'element_type' => 'p',
        'content' => 'Freeze time with multiple cameras capturing the iconic Matrix slow-motion effect',
        'section' => 'video-booths'
    ],
    [
        'element_id' => 'video-booths-bullet-button',
        'element_type' => 'span',
        'content' => 'Discover More',
        'section' => 'video-booths'
    ],
    [
        'element_id' => 'video-booths-glambot-heading',
        'element_type' => 'h3',
        'content' => 'GlamBot Video',
        'section' => 'video-booths'
    ],
    [
        'element_id' => 'video-booths-glambot-desc',
        'element_type' => 'p',
        'content' => 'Automated camera on a robotic arm creates stunning cinematic slow-motion videos',
        'section' => 'video-booths'
    ],
    [
        'element_id' => 'video-booths-glambot-button',
        'element_type' => 'span',
        'content' => 'See It In Action',
        'section' => 'video-booths'
    ],
    [
        'element_id' => 'video-booths-vogue-heading',
        'element_type' => 'h3',
        'content' => 'Vogue Video Booth',
        'section' => 'video-booths'
    ],
    [
        'element_id' => 'video-booths-vogue-desc',
        'element_type' => 'p',
        'content' => 'Create magazine-worthy video content with style and elegance',
        'section' => 'video-booths'
    ],
    [
        'element_id' => 'video-booths-vogue-button',
        'element_type' => 'span',
        'content' => 'Discover More',
        'section' => 'video-booths'
    ],
    [
        'element_id' => 'video-booths-slowmo-heading',
        'element_type' => 'h3',
        'content' => 'Slow Motion Video Booth',
        'section' => 'video-booths'
    ],
    [
        'element_id' => 'video-booths-slowmo-desc',
        'element_type' => 'p',
        'content' => 'Capture moments in stunning slow motion for dramatic effect',
        'section' => 'video-booths'
    ],
    [
        'element_id' => 'video-booths-slowmo-button',
        'element_type' => 'span',
        'content' => 'See It In Action',
        'section' => 'video-booths'
    ],
    [
        'element_id' => 'video-booths-testimonial-heading',
        'element_type' => 'h3',
        'content' => 'Video Testimonial Booth',
        'section' => 'video-booths'
    ],
    [
        'element_id' => 'video-booths-testimonial-desc',
        'element_type' => 'p',
        'content' => 'Collect authentic customer testimonials and feedback in video format',
        'section' => 'video-booths'
    ],
    [
        'element_id' => 'video-booths-testimonial-button',
        'element_type' => 'span',
        'content' => 'Discover More',
        'section' => 'video-booths'
    ],
    [
        'element_id' => 'video-booths-see-more',
        'element_type' => 'span',
        'content' => 'See More',
        'section' => 'video-booths'
    ]
];

?>
<!DOCTYPE html>
<html>
<head>
    <title>Sync Video Booths Section Content</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; background: #f5f5f5; }
        .container { max-width: 800px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; }
        .success { color: green; }
        .error { color: red; }
        h1 { color: #333; }
        pre { background: #f0f0f0; padding: 10px; border-radius: 4px; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Syncing Video Booths Section Content to Database</h1>
        <pre>
<?php

$success_count = 0;
$error_count = 0;

echo "Syncing Video Booths Section content...\n\n";

foreach ($video_booths_elements as $element) {
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
echo "Video Booths Section content sync completed!\n";
echo "Success: {$success_count} | Errors: {$error_count}\n";
echo "All 31 Video Booths Section elements are now in the database with section='video-booths'.\n";
?>
        </pre>
        <p><a href="admin.php?section=video-booths">View Video Booths Section in Admin Panel</a> | <a href="admin.php">Back to Admin Dashboard</a></p>
    </div>
</body>
</html>
<?php
$conn->close();
?>

