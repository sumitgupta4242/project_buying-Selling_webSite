<?php
require_once '../includes/db.php';
require_once 'partials/header.php';

$employeeId = $_SESSION['employee_id'];

// Fetch the projects submitted by the currently logged-in employee
$stmt = $conn->prepare("SELECT id, title, created_at, status, admin_review FROM projects WHERE submitted_by_id = ? ORDER BY created_at DESC");
$stmt->bind_param("i", $employeeId);
$stmt->execute();
$result = $stmt->get_result();
?>
<h3>My Project Submissions</h3>
<?php if (isset($_SESSION['message'])): ?>
    <div class="alert alert-success"><?php echo $_SESSION['message']; unset($_SESSION['message']); ?></div>
<?php endif; ?>
<div class="card mt-4">
    <div class="card-body">
        <!-- FIX: Wrapped table in a responsive container -->
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Project Title</th>
                        <th>Date Submitted</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($result->num_rows > 0): ?>
                        <?php while($project = $result->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($project['title']); ?></td>
                                <td><?php echo date('d M Y', strtotime($project['created_at'])); ?></td>
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
                                <td>
                                    <a href="edit_project.php?id=<?php echo $project['id']; ?>" class="btn btn-sm btn-primary">Edit</a>
                                    <!-- Optional: View Admin Review -->
                                    <?php if ($project['status'] == 'rejected' && !empty($project['admin_review'])): ?>
                                        <button type="button" class="btn btn-sm btn-info" data-bs-toggle="modal" data-bs-target="#reviewModal<?php echo $project['id']; ?>">
                                            View Review
                                        </button>
                                    <?php endif; ?>
                                </td>
                            </tr>

                            <?php if ($project['status'] == 'rejected' && !empty($project['admin_review'])): ?>
                            <!-- Modal for Admin Review -->
                            <div class="modal fade" id="reviewModal<?php echo $project['id']; ?>" tabindex="-1" aria-labelledby="reviewModalLabel<?php echo $project['id']; ?>" aria-hidden="true">
                                <div class="modal-dialog">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title" id="reviewModalLabel<?php echo $project['id']; ?>">Admin Feedback</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                        </div>
                                        <div class="modal-body">
                                            <?php echo htmlspecialchars($project['admin_review']); ?>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <?php endif; ?>

                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="4" class="text-center">You have not submitted any projects yet.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php require_once 'partials/footer.php'; ?>

