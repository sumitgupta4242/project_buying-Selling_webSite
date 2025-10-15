<?php
require_once 'includes/db.php';
// session_start() might be in header.php, but if not, add it here for consistency
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Helper function to display stars.
if (!function_exists('display_stars')) {
    function display_stars($rating) {
        $stars_html = '';
        $full_stars = floor($rating);
        $half_star = ceil($rating) - $full_stars;
        $empty_stars = 5 - $full_stars - $half_star;
        for ($i = 0; $i < $full_stars; $i++) { $stars_html .= '<i class="fas fa-star"></i>'; }
        if ($half_star) { $stars_html .= '<i class="fas fa-star-half-alt"></i>'; }
        for ($i = 0; $i < $empty_stars; $i++) { $stars_html .= '<i class="far fa-star"></i>'; }
        return $stars_html;
    }
}

// Fetches settings for the hero banner text
$settings_result = $conn->query("SELECT setting_key, setting_value FROM site_settings");
$settings = [];
while ($row = $settings_result->fetch_assoc()) {
    $settings[$row['setting_key']] = $row['setting_value'];
}

// Fetch all active slider images
$slider_images_result = $conn->query("SELECT image_filename FROM hero_slider_images WHERE is_active = 1 ORDER BY sort_order ASC, uploaded_at DESC");
$slider_images = [];
while ($row = $slider_images_result->fetch_assoc()) {
    $slider_images[] = $row['image_filename'];
}

require_once 'includes/header.php'; // Header is included here

// Fetches all unique subjects for the filter dropdown
$subjects_result = $conn->query("SELECT DISTINCT subject FROM projects WHERE subject IS NOT NULL AND subject != '' ORDER BY subject ASC");
$subjects = [];
while ($row = $subjects_result->fetch_assoc()) {
    $subjects[] = $row['subject'];
}

// --- Dynamic SQL Query for Projects (including ratings, search, and filter) ---
$base_sql = "
    SELECT 
        p.id, p.title, p.subject, p.description, p.price, p.cover_image,
        r_stats.avg_rating,
        r_stats.total_reviews
    FROM 
        projects AS p
    LEFT JOIN 
        (SELECT project_id, AVG(rating) as avg_rating, COUNT(id) as total_reviews FROM reviews GROUP BY project_id) AS r_stats
    ON p.id = r_stats.project_id
    WHERE p.status = 'published'
";

$where_clauses = [];
$params = [];
$types = '';
$searchTerm = '';
if (isset($_GET['search']) && !empty(trim($_GET['search']))) {
    $searchTerm = trim($_GET['search']);
    $where_clauses[] = "p.title LIKE ?";
    $params[] = "%" . $searchTerm . "%";
    $types .= 's';
}
$selectedSubject = '';
if (isset($_GET['subject']) && !empty($_GET['subject'])) {
    $selectedSubject = $_GET['subject'];
    $where_clauses[] = "p.subject = ?";
    $params[] = $selectedSubject;
    $types .= 's';
}

if (!empty($where_clauses)) {
    $base_sql .= " AND " . implode(' AND ', $where_clauses);
}

// --- Count total projects for "See More" logic ---
$count_sql = "SELECT COUNT(p.id) AS total_projects FROM projects AS p ";
$count_sql .= "LEFT JOIN (SELECT project_id FROM reviews GROUP BY project_id) AS r_stats ON p.id = r_stats.project_id "; // Only join if needed for WHERE clause
$count_sql .= "WHERE p.status = 'published'";
if (!empty($where_clauses)) {
    $count_sql .= " AND " . implode(' AND ', $where_clauses);
}

$count_stmt = $conn->prepare($count_sql);
if (!empty($params)) {
    $count_stmt->bind_param($types, ...$params);
}
$count_stmt->execute();
$count_result = $count_stmt->get_result();
$total_projects = $count_result->fetch_assoc()['total_projects'];
$count_stmt->close();


// --- Fetch projects for display (limited to 8 for homepage) ---
$display_limit = 8;
$sql = $base_sql . " ORDER BY p.created_at DESC LIMIT ?";
$params_display = $params; // Copy params for the display query
$types_display = $types . 'i'; // Add 'i' for the LIMIT parameter
$params_display[] = $display_limit;

$stmt = $conn->prepare($sql);
if ($stmt === false) {
    die('Prepare failed: ' . htmlspecialchars($conn->error));
}
if (!empty($params_display)) {
    $stmt->bind_param($types_display, ...$params_display);
}
$stmt->execute();
$result = $stmt->get_result();
$animation_delay = 0; // Initialize animation delay counter
?>

<div class="p-5 mb-4 rounded-3 hero-banner" data-aos="fade-down">
    <?php foreach ($slider_images as $index => $image_filename): ?>
        <div class="hero-slide <?php echo ($index == 0) ? 'active' : ''; ?>" 
             style="background-image: url('uploads/slider/<?php echo htmlspecialchars($image_filename); ?>');">
        </div>
    <?php endforeach; ?>

    <div class="container-fluid py-5">
        <h1 class="display-5 fw-bold"><?php echo htmlspecialchars($settings['homepage_heading'] ?? 'Welcome!'); ?></h1>
        <p class="col-md-8 fs-4"><?php echo htmlspecialchars($settings['homepage_subheading'] ?? 'Browse our projects.'); ?></p>
    </div>
