<?php
// Name: [Your Name]
// Surname: [Your Surname]
// Student Number: u25135742
// COS216 PA3 - config.php
// Database connection and global configuration

$servername = "localhost";
$username   = "u25135742";
$password   = "ZVSEEWIVPQOD6PYSERI6ZNO7FN7JN547";
$dbname     = "u25135742_Flights";

// Create database connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    // We can't use sendError() here since we're not inside the class yet.
    // Output a raw JSON error and die - this covers the case where api.php includes this.
    http_response_code(500);
    echo json_encode(array(
        "status"    => "error",
        "timestamp" => (string) round(microtime(true) * 1000),
        "data"      => "Database connection failed."
    ));
    exit;
}

// Force UTF-8 character set for all queries
$conn->set_charset("utf8mb4");

// Global API URL constant (used by frontend PHP pages to know where the API lives)
if (!defined('API_URL')) {
    define('API_URL', 'https://wheatley.cs.up.ac.za/u25135742/api.php');
}

// NOTE: The X-Content-Type-Options header has been moved to api.php
// so it doesn't interfere with header ordering when config is included elsewhere.
?>