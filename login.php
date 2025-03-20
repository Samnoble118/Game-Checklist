<?php

//Include DB
include "db.php";

//start the session
session_start();

if ($_SERVER["REQUEST_METHOD"] === "POST"){
    $input = htmlspecialchars(trim($_POST["email_or_username"] ?? ''));
    $password = trim($_POST["password"] ?? '');    

    //check if the fields are empty
    if (empty($input) || empty($password)){
        die("Username/Email and Password are required");
    }

    // Prepare the statement
    $stmt = $conn->prepare("SELECT id, password, username FROM users WHERE email = ? OR username = ?");
    $stmt->bind_param("ss", $input, $input);
    $stmt->execute();
    $stmt->store_result();

    //Check if the user exists
    if ($stmt->num_rows > 0){
        $stmt->bind_result($id, $hashed_password, $username);
        $stmt->fetch();

        //verify the password
        if(password_verify($password, $hashed_password)){
            $_SESSION["user_id"] = $id;
            $_SESSION["username"] = $username;
            header("Location: dashboard.php");
            exit();
        } else {
            echo "Invalid password. Please try again.";
        }
    } else {
        echo "User not found";
    }

    $stmt->close();
    $conn->close();
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
</head>
<body>
    <h1>Login</h1>
    <h2>Please Log into your account</h2>
    <form action="" method="post">
        <label>Username or Email</label>
        <input type="text" name="email_or_username" placeholder="Please enter your username or email address" required>
        <label>Password</label>
        <input type="password" name="password" placeholder="Please enter your password" required>
        <input type="submit" value="login">
        <button>Forgot your password?</button>
        <button>Already have an account?</button>
    </form>
    
</body>
</html>