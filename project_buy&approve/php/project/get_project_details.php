<?php
// php/project/get_project_details.php
require_once '../config.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'You must be logged in.']);
    exit;
}

$projectId = $_GET['id'] ?? null;
if (!$projectId) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Project ID is required.']);
    exit;
}

$userId = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT * FROM projects WHERE id = ? AND user_id = ?");
$stmt->bind_param("ii", $projectId, $userId);
$stmt->execute();
$result = $stmt->get_result();

if ($project = $result->fetch_assoc()) {
    echo json_encode(['success' => true, 'project' => $project]);
} else {
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'Project not found or you do not have permission to edit it.']);
}
$stmt->close();
$conn->close();
?>