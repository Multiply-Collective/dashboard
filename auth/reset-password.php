<?php
require '../config/config.php';
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$email = $_GET['email'] ?? '';
$token = $_GET['token'] ?? '';
$successMessage = $_SESSION['success_message'] ?? '';
$errorMessage = $_SESSION['error_message'] ?? '';
unset($_SESSION['success_message']);
unset($_SESSION['error_message']);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - Dashboard</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>

<body>
    <div class="container">
        <div class="auth-card">
            <h1>Create New Password</h1>
            <p class="subtitle">Enter your new password below</p>

            <?php if ($successMessage): ?>
                <div class="alert alert-success"><?= htmlspecialchars($successMessage) ?></div>
            <?php endif; ?>

            <?php if ($errorMessage): ?>
                <div class="alert alert-error"><?= htmlspecialchars($errorMessage) ?></div>
            <?php endif; ?>

            <form method="POST" action="update-password.php" class="form">
                <input type="hidden" name="email" value="<?= htmlspecialchars($email) ?>">
                <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">

                <div class="form-group">
                    <label for="password">New Password</label>
                    <input type="password" id="password" name="password" minlength="12" required placeholder="Min. 12 characters">
                    <p class="hint">Must contain: uppercase, lowercase, number, and symbol</p>
                </div>

                <button type="submit" class="btn btn-primary">Update Password</button>
            </form>

            <div class="auth-footer">
                <p>Know your password? <a href="login.php" class="link">Sign in here</a></p>
            </div>
        </div>
    </div>
</body>

</html>