<?php
require_once 'includes/db.php';
// session_start() might be in header.php, but if not, add it here for consistency
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Helper function to display stars (re-used from index.php)
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

require_once 'includes/header.php'; // Header is included here

// Fetches all unique subjects for the filter dropdown
$subjects_result = $conn->query("SELECT DISTINCT subject FROM projects WHERE subject IS NOT NULL AND subject != '' ORDER BY subject ASC");
$all_subjects = []; // Renamed to avoid conflict with selectedSubject
while ($row = $subjects_result->fetch_assoc()) {
    $all_subjects[] = $row['subject'];
}

// --- Pagination settings ---
$projects_per_page = 9; // Number of projects to display per page
$current_page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($current_page < 1) $current_page = 1; // Ensure page is not less than 1
$offset = ($current_page - 1) * $projects_per_page;

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
$selectedSubject = ''; // This will hold the subject selected for filtering

// Handle search term
if (isset($_GET['search']) && !empty(trim($_GET['search']))) {
    $searchTerm = trim($_GET['search']);
    $where_clauses[] = "p.title LIKE ?";
    $params[] = "%" . $searchTerm . "%";
    $types .= 's';
}

// Handle subject filter
if (isset($_GET['subject']) && !empty($_GET['subject']) && in_array($_GET['subject'], $all_subjects)) {
    $selectedSubject = $_GET['subject'];
    $where_clauses[] = "p.subject = ?";
    $params[] = $selectedSubject;
    $types .= 's';
}

if (!empty($where_clauses)) {
    $base_sql .= " AND " . implode(' AND ', $where_clauses);
}

// --- Count total projects for pagination ---
$count_sql = "SELECT COUNT(p.id) AS total_projects FROM projects AS p ";
$count_sql .= "LEFT JOIN (SELECT project_id FROM reviews GROUP BY project_id) AS r_stats ON p.id = r_stats.project_id "; // Only join if needed for WHERE clause
$count_sql .= "WHERE p.status = 'published'";
if (!empty($where_clauses)) {
    $count_sql .= " AND " . implode(' AND ', $where_clauses);
}

$count_stmt = $conn->prepare($count_sql);
if ($count_stmt === false) {
    die('Count prepare failed: ' . htmlspecialchars($conn->error));
}
if (!empty($params)) {
    $count_stmt->bind_param($types, ...$params);
}
$count_stmt->execute();
$count_result = $count_stmt->get_result();
$total_projects = $count_result->fetch_assoc()['total_projects'];
$count_stmt->close();

$total_pages = ceil($total_projects / $projects_per_page);

// --- Fetch projects for display on the current page ---
// Ordering by subject first, then by creation date for categorization
$sql = $base_sql . " ORDER BY p.subject ASC, p.created_at DESC LIMIT ?, ?";
$params_display = $params; // Copy params for the display query
$types_display = $types . 'ii'; // Add 'ii' for OFFSET and LIMIT parameters
$params_display[] = $offset;
$params_display[] = $projects_per_page;

$stmt = $conn->prepare($sql);
if ($stmt === false) {
    die('Prepare failed: ' . htmlspecialchars($conn->error));
}
if (!empty($params_display)) {
    $stmt->bind_param($types_display, ...$params_display);
}
$stmt->execute();
$result = $stmt->get_result();

$projects_by_subject = [];
if ($result->num_rows > 0) {
    while ($project = $result->fetch_assoc()) {
        $projects_by_subject[$project['subject']][] = $project;
    }
}
$stmt->close();
?>

<div class="container mt-5">
    <h1 class="text-center mb-4">All Projects</h1>

    <div class="card mb-4" data-aos="fade-up">
        <div class="card-body">
            <form action="projects.php" method="GET" class="row g-3 align-items-center">
                <div class="col-md">
                    <div class="position-relative">
                        <input type="text" name="search" id="search-input" class="form-control" placeholder="Search for project titles..." value="<?php echo htmlspecialchars($searchTerm); ?>" autocomplete="off">
                        <div id="suggestions-box" class="list-group position-absolute w-100"></div>
                    </div>
                </div>
                <div class="col-md">
                    <select name="subject" class="form-select">
                        <option value="">Filter by Subject (All)</option>
                        <?php foreach ($all_subjects as $subject): ?>
                            <option value="<?php echo htmlspecialchars($subject); ?>" <?php echo ($selectedSubject == $subject) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($subject); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-auto">
                    <button type="submit" class="btn btn-primary w-100">Apply Filter</button>
                </div>
            </form>
        </div>
    </div>

    <?php if (!empty($projects_by_subject)): ?>
        <?php foreach ($projects_by_subject as $subject_name => $projects_in_subject): ?>
            <h3 class="mt-5 mb-3 text-white"><?php echo htmlspecialchars($subject_name); ?> Projects</h3>
            <div class="row">
                <?php $animation_delay = 0; // Reset for each category ?>
                <?php foreach ($projects_in_subject as $project): ?>
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
                    <?php $animation_delay += 100; ?>
                <?php endforeach; ?>
            </div>
        <?php endforeach; ?>

        <?php if ($total_pages > 1): ?>
            <nav aria-label="Page navigation" class="mt-5">
                <ul class="pagination justify-content-center">
                    <?php
                    // Function to build query string for pagination links
                    function buildPaginationQuery($page, $searchTerm, $selectedSubject) {
                        $query_params = ['page' => $page];
                        if (!empty($searchTerm)) {
                            $query_params['search'] = urlencode($searchTerm);
                        }
                        if (!empty($selectedSubject)) {
                            $query_params['subject'] = urlencode($selectedSubject);
                        }
                        return http_build_query($query_params);
                    }
                    ?>

                    <li class="page-item <?php echo ($current_page <= 1) ? 'disabled' : ''; ?>">
                        <a class="page-link" href="?<?php echo buildPaginationQuery($current_page - 1, $searchTerm, $selectedSubject); ?>" aria-label="Previous">
                            <span aria-hidden="true">&laquo;</span>
                        </a>
                    </li>

                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                        <li class="page-item <?php echo ($i == $current_page) ? 'active' : ''; ?>">
                            <a class="page-link" href="?<?php echo buildPaginationQuery($i, $searchTerm, $selectedSubject); ?>">
                                <?php echo $i; ?>
                            </a>
                        </li>
                    <?php endfor; ?>

                    <li class="page-item <?php echo ($current_page >= $total_pages) ? 'disabled' : ''; ?>">
                        <a class="page-link" href="?<?php echo buildPaginationQuery($current_page + 1, $searchTerm, $selectedSubject); ?>" aria-label="Next">
                            <span aria-hidden="true">&raquo;</span>
                        </a>
                    </li>
                </ul>
            </nav>
        <?php endif; ?>

    <?php else: ?>
        <div class="alert alert-info text-center mt-5" data-aos="fade-up">
            <h3>No Projects Found</h3>
            <p>We couldn't find any projects matching your criteria. Try adjusting your filters or search terms.</p>
        </div>
    <?php endif; ?>

</div> <?php
$conn->close();
require_once 'includes/footer.php';
?>