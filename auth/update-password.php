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
    $_SESSION['error_message'] = "Invalid reset request.";
    header('Location: forgot-password.php');
    exit;
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
    $_SESSION['error_message'] = "Invalid reset request.";
    header('Location: forgot-password.php');
    exit;
}

/**
 * 3. Check expiry
 */
if (strtotime($row['expires_at']) < time()) {
    $_SESSION['error_message'] = "Reset link expired. Please request a new one.";
    header('Location: forgot-password.php');
    exit;
}

/**
 * 4. Verify token
 */
if (!password_verify($token, $row['token'])) {
    $_SESSION['error_message'] = "Invalid token.";
    header('Location: forgot-password.php');
    exit;
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

/**
 * 6. Clear used token
 */
$stmt = $pdo->prepare("DELETE FROM password_resets WHERE id = ?");
$stmt->execute([$row['id']]);

/**
 * 7. Send confirmation email
 */
require_once '../mail/mailer.php';

$mail = createMailer();
$mail->addAddress($email);
$mail->Subject = "Password Updated Successfully";

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
                            <h1 style="margin: 0; color: #1f3a5c; font-size: 24px; font-weight: 700;">✓ Password Updated Successfully</h1>
                        </td>
                    </tr>
                    
                    <!-- Content -->
                    <tr>
                        <td style="padding: 30px;">
                            <p style="margin: 0 0 16px; color: #5a7a95; font-size: 14px; line-height: 1.6;">Hello,</p>
                            
                            <!-- Success Box -->
                            <table width="100%" cellpadding="0" cellspacing="0" style="background-color: #e8f5e9; border-left: 4px solid #4caf50; margin: 20px 0;">
                                <tr>
                                    <td style="padding: 16px; color: #2e7d32; font-size: 14px; font-weight: 600;">
                                        Your password has been updated successfully
                                    </td>
                                </tr>
                            </table>
                            
                            <p style="margin: 24px 0 16px; color: #5a7a95; font-size: 14px; line-height: 1.6;">Your Multiply Collective Dashboard account is now secured with your new password. You can sign in immediately with your new credentials.</p>
                            
                            <!-- Button -->
                            <table cellpadding="0" cellspacing="0" style="margin: 30px 0;">
                                <tr>
                                    <td align="center">
                                        <a href="https://dashboard.msparenti.com/auth/login.php" style="display: inline-block; background-color: #2d5a8f; color: #ffffff; padding: 14px 40px; text-decoration: none; border-radius: 8px; font-weight: 600; font-size: 15px; line-height: 1.4; border: 2px solid #2d5a8f;">Sign In to Dashboard</a>
                                    </td>
                                </tr>
                            </table>
                            
                            <!-- Security Warning -->
                            <table width="100%" cellpadding="0" cellspacing="0" style="background-color: #fff3e0; border-left: 4px solid #ff9800; margin: 20px 0;">
                                <tr>
                                    <td style="padding: 16px; color: #e65100; font-size: 12px; line-height: 1.6;">
                                        <strong>Security Note:</strong> If you didn't request this password change or don't recognize this activity, please contact support immediately.
                                    </td>
                                </tr>
                            </table>
                            
                            <p style="margin: 24px 0 8px; color: #5a7a95; font-size: 12px; font-weight: 600; line-height: 1.6;">For your security, we recommend:</p>
                            
                            <ul style="margin: 0 0 24px 20px; padding: 0; color: #5a7a95; font-size: 12px; line-height: 1.8;">
                                <li>Using a strong, unique password</li>
                                <li>Never sharing your password with anyone</li>
                                <li>Keeping your browser and devices updated</li>
                            </ul>
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
$mail->AltBody = "Hello,\n\nYour password has been updated successfully. You can now sign in at:\nhttps://dashboard.msparenti.com/auth/login.php\n\nIf you didn't request this password change or don't recognize this activity, please contact support immediately.\n\nMultiply Collective Dashboard";

try {
    $mail->send();
} catch (Exception $e) {
    // Email send failed, but don't block the user from logging in
    // Log the error if needed
}

$_SESSION['success_message'] = "Your password has been updated successfully. You can now sign in.";
header('Location: login.php');
exit;
