<?php
require '../config/config.php';

$email = $_POST['email'];

// 1. Check user exists 
$stmt = $pdo->prepare("SELECT uid FROM Users WHERE email = ?");
$stmt->execute([$email]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user || !isset($user['uid'])) {
    // Don't reveal whether email exists
    exit("If that email exists, a reset link has been sent.");
}

$user_id = $user['uid'];

// 2. Generate token
$token = bin2hex(random_bytes(32));
$hashedToken = password_hash($token, PASSWORD_DEFAULT);
$expires = date("Y-m-d H:i:s", strtotime("+1 hour"));

// 3. Store token
$stmt = $pdo->prepare("
    INSERT INTO password_resets (user_id, token, expires_at)
    VALUES (?, ?, ?)
");
$stmt->execute([$user_id, $hashedToken, $expires]);

// 4. Create reset link
$resetLink = "https://dashboard.msparenti.com/auth/reset-password.php?token=$token&email=" . urlencode($email);

// 5. Send email via PHPMailer
require_once '../mail/mailer.php';

$mail = createMailer();
$mail->addAddress($email);
$mail->Subject = "Password Reset Request";
$mail->Body = "Click this link to reset your password:\n\n" . $resetLink;

$mail->send();

echo "If that email exists, a reset link has been sent.";
