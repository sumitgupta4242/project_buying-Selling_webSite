<?php
require_once '../includes/db.php';
require_once 'partials/header.php';

$coupon_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Handle form submission for updating the coupon
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_coupon'])) {
    $id = intval($_POST['id']);
    $code = strtoupper(trim($_POST['code']));
    $discount = trim($_POST['discount_percentage']);
    $valid_until = !empty($_POST['valid_until']) ? $_POST['valid_until'] : null;

    if ($id > 0 && !empty($code) && is_numeric($discount) && $discount > 0 && $discount <= 100) {
        $stmt = $conn->prepare("UPDATE coupons SET code = ?, discount_percentage = ?, valid_until = ? WHERE id = ?");
        $stmt->bind_param("sdsi", $code, $discount, $valid_until, $id);
        if ($stmt->execute()) {
            $_SESSION['message'] = "Coupon updated successfully!";
        } else {
            $_SESSION['message'] = "Error updating coupon. Code might already exist.";
        }
        $stmt->close();
    } else {
        $_SESSION['message'] = "Error: Invalid data provided.";
    }
    header('Location: manage_coupons.php');
    exit;
}

// Fetch the coupon data to pre-fill the form
if ($coupon_id > 0) {
    $stmt = $conn->prepare("SELECT * FROM coupons WHERE id = ?");
    $stmt->bind_param("i", $coupon_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows === 1) {
        $coupon = $result->fetch_assoc();
    } else {
        $_SESSION['message'] = "Coupon not found.";
        header('Location: manage_coupons.php');
        exit;
    }
    $stmt->close();
} else {
    header('Location: manage_coupons.php');
    exit;
}
?>

<div class="container-fluid">
    <h1 class="mt-4">Edit Coupon</h1>

    <div class="card mb-4">
        <div class="card-header">
            <h4>Editing Coupon: <?php echo htmlspecialchars($coupon['code']); ?></h4>
        </div>
        <div class="card-body">
            <form action="edit_coupon.php" method="POST">
                <input type="hidden" name="id" value="<?php echo $coupon['id']; ?>">
                <div class="mb-3">
                    <label for="code" class="form-label">Coupon Code</label>
                    <input type="text" class="form-control" name="code" value="<?php echo htmlspecialchars($coupon['code']); ?>" required>
                </div>
                <div class="mb-3">
                    <label for="discount_percentage" class="form-label">Discount %</label>
                    <input type="number" step="0.01" class="form-control" name="discount_percentage" value="<?php echo htmlspecialchars($coupon['discount_percentage']); ?>" required>
                </div>
                <div class="mb-3">
                    <label for="valid_until" class="form-label">Expiry Date (Leave empty for no expiry)</label>
                    <input type="date" class="form-control" name="valid_until" value="<?php echo htmlspecialchars($coupon['valid_until']); ?>">
                </div>
                <button type="submit" name="update_coupon" class="btn btn-primary">Save Changes</button>
                <a href="manage_coupons.php" class="btn btn-secondary">Cancel</a>
            </form>
        </div>
    </div>
</div>

<?php
$conn->close();
require_once 'partials/footer.php';
?>