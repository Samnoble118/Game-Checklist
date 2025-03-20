<?php
//Include the DB connection
include "db.php";

//form variables after the form has been submitted 
if ($_SERVER["REQUEST_METHOD"] === "POST"){
    //Get the form data
    $username = trim($_POST["username"] ?? '');
    $email = trim($_POST["email"] ?? '');
    $password = $_POST["password"] ?? '';

    //Make sure fields are filled
    if (empty($username) || empty($email) || empty($password)){
        die("All fields are required!");
    }

    //Secure the password
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    //Check if the user exists
    $check_query = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $check_query->bind_param("s", $email);
    $check_query->execute();
    $check_query->store_result();

    if ($check_query->num_rows > 0){
        die("User already exists, Please log in");
    }

    $check_query->close();

    //store user in database
    $stmt = $conn->prepare("INSERT INTO users (username, email, password) VALUES (?, ?, ?)");
    $stmt->bind_param("sss", $username, $email, $hashed_password);

    if ($stmt->execute()){
        echo "Registration successful! <a href='login.php'>Login here</a>";
    } else {
        echo "Error: " . $stmt->error;
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
    <title>Register</title>
</head>
<body>
    <h1>Sign Up</h1>
    <form method="post" action="">
        <input type="text" name="username" placeholder="Create a username" required>
        <input type="email" name="email" placeholder="Provide your email address" required>
        <input type="password" name="password">
        <input type="submit">
    </form>
    <button onclick="window.location.href='login.php'">Already have an account</button>
    <button onclick="window.location.href='forgot_password.php'">Forgot password</button>
    
</body>
</html>