<?php

require 'mailer.php';

try {

    $mail = createMailer();

    $mail->addAddress("m-coder_server@outlook.com");

    $mail->Subject = "SMTP Test";

    $mail->Body = "If you're reading this, PHPMailer is working!";

    $mail->send();

    echo "Email sent successfully.";
} catch (Exception $e) {

    echo "Mailer Error: " . $mail->ErrorInfo;
}
