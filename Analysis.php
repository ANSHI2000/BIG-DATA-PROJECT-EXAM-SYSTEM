<?php
session_start();

// Database connection
$servername = "localhost";
$username = "root";
$password = "password@123";
$dbname = "BigData";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

// Fetch user data from session
$email = $_SESSION['email'] ?? '';
$name = $_SESSION['name'] ?? '';
$selectedSubject = $_POST['subject'] ?? ''; // From dropdown form

// Function to get list of subjects the user has attempted
function getSubjects($conn, $email) {
    $stmt = $conn->prepare("SELECT DISTINCT subject FROM scores WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $res = $stmt->get_result();
    $subjects = [];
    while ($row = $res->fetch_assoc()) {
        $subjects[] = $row['subject'];
    }
    $stmt->close();
    return $subjects;
}

$subjects = getSubjects($conn, $email); // Get subject options
$results = [];
$rank = null;

// If a subject is selected and form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $selectedSubject !== '') {

    // Get user's test history for selected subject
    $stmt = $conn->prepare("
        SELECT score, total, date_taken
        FROM scores
        WHERE email = ? AND subject = ?
        ORDER BY date_taken DESC
    ");
    $stmt->bind_param("ss", $email, $selectedSubject);
    $stmt->execute();
    $results = $stmt->get_result();

    // Calculate user's rank based on total score across all attempts in that subject
    $rankQuery = $conn->prepare("
        SELECT email, SUM(score) AS total_score
        FROM scores
        WHERE subject = ?
        GROUP BY email
        ORDER BY total_score DESC, MAX(date_taken) ASC
    ");
    $rankQuery->bind_param("s", $selectedSubject);
    $rankQuery->execute();
    $rankRes = $rankQuery->get_result();

    $rank = 1;
    while ($row = $rankRes->fetch_assoc()) {
        if ($row['email'] == $email) break;
        $rank++;
    }

    $stmt->close();
    $rankQuery->close();
}
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>📊 Subject-wise Test Analysis</title>
    <style>
        body {
            background: url('auto.jpg') center center/cover;
            height: 100vh;
            font-family: 'Segoe UI';
            justify-content: center;
            align-items: center;
        }

        .container {
            background: rgba(255, 255, 255, 0.15);
            backdrop-filter: blur(12px);
            padding: 40px 30px;
            border-radius: 16px;
            box-shadow: 10px 8px 32px rgba(0,0,0,0.2);
            width: 80%;
            color: black;
        }

        h1 { color: #fff; }

        .info {
            margin-bottom: 20px;
            color: black;
            font-size: 16px;
        }

        select, button {
            padding: 10px;
            font-size: 15px;
            border-radius: 10px;
        }

        .btn-primary {
            display: inline-block;
            margin-top: 20px;
            padding: 10px 20px;
            background: #007bff;
            color: white;
            text-decoration: none;
            border-radius: 5px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }

        th, td {
            padding: 12px;
            border: 1px solid #ccc;
            text-align: center;
        }

        th {
            background-color: #007bff;
            color: white;
        }
    </style>
</head>
<body>

<!-- Main container -->
<div class="container">
    <h1>📊 Test History & Rank</h1>

    <!-- User Info -->
    <div class="info">
        <strong>👤 Name:</strong> <?= htmlspecialchars($name ?: 'Unknown') ?><br>
        <strong>📧 Email:</strong> <?= htmlspecialchars($email) ?>
    </div>

    <!-- Subject Dropdown Form -->
    <form method="post">
        <label for="subject">Choose Subject:</label>
        <select name="subject" id="subject" required>
            <option value="">-- Select Subject --</option>
            <?php foreach ($subjects as $sub): ?>
                <option value="<?= htmlspecialchars($sub) ?>" <?= $sub === $selectedSubject ? 'selected' : '' ?>>
                    <?= htmlspecialchars($sub) ?>
                </option>
            <?php endforeach; ?>
        </select>
        <button style="background-color:#ffc107" type="submit">View</button>
    </form>

    <!-- Results Table + Rank -->
    <?php if ($_SERVER['REQUEST_METHOD'] === 'POST' && $selectedSubject !== ''): ?>
        <p><strong>Your Rank in <?= htmlspecialchars($selectedSubject) ?>:</strong> <?= $rank ?></p>
        <h3>Subject: <?= htmlspecialchars($selectedSubject) ?></h3>

        <?php if ($results && $results->num_rows > 0): ?>
            <table>
                <thead>
                    <tr>
                        <th>Score</th>
                        <th>Total</th>
                        <th>Date Taken</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = $results->fetch_assoc()): ?>
                        <tr>
                            <td><?= (int)$row['score'] ?></td>
                            <td><?= (int)$row['total'] ?></td>
                            <td><?= date('d M Y, H:i', strtotime($row['date_taken'])) ?></td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p>No tests taken for this subject.</p>
        <?php endif; ?>
    <?php endif; ?>

    <!-- Back to dashboard -->
    <a href="Student.php" class="btn-primary">⬅ Back to Dashboard</a>
</div>
</body>
</html>

<?php $conn->close(); ?>
