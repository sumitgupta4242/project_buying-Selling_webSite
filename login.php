<?php
session_start(); // Ensure session is started at the very beginning if header.php doesn't do it
require_once 'includes/db.php';
require_once 'includes/config.php'; // Include the new config file
require_once 'vendor/autoload.php'; // Autoload PHPMailer

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

$errors = [];
$message = ''; // Using $message and $message_type for consistent alert styling
$message_type = '';
$email_input = ''; // To preserve email in the form after submission

// Function to send verification email (reused from register.php/signup.php logic)
// IMPORTANT: Changed $username parameter to $name for consistency
function sendVerificationEmail($email, $name, $token, $conn) {
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host       = SMTP_HOST;
        $mail->SMTPAuth   = true;
        $mail->Username   = SMTP_USERNAME;
        $mail->Password   = SMTP_PASSWORD;
        $mail->SMTPSecure = SMTP_SECURE;
        $mail->Port       = SMTP_PORT;

        $mail->setFrom(SMTP_USERNAME, SITE_NAME . ' Support');
        $mail->addAddress($email, $name); // Add recipient's email and name, using $name

        $mail->isHTML(true);
        $mail->Subject = SITE_NAME . ' - Verify Your Email Address';
        $verification_link = BASE_URL . '/verify.php?token=' . $token;
        $mail->Body    = '
            <p>Hello ' . htmlspecialchars($name) . ',</p>
            <p>Thank you for registering with ' . SITE_NAME . '.</p>
            <p>Please click the link below to verify your email address:</p>
            <p><a href="' . htmlspecialchars($verification_link) . '">' . htmlspecialchars($verification_link) . '</a></p>
            <p>This link will expire in 1 hour.</p>
            <p>If you did not register for an account, please ignore this email.</p>
            <p>Regards,</p>
            <p>' . SITE_NAME . ' Team</p>
        ';
        $mail->AltBody = 'Hello ' . htmlspecialchars($name) . ', Thank you for registering with ' . SITE_NAME . '. Please copy and paste the link below into your browser to verify your email address: ' . htmlspecialchars($verification_link) . ' This link will expire in 1 hour. If you did not register for an account, please ignore this email. Regards, ' . SITE_NAME . ' Team';

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Resend verification email failed. Mailer Error: {$mail->ErrorInfo}");
        return false;
    }
}

// Handle login attempt
if ($_SERVER['REQUEST_METHOD'] == 'POST' && !isset($_POST['resend_email'])) {
    $email_input = trim($_POST['email']);
    $password = $_POST['password'];

    // Validation
    if (empty($email_input) || !filter_var($email_input, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'A valid email is required.';
    }
    if (empty($password)) {
        $errors[] = 'Password is required.';
    }

    if (empty($errors)) {
        // Fetch user data including verification status and token
        // CHANGED: username to name in SELECT query
        $stmt = $conn->prepare("SELECT id, name, email, password, is_verified, verification_token, token_expires FROM users WHERE email = ?");
        $stmt->bind_param("s", $email_input);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();

            // Verify password
            if (password_verify($password, $user['password'])) {
                if ($user['is_verified'] == 1) {
                    // Login successful and verified
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['user_name'] = $user['name']; // CHANGED: $user['username'] to $user['name']
                    $_SESSION['email'] = $user['email'];
                    $_SESSION['is_verified'] = true; // Set verified flag in session

                    // Check if a redirect URL was passed
                    if (isset($_GET['redirect']) && !empty($_GET['redirect'])) {
                        header('Location: ' . $_GET['redirect']);
                    } else {
                        header('Location: index.php');
                    }
                    exit();
                } else {
                    // Account not verified
                    $message = "Your account is not verified. Please check your email (" . htmlspecialchars($user['email']) . ") for a verification link.";
                    $message_type = "warning";
                    $_SESSION['unverified_email'] = $user['email']; // Store email to allow resend
                    $_SESSION['unverified_username'] = $user['name']; // CHANGED: $user['username'] to $user['name'] for session
                }
            } else {
                $errors[] = "Invalid email or password.";
            }
        } else {
            $errors[] = "Invalid email or password.";
        }
        $stmt->close();
    }
}

