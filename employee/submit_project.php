<?php
require_once '../includes/db.php';
require_once 'partials/header.php';

$errors = [];
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $conn->begin_transaction();
    try {
        $title = trim($_POST['title']);
        $subject = trim($_POST['subject']);
        $description = trim($_POST['description']);
        // *** NEW: Get employee notes from form ***
        $employee_notes = trim($_POST['employee_notes']);
        $price = trim($_POST['price']);
        $employeeId = $_SESSION['employee_id'];

        $cover_image_name = null;
        if (isset($_FILES['cover_image']) && $_FILES['cover_image']['error'] == 0) {
            $target_dir_img = "../uploads/covers/";
            if (!is_dir($target_dir_img)) mkdir($target_dir_img, 0755, true);
            $cover_image_name = time() . '_cover_' . basename($_FILES["cover_image"]["name"]);
            move_uploaded_file($_FILES["cover_image"]["tmp_name"], $target_dir_img . $cover_image_name);
        }

        // *** UPDATED: Insert statement includes the new notes field ***
        $stmt = $conn->prepare("INSERT INTO projects (title, subject, description, employee_notes, price, cover_image, status, submitted_by_id) VALUES (?, ?, ?, ?, ?, ?, 'pending_approval', ?)");
        $stmt->bind_param("ssssdsi", $title, $subject, $description, $employee_notes, $price, $cover_image_name, $employeeId);
        $stmt->execute();
        $project_id = $conn->insert_id;

        if (isset($_FILES['project_files']) && !empty($_FILES['project_files']['name'][0])) {
            $target_dir_proj = "../uploads/projects/";
            if (!is_dir($target_dir_proj)) mkdir($target_dir_proj, 0755, true);
            foreach ($_FILES['project_files']['name'] as $key => $name) {
                if ($_FILES['project_files']['error'][$key] == 0) {
                    $project_file_name = time() . '_' . basename($name);
                    move_uploaded_file($_FILES["project_files"]["tmp_name"][$key], $target_dir_proj . $project_file_name);
                    $insert_file_stmt = $conn->prepare("INSERT INTO project_files (project_id, file_name) VALUES (?, ?)");
                    $insert_file_stmt->bind_param("is", $project_id, $project_file_name);
                    $insert_file_stmt->execute();
                }
            }
        } else {
            throw new Exception("At least one project file is required.");
        }

        $conn->commit();
        $_SESSION['message'] = "Project submitted for review successfully!";
        header('Location: dashboard.php');
        exit();
    } catch (Exception $e) {
        $conn->rollback();
        $errors[] = $e->getMessage();
    }
}
?>
<h3>Submit New Project for Review</h3>
<p class="text-muted">Fill out the details below. An administrator will review your submission before it is published.</p>
<?php if (!empty($errors)): ?>
    <div class="alert alert-danger"><?php foreach ($errors as $error) echo "<p class='mb-0'>$error</p>"; ?></div>
<?php endif; ?>

<form action="submit_project.php" method="post" enctype="multipart/form-data" class="card p-4">
    <div class="mb-3"><label for="title" class="form-label">Project Title</label><input type="text" name="title" class="form-control" required></div>
    <div class="mb-3"><label for="subject" class="form-label">Subject</label><input type="text" name="subject" class="form-control" required></div>
    <div class="mb-3"><label for="description" class="form-label">Description</label><textarea name="description" class="form-control" rows="5" required></textarea></div>
    
    <!-- *** NEW: Notes field for the employee *** -->
    <div class="mb-3">
        <label for="employee_notes" class="form-label">Notes for Admin (Optional)</label>
        <textarea name="employee_notes" class="form-control" rows="3" placeholder="Provide any specific instructions or comments for the reviewer..."></textarea>
    </div>

    <div class="row">
        <div class="col-md-6 mb-3"><label for="price" class="form-label">Price (₹)</label><input type="number" name="price" step="0.01" class="form-control" required></div>
        <div class="col-md-6 mb-3"><label for="cover_image" class="form-label">Cover Image (Optional)</label><input type="file" name="cover_image" class="form-control"></div>
    </div>
    <div class="mb-3">
        <label for="project_files" class="form-label">Project File(s)</label>
        <input type="file" name="project_files[]" class="form-control" required multiple>
        <div class="form-text">You can select multiple files by holding down Ctrl (or Cmd on Mac) and clicking.</div>
    </div>
    <button type="submit" class="btn btn-primary">Submit for Review</button>
</form>
<?php require_once 'partials/footer.php'; ?>

