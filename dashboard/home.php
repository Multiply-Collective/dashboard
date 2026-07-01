<?php

require '../config/config.php';

if (!isset($_SESSION['uid']))

    header('Location: ../auth/login.php');

?>

<link rel="stylesheet" href="../assets/css/style.css">

<div class=nav>Multiply Collective</div>

<div class=container>
    <h1>Welcome <?= $_SESSION['name'] ?></h1>
    <p>You are logged in.</p>
    <a href="../auth/logout.php">Logout</a>
</div>