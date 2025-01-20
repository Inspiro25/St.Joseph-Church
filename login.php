<?php
session_start(); // Start the session

// Handle logout
if (isset($_GET['logout'])) {
    session_unset();  // Clear all session variables
    session_destroy();  // Destroy the session
    header("Location: login.php");  // Redirect to the login page
    exit();  // Stop further code execution
}

// Database connection setup
$host = 'mysql-mariadb-sea01-10-101.zap-hosting.com';
$user = 'zap1140889-5';
$password = 'CxldZLjAeYIhuk68'; // Change this according to your MySQL password
$dbname = 'zap1140889-5';
$conn = new mysqli($host, $user, $password, $dbname);
if ($conn->connect_error) {
    die('Connection failed: ' . $conn->connect_error);
}

// Create tables if they don't exist
$sql = "CREATE TABLE IF NOT EXISTS users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(255) NOT NULL,
    password VARCHAR(255) NOT NULL,
    ward VARCHAR(255),
    unit VARCHAR(255),
    is_admin BOOLEAN DEFAULT 0
)";
$conn->query($sql);

$sql = "CREATE TABLE IF NOT EXISTS bills (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT,
    monthly_collection DECIMAL(10, 2) DEFAULT 0,
    work DECIMAL(10, 2) DEFAULT 0,
    funeral_charity_fund DECIMAL(10, 2) DEFAULT 0,
    cleaning DECIMAL(10, 2) DEFAULT 0,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
)";
$conn->query($sql);

// Function to sanitize input
function sanitize($input) {
    return htmlspecialchars(strip_tags($input));
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        $action = $_POST['action'];

        if ($action === 'register') {
            $username = sanitize($_POST['username']);
            $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
            $ward = sanitize($_POST['ward']);
            $unit = sanitize($_POST['unit']);

            $stmt = $conn->prepare('INSERT INTO users (username, password, ward, unit, is_admin) VALUES (?, ?, ?, ?, 0)');
            $stmt->bind_param('ssss', $username, $password, $ward, $unit);
            $stmt->execute();

            // Initialize bill for new user
            $user_id = $stmt->insert_id;
            $stmt = $conn->prepare('INSERT INTO bills (user_id, monthly_collection, work, funeral_charity_fund, cleaning) VALUES (?, 100.00, 50.00, 25.00, 75.00)');
            $stmt->bind_param('i', $user_id);
            $stmt->execute();

            echo "User registered successfully!";
        } elseif ($action === 'login') {
            $username = sanitize($_POST['username']);
            $password = $_POST['password'];
            $stmt = $conn->prepare('SELECT * FROM users WHERE username = ?');
            $stmt->bind_param('s', $username);
            $stmt->execute();
            $result = $stmt->get_result();
            $user = $result->fetch_assoc();

            if ($user && password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['is_admin'] = $user['is_admin'];
                echo "Logged in successfully!";
            } else {
                echo "Invalid username or password.";
            }
        } elseif ($action === 'update_bill' && isset($_SESSION['is_admin']) && $_SESSION['is_admin']) {
            $user_id = sanitize($_POST['user_id']);
            $monthly_collection = sanitize($_POST['monthly_collection']);
            $work = sanitize($_POST['work']);
            $funeral_charity_fund = sanitize($_POST['funeral_charity_fund']);
            $cleaning = sanitize($_POST['cleaning']);

            $stmt = $conn->prepare('UPDATE bills SET monthly_collection = ?, work = ?, funeral_charity_fund = ?, cleaning = ? WHERE user_id = ?');
            $stmt->bind_param('dddii', $monthly_collection, $work, $funeral_charity_fund, $cleaning, $user_id);
            $stmt->execute();

            echo "Bill updated successfully!";
        } elseif ($action === 'delete_user' && isset($_SESSION['is_admin']) && $_SESSION['is_admin']) {
            $user_id = sanitize($_POST['user_id']);

            // Delete the user's bills and then delete the user
            $stmt = $conn->prepare('DELETE FROM bills WHERE user_id = ?');
            $stmt->bind_param('i', $user_id);
            $stmt->execute();

            $stmt = $conn->prepare('DELETE FROM users WHERE id = ?');
            $stmt->bind_param('i', $user_id);
            $stmt->execute();

            echo "User and their bills have been deleted successfully!";
        }
    }
}

