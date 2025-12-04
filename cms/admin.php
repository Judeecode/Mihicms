<?php
require_once 'config.php';
requireLogin();

// Increase upload limits for hero media uploads
// Note: These settings may not work if PHP is running in safe mode or if ini_set is disabled
// In that case, you'll need to edit php.ini directly
@ini_set('upload_max_filesize', '100M');
@ini_set('post_max_size', '100M');
@ini_set('max_execution_time', '300');
@ini_set('memory_limit', '256M');

// Helper function to convert PHP ini size values to bytes
function convertToBytes($val) {
    $val = trim($val);
    $last = strtolower($val[strlen($val)-1]);
    $val = (int)$val;
    switch($last) {
        case 'g': $val *= 1024;
        case 'm': $val *= 1024;
        case 'k': $val *= 1024;
    }
    return $val;
}

$conn = getDBConnection();

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add':
                $element_id = trim(isset($_POST['element_id']) ? $_POST['element_id'] : '');
                $element_type = isset($_POST['element_type']) ? $_POST['element_type'] : '';
                $content = trim(isset($_POST['content']) ? $_POST['content'] : '');
                $page = isset($_POST['page']) ? $_POST['page'] : 'index';
                // Use custom section if provided, otherwise use dropdown selection
                $section = trim(isset($_POST['section_custom']) && !empty($_POST['section_custom']) ? $_POST['section_custom'] : (isset($_POST['section']) ? $_POST['section'] : ''));
                
                if (!empty($element_id) && !empty($element_type) && !empty($content)) {
                    $stmt = $conn->prepare("INSERT INTO content_elements (element_id, element_type, content, page, section, updated_at) VALUES (?, ?, ?, ?, ?, NOW())");
                    $stmt->bind_param("sssss", $element_id, $element_type, $content, $page, $section);
                    if ($stmt->execute()) {
                        $success = "Content added successfully!";
                    } else {
                        $error = "Error: " . $stmt->error;
                    }
                    $stmt->close();
                } else {
                    $error = "Please fill in all required fields.";
                }
                break;
                
            case 'edit':
                $id = intval(isset($_POST['id']) ? $_POST['id'] : 0);
                $content = trim(isset($_POST['content']) ? $_POST['content'] : '');
                
                if ($id > 0 && !empty($content)) {
                    $stmt = $conn->prepare("UPDATE content_elements SET content = ?, updated_at = NOW() WHERE id = ?");
                    $stmt->bind_param("si", $content, $id);
                    if ($stmt->execute()) {
                        $success = "Content updated successfully!";
                    } else {
                        $error = "Error: " . $stmt->error;
                    }
                    $stmt->close();
                } else {
                    $error = "Invalid data provided.";
                }
                break;
                
            case 'delete':
                $id = intval(isset($_POST['id']) ? $_POST['id'] : 0);
                
                if ($id > 0) {
                    $stmt = $conn->prepare("DELETE FROM content_elements WHERE id = ?");
                    $stmt->bind_param("i", $id);
                    if ($stmt->execute()) {
                        $success = "Content deleted successfully!";
                    } else {
                        $error = "Error: " . $stmt->error;
                    }
                    $stmt->close();
                }
                break;
                
            case 'upload_brochure_thumbnail':
                // Handle brochure thumbnail image upload
                $upload_dir = '../uploads/brochure/';
                if (!file_exists($upload_dir)) {
                    mkdir($upload_dir, 0755, true);
                }
                
                if (isset($_FILES['brochure_thumbnail']) && $_FILES['brochure_thumbnail']['error'] === UPLOAD_ERR_OK) {
                    $file = $_FILES['brochure_thumbnail'];
                    $file_name = $file['name'];
                    $file_tmp = $file['tmp_name'];
                    $file_size = $file['size'];
                    $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
                    
                    // Only allow image files
                    $allowed_image = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
                    if (in_array($file_ext, $allowed_image)) {
                        // Check file size (10MB limit for images)
                        $max_size = 10 * 1024 * 1024; // 10MB in bytes
                        if ($file_size > $max_size) {
                            $error = "File is too large. Maximum size is 10MB. Your file is " . round($file_size / 1024 / 1024, 2) . "MB.";
                        } else {
                            // Generate unique filename
                            $new_filename = 'brochure-thumbnail-' . time() . '.' . $file_ext;
                            $upload_path = $upload_dir . $new_filename;
                            
                            // Move uploaded file
                            if (move_uploaded_file($file_tmp, $upload_path)) {
                                // Convert path to root-relative for database storage (remove ../ prefix)
                                $db_path = str_replace('../', '', $upload_path);
                                
                                // Store in database
                                $element_id = 'brochure-thumbnail';
                                $element_type = 'p';
                                $content = 'IMAGE:' . $db_path;
                                $page = 'index';
                                $section = 'online-brochure';
                                
                                // Check if brochure thumbnail already exists
                                $check_stmt = $conn->prepare("SELECT id, content FROM content_elements WHERE element_id = ? AND page = ?");
                                $check_stmt->bind_param("ss", $element_id, $page);
                                $check_stmt->execute();
                                $check_result = $check_stmt->get_result();
                                
                                if ($check_result->num_rows > 0) {
                                    // Update existing
                                    $row = $check_result->fetch_assoc();
                                    $old_content = $row['content'];
                                    
                                    // Extract old file path
                                    $old_file = $old_content;
                                    if (strpos($old_content, ':') !== false) {
                                        $old_file = substr($old_content, strpos($old_content, ':') + 1);
                                    }
                                    
                                    // Convert root-relative path to path accessible from cms/ folder
                                    // If path doesn't start with ../, add it (paths in DB are root-relative)
                                    if (strpos($old_file, '../') !== 0 && strpos($old_file, '/') !== 0) {
                                        $old_file = '../' . $old_file;
                                    }
                                    
                                    // Delete old file if it exists
                                    if (file_exists($old_file)) {
                                        @unlink($old_file);
                                    }
                                    
                                    $update_stmt = $conn->prepare("UPDATE content_elements SET element_type = ?, content = ?, updated_at = NOW() WHERE element_id = ? AND page = ?");
                                    $update_stmt->bind_param("ssss", $element_type, $content, $element_id, $page);
                                    if ($update_stmt->execute()) {
                                        $success = "Brochure thumbnail updated successfully!";
                                    } else {
                                        $error = "Error updating thumbnail: " . $update_stmt->error;
                                    }
                                    $update_stmt->close();
                                } else {
                                    // Insert new
                                    $insert_stmt = $conn->prepare("INSERT INTO content_elements (element_id, element_type, content, page, section, updated_at) VALUES (?, ?, ?, ?, ?, NOW())");
                                    $insert_stmt->bind_param("sssss", $element_id, $element_type, $content, $page, $section);
                                    if ($insert_stmt->execute()) {
                                        $success = "Brochure thumbnail uploaded successfully!";
                                    } else {
                                        $error = "Error saving thumbnail: " . $insert_stmt->error;
                                    }
                                    $insert_stmt->close();
                                }
                                
                                $check_stmt->close();
                            } else {
                                $error = "Failed to move uploaded file. Check directory permissions for: " . $upload_dir;
                            }
                        }
                    } else {
                        $error = "Invalid file type. Only image files (JPG, PNG, GIF, WebP) are allowed.";
                    }
                } else {
                    $upload_error = isset($_FILES['brochure_thumbnail']) ? $_FILES['brochure_thumbnail']['error'] : UPLOAD_ERR_NO_FILE;
                    $upload_errors = array(
                        UPLOAD_ERR_INI_SIZE => 'File exceeds upload_max_filesize directive in php.ini',
                        UPLOAD_ERR_FORM_SIZE => 'File exceeds MAX_FILE_SIZE directive in HTML form',
                        UPLOAD_ERR_PARTIAL => 'File was only partially uploaded',
                        UPLOAD_ERR_NO_FILE => 'No file was uploaded',
                        UPLOAD_ERR_NO_TMP_DIR => 'Missing temporary folder',
                        UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk',
                        UPLOAD_ERR_EXTENSION => 'File upload stopped by extension'
                    );
                    if (isset($upload_errors[$upload_error])) {
                        $error = "Upload error: " . $upload_errors[$upload_error];
                    } else {
                        $error = "File upload error (Code: " . $upload_error . "). Please try again.";
                    }
                }
                break;
                
            case 'upload_brochure_pdf':
                // Handle brochure PDF upload
                $upload_dir = '../uploads/brochure/';
                if (!file_exists($upload_dir)) {
                    mkdir($upload_dir, 0755, true);
                }
                
                if (isset($_FILES['brochure_pdf']) && $_FILES['brochure_pdf']['error'] === UPLOAD_ERR_OK) {
                    $file = $_FILES['brochure_pdf'];
                    $file_name = $file['name'];
                    $file_tmp = $file['tmp_name'];
                    $file_size = $file['size'];
                    $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
                    
                    // Only allow PDF files
                    if ($file_ext === 'pdf') {
                        // Check file size (100MB limit)
                        $max_size = 100 * 1024 * 1024; // 100MB in bytes
                        if ($file_size > $max_size) {
                            $error = "File is too large. Maximum size is 100MB. Your file is " . round($file_size / 1024 / 1024, 2) . "MB.";
                        } else {
                            // Generate unique filename
                            $new_filename = 'brochure-' . time() . '.pdf';
                            $upload_path = $upload_dir . $new_filename;
                            
                            // Move uploaded file
                            if (move_uploaded_file($file_tmp, $upload_path)) {
                                // Convert path to root-relative for database storage (remove ../ prefix)
                                $db_path = str_replace('../', '', $upload_path);
                                
                                // Store in database
                                $element_id = 'brochure-pdf';
                                $element_type = 'p';
                                $content = 'PDF:' . $db_path;
                                $page = 'index';
                                $section = 'online-brochure';
                                
                                // Check if brochure PDF already exists
                                $check_stmt = $conn->prepare("SELECT id, content FROM content_elements WHERE element_id = ? AND page = ?");
                                $check_stmt->bind_param("ss", $element_id, $page);
                                $check_stmt->execute();
                                $check_result = $check_stmt->get_result();
                                
                                if ($check_result->num_rows > 0) {
                                    // Update existing
                                    $row = $check_result->fetch_assoc();
                                    $old_content = $row['content'];
                                    
                                    // Extract old file path
                                    $old_file = $old_content;
                                    if (strpos($old_content, ':') !== false) {
                                        $old_file = substr($old_content, strpos($old_content, ':') + 1);
                                    }
                                    
                                    // Convert root-relative path to path accessible from cms/ folder
                                    // If path doesn't start with ../, add it (paths in DB are root-relative)
                                    if (strpos($old_file, '../') !== 0 && strpos($old_file, '/') !== 0) {
                                        $old_file = '../' . $old_file;
                                    }
                                    
                                    // Delete old file if it exists
                                    if (file_exists($old_file)) {
                                        @unlink($old_file);
                                    }
                                    
                                    $update_stmt = $conn->prepare("UPDATE content_elements SET element_type = ?, content = ?, updated_at = NOW() WHERE element_id = ? AND page = ?");
                                    $update_stmt->bind_param("ssss", $element_type, $content, $element_id, $page);
                                    if ($update_stmt->execute()) {
                                        $success = "Brochure PDF updated successfully!";
                                    } else {
                                        $error = "Error updating PDF: " . $update_stmt->error;
                                    }
                                    $update_stmt->close();
                                } else {
                                    // Insert new
                                    $insert_stmt = $conn->prepare("INSERT INTO content_elements (element_id, element_type, content, page, section, updated_at) VALUES (?, ?, ?, ?, ?, NOW())");
                                    $insert_stmt->bind_param("sssss", $element_id, $element_type, $content, $page, $section);
                                    if ($insert_stmt->execute()) {
                                        $success = "Brochure PDF uploaded successfully!";
                                    } else {
                                        $error = "Error saving PDF: " . $insert_stmt->error;
                                    }
                                    $insert_stmt->close();
                                }
                                
                                $check_stmt->close();
                            } else {
                                $error = "Failed to move uploaded file. Check directory permissions for: " . $upload_dir;
                            }
                        }
                    } else {
                        $error = "Invalid file type. Only PDF files are allowed.";
                    }
                } else {
                    $upload_error = isset($_FILES['brochure_pdf']) ? $_FILES['brochure_pdf']['error'] : UPLOAD_ERR_NO_FILE;
                    $upload_errors = array(
                        UPLOAD_ERR_INI_SIZE => 'File exceeds upload_max_filesize directive in php.ini',
                        UPLOAD_ERR_FORM_SIZE => 'File exceeds MAX_FILE_SIZE directive in HTML form',
                        UPLOAD_ERR_PARTIAL => 'File was only partially uploaded',
                        UPLOAD_ERR_NO_FILE => 'No file was uploaded',
                        UPLOAD_ERR_NO_TMP_DIR => 'Missing temporary folder',
                        UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk',
                        UPLOAD_ERR_EXTENSION => 'File upload stopped by extension'
                    );
                    if (isset($upload_errors[$upload_error])) {
                        $error = "Upload error: " . $upload_errors[$upload_error];
                    } else {
                        $error = "File upload error (Code: " . $upload_error . "). Please try again.";
                    }
                }
                break;
                
            case 'upload_hero_media':
                // Handle hero background media upload (image or video)
                $upload_dir = '../uploads/hero/';
                if (!file_exists($upload_dir)) {
                    mkdir($upload_dir, 0755, true);
                }
                
                if (isset($_FILES['hero_media'])) {
                    $file = $_FILES['hero_media'];
                    $upload_error = $file['error'];
                    
                    // Check for upload errors
                    $upload_errors = array(
                        UPLOAD_ERR_INI_SIZE => 'File exceeds upload_max_filesize directive in php.ini',
                        UPLOAD_ERR_FORM_SIZE => 'File exceeds MAX_FILE_SIZE directive in HTML form',
                        UPLOAD_ERR_PARTIAL => 'File was only partially uploaded',
                        UPLOAD_ERR_NO_FILE => 'No file was uploaded',
                        UPLOAD_ERR_NO_TMP_DIR => 'Missing temporary folder',
                        UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk',
                        UPLOAD_ERR_EXTENSION => 'File upload stopped by extension'
                    );
                    
                    if ($upload_error === UPLOAD_ERR_OK) {
                        $file_name = $file['name'];
                        $file_tmp = $file['tmp_name'];
                        $file_size = $file['size'];
                        $file_type = $file['type'];
                        
                        // Check file size (50MB limit)
                        $max_size = 50 * 1024 * 1024; // 50MB in bytes
                        if ($file_size > $max_size) {
                            $error = "File is too large. Maximum size is 50MB. Your file is " . round($file_size / 1024 / 1024, 2) . "MB.";
                        } else {
                            // Get file extension
                            $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
                            
                            // Allowed file types
                            $allowed_image = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
                            $allowed_video = ['mp4', 'webm', 'ogg'];
                            $allowed = array_merge($allowed_image, $allowed_video);
                            
                            if (in_array($file_ext, $allowed)) {
                                // Generate unique filename
                                $new_filename = 'hero-background-' . time() . '.' . $file_ext;
                                $upload_path = $upload_dir . $new_filename;
                                
                                // Move uploaded file
                                if (move_uploaded_file($file_tmp, $upload_path)) {
                            // Determine media type
                            $media_type = in_array($file_ext, $allowed_image) ? 'image' : 'video';
                            
                            // Convert path to root-relative for database storage (remove ../ prefix)
                            $db_path = str_replace('../', '', $upload_path);
                            
                            // Store in database - use 'p' as element_type (compatible with existing ENUM)
                            // Store media type and path as "MEDIA_TYPE:path" format
                            $element_id = 'hero-background-media';
                            $element_type = 'p'; // Use 'p' to work with existing ENUM constraint
                            $content = strtoupper($media_type) . ':' . $db_path; // Format: "IMAGE:path" or "VIDEO:path"
                            $page = 'index';
                            $section = 'hero';
                            
                            // Check if hero background media already exists
                            $check_stmt = $conn->prepare("SELECT id, content FROM content_elements WHERE element_id = ? AND page = ?");
                            $check_stmt->bind_param("ss", $element_id, $page);
                            $check_stmt->execute();
                            $check_result = $check_stmt->get_result();
                            
                            if ($check_result->num_rows > 0) {
                                // Update existing
                                $row = $check_result->fetch_assoc();
                                $old_content = $row['content'];
                                
                                // Extract old file path (handle both old format and new format)
                                $old_file = $old_content;
                                if (strpos($old_content, ':') !== false) {
                                    $old_file = substr($old_content, strpos($old_content, ':') + 1);
                                }
                                
                                // Convert root-relative path to path accessible from cms/ folder
                                // If path doesn't start with ../, add it (paths in DB are root-relative)
                                if (strpos($old_file, '../') !== 0 && strpos($old_file, '/') !== 0) {
                                    $old_file = '../' . $old_file;
                                }
                                
                                // Delete old file if it exists
                                if (file_exists($old_file)) {
                                    @unlink($old_file);
                                }
                                
                                $update_stmt = $conn->prepare("UPDATE content_elements SET element_type = ?, content = ?, updated_at = NOW() WHERE element_id = ? AND page = ?");
                                $update_stmt->bind_param("ssss", $element_type, $content, $element_id, $page);
                                if ($update_stmt->execute()) {
                                    $success = "Hero background media updated successfully!";
                                } else {
                                    $error = "Error updating media: " . $update_stmt->error;
                                }
                                $update_stmt->close();
                            } else {
                                // Insert new
                                $insert_stmt = $conn->prepare("INSERT INTO content_elements (element_id, element_type, content, page, section, updated_at) VALUES (?, ?, ?, ?, ?, NOW())");
                                $insert_stmt->bind_param("sssss", $element_id, $element_type, $content, $page, $section);
                                if ($insert_stmt->execute()) {
                                    $success = "Hero background media uploaded successfully!";
                                } else {
                                    $error = "Error saving media: " . $insert_stmt->error;
                                }
                                $insert_stmt->close();
                            }
                            
                            $check_stmt->close();
                                } else {
                                    $error = "Failed to move uploaded file. Check directory permissions for: " . $upload_dir;
                                }
                            } else {
                                $error = "Invalid file type. Allowed: " . implode(', ', $allowed) . ". Your file: ." . $file_ext;
                            }
                        }
                    } else {
                        // Show specific upload error
                        if (isset($upload_errors[$upload_error])) {
                            $error = "Upload error: " . $upload_errors[$upload_error];
                        } else {
                            $error = "File upload error (Code: " . $upload_error . "). Please try again.";
                        }
                    }
                } else {
                    $error = "No file was uploaded. Please select a file.";
                }
                break;
        }
    }
}

