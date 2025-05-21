<?php
require 'includes/config.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $firstName = $_POST['first_name'];
    $lastName = $_POST['last_name'];
    $email = $_POST['email'];
    $password = $_POST['password'];
    $confirmPassword = $_POST['confirm_password'];

    // Validate that passwords match
    if ($password !== $confirmPassword) {
        $error = "Passwords do not match.";
    } elseif (strlen($password) < 8) {
        $error = "Password must be at least 8 characters long.";
    } else {
        // Hash the password
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

        try {
            // Insert user into the database
            $stmt = $pdo->prepare("INSERT INTO Users (first_name, last_name, email, password) VALUES (?, ?, ?, ?)");
            $stmt->execute([$firstName, $lastName, $email, $hashedPassword]);

            // Redirect to login page with success message
            header("Location: login.php?registered=1");
            exit();
        } catch (PDOException $e) {
            if ($e->getCode() == 23000) { // Duplicate entry
                $error = "Email already exists.";
            } else {
                error_log($e->getMessage()); // For debugging
                $error = "Registration failed. Please try again later.";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Plant Watering Reminder</title>
    <link rel="stylesheet" href="assets/css/register.css">
</head>
<body>
    <div class="container">
        <div class="register-form">
            <h1>Plant Watering Reminder</h1>
            <h2>Register</h2>

            <!-- Error Message -->
            <?php if (isset($error)): ?>
                <p class="error-message"><?= $error ?></p>
            <?php endif; ?>

            <!-- Success Message -->
            <?php if (isset($_GET['registered'])): ?>
                <p class="success-message">Registration successful! Please login.</p>
            <?php endif; ?>

            <form method="post">
                <div class="form-group">
                    <label for="first_name">First Name:</label>
                    <input type="text" id="first_name" name="first_name" required>
                </div>
                <div class="form-group">
                    <label for="last_name">Last Name:</label>
                    <input type="text" id="last_name" name="last_name" required>
                </div>
                <div class="form-group">
                    <label for="email">Email:</label>
                    <input type="email" id="email" name="email" required>
                </div>
                <div class="form-group">
                    <label for="password">Password:</label>
                    <input type="password" id="password" name="password" required>
                </div>
                <div class="form-group">
                    <label for="confirm_password">Confirm Password:</label>
                    <input type="password" id="confirm_password" name="confirm_password" required>
                    <small>Passwords must match.</small>
                </div>
                <button type="submit">Register</button>
            </form>
            <p>Already have an account? <a href="login.php">Login here</a></p>
        </div>
    </div>
</body>
</html>