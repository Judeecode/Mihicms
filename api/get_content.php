<?php
require_once '../cms/config.php';

header('Content-Type: application/json');

$page = isset($_GET['page']) ? $_GET['page'] : 'index';
$section = isset($_GET['section']) ? $_GET['section'] : null;

$conn = getDBConnection();

$query = "SELECT element_id, element_type, content, section FROM content_elements WHERE page = ?";
$params = [$page];
$types = "s";

if ($section !== null) {
    $query .= " AND section = ?";
    $params[] = $section;
    $types .= "s";
}

$query .= " ORDER BY section, element_type, id";

$stmt = $conn->prepare($query);
if ($section !== null) {
    $stmt->bind_param($types, $page, $section);
} else {
    $stmt->bind_param($types, $page);
}

$stmt->execute();
$result = $stmt->get_result();

$content = [];
while ($row = $result->fetch_assoc()) {
    $content[$row['element_id']] = [
        'type' => $row['element_type'],
        'content' => $row['content'],
        'section' => $row['section']
    ];
}

$stmt->close();
$conn->close();

echo json_encode($content);
?>

