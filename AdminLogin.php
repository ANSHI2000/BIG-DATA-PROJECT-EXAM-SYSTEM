<?php
session_start(); // Start or resume a session

// Define admin credentials
define("ADMIN_USER", "admin"); // Admin username
define("ADMIN_PASS_HASH", hash('sha256', 'admin123')); // Hashed admin password

// Logout logic
if (isset($_GET['logout'])) {
    session_destroy(); // Destroy session on logout
    header("Location: AuthPage.php"); // Redirect to login page
    exit();
}

// Initialize variables
$error = ""; // To store login error
$results = []; // Store all exam results
$students = []; // Store all student data
$filteredResults = []; // Store filtered results based on subject or student
$showAllStudents = isset($_GET['show_students']); // Flag to show all students
$filterApplied = false; // Track whether a filter is applied

// Connect to MySQL database
$conn = new mysqli("localhost", "root", "password@123", "BigData");

// Check DB connection
if ($conn->connect_error) {
    die("DB Connection failed: " . $conn->connect_error);
}

// Admin login validation
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['login'])) {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    // Validate against hardcoded credentials
    if ($username === ADMIN_USER && hash('sha256', $password) === ADMIN_PASS_HASH) {
        $_SESSION['admin'] = true; // Mark user as logged in
    } else {
        $error = "Invalid username or password."; // Show error
    }
}

// Fetch all students data from `students` table
$res = $conn->query("SELECT name, email ,average,subject,tmarks,marksobtain ,nooftest FROM students ORDER BY name ASC");
if ($res) {
    while ($row = $res->fetch_assoc()) {
        $students[] = $row; // Store each student row
    }
}

// Base query to fetch scores
$baseQuery = "SELECT name, email, subject, score, total, date_taken FROM scores";

// If admin is logged in, proceed with result fetching
if (isset($_SESSION['admin'])) {
    if (isset($_GET['filter_student']) && $_GET['filter_student']) {
        // Filter results by specific student email
        $email = $_GET['filter_student'];
        $stmt = $conn->prepare("$baseQuery WHERE email = ? ORDER BY date_taken DESC");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $filteredResults = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $filterApplied = true;
        $stmt->close();
    } elseif (isset($_GET['filter_subject']) && $_GET['filter_subject']) {
        // Filter results by subject
        $subject = $_GET['filter_subject'];
        $stmt = $conn->prepare("$baseQuery WHERE subject = ? ORDER BY date_taken DESC");
        $stmt->bind_param("s", $subject);
        $stmt->execute();
        $filteredResults = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $filterApplied = true;
        $stmt->close();
    } else {
        // No filter, fetch all results
        $resAll = $conn->query("$baseQuery ORDER BY date_taken DESC");
        if ($resAll) {
            $results = $resAll->fetch_all(MYSQLI_ASSOC);
        }
    }
}

// Close the database connection
$conn->close();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Admin Panel</title>
    <style>
        /* Background and font styling */
        body {
            background: url('auto.jpg') center center / cover no-repeat;
            font-family: 'Segoe UI';
            margin: 0;
            padding: 0;
        }

        /* Main container */
        .container {
            padding: 40px;
        }

        /* Box styling for login and dashboard */
        .login-box, .dashboard {
            background: rgba(255, 255, 255, 0.15);
            backdrop-filter: blur(12px);
            border-radius: 16px;
            box-shadow: 10px 8px 32px rgba(0,0,0,0.2);
            max-width: 900px;
            margin: auto;
            padding: 30px;
            color: black;
        }

        h2 { color: white; }

        input, select, button {
            width: 100%;
            padding: 10px;
            margin-top: 10px;
            border-radius: 8px;
            border: none;
        }

        button {
            background: #007BFF;
            color: white;
            cursor: pointer;
        }

        /* Logout button */
        .logout-btn {
            float: right;
            background: #dc3545;
            width: auto;
            padding: 10px 20px;
        }

        /* Student login redirection button */
        .student-btn {
            background-color: orange;
            width: 100%;
            margin-top: 20px;
        }

        /* Filters layout */
        .filter-form {
            display: flex;
            gap: 20px;
            flex-wrap: wrap;
            margin-top: 20px;
        }

        /* Table styling */
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 30px;
            background: white;
            color: black;
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

        /* Section headings */
        .section-title {
            margin-top: 40px;
            font-size: 18px;
            font-weight: bold;
            color: white;
        }

        .error { color: red; }
    </style>
</head>

