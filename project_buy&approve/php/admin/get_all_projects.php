<?php
// php/admin/get_all_projects.php
require_once '../config.php';

if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Access denied.']);
    exit;
}

$sql = "SELECT p.id, p.title, p.status, p.submitted_at, p.admin_review, u.name as seller_name 
        FROM projects p 
        JOIN users u ON p.user_id = u.id 
        ORDER BY p.submitted_at DESC";
$result = $conn->query($sql);

$projects = [];
while ($row = $result->fetch_assoc()) {
    $projects[] = $row;
}
echo json_encode(['success' => true, 'projects' => $projects]);
$conn->close();
?>