<?php
require '../config/config.php';

$email = $_POST['email'];

// 1. Check user exists 
$stmt = $pdo->prepare("SELECT uid FROM Users WHERE email = ?");
$stmt->execute([$email]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user || !isset($user['uid'])) {
    // Don't reveal whether email exists - but redirect to forgot password page
    $_SESSION['success_message'] = "If that email exists in our system, a reset link has been sent to your inbox.";
    header('Location: forgot-password.php');
    exit;
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

// Create professional HTML email
$htmlBody = <<<HTML
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body style="margin: 0; padding: 0; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Roboto', 'Oxygen', 'Ubuntu', 'Cantarell', sans-serif; background-color: #f0f5fa; color: #333;">
    
    <table width="100%" cellpadding="0" cellspacing="0" style="background-color: #f0f5fa;">
        <tr>
            <td align="center" style="padding: 40px 20px;">
                
                <table width="600" cellpadding="0" cellspacing="0" style="background-color: #ffffff; border-radius: 8px; border: 1px solid #d4e3f0; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
                    
                    <!-- Header -->
                    <tr>
                        <td style="padding: 40px 30px 20px; text-align: center; border-bottom: 1px solid #d4e3f0;">
                            <h1 style="margin: 0; color: #1f3a5c; font-size: 24px; font-weight: 700;">Password Reset Request</h1>
                        </td>
                    </tr>
                    
                    <!-- Content -->
                    <tr>
                        <td style="padding: 30px;">
                            <p style="margin: 0 0 16px; color: #5a7a95; font-size: 14px; line-height: 1.6;">Hello,</p>
                            
                            <p style="margin: 0 0 24px; color: #5a7a95; font-size: 14px; line-height: 1.6;">We received a request to reset your password for your Multiply Collective Dashboard account. Click the button below to create a new password.</p>
                            
                            <!-- Button -->
                            <table cellpadding="0" cellspacing="0" style="margin: 30px 0;">
                                <tr>
                                    <td align="center">
                                        <a href="{$resetLink}" style="display: inline-block; background-color: #2d5a8f; color: #ffffff; padding: 14px 40px; text-decoration: none; border-radius: 8px; font-weight: 600; font-size: 15px; line-height: 1.4; border: 2px solid #2d5a8f;">Reset Your Password</a>
                                    </td>
                                </tr>
                            </table>
                            
                            <p style="margin: 24px 0 16px; color: #5a7a95; font-size: 13px; line-height: 1.6;"><strong>This link will expire in 1 hour for security reasons.</strong></p>
                            
                            <p style="margin: 24px 0 8px; color: #5a7a95; font-size: 12px; line-height: 1.6; border-top: 1px solid #d4e3f0; padding-top: 16px;">If the button above doesn't work, copy and paste this link into your browser:</p>
                            
                            <p style="margin: 8px 0 24px; color: #2d5a8f; font-size: 11px; line-height: 1.4; word-break: break-all; background-color: #f5f8fc; padding: 12px; border-radius: 4px; border-left: 3px solid #5a8fd6;">{$resetLink}</p>
                            
                            <p style="margin: 24px 0 0; color: #7a95ac; font-size: 12px; line-height: 1.6;">If you didn't request this password reset, please ignore this email or contact support if you have concerns.</p>
                        </td>
                    </tr>
                    
                    <!-- Footer -->
                    <tr>
                        <td style="padding: 24px 30px; text-align: center; border-top: 1px solid #d4e3f0; background-color: #f9fafb;">
                            <p style="margin: 0 0 8px; color: #7a95ac; font-size: 11px;">© 2026 Multiply Collective. All rights reserved.</p>
                            <p style="margin: 0; color: #7a95ac; font-size: 11px;">This is an automated message, please do not reply to this email.</p>
                        </td>
                    </tr>
                </table>
                
            </td>
        </tr>
    </table>
</body>
</html>
HTML;

$mail->Body = $htmlBody;
$mail->AltBody = "Hello,\n\nWe received a request to reset your password for your Multiply Collective Dashboard account.\n\nClick this link to reset your password:\n{$resetLink}\n\nThis link will expire in 1 hour for security reasons.\n\nIf you didn't request this password reset, please ignore this email.\n\nMultiply Collective Dashboard";

$mail->send();

$_SESSION['success_message'] = "If that email exists in our system, a reset link has been sent to your inbox.";
header('Location: forgot-password.php');
exit;
