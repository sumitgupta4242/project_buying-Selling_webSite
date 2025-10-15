<?php
require_once '../includes/db.php';

// Secure this page
if (!isset($_SESSION['admin_loggedin']) || $_SESSION['admin_loggedin'] !== true) {
    header('Location: index.php');
    exit;
}

if (!isset($_GET['id']) || empty($_GET['id'])) {
    header('Location: dashboard.php');
    exit();
}

$project_id = $_GET['id'];

// First, get the file path to delete the actual file
$stmt = $conn->prepare("SELECT file_path FROM projects WHERE id = ?");
$stmt->bind_param("i", $project_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows === 1) {
    $project = $result->fetch_assoc();
    $file_to_delete = '../uploads/' . $project['file_path'];
    if (file_exists($file_to_delete)) {
        unlink($file_to_delete); // Delete the file
    }
}
$stmt->close();

// Now delete the record from the database
$stmt = $conn->prepare("DELETE FROM projects WHERE id = ?");
$stmt->bind_param("i", $project_id);

if ($stmt->execute()) {
    $_SESSION['message'] = "Project deleted successfully!";
} else {
    $_SESSION['message'] = "Error deleting project."; // You might want a different alert class for errors
}

$stmt->close();
$conn->close();

header('Location: manage_projects.php');
exit();
?>