// Get selected section filter
$selected_section = isset($_GET['section']) ? $_GET['section'] : 'all';

// Get all content elements, grouped by section
if ($selected_section === 'all') {
    $result = $conn->query("SELECT * FROM content_elements WHERE page = 'index' ORDER BY section, element_type, id");
} elseif ($selected_section === 'none') {
    $result = $conn->query("SELECT * FROM content_elements WHERE page = 'index' AND (section IS NULL OR section = '') ORDER BY element_type, id");
} else {
    $stmt = $conn->prepare("SELECT * FROM content_elements WHERE page = 'index' AND section = ? ORDER BY element_type, id");
    $stmt->bind_param("s", $selected_section);
    $stmt->execute();
    $result = $stmt->get_result();
}

$content_elements = [];
$sections = [];
while ($row = $result->fetch_assoc()) {
    $content_elements[] = $row;
    $section_name = !empty($row['section']) ? $row['section'] : 'none';
    if (!isset($sections[$section_name])) {
        $sections[$section_name] = [];
    }
    $sections[$section_name][] = $row;
}

// Get all unique sections from database
$sections_query = $conn->query("SELECT DISTINCT section FROM content_elements WHERE page = 'index' ORDER BY section");
$db_sections = [];
while ($row = $sections_query->fetch_assoc()) {
    if (!empty($row['section'])) {
        $db_sections[$row['section']] = true;
    }
}

