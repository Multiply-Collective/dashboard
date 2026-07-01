<?php

require '../config/config.php';

$sql = $pdo->prepare("SELECT * FROM Users WHERE email=?");
$sql->execute([$_POST['email']]);

$user = $sql->fetch(PDO::FETCH_ASSOC);

if ($user && password_verify($_POST['password'], $user['password'])) {

    session_regenerate_id(true);

    $_SESSION['uid'] = $user['uid'];
    $_SESSION['name'] = $user['first_name'];

    header("Location: ../dashboard/home.php");
} else {

    die("Invalid Login Attempt");
}
