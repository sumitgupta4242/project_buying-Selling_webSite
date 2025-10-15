<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
// Protect all pages except the login page
$current_page = basename($_SERVER['PHP_SELF']);
if (!isset($_SESSION['employee_loggedin']) && $current_page != 'index.php') {
    header('Location: index.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <!-- FIX: Added viewport meta tag for mobile responsiveness -->
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Employee Panel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="dashboard.php">Employee Panel</a>
            <!-- FIX: Added button for mobile navigation toggle -->
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <?php if (isset($_SESSION['employee_loggedin'])): ?>
            <!-- FIX: Added ID and collapse class for mobile functionality -->
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                    <li class="nav-item"><a class="nav-link" href="dashboard.php">My Submissions</a></li>
                    <li class="nav-item"><a class="nav-link" href="submit_project.php">Submit New Project</a></li>
                </ul>
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item"><span class="navbar-text me-3">Welcome, <?php echo htmlspecialchars($_SESSION['employee_name']); ?></span></li>
                    <li class="nav-item"><a class="nav-link" href="logout.php">Logout</a></li>
                </ul>
            </div>
            <?php endif; ?>
        </div>
    </nav>
    <div class="container mt-4">
