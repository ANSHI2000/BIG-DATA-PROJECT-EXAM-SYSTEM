<?php
session_start();

// Hardcoded admin credentials
define("ADMIN_USER", "admin");
define("ADMIN_PASS_HASH", hash('sha256', 'admin123'));

// Handle logout
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: AuthPage.php");
    exit();
}

// Handle login POST request
$error = "";
$results = [];
$students = [];

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['login'])) {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    if ($username === ADMIN_USER && hash('sha256', $password) === ADMIN_PASS_HASH) {
        $_SESSION['admin'] = true;
    } else {
        $error = "Invalid username or password.";
    }
}

// Fetch data if admin is logged in
if (isset($_SESSION['admin'])) {
    $conn = new mysqli("localhost", "root", "password@123", "BigData");
    if ($conn->connect_error) {
        die("DB Connection failed: " . $conn->connect_error);
    }

    // Fetch scores
    $result = $conn->query("SELECT name, email, subject, score, total, date_taken FROM scores ORDER BY date_taken DESC");
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $results[] = $row;
        }
    }


    // Fetch students
    $res = $conn->query("SELECT  name, email FROM students ORDER BY name ASC");
    if ($res) {
        while ($row = $res->fetch_assoc()) {
            $students[] = $row;
        }
    }

    $conn->close();
}
?>


<!DOCTYPE html>
<html>
<head>
    <title>Admin Panel</title>
    <style>
        body {
           background: url('auto.jpg');
            background-size: cover;
            height: 100vh;
            padding: 40px;
        }
        .login-box, .dashboard {
            max-width: 500px;
            margin: auto;
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 0 10px #ccc;
        }
        input {
            width: 100%;
            padding: 10px;
            margin-top: 10px;
        }
        button {
            width: 100%;
            padding: 10px;
            background: #007BFF;
            color: white;
            border: none;
            margin-top: 15px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 30px;
            background: white;
            box-shadow: 0 0 10px #ccc;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 10px;
            text-align: center;
        }
        th {
            background-color: #007BFF;
            color: white;
        }
        .logout-btn {
            float: right;
            margin-top: -35px;
            background: #dc3545;
            padding: 8px 15px;
            border: none;
            color: white;
            cursor: pointer;
        }
        .error {
            color: red;
        }
    </style>
</head>
<body>

<div class="container">
    <?php if (!isset($_SESSION['admin'])): ?>
        <!-- Admin Login Page -->
        <div class="login-box">
            <h2>Admin Login</h2>
            <?php if (!empty($error)): ?>
                <p class="error"><?= $error ?></p>
            <?php endif; ?>
            <form method="post">
                <input type="text" name="username" placeholder="Username" required>
                <input type="password" name="password" placeholder="Password" required>
                <button type="submit" name="login">Login</button>
            </form>
        </div>

    <?php else: ?>
        <!-- Admin Dashboard -->
        <div class="dashboard">
            <h2>Welcome Admin</h2>
            <div class="section-title">📚 Student Details</div>
            <table>
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Email</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (!empty($students)): ?>
                    <?php foreach ($students as $student): ?>
                        <tr>
                            <td><?= htmlspecialchars($student['name']) ?></td>
                            <td><?= htmlspecialchars($student['email']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="3">No student records found.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
            <form method="get">
                <button type="submit" name="logout" class="logout-btn">Logout</button>
            </form>

            <div class="section-title">📊 Exam Results</div>
            <table>
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Subject</th>
                        <th>Score</th>
                        <th>Total</th>
                        <th>Date Taken</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (!empty($results)): ?>
                    <?php foreach ($results as $row): ?>
                        <tr>
                            <td><?= htmlspecialchars($row['name']) ?></td>
                            <td><?= htmlspecialchars($row['email']) ?></td>
                            <td><?= htmlspecialchars($row['subject']) ?></td>
                            <td><?= $row['score'] ?></td>
                            <td><?= $row['total'] ?></td>
                            <td><?= date("d M Y, H:i", strtotime($row['date_taken'])) ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="6">No exam results found.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>


</body>
</html>
