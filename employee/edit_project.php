<?php
require_once '../includes/db.php';
require_once 'partials/header.php';

$errors = [];
$projectId = $_GET['id'] ?? null;
$employeeId = $_SESSION['employee_id'];

if (!$projectId) {
    header('Location: dashboard.php');
    exit;
}

// Fetch project details to ensure it belongs to the logged-in employee
$stmt = $conn->prepare("SELECT * FROM projects WHERE id = ? AND submitted_by_id = ?");
$stmt->bind_param("ii", $projectId, $employeeId);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows === 0) {
    // Project not found or doesn't belong to the user
    header('Location: dashboard.php');
    exit;
}
$project = $result->fetch_assoc();

// Fetch existing project files
$files_stmt = $conn->prepare("SELECT id, file_name FROM project_files WHERE project_id = ?");
$files_stmt->bind_param("i", $projectId);
$files_stmt->execute();
$existing_files = $files_stmt->get_result()->fetch_all(MYSQLI_ASSOC);


// Handle form submission for updating
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Handle file deletion
    if (isset($_POST['delete_files'])) {
        foreach ($_POST['delete_files'] as $file_id) {
            // Further security: double-check file belongs to project before deleting
            $file_id = intval($file_id);
            $del_stmt = $conn->prepare("DELETE FROM project_files WHERE id = ? AND project_id = ?");
            $del_stmt->bind_param("ii", $file_id, $projectId);
            $del_stmt->execute();
            // Optionally, delete the actual file from the server
        }
    }

    // Handle new file uploads
    if (isset($_FILES['project_files'])) {
        $target_dir_proj = "../uploads/projects/";
        foreach ($_FILES['project_files']['name'] as $key => $name) {
            if ($_FILES['project_files']['error'][$key] == 0) {
                $project_file_name = time() . '_' . basename($name);
                move_uploaded_file($_FILES["project_files"]["tmp_name"][$key], $target_dir_proj . $project_file_name);
                
                $insert_file_stmt = $conn->prepare("INSERT INTO project_files (project_id, file_name) VALUES (?, ?)");
                $insert_file_stmt->bind_param("is", $projectId, $project_file_name);
                $insert_file_stmt->execute();
            }
        }
    }

    // Update project details
    $title = trim($_POST['title']);
    $subject = trim($_POST['subject']);
    $description = trim($_POST['description']);
    $price = trim($_POST['price']);
    
    // After an edit, the status should go back to pending review
    $update_stmt = $conn->prepare("UPDATE projects SET title = ?, subject = ?, description = ?, price = ?, status = 'pending_approval' WHERE id = ? AND submitted_by_id = ?");
    $update_stmt->bind_param("sssisi", $title, $subject, $description, $price, $projectId, $employeeId);
    
    if ($update_stmt->execute()) {
        $_SESSION['message'] = "Project updated successfully and is pending review.";
        header('Location: dashboard.php');
        exit();
    } else {
        $errors[] = "Database error during project update.";
    }
}

?>
<h3>Edit Project Submission</h3>
<p class="text-muted">Update the details of your project below.</p>

<?php if (!empty($errors)): ?>
    <div class="alert alert-danger">
        <?php foreach ($errors as $error) echo "<p>$error</p>"; ?>
    </div>
<?php endif; ?>

<form action="edit_project.php?id=<?php echo $projectId; ?>" method="post" enctype="multipart/form-data" class="card p-4">
    <div class="mb-3">
        <label for="title" class="form-label">Project Title</label>
        <input type="text" name="title" class="form-control" value="<?php echo htmlspecialchars($project['title']); ?>" required>
    </div>
    <div class="mb-3">
        <label for="subject" class="form-label">Subject</label>
        <input type="text" name="subject" class="form-control" value="<?php echo htmlspecialchars($project['subject']); ?>" required>
    </div>
    <div class="mb-3">
        <label for="description" class="form-label">Description</label>
        <textarea name="description" class="form-control" rows="5" required><?php echo htmlspecialchars($project['description']); ?></textarea>
    </div>
    <div class="row">
        <div class="col-md-6 mb-3">
            <label for="price" class="form-label">Price (₹)</label>
            <input type="number" name="price" step="0.01" class="form-control" value="<?php echo htmlspecialchars($project['price']); ?>" required>
        </div>
    </div>

    <hr>
    
    <h5>Manage Project Files</h5>
    <div class="mb-3">
        <h6>Existing Files:</h6>
        <?php if (count($existing_files) > 0): ?>
            <?php foreach($existing_files as $file): ?>
            <div class="form-check">
                <input class="form-check-input" type="checkbox" name="delete_files[]" value="<?php echo $file['id']; ?>" id="file_<?php echo $file['id']; ?>">
                <label class="form-check-label" for="file_<?php echo $file['id']; ?>">
                    Delete - <?php echo htmlspecialchars($file['file_name']); ?>
                </label>
            </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p class="text-muted">No files currently uploaded for this project.</p>
        <?php endif; ?>
    </div>

    <div class="mb-3">
        <label for="project_files" class="form-label">Upload New Files</label>
        <input type="file" name="project_files[]" class="form-control" multiple>
        <div class="form-text">You can select multiple files to upload.</div>
    </div>
    
    <button type="submit" class="btn btn-primary">Update Project</button>
</form>

<?php require_once 'partials/footer.php'; ?>
