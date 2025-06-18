<?php
session_start();
$conn = new mysqli("localhost", "root", "password@123", "BigData");

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$errors = [];
$success = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // LOGIN
    if (isset($_POST['action']) && $_POST['action'] === 'login') {
        $email = trim($_POST['email']);
        $password = trim($_POST['password']);

        if (empty($email) || empty($password)) {
            $errors[] = "Please fill all login fields.";
        } else {
            $stmt = $conn->prepare("SELECT * FROM students WHERE email = ? AND password = ?");
            $stmt->bind_param("ss", $email, $password);
            $stmt->execute();
            $result = $stmt->get_result();

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

    // SIGNUP
    if (isset($_POST['action']) && $_POST['action'] === 'signup') {
        $name = trim($_POST['name']);
        $email = trim($_POST['email']);
        $password = trim($_POST['password']);

        if (empty($name) || empty($email) || empty($password)) {
            $errors[] = "Please fill all signup fields.";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = "Invalid email format.";
        } else {
            $check = $conn->prepare("SELECT * FROM students WHERE email = ? OR name = ?");
            $check->bind_param("ss", $email, $name);
            $check->execute();
            $result = $check->get_result();

            if ($result->fetch_assoc()) {
                $errors[] = "Email or name already exists.";
            } else {
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
    <title>Auth Page</title>
    <style>
        body {
            background: url('auto.jpg');
            background-size: cover;
            height: 100vh;
            font-family: Arial, sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
        }
        .auth-container {
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 0 10px #ccc;
            width: 300px;
        }
        h1 {
            text-align: center;
            color: black;
        }
        form {
            display: none;
        }
        form.active {
            display: block;
        }
        input, button {
            width: 100%;
            padding: 10px;
            margin: 10px 0;
        }
        .switch-link {
            text-align: center;
            margin-top: 10px;
            cursor: pointer;
            color: blue;
            text-decoration: underline;
        }
        .error {
            color: red;
            font-size: 0.9rem;
            text-align: center;
        }
    </style>
</head>
<body>

<div class="auth-container">
    <h1 id="form-title">Login</h1>

    <?php
    if (!empty($errors)) {
        echo '<div class="error">' . implode("<br>", $errors) . '</div>';
    }
    ?>

    <!-- Login Form -->
    <form id="login-form" class="active" method="post">
        <input type="hidden" name="action" value="login">
        <input type="email" name="email" placeholder="Email" required>
        <input type="password" name="password" placeholder="Password" required>
        <button type="submit">Login</button>
    </form>

    <!-- Signup Form -->
    <form id="signup-form" method="post">
        <input type="hidden" name="action" value="signup">
        <input type="text" name="name" placeholder="Name" required>
        <input type="email" name="email" placeholder="Email" required>
        <input type="password" name="password" placeholder="Password" required>
        <button type="submit">Signup</button>
    </form>

    <div class="switch-link" onclick="toggleForm()">Don't have an account? Sign up</div>
</div>

<script>
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
