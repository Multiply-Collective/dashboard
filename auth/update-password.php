<?php
require '../config/config.php';

$email = $_POST['email'];
$token = $_POST['token'];
$newPassword = password_hash($_POST['password'], PASSWORD_DEFAULT);

/**
 * 1. Get user by email (PDO + uid)
 */
$stmt = $pdo->prepare("SELECT uid FROM Users WHERE email = ?");
$stmt->execute([$email]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    exit("Invalid reset request.");
}

$user_id = $user['uid'];

/**
 * 2. Get latest reset record for this user
 */
$stmt = $pdo->prepare("
    SELECT *
    FROM password_resets
    WHERE user_id = ?
    ORDER BY created_at DESC
    LIMIT 1
");

$stmt->execute([$user_id]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$row) {
    exit("Invalid reset request.");
}

/**
 * 3. Check expiry
 */
if (strtotime($row['expires_at']) < time()) {
    exit("Reset link expired.");
}

/**
 * 4. Verify token
 */
if (!password_verify($token, $row['token'])) {
    exit("Invalid token.");
}

/**
 * 5. Update password (PDO + uid)
 */
$stmt = $pdo->prepare("
    UPDATE Users
    SET password = ?
    WHERE uid = ?
");

$stmt->execute([$newPassword, $user_id]);

echo "Password updated successfully.";
