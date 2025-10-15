<?php
// php/project/get_my_projects.php
require_once '../config.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401); // Unauthorized
    echo json_encode(['success' => false, 'message' => 'You must be logged in.']);
    exit;
}

$user_id = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT id, title, subject, status, submitted_at, admin_review FROM projects WHERE user_id = ? ORDER BY submitted_at DESC");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

$projects = [];
while ($row = $result->fetch_assoc()) {
    $projects[] = $row;
}
echo json_encode(['success' => true, 'projects' => $projects]);
$stmt->close();
$conn->close();
?>