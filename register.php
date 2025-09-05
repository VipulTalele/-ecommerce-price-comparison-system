<?php
session_start();
require "database.php";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST["username"]);
    $email = trim($_POST["email"]);
    $password = $_POST["password"];
    $confirm_password = $_POST["confirm_password"];

    if (empty($username) || empty($email) || empty($password) || empty($confirm_password)) {
        $error = "⚠️ All fields are required!";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "❌ Invalid email format!";
    } elseif ($password !== $confirm_password) {
        $error = "❌ Passwords do not match!";
    } else {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        try {
            $stmt = $pdo->prepare("INSERT INTO users (username, email, password) VALUES (?, ?, ?)");
            $stmt->execute([$username, $email, $hashed_password]);

            $_SESSION["user_id"] = $pdo->lastInsertId();
            $_SESSION["username"] = $username;
            header("Location: index.php");
            exit();
        } catch (PDOException $e) {
            $error = "⚠️ Username or email already exists!";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register | Price Comparison</title>
    <style>
        body { font-family: 'Poppins', sans-serif; text-align: center; background: linear-gradient(to right, #ff416c, #ff4b2b); display: flex; align-items: center; justify-content: center; height: 100vh; }
        .register-container { background: white; padding: 30px; border-radius: 15px; box-shadow: 0 8px 20px rgba(0, 0, 0, 0.2); width: 400px; text-align: center; animation: fadeIn 1s ease-in-out; }
        .register-container h2 { margin-bottom: 20px; color: #333; font-size: 24px; }
        input { width: 100%; padding: 12px; margin: 10px 0; border: 2px solid #ddd; border-radius: 8px; font-size: 16px; transition: 0.3s; }
        input:focus { border-color: #ff416c; outline: none; }
        .register-btn { width: 100%; background: linear-gradient(to right, #6a11cb, #2575fc); color: white; border: none; padding: 12px; margin-top: 15px; border-radius: 8px; font-size: 18px; cursor: pointer; transition: 0.3s; }
        .register-btn:hover { background: linear-gradient(to right, #2575fc, #6a11cb); transform: scale(1.05); }
    </style>
</head>
<body>
    <div class="register-container">
        <h2>📝 Create an Account</h2>
        <?php if (isset($error)) echo "<p class='error'>$error</p>"; ?>
        <form method="POST">
            <input type="text" name="username" placeholder="👤 Username" required>
            <input type="email" name="email" placeholder="📧 Email" required>
            <input type="password" name="password" placeholder="🔑 Password" required>
            <input type="password" name="confirm_password" placeholder="🔑 Confirm Password" required>
            <button type="submit" class="register-btn">Register 🚀</button>
        </form>
    </div>
</body>
</html>
