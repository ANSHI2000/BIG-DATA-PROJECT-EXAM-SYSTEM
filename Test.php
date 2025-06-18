<?php
session_start();

if (!isset($_SESSION['name']) || !isset($_SESSION['email']) || !isset($_SESSION['subject'])) {
    header("Location: Analysis.php");
    exit();
}

$name = $_SESSION['name'];
$email = $_SESSION['email'];
$subject = $_SESSION['subject'];

// Load questions from CSV
$questions = [];
if (($handle = fopen("question.csv", "r")) !== false) {
    fgetcsv($handle); // skip header
    while (($data = fgetcsv($handle)) !== false) {
        $sub = trim($data[0]);
        $question = $data[1];
        $options = array_slice($data, 2, 4);
        $answer = $data[6];
        $questions[$sub][] = [$question, $options, $answer];
    }
    fclose($handle);
}

// Initialize variables
$total = 0;
$score = 0;
$analysis = [];

// ------------------ MAIN LOGIC -------------------

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_SESSION['test_questions'])) {
    $currentTest = $_SESSION['test_questions'];
    $total = count($currentTest);

    foreach ($currentTest as $index => $q) {
        $questionText = $q[0];
        $options = $q[1];
        $correctAnswer = $q[2];
        $userAnswer = $_POST["q$index"] ?? "";

        if ($userAnswer == $correctAnswer) {
            $score++;
        } else {
            $userAnswerIndex = array_search($userAnswer, $options);
            $correctAnswerIndex = array_search($correctAnswer, $options);

            $analysis[] = "Q" . ($index + 1) . ": " . htmlspecialchars($questionText) . "<br>" .
                "1. " . htmlspecialchars($options[0]) . "<br>" .
                "2. " . htmlspecialchars($options[1]) . "<br>" .
                "3. " . htmlspecialchars($options[2]) . "<br>" .
                "4. " . htmlspecialchars($options[3]) . "<br>" .
                "Your Answer: Option " . ($userAnswerIndex+ 1 ) . "<br>" .
                "Correct Answer: Option " . ($correctAnswerIndex + 1) . "<br><br>";
        }
    }

    // Save to DB
    $conn = new mysqli("localhost", "root", "password@123", "BigData");
    if ($conn->connect_error) die("Connection failed");

    $stmt = $conn->prepare("INSERT INTO scores (name, email, subject, score, total, date_taken) VALUES (?, ?, ?, ?, ?, NOW())");
    $stmt->bind_param("sssii", $name, $email, $subject, $score, $total);
    $stmt->execute();
    $stmt->close();
    $conn->close();

    // Clear test questions to prevent resubmission
    unset($_SESSION['test_questions']);

} else {
    // First time test loaded → pick 5 random and store in session
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
        body { background-color: #1e1e3c; color: white; padding: 30px; }
        .question { margin-bottom: 30px; }
    </style>
</head>
<body>
    <div class="container">
        <h2><?= htmlspecialchars($subject) ?> Test for <?= htmlspecialchars($name) ?></h2>

        <?php if ($_SERVER["REQUEST_METHOD"] != "POST"): ?>
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
            <h3>Test Completed!</h3>
            <p>Score: <?= $score ?>/<?= $total ?></p>
            <?php if (!empty($analysis)): ?>
                <h4>Incorrect Answers:</h4>
                <div style="color: lightcoral;">
                    <?= implode("", $analysis) ?>
                </div>
            <?php else: ?>
                <p>Excellent! All answers correct.</p>
            <?php endif; ?>
            <a href="Student.php" class="btn btn-success mt-3">Back to Dashboard</a>
        <?php endif; ?>
    </div>
</body>
</html>
