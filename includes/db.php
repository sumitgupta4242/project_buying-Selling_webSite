<?php
// --- Database Configuration ---
define('DB_HOST', 'localhost:3307');
define('DB_USER', 'root'); // Your database username
define('DB_PASS', '');     // Your database password
define('DB_NAME', 'project_store'); // Your database name

// --- Establish Connection ---
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

// --- Check Connection ---
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Start the session on every page that includes this file
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>