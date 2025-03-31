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
    // Handle adding a new game
    if (isset($_POST["add_game"])) {
        $game_name = trim($_POST["game"]);
        $platform = trim($_POST["platform"]);
        $release_year = trim($_POST["year"]);

        if ($platform === "Other" && isset($_POST["other_platform"])) {
            $platform = trim($_POST["other_platform"]);
        }

        $status = isset($_POST["owned"]) ? 1 : (isset($_POST["wishlist"]) ? 0 : null);
        if ($status === null) {
            echo "Please select either 'Owned' or 'Wishlist'.";
            exit;
        }

        // Check if game exists
        $stmt = $conn->prepare("SELECT id FROM games WHERE title = ? AND platform = ?");
        $stmt->bind_param("ss", $game_name, $platform);
        $stmt->execute();
        $stmt->bind_result($game_id);
        $stmt->fetch();
        $stmt->close();

        if (!$game_id) {
            $stmt = $conn->prepare("INSERT INTO games (title, platform, release_year) VALUES (?, ?, ?)");
            $stmt->bind_param("ssi", $game_name, $platform, $release_year);
            $stmt->execute();
            $game_id = $stmt->insert_id;
            $stmt->close();
        }

        // Check if user already owns this game
        $stmt = $conn->prepare("SELECT id FROM user_games WHERE game_id = ? AND user_id = ?");
        $stmt->bind_param("ii", $game_id, $user_id);
        $stmt->execute();
        $stmt->bind_result($existing_entry);
        $stmt->fetch();
        $stmt->close();

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

    // Handle deleting a game
    if (isset($_POST["delete_game"])) {
        $game_id_to_delete = $_POST["delete_game"];
        $stmt = $conn->prepare("DELETE FROM user_games WHERE game_id = ? AND user_id = ?");
        $stmt->bind_param("ii", $game_id_to_delete, $user_id);
        $stmt->execute();
        $stmt->close();
        echo "Game deleted successfully!";
    }

    // Handle toggling game status (Owned <=> Wishlist)
    if (isset($_POST["toggle_status"])) {
        $game_id_to_toggle = $_POST["toggle_status"];
        $stmt = $conn->prepare("SELECT status FROM user_games WHERE game_id = ? AND user_id = ?");
        $stmt->bind_param("ii", $game_id_to_toggle, $user_id);
        $stmt->execute();
        $stmt->bind_result($current_status);
        $stmt->fetch();
        $stmt->close();

        $new_status = ($current_status === 1) ? 0 : 1;
        $stmt = $conn->prepare("UPDATE user_games SET status = ? WHERE game_id = ? AND user_id = ?");
        $stmt->bind_param("iii", $new_status, $game_id_to_toggle, $user_id);
        $stmt->execute();
        $stmt->close();
        echo "Game status updated successfully!";
    }

    // Handle editing a game
    if (isset($_POST["edit_game"])) {
        $game_id_to_edit = isset($_POST["game_id"]) ? (int) $_POST["game_id"] : 0;
        if ($game_id_to_edit === 0) {
            echo "Error: Invalid game ID.";
            exit();
        }

        $new_name = trim($_POST["new_name"]);
        $new_platform = trim($_POST["new_platform"]);
        $new_release_year = (int) trim($_POST["new_year"]);

        if (empty($new_name) || empty($new_platform) || empty($new_release_year)) {
            echo "Error: Missing required fields.";
            exit();
        }

        // Ensure the game exists before updating
        $stmt = $conn->prepare("SELECT id FROM games WHERE id = ?");
        $stmt->bind_param("i", $game_id_to_edit);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows === 0) {
            echo "Error: Game not found.";
            exit();
        }
        $stmt->close();

        // Update game details
        $stmt = $conn->prepare("UPDATE games SET title = ?, platform = ?, release_year = ? WHERE id = ?");
        $stmt->bind_param("ssii", $new_name, $new_platform, $new_release_year, $game_id_to_edit);
        $stmt->execute();
        echo ($stmt->affected_rows > 0) ? "Game updated successfully!" : "No changes made.";
        $stmt->close();
    }
}

