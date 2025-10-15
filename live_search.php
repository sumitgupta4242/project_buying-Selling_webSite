<?php
require('includes/db.php');

$suggestions = [];

// Check if the 'query' GET parameter is set and not empty
if (isset($_GET['query']) && !empty($_GET['query'])) {
    $searchTerm = trim($_GET['query']);

    // Prepare a statement to prevent SQL injection
    // We use "LIKE ?" to find titles that START WITH the search term
    // We limit the results to 5 for better performance
    $stmt = $conn->prepare("SELECT title FROM projects WHERE title LIKE ? LIMIT 5");
    
    // The '%' is a wildcard character
    $likeSearchTerm = $searchTerm . "%";
    $stmt->bind_param("s", $likeSearchTerm);
    
    $stmt->execute();
    $result = $stmt->get_result();

    // Fetch the results into an array
    while ($row = $result->fetch_assoc()) {
        $suggestions[] = $row['title'];
    }

    $stmt->close();
}

$conn->close();

// Set the header to indicate the response is JSON
header('Content-Type: application/json');

// Encode the suggestions array as JSON and echo it
echo json_encode($suggestions);
?>