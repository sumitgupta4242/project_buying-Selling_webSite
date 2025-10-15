<?php
session_start();
require_once 'includes/db.php';
require_once 'includes/config.php'; // For SITE_NAME and BASE_URL

$message = '';
$message_type = 'danger'; // Default to danger for errors

if (isset($_GET['token']) && !empty($_GET['token'])) {
    $token = $_GET['token'];

    // Find the user with this verification token
    $stmt = $conn->prepare("SELECT id, name, email, is_verified, token_expires FROM users WHERE verification_token = ?");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();

        // Check if the account is already verified
        if ($user['is_verified'] == 1) {
            $message = "Your email address is already verified. You can now log in.";
            $message_type = "info";
            // header('Location: login.php?verified=already'); // Optional: redirect immediately
            // exit;
        }
        // Check if the token has expired
        elseif (strtotime($user['token_expires']) < time()) {
            $message = "Your verification link has expired. Please log in to request a new one.";
            $message_type = "danger";
            // Optional: clear expired token from DB to prevent reuse
            $clear_token_stmt = $conn->prepare("UPDATE users SET verification_token = NULL, token_expires = NULL WHERE id = ?");
            $clear_token_stmt->bind_param("i", $user['id']);
            $clear_token_stmt->execute();
            $clear_token_stmt->close();
        }
        // Token is valid and unexpired, verify the user
        else {
            $update_stmt = $conn->prepare("UPDATE users SET is_verified = 1, verification_token = NULL, token_expires = NULL WHERE id = ?");
            $update_stmt->bind_param("i", $user['id']);

            if ($update_stmt->execute()) {
                $message = "Email address successfully verified! You can now log in.";
                $message_type = "success";
                // Optionally log the user in immediately (if desired, but usually better to send them to login page)
                // $_SESSION['user_id'] = $user['id'];
                // $_SESSION['user'] = $user['user'];
                // $_SESSION['email'] = $user['email'];
                // $_SESSION['is_verified'] = true;
                // header('Location: index.php'); // Redirect to homepage
            } else {
                $message = "Error verifying your email. Please try again or contact support.";
                $message_type = "danger";
            }
            $update_stmt->close();
        }
    } else {
        $message = "Invalid or tampered verification token.";
        $message_type = "danger";
    }
    $stmt->close();
} else {
    $message = "No verification token provided.";
    $message_type = "danger";
}

$conn->close(); // Close DB connection
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify Email - <?php echo SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="main-content-area">
    <div id="stars-container">
        <div id="stars1"></div>
        <div id="stars2"></div>
        <div id="stars3"></div>
    </div>
    <?php include 'includes/header.php'; ?>

    <div class="container d-flex align-items-center justify-content-center" style="min-height: calc(100vh - 90px);">
        <div class="col-md-6 col-lg-5 col-xl-4">
            <div class="card form-card glassmorphism-card">
                <div class="card-body text-center">
                    <h2 class="card-title mb-4">Email Verification</h2>

                    <?php if ($message): ?>
                        <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show" role="alert">
                            <?php echo $message; ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>

                    <?php if ($message_type == 'success' || $message_type == 'info'): ?>
                        <p class="mt-4">You can now proceed to the <a href="login.php" class="btn btn-primary btn-sm mt-2">Login Page</a>.</p>
                    <?php else: ?>
                        <p class="mt-4">If you need to resend the verification email, please try logging in: <a href="login.php" class="btn btn-info btn-sm mt-2">Login Page</a>.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <?php include 'partials/footer.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/script.js"></script>
</body>
</html>