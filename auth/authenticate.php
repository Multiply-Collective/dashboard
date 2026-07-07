<?php

require_once '../config/config.php';

$sql = $pdo->prepare("SELECT * FROM Users WHERE email=?");
$sql->execute([$_POST['email']]);

$user = $sql->fetch(PDO::FETCH_ASSOC);

if ($user && password_verify($_POST['password'], $user['password'])) {

    session_regenerate_id(true);

    $role = $user['role'] ?? 'standard_user';
    $updateStmt = $pdo->prepare("UPDATE Users SET role = ?, last_login_at = NOW() WHERE uid = ?");
    $updateStmt->execute([$role, $user['uid']]);

    $_SESSION['uid'] = $user['uid'];
    $_SESSION['name'] = $user['first_name'];
    $_SESSION['role'] = $role;

    header("Location: ../dashboard/home.php");
} else {

    die("Invalid Login Attempt");
}
