<?php
require_once '../includes/db.php';
require_once 'partials/header.php'; // Assuming your admin panel has a similar header
?>
<style>
    .form-label,
    .form-text,
    h2, h4, h6,
    p.text-muted,
    strong {
        color: #212529 !important; /* Force dark text color to ensure visibility */
    }
    .card {
        background-color: #ffffff; /* This ensures the card background is light */
    }
</style>
<?php

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: manage_projects.php');
    exit();
}
$project_id = $_GET['id'];
$errors = [];

// --- Define upload directory once ---
$target_dir_proj = "../uploads/projects/";

// --- Begin Transaction on POST ---
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $conn->begin_transaction();
    try {
        // 1. Handle file deletions first
        if (isset($_POST['delete_files'])) {
            foreach ($_POST['delete_files'] as $file_id) {
                $file_id = intval($file_id);
                // Get filename to delete from server
                $file_del_stmt = $conn->prepare("SELECT file_name FROM project_files WHERE id = ? AND project_id = ?");
                $file_del_stmt->bind_param("ii", $file_id, $project_id);
                $file_del_stmt->execute();
                $file_to_delete_result = $file_del_stmt->get_result();
                
                if ($file_to_delete_result->num_rows > 0) {
                    $file_to_delete = $file_to_delete_result->fetch_assoc();
                    $file_path_to_delete = $target_dir_proj . $file_to_delete['file_name'];
                    
                    if (!empty($file_to_delete['file_name']) && file_exists($file_path_to_delete)) {
                        // --- REFINEMENT START ---
                        // Check if unlink succeeds. If not, throw an exception to trigger the rollback.
                        if (!unlink($file_path_to_delete)) {
                            throw new Exception("Could not delete file: " . htmlspecialchars($file_to_delete['file_name']) . ". Check server permissions.");
                        }
                        // --- REFINEMENT END ---
                    }

                    // Delete from database
                    $del_stmt = $conn->prepare("DELETE FROM project_files WHERE id = ? AND project_id = ?");
                    $del_stmt->bind_param("ii", $file_id, $project_id);
                    $del_stmt->execute();
                }
            }
        }

        // 2. Handle new file uploads
        if (isset($_FILES['project_files']) && !empty($_FILES['project_files']['name'][0])) {
            if (!is_dir($target_dir_proj)) {
                mkdir($target_dir_proj, 0755, true);
            }
            foreach ($_FILES['project_files']['name'] as $key => $name) {
                if ($_FILES['project_files']['error'][$key] == 0) {
                    // Create a more unique and safe filename
                    $file_extension = pathinfo($name, PATHINFO_EXTENSION);
                    $project_file_name = bin2hex(random_bytes(8)) . '_' . time() . '.' . $file_extension;
                    
                    if (!move_uploaded_file($_FILES["project_files"]["tmp_name"][$key], $target_dir_proj . $project_file_name)) {
                        throw new Exception("Failed to upload file: " . htmlspecialchars($name));
                    }

                    $insert_file_stmt = $conn->prepare("INSERT INTO project_files (project_id, file_name) VALUES (?, ?)");
                    $insert_file_stmt->bind_param("is", $project_id, $project_file_name);
                    $insert_file_stmt->execute();
                }
            }
        }

        // 3. Update main project details
        $title = trim($_POST['title']);
        $subject = trim($_POST['subject']);
        $description = trim($_POST['description']);
        $price = trim($_POST['price']);
        $status = trim($_POST['status']);
        $admin_review = trim($_POST['admin_review']);

        $stmt = $conn->prepare("UPDATE projects SET title = ?, subject = ?, description = ?, price = ?, status = ?, admin_review = ? WHERE id = ?");
        $stmt->bind_param("ssssssi", $title, $subject, $description, $price, $status, $admin_review, $project_id);
        $stmt->execute();

        $conn->commit();
        // It's good practice to start the session if you are using $_SESSION
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
        $_SESSION['message'] = "Project updated successfully!";
        header('Location: manage_projects.php');
        exit();

    } catch (Exception $e) {
        $conn->rollback();
        $errors[] = "An error occurred: " . $e->getMessage();
    }
}