</div>

<div class="card mb-4" data-aos="fade-up">
    <div class="card-body">
        <form action="index.php" method="GET" class="row g-3 align-items-center">
            <div class="col-md">
                <div class="position-relative">
                    <input type="text" name="search" id="search-input" class="form-control" placeholder="Search for project titles..." value="<?php echo htmlspecialchars($searchTerm); ?>" autocomplete="off">
                    <div id="suggestions-box" class="list-group position-absolute w-100"></div>
                </div>
            </div>
            <div class="col-md">
                <select name="subject" class="form-select">
                    <option value="">Filter by Subject (All)</option>
                    <?php foreach ($subjects as $subject): ?>
                        <option value="<?php echo htmlspecialchars($subject); ?>" <?php echo ($selectedSubject == $subject) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($subject); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-auto">
                <button type="submit" class="btn btn-primary w-100">Search</button>
            </div>
        </form>
    </div>
</div>

<div class="row">
    <?php if ($result->num_rows > 0): ?>
        <?php while ($project = $result->fetch_assoc()): ?>
            <div class="col-md-4 mb-4" data-aos="fade-up" data-aos-delay="<?php echo $animation_delay; ?>">
                <div class="card h-100 position-relative">
                    <?php if (!empty($project['cover_image'])): ?>
                        <img src="uploads/covers/<?php echo htmlspecialchars($project['cover_image']); ?>" class="card-img-top" alt="<?php echo htmlspecialchars($project['title']); ?>" style="height: 200px; object-fit: cover;">
                    <?php endif; ?>
                    <div class="card-body d-flex flex-column">
                        <h5 class="card-title"><?php echo htmlspecialchars($project['title']); ?></h5>
                        <div class="mb-2" style="color: #f5b301;">
                            <?php $rating = $project['avg_rating'] ?? 0; echo display_stars($rating); ?>
                            <small class="text-muted">(<?php echo $project['total_reviews'] ?? 0; ?>)</small>
                        </div>
                        <h6 class="card-subtitle mb-2 text-muted"><?php echo htmlspecialchars($project['subject']); ?></h6>
                        <p class="card-text text-light opacity-75">
                            <?php echo htmlspecialchars(substr(strip_tags($project['description']), 0, 100)); ?>...
                        </p>
                        <p class="card-text mt-auto pt-3"><strong>Price: ₹<?php echo htmlspecialchars($project['price']); ?></strong></p>
                        <a href="project_details.php?id=<?php echo $project['id']; ?>" class="btn btn-primary stretched-link">View Details</a>
                    </div>
                </div>
            </div>
            <?php $animation_delay += 100; // Increment the delay for the next card ?>
        <?php endwhile; ?>
    <?php else: ?>
        <div class="col-12 text-center" data-aos="fade-up">
            <h3>No Projects Found</h3>
            <p>Try adjusting your search or filter criteria.</p>
        </div>
    <?php endif; ?>
</div>

<?php if ($total_projects > $display_limit): ?>
    <div class="row mt-4 mb-5">
        <div class="col-12 text-center">
            <?php
            // Construct the URL for projects.php with existing search/subject parameters
            $projects_link_params = [];
            if (!empty($searchTerm)) {
                $projects_link_params['search'] = urlencode($searchTerm);
            }
            if (!empty($selectedSubject)) {
                $projects_link_params['subject'] = urlencode($selectedSubject);
            }
            $projects_link = 'projects.php';
            if (!empty($projects_link_params)) {
                $projects_link .= '?' . http_build_query($projects_link_params);
            }
            ?>
            <a href="<?php echo htmlspecialchars($projects_link); ?>" class="btn btn-lg btn-secondary">
                See All Projects (<?php echo $total_projects; ?>) <i class="fas fa-arrow-right ms-2"></i>
            </a>
        </div>
    </div>
<?php endif; ?>

<?php
// Fetch features for comparison table
$features_result = $conn->query("SELECT * FROM feature_comparisons WHERE is_active = 1 ORDER BY sort_order ASC, created_at DESC");
$comparison_features = [];
while ($row = $features_result->fetch_assoc()) {
    $comparison_features[] = $row;
}
?>

<div class="row">
</div>

<div class="difference-section py-5">
    <div class="container" data-aos="fade-up">
        <div class="text-center mb-5">
            <h2 class="text-white display-5 fw-bold">The ProjectStore Difference</h2>
            <p class="lead text-muted opacity-75 mt-3">Why choose us? Here's how we stand out:</p>
        </div>
    </div>
</div>
<script src="assets/js/main.js"></script>

<?php
$stmt->close(); // Close the statement for main project display
$conn->close();
require_once 'includes/footer.php';
?>