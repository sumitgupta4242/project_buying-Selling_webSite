<?php
session_start(); // Ensure session is started at the very beginning
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
$name_input = ''; // To preserve values in the form
$email_input = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name_input = trim($_POST['name']); // Changed from username to name
    $email_input = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password']; // Assuming you'll add this to your form for better UX

    // Basic validation
    if (empty($name_input)) {
        $errors[] = 'Full Name is required.';
    }
    if (empty($email_input) || !filter_var($email_input, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'A valid email is required.';
    }
    if (empty($password)) {
        $errors[] = 'Password is required.';
    }
    // Added confirmation password validation
    if ($password !== $confirm_password) {
        $errors[] = "Passwords do not match.";
    }
    if (strlen($password) < 6) {
        $errors[] = 'Password must be at least 6 characters long.';
    }

    // Check if email already exists
    $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->bind_param("s", $email_input);
    $stmt->execute();
    $stmt->store_result();
    if ($stmt->num_rows > 0) {
        $errors[] = 'An account with this email already exists.';
    }
    $stmt->close();

    // If no errors, create the user
    if (empty($errors)) {
        // Generate verification token
        $verification_token = bin2hex(random_bytes(32)); // 64-character hex string
        $token_expires = date('Y-m-d H:i:s', strtotime('+1 hour')); // Token valid for 1 hour

        // Hash the password for security
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        // Insert new user into database, with verification fields
        // Ensure your 'users' table has 'username' column, or change to 'name' if you prefer
        // I'm using 'username' as per previous discussion, assuming 'name' input maps to 'username' in DB
        $stmt = $conn->prepare("INSERT INTO users (name, email, password, is_verified, verification_token, token_expires) VALUES (?, ?, ?, 0, ?, ?)");
        $stmt->bind_param("sssss", $name_input, $email_input, $hashed_password, $verification_token, $token_expires);
        
        if ($stmt->execute()) {
            // Send verification email
            $mail = new PHPMailer(true);
            try {
                //Server settings
                $mail->isSMTP();
                $mail->Host       = SMTP_HOST;
                $mail->SMTPAuth   = true;
                $mail->Username   = SMTP_USERNAME;
                $mail->Password   = SMTP_PASSWORD;
                $mail->SMTPSecure = SMTP_SECURE;
                $mail->Port       = SMTP_PORT;

                //Recipients
                $mail->setFrom(SMTP_USERNAME, SITE_NAME . ' Support');
                $mail->addAddress($email_input, $name_input); // Use name_input for recipient name

                //Content
                $mail->isHTML(true);
                $mail->Subject = SITE_NAME . ' - Verify Your Email Address';
                $verification_link = BASE_URL . '/verify.php?token=' . $verification_token;
                $mail->Body    = '
                    <p>Hello ' . htmlspecialchars($name_input) . ',</p>
                    <p>Thank you for registering with ' . SITE_NAME . '.</p>
                    <p>Please click the link below to verify your email address:</p>
                    <p><a href="' . htmlspecialchars($verification_link) . '">' . htmlspecialchars($verification_link) . '</a></p>
                    <p>This link will expire in 1 hour.</p>
                    <p>If you did not register for an account, please ignore this email.</p>
                    <p>Regards,</p>
                    <p>' . SITE_NAME . ' Team</p>
                ';
                $mail->AltBody = 'Hello ' . htmlspecialchars($name_input) . ', Thank you for registering with ' . SITE_NAME . '. Please copy and paste the link below into your browser to verify your email address: ' . htmlspecialchars($verification_link) . ' This link will expire in 1 hour. If you did not register for an account, please ignore this email. Regards, ' . SITE_NAME . ' Team';

                $mail->send();

                // Redirect to login page with a success message for verification
                header('Location: login.php?signup=success');
                exit();

            } catch (Exception $e) {
                // Log the error for debugging, but show a user-friendly message
                error_log("Verification email could not be sent. Mailer Error: {$mail->ErrorInfo}");
                $errors[] = "Registration successful, but the verification email could not be sent. Please contact support.";
                $message_type = "warning"; // Use warning for email issue
            }
        } else {
            $errors[] = 'Something went wrong. Please try again: ' . $stmt->error;
            $message_type = "danger";
        }
        $stmt->close();
    }
    // If there were errors (validation or email sending), don't redirect, display them on the page
    // $conn->close(); // Close connection here if not redirecting, otherwise it will be closed by header
}

// Reopen connection if it was closed prematurely and needed for header/footer
if (!$conn->ping()) {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }
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
                <h2 class="card-title text-center mb-4">Create an Account</h2>

                <?php
                // Display general messages (like email sending failure) or errors
                if ($message && empty($errors)) : ?>
                    <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show" role="alert">
                        <?php echo $message; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php elseif (!empty($errors)): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <?php foreach ($errors as $error): ?>
                            <p><?php echo $error; ?></p>
                        <?php endforeach; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <form action="signup.php" method="post">
                    <div class="mb-3">
                        <label for="name" class="form-label">Full Name</label>
                        <input type="text" class="form-control" id="name" name="name" value="<?php echo htmlspecialchars($name_input); ?>" required>
                    </div>
                    <div class="mb-3">
                        <label for="email" class="form-label">Email Address</label>
                        <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($email_input); ?>" required>
                    </div>
                    <div class="mb-3">
                        <label for="password" class="form-label">Password</label>
                        <input type="password" class="form-control" id="password" name="password" required>
                    </div>
                    <div class="mb-3">
                        <label for="confirm_password" class="form-label">Confirm Password</label>
                        <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">Sign Up</button>
                    <p class="text-center mt-3">
                        Already have an account? <a href="login.php">Login here</a>.
                    </p>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>