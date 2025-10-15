<?php
require_once 'includes/db.php';
require_once 'includes/header.php';

// Fetch all *published* blog posts, ordered by the newest first
$posts_result = $conn->query("
    SELECT id, title, slug, content, featured_image, created_at 
    FROM blog_posts 
    WHERE status = 'published' 
    ORDER BY created_at DESC
");

?>

<div class="container">
    <div class="p-5 mb-4 bg-light rounded-3">
        <div class="container-fluid py-5">
            <h1 class="display-5 fw-bold">Our Blog & Articles</h1>
            <p class="col-md-8 fs-4">Insights, tips, and updates from our team to help you succeed.</p>
        </div>
    </div>

    <div class="row">
        <?php if ($posts_result->num_rows > 0): ?>
            <?php while($post = $posts_result->fetch_assoc()): ?>
                <div class="col-lg-4 col-md-6 mb-4">
                    <div class="card h-100">
                        <?php if (!empty($post['featured_image'])): ?>
                            <a href="post.php?slug=<?php echo $post['slug']; ?>">
                                <img src="uploads/blog/<?php echo htmlspecialchars($post['featured_image']); ?>" class="card-img-top" alt="<?php echo htmlspecialchars($post['title']); ?>" style="height: 200px; object-fit: cover;">
                            </a>
                        <?php endif; ?>
                        <div class="card-body d-flex flex-column">
                            <h5 class="card-title"><?php echo htmlspecialchars($post['title']); ?></h5>
                            <small class="text-muted mb-2">Published on <?php echo date('F j, Y', strtotime($post['created_at'])); ?></small>
                            <p class="card-text">
                                <?php
                                    // Create a short excerpt from the content
                                    $content_excerpt = strip_tags($post['content']);
                                    echo htmlspecialchars(substr($content_excerpt, 0, 120)) . '...';
                                ?>
                            </p>
                            <a href="post.php?slug=<?php echo $post['slug']; ?>" class="btn btn-primary mt-auto">Read More</a>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="col-12 text-center">
                <h3>No articles published yet.</h3>
                <p>Check back soon for new content!</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php
$conn->close();
require_once 'includes/footer.php';
?>