<?php
require_once '../includes/db.php'; // Go up one directory to find db.php
$error_message = '';
// This file uses its own session logic, so no header is included at the top
if (session_status() === PHP_SESSION_NONE) { session_start(); }
if (isset($_SESSION['employee_loggedin'])) { header('Location: dashboard.php'); exit; }

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    if (!empty($email) && !empty($password)) {
        $stmt = $conn->prepare("SELECT id, name, password FROM employees WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows === 1) {
            $employee = $result->fetch_assoc();
            if (password_verify($password, $employee['password'])) {
                session_regenerate_id();
                $_SESSION['employee_loggedin'] = true;
                $_SESSION['employee_id'] = $employee['id'];
                $_SESSION['employee_name'] = $employee['name'];
                header('Location: dashboard.php');
                exit;
            }
        }
    }
    $error_message = 'Invalid email or password.';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <!-- FIX: Added viewport meta tag for mobile responsiveness -->
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Employee Login</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- FIX: Adjusted margin for better mobile view -->
    <style> .login-container { max-width: 450px; margin: 50px auto; padding: 30px; border: 1px solid #ddd; border-radius: 10px; background-color: #f8f9fa; } </style>
</head>
<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-6 col-lg-5">
                <div class="login-container">
                    <h2 class="text-center mb-4">Employee Login</h2>
                    <?php if(!empty($error_message)): ?>
                        <div class="alert alert-danger"><?php echo $error_message; ?></div>
                    <?php endif; ?>
                    <form action="index.php" method="post">
                        <div class="mb-3">
                            <label for="email" class="form-label">Email Address</label>
                            <input type="email" name="email" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label for="password" class="form-label">Password</label>
                            <input type="password" name="password" class="form-control" required>
                        </div>
                        <button type="submit" class="btn btn-primary w-100">Login</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
