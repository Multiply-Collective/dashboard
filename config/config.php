<?php
session_start();
$pdo = new PDO("mysql:host=localhost;dbname=Multiply_Collective_db;charset=utf8", "mc_admin", "mc_admin");
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
