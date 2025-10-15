<?php
require_once '../includes/db.php';
require_once 'partials/header.php';

// Handle Add/Edit Feature
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit_feature'])) {
    $feature_name = trim($_POST['feature_name']);
    $typical_description = trim($_POST['typical_description']);
    $projectstore_description = trim($_POST['projectstore_description']);
    $sort_order = intval($_POST['sort_order']);
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    $feature_id = isset($_POST['feature_id']) ? intval($_POST['feature_id']) : 0;

    if (empty($feature_name) || empty($typical_description) || empty($projectstore_description)) {
        $_SESSION['message'] = "All fields are required.";
        $_SESSION['message_type'] = "danger";
    } else {
        if ($feature_id > 0) {
            // Update existing feature
            $stmt = $conn->prepare("UPDATE feature_comparisons SET feature_name = ?, typical_platform_description = ?, projectstore_description = ?, sort_order = ?, is_active = ? WHERE id = ?");
            $stmt->bind_param("sssiii", $feature_name, $typical_description, $projectstore_description, $sort_order, $is_active, $feature_id);
            $stmt->execute();
            $_SESSION['message'] = "Feature updated successfully.";
        } else {
            // Add new feature
            $stmt = $conn->prepare("INSERT INTO feature_comparisons (feature_name, typical_platform_description, projectstore_description, sort_order, is_active) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("sssii", $feature_name, $typical_description, $projectstore_description, $sort_order, $is_active);
            $stmt->execute();
            $_SESSION['message'] = "Feature added successfully.";
        }
        $_SESSION['message_type'] = "success";
    }
    header('Location: manage_features.php');
    exit;
}

// Handle Delete Feature
if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
    $id_to_delete = intval($_GET['id']);
    $stmt = $conn->prepare("DELETE FROM feature_comparisons WHERE id = ?");
    $stmt->bind_param("i", $id_to_delete);
    $stmt->execute();
    $_SESSION['message'] = "Feature deleted successfully.";
    $_SESSION['message_type'] = "success";
    header('Location: manage_features.php');
    exit;
}

// Fetch all features for display
$features_result = $conn->query("SELECT * FROM feature_comparisons ORDER BY sort_order ASC, created_at DESC");

// Fetch a single feature for editing if ID is provided
$edit_feature = null;
if (isset($_GET['action']) && $_GET['action'] == 'edit' && isset($_GET['id'])) {
    $edit_id = intval($_GET['id']);
    $stmt = $conn->prepare("SELECT * FROM feature_comparisons WHERE id = ?");
    $stmt->bind_param("i", $edit_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $edit_feature = $result->fetch_assoc();
    }
}
?>

<div class="container-fluid">
    <h1 class="mt-4">Manage Feature Comparison</h1>

    <?php if (isset($_SESSION['message'])): ?>
        <div class="alert alert-<?php echo $_SESSION['message_type'] ?? 'info'; ?> alert-dismissible fade show" role="alert">
            <?php echo $_SESSION['message']; unset($_SESSION['message']); unset($_SESSION['message_type']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <div class="card mb-4">
        <div class="card-header">
            <h4><?php echo $edit_feature ? 'Edit Feature' : 'Add New Feature'; ?></h4>
        </div>
        <div class="card-body">
            <form action="manage_features.php" method="POST">
                <input type="hidden" name="feature_id" value="<?php echo htmlspecialchars($edit_feature['id'] ?? ''); ?>">

                <div class="mb-3">
                    <label for="feature_name" class="form-label">Feature Name</label>
                    <input type="text" class="form-control" id="feature_name" name="feature_name" value="<?php echo htmlspecialchars($edit_feature['feature_name'] ?? ''); ?>" required>
                </div>
                <div class="mb-3">
                    <label for="typical_description" class="form-label">Typical Platforms Description</label>
                    <textarea class="form-control" id="typical_description" name="typical_description" rows="2" required><?php echo htmlspecialchars($edit_feature['typical_platform_description'] ?? ''); ?></textarea>
                    <div class="form-text">e.g., "Often buggy or incomplete"</div>
                </div>
                <div class="mb-3">
                    <label for="projectstore_description" class="form-label">ProjectStore Description</label>
                    <textarea class="form-control" id="projectstore_description" name="projectstore_description" rows="2" required><?php echo htmlspecialchars($edit_feature['projectstore_description'] ?? ''); ?></textarea>
                    <div class="form-text">e.g., "✅ Clean, tested, and working code"</div>
                </div>
                <div class="mb-3">
                    <label for="sort_order" class="form-label">Sort Order</label>
                    <input type="number" class="form-control" id="sort_order" name="sort_order" value="<?php echo htmlspecialchars($edit_feature['sort_order'] ?? '0'); ?>">
                    <div class="form-text">Lower numbers appear first.</div>
                </div>
                <div class="mb-3 form-check">
                    <input type="checkbox" class="form-check-input" id="is_active" name="is_active" <?php echo ($edit_feature['is_active'] ?? 1) ? 'checked' : ''; ?>>
                    <label class="form-check-label" for="is_active">Is Active</label>
                </div>
                <button type="submit" name="submit_feature" class="btn btn-primary">
                    <?php echo $edit_feature ? 'Update Feature' : 'Add Feature'; ?>
                </button>
                <?php if ($edit_feature): ?>
                    <a href="manage_features.php" class="btn btn-secondary">Cancel Edit</a>
                <?php endif; ?>
            </form>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header">
            <h4>Current Features</h4>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead>
                        <tr>
                            <th>Feature Name</th>
                            <th>Typical Platforms</th>
                            <th>ProjectStore</th>
                            <th>Order</th>
                            <th>Active</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($features_result->num_rows > 0): ?>
                            <?php while($feature = $features_result->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($feature['feature_name']); ?></td>
                                    <td><?php echo htmlspecialchars($feature['typical_platform_description']); ?></td>
                                    <td><?php echo htmlspecialchars($feature['projectstore_description']); ?></td>
                                    <td><?php echo htmlspecialchars($feature['sort_order']); ?></td>
                                    <td>
                                        <?php if ($feature['is_active']): ?>
                                            <span class="badge bg-success">Yes</span>
                                        <?php else: ?>
                                            <span class="badge bg-danger">No</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <a href="manage_features.php?action=edit&id=<?php echo $feature['id']; ?>" class="btn btn-sm btn-info me-2">Edit</a>
                                        <a href="manage_features.php?action=delete&id=<?php echo $feature['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to delete this feature?');">Delete</a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" class="text-center">No features added yet.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php require_once 'partials/footer.php'; ?>