// --- Fetch existing project data for the form ---
$stmt = $conn->prepare("
    SELECT p.*, e.name AS employee_name 
    FROM projects p
    LEFT JOIN employees e ON p.submitted_by_id = e.id
    WHERE p.id = ?
");
$stmt->bind_param("i", $project_id);
$stmt->execute();
$project_result = $stmt->get_result();
$project = $project_result->fetch_assoc();

if (!$project) {
    header('Location: manage_projects.php');
    exit();
}

// Fetch existing project files
$files_stmt = $conn->prepare("SELECT id, file_name FROM project_files WHERE project_id = ?");
$files_stmt->bind_param("i", $project_id);
$files_stmt->execute();
$existing_files = $files_stmt->get_result()->fetch_all(MYSQLI_ASSOC);

?>
<h2>Edit Project Submission</h2>
<p class="text-muted">Review, manage files, and update the status of this project.</p>
<?php if (!empty($errors)): ?>
    <div class="alert alert-danger"><?php foreach ($errors as $error) echo "<p class='mb-0'>".htmlspecialchars($error)."</p>"; ?></div>
<?php endif; ?>

<form action="edit_project.php?id=<?php echo $project_id; ?>" method="post" enctype="multipart/form-data" class="card p-4">
    <div class="card bg-light mb-4 border">
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <h6 class="card-title text-muted mb-1">Submitted By:</h6>
                    <p class="card-text fs-5"><?php echo htmlspecialchars($project['employee_name'] ?? 'N/A'); ?></p>
                </div>
                <div class="col-md-6">
                    <h6 class="card-title text-muted mb-1">Project Subject:</h6>
                    <p class="card-text fs-5"><?php echo htmlspecialchars($project['subject']); ?></p>
                </div>
            </div>
        </div>
    </div>
    <div class="mb-3">
        <label for="title" class="form-label">Project Title</label>
        <input type="text" class="form-control" id="title" name="title" value="<?php echo htmlspecialchars($project['title']); ?>" required>
    </div>
    <div class="mb-3">
        <label for="subject" class="form-label">Subject</label>
        <input type="text" class="form-control" id="subject" name="subject" value="<?php echo htmlspecialchars($project['subject']); ?>" required>
    </div>
    <div class="mb-3">
        <label for="description" class="form-label">Description</label>
        <textarea class="form-control" id="description" name="description" rows="5"><?php echo htmlspecialchars($project['description']); ?></textarea>
    </div>
    <div class="mb-3">
        <label for="price" class="form-label">Price (₹)</label>
        <input type="number" step="0.01" class="form-control" id="price" name="price" value="<?php echo htmlspecialchars($project['price']); ?>" required>
    </div>

    <?php if (!empty($project['employee_notes'])): ?>
    <div class="card mb-4">
        <div class="card-header">
            <strong>Notes from Employee</strong>
        </div>
        <div class="card-body bg-light">
            <p class="card-text" style="white-space: pre-wrap;"><?php echo htmlspecialchars($project['employee_notes']); ?></p>
        </div>
    </div>
    <?php endif; ?>

    <hr>

    <h4>File Management</h4>
    <div class="mb-3 p-3 bg-light border rounded">
        <h6>Existing Files (Select to delete):</h6>
        <?php if (count($existing_files) > 0): ?>
            <?php foreach ($existing_files as $file): ?>
            <div class="form-check">
                <input class="form-check-input" type="checkbox" name="delete_files[]" value="<?php echo $file['id']; ?>" id="file_<?php echo $file['id']; ?>">
                <label class="form-check-label" for="file_<?php echo $file['id']; ?>">
                    <a href="../uploads/projects/<?php echo htmlspecialchars($file['file_name']); ?>" target="_blank" class="text-decoration-none">
                        <?php echo htmlspecialchars($file['file_name']); ?>
                    </a>
                </label>
            </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p class="text-muted mb-0">No files currently uploaded for this project.</p>
        <?php endif; ?>
    </div>
    <div class="mb-3">
        <label for="project_files" class="form-label">Upload New Files (Optional)</label>
        <input type="file" name="project_files[]" class="form-control" multiple>
        <div class="form-text">You can add new files to this submission.</div>
    </div>

    <hr>

    <h4>Admin Review</h4>
    <div class="row">
        <div class="col-md-6 mb-3">
            <label for="status" class="form-label">Project Status</label>
            <select name="status" id="status" class="form-select" required>
                <option value="pending_approval" <?php echo ($project['status'] == 'pending_approval') ? 'selected' : ''; ?>>Pending Approval</option>
                <option value="published" <?php echo ($project['status'] == 'published') ? 'selected' : ''; ?>>Published</option>
                <option value="rejected" <?php echo ($project['status'] == 'rejected') ? 'selected' : ''; ?>>Rejected</option>
            </select>
        </div>
    </div>
    <div class="mb-3">
        <label for="admin_review" class="form-label">Feedback / Review Comments for Employee</label>
        <textarea name="admin_review" id="admin_review" class="form-control" rows="4"><?php echo htmlspecialchars($project['admin_review']); ?></textarea>
        <div class="form-text">This feedback will be visible to the employee.</div>
    </div>

    <button type="submit" class="btn btn-primary">Update Project</button>
</form>

<?php require_once 'partials/footer.php'; ?>