// Fetch user's bill or all bills for admin
$bills = [];
if (isset($_SESSION['user_id'])) {
    if ($_SESSION['is_admin']) {
        $result = $conn->query('SELECT bills.*, users.username FROM bills JOIN users ON bills.user_id = users.id');
        while ($row = $result->fetch_assoc()) {
            $bills[] = $row;
        }
    } else {
        $stmt = $conn->prepare('SELECT * FROM bills WHERE user_id = ?');
        $stmt->bind_param('i', $_SESSION['user_id']);
        $stmt->execute();
        $result = $stmt->get_result();
        $bills[] = $result->fetch_assoc();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bill System</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #141414;
            color: #ffffff;
            margin: 0;
            padding: 0;
        }

        .container {
            max-width: 1000px;
            margin: 0 auto;
            padding: 20px;
        }

        .header {
            text-align: center;
            margin-bottom: 20px;
        }

        .header h1 {
            color: #e50914;
        }

        .form-container {
            display: flex;
            justify-content: space-between;
        }

        .form-container form {
            width: 45%;
            padding: 20px;
            background-color: #333333;
            border-radius: 8px;
        }

        .form-container input[type="text"],
.form-container input[type="password"],
.form-container input[type="number"],
.form-container input[type="submit"] {
    width: 100%;  /* Ensures full width usage */
    padding: 10px;
    margin-bottom: 10px;
    border-radius: 5px;
    border: 1px solid #666666;
    background-color: #454545;
    color: #ffffff;
    box-sizing: border-box;  /* Ensure padding and border are part of the element's width */
}


        .form-container input[type="submit"] {
            background-color: #e50914;
            border: none;
            cursor: pointer;
        }

        .form-container input[type="submit"]:hover {
            background-color: #b81d24;
        }

        .logout-link {
            display: inline-block;
            margin-top: 20px;
            background-color: #e50914;
            color: white;
            padding: 10px 20px;
            border-radius: 5px;
            text-decoration: none;
        }

        .logout-link:hover {
            background-color: #b81d24;
        }

        .table-container {
            margin-top: 40px;
        }

        .table-container table {
            width: 100%;
            border-collapse: collapse;
            color: #ffffff;
        }

        .table-container th, .table-container td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #444444;
        }

        .table-container th {
            background-color: #222222;
        }

        .action-buttons input[type="submit"] {
            background-color: #e50914;
            color: white;
            cursor: pointer;
            padding: 5px 10px;
            border: none;
            border-radius: 5px;
        }

        .action-buttons input[type="submit"]:hover {
            background-color: #b81d24;
        }
        /* Mobile Adjustments */
@media (max-width: 768px) {
    .form-container {
        flex-direction: column;
        align-items: center;
    }

    .form-container form {
        width: 90%;  /* Set forms to 90% width on mobile */
        margin-bottom: 20px;  /* Space between the forms */
    }

    .header h1 {
        font-size: 24px;
    }

    .logout-link {
        width: 100%;
        text-align: center;
    }

    .table-container table {
        font-size: 14px;
    }

    .table-container th, .table-container td {
        padding: 10px;
    }
}
    </style>
</head>
<body>

