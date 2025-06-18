<?php
    session_start();
if (!isset($_SESSION['email'])) {
    header("Location: login.php");
    exit();
}
    $name = $_SESSION['name'];
    $email = $_SESSION['email'];
?>
<?php
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (isset($_POST['take_test']) && !empty($_POST['subject'])) {
            $_SESSION['subject'] = $_POST['subject'];
            header("Location: test.php");
            exit();
        }
    }
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Student Dashboard</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <style>
        body {
            background-color: #1e1e3c;
            color: white;
            text-align: center;
            padding-top: 60px;
        }
        .btn-custom {
            width: 250px;
            margin: 15px auto;
            font-size: 20px;
        }
        .logo {
            width: 60px;
            height: 60px;
        }
    </style>
</head>
<body>
    <!-- Header -->
    <div class="container">
        <div class="d-flex justify-content-center align-items-center mb-4">
            <img src="logo.png" alt="Logo" class="logo mr-3">
            <h2>Automated Exam System</h2>
        </div>
        <!-- Welcome Message -->
        <h3>Welcome, <?php echo htmlspecialchars($name); ?></h3>        
        <!-- Buttons -->
        <form method="post" action="">
            <div>
               <?php
                // Load unique subjects from CSV
                    $subjects = [];
                    if (($handle = fopen("question.csv", "r")) !== false) {
                        fgetcsv($handle); // Skip header row
                        while (($row = fgetcsv($handle)) !== false) {
                            $sub = trim($row[0]);
                            if (!in_array($sub, $subjects)) {
                                $subjects[] = $sub;
                            }
                        }
                        fclose($handle);
                    }
                ?>
                <select name="subject" class="form-control mt-4 w-50 mx-auto" required>
                    <option value="" disabled selected>Select Subject</option>
                    <?php foreach ($subjects as $subject): ?>
                    <option value="<?= htmlspecialchars($subject) ?>"><?= htmlspecialchars($subject) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <button type="submit" name="take_test" class="btn btn-primary btn-custom">Take Test</button>
        </form>
        <form method="get" action="map.html">
            <button type="submit" class="btn btn-success btn-custom">Special Test</button>
        </form>

        <form method="post" action="analysis.php">
            <button type="submit" class="btn btn-warning btn-custom">Analysis</button>
        </form>

        <form method="post" action="AuthPage.php">
            <button type="submit" class="btn btn-danger btn-custom">Logout</button>
        </form>
    </div>
</body>
</html>

