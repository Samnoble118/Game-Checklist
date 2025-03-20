<?php

// Include the database
include "db.php";

// Start the session
session_start();

// Check if the user is logged in
if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit();
}

// Get the username from the session
$username = $_SESSION["username"] ?? 'Guest';

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>
</head>
<body>
    <h1>Gaming Library Checklist</h1>
    <?= "<h3>Welcome $username</h3>" ?>
    <ul>
        <li><a href="add_game.php">Add New Game</a></li>
        <li><a href="view_games.php">View Games</a></li>
        <li><a href="logout.php">Logout</a></li>
    </ul>
    
</body>
</html>