<div class="container">
    <div class="header">
        <h1>Bill System</h1>
    </div>

    <?php if (!isset($_SESSION['user_id'])): ?>
        <div class="form-container">
            <!-- Register Form -->
            <form method="post">
                <h2>Register</h2>
                <input type="hidden" name="action" value="register">
                <input type="text" name="username" placeholder="Username" required>
                <input type="password" name="password" placeholder="Password" required>
                <input type="text" name="ward" placeholder="Ward" required>
                <input type="text" name="unit" placeholder="Unit" required>
                <input type="submit" value="Register">
            </form>

            <!-- Login Form -->
            <form method="post">
                <h2>Login</h2>
                <input type="hidden" name="action" value="login">
                <input type="text" name="username" placeholder="Username" required>
                <input type="password" name="password" placeholder="Password" required>
                <input type="submit" value="Login">
            </form>
        </div>

    <?php else: ?>
        <div class="table-container">
            <h2>Bills</h2>
            <table>
                <tr>
                    <?php if ($_SESSION['is_admin']): ?>
                        <th>Username</th>
                    <?php endif; ?>
                    <th>Monthly Collection</th>
                    <th>Work</th>
                    <th>Funeral Charity Fund</th>
                    <th>Cleaning</th>
                    <th>Total</th>
                    <?php if ($_SESSION['is_admin']): ?>
                        <th>Action</th>
                    <?php endif; ?>
                </tr>
                <?php foreach ($bills as $bill): ?>
                    <tr>
                        <?php if ($_SESSION['is_admin']): ?>
                            <td><?php echo isset($bill['username']) ? $bill['username'] : 'N/A'; ?></td>
                        <?php endif; ?>
                        <td><?php echo isset($bill['monthly_collection']) ? number_format($bill['monthly_collection'], 2) : '0.00'; ?></td>
                        <td><?php echo isset($bill['work']) ? number_format($bill['work'], 2) : '0.00'; ?></td>
                        <td><?php echo isset($bill['funeral_charity_fund']) ? number_format($bill['funeral_charity_fund'], 2) : '0.00'; ?></td>
                        <td><?php echo isset($bill['cleaning']) ? number_format($bill['cleaning'], 2) : '0.00'; ?></td>
                        <td>
                            <?php 
                                $total = (isset($bill['monthly_collection']) ? $bill['monthly_collection'] : 0) +
                                         (isset($bill['work']) ? $bill['work'] : 0) +
                                         (isset($bill['funeral_charity_fund']) ? $bill['funeral_charity_fund'] : 0) +
                                         (isset($bill['cleaning']) ? $bill['cleaning'] : 0);
                                echo number_format($total, 2);
                            ?>
                        </td>
                        <?php if ($_SESSION['is_admin']): ?>
                            <td class="action-buttons">
                                <form method="post">
                                    <input type="hidden" name="action" value="update_bill">
                                    <input type="hidden" name="user_id" value="<?php echo isset($bill['user_id']) ? $bill['user_id'] : ''; ?>">
                                    <input type="number" name="monthly_collection" value="<?php echo isset($bill['monthly_collection']) ? $bill['monthly_collection'] : 0; ?>" step="0.01" required>
                                    <input type="number" name="work" value="<?php echo isset($bill['work']) ? $bill['work'] : 0; ?>" step="0.01" required>
                                    <input type="number" name="funeral_charity_fund" value="<?php echo isset($bill['funeral_charity_fund']) ? $bill['funeral_charity_fund'] : 0; ?>" step="0.01" required>
                                    <input type="number" name="cleaning" value="<?php echo isset($bill['cleaning']) ? $bill['cleaning'] : 0; ?>" step="0.01" required>
                                    <input type="submit" value="Update">
                                </form>

                                <form method="post" onsubmit="return confirm('Are you sure you want to delete this user?');">
                                    <input type="hidden" name="action" value="delete_user">
                                    <input type="hidden" name="user_id" value="<?php echo isset($bill['user_id']) ? $bill['user_id'] : ''; ?>">
                                    <input type="submit" value="Delete" style="background-color: #d9534f; color: white;">
                                </form>
                            </td>
                        <?php endif; ?>
                    </tr>
                <?php endforeach; ?>
            </table>
        </div>

        <div class="button-wrapper">
            <a href="?logout" class="logout-link">Logout</a>
        </div>
    <?php endif; ?>
</div>

</body>
</html>
