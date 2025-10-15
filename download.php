<?php
require('includes/db.php');

// 1. Check if a payment ID is provided
if (!isset($_GET['payment_id']) || empty($_GET['payment_id'])) {
    die("Error: No payment ID specified.");
}

// Check if the ZipArchive class exists
if (!class_exists('ZipArchive')) {
    die("Error: Server is missing the ZipArchive extension, which is required to bundle files. Please contact support.");
}

$paymentId = $_GET['payment_id'];

// 2. Verify the payment and get project details using a JOIN
// --- THIS IS THE CORRECTED QUERY ---
$sql = "
    SELECT
        o.project_id,
        p.title AS project_title
    FROM
        orders o
    JOIN
        projects p ON o.project_id = p.id
    WHERE
        o.razorpay_payment_id = ? AND o.status = 'success'
";
$stmt = $conn->prepare($sql);

// This is the line (18) that was causing the error. It will now work.
$stmt->bind_param("s", $paymentId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows !== 1) {
    die("Error: Invalid download link or payment not completed. Please contact support.");
}

$order = $result->fetch_assoc();
$projectId = $order['project_id'];
$projectTitle = !empty($order['project_title']) ? preg_replace('/[^a-zA-Z0-9_-]/', '', $order['project_title']) : 'ProjectFiles';
$zipFileName = $projectTitle . '.zip';

// 3. Get ALL project files from the 'project_files' table
$stmt = $conn->prepare("SELECT file_name FROM project_files WHERE project_id = ?");
$stmt->bind_param("i", $projectId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die("Error: No downloadable files were found for this project. Please contact support.");
}

$filesToZip = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();
$conn->close();

// 4. Create the ZIP archive
$zip = new ZipArchive();
$zipPath = tempnam(sys_get_temp_dir(), 'zip'); 

if ($zip->open($zipPath, ZipArchive::CREATE) !== TRUE) {
    die("Error: Could not create the ZIP file. Please contact support.");
}

$basePath = 'uploads/projects/';
foreach ($filesToZip as $file) {
    $filePath = $basePath . $file['file_name'];
    if (file_exists($filePath)) {
        $zip->addFile($filePath, basename($file['file_name']));
    }
}

$zip->close();

// 5. Check if the zip file was created and is not empty
if (file_exists($zipPath) && filesize($zipPath) > 0) {
    header('Content-Description: File Transfer');
    header('Content-Type: application/zip');
    header('Content-Disposition: attachment; filename="' . basename($zipFileName) . '"');
    header('Expires: 0');
    header('Cache-Control: must-revalidate');
    header('Pragma: public');
    header('Content-Length: ' . filesize($zipPath));
    
    ob_clean();
    flush();
    readfile($zipPath);
    
    unlink($zipPath);
    exit;
} else {
    if (file_exists($zipPath)) {
        unlink($zipPath);
    }
    die("Error: The final ZIP file could not be created or was empty. Please ensure project files exist and contact support.");
}
?>