<?php
require_once '../includes/db.php';
require_once 'partials/header.php';

// Handle a delete action
if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
    $id_to_delete = intval($_GET['id']);
    
    // Note: In a real-world application, you might want to handle user's orders and reviews
    // before deleting them (e.g., anonymize them or delete them via cascading rules).
    // For now, we will proceed with a direct deletion.
    
    $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
    $stmt->bind_param("i", $id_to_delete);
    
    if ($stmt->execute()) {
        $_SESSION['message'] = "User deleted successfully.";
    } else {
        $_SESSION['message'] = "Error deleting user.";
    }
    $stmt->close();
    
    header('Location: manage_users.php');
    exit;
}

// Fetch all users to display in the table
$users_result = $conn->query("SELECT id, name, email, created_at FROM users ORDER BY created_at DESC");
?>

<div class="container-fluid">
    <h1 class="mt-4">Manage Users</h1>
    <p class="text-muted">Here you can see all the users who have registered on your website.</p>

    <?php if (isset($_SESSION['message'])): ?>
        <div class="alert alert-info alert-dismissible fade show" role="alert">
            <?php echo $_SESSION['message']; unset($_SESSION['message']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <div class="card mt-4">
        <div class="card-header">
            <h4>Registered Users List</h4>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-striped table-hover">
                    <thead class="table-dark">
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Registration Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($users_result->num_rows > 0): ?>
                            <?php while($user = $users_result->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo $user['id']; ?></td>
                                    <td><?php echo htmlspecialchars($user['name']); ?></td>
                                    <td><?php echo htmlspecialchars($user['email']); ?></td>
                                    <td><?php echo date('d M Y, h:i A', strtotime($user['created_at'])); ?></td>
                                    <td>
                                        <a href="manage_users.php?action=delete&id=<?php echo $user['id']; ?>" 
                                           class="btn btn-danger btn-sm" 
                                           onclick="return confirm('Are you sure you want to delete this user? This will also delete their reviews and might affect order history. This action cannot be undone.');">
                                            Delete
                                        </a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="5" class="text-center">No users have registered yet.</td></tr>
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