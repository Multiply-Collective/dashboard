<?php

require __DIR__ . '/common.php';

if (!isset($_SESSION['uid'])) {
    header('Location: ../auth/login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_form_id'])) {
    $deleteId = (int) $_POST['delete_form_id'];
    $stmt = $pdo->prepare('DELETE FROM form_fields WHERE form_id = ?');
    $stmt->execute([$deleteId]);
    $stmt = $pdo->prepare('DELETE FROM form_submissions WHERE form_id = ?');
    $stmt->execute([$deleteId]);
    $stmt = $pdo->prepare('DELETE FROM forms WHERE id = ?');
    $stmt->execute([$deleteId]);
    header('Location: index.php?deleted=1');
    exit;
}

$forms = get_all_forms($pdo);
$submissions = get_all_submissions($pdo);

function form_url(string $slug): string
{
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    return $scheme . '://' . $host . '/dashboard/forms/view.php?slug=' . urlencode($slug);
}

?>
<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Form Builder</title>
    <link rel="stylesheet" href="../../assets/css/style.css">
</head>

<body class="dashboard-page">
    <header class="topbar">
        <div class="brand-wrap">
            <a class="brand" href="../home.php">Multiply Collective</a>
            <p>Dashboard workspace</p>
        </div>
        <nav class="topnav">
            <a class="nav-link" href="../home.php">Dashboard</a>
            <a class="nav-link active" href="index.php">Forms</a>
            <a class="nav-link" href="../auth/logout.php">Logout</a>
        </nav>
    </header>

    <main class="container">
        <section class="page-header">
            <div>
                <p class="eyebrow">Form Builder</p>
                <h1>Create and manage your forms</h1>
                <p class="subtitle">Design forms that map directly to your database and collect structured submissions.</p>
            </div>
            <div class="button-row">
                <a class="button" href="builder.php">Create New Form</a>
            </div>
        </section>

        <?php if (isset($_GET['deleted'])): ?>
            <div class="alert alert-success">Form deleted successfully.</div>
        <?php endif; ?>

        <section class="panel">
            <div class="section-title">
                <h2>Existing Forms</h2>
            </div>
            <?php if (!$forms): ?>
                <div class="empty-state">No forms created yet. Start with a new one to see it here.</div>
            <?php else: ?>
                <div class="list-stack">
                    <?php foreach ($forms as $form): ?>
                        <?php $fieldCount = count(get_form_fields($pdo, (int) $form['id'])); ?>
                        <?php $submissionCount = count(array_filter($submissions, static fn($item) => (int) $item['form_id'] === (int) $form['id'])); ?>
                        <article class="card">
                            <div class="card-top">
                                <div>
                                    <h3><?= htmlspecialchars($form['title']) ?></h3>
                                    <p><?= htmlspecialchars($form['description'] ?: 'No description provided.') ?></p>
                                </div>
                                <span class="badge"><?= $fieldCount ?> field<?= $fieldCount === 1 ? '' : 's' ?></span>
                            </div>
                            <div class="meta-row">
                                <span>Slug: <strong><?= htmlspecialchars($form['slug']) ?></strong></span>
                                <span>Access: <strong><?= (int) $form['require_login'] === 1 ? 'Login required' : 'Public' ?></strong></span>
                                <?php if ((int) $form['require_login'] === 1 && $form['required_role']): ?>
                                    <span>Role: <strong><?= htmlspecialchars($form['required_role']) ?></strong></span>
                                <?php endif; ?>
                            </div>
                            <div class="actions">
                                <a class="button secondary" href="<?= form_url($form['slug']) ?>" target="_blank">Open Link</a>
                                <a class="button secondary" href="view.php?slug=<?= urlencode($form['slug']) ?>&preview=1" target="_blank">Preview</a>
                                <a class="button secondary" href="builder.php?id=<?= (int) $form['id'] ?>">Edit</a>
                                <form method="post" onsubmit="return confirm('Are you sure you want to delete this form?');">
                                    <input type="hidden" name="delete_form_id" value="<?= (int) $form['id'] ?>">
                                    <button class="button danger" type="submit">Delete</button>
                                </form>
                            </div>
                            <p class="tiny">Submissions: <?= $submissionCount ?></p>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>

        <section class="panel">
            <div class="section-title">
                <h2>Recent Submissions</h2>
            </div>
            <?php if (!$submissions): ?>
                <div class="empty-state">No submissions yet.</div>
            <?php else: ?>
                <ul class="list-stack">
                    <?php foreach (array_slice($submissions, 0, 8) as $submission): ?>
                        <li class="card compact">
                            <strong><?= htmlspecialchars($submission['title']) ?></strong>
                            <span class="tiny">Submitted on <?= htmlspecialchars($submission['submitted_at']) ?></span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </section>
    </main>
</body>

</html>