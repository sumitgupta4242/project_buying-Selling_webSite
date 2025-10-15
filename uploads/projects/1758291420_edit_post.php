<?php
require_once '../includes/db.php';
require_once 'partials/header.php';

// Helper function to create a URL-friendly "slug"
function create_slug($string) {
    $string = strtolower($string);
    $string = preg_replace('/[^a-z0-9_\s-]/', '', $string);
    $string = preg_replace('/[\s-]+/', '-', $string);
    $string = trim($string, '-');
    return $string;
}

// Check for post ID in URL
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: manage_posts.php');
    exit;
}
$post_id = $_GET['id'];

// --- Handle POST Request to Update the Post ---
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $title = trim($_POST['title']);
    $content = trim($_POST['content']);
    $status = $_POST['status'];
    $slug = create_slug($title);
    $current_image = $_POST['current_image']; // Get the existing image filename

    // Handle new featured image upload
    if (isset($_FILES['featured_image']) && $_FILES['featured_image']['error'] == 0) {
        $target_dir = "../uploads/blog/";
        $new_image_name = time() . '_' . basename($_FILES["featured_image"]["name"]);
        $target_file = $target_dir . $new_image_name;
        $image_file_type = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));
        if (in_array($image_file_type, ['jpg', 'png', 'jpeg', 'gif'])) {
            if (move_uploaded_file($_FILES["featured_image"]["tmp_name"], $target_file)) {
                // If new image is uploaded successfully, delete the old one
                if (!empty($current_image) && file_exists($target_dir . $current_image)) {
                    unlink($target_dir . $current_image);
                }
                $current_image = $new_image_name; // Update with the new image name
            }
        }
    }

    $stmt = $conn->prepare("UPDATE blog_posts SET title = ?, slug = ?, content = ?, status = ?, featured_image = ? WHERE id = ?");
    $stmt->bind_param("sssssi", $title, $slug, $content, $status, $current_image, $post_id);

    if ($stmt->execute()) {
        $_SESSION['message'] = "Blog post updated successfully!";
        header('Location: manage_posts.php');
        exit();
    } else {
        $_SESSION['message'] = "Error updating post.";
    }
}

// --- Fetch Existing Post Data to populate the form ---
$stmt = $conn->prepare("SELECT title, content, status, featured_image FROM blog_posts WHERE id = ?");
$stmt->bind_param("i", $post_id);
$stmt->execute();
$result = $stmt->get_result();
$post = $result->fetch_assoc();
if (!$post) {
    header('Location: manage_posts.php');
    exit;
}
$stmt->close();
?>

<div class="container-fluid">
    <h1 class="mt-4">Edit Blog Post</h1>

    <?php if (isset($_SESSION['message'])): ?>
        <div class="alert alert-danger"><?php echo $_SESSION['message']; unset($_SESSION['message']); ?></div>
    <?php endif; ?>

    <form action="edit_post.php?id=<?php echo $post_id; ?>" method="post" enctype="multipart/form-data" class="card mt-4">
        <div class="card-body">
            <div class="mb-3">
                <label for="title" class="form-label">Post Title</label>
                <input type="text" class="form-control" id="title" name="title" value="<?php echo htmlspecialchars($post['title']); ?>" required>
            </div>
            <div class="mb-3">
                <label for="post-content" class="form-label">Content</label>
                <textarea class="form-control" id="post-content" name="content" rows="15"><?php echo htmlspecialchars($post['content']); ?></textarea>
            </div>
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="featured_image" class="form-label">Upload New Featured Image</label>
                    <input type="file" class="form-control" id="featured_image" name="featured_image">
                    <input type="hidden" name="current_image" value="<?php echo htmlspecialchars($post['featured_image']); ?>">
                    <?php if (!empty($post['featured_image'])): ?>
                        <div class="mt-2">
                            <img src="../uploads/blog/<?php echo htmlspecialchars($post['featured_image']); ?>" alt="Current Image" style="max-height: 100px; border-radius: 5px;">
                        </div>
                    <?php endif; ?>
                </div>
                <div class="col-md-6 mb-3">
                    <label for="status" class="form-label">Status</label>
                    <select class="form-select" id="status" name="status">
                        <option value="draft" <?php echo ($post['status'] == 'draft') ? 'selected' : ''; ?>>Draft</option>
                        <option value="published" <?php echo ($post['status'] == 'published') ? 'selected' : ''; ?>>Published</option>
                    </select>
                </div>
            </div>
            <button type="submit" class="btn btn-primary">Update Post</button>
            <a href="manage_posts.php" class="btn btn-secondary">Cancel</a>
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