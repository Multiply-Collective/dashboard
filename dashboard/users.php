<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/forms/common.php';

if (!isset($_SESSION['uid'])) {
    header('Location: ../auth/login.php');
    exit;
}

if (role_rank($_SESSION['role'] ?? 'standard_user') < role_rank('admin')) {
    http_response_code(403);
    echo 'You do not have permission to manage users.';
    exit;
}

$successMessage = '';
$errorMessage = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $userId = (int) ($_POST['uid'] ?? 0);
    $role = normalize_role(trim($_POST['role'] ?? ''));

    if (!in_array($role, get_role_options(), true)) {
        $errorMessage = 'Invalid role selected.';
    } elseif ($action === 'update_role' && $userId > 0) {
        $stmt = $pdo->prepare('UPDATE Users SET role = ? WHERE uid = ?');
        $stmt->execute([$role, $userId]);
        $successMessage = 'User role updated successfully.';
    } elseif ($action === 'delete_user' && $userId > 0) {
        $stmt = $pdo->prepare('DELETE FROM Users WHERE uid = ?');
        $stmt->execute([$userId]);
        $successMessage = 'User deleted successfully.';
    } else {
        $errorMessage = 'Invalid request.';
    }
}

$stmt = $pdo->query('SELECT uid, first_name, last_name, email, role, last_login_at FROM Users ORDER BY first_name, last_name');
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>User Management</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>

<body class="dashboard-page">
    <header class="topbar">
        <div class="brand-wrap">
            <a class="brand" href="home.php">Multiply Collective</a>
            <p>Manage users and permissions</p>
        </div>
        <nav class="topnav">
            <a class="nav-link" href="home.php">Dashboard</a>
            <a class="nav-link" href="forms/index.php">Forms</a>
            <a class="nav-link" href="settings.php">Settings</a>
            <a class="nav-link" href="../auth/logout.php">Logout</a>
        </nav>
    </header>

    <main class="container">
        <section class="panel">
            <div class="section-title">
                <h2>User management</h2>
                <a class="button secondary" href="home.php">Back to dashboard</a>
            </div>

            <?php if ($successMessage): ?>
                <p style="color: #0f5132; margin-bottom: 1rem;"><?= htmlspecialchars($successMessage) ?></p>
            <?php endif; ?>
            <?php if ($errorMessage): ?>
                <p style="color: #b42318; margin-bottom: 1rem;"><?= htmlspecialchars($errorMessage) ?></p>
            <?php endif; ?>

            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Permission</th>
                            <th>Last login</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $user): ?>
                            <tr>
                                <td><?= htmlspecialchars(trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? ''))) ?></td>
                                <td><?= htmlspecialchars($user['email'] ?? '') ?></td>
                                <td>
                                    <form method="post" style="display: flex; gap: 0.5rem; align-items: center;">
                                        <input type="hidden" name="action" value="update_role">
                                        <input type="hidden" name="uid" value="<?= (int) $user['uid'] ?>">
                                        <select name="role">
                                            <?php foreach (get_role_options() as $roleOption): ?>
                                                <option value="<?= htmlspecialchars($roleOption) ?>" <?= (($user['role'] ?? 'standard_user') === $roleOption) ? 'selected' : '' ?>><?= htmlspecialchars($roleOption) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                        <button class="button" type="submit">Save</button>
                                    </form>
                                </td>
                                <td><?= htmlspecialchars($user['last_login_at'] ?? 'Never') ?></td>
                                <td>
                                    <form method="post" onsubmit="return confirm('Are you sure you want to delete this user? This action cannot be undone.');">
                                        <input type="hidden" name="action" value="delete_user">
                                        <input type="hidden" name="uid" value="<?= (int) $user['uid'] ?>">
                                        <button class="button secondary" type="submit">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </main>
</body>

</html>