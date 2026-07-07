<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/forms/common.php';

if (!isset($_SESSION['uid'])) {
    header('Location: ../auth/login.php');
    exit;
}

$canManageUsers = role_rank($_SESSION['role'] ?? 'standard_user') >= role_rank('admin');
?>
<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Settings</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>

<body class="dashboard-page">
    <header class="topbar">
        <div class="brand-wrap">
            <a class="brand" href="home.php">Multiply Collective</a>
            <p>Account and access settings</p>
        </div>
        <nav class="topnav">
            <a class="nav-link" href="home.php">Dashboard</a>
            <a class="nav-link" href="forms/index.php">Forms</a>
            <a class="nav-link active" href="settings.php">Settings</a>
            <a class="nav-link" href="../auth/logout.php">Logout</a>
        </nav>
    </header>

    <main class="container">
        <section class="panel">
            <div class="section-title">
                <h2>Settings</h2>
            </div>
            <div class="list-stack">
                <?php if ($canManageUsers): ?>
                    <article class="card">
                        <div class="card-top">
                            <div>
                                <h3>User management</h3>
                                <p>Manage signed-in users and assign permission levels.</p>
                            </div>
                        </div>
                        <div class="actions">
                            <a class="button" href="users.php">Open user management</a>
                        </div>
                    </article>
                <?php endif; ?>
                <article class="card">
                    <div class="card-top">
                        <div>
                            <h3>Profile</h3>
                            <p>Update your account details and sign-in information.</p>
                        </div>
                    </div>
                    <div class="actions">
                        <a class="button secondary" href="../auth/logout.php">Log out</a>
                    </div>
                </article>
            </div>
        </section>
    </main>
</body>

</html>