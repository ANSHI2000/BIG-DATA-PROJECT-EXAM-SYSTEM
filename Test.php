<?php
session_start();

// Redirect to analysis page if required session values are missing
if (!isset($_SESSION['name']) || !isset($_SESSION['email']) || !isset($_SESSION['subject'])) {
    header("Location: Analysis.php");
    exit();
}

$name = $_SESSION['name'];
$email = $_SESSION['email'];
$subject = $_SESSION['subject'];

// Load all questions from the CSV file and group them by subject
$questions = [];
if (($handle = fopen("question1.csv", "r")) !== false) {
    fgetcsv($handle); // Skip the header
    while (($data = fgetcsv($handle)) !== false) {
        $sub = trim($data[0]);         // Subject name
        $question = $data[1];          // Question text
        $options = array_slice($data, 2, 4); // Options (2,3,4,5)
        $answer = $data[5];            // Correct answer
        $questions[$sub][] = [$question, $options, $answer]; // Save grouped by subject
    }
    fclose($handle);
}

// Initialize score tracking
$total = 0;
$score = 0;
$analysis = [];

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_SESSION['test_questions'])) {
    // If test was submitted, evaluate answers
    $currentTest = $_SESSION['test_questions'];
    $total = count($currentTest);

    foreach ($currentTest as $index => $q) {
        $questionText = $q[0];
        $options = $q[1];
        $correctAnswer = $q[2];
        $userAnswer = $_POST["q$index"] ?? ""; // Get selected answer

        if ($userAnswer == $correctAnswer) {
            $score++; // Correct
        } else {
            // If wrong, prepare analysis to show correct option
            $userAnswerIndex = array_search($userAnswer, $options);
            $correctAnswerIndex = array_search($correctAnswer, $options);

            $analysis[] = "Q" . ($index + 1) . ": " . htmlspecialchars($questionText) . "<br>" .
                "1. " . htmlspecialchars($options[0]) . "<br>" .
                "2. " . htmlspecialchars($options[1]) . "<br>" .
                "3. " . htmlspecialchars($options[2]) . "<br>" .
                "4. " . htmlspecialchars($options[3]) . "<br>" .
                "Your Answer: Option " . ($userAnswerIndex + 1) . "<br>" .
                "Correct Answer: Option " . ($correctAnswerIndex + 1) . "<br><br>";
        }
    }

    // Save test result in scores table
    $conn = new mysqli("localhost", "root", "password@123", "BigData");
    if ($conn->connect_error) die("Connection failed");

    $stmt = $conn->prepare("INSERT INTO scores (name, email, subject, score, total, date_taken) VALUES (?, ?, ?, ?, ?, NOW())");
    $stmt->bind_param("sssii", $name, $email, $subject, $score, $total);
    $stmt->execute();
    $stmt->close();

    // Update or Insert student's subject-wise performance
    $fetch = $conn->prepare("SELECT tmarks, marksobtain, nooftest FROM students WHERE email = ? AND subject = ?");
    $fetch->bind_param("ss", $email, $subject);
    $fetch->execute();
    $res = $fetch->get_result();

    if ($res->num_rows > 0) {
        // If subject already exists in student record, update it
        $row = $res->fetch_assoc();
        $totalMarks = $row['tmarks'] + $total;
        $marksObtained = $row['marksobtain'] + $score;
        $noOfTest = $row['nooftest'] + 1;
        $average = $marksObtained / $noOfTest;

        $update = $conn->prepare("UPDATE students SET tmarks = ?, marksobtain = ?, nooftest = ?, average = ? WHERE email = ? AND subject = ?");
        $update->bind_param("iiidss", $totalMarks, $marksObtained, $noOfTest, $average, $email, $subject);
        $update->execute();
        $update->close();
    } else {
        // New subject entry for this student
        if (!isset($password) || empty($password)) {
            // Fetch password to insert again (since subject-wise info is stored)
            $stmt = $conn->prepare("SELECT password FROM students WHERE email = ?");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($result && $result->num_rows > 0) {
                $password = $result->fetch_assoc()['password'];
            }
            $stmt->close();
        }

        $average = $score;

        $insert = $conn->prepare("INSERT INTO students (email, name, subject, password, tmarks, marksobtain, nooftest, average) VALUES (?, ?, ?, ?, ?, ?, 1, ?)");
        $insert->bind_param("ssssiii", $email, $name, $subject, $password, $total, $score, $average);
        $insert->execute();
        $insert->close();
    }

    $fetch->close();
    $conn->close();
    unset($_SESSION['test_questions']); // Clear questions after test ends
} else {
    // First-time test load: pick 5 random questions
    $allSubjectQuestions = $questions[$subject] ?? [];
    shuffle($allSubjectQuestions);
    $currentTest = array_slice($allSubjectQuestions, 0, 5);
    $_SESSION['test_questions'] = $currentTest;
    $total = count($currentTest);
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Take Test - <?= htmlspecialchars($subject) ?></title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <style>
        body {
            background: url('auto.jpg') center center/cover;
            height: 100vh;
            color: #fff;
            justify-content: center;
            align-items: center;
        }

        .question {
            background: rgba(255, 255, 255, 0.15);
            backdrop-filter: blur(12px);
            padding: 40px 30px;
            border-radius: 16px;
            box-shadow: 10px 8px 32px rgba(0,0,0,0.2);
            width: 90%;
            max-width: 90%;
            color: black;
            margin-bottom: 30px;
            font-size: 20px;
            font-weight: 500;
        }

        .container {
            padding: 30px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2><?= htmlspecialchars($subject) ?> Test for <?= htmlspecialchars($name) ?></h2>

        <?php if ($_SERVER["REQUEST_METHOD"] != "POST"): ?>
            <!-- TEST FORM -->
            <form method="post">
                <?php foreach ($currentTest as $i => $q): ?>
                    <div class="question">
                        <strong>Q<?= $i + 1 ?>: <?= htmlspecialchars($q[0]) ?></strong><br>
                        <?php foreach ($q[1] as $option): ?>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="q<?= $i ?>" value="<?= htmlspecialchars($option) ?>" required>
                                <label class="form-check-label"><?= htmlspecialchars($option) ?></label>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endforeach; ?>
                <button type="submit" class="btn btn-primary">Submit</button>
            </form>

        <?php else: ?>
            <!-- AFTER TEST SUBMIT -->
            <h3>Test Completed!</h3>
            <p>Score: <?= $score ?>/<?= $total ?></p>

            <?php if (!empty($analysis)): ?>
                <h4>Incorrect Answers:</h4>
                <div class="question" style="color: black;">
                    <?= implode("", $analysis) ?>
                </div>
            <?php else: ?>
                <p>Excellent! All answers correct.</p>
            <?php endif; ?>

            <a href="Student.php" class="btn btn-success mt-3">⬅ Back to Dashboard</a>
        <?php endif; ?>
    </div>
</body>
</html>

