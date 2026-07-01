<?php

require '../config/config.php';

$fname = $_POST['first_name'];
$lname = $_POST['last_name'];
$email = trim($_POST['email']);
$phone = preg_replace('/\D/', '', $_POST['phone']); // Remove all non-digit characters
$password = $_POST['password'];
$confirmpassword = $_POST['confirm_password'];

if (!filter_var($email, FILTER_VALIDATE_EMAIL))
    die("Invalid email");

if ($password !== $confirmpassword)
    die("Passwords do not match");

if (
    strlen($password) < 12
    || !preg_match('/[A-Z]/', $password)
    || !preg_match('/[a-z]/', $password)
    || !preg_match('/[0-9]/', $password)
    || !preg_match('/[^A-Za-z0-9]/', $password)
)
    die("Weak password");

$sql = $pdo->prepare("SELECT uid FROM Users WHERE email=?");
$sql->execute([$email]);

if ($sql->fetch())
    die("Email exists");

$hash = password_hash($password, PASSWORD_DEFAULT);

$sql = $pdo->prepare("INSERT INTO Users(first_name,last_name,email,phone,password) VALUES(?,?,?,?,?)");
$sql->execute([$fname, $lname, $email, $phone, $hash]);

header("Location: login.php");
