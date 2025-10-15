<?php
// php/config.php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
session_start();

// IMPORTANT: Update these values to match your MySQL setup
$db_host = 'localhost:3307';
$db_user = 'root';
$db_pass = '';
$db_name = 'seller_data'; // The new database name

$conn = new mysqli($db_host, $db_user, $db_pass, $db_name);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Set header to return JSON responses by default
header('Content-Type: application/json');
?>