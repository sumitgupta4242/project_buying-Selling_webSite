<?php
require_once '../includes/db.php';
require_once 'partials/header.php';

// Handle delete action
if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
    $id_to_delete = intval($_GET['id']);
    // First, get the image filename to delete the file from server
    $stmt = $conn->prepare("SELECT featured_image FROM blog_posts WHERE id = ?");
    $stmt->bind_param("i", $id_to_delete);
    $stmt->execute();
    $result = $stmt->get_result();
    if($row = $result->fetch_assoc()){
        if(!empty($row['featured_image']) && file_exists("../uploads/blog/" . $row['featured_image'])){
            unlink("../uploads/blog/" . $row['featured_image']);
        }
    }
    $stmt->close();
    
    // Now, delete the post from the database
    $stmt = $conn->prepare("DELETE FROM blog_posts WHERE id = ?");
    $stmt->bind_param("i", $id_to_delete);
    $stmt->execute();
    $stmt->close();
    $_SESSION['message'] = "Blog post deleted successfully.";
    header('Location: manage_posts.php');
    exit;
}

// Fetch all blog posts
$posts_result = $conn->query("SELECT id, title, status, created_at FROM blog_posts ORDER BY created_at DESC");
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mt-4">
        <h1>Manage Blog Posts</h1>
        <a href="add_post.php" class="btn btn-primary">Add New Post</a>
    </div>

    <?php if (isset($_SESSION['message'])): ?>
        <div class="alert alert-info alert-dismissible fade show mt-3" role="alert">
            <?php echo $_SESSION['message']; unset($_SESSION['message']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <div class="card mt-4">
        <div class="card-body">
            <table class="table table-hover">
                <thead class="table-dark">
                    <tr>
                        <th>Title</th>
                        <th>Status</th>
                        <th>Date Created</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($posts_result->num_rows > 0): ?>
                        <?php while($post = $posts_result->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($post['title']); ?></td>
                                <td>
                                    <span class="badge <?php echo ($post['status'] == 'published') ? 'bg-success' : 'bg-secondary'; ?>">
                                        <?php echo ucfirst($post['status']); ?>
                                    </span>
                                </td>
                                <td><?php echo date('d M Y', strtotime($post['created_at'])); ?></td>
                                <td>
                                    <a href="edit_post.php?id=<?php echo $post['id']; ?>" class="btn btn-warning btn-sm">Edit</a>
                                    <a href="manage_posts.php?action=delete&id=<?php echo $post['id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure?');">Delete</a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="4" class="text-center">No blog posts found.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once 'partials/footer.php'; ?>