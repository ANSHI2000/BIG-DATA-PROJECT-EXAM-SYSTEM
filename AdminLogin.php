<?php
session_start();
require_once 'db_connect.php'; // Your database connection file

// Hardcoded admin credentials
$adminUsername = 'admin';
$adminPasswordHash = hash('sha256', 'admin123');

// Handle logout
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: admin_portal.php");
    exit();
}

// Handle login
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    $inputUser = $_POST['username'] ?? '';
    $inputPass = $_POST['password'] ?? '';

    if ($inputUser === $adminUsername && hash('sha256', $inputPass) === $adminPasswordHash) {
        $_SESSION['admin'] = true;
    } else {
        $loginError = "Invalid username or password!";
    }
}

// If admin is logged in, fetch scores
if (isset($_SESSION['admin'])) {
    $result = $conn->query("SELECT name, email, subject, score, total, date_taken FROM scores");
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Admin Portal</title>
    <style>
        body { font-family: Arial; background: #f0f0f0; padding: 40px; }
        .container { max-width: 800px; margin: auto; background: white; padding: 20px; box-shadow: 0 0 10px #ccc; }
        h2 { margin-bottom: 20px; }
        .form-group { margin-bottom: 15px; }
        .form-group input { width: 100%; padding: 8px; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { padding: 10px; border: 1px solid #ccc; text-align: center; }
        th { background: #007bff; color: white; }
        .logout { float: right; text-decoration: none; padding: 5px 10px; background: red; color: white; border-radius: 5px; }
        .error { color: red; margin-bottom: 10px; }
    </style>
</head>
<body>
<div class="container">
    <?php if (!isset($_SESSION['admin'])): ?>
        <h2>Admin Login</h2>
        <?php if (!empty($loginError)): ?>
            <div class="error"><?= $loginError ?></div>
        <?php endif; ?>
        <form method="post">
            <div class="form-group">
                <label>Username</label><br>
                <input type="text" name="username" required>
            </div>
            <div class="form-group">
                <label>Password</label><br>
                <input type="password" name="password" required>
            </div>
            <button type="submit" name="login">Login</button>
        </form>

    <?php else: ?>
        <h2>
            Admin Dashboard
            <a href="?logout=1" class="logout">Logout</a>
        </h2>
        <table>
            <tr>
                <th>Name</th>
                <th>Email</th>
                <th>Subject</th>
                <th>Score</th>
                <th>Total</th>
                <th>Date Taken</th>
            </tr>
            <?php if ($result && $result->num_rows > 0): ?>
                <?php while($row = $result->fetch_assoc()): ?>
                    <tr>
                        <td><?= htmlspecialchars($row['name']) ?></td>
                        <td><?= htmlspecialchars($row['email']) ?></td>
                        <td><?= htmlspecialchars($row['subject']) ?></td>
                        <td><?= $row['score'] ?></td>
                        <td><?= $row['total'] ?></td>
                        <td><?= $row['date_taken'] ?></td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr><td colspan="6">No records found</td></tr>
            <?php endif; ?>
        </table>
    <?php endif; ?>
</div>
</body>
</html>
