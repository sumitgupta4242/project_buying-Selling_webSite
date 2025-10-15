<?php
// php/project/upload.php
require_once '../config.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Authentication required.']);
    exit;
}

$target_dir = "../../uploads/";
if (!is_dir($target_dir)) {
    mkdir($target_dir, 0777, true);
}

function handle_upload($file_key, $target_dir) {
    if (!isset($_FILES[$file_key]) || $_FILES[$file_key]['error'] != 0) {
        $errorCode = $_FILES[$file_key]['error'];
        return ['success' => false, 'message' => "Error with file: {$file_key}."];
    }
    $file = $_FILES[$file_key];
    $file_extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $safe_filename = uniqid($file_key . '_', true) . '.' . $file_extension;
    $target_file = $target_dir . $safe_filename;

    if (move_uploaded_file($file['tmp_name'], $target_file)) {
        return ['success' => true, 'path' => "uploads/" . $safe_filename];
    }
    return ['success' => false, 'message' => "Failed to move uploaded file: {$file_key}."];
}

$zip_upload = handle_upload('zip-file', $target_dir);
if (!$zip_upload['success']) { echo json_encode($zip_upload); exit; }

$doc_upload = handle_upload('doc-file', $target_dir);
if (!$doc_upload['success']) { echo json_encode($doc_upload); exit; }

$run_file_path = null;
if (isset($_FILES['run-file']) && $_FILES['run-file']['error'] == 0) {
    $run_upload = handle_upload('run-file', $target_dir);
    if ($run_upload['success']) { $run_file_path = $run_upload['path']; }
}

$user_id = $_SESSION['user_id'];
$title = $_POST['project-title'];
$subject = $_POST['project-subject'];
$min_price = $_POST['min-price'];
$max_price = $_POST['max-price'];
$description = $_POST['project-description'];
$video_link = $_POST['video-link'] ?: null;
$how_to_run = $_POST['how-to-run'];
$seller_notes = $_POST['seller-notes'] ?: null;

$stmt = $conn->prepare("INSERT INTO projects (user_id, title, subject, min_price, max_price, description, video_link, zip_file_path, doc_file_path, how_to_run_text, run_file_path, seller_notes) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
$stmt->bind_param("issddsssssss", 
    $user_id, $title, $subject, $min_price, $max_price, $description, $video_link, 
    $zip_upload['path'], $doc_upload['path'], $how_to_run, $run_file_path, $seller_notes
);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Project submitted successfully!']);
} else {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $stmt->error]);
}
$stmt->close();
$conn->close();
?>