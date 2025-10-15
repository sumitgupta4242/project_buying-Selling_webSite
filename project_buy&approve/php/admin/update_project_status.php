<?php
// php/admin/update_project_status.php
require_once '../config.php';

if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Access denied.']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$projectId = $data['projectId'] ?? null;
$status = $data['status'] ?? null;
$review = $data['review'] ?? null;

if (!$projectId || !$status) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Project ID and status are required.']);
    exit;
}

$stmt = $conn->prepare("UPDATE projects SET status = ?, admin_review = ? WHERE id = ?");
$stmt->bind_param("ssi", $status, $review, $projectId);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Project updated successfully.']);
} else {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Failed to update project.']);
}
$stmt->close();
$conn->close();
?>