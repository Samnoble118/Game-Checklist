<?php
include "db.php";

session_start();

// Only logged-in users can see this page
if (!isset($_SESSION["user_id"])) {
    echo "Please login to view this page";
    header("Location: login.php");
    exit();
}

$username = $_SESSION["username"];
$user_id = $_SESSION["user_id"];

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $game_name = trim($_POST["game"]);
    $platform = trim($_POST["platform"]);
    $release_year = trim($_POST["year"]);
    $status = isset($_POST["owned"]) ? 1 : 0;

    // Step 1: Check if the game already exists
    $stmt = $conn->prepare("SELECT id FROM games WHERE title = ? AND platform = ?");
    $stmt->bind_param("ss", $game_name, $platform);
    $stmt->execute();
    $stmt->bind_result($game_id);
    $stmt->fetch();
    $stmt->close();

    // Step 2: If game doesn't exist, insert it
    if (!$game_id) {
        $stmt = $conn->prepare("INSERT INTO games (title, platform, release_year) VALUES (?, ?, ?)");
        $stmt->bind_param("ssi", $game_name, $platform, $release_year);
        $stmt->execute();
        $game_id = $stmt->insert_id;
        $stmt->close();
    }

    // Step 3: Check if the game is already in user_games
    $stmt = $conn->prepare("SELECT id FROM user_games WHERE game_id = ? AND user_id = ?");
    $stmt->bind_param("ii", $game_id, $user_id);
    $stmt->execute();
    $stmt->bind_result($existing_entry);
    $stmt->fetch();
    $stmt->close();

    // Step 4: If the game is NOT already in user_games, insert it
    if (!$existing_entry) {
        $stmt = $conn->prepare("INSERT INTO user_games (game_id, user_id, status, added_at) VALUES (?, ?, ?, NOW())");
        $stmt->bind_param("iii", $game_id, $user_id, $status);
        $stmt->execute();
        $stmt->close();
        echo "Game added successfully!";
    } else {
        echo "This game is already in your collection!";
    }
}

// Fetch the user's games
$stmt = $conn->prepare("
    SELECT g.title, g.platform, g.release_year, ug.status 
    FROM user_games ug 
    JOIN games g ON ug.game_id = g.id 
    WHERE ug.user_id = ?
    ORDER BY g.title
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user_games = [];

while ($row = $result->fetch_assoc()) {
    $user_games[] = $row;
}

$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Game Checklist</title>
</head>
<body>
    <h1>Game Checklist</h1>
    <?= "<h2>$username's Games:</h2>" ?>

    <form action="" method="post">
        <label>Game Name</label>
        <input name="game" type="text" required />

        <label>Platform</label>
        <select name="platform" required>
            <option value="PC">PC</option>
            <option value="PlayStation">PlayStation</option>
            <option value="Xbox">Xbox</option>
            <option value="Nintendo Switch">Nintendo Switch</option>
        </select>

        <label>Release Year</label>
        <input name="year" type="number" min="1980" max="2030" required />

        <label>Status</label>
        <input type="radio" name="owned" value="1" required /> <label>Owned</label>
        <input type="radio" name="owned" value="0" required /> <label>Wishlist</label>

        <input type="submit" value="Add Game" />
    </form>

    <h2>🎮 Owned Games</h2>
    <ul>
        <?php foreach ($user_games as $game): ?>
            <?php if ((int)$game['status'] === 1): ?>
                <li><?= htmlspecialchars($game['title']) ?> (<?= htmlspecialchars($game['platform']) ?>, <?= $game['release_year'] ?>)</li>
            <?php endif; ?>
        <?php endforeach; ?>
    </ul>

    <h2>🌟 Wishlist</h2>
    <ul>
        <?php foreach ($user_games as $game): ?>
            <?php if ($game['status'] == 0): ?>
                <li><?= htmlspecialchars($game['title']) ?> (<?= htmlspecialchars($game['platform']) ?>, <?= $game['release_year'] ?>)</li>
            <?php endif; ?>
        <?php endforeach; ?>
    </ul>
</body>
</html>
