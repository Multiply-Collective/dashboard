<?php
$email = $_GET['email'] ?? '';
$token = $_GET['token'] ?? '';
?>

<form method="POST" action="update-password.php">
    <input type="hidden" name="email" value="<?= htmlspecialchars($email) ?>">
    <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">

    <input type="password" name="password" placeholder="New password" required />
    <button type="submit">Update Password</button>
</form>