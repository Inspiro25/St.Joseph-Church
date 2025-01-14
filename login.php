<?php
// Start the session
session_start();

// Database connection
$conn = new mysqli('mysql-mariadb-sea01-10-101.zap-hosting.com', 'zap1140889-5', 'CxldZLjAeYIhuk68', 'zap1140889-5');
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Handle login
$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];

    $query = "SELECT * FROM users WHERE username = '$username' AND password = '$password'";
    $result = $conn->query($query);

    if ($result->num_rows === 1) {
        $_SESSION['username'] = $username;
        header('Location: admin.php');
        exit();
    } else {
        $message = 'Invalid credentials. Please try again.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <h2>Login</h2>
        <form method="POST" action="login.php">
            <input type="text" name="username" placeholder="Username" required>
            <input type="password" name="password" placeholder="Password" required>
            <button type="submit">Login</button>
        </form>
        <?php if ($message): ?>
            <p style="color: red;"><?= $message ?></p>
        <?php endif; ?>
    </div>
</body>
</html>