<body>
<div class="container">
    <?php if (!isset($_SESSION['admin'])): ?>
        <!-- Login Form for Admin -->
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
            <!-- Link to student login page -->
            <form action="AuthPage.php">
                <button type="submit" class="student-btn">Login As Student</button>
            </form>
        </div>
    <?php else: ?>
        <!-- Admin Dashboard -->
        <div class="dashboard">
            <!-- Logout Button -->
            <form method="get">
                <button type="submit" name="logout" class="logout-btn">Logout</button>
            </form>
            <h2>Welcome Admin</h2>

            <!-- Filter Forms -->
            <div class="filter-form">
                <!-- Filter by student -->
                <form method="get">
                    <label>🎓 Select Student:</label>
                    <select name="filter_student">
                        <option value="">--Choose--</option>
                        <?php
                        $seenEmails = [];
                        foreach ($students as $stu):
                            if (!in_array($stu['email'], $seenEmails)):
                                $seenEmails[] = $stu['email'];
                        ?>
                            <option value="<?= $stu['email'] ?>"><?= $stu['name'] ?></option>
                        <?php
                            endif;
                        endforeach;
                        ?>
                    </select>
                    <button type="submit">View Results</button>
                </form>

                <!-- Filter by subject -->
                <form method="get">
                    <label>📘 Select Subject:</label>
                    <select name="filter_subject">
                        <option value="">--Choose--</option>
                        <?php
                        $subjects = [];
                        foreach (array_merge($results, $filteredResults) as $row) {
                            if (!in_array($row['subject'], $subjects)) {
                                $subjects[] = $row['subject'];
                            }
                        }
                        foreach ($subjects as $subj): ?>
                            <option value="<?= $subj ?>"><?= $subj ?></option>
                        <?php endforeach; ?>
                    </select>
                    <button type="submit">View Results</button>
                </form>

                <!-- Show all students -->
                <form method="get">
                    <button type="submit" name="show_students" style="background-color: teal;">Show All Students</button>
                </form>
            </div>

            <!-- Display filtered results -->
            <?php if ($filterApplied): ?>
                <div class="section-title">📊 Filtered Results</div>
                <?php if (!empty($filteredResults)): ?>
                    <table>
                        <thead>
                            <tr><th>Name</th><th>Email</th><th>Subject</th><th>Score</th><th>Total</th><th>Date</th></tr>
                        </thead>
                        <tbody>
                            <?php foreach ($filteredResults as $row): ?>
                                <tr>
                                    <td><?= $row['name'] ?? '-' ?></td>
                                    <td><?= $row['email'] ?? '-' ?></td>
                                    <td><?= $row['subject'] ?? '-' ?></td>
                                    <td><?= $row['score'] ?? '-' ?></td>
                                    <td><?= $row['total'] ?? '-' ?></td>
                                    <td><?= isset($row['date_taken']) ? date("d M Y, H:i", strtotime($row['date_taken'])) : '-' ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p style="color:white;">No results found.</p>
                <?php endif; ?>
            <?php endif; ?>

            <!-- Show all students data -->
            <?php if ($showAllStudents): ?>
                <div class="section-title">📚 All Registered Students</div>
                <table>
                    <thead>
                        <tr><th>Name</th><th>Email</th><th>Average Score</th><th>Subject</th><th>Total Marks</th><th>Marks Obtained</th><th>Total Tests</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($students as $student): ?>
                            <tr>
                                <td><?= $student['name'] ?? '-' ?></td>
                                <td><?= $student['email'] ?? '-' ?></td>
                                <td><?= $student['average'] ?? '-' ?></td>
                                <td><?= $student['subject'] ?? '-' ?></td>
                                <td><?= $student['tmarks'] ?? '-' ?></td>
                                <td><?= $student['marksobtain'] ?? '-' ?></td>
                                <td><?= $student['nooftest'] ?? '-' ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>

            <!-- Show all results if no filter applied -->
            <?php if (!$filterApplied && !$showAllStudents): ?>
                <div class="section-title">📊 All Exam Results</div>
                <table>
                    <thead>
                        <tr><th>Name</th><th>Email</th><th>Subject</th><th>Score</th><th>Total</th><th>Date Taken</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($results as $row): ?>
                            <tr>
                                <td><?= $row['name'] ?? '-' ?></td>
                                <td><?= $row['email'] ?? '-' ?></td>
                                <td><?= $row['subject'] ?? '-' ?></td>
                                <td><?= $row['score'] ?? '-' ?></td>
                                <td><?= $row['total'] ?? '-' ?></td>
                                <td><?= isset($row['date_taken']) ? date("d M Y, H:i", strtotime($row['date_taken'])) : '-' ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>
</body>
</html>
