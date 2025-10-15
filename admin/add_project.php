<?php
require_once '../includes/db.php';
require_once 'partials/header.php';

$title = $subject = $description = $price = '';
$errors = [];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $title = trim($_POST['title']);
    $subject = trim($_POST['subject']);
    $description = trim($_POST['description']);
    $price = trim($_POST['price']);
    $cover_image_name = '';
    $project_file_name = '';

    // --- Cover Image Upload Logic ---
    if (isset($_FILES['cover_image']) && $_FILES['cover_image']['error'] == 0) {
        $target_dir_img = "../uploads/covers/";
        if (!is_dir($target_dir_img)) mkdir($target_dir_img, 0755, true);
        $image_name = time() . '_cover_' . basename($_FILES["cover_image"]["name"]);
        $target_file_img = $target_dir_img . $image_name;
        $image_file_type = strtolower(pathinfo($target_file_img, PATHINFO_EXTENSION));
        $check = getimagesize($_FILES["cover_image"]["tmp_name"]);
        if($check !== false && in_array($image_file_type, ['jpg', 'png', 'jpeg', 'gif'])) {
            if (move_uploaded_file($_FILES["cover_image"]["tmp_name"], $target_file_img)) {
                $cover_image_name = $image_name;
            } else { $errors[] = "Error uploading cover image."; }
        } else { $errors[] = "Cover image must be a valid image file (JPG, PNG, GIF)."; }
    }

    // --- Project File Upload Logic ---
    if (isset($_FILES['project_file']) && $_FILES['project_file']['error'] == 0) {
        $target_dir_proj = "../uploads/projects/";
        if (!is_dir($target_dir_proj)) mkdir($target_dir_proj, 0755, true);
        $file_name = time() . '_proj_' . basename($_FILES["project_file"]["name"]);
        $target_file_proj = $target_dir_proj . $file_name;
        if (move_uploaded_file($_FILES["project_file"]["tmp_name"], $target_file_proj)) {
            $project_file_name = $file_name;
        } else { $errors[] = "Error uploading project file."; }
    } else {
        $errors[] = "A project file is required.";
    }

    // Final Validation and DB Insert
    if (empty($title)) $errors[] = 'Title is required.';
    if (empty($subject)) $errors[] = 'Subject is required.';
    if (empty($price)) $errors[] = 'A valid price is required.';

    if (empty($errors)) {
        $stmt = $conn->prepare("INSERT INTO projects (title, subject, description, price, file_path, cover_image) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sssiss", $title, $subject, $description, $price, $project_file_name, $cover_image_name);
        if ($stmt->execute()) {
            $_SESSION['message'] = "Project added successfully!";
            header('Location: manage_projects.php');
            exit();
        } else { $errors[] = "Database error: " . $stmt->error; }
        $stmt->close();
    }
}
?>

<h2>Add New Project</h2>
<form action="add_project.php" method="post" enctype="multipart/form-data">
    <div class="mb-3">
        <label for="title" class="form-label">Project Title</label>
        <input type="text" class="form-control" id="title" name="title" required>
    </div>
     <div class="mb-3">
        <label for="subject" class="form-label">Subject</label>
        <input type="text" class="form-control" id="subject" name="subject" required>
    </div>
    <div class="mb-3">
        <label for="project-description" class="form-label">Description</label>
        <textarea class="form-control" id="project-description" name="description" rows="10"></textarea>
    </div>
    <div class="row">
        <div class="col-md-6 mb-3">
            <label for="price" class="form-label">Price (₹)</label>
            <input type="number" step="0.01" class="form-control" id="price" name="price" required>
        </div>
        <div class="col-md-6 mb-3">
            <label for="cover_image" class="form-label">Cover Image (Optional)</label>
            <div class="custom-file-input">
                <input type="file" id="cover_image" name="cover_image">
                <label class="file-label" for="cover_image">Choose File</label>
                <span class="file-name">No file selected...</span>
            </div>
        </div>
    </div>
    <div class="mb-3">
        <label for="project_file" class="form-label">Project Download File (Required)</label>
        <div class="custom-file-input">
            <input type="file" id="project_file" name="project_file" required>
            <label class="file-label" for="project_file">Choose File</label>
            <span class="file-name">No file selected...</span>
        </div>
    </div>
    <button type="submit" class="btn btn-primary">Add Project</button>
</form>

<script>
    tinymce.init({ selector: '#project-description' /* ... */ });
</script>
<?php require_once 'partials/footer.php'; ?>