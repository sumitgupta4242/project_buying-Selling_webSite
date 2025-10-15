<?php
session_start();
require('includes/db.php');

header('Content-Type: application/json');

// Rule 1: User must be logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'You must be logged in to leave a review.']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$userId = $_SESSION['user_id'];
$projectId = $data['project_id'];
$rating = intval($data['rating']);
$reviewText = trim($data['review_text']);

if (empty($projectId) || empty($rating) || $rating < 1 || $rating > 5) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid data provided.']);
    exit;
}

// Rule 2: Verify the user has purchased this project
$stmt = $conn->prepare("SELECT id FROM orders WHERE user_id = ? AND project_id = ? AND status = 'success'");
$stmt->bind_param("ii", $userId, $projectId);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows === 0) {
    echo json_encode(['status' => 'error', 'message' => 'You can only review projects you have purchased.']);
    exit;
}
$stmt->close();

// Insert the review (or update if it exists, due to the UNIQUE KEY)
// "ON DUPLICATE KEY UPDATE" handles cases where a user edits their review
$stmt = $conn->prepare("
    INSERT INTO reviews (project_id, user_id, rating, review_text) 
    VALUES (?, ?, ?, ?)
    ON DUPLICATE KEY UPDATE rating = VALUES(rating), review_text = VALUES(review_text)
");
$stmt->bind_param("iiis", $projectId, $userId, $rating, $reviewText);

if ($stmt->execute()) {
    echo json_encode(['status' => 'success', 'message' => 'Thank you for your review!']);
} else {
    echo json_encode(['status' => 'error', 'message' => 'There was an error submitting your review.']);
}

$stmt->close();
$conn->close();
?>