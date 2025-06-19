<?php
session_start(); // Start session to access user login data

// Redirect to login page if user is not logged in
if (!isset($_SESSION['email'])) {
    header("Location: login.php");
    exit();
}

// Store session values for current user
$name = $_SESSION['name'];
$email = $_SESSION['email'];
?>

<?php
// Handle "Take Test" form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['take_test']) && !empty($_POST['subject'])) {
        // Save selected subject in session and redirect to Test page
        $_SESSION['subject'] = $_POST['subject'];
        header("Location: Test.php");
        exit();
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Student Dashboard</title>

    <!-- Bootstrap CDN for styles -->
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">

    <!-- Custom Styles -->
    <style>
        body {
            background: url('auto.jpg'); /* Background image */
            background-size: cover;
            height: 100vh;
            font-family: Arial, sans-serif;
            justify-content: center;
            align-items: center;
            text-align: center;
            padding-top: 60px;
        }

        .container {
            background: rgba(255, 255, 255, 0.15); /* Glassmorphism effect */
            backdrop-filter: blur(12px);
            padding: 30px;
            border-radius: 16px;
            box-shadow: 10px 8px 32px rgba(0,0,0,0.2);
            width: 90%;
            max-width: 600px;
            color: #fff;
        }

        .btn-custom {
            width: 250px;
            margin: 15px auto;
            font-size: 20px;
            border-radius: 10px;
        }

        .logo {
            width: 60px;
            height: 60px;
        }
    </style>
</head>
<body>

<!-- Dashboard Container -->
<div class="container">

    <!-- Header -->
    <div class="d-flex justify-content-center align-items-center mb-4">
        <h2>Automated Exam System</h2>
    </div>

    <!-- Personalized welcome -->
    <h3>Welcome, <?php echo htmlspecialchars($name); ?></h3>

    <!-- Form for selecting subject and taking test -->
    <form method="post" action="">
        <div>
            <?php
            // Load unique subjects from the CSV file (question1.csv)
            $subjects = [];
            if (($handle = fopen("question1.csv", "r")) !== false) {
                fgetcsv($handle); // Skip header
                while (($row = fgetcsv($handle)) !== false) {
                    $sub = trim($row[0]);
                    if (!in_array($sub, $subjects)) {
                        $subjects[] = $sub; // Add only unique subjects
                    }
                }
                fclose($handle);
            }
            ?>

            <!-- Subject dropdown menu -->
            <select name="subject" class="form-control mt-4 w-50 mx-auto" required>
                <option value="" disabled selected>Select Subject</option>
                <?php foreach ($subjects as $subject): ?>
                <option value="<?= htmlspecialchars($subject) ?>"><?= htmlspecialchars($subject) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <!-- Button to Take Test -->
        <button type="submit" name="take_test" class="btn-custom" style="background-color:#007bff;color:white;">
            Take Test
        </button>
    </form>

    <!-- Special Test Button -->
    <form method="get" action="map.html">
        <button type="submit" class="btn-custom" style="background-color:#28a745;color:white;">
            Special Test
        </button>
    </form>

    <!-- Button to View Test Analysis -->
    <form method="post" action="Analysis.php">
        <button type="submit" class="btn-custom" style="background-color:#ffc107;color:white;">
            Analysis
        </button>
    </form>

    <!-- Logout Button -->
    <form method="post" action="AuthPage.php">
        <button type="submit" class="btn-custom" style="background-color:#dc3545;color:white;">
            Logout
        </button>
    </form>

</div>
</body>
</html>

