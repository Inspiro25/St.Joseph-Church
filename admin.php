<?php
// Start the session and check if the admin is logged in
session_start();
if (!isset($_SESSION['username'])) {
    header('Location: login.php');
    exit();
}

// Database connection
$conn = new mysqli('mysql-mariadb-sea01-10-101.zap-hosting.com', 'zap1140889-5', 'CxldZLjAeYIhuk68', 'zap1140889-5');
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Handle user actions
$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_user'])) {
        $username = $_POST['username'];
        $password = $_POST['password'];
        $query = "INSERT INTO users (username, password) VALUES ('$username', '$password')";
        if ($conn->query($query)) {
            $message = 'User added successfully.';
        } else {
            $message = 'Error adding user.';
        }
    } elseif (isset($_POST['edit_user'])) {
        $id = $_POST['id'];
        $username = $_POST['username'];
        $password = $_POST['password'];
        $query = "UPDATE users SET username = '$username', password = '$password' WHERE id = $id";
        if ($conn->query($query)) {
            $message = 'User updated successfully.';
        } else {
            $message = 'Error updating user.';
        }
    } elseif (isset($_POST['delete_user'])) {
        $id = $_POST['id'];
        $query = "DELETE FROM users WHERE id = $id";
        if ($conn->query($query)) {
            $message = 'User deleted successfully.';
        } else {
            $message = 'Error deleting user.';
        }
    }
}

// Fetch all users
$result = $conn->query("SELECT * FROM users");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <h2>Admin Panel</h2>
        <p>Welcome, <?= htmlspecialchars($_SESSION['username']); ?>! <a href="logout.php">Logout</a></p>

        <h3>Manage Users</h3>
        <?php if ($message): ?>
            <p style="color: green;"><?= $message ?></p>
        <?php endif; ?>

        <!-- Add User Form -->
        <form method="POST" action="admin.php">
            <h4>Add User</h4>
            <input type="text" name="username" placeholder="Username" required>
            <input type="password" name="password" placeholder="Password" required>
            <button type="submit" name="add_user">Add User</button>
        </form>

        <!-- User List -->
        <h4>Existing Users</h4>
        <table border="1" cellpadding="10">
            <tr>
                <th>ID</th>
                <th>Username</th>
                <th>Actions</th>
            </tr>
            <?php while ($row = $result->fetch_assoc()): ?>
            <tr>
                <td><?= $row['id'] ?></td>
                <td><?= htmlspecialchars($row['username']) ?></td>
                <td>
                    <form method="POST" action="admin.php" style="display:inline;">
                        <input type="hidden" name="id" value="<?= $row['id'] ?>">
                        <input type="text" name="username" value="<?= htmlspecialchars($row['username']) ?>" required>
                        <input type="password" name="password" placeholder="New Password" required>
                        <button type="submit" name="edit_user">Edit</button>
                    </form>
                    <form method="POST" action="admin.php" style="display:inline;">
                        <input type="hidden" name="id" value="<?= $row['id'] ?>">
                        <button type="submit" name="delete_user">Delete</button>
                    </form>
                </td>
            </tr>
            <?php endwhile; ?>
        </table>
    </div>
</body>
</html>