// Handle resend verification email request
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['resend_email']) && isset($_SESSION['unverified_email'])) {
    $email_to_resend = $_SESSION['unverified_email'];
    $name_to_resend = $_SESSION['unverified_username']; // CHANGED: to $name_to_resend

    // CHANGED: username to name in SELECT query
    $stmt = $conn->prepare("SELECT id, name, email, verification_token, token_expires FROM users WHERE email = ? AND is_verified = 0");
    $stmt->bind_param("s", $email_to_resend);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();

        // Check if existing token is still valid (e.g., within the last 5 minutes to prevent spamming)
        // Also check if token_expires is not null to avoid errors on first resend
        if ($user['token_expires'] && strtotime($user['token_expires']) > time() - 300 && !empty($user['verification_token'])) { // 300 seconds = 5 minutes
             $message = "A verification email was sent recently. Please check your inbox or wait a few minutes before trying again.";
             $message_type = "info";
        } else {
            // Generate new token if existing one is expired or not present
            $new_token = bin2hex(random_bytes(32));
            $new_token_expires = date('Y-m-d H:i:s', strtotime('+1 hour')); // Token valid for 1 hour

            $update_stmt = $conn->prepare("UPDATE users SET verification_token = ?, token_expires = ? WHERE id = ?");
            // This is the line that was causing the error if columns didn't exist
            $update_stmt->bind_param("ssi", $new_token, $new_token_expires, $user['id']);
            $update_stmt->execute();
            $update_stmt->close();

            // CHANGED: sendVerificationEmail parameter from $user['username'] to $user['name']
            if (sendVerificationEmail($user['email'], $user['name'], $new_token, $conn)) {
                $message = "A new verification email has been sent to " . htmlspecialchars($user['email']) . ". Please check your inbox.";
                $message_type = "success";
            } else {
                $message = "Could not send verification email. Please try again or contact support.";
                $message_type = "danger";
            }
        }
    } else {
        $message = "Account not found or already verified.";
        $message_type = "danger";
    }
    // Clear session variables after resend attempt to avoid resending on page refresh
    unset($_SESSION['unverified_email']);
    unset($_SESSION['unverified_username']);
}

require_once 'includes/header.php'; // Place header inclusion here after all PHP logic
?>

<div id="stars-container">
    <div id="stars1"></div>
    <div id="stars2"></div>
    <div id="stars3"></div>
</div>

<div class="container d-flex align-items-center justify-content-center" style="min-height: calc(100vh - 90px);">
    <div class="col-md-6 col-lg-5 col-xl-4">
        <div class="card form-card glassmorphism-card">
            <div class="card-body">
                <h2 class="card-title text-center mb-4">Login to Your Account</h2>

                <?php if (isset($_GET['signup']) && $_GET['signup'] == 'success'): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        Registration successful! Please check your email to verify your account.
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <?php foreach ($errors as $error): ?>
                            <p><?php echo $error; ?></p>
                        <?php endforeach; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <?php if ($message): // Display messages from verification checks ?>
                    <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show" role="alert">
                        <?php echo $message; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                    <?php if ($message_type == 'warning' && isset($_SESSION['unverified_email'])): // Show resend button only if unverified after login attempt ?>
                        <form action="login.php" method="POST" class="text-center mt-3">
                            <input type="hidden" name="resend_email" value="1">
                            <button type="submit" class="btn btn-sm btn-info">Resend Verification Email</button>
                        </form>
                    <?php endif; ?>
                <?php endif; ?>

                <form action="login.php" method="post">
                    <div class="mb-3">
                        <label for="email" class="form-label">Email Address</label>
                        <input type="email" class="form-control" id="email" name="email"
                            value="<?php echo htmlspecialchars($email_input); ?>" required>
                    </div>
                    <div class="mb-3">
                        <label for="password" class="form-label">Password</label>
                        <input type="password" class="form-control" id="password" name="password" required>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">Login</button>
                    <p class="text-center mt-3">
                        Don't have an account? <a href="signup.php">Sign up here</a>.
                    </p>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="assets/js/script.js"></script>