// Define all available sections (predefined list)
$all_sections = array('all' => 'All Sections');
$predefined_sections = array(
    'hero' => 'Hero Section',
    'online-brochure' => 'Brochure Section',
    'what-we-offer-part-a' => 'What We Offer Section - Part A',
    'video-booths' => 'Video Booths Section',
    'additional-services' => 'Additional Services Section',
    'event-types' => 'Event Types Section',
    'rentals' => 'Rentals Section',
    'galleries' => 'Explore Our Work Section',
    'about' => 'About MiHi Section',
    'reviews' => 'What Our Clients Say Section',
    'locations' => 'Locations Section',
    'contact' => 'Contact/CTA Section'
);

// Add all predefined sections to dropdown (even if they don't have content yet)
foreach ($predefined_sections as $section_key => $section_label) {
    $all_sections[$section_key] = $section_label;
}

// Add any other sections found in database that aren't in predefined list
$sections_query2 = $conn->query("SELECT DISTINCT section FROM content_elements WHERE page = 'index' ORDER BY section");
while ($row = $sections_query2->fetch_assoc()) {
    if (!empty($row['section']) && !isset($predefined_sections[$row['section']])) {
        $all_sections[$row['section']] = ucfirst(str_replace('-', ' ', $row['section']));
    }
}

$all_sections['none'] = 'No Section';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - MiHi CMS</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body {
            font-family: 'Inter', sans-serif;
        }
    </style>
