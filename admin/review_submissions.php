<?php
require_once '../includes/db.php';
require_once 'partials/header.php';

// --- HANDLE APPROVE/REJECT ACTIONS ---
if (isset($_GET['action']) && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $action = $_GET['action'];
    $new_status = '';

    if ($action == 'approve') {
        $new_status = 'published';
    } elseif ($action == 'reject') {
        $new_status = 'rejected';
    }

    if (!empty($new_status)) {
        $stmt = $conn->prepare("UPDATE projects SET status = ? WHERE id = ?");
        $stmt->bind_param("si", $new_status, $id);
        $stmt->execute();
        $stmt->close();
        $_SESSION['message'] = "Submission status updated successfully!";
        header('Location: review_submissions.php');
        exit;
    }
}

// Fetch all projects awaiting approval. We JOIN with the employees table to get the submitter's name.
$submissions_result = $conn->query(
    "SELECT p.id, p.title, p.subject, p.price, e.name AS employee_name 
    FROM projects AS p 
    JOIN employees AS e ON p.submitted_by_id = e.id 
    WHERE p.status = 'pending_approval' 
    ORDER BY p.created_at ASC"
);
?>

<div class="container-fluid">
    <h1 class="mt-4">Review Project Submissions</h1>
    <p class="text-muted">These projects have been submitted by employees and are awaiting your approval before they go live.</p>

    <?php if (isset($_SESSION['message'])): ?>
        <div class="alert alert-info alert-dismissible fade show" role="alert">
            <?php echo $_SESSION['message']; unset($_SESSION['message']); ?>
        </div>
    <?php endif; ?>

    <div class="card mt-4">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead class="table-dark">
                        <tr>
                            <th>Title</th>
                            <th>Subject</th>
                            <th>Price</th>
                            <th>Submitted By</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($submissions_result->num_rows > 0): ?>
                            <?php while($project = $submissions_result->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($project['title']); ?></td>
                                    <td><?php echo htmlspecialchars($project['subject']); ?></td>
                                    <td>₹<?php echo htmlspecialchars($project['price']); ?></td>
                                    <td><?php echo htmlspecialchars($project['employee_name']); ?></td>
                                    <td>
                                        <a href="edit_project.php?id=<?php echo $project['id']; ?>" class="btn btn-secondary btn-sm">Edit & Review</a>
                                        <a href="review_submissions.php?action=approve&id=<?php echo $project['id']; ?>" class="btn btn-success btn-sm" onclick="return confirm('Are you sure you want to approve and publish this project?');">Approve</a>
                                        <a href="review_submissions.php?action=reject&id=<?php echo $project['id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure you want to reject this project?');">Reject</a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="5" class="text-center">No pending submissions. Great job!</td></tr>
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