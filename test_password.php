<?php
// Set the timezone to avoid potential warnings
date_default_timezone_set('Asia/Kolkata');

echo "<h1>Password Verification Test</h1>";
echo "Current Time: " . date('Y-m-d H:i:s') . "<br>";
echo "PHP Version: " . phpversion() . "<br><br>";

// These are the exact credentials we want to test
$password_we_are_checking = '1234';
$correct_hash_from_sql = '$2y$10$tJ0p/L5n4j.yqP6uYv8k9eE2KzB/4N2gW8.Z.r6tP.Z2oD3eW8uG.';

echo "<b>Password to check:</b> '" . $password_we_are_checking . "'<br>";
echo "<b>Hash from database:</b> '" . $correct_hash_from_sql . "'<br><br>";

echo "<hr>";
echo "<h3>Running password_verify()...</h3>";

// The core test
if (password_verify($password_we_are_checking, $correct_hash_from_sql)) {
    echo '<h2 style="color: green; font-family: sans-serif;">SUCCESS!</h2>';
    echo '<p>The password correctly matches the hash. This means your PHP environment is working perfectly.</p>';
    echo '<p>If this test passes but your login fails, it proves 100% that the hash in your `admins` table in the database is still incorrect.</p>';
} else {
    echo '<h2 style="color: red; font-family: sans-serif;">FAILURE!</h2>';
    echo '<p>The password does NOT match the hash.</p>';
    echo '<p>This is extremely rare and suggests a problem with your PHP installation. Please tell me your PHP version shown above.</p>';
}
?>