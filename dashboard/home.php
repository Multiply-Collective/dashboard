<?php

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/forms/common.php';

if (!isset($_SESSION['uid'])) {
    header('Location: ../auth/login.php');
    exit;
}

$forms = get_all_forms($pdo);
$submissions = get_all_submissions($pdo);
$canManageUsers = role_rank($_SESSION['role'] ?? 'standard_user') >= role_rank('admin');

?>
<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Dashboard</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>

<body class="dashboard-page">
    <header class="topbar">
        <div class="brand-wrap">
            <a class="brand" href="home.php">Multiply Collective</a>
            <p>Dashboard workspace</p>
        </div>
        <nav class="topnav">
            <a class="nav-link active" href="home.php">Dashboard</a>
            <a class="nav-link" href="forms/index.php">Forms</a>
            <a class="nav-link" href="settings.php">Settings</a>
            <a class="nav-link" href="../auth/logout.php">Logout</a>
        </nav>
    </header>

    <main class="container">
        <section class="hero-card">
            <div class="hero-copy">
                <p class="eyebrow">Dashboard</p>
                <h1>Welcome <?= htmlspecialchars($_SESSION['name'] ?? 'there') ?></h1>
                <p>Manage forms, review submissions, and keep your data collection organized from one place.</p>
            </div>
            <div class="button-row">
                <a class="button" href="forms/index.php">Open Form Builder</a>
                <a class="button secondary" href="../auth/logout.php">Logout</a>
            </div>
        </section>

        <section class="stats-grid">
            <article class="stat-card">
                <h3><?= count($forms) ?></h3>
                <p>Forms</p>
            </article>
            <article class="stat-card">
                <h3><?= count($submissions) ?></h3>
                <p>Submissions</p>
            </article>
        </section>

        <section class="panel">
            <div class="section-title">
                <h2>Quick actions</h2>
            </div>
            <div class="button-row">
                <a class="button" href="forms/index.php">Go to form manager</a>
                <a class="button secondary" href="forms/builder.php">Create a new form</a>
                <a class="button secondary" href="settings.php">Open settings</a>
            </div>
        </section>
    </main>
</body>

</html>