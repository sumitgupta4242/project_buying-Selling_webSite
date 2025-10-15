<?php
require_once '../includes/db.php';
require_once 'partials/header.php';

// Check if a valid employee ID is provided
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: manage_employees.php');
    exit();
}
$employee_id = intval($_GET['id']);

// Fetch employee details to display their name
$employee_stmt = $conn->prepare("SELECT name FROM employees WHERE id = ?");
$employee_stmt->bind_param("i", $employee_id);
$employee_stmt->execute();
$employee_result = $employee_stmt->get_result();
if ($employee_result->num_rows === 0) {
    // Redirect if employee not found
    $_SESSION['message'] = "Error: Employee not found.";
    header('Location: manage_employees.php');
    exit();
}
$employee = $employee_result->fetch_assoc();
$employee_name = $employee['name'];

// Fetch all projects submitted by this specific employee
$projects_stmt = $conn->prepare("SELECT id, title, subject, status, created_at FROM projects WHERE submitted_by_id = ? ORDER BY created_at DESC");
$projects_stmt->bind_param("i", $employee_id);
$projects_stmt->execute();
$projects_result = $projects_stmt->get_result();
?>

<div class="container-fluid">
    <!-- Page Title -->
    <h1 class="mt-4">Project Submissions by <?php echo htmlspecialchars($employee_name); ?></h1>
    <a href="manage_employees.php" class="btn btn-secondary mb-3">‹ Back to All Employees</a>

    <!-- Projects Table -->
    <div class="card">
        <div class="card-header"><h4>All Projects</h4></div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead class="table-dark">
                        <tr>
                            <th>Project Title</th>
                            <th>Subject</th>
                            <th>Status</th>
                            <th>Date Submitted</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($projects_result->num_rows > 0): ?>
                            <?php while($project = $projects_result->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($project['title']); ?></td>
                                    <td><?php echo htmlspecialchars($project['subject']); ?></td>
                                    <td>
                                        <?php 
                                            $status = $project['status'];
                                            $badge_class = 'bg-secondary';
                                            if ($status == 'pending_approval') $badge_class = 'bg-warning text-dark';
                                            if ($status == 'published') $badge_class = 'bg-success';
                                            if ($status == 'rejected') $badge_class = 'bg-danger';
                                        ?>
                                        <span class="badge <?php echo $badge_class; ?>"><?php echo str_replace('_', ' ', ucfirst($status)); ?></span>
                                    </td>
                                    <td><?php echo date('d M Y', strtotime($project['created_at'])); ?></td>
                                    <td>
                                        <a href="edit_project.php?id=<?php echo $project['id']; ?>" class="btn btn-primary btn-sm">Edit / Review</a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="5" class="text-center">This employee has not submitted any projects yet.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php
$conn->close();
require_once 'partials/footer.php';
?>
