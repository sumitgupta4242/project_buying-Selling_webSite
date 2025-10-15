<?php
require_once '../includes/db.php';
require_once 'partials/header.php';

// Handle Image Upload
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['slider_image'])) {
    if ($_FILES['slider_image']['error'] == 0) {
        $target_dir = "../uploads/slider/";
        if (!is_dir($target_dir)) mkdir($target_dir, 0755, true);
        
        $image_name = time() . '_' . basename($_FILES["slider_image"]["name"]);
        $target_file = $target_dir . $image_name;
        $image_file_type = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));

        if (in_array($image_file_type, ['jpg', 'png', 'jpeg', 'gif'])) {
            if (move_uploaded_file($_FILES["slider_image"]["tmp_name"], $target_file)) {
                $stmt = $conn->prepare("INSERT INTO hero_slider_images (image_filename) VALUES (?)");
                $stmt->bind_param("s", $image_name);
                $stmt->execute();
                $_SESSION['message'] = "Slider image uploaded successfully.";
            }
        } else {
            $_SESSION['message'] = "Error: Only JPG, JPEG, PNG & GIF files are allowed.";
        }
    }
    header('Location: manage_slider.php');
    exit;
}

// Handle Image Deletion
if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
    $id_to_delete = intval($_GET['id']);
    $stmt = $conn->prepare("SELECT image_filename FROM hero_slider_images WHERE id = ?");
    $stmt->bind_param("i", $id_to_delete);
    $stmt->execute();
    $result = $stmt->get_result();
    if($row = $result->fetch_assoc()){
        if(!empty($row['image_filename']) && file_exists("../uploads/slider/" . $row['image_filename'])){
            unlink("../uploads/slider/" . $row['image_filename']);
        }
    }
    $stmt->close();
    
    $stmt = $conn->prepare("DELETE FROM hero_slider_images WHERE id = ?");
    $stmt->bind_param("i", $id_to_delete);
    $stmt->execute();
    $_SESSION['message'] = "Slider image deleted successfully.";
    header('Location: manage_slider.php');
    exit;
}

// Fetch all slider images
$images_result = $conn->query("SELECT * FROM hero_slider_images ORDER BY sort_order ASC, uploaded_at DESC");
?>

<div class="container-fluid">
    <h1 class="mt-4">Manage Homepage Slider</h1>

    <?php if (isset($_SESSION['message'])): ?>
        <div class="alert alert-info alert-dismissible fade show" role="alert">
            <?php echo $_SESSION['message']; unset($_SESSION['message']); ?>
        </div>
    <?php endif; ?>

    <div class="card mb-4">
        <div class="card-header"><h4>Upload New Slider Image</h4></div>
        <div class="card-body">
            <form action="manage_slider.php" method="POST" enctype="multipart/form-data">
                <div class="input-group">
                    <input type="file" class="form-control" name="slider_image" required>
                    <button class="btn btn-primary" type="submit">Upload Image</button>
                </div>
                <div class="form-text">Use large, high-quality images (e.g., 1920x1080px). Abstract or subtle patterns work best.</div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-header"><h4>Current Slider Images</h4></div>
        <div class="card-body">
            <div class="row">
                <?php if ($images_result->num_rows > 0): ?>
                    <?php while($image = $images_result->fetch_assoc()): ?>
                        <div class="col-lg-3 col-md-4 col-sm-6 mb-4">
                            <div class="card">
                                <img src="../uploads/slider/<?php echo htmlspecialchars($image['image_filename']); ?>" class="card-img-top" style="height: 150px; object-fit: cover;">
                                <div class="card-footer text-center">
                                    <a href="manage_slider.php?action=delete&id=<?php echo $image['id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure?');">Delete</a>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <p class="text-center">No slider images uploaded yet.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require_once 'partials/footer.php'; ?>