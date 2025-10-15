<?php
// php/project/update_project.php
require_once '../config.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Authentication required.']);
    exit;
}

$projectId = $_POST['project-id'] ?? null;
if (!$projectId) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Project ID is missing.']);
    exit;
}

$userId = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT user_id FROM projects WHERE id = ?");
$stmt->bind_param("i", $projectId);
$stmt->execute();
$result = $stmt->get_result();
$project = $result->fetch_assoc();

if (!$project || $project['user_id'] != $userId) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'You do not have permission to update this project.']);
    exit;
}
$stmt->close();

$title = $_POST['project-title'];
$subject = $_POST['project-subject'];
$min_price = $_POST['min-price'];
$max_price = $_POST['max-price'];
$description = $_POST['project-description'];
$video_link = $_POST['video-link'] ?: null;
$how_to_run = $_POST['how-to-run'];
$seller_notes = $_POST['seller-notes'] ?: null;
$newStatus = 'pending'; 

$stmt = $conn->prepare(
    "UPDATE projects SET title = ?, subject = ?, min_price = ?, max_price = ?, description = ?, video_link = ?, how_to_run_text = ?, seller_notes = ?, status = ? WHERE id = ? AND user_id = ?"
);
$stmt->bind_param("ssddsssssii", 
    $title, $subject, $min_price, $max_price, $description, $video_link, 
    $how_to_run, $seller_notes, $newStatus, $projectId, $userId
);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Project updated successfully and re-submitted for review.']);
} else {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $stmt->error]);
}
$stmt->close();
$conn->close();
?>