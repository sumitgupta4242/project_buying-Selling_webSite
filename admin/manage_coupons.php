<?php
require_once '../includes/db.php';
require_once 'partials/header.php';

// Handle form submission for creating a new coupon
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['create_coupon'])) {
    $code = strtoupper(trim($_POST['code']));
    $discount = trim($_POST['discount_percentage']);
    // Handle empty date field by setting it to NULL
    $valid_until = !empty($_POST['valid_until']) ? $_POST['valid_until'] : null;

    if (!empty($code) && is_numeric($discount) && $discount > 0 && $discount <= 100) {
        $stmt = $conn->prepare("INSERT INTO coupons (code, discount_percentage, valid_until) VALUES (?, ?, ?)");
        // Use 'sds' for string, double, string. NULL is passed as a string here.
        $stmt->bind_param("sds", $code, $discount, $valid_until);
        if ($stmt->execute()) {
            $_SESSION['message'] = "Coupon '" . htmlspecialchars($code) . "' created successfully!";
        } else {
            $_SESSION['message'] = "Error: Coupon code might already exist.";
        }
        $stmt->close();
    } else {
        $_SESSION['message'] = "Error: Please provide a valid code and a discount percentage (1-100).";
    }
    header('Location: manage_coupons.php');
    exit;
}

// Handle all actions like activate, deactivate, and delete
if (isset($_GET['action']) && isset($_GET['id'])) {
    $action = $_GET['action'];
    $id = intval($_GET['id']);

    if ($action == 'deactivate' || $action == 'activate') {
        $newStatus = ($action == 'deactivate') ? 0 : 1;
        $stmt = $conn->prepare("UPDATE coupons SET is_active = ? WHERE id = ?");
        $stmt->bind_param("ii", $newStatus, $id);
        $stmt->execute();
        $stmt->close();
        $_SESSION['message'] = "Coupon status updated!";
    }
    elseif ($action == 'delete') {
        $stmt = $conn->prepare("DELETE FROM coupons WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $stmt->close();
        $_SESSION['message'] = "Coupon deleted successfully!";
    }

    header('Location: manage_coupons.php');
    exit;
}

// Fetch all existing coupons to display them
$coupons_result = $conn->query("SELECT * FROM coupons ORDER BY created_at DESC");
?>

<div class="container-fluid">
    <h1 class="mt-4">Manage Coupons</h1>

    <?php if (isset($_SESSION['message'])): ?>
        <div class="alert alert-info alert-dismissible fade show" role="alert">
            <?php echo $_SESSION['message']; unset($_SESSION['message']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <div class="card mb-4">
        <div class="card-header">
            <h4><i class="fas fa-plus-circle me-2"></i>Create New Coupon</h4>
        </div>
        <div class="card-body">
            <form action="manage_coupons.php" method="POST">
                <div class="row g-3 align-items-end">
                    <div class="col-md-4">
                        <label for="code" class="form-label">Coupon Code</label>
                        <input type="text" class="form-control" id="code" name="code" placeholder="e.g., SAVE20" required>
                    </div>
                    <div class="col-md-3">
                        <label for="discount_percentage" class="form-label">Discount %</label>
                        <input type="number" step="0.01" class="form-control" id="discount_percentage" name="discount_percentage" placeholder="e.g., 20" required>
                    </div>
                    <div class="col-md-3">
                        <label for="valid_until" class="form-label">Expires On (Optional)</label>
                        <input type="date" class="form-control" id="valid_until" name="valid_until">
                    </div>
                    <div class="col-md-2">
                        <button type="submit" name="create_coupon" class="btn btn-primary w-100">Create</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
    <div class="card">
        <div class="card-header">
            <h4><i class="fas fa-tags me-2"></i>Existing Coupons</h4>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-striped">
                    <thead class="table-dark">
                        <tr>
                            <th>Code</th>
                            <th>Discount %</th>
                            <th>Times Used</th>
                            <th>Expires On</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($coupons_result->num_rows > 0): ?>
                            <?php while($coupon = $coupons_result->fetch_assoc()): ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($coupon['code']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($coupon['discount_percentage']); ?>%</td>
                                    <td><?php echo $coupon['times_used']; ?></td>
                                    <td><?php echo $coupon['valid_until'] ? date('d M Y', strtotime($coupon['valid_until'])) : 'Never'; ?></td>
                                    <td>
                                        <?php
                                            // Check if the coupon has a validity date and if that date is in the past.
                                            $is_expired = $coupon['valid_until'] && (new DateTime() > new DateTime($coupon['valid_until']));
                                            if ($is_expired) { echo '<span class="badge bg-danger">Expired</span>'; }
                                            elseif ($coupon['is_active']) { echo '<span class="badge bg-success">Active</span>'; }
                                            else { echo '<span class="badge bg-secondary">Inactive</span>'; }
                                        ?>
                                    </td>
                                    <td>
                                        <?php if (!$is_expired): // Only show activate/deactivate if not expired ?>
                                            <?php if ($coupon['is_active']): ?>
                                                <a href="manage_coupons.php?action=deactivate&id=<?php echo $coupon['id']; ?>" class="btn btn-warning btn-sm">Deactivate</a>
                                            <?php else: ?>
                                                <a href="manage_coupons.php?action=activate&id=<?php echo $coupon['id']; ?>" class="btn btn-success btn-sm">Activate</a>
                                            <?php endif; ?>
                                        <?php endif; ?>

                                        <a href="edit_coupon.php?id=<?php echo $coupon['id']; ?>" class="btn btn-info btn-sm">Edit</a>
                                        <a href="manage_coupons.php?action=delete&id=<?php echo $coupon['id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure you want to permanently delete this coupon?');">Delete</a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="6" class="text-center">No coupons created yet.</td></tr>
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