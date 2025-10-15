<?php
require_once '../includes/db.php';
require_once 'partials/header.php';

// Fetch all projects to display in the table
$result = $conn->query("SELECT id, title, subject, price FROM projects ORDER BY created_at DESC");
?>

<div class="container-fluid">
    <h1 class="mt-4">Manage Projects</h1>
    <p class="text-muted">Here you can view, edit, or delete your existing projects.</p>

    <?php if (isset($_SESSION['message'])): ?>
        <div class="alert alert-info alert-dismissible fade show" role="alert">
            <?php echo $_SESSION['message']; unset($_SESSION['message']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <div class="card mt-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h4>All Projects</h4>
            <a href="add_project.php" class="btn btn-primary">Add New Project</a>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-striped table-hover">
                    <thead class="table-dark">
                        <tr>
                            <th>Title</th>
                            <th>Subject</th>
                            <th>Price</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($result->num_rows > 0): ?>
                            <?php while($project = $result->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($project['title']); ?></td>
                                    <td><?php echo htmlspecialchars($project['subject']); ?></td>
                                    <td>₹<?php echo htmlspecialchars($project['price']); ?></td>
                                    <td>
                                        <a href="edit_project.php?id=<?php echo $project['id']; ?>" class="btn btn-warning btn-sm">Edit</a>
                                        <a href="delete_project.php?id=<?php echo $project['id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure you want to delete this project?');">Delete</a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="4" class="text-center">No projects found. <a href="add_project.php">Add one now</a>.</td>
                            </tr>
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