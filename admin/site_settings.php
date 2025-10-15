<?php
require_once '../includes/db.php';
require_once 'partials/header.php';

// --- Handle Form Submission to UPDATE settings ---
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Loop through the text-based POST data and update each setting
    foreach ($_POST as $key => $value) {
        $stmt = $conn->prepare("UPDATE site_settings SET setting_value = ? WHERE setting_key = ?");
        $stmt->bind_param("ss", $value, $key);
        $stmt->execute();
        $stmt->close();
    }
    
    // --- NEW: Handle Background Image Upload ---
    if (isset($_FILES['homepage_background_image']) && $_FILES['homepage_background_image']['error'] == 0) {
        // First, get the old image name to delete it after successful upload
        $old_img_result = $conn->query("SELECT setting_value FROM site_settings WHERE setting_key = 'homepage_background_image'");
        $old_img_name = $old_img_result->fetch_assoc()['setting_value'];

        $target_dir = "../uploads/backgrounds/";
        if (!is_dir($target_dir)) mkdir($target_dir, 0755, true);
        
        $new_img_name = time() . '_' . basename($_FILES["homepage_background_image"]["name"]);
        $target_file = $target_dir . $new_img_name;

        // Basic validation for image type
        $image_file_type = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));
        if (in_array($image_file_type, ['jpg', 'png', 'jpeg', 'gif'])) {
            if (move_uploaded_file($_FILES["homepage_background_image"]["tmp_name"], $target_file)) {
                // Update the database with the new filename
                $stmt = $conn->prepare("UPDATE site_settings SET setting_value = ? WHERE setting_key = 'homepage_background_image'");
                $stmt->bind_param("s", $new_img_name);
                $stmt->execute();
                $stmt->close();
                
                // If a new image was uploaded successfully, delete the old one
                if (!empty($old_img_name) && file_exists($target_dir . $old_img_name)) {
                    unlink($target_dir . $old_img_name);
                }
            }
        }
    }

    $_SESSION['message'] = "Site settings updated successfully!";
    header('Location: site_settings.php');
    exit;
}

// Fetch all current settings to display in the form
$settings_result = $conn->query("SELECT * FROM site_settings");
$settings = [];
while ($row = $settings_result->fetch_assoc()) {
    $settings[$row['setting_key']] = $row['setting_value'];
}
?>

<div class="container-fluid">
    <h1 class="mt-4">Site Settings</h1>
    <p class="text-muted">Edit the content and appearance of your public-facing website.</p>

    <?php if (isset($_SESSION['message'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?php echo $_SESSION['message']; unset($_SESSION['message']); ?>
        </div>
    <?php endif; ?>

    <form action="site_settings.php" method="POST" enctype="multipart/form-data">
        <div class="card mt-4">
            <div class="card-header"><h4>Homepage Banner Content</h4></div>
            <div class="card-body">
                <div class="mb-3">
                    <label for="homepage_heading" class="form-label">Main Heading</label>
                    <input type="text" class="form-control" name="homepage_heading" value="<?php echo htmlspecialchars($settings['homepage_heading'] ?? ''); ?>">
                </div>
                <div class="mb-3">
                    <label for="homepage_subheading" class="form-label">Subheading / Paragraph</label>
                    <textarea class="form-control" name="homepage_subheading" rows="3"><?php echo htmlspecialchars($settings['homepage_subheading'] ?? ''); ?></textarea>
                </div>
            </div>
        </div>

        <!-- <div class="card mt-4">
            <div class="card-header"><h4>Homepage Background</h4></div>
            <div class="card-body">
                <div class="mb-3">
                    <label for="homepage_background_image" class="form-label">Upload New Background Image</label>
                    <input type="file" class="form-control" name="homepage_background_image">
                    <div class="form-text">For best results, use a large, high-quality image (e.g., 1920x1080px). Abstract or subtle patterns work well.</div>
                </div>
                <?php if (!empty($settings['homepage_background_image'])): ?>
                    <p><strong>Current Background:</strong></p>
                    <img src="../uploads/backgrounds/<?php echo htmlspecialchars($settings['homepage_background_image']); ?>" style="max-width: 300px; border-radius: 5px;">
                <?php endif; ?>
            </div>
        </div> -->

        <button type="submit" class="btn btn-primary mt-4">Save All Settings</button>
    </form>
</div>

<?php
$conn->close();
require_once 'partials/footer.php';
?>