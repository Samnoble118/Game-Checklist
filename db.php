<?php

// Load in the .env file
$env = parse_ini_file(__DIR__ . "/.env");

// If the file can't be found
if (!$env) {
    die("Error loading environment file.");
}

// Database variables - use $env instead of $_ENV
$database = $env["DB_NAME"];
$username = $env["DB_USERNAME"];
$password = $env["DB_PASSWORD"];

// Create MySQL connection
$conn = new mysqli("localhost", $username, $password, $database);

// Check if the DB connected
if ($conn->connect_error) {
    die("Failed to connect: " . $conn->connect_error);
}

// echo "Database connected successfully.";
?>
