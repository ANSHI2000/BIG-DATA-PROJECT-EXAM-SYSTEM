<?php
session_start();

$servername = "localhost";
$username = "root";
$password = "password@123";
$dbname = "BigData";

// DB connection
$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

// Get student email and name (from GET or SESSION fallback)
$email = $_GET['email'] ?? ($_SESSION['email'] ?? '');
$name = $_GET['name'] ?? ($_SESSION['name'] ?? '');

if (empty($email)) {
    die("⚠️ Email is required to view test history.");
}

$stmt = $conn->prepare("SELECT subject, score, total, date_taken FROM scores WHERE email = ? ORDER BY date_taken DESC");
if (!$stmt) die("Database error: " . $conn->error);
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>📊 Test History & Analysis</title>
    <style>
        body { font-family: 'Segoe UI', sans-serif; background: #f4f4f4; margin: 40px; }
        .container { max-width: 800px; margin: auto; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 0 10px #ccc; }
        h2 { margin-bottom: 10px; }
        .info { font-size: 16px; margin-bottom: 20px; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { padding: 10px; border: 1px solid #ccc; text-align: center; }
        th { background: #2196f3; color: white; }
        .no-data { color: #888; text-align: center; padding: 20px; }
        .btn-primary {
            padding: 10px 20px;
            background-color: #007bff;
            color: white;
            text-decoration: none;
            border-radius: 5px;
        }
        .btn-primary:hover {
            background-color: #0056b3;
        }
    </style>
</head>
<body>
<div class="container">
    <h2>📊 Test History & Analysis</h2>
    <div class="info">
        <strong>👤 Name:</strong> <?= htmlspecialchars($name ?: 'Not Provided') ?><br>
        <strong>📧 Email:</strong> <?= htmlspecialchars($email) ?>
    </div>

    <?php if ($result && $result->num_rows > 0): ?>
        <table>
            <tr>
                <th>Subject</th>
                <th>Score</th>
                <th>Total</th>
                <th>Date Taken</th>
            </tr>
            <?php while ($row = $result->fetch_assoc()): ?>
                <tr>
                    <td><?= htmlspecialchars($row['subject']) ?></td>
                    <td><?= (int)$row['score'] ?></td>
                    <td><?= (int)$row['total'] ?></td>
                    <td><?= date('d M Y, H:i', strtotime($row['date_taken'])) ?></td>
                </tr>
            <?php endwhile; ?>
        </table>
    <?php else: ?>
        <div class="no-data">No test records found for this email.</div>
    <?php endif; ?>

    <div style="text-align: center; margin-top: 20px;">
        <a href="Student.php" class="btn btn-primary">⬅️ Back to Dashboard</a>
    </div>
</div>
</body>
</html>
<?php
$stmt->close();
$conn->close();
?>
