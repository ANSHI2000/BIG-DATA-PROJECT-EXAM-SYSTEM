<?php
session_start(); // Starts a new session or resumes an existing session

// Connect to the MySQL database
$conn = new mysqli("localhost", "root", "password@123", "BigData");

// Check connection error
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$errors = [];   // To store error messages
$success = "";  // To store success messages (not used currently)

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // LOGIN PROCESS
    if (isset($_POST['action']) && $_POST['action'] === 'login') {
        $email = trim($_POST['email']);
        $password = trim($_POST['password']);

        // Check if fields are filled
        if (empty($email) || empty($password)) {
            $errors[] = "Please fill all login fields.";
        } else {
            // Prepare and execute SQL query to verify credentials
            $stmt = $conn->prepare("SELECT * FROM students WHERE email = ? AND password = ?");
            $stmt->bind_param("ss", $email, $password);
            $stmt->execute();
            $result = $stmt->get_result();

            // If login successful, redirect to student page
            if ($row = $result->fetch_assoc()) {
                $_SESSION['name'] = $row['name'];
                $_SESSION['email'] = $row['email'];
                header("Location: student.php");
                exit();
            } else {
                $errors[] = "Invalid email or password.";
            }
        }
    }

    // SIGNUP PROCESS
    if (isset($_POST['action']) && $_POST['action'] === 'signup') {
        $name = trim($_POST['name']);
        $email = trim($_POST['email']);
        $password = trim($_POST['password']);

        // Basic validation
        if (empty($name) || empty($email) || empty($password)) {
            $errors[] = "Please fill all signup fields.";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = "Invalid email format.";
        } else {
            // Check if name or email already exists
            $check = $conn->prepare("SELECT * FROM students WHERE email = ? OR name = ?");
            $check->bind_param("ss", $email, $name);
            $check->execute();
            $result = $check->get_result();

            if ($result->fetch_assoc()) {
                $errors[] = "Email or name already exists.";
            } else {
                // Insert new user into database
                $stmt = $conn->prepare("INSERT INTO students (name, email, password) VALUES (?, ?, ?)");
                $stmt->bind_param("sss", $name, $email, $password);

                if ($stmt->execute()) {
                    $_SESSION['name'] = $name;
                    $_SESSION['email'] = $email;
                    header("Location: student.php");
                    exit();
                } else {
                    $errors[] = "Sign-up failed. Try again.";
                }
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Login & Signup | Exam System</title>
    <style>
        /* Reset and global styles */
        * {
            box-sizing: border-box;
            padding: 0;
            margin: 0;
        }

        body {
            background: url('auto.jpg') no-repeat center center/cover;
            height: 100vh;
            font-family: 'Segoe UI';
            display: flex;
            justify-content: center;
            align-items: center;
        }

        /* Main container style with glassmorphism */
        .auth-container {
            background: rgba(255, 255, 255, 0.15);
            backdrop-filter: blur(12px);
            padding: 40px 30px;
            border-radius: 16px;
            box-shadow: 10px 8px 32px rgba(0,0,0,0.2);
            width: 90%;
            max-width: 380px;
            color: #fff;
        }

        h1 {
            text-align: center;
            margin-bottom: 20px;
            color: #fff;
        }

        /* Forms are hidden by default */
        form {
            display: none;
            animation: fadeIn 0.4s ease-in-out;
        }

        /* Only one form is visible at a time */
        form.active {
            display: block;
        }

        input, button {
            width: 100%;
            padding: 12px 14px;
            margin: 12px 0;
            border-radius: 8px;
            border: none;
            font-size: 1rem;
        }

        input {
            background: rgba(255,255,255,0.85);
            color: #333;
        }

        button {
            background-color: #007bff;
            color: white;
            font-weight: bold;
            cursor: pointer;
            transition:  0.3s;
        }

        button:hover {
            background-color: #0056b3;
        }

        .switch-link {
            text-align: center;
            margin-top: 12px;
            cursor: pointer;
            color: rgb(11, 8, 8);
            text-decoration: underline;
            font-size: 0.9rem;
        }

        .error {
            background: rgba(255, 0, 0, 0.1);
            padding: 10px;
            color: red;
            font-size: 0.9rem;
            margin-bottom: 10px;
            border-radius: 8px;
            text-align: center;
        }

        .admin-btn {
            padding: 12px;
            background-color: orange;
            color: white;
            font-weight: bold;
            border: none;
            width: 100%;
            border-radius: 8px;
            margin-top: 20px;
            cursor: pointer;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        @media (max-width: 400px) {
            .auth-container {
                padding: 25px 20px;
            }
            h1 {
                font-size: 1.4rem;
            }
        }
    </style>
</head>
<body>
<div class="auth-container">
    <h1>Automated Exam System</h1>
    <h1 id="form-title">Login</h1>

    <!-- Display error messages -->
    <?php if (!empty($errors)): ?>
        <div class="error"><?= implode("<br>", $errors); ?></div>
    <?php endif; ?>

    <!-- Login form -->
    <form id="login-form" class="active" method="post">
        <input type="hidden" name="action" value="login">
        <input type="email" name="email" placeholder="Email" required>
        <input type="password" name="password" placeholder="Password" required>
        <button type="submit">Login</button>
    </form>

    <!-- Signup form -->
    <form id="signup-form" method="post">
        <input type="hidden" name="action" value="signup">
        <input type="text" name="name" placeholder="Name" required>
        <input type="email" name="email" placeholder="Email" required>
        <input type="password" name="password" placeholder="Password" required>
        <button type="submit">Signup</button>
    </form>

    <!-- Link to switch between login/signup -->
    <div class="switch-link" onclick="toggleForm()">Don't have an account? Sign up</div>

    <!-- Button for admin login -->
    <form action="AdminLogin.php" class="active">
        <button type="submit" class="admin-btn">Login As Admin</button>
    </form>
</div>

<script>
    // JavaScript function to toggle between login and signup forms
    let isLogin = true;

    function toggleForm() {
        isLogin = !isLogin;

        document.getElementById("login-form").classList.toggle("active", isLogin);
        document.getElementById("signup-form").classList.toggle("active", !isLogin);

        document.getElementById("form-title").textContent = isLogin ? "Login" : "Signup";
        document.querySelector(".switch-link").textContent = isLogin
            ? "Don't have an account? Sign up"
            : "Already have an account? Login";
    }
</script>

</body>
</html>


