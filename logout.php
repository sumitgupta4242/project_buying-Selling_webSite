<?php
session_start(); // Access the existing session
session_unset(); // Unset all session variables
session_destroy(); // Destroy the session
header('Location: index.php'); // Redirect to the homepage
exit();
?>