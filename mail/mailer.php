<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require __DIR__ . '/../vendor/autoload.php';

function createMailer()
{
    $config = require __DIR__ . '/smtp.config.php';

    $mail = new PHPMailer(true);

    $mail->isSMTP();
    $mail->isHTML(true);
    $mail->CharSet = 'UTF-8';

    // $mail->SMTPDebug = 2;
    // $mail->Debugoutput = 'html';
    // $mail->Timeout = 10;


    $mail->Host = $config['host'];

    $mail->SMTPAuth = true;

    $mail->Username = $config['username'];

    $mail->Password = $config['password'];

    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;

    $mail->Port = $config['port'];

    $mail->setFrom(
        $config['username'],
        'Multiply Collective Dashboard'
    );

    return $mail;
}