// Fetch user's games
$stmt = $conn->prepare("
    SELECT g.id, g.title, g.platform, g.release_year, ug.status 
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

// Check if a game is being edited
$edit_game = null;
if (isset($_GET["edit_game"])) {
    $game_id = $_GET["edit_game"];
    $stmt = $conn->prepare("SELECT title, platform, release_year FROM games WHERE id = ?");
    $stmt->bind_param("i", $game_id);
    $stmt->execute();
    $stmt->bind_result($title, $platform, $release_year);
    $stmt->fetch();
    $stmt->close();

    $edit_game = [
        "id" => $game_id,
        "title" => $title,
        "platform" => $platform,
        "release_year" => $release_year
    ];
}
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

    <!-- Add New Game Form -->
    <form action="" method="post">
        <label>Game Name</label>
        <input name="game" type="text" required />

        <label>Platform</label>
        <select name="platform" id="platform" required>
            <option value="Nintendo Switch">Nintendo Switch</option>    
            <option value="Nintendo DS">Nintendo DS</option>    
            <option value="GameCube">GameCube</option>    
            <option value="Nintendo WII">Nintendo WII</option>    
            <option value="Nintendo WII U">Nintendo WII U</option>    
            <option value="PC">PC</option>
            <option value="PlayStation">PlayStation</option>
            <option value="PlayStation 2">PlayStation 2</option>
            <option value="PlayStation 3">PlayStation 3</option>
            <option value="PlayStation 4">PlayStation 4</option>
            <option value="PlayStation 5">PlayStation 5</option>
            <option value="Xbox">Xbox</option>
            <option value="Other">Other</option>
        </select>

        <div id="other-platform" style="display: none;">
            <label for="other_platform">Please specify the platform</label>
            <input name="other_platform" type="text" id="other_platform" />
        </div>

        <label>Release Year</label>
        <input name="year" type="number" min="1980" max="2030" required />

        <label>Status</label>
        <input type="radio" name="owned" value="1" /> <label>Owned</label>
        <input type="radio" name="wishlist" value="0" /> <label>Wishlist</label>

        <input type="submit" name="add_game" value="Add Game" />
    </form>

    <script>
        document.getElementById("platform").addEventListener("change", function() {
            var otherPlatformField = document.getElementById("other-platform");
            if (this.value === "Other") {
                otherPlatformField.style.display = "block";
            } else {
                otherPlatformField.style.display = "none";
            }
        });
    </script>

    <!-- Edit Game Form -->
    <?php if ($edit_game): ?>
    <h2>Edit Game</h2>
    <form action="" method="post">
        <input type="hidden" name="game_id" value="<?= $edit_game['id'] ?>" />

        <label>Game Name</label>
        <input name="new_name" type="text" value="<?= htmlspecialchars($edit_game['title']) ?>" required />

        <label>Platform</label>
        <select name="new_platform" required>
            <option value="Nintendo Switch" <?= $edit_game['platform'] === "Nintendo Switch" ? "selected" : "" ?>>Nintendo Switch</option>
            <option value="Nintendo DS" <?= $edit_game['platform'] === "Nintendo DS" ? "selected" : "" ?>>Nintendo DS</option>
            <option value="GameCube" <?= $edit_game['platform'] === "GameCube" ? "selected" : "" ?>>GameCube</option>
            <option value="Nintendo WII" <?= $edit_game['platform'] === "Nintendo WII" ? "selected" : "" ?>>Nintendo WII</option>
            <option value="Nintendo WII U" <?= $edit_game['platform'] === "Nintendo WII U" ? "selected" : "" ?>>Nintendo WII U</option>
            <option value="PC" <?= $edit_game['platform'] === "PC" ? "selected" : "" ?>>PC</option>
            <option value="PlayStation" <?= $edit_game['platform'] === "PlayStation" ? "selected" : "" ?>>PlayStation</option>
            <option value="PlayStation 2" <?= $edit_game['platform'] === "PlayStation 2" ? "selected" : "" ?>>PlayStation 2</option>
            <option value="PlayStation 3" <?= $edit_game['platform'] === "PlayStation 3" ? "selected" : "" ?>>PlayStation 3</option>
            <option value="PlayStation 4" <?= $edit_game['platform'] === "PlayStation 4" ? "selected" : "" ?>>PlayStation 4</option>
            <option value="PlayStation 5" <?= $edit_game['platform'] === "PlayStation 5" ? "selected" : "" ?>>PlayStation 5</option>
            <option value="Xbox" <?= $edit_game['platform'] === "Xbox" ? "selected" : "" ?>>Xbox</option>
            <option value="Other" <?= $edit_game['platform'] === "Other" ? "selected" : "" ?>>Other</option>
        </select>

        <label>Release Year</label>
        <input name="new_year" type="number" min="1980" max="2030" value="<?= $edit_game['release_year'] ?>" required />

        <input type="submit" name="edit_game" value="Save Changes" />
    </form>
    <?php endif; ?>

    <!-- Display Owned Games -->
    <h2>🎮 Owned Games</h2>
    <ul>
        <?php foreach ($user_games as $game): ?>
            <?php if ((int)$game['status'] === 1): ?>
                <li>
                    <?= htmlspecialchars($game['title']) ?> (<?= htmlspecialchars($game['platform']) ?>, <?= $game['release_year'] ?>)
                    <form action="" method="post" style="display:inline;">
                        <button type="submit" name="toggle_status" value="<?= $game['id'] ?>">Move to Wishlist</button>
                    </form>
                    <form action="" method="post" style="display:inline;">
                        <button type="submit" name="delete_game" value="<?= $game['id'] ?>">Delete</button>
                    </form>
                    <form action="" method="get" style="display:inline;">
                        <button type="submit" name="edit_game" value="<?= $game['id'] ?>">Edit</button>
                    </form>
                </li>
            <?php endif; ?>
        <?php endforeach; ?>
    </ul>

    <!-- Display Wishlist Games -->
    <h2>🌟 Wishlist</h2>
    <ul>
        <?php foreach ($user_games as $game): ?>
            <?php if ((int)$game['status'] === 0): ?>
                <li>
                    <?= htmlspecialchars($game['title']) ?> (<?= htmlspecialchars($game['platform']) ?>, <?= $game['release_year'] ?>)
                    <form action="" method="post" style="display:inline;">
                        <button type="submit" name="toggle_status" value="<?= $game['id'] ?>">Move to Owned</button>
                    </form>
                    <form action="" method="post" style="display:inline;">
                        <button type="submit" name="delete_game" value="<?= $game['id'] ?>">Delete</button>
                    </form>
                    <form action="" method="get" style="display:inline;">
                        <button type="submit" name="edit_game" value="<?= $game['id'] ?>">Edit</button>
                    </form>
                </li>
            <?php endif; ?>
        <?php endforeach; ?>
    </ul>
</body>
</html>

