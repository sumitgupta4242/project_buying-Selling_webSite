<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['admin_loggedin']) || $_SESSION['admin_loggedin'] !== true) {
    header('Location: index.php');
    exit;
}
require_once '../includes/db.php';
$pending_count_result = $conn->query("SELECT COUNT(id) as pending_count FROM projects WHERE status = 'pending_approval'");
$pending_count = $pending_count_result->fetch_assoc()['pending_count'] ?? 0;
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Admin Panel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="stylesheet" href="assets/css/admin_style.css">
</head>

<body>
    <div id="sidebar">
        <a href="dashboard.php" class="sidebar-brand">ProjectStore</a>
        <ul class="sidebar-links">
            <li class="sidebar-link"><a href="dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
            <li class="sidebar-link">
                <a href="review_submissions.php" class="d-flex justify-content-between align-items-center">
                    <span><i class="fas fa-check-double"></i> Review Submissions</span>
                    <?php if ($pending_count > 0): ?>
                        <span class="badge bg-warning rounded-pill"><?php echo $pending_count; ?></span>
                    <?php endif; ?>
                </a>
            </li>
            <li class="sidebar-heading px-4 mt-4 mb-1 text-muted text-uppercase"><span>Content</span></li>
            <li class="sidebar-link"><a href="manage_projects.php"><i class="fas fa-folder"></i> Manage Projects</a>
            </li>
            <li class="sidebar-link">
                <a href="manage_slider.php"><i class="fas fa-images"></i> Manage Slider</a>
            </li>
            <li class="sidebar-link">
                <a href="manage_features.php"><i class="fas fa-list-check"></i> Manage Features</a>
            </li>
            <li class="sidebar-link"><a href="manage_posts.php"><i class="fas fa-newspaper"></i> Manage Posts</a></li>
            <li class="sidebar-link"><a href="site_settings.php"><i class="fas fa-cog"></i> Site Settings</a></li>
            <li class="sidebar-heading px-4 mt-4 mb-1 text-muted text-uppercase"><span>Business</span></li>
            <li class="sidebar-link"><a href="manage_users.php"><i class="fas fa-users"></i> Manage Users</a></li>
            <li class="sidebar-link"><a href="manage_employees.php"><i class="fas fa-user-tie"></i> Manage Employees</a>
            </li>
            <li class="sidebar-link"><a href="manage_coupons.php"><i class="fas fa-tags"></i> Manage Coupons</a></li>
            <li class="sidebar-link"><a href="manage_requests.php"><i class="fas fa-file-alt"></i> Manage Requests</a>
            </li>
        </ul>
    </div>
    <div id="main-content">
        <div class="card top-bar">
            <div class="card-body d-flex justify-content-between align-items-center">
                <div><span class="navbar-text">Welcome,
                        <strong><?php echo htmlspecialchars($_SESSION['admin_username']); ?></strong></span></div>
                <div><a href="../index.php" class="btn btn-outline-secondary btn-sm" target="_blank">View Site</a> <a
                        href="logout.php" class="btn btn-primary btn-sm">Logout</a>
                    <a href="\last_second\project_buy&approve\index.html" class="btn btn-primary btn-sm">sell-admin</a></div>
            </div>
        </div>