<?php
// php/auth/session_check.php
require_once '../config.php';

if (isset($_SESSION['user_id']) && isset($_SESSION['user_name'])) {
    echo json_encode([
        'loggedIn' => true, 
        'name' => $_SESSION['user_name'],
        'role' => $_SESSION['user_role'] ?? 'seller'
    ]);
} else {
    echo json_encode(['loggedIn' => false]);
}
?>