</head>
<body class="bg-gray-50 min-h-screen">
    <!-- Header -->
    <header class="bg-white shadow-sm border-b">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4">
            <div class="flex justify-between items-center">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">MiHi CMS Dashboard</h1>
                    <p class="text-sm text-gray-600">Welcome, <?php echo htmlspecialchars($_SESSION['admin_username']); ?></p>
                </div>
                <div class="flex gap-4">
                    <a href="../index.html" target="_blank" class="px-4 py-2 text-blue-600 hover:text-blue-700 font-medium">
                        View Site
                    </a>
                    <a href="logout.php" class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition">
                        Logout
                    </a>
                </div>
            </div>
        </div>
    </header>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <?php if (isset($success)): ?>
            <div class="bg-green-50 border-l-4 border-green-400 text-green-700 px-4 py-3 rounded-lg mb-6 flex items-center gap-2">
                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                </svg>
                <span><?php echo htmlspecialchars($success); ?></span>
            </div>
        <?php endif; ?>
        
        <?php if (isset($error)): ?>
            <div class="bg-red-50 border-l-4 border-red-400 text-red-700 px-4 py-3 rounded-lg mb-6 flex items-center gap-2">
                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                </svg>
                <span><?php echo htmlspecialchars($error); ?></span>
            </div>
        <?php endif; ?>
        
        <!-- Quick Help Card -->
        <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6">
            <div class="flex items-start gap-3">
                <svg class="w-5 h-5 text-blue-600 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-8-3a1 1 0 00-.867.5 1 1 0 11-1.731-1A3 3 0 0113 8a3.001 3.001 0 01-2 2.829V11a1 1 0 112 0v1a1 1 0 11-2 0 1 1 0 10-4 0v-1a5.002 5.002 0 00-1-9.9V5a1 1 0 112 0v.01a1 1 0 11-2 0 1 1 0 10-4 0 1 1 0 112 0v1a1 1 0 11-2 0 1 1 0 10-4 0z" clip-rule="evenodd"/>
                </svg>
                <div class="flex-1">
                    <h3 class="text-sm font-semibold text-blue-900 mb-1">Quick Tips</h3>
                    <ul class="text-sm text-blue-800 space-y-1 list-disc list-inside">
                        <li>Use the section filter to quickly find content by page section</li>
                        <li>Element IDs must match the <code class="bg-blue-100 px-1 rounded">data-cms-id</code> attributes in your HTML</li>
                        <li>Changes are saved immediately and will appear on your website</li>
                        <li>Use "View Site" to preview your changes in real-time</li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- Add New Content Form -->
        <div class="bg-white rounded-lg shadow-md p-6 mb-8">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <h2 class="text-xl font-bold text-gray-900">Add New Content</h2>
                    <p class="text-sm text-gray-600 mt-1">Create new editable content elements for your website</p>
                </div>
                <button type="button" onclick="toggleAddForm()" 
                    class="px-4 py-2 text-gray-600 hover:text-gray-900 border border-gray-300 rounded-lg hover:bg-gray-50">
                    <span id="toggleFormText">Collapse</span>
                </button>
            </div>
            <div id="addFormContainer">
            <form method="POST" action="" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <input type="hidden" name="action" value="add">
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Element ID (unique identifier)</label>
                    <input type="text" name="element_id" required 
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                        placeholder="e.g., new-section-heading">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Element Type</label>
                    <select name="element_type" required 
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        <option value="title">Title</option>
                        <option value="h1">Heading 1</option>
                        <option value="h2">Heading 2</option>
                        <option value="h3">Heading 3</option>
                        <option value="h4">Heading 4</option>
                        <option value="h5">Heading 5</option>
                        <option value="h6">Heading 6</option>
                        <option value="p">Paragraph</option>
                    </select>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Section</label>
                    <select name="section" 
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        <option value="">No Section</option>
                        <option value="hero">Hero Section</option>
                        <option value="online-brochure">Brochure Section</option>
                        <option value="what-we-offer-part-a">What We Offer Section - Part A</option>
                        <option value="video-booths">Video Booths Section</option>
                        <option value="additional-services">Additional Services Section</option>
                        <option value="event-types">Event Types Section</option>
                        <option value="rentals">Rentals Section</option>
                        <option value="galleries">Explore Our Work Section</option>
                        <option value="about">About MiHi Section</option>
                        <option value="reviews">What Our Clients Say Section</option>
                        <option value="locations">Locations Section</option>
                        <option value="contact">Contact/CTA Section</option>
                    </select>
                    <p class="mt-1 text-xs text-gray-500">Or type a custom section name</p>
                    <input type="text" name="section_custom" 
                        class="mt-2 w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                        placeholder="Custom section name (optional)">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Page</label>
                    <input type="text" name="page" value="index" required 
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                </div>
                
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Content</label>
                    <textarea name="content" required rows="3"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                        placeholder="Enter the content text"></textarea>
                </div>
                
                <div class="md:col-span-2">
                    <button type="submit" 
                        class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition font-medium">
                        Add Content
                    </button>
                </div>
            </form>
            </div>
        </div>

        <!-- Hero Background Media Management -->
        <div class="bg-white rounded-lg shadow-md p-6 mb-8">
            <div class="mb-4 flex items-center justify-between" id="heroMediaBadgeContainer">
                <div class="inline-flex items-center gap-2 px-4 py-2 bg-blue-100 text-blue-800 rounded-full text-base font-bold border-2 border-blue-300 shadow-sm" id="heroMediaBadge">
                    <span>Media CMS</span>
                    <span id="heroMediaBadgeSeparator" class="text-blue-600">></span>
                    <span id="heroMediaBadgeText">Manage Media by Section</span>
                </div>
                <button type="button" onclick="toggleHeroMedia()" 
                    class="px-4 py-2 text-gray-600 hover:text-gray-900 border border-gray-300 rounded-lg hover:bg-gray-50">
                    <span id="toggleHeroMediaText">Collapse</span>
                </button>
            </div>
            <div id="heroMediaContainer">
                <!-- Hero Section Dropdown -->
                <div class="mb-4 border border-gray-200 rounded-lg overflow-hidden">
                    <button type="button" onclick="toggleHeroSection()" 
                        class="w-full flex items-center justify-between px-4 py-3 bg-blue-50 hover:bg-blue-100 transition-colors text-left">
                        <div class="flex items-center gap-3">
                            <svg id="heroSectionIcon" class="w-5 h-5 text-blue-600 transform transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                            </svg>
                            <span class="font-semibold text-gray-900">Hero Section</span>
                        </div>
                        <span class="text-sm text-gray-600">Background Media</span>
                    </button>
                    <div id="heroSectionContent" class="hidden border-t border-gray-200 bg-white">
                        <div class="p-6">
                            <?php
                            // Get current hero background media
                            $hero_media_stmt = $conn->prepare("SELECT element_type, content FROM content_elements WHERE element_id = 'hero-background-media' AND page = 'index'");
                            $hero_media_stmt->execute();
                            $hero_media_result = $hero_media_stmt->get_result();
                            $current_hero_media = $hero_media_result->fetch_assoc();
                            $hero_media_stmt->close();
                            ?>
                            
                            <?php if ($current_hero_media): 
                                // Parse media type and path from content (format: "IMAGE:path" or "VIDEO:path")
                                $media_content = $current_hero_media['content'];
                                $media_type = 'image'; // default
                                $media_path = $media_content;
                                
                                if (strpos($media_content, ':') !== false) {
                                    $parts = explode(':', $media_content, 2);
                                    $media_type = strtolower($parts[0]);
                                    $media_path = $parts[1];
                                }
                            ?>
                                <div class="mb-6 p-4 bg-gray-50 rounded-lg border border-gray-200">
                                    <h3 class="text-sm font-semibold text-gray-700 mb-2">Current Background Media</h3>
                                    <div class="flex items-center gap-4">
                                        <?php if ($media_type === 'video'): ?>
                                            <video src="<?php echo htmlspecialchars($media_path); ?>" class="w-48 h-32 object-cover rounded-lg" controls></video>
                                        <?php else: ?>
                                            <img src="<?php echo htmlspecialchars($media_path); ?>" alt="Hero background" class="w-48 h-32 object-cover rounded-lg">
                                        <?php endif; ?>
                                        <div class="flex-1">
                                            <p class="text-sm text-gray-600">
                                                <span class="font-medium">Type:</span> <?php echo htmlspecialchars(ucfirst($media_type)); ?>
                                            </p>
                                            <p class="text-sm text-gray-600">
                                                <span class="font-medium">Path:</span> <code class="text-xs bg-gray-200 px-2 py-1 rounded"><?php echo htmlspecialchars($media_path); ?></code>
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            <?php else: ?>
                                <div class="mb-6 p-4 bg-yellow-50 rounded-lg border border-yellow-200">
                                    <p class="text-sm text-yellow-800">No background media set. Upload an image or video below.</p>
                                </div>
                            <?php endif; ?>
                            
                            <form method="POST" action="" enctype="multipart/form-data" class="space-y-4">
                                <input type="hidden" name="action" value="upload_hero_media">
                                
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Upload Image or Video</label>
                                    <input type="file" name="hero_media" accept="image/*,video/*" required
                                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                    <p class="mt-1 text-xs text-gray-500">
                                        Supported formats: Images (JPG, PNG, GIF, WebP) or Videos (MP4, WebM, OGG). Max file size: 100MB
                                    </p>
                                    <?php
                                    // Show PHP upload limits
                                    $upload_max = ini_get('upload_max_filesize');
                                    $post_max = ini_get('post_max_size');
                                    
                                    $upload_max_bytes = convertToBytes($upload_max);
                                    $post_max_bytes = convertToBytes($post_max);
                                    $max_size_bytes = min($upload_max_bytes, $post_max_bytes);
                                    $max_size_mb = round($max_size_bytes / 1024 / 1024, 2);
                                    ?>
                                    <p class="mt-1 text-xs <?php echo $max_size_mb < 50 ? 'text-red-600' : 'text-orange-600'; ?>">
                                        <strong>Current PHP Limits:</strong> Upload Max: <?php echo $upload_max; ?> | Post Max: <?php echo $post_max; ?>
                                        <?php if ($max_size_mb < 50): ?>
                                            <br><strong class="text-red-700">⚠️ Your PHP settings limit uploads to <?php echo $max_size_mb; ?>MB.</strong>
                                            <br>To upload larger files, edit <code>php.ini</code> in XAMPP:
                                            <br>1. Open XAMPP Control Panel → Click "Config" next to Apache → Select "PHP (php.ini)"
                                            <br>2. Find and change:
                                            <br>&nbsp;&nbsp;&nbsp;&nbsp;<code>upload_max_filesize = 100M</code>
                                            <br>&nbsp;&nbsp;&nbsp;&nbsp;<code>post_max_size = 100M</code>
                                            <br>3. Restart Apache in XAMPP Control Panel
                                        <?php endif; ?>
                                    </p>
                                </div>
                                
                                <div>
                                    <button type="submit" 
                                        class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition font-medium">
                                        <?php echo $current_hero_media ? 'Update Background Media' : 'Upload Background Media'; ?>
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                
                <!-- Brochure Section Dropdown -->
                <div class="mb-4 border border-gray-200 rounded-lg overflow-hidden">
                    <button type="button" onclick="toggleBrochureSection()" 
                        class="w-full flex items-center justify-between px-4 py-3 bg-blue-50 hover:bg-blue-100 transition-colors text-left">
                        <div class="flex items-center gap-3">
                            <svg id="brochureSectionIcon" class="w-5 h-5 text-blue-600 transform transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                            </svg>
                            <span class="font-semibold text-gray-900">Brochure Section</span>
                        </div>
                        <span class="text-sm text-gray-600">PDF Document</span>
                    </button>
                    <div id="brochureSectionContent" class="hidden border-t border-gray-200 bg-white">
                        <div class="p-6">
                            <?php
                            // Get current brochure PDF
                            $brochure_pdf_stmt = $conn->prepare("SELECT element_type, content FROM content_elements WHERE element_id = 'brochure-pdf' AND page = 'index'");
                            $brochure_pdf_stmt->execute();
                            $brochure_pdf_result = $brochure_pdf_stmt->get_result();
                            $current_brochure_pdf = $brochure_pdf_result->fetch_assoc();
                            $brochure_pdf_stmt->close();
                            ?>
                            
                            <?php if ($current_brochure_pdf): 
                                // Parse PDF path from content (format: "PDF:path")
                                $pdf_content = $current_brochure_pdf['content'];
                                $pdf_path = $pdf_content;
                                
                                if (strpos($pdf_content, ':') !== false) {
                                    $parts = explode(':', $pdf_content, 2);
                                    $pdf_path = $parts[1];
                                }
                            ?>
                                <div class="mb-6 p-4 bg-gray-50 rounded-lg border border-gray-200">
                                    <h3 class="text-sm font-semibold text-gray-700 mb-2">Current Brochure PDF</h3>
                                    <div class="flex items-center gap-4">
                                        <div class="flex items-center justify-center w-48 h-32 bg-red-50 border-2 border-red-200 rounded-lg">
                                            <div class="text-center">
                                                <svg class="w-12 h-12 text-red-500 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
                                                </svg>
                                                <p class="text-xs text-red-700 font-medium">PDF Document</p>
                                            </div>
                                        </div>
                                        <div class="flex-1">
                                            <p class="text-sm text-gray-600 mb-2">
                                                <span class="font-medium">File:</span> <?php echo htmlspecialchars(basename($pdf_path)); ?>
                                            </p>
                                            <p class="text-sm text-gray-600 mb-3">
                                                <span class="font-medium">Path:</span> <code class="text-xs bg-gray-200 px-2 py-1 rounded"><?php echo htmlspecialchars($pdf_path); ?></code>
                                            </p>
                                            <a href="<?php echo htmlspecialchars($pdf_path); ?>" target="_blank" 
                                                class="inline-flex items-center px-3 py-1.5 bg-blue-600 text-white text-xs font-medium rounded hover:bg-blue-700 transition">
                                                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                                </svg>
                                                View PDF
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            <?php else: ?>
                                <div class="mb-6 p-4 bg-yellow-50 rounded-lg border border-yellow-200">
                                    <p class="text-sm text-yellow-800">No brochure PDF set. Upload a PDF file below.</p>
                                </div>
                            <?php endif; ?>
                            
                            <form method="POST" action="" enctype="multipart/form-data" class="space-y-4">
                                <input type="hidden" name="action" value="upload_brochure_pdf">
                                
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Upload PDF Document</label>
                                    <input type="file" name="brochure_pdf" accept=".pdf,application/pdf" required
                                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                    <p class="mt-1 text-xs text-gray-500">
                                        Supported format: PDF only. Max file size: 100MB
                                    </p>
                                    <?php
                                    // Show PHP upload limits (reuse the same function)
                                    $upload_max = ini_get('upload_max_filesize');
                                    $post_max = ini_get('post_max_size');
                                    
                                    $upload_max_bytes = convertToBytes($upload_max);
                                    $post_max_bytes = convertToBytes($post_max);
                                    $max_size_bytes = min($upload_max_bytes, $post_max_bytes);
                                    $max_size_mb = round($max_size_bytes / 1024 / 1024, 2);
                                    ?>
                                    <p class="mt-1 text-xs <?php echo $max_size_mb < 50 ? 'text-red-600' : 'text-orange-600'; ?>">
                                        <strong>Current PHP Limits:</strong> Upload Max: <?php echo $upload_max; ?> | Post Max: <?php echo $post_max; ?>
                                        <?php if ($max_size_mb < 50): ?>
                                            <br><strong class="text-red-700">⚠️ Your PHP settings limit uploads to <?php echo $max_size_mb; ?>MB.</strong>
                                            <br>To upload larger files, edit <code>php.ini</code> in XAMPP:
                                            <br>1. Open XAMPP Control Panel → Click "Config" next to Apache → Select "PHP (php.ini)"
                                            <br>2. Find and change:
                                            <br>&nbsp;&nbsp;&nbsp;&nbsp;<code>upload_max_filesize = 100M</code>
                                            <br>&nbsp;&nbsp;&nbsp;&nbsp;<code>post_max_size = 100M</code>
                                            <br>3. Restart Apache in XAMPP Control Panel
                                        <?php endif; ?>
                                    </p>
                                </div>
                                
                                <div>
                                    <button type="submit" 
                                        class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition font-medium">
                                        <?php echo $current_brochure_pdf ? 'Update Brochure PDF' : 'Upload Brochure PDF'; ?>
                                    </button>
                                </div>
                            </form>
                            
                            <!-- Brochure Thumbnail Section -->
                            <div class="mt-8 pt-8 border-t border-gray-300">
                                <h3 class="text-lg font-semibold text-gray-900 mb-4">Brochure Thumbnail</h3>
                                
                                <?php
                                // Get current brochure thumbnail
                                $brochure_thumbnail_stmt = $conn->prepare("SELECT element_type, content FROM content_elements WHERE element_id = 'brochure-thumbnail' AND page = 'index'");
                                $brochure_thumbnail_stmt->execute();
                                $brochure_thumbnail_result = $brochure_thumbnail_stmt->get_result();
                                $current_brochure_thumbnail = $brochure_thumbnail_result->fetch_assoc();
                                $brochure_thumbnail_stmt->close();
                                ?>
                                
                                <?php if ($current_brochure_thumbnail): 
                                    // Parse image path from content (format: "IMAGE:path")
                                    $thumbnail_content = $current_brochure_thumbnail['content'];
                                    $thumbnail_path = $thumbnail_content;
                                    
                                    if (strpos($thumbnail_content, ':') !== false) {
                                        $parts = explode(':', $thumbnail_content, 2);
                                        $thumbnail_path = $parts[1];
                                    }
                                ?>
                                    <div class="mb-6 p-4 bg-gray-50 rounded-lg border border-gray-200">
                                        <h4 class="text-sm font-semibold text-gray-700 mb-2">Current Thumbnail</h4>
                                        <div class="flex items-center gap-4">
                                            <img src="<?php echo htmlspecialchars($thumbnail_path); ?>" 
                                                alt="Brochure thumbnail" 
                                                class="w-48 h-32 object-cover rounded-lg border border-gray-300">
                                            <div class="flex-1">
                                                <p class="text-sm text-gray-600 mb-2">
                                                    <span class="font-medium">File:</span> <?php echo htmlspecialchars(basename($thumbnail_path)); ?>
                                                </p>
                                                <p class="text-sm text-gray-600 mb-3">
                                                    <span class="font-medium">Path:</span> <code class="text-xs bg-gray-200 px-2 py-1 rounded"><?php echo htmlspecialchars($thumbnail_path); ?></code>
                                                </p>
                                                <a href="<?php echo htmlspecialchars($thumbnail_path); ?>" target="_blank" 
                                                    class="inline-flex items-center px-3 py-1.5 bg-blue-600 text-white text-xs font-medium rounded hover:bg-blue-700 transition">
                                                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                                    </svg>
                                                    View Image
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                <?php else: ?>
                                    <div class="mb-6 p-4 bg-yellow-50 rounded-lg border border-yellow-200">
                                        <p class="text-sm text-yellow-800">No thumbnail image set. Upload an image below.</p>
                                    </div>
                                <?php endif; ?>
                                
                                <form method="POST" action="" enctype="multipart/form-data" class="space-y-4">
                                    <input type="hidden" name="action" value="upload_brochure_thumbnail">
                                    
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Upload Thumbnail Image</label>
                                        <input type="file" name="brochure_thumbnail" accept="image/*" required
                                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                        <p class="mt-1 text-xs text-gray-500">
                                            Supported formats: JPG, PNG, GIF, WebP. Max file size: 10MB
                                        </p>
                                        <?php
                                        // Show PHP upload limits
                                        $upload_max = ini_get('upload_max_filesize');
                                        $post_max = ini_get('post_max_size');
                                        
                                        $upload_max_bytes = convertToBytes($upload_max);
                                        $post_max_bytes = convertToBytes($post_max);
                                        $max_size_bytes = min($upload_max_bytes, $post_max_bytes);
                                        $max_size_mb = round($max_size_bytes / 1024 / 1024, 2);
                                        ?>
                                        <p class="mt-1 text-xs <?php echo $max_size_mb < 10 ? 'text-red-600' : 'text-orange-600'; ?>">
                                            <strong>Current PHP Limits:</strong> Upload Max: <?php echo $upload_max; ?> | Post Max: <?php echo $post_max; ?>
                                            <?php if ($max_size_mb < 10): ?>
                                                <br><strong class="text-red-700">⚠️ Your PHP settings limit uploads to <?php echo $max_size_mb; ?>MB.</strong>
                                                <br>To upload larger files, edit <code>php.ini</code> in WAMP:
                                                <br>1. Click WAMP icon → PHP → php.ini
                                                <br>2. Find and change:
                                                <br>&nbsp;&nbsp;&nbsp;&nbsp;<code>upload_max_filesize = 100M</code>
                                                <br>&nbsp;&nbsp;&nbsp;&nbsp;<code>post_max_size = 100M</code>
                                                <br>3. Restart Apache in WAMP
                                            <?php endif; ?>
                                        </p>
                                    </div>
                                    
                                    <div>
                                        <button type="submit" 
                                            class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition font-medium">
                                            <?php echo $current_brochure_thumbnail ? 'Update Thumbnail' : 'Upload Thumbnail'; ?>
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Content List -->
        <div class="bg-white rounded-lg shadow-md p-6">
            <div class="mb-4 flex items-center justify-between" id="contentListBadgeContainer">
                <div class="inline-flex items-center gap-2 px-4 py-2 bg-green-100 text-green-800 rounded-full text-base font-bold border-2 border-green-300 shadow-sm" id="contentListBadge">
                    <span>Content CMS</span>
                    <span id="contentListBadgeSeparator" class="text-green-600">></span>
                    <span id="contentListBadgeText">Manage Content by Section</span>
                </div>
                <button type="button" onclick="toggleContentList()" 
                    class="px-4 py-2 text-gray-600 hover:text-gray-900 border border-gray-300 rounded-lg hover:bg-gray-50 whitespace-nowrap">
                    <span id="toggleContentListText">Collapse</span>
                </button>
            </div>
            <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 mb-6" id="contentListHeader">
                <div>
                    <h2 class="text-xl font-bold text-gray-900">Manage Content by Section</h2>
                    <p class="text-sm text-gray-600 mt-1">Edit, add, or delete content elements from your website</p>
                </div>
                <div class="flex items-center gap-3 w-full md:w-auto">
                    <label class="text-sm font-medium text-gray-700 whitespace-nowrap">Filter by Section:</label>
                    <select onchange="window.location.href='?section=' + this.value" 
                        class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent w-full md:w-auto">
                        <option value="all" <?php echo ($selected_section === 'all') ? 'selected' : ''; ?>>All Sections</option>
                        <option value="hero" <?php echo ($selected_section === 'hero') ? 'selected' : ''; ?>>Hero Section</option>
                        <option value="online-brochure" <?php echo ($selected_section === 'online-brochure') ? 'selected' : ''; ?>>Brochure Section</option>
                        <option value="what-we-offer-part-a" <?php echo ($selected_section === 'what-we-offer-part-a') ? 'selected' : ''; ?>>What We Offer Section - Part A</option>
                        <option value="video-booths" <?php echo ($selected_section === 'video-booths') ? 'selected' : ''; ?>>Video Booths Section</option>
                        <option value="additional-services" <?php echo ($selected_section === 'additional-services') ? 'selected' : ''; ?>>Additional Services Section</option>
                        <option value="event-types" <?php echo ($selected_section === 'event-types') ? 'selected' : ''; ?>>Event Types Section</option>
                        <option value="rentals" <?php echo ($selected_section === 'rentals') ? 'selected' : ''; ?>>Rentals Section</option>
                        <option value="galleries" <?php echo ($selected_section === 'galleries') ? 'selected' : ''; ?>>Explore Our Work Section</option>
                        <option value="about" <?php echo ($selected_section === 'about') ? 'selected' : ''; ?>>About MiHi Section</option>
                        <option value="reviews" <?php echo ($selected_section === 'reviews') ? 'selected' : ''; ?>>What Our Clients Say Section</option>
                        <option value="locations" <?php echo ($selected_section === 'locations') ? 'selected' : ''; ?>>Locations Section</option>
                        <option value="contact" <?php echo ($selected_section === 'contact') ? 'selected' : ''; ?>>Contact/CTA Section</option>
                        <option value="none" <?php echo ($selected_section === 'none') ? 'selected' : ''; ?>>No Section</option>
                    </select>
                </div>
            </div>
            <div id="contentListContainer">
            <?php
            // Initialize grouped array for stats (must be before stats section)
            $grouped = [];
            foreach ($content_elements as $element) {
                $section_key = !empty($element['section']) ? $element['section'] : 'none';
                if (!isset($grouped[$section_key])) {
                    $grouped[$section_key] = [];
                }
                $grouped[$section_key][] = $element;
            }
            
            // Get unique element types (PHP 5.4 compatible - array_column not available)
            $element_types = [];
            foreach ($content_elements as $element) {
                if (!empty($element['element_type'])) {
                    $element_types[$element['element_type']] = true;
                }
            }
            $unique_types_count = count($element_types);
            ?>
            
            <!-- Quick Stats -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
                <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                    <div class="text-2xl font-bold text-blue-900"><?php echo count($content_elements); ?></div>
                    <div class="text-sm text-blue-700">Total Elements</div>
                </div>
                <div class="bg-green-50 border border-green-200 rounded-lg p-4">
                    <div class="text-2xl font-bold text-green-900"><?php echo count($grouped); ?></div>
                    <div class="text-sm text-green-700">Sections</div>
                </div>
                <div class="bg-purple-50 border border-purple-200 rounded-lg p-4">
                    <div class="text-2xl font-bold text-purple-900"><?php echo $unique_types_count; ?></div>
                    <div class="text-sm text-purple-700">Content Types</div>
                </div>
                <div class="bg-orange-50 border border-orange-200 rounded-lg p-4">
                    <div class="text-2xl font-bold text-orange-900"><?php echo $selected_section === 'all' ? 'All' : ucfirst($selected_section); ?></div>
                    <div class="text-sm text-orange-700">Current Filter</div>
                </div>
            </div>
            
            <?php
            
            // Section descriptions for better UX
            $section_descriptions = array(
                'hero' => 'Hero section with main heading, subtitle, and call-to-action buttons. Includes: title line 1, main title, subtitle paragraph, primary CTA button, call button, and video fallback text.',
                'online-brochure' => 'Brochure Section: Interactive brochure showcasing photo booth products. Includes: lookbook badge, story lab badge, main heading, description paragraph, 3 feature titles and descriptions, 2 CTA buttons, and 4 tag labels.',
                'what-we-offer-part-a' => 'What We Offer Section - Part A: Main section header and AI Photo Booth feature. Includes: section badge, main heading, description, AI booth badge, heading, description, 3 feature titles and descriptions, 2 CTA buttons, and stat card (number and label).',
                'video-booths' => 'Video Booths Section: Showcases video booth experiences. Includes: section badge, heading, description, 360° video booth (heading, desc, button), 3 feature cards (badges, titles, descriptions), 5 video booth types (bullet-time, glambot, vogue, slow-mo, testimonial) each with heading, description, and button, plus "See More" button.',
                'additional-services' => 'Additional Services Section: Professional services to complete the event experience. Includes: section heading, description, Event Photography (badge, heading, desc, button), SketchBot (badge, heading, desc, button), Cookie Printer (heading, desc, button), Signature Pose Cards (heading, desc, button), and Lux Photography (heading, desc, button).',
                'event-types' => 'Event Types Section: Showcases different event categories. Includes: section badge, heading, description, and 6 event type cards (Wedding, Corporate, Social, Trade Shows, Holiday Parties, Casino Parties) each with badge, heading, and description.',
                'rentals' => 'Rentals Section: Complete event solutions including AV services, decor, and games. Includes: section badge, heading, description, AV Services card (badge, heading, desc, 5 service links, button), Event Decor card (badge, heading, desc, 5 decor items, button), and Game Rentals card (badge, heading, desc, 4 game types, button).',
                'galleries' => 'Explore Our Work Section: Portfolio showcase section. Includes: badge text, main heading, description paragraph, and 5 gallery cards (Our Work, Our Services, Our Booths, Our Props, Our Themes) each with heading and description.',
                'about' => 'About MiHi Section: Company information and resources. Includes: badge, main heading, description paragraph with highlight, and 5 feature cards (Read our Blogs, Our Locations, Case Studies, FAQ, About MiHi) each with heading, description, and button.',
                'reviews' => 'What Our Clients Say Section: Client testimonials and reviews. Includes: badge text, heading, description, rating text, button text, and 4 trust indicators (Verified Reviews, Real Experiences, Client Testimonials, Google Verified).',
                'locations' => 'Locations Section: Service areas and coverage information. Includes: badge text, heading, description, CTA text, and CTA button.',
                'contact' => 'Contact/CTA Section: Final call-to-action section. Includes: section badge, heading, description, 3 feature cards (Props, Lighting, Sharing) each with badge, heading, and description, 2 CTA buttons, and 4 trust indicators.'
            );
            ?>
            
            <?php 
            // When showing "All Sections", also include predefined sections that have no content yet
            if ($selected_section === 'all') {
                // Add empty predefined sections to grouped array so they show up
                foreach ($predefined_sections as $section_key => $section_label) {
                    if (!isset($grouped[$section_key])) {
                        $grouped[$section_key] = array();
                    }
                }
            } elseif ($selected_section !== 'none' && isset($predefined_sections[$selected_section])) {
                // If a specific predefined section is selected but has no content, add it to grouped
                if (!isset($grouped[$selected_section])) {
                    $grouped[$selected_section] = array();
                }
            }
            
            if (empty($grouped)): ?>
                <div class="text-center py-12">
                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    <h3 class="mt-2 text-sm font-medium text-gray-900">No content found</h3>
                    <p class="mt-1 text-sm text-gray-500">Get started by adding content above.</p>
                </div>
            <?php else: ?>
                <?php 
                // Sort sections: predefined sections first, then others
                $sorted_grouped = array();
                foreach ($predefined_sections as $section_key => $section_label) {
                    if (isset($grouped[$section_key])) {
                        $sorted_grouped[$section_key] = $grouped[$section_key];
                    }
                }
                // Add any other sections not in predefined list
                foreach ($grouped as $section_name => $section_elements) {
                    if (!isset($predefined_sections[$section_name])) {
                        $sorted_grouped[$section_name] = $section_elements;
                    }
                }
                
                foreach ($sorted_grouped as $section_name => $section_elements): 
                    // Special handling for section display names
                    if ($section_name === 'none' || empty($section_name)) {
                        $display_section = 'No Section';
                    } elseif ($section_name === 'hero') {
                        $display_section = 'Hero Section';
                    } elseif ($section_name === 'online-brochure') {
                        $display_section = 'Brochure Section';
                    } elseif ($section_name === 'what-we-offer-part-a') {
                        $display_section = 'What We Offer Section - Part A';
                    } elseif ($section_name === 'video-booths') {
                        $display_section = 'Video Booths Section';
                    } elseif ($section_name === 'additional-services') {
                        $display_section = 'Additional Services Section';
                    } elseif ($section_name === 'event-types') {
                        $display_section = 'Event Types Section';
                    } elseif ($section_name === 'rentals') {
                        $display_section = 'Rentals Section';
                    } elseif ($section_name === 'galleries') {
                        $display_section = 'Explore Our Work Section';
                    } elseif ($section_name === 'about') {
                        $display_section = 'About MiHi Section';
                    } elseif ($section_name === 'reviews') {
                        $display_section = 'What Our Clients Say Section';
                    } elseif ($section_name === 'locations') {
                        $display_section = 'Locations Section';
                    } elseif ($section_name === 'contact') {
                        $display_section = 'Contact/CTA Section';
                    } else {
                        $display_section = ucfirst(str_replace('-', ' ', $section_name));
                    }
                    $section_desc = isset($section_descriptions[$section_name]) ? $section_descriptions[$section_name] : '';
                ?>
                    <div class="mb-8 border border-gray-200 rounded-lg overflow-hidden">
                        <div class="bg-gradient-to-r from-blue-50 to-indigo-50 px-6 py-4 border-b border-gray-200">
                            <div class="flex items-center justify-between">
                                <div>
                                    <h3 class="text-lg font-semibold text-gray-900 flex items-center gap-2">
                                        <span><?php echo htmlspecialchars($display_section); ?></span>
                                        <span class="px-3 py-1 bg-blue-600 text-white rounded-full text-xs font-medium">
                                            <?php echo count($section_elements); ?> <?php echo count($section_elements) === 1 ? 'item' : 'items'; ?>
                                        </span>
                                    </h3>
                                    <?php if ($section_desc): ?>
                                        <p class="text-sm text-gray-600 mt-1"><?php echo htmlspecialchars($section_desc); ?></p>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Element ID</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Content Preview</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Last Updated</th>
                                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <?php if (empty($section_elements)): ?>
                                        <tr>
                                            <td colspan="5" class="px-6 py-12 text-center">
                                                <div class="flex flex-col items-center">
                                                    <svg class="w-12 h-12 text-gray-400 mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                                    </svg>
                                                    <p class="text-sm font-medium text-gray-900 mb-2">No content in this section yet</p>
                                                    <p class="text-sm text-gray-500 mb-4">Run the sync script to populate content for this section</p>
                                                    <?php 
                                                    $sync_script_map = array(
                                                        'video-booths' => 'sync_video_booths.php',
                                                        'additional-services' => 'sync_additional_services.php',
                                                        'event-types' => 'sync_event_types.php',
                                                        'rentals' => 'sync_rentals.php',
                                                        'galleries' => 'sync_galleries.php',
                                                        'about' => 'sync_about.php',
                                                        'reviews' => 'sync_reviews.php',
                                                        'locations' => 'sync_locations.php',
                                                        'contact' => 'sync_contact.php'
                                                    );
                                                    if (isset($sync_script_map[$section_name])): ?>
                                                        <a href="<?php echo htmlspecialchars($sync_script_map[$section_name]); ?>" 
                                                            class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition text-sm font-medium">
                                                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                                                            </svg>
                                                            Sync This Section
                                                        </a>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($section_elements as $element): ?>
                                            <tr class="hover:bg-blue-50 transition-colors">
                                                <td class="px-6 py-4 whitespace-nowrap">
                                                    <code class="px-2 py-1 bg-gray-100 rounded text-xs font-mono text-gray-900">
                                                        <?php echo htmlspecialchars($element['element_id']); ?>
                                                    </code>
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap">
                                                    <span class="px-2 py-1 bg-blue-100 text-blue-800 rounded-full text-xs font-medium">
                                                        <?php echo htmlspecialchars(ucfirst($element['element_type'])); ?>
                                                    </span>
                                                </td>
                                                <td class="px-6 py-4 text-sm text-gray-900 max-w-md">
                                                    <div class="truncate" title="<?php echo htmlspecialchars($element['content']); ?>">
                                                        <?php 
                                                        $preview = htmlspecialchars($element['content']);
                                                        echo strlen($preview) > 80 ? substr($preview, 0, 80) . '...' : $preview;
                                                        ?>
                                                    </div>
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                    <?php 
                                                    if (!empty($element['updated_at'])) {
                                                        $date = new DateTime($element['updated_at']);
                                                        echo $date->format('M d, Y');
                                                    } else {
                                                        echo '—';
                                                    }
                                                    ?>
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                                    <button onclick="editContent(<?php echo htmlspecialchars(json_encode($element)); ?>)" 
                                                        class="text-blue-600 hover:text-blue-900 mr-4 font-medium hover:underline">
                                                        Edit
                                                    </button>
                                                    <form method="POST" action="" class="inline" onsubmit="return confirm('Are you sure you want to delete this content? This action cannot be undone.');">
                                                        <input type="hidden" name="action" value="delete">
                                                        <input type="hidden" name="id" value="<?php echo $element['id']; ?>">
                                                        <button type="submit" class="text-red-600 hover:text-red-900 font-medium hover:underline">
                                                            Delete
                                                        </button>
                                                    </form>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Edit Modal -->
    <div id="editModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
        <div class="bg-white rounded-lg shadow-xl max-w-2xl w-full p-6 max-h-[90vh] overflow-y-auto">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-xl font-bold text-gray-900">Edit Content</h3>
                <button onclick="closeEditModal()" class="text-gray-400 hover:text-gray-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
            <form method="POST" action="" id="editForm">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="id" id="edit_id">
                
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Element ID</label>
                    <input type="text" id="edit_element_id" readonly
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg bg-gray-100">
                </div>
                
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Element Type</label>
                    <input type="text" id="edit_element_type" readonly
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg bg-gray-100">
                </div>
                
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Content</label>
                    <textarea name="content" id="edit_content" required rows="5"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"></textarea>
                </div>
                
                <div class="flex justify-end gap-4">
                    <button type="button" onclick="closeEditModal()" 
                        class="px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50">
                        Cancel
                    </button>
                    <button type="submit" 
                        class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition">
                        Save Changes
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function editContent(element) {
            document.getElementById('edit_id').value = element.id;
            document.getElementById('edit_element_id').value = element.element_id;
            document.getElementById('edit_element_type').value = element.element_type;
            document.getElementById('edit_content').value = element.content;
            document.getElementById('editModal').classList.remove('hidden');
            document.body.style.overflow = 'hidden';
        }

        function closeEditModal() {
            document.getElementById('editModal').classList.add('hidden');
            document.body.style.overflow = 'auto';
        }

        function toggleAddForm() {
            const container = document.getElementById('addFormContainer');
            const toggleText = document.getElementById('toggleFormText');
            if (container.style.display === 'none') {
                container.style.display = 'block';
                toggleText.textContent = 'Collapse';
            } else {
                container.style.display = 'none';
                toggleText.textContent = 'Expand';
            }
        }

        function toggleHeroMedia() {
            const container = document.getElementById('heroMediaContainer');
            const toggleText = document.getElementById('toggleHeroMediaText');
            const badgeSeparator = document.getElementById('heroMediaBadgeSeparator');
            const badgeText = document.getElementById('heroMediaBadgeText');
            const badge = document.getElementById('heroMediaBadge');
            const badgeContainer = document.getElementById('heroMediaBadgeContainer');
            
            if (container.style.display === 'none') {
                container.style.display = 'block';
                toggleText.textContent = 'Collapse';
                // Show full badge text and reduce emphasis
                if (badgeSeparator) badgeSeparator.style.display = 'inline';
                if (badgeText) badgeText.style.display = 'inline';
                if (badge) {
                    badge.classList.remove('px-6', 'py-3', 'text-lg', 'border-4', 'shadow-lg', 'bg-blue-200', 'border-blue-400');
                    badge.classList.add('px-4', 'py-2', 'text-base', 'border-2', 'shadow-sm', 'bg-blue-100', 'border-blue-300');
                }
                if (badgeContainer) {
                    badgeContainer.classList.remove('mb-6', 'py-4');
                    badgeContainer.classList.add('mb-4');
                }
            } else {
                container.style.display = 'none';
                toggleText.textContent = 'Expand';
                // Hide badge text, show only "Media CMS" with more emphasis
                if (badgeSeparator) badgeSeparator.style.display = 'none';
                if (badgeText) badgeText.style.display = 'none';
                if (badge) {
                    badge.classList.remove('px-4', 'py-2', 'text-base', 'border-2', 'shadow-sm', 'bg-blue-100', 'border-blue-300');
                    badge.classList.add('px-6', 'py-3', 'text-lg', 'border-4', 'shadow-lg', 'bg-blue-200', 'border-blue-400');
                }
                if (badgeContainer) {
                    badgeContainer.classList.remove('mb-4');
                    badgeContainer.classList.add('mb-6', 'py-4');
                }
            }
        }

        function toggleHeroSection() {
            const content = document.getElementById('heroSectionContent');
            const icon = document.getElementById('heroSectionIcon');
            
            if (content.classList.contains('hidden')) {
                content.classList.remove('hidden');
                if (icon) icon.style.transform = 'rotate(180deg)';
            } else {
                content.classList.add('hidden');
                if (icon) icon.style.transform = 'rotate(0deg)';
            }
        }

        function toggleBrochureSection() {
            const content = document.getElementById('brochureSectionContent');
            const icon = document.getElementById('brochureSectionIcon');
            
            if (content.classList.contains('hidden')) {
                content.classList.remove('hidden');
                if (icon) icon.style.transform = 'rotate(180deg)';
            } else {
                content.classList.add('hidden');
                if (icon) icon.style.transform = 'rotate(0deg)';
            }
        }

        function toggleContentList() {
            const container = document.getElementById('contentListContainer');
            const header = document.getElementById('contentListHeader');
            const toggleText = document.getElementById('toggleContentListText');
            const badgeSeparator = document.getElementById('contentListBadgeSeparator');
            const badgeText = document.getElementById('contentListBadgeText');
            const badge = document.getElementById('contentListBadge');
            const badgeContainer = document.getElementById('contentListBadgeContainer');
            
            if (container.style.display === 'none') {
                container.style.display = 'block';
                if (header) header.style.display = 'flex';
                toggleText.textContent = 'Collapse';
                // Show full badge text and reduce emphasis
                if (badgeSeparator) badgeSeparator.style.display = 'inline';
                if (badgeText) badgeText.style.display = 'inline';
                if (badge) {
                    badge.classList.remove('px-6', 'py-3', 'text-lg', 'border-4', 'shadow-lg', 'bg-green-200', 'border-green-400');
                    badge.classList.add('px-4', 'py-2', 'text-base', 'border-2', 'shadow-sm', 'bg-green-100', 'border-green-300');
                }
                if (badgeContainer) {
                    badgeContainer.classList.remove('mb-6', 'py-4');
                    badgeContainer.classList.add('mb-4');
                }
            } else {
                container.style.display = 'none';
                if (header) header.style.display = 'none';
                toggleText.textContent = 'Expand';
                // Hide badge text, show only "Content CMS" with more emphasis
                if (badgeSeparator) badgeSeparator.style.display = 'none';
                if (badgeText) badgeText.style.display = 'none';
                if (badge) {
                    badge.classList.remove('px-4', 'py-2', 'text-base', 'border-2', 'shadow-sm', 'bg-green-100', 'border-green-300');
                    badge.classList.add('px-6', 'py-3', 'text-lg', 'border-4', 'shadow-lg', 'bg-green-200', 'border-green-400');
                }
                if (badgeContainer) {
                    badgeContainer.classList.remove('mb-4');
                    badgeContainer.classList.add('mb-6', 'py-4');
                }
            }
        }

        // Close modal on outside click
        document.getElementById('editModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeEditModal();
            }
        });

        // Close modal on Escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeEditModal();
            }
        });

        // Auto-hide success/error messages after 5 seconds
        setTimeout(function() {
            const alerts = document.querySelectorAll('.bg-green-50, .bg-red-50');
            alerts.forEach(function(alert) {
                alert.style.transition = 'opacity 0.5s';
                alert.style.opacity = '0';
                setTimeout(function() {
                    alert.remove();
                }, 500);
            });
        }, 5000);
    </script>
</body>
</html>

<?php $conn->close(); ?>

