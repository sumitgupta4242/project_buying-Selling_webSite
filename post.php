<?php
require_once 'includes/db.php';

// Check for a slug in the URL
if (!isset($_GET['slug']) || empty($_GET['slug'])) {
    header('Location: blog.php');
    exit;
}

$slug = $_GET['slug'];

// Fetch the specific blog post that is "published"
$stmt = $conn->prepare("SELECT title, content, featured_image, created_at FROM blog_posts WHERE slug = ? AND status = 'published'");
$stmt->bind_param("s", $slug);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows !== 1) {
    // If no post is found, or it's a draft, redirect to the blog index
    header('Location: blog.php');
    exit;
}

$post = $result->fetch_assoc();
$stmt->close();

require_once 'includes/header.php';
?>

<div class="container mt-5">
    <div class="row">
        <div class="col-lg-8 offset-lg-2">
            
            <article>
                <header class="mb-4">
                    <h1 class="fw-bolder mb-1"><?php echo htmlspecialchars($post['title']); ?></h1>
                    <div class="text-muted fst-italic mb-2">Posted on <?php echo date('F j, Y', strtotime($post['created_at'])); ?></div>
                </header>

                <?php if (!empty($post['featured_image'])): ?>
                    <figure class="mb-4">
                        <img class="img-fluid rounded" src="uploads/blog/<?php echo htmlspecialchars($post['featured_image']); ?>" alt="<?php echo htmlspecialchars($post['title']); ?>">
                    </figure>
                <?php endif; ?>

                <section class="mb-5">
                    <?php
                        // We echo the content directly because TinyMCE provides clean, safe HTML.
                        // For a site with multiple untrusted authors, you would use a library like HTML Purifier here.
                        echo $post['content']; 
                    ?>
                </section>
            </article>

            <a href="blog.php" class="btn btn-outline-primary">← Back to Blog</a>
        </div>
    </div>
</div>

<?php
$conn->close();
require_once 'includes/footer.php';
?>