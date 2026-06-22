<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();
require 'includes/db.php';

// get mode and set empty error
$mode = $_POST['mode'] ?? $_GET['mode'] ?? 'login';
$error = '';

// check if form was submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // handle login form
    if ($mode === 'login') {
        $email = $_POST['email'];
        $password = $_POST['password'];

        $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();

        if ($result && password_verify($password, $result['password_hash'])) {
            $_SESSION['user_id'] = $result['user_id'];
            $_SESSION['role'] = $result['role'];
            $_SESSION['name'] = $result['name'];
            header("Location: " . ($result['role'] === 'advisor' ? 'advisor.php' : 'student.php'));
            exit();
        } else {
            $error = "Invalid email or password.";
        }

    // handle registration form
    } elseif ($mode === 'register') {
        $name = trim($_POST['name']);
        $email = trim($_POST['email']);
        $password = $_POST['password'];
        $confirm = $_POST['confirm_password'];
        $role = 'student';

        if (!$name || !$email || !$password) {
            $error = "All fields are required.";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = "Please enter a valid email address.";
        } elseif (strlen($password) < 8) {
            $error = "Password must be at least 8 characters.";
        } elseif ($password !== $confirm) {
            $error = "Passwords do not match.";
        } else {
            $safe_email = $conn->real_escape_string($email);
            $exists = $conn->query("SELECT user_id FROM users WHERE email = '$safe_email'")->num_rows;
            if ($exists) {
                $error = "An account with that email already exists.";
            } else {
                $hashed = password_hash($password, PASSWORD_BCRYPT);
                $safe_name = $conn->real_escape_string($name);
                $conn->query("INSERT INTO users (name, email, password_hash, role) VALUES ('$safe_name', '$safe_email', '$hashed', '$role')");

                // log them straight in
                $new_user = $conn->query("SELECT * FROM users WHERE email = '$safe_email'")->fetch_assoc();
                $_SESSION['user_id'] = $new_user['user_id'];
                $_SESSION['role'] = $new_user['role'];
                $_SESSION['name'] = $new_user['name'];
                header("Location: student.php");
                exit();
            }
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
  <title>Class Manager</title>
  <link rel="stylesheet" href="css/style.css">
</head>
<body>
  <div class="login-wrap">
    <?php if ($mode === 'register'): ?>
      <div class="login-box">
        <h1>Class Manager</h1>
        <p>Create your student account</p>
        <?php if ($error): ?><p class="error"><?= $error ?></p><?php endif; ?>
        <form method="POST">
          <input type="hidden" name="mode" value="register">
          <input type="text"     name="name"             placeholder="Full name"        required>
          <input type="email"    name="email"            placeholder="Email"            required>
          <input type="password" name="password"         placeholder="Password"         required>
          <input type="password" name="confirm_password" placeholder="Confirm password" required>
          <button type="submit">Create Account</button>
        </form>
        <div class="toggle-link">Already have an account? <a href="index.php">Sign in</a></div>
      </div>
    <?php else: ?>
      <div class="login-box">
        <h1>Class Manager</h1>
          <p class="toggle-link2">by signing in or creating an account you agreeing to the <a href="eula.html">EULA</a></p>
        <?php if ($error): ?><p class="error"><?= $error ?></p><?php endif; ?>
        <form method="POST">
          <input type="hidden" name="mode" value="login">
          <input type="email"    name="email"    placeholder="Email"    required>
          <input type="password" name="password" placeholder="Password" required>
          <button type="submit">Sign in</button>
        </form>
        <div class="toggle-link">New student? <a href="index.php?mode=register">Create an account</a></div>
      </div>
    <?php endif; ?>
  </div>
</body>
</html>