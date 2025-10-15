<?php
require_once '../includes/db.php';
require_once 'partials/header.php';

// Helper function to create a URL-friendly "slug" from a string
function create_slug($string) {
    $string = strtolower($string);
    $string = preg_replace('/[^a-z0-9_\s-]/', '', $string);
    $string = preg_replace('/[\s-]+/', '-', $string);
    $string = trim($string, '-');
    return $string;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $title = trim($_POST['title']);
    $content = trim($_POST['content']);
    $status = $_POST['status'];
    $slug = create_slug($title);
    $image_name = '';

    // Handle featured image upload
    if (isset($_FILES['featured_image']) && $_FILES['featured_image']['error'] == 0) {
        $target_dir = "../uploads/blog/";
        if (!is_dir($target_dir)) mkdir($target_dir, 0755, true);
        $image_name = time() . '_' . basename($_FILES["featured_image"]["name"]);
        $target_file = $target_dir . $image_name;
        // Basic validation for image type
        $image_file_type = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));
        if (in_array($image_file_type, ['jpg', 'png', 'jpeg', 'gif'])) {
            move_uploaded_file($_FILES["featured_image"]["tmp_name"], $target_file);
        } else {
            $image_name = ''; // Clear image name if not a valid image
        }
    }

    $stmt = $conn->prepare("INSERT INTO blog_posts (title, slug, content, status, featured_image) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("sssss", $title, $slug, $content, $status, $image_name);

    if ($stmt->execute()) {
        $_SESSION['message'] = "Blog post created successfully!";
        header('Location: manage_posts.php');
        exit();
    } else {
        // Handle potential duplicate slug error
        $_SESSION['message'] = "Error: A post with a similar title might already exist.";
        header('Location: add_post.php');
        exit();
    }
}
?>

<div class="container-fluid">
    <h1 class="mt-4">Add New Blog Post</h1>
    
    <?php if (isset($_SESSION['message'])): ?>
        <div class="alert alert-danger"><?php echo $_SESSION['message']; unset($_SESSION['message']); ?></div>
    <?php endif; ?>

    <form action="add_post.php" method="post" enctype="multipart/form-data" class="card mt-4">
        <div class="card-body">
            <div class="mb-3">
                <label for="title" class="form-label">Post Title</label>
                <input type="text" class="form-control" id="title" name="title" required>
            </div>
            <div class="mb-3">
                <label for="post-content" class="form-label">Content</label>
                <textarea class="form-control" id="post-content" name="content" rows="15"></textarea>
            </div>
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="featured_image" class="form-label">Featured Image</label>
                    <input type="file" class="form-control" id="featured_image" name="featured_image">
                </div>
                <div class="col-md-6 mb-3">
                    <label for="status" class="form-label">Status</label>
                    <select class="form-select" id="status" name="status">
                        <option value="draft" selected>Draft</option>
                        <option value="published">Published</option>
                    </select>
                </div>
            </div>
            <button type="submit" class="btn btn-primary">Save Post</button>
        </div>
    </form>
</div>

<script>
    tinymce.init({
        selector: '#post-content',
        plugins: 'lists link image media table code help wordcount',
        toolbar: 'undo redo | blocks | bold italic | alignleft aligncenter alignright | indent outdent | bullist numlist | code | help'
    });
</script>

<?php require_once 'partials/footer.php'; ?>