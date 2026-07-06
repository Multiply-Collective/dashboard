<?php

require __DIR__ . '/common.php';

$slug = $_GET['slug'] ?? '';
$form = $slug ? get_form_by_slug($pdo, $slug) : null;
$preview = isset($_GET['preview']) && $_GET['preview'] === '1';

if (!$form) {
    http_response_code(404);
    echo 'Form not found.';
    exit;
}

if ((int) $form['require_login'] === 1 && !isset($_SESSION['uid'])) {
    header('Location: ../auth/login.php');
    exit;
}

$userRole = $_SESSION['role'] ?? null;
if (!user_can_access_form($form, $userRole)) {
    http_response_code(403);
    echo 'You do not have permission to access this form.';
    exit;
}

$fields = get_form_fields($pdo, (int) $form['id']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $submissionData = [];
    $mappedValues = [];

    foreach ($fields as $field) {
        $fieldKey = $field['field_key'];
        $value = '';

        if (in_array($field['field_type'], ['radio', 'checkbox'], true)) {
            $options = parse_field_options($field['options_json'] ?? null);
            if ($field['field_type'] === 'radio') {
                $rawValue = $_POST[$fieldKey] ?? '';
                $value = trim((string) $rawValue);
                if ($value === 'other') {
                    $value = trim((string) ($_POST[$fieldKey . '_other'] ?? ''));
                }
            } else {
                $selected = $_POST[$fieldKey] ?? [];
                if (!is_array($selected)) {
                    $selected = [$selected];
                }
                $selectedValues = array_values(array_filter(array_map(static function ($item): string {
                    return trim((string) $item);
                }, $selected), static function (string $item): bool {
                    return $item !== '';
                }));
                if ((int) ($field['allow_other'] ?? 0) === 1 && in_array('other', $selectedValues, true)) {
                    $otherValue = trim((string) ($_POST[$fieldKey . '_other'] ?? ''));
                    if ($otherValue !== '') {
                        $selectedValues[] = $otherValue;
                    }
                }
                $value = implode(', ', $selectedValues);
            }
        } else {
            $value = trim((string) ($_POST[$fieldKey] ?? ''));
        }

        $submissionData[$fieldKey] = $value;

        if ($field['mapping_table'] && $field['mapping_column']) {
            $mappedValues[$fieldKey] = [
                'table' => $field['mapping_table'],
                'column' => $field['mapping_column'],
                'value' => $value,
            ];
        }
    }

    $stmt = $pdo->prepare('INSERT INTO form_submissions (form_id, submitted_by, submission_data, mapped_values) VALUES (?, ?, ?, ?)');
    $stmt->execute([(int) $form['id'], isset($_SESSION['uid']) ? (int) $_SESSION['uid'] : null, json_encode($submissionData), json_encode($mappedValues)]);

    foreach ($mappedValues as $map) {
        save_mapping_value($pdo, $map['table'], $map['column'], $map['value']);
    }

    $successMessage = 'Submission received successfully.';
}

?>
<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlspecialchars($form['title']) ?></title>
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
            <a class="nav-link" href="index.php">Forms</a>
            <a class="nav-link" href="../auth/logout.php">Logout</a>
        </nav>
    </header>

    <main class="container">
        <section class="panel">
            <p class="eyebrow">Form Preview</p>
            <h1><?= htmlspecialchars($form['title']) ?></h1>
            <p><?= htmlspecialchars($form['description'] ?? '') ?></p>
            <?php if (!empty($successMessage)): ?>
                <div class="alert alert-success"><?= htmlspecialchars($successMessage) ?></div>
            <?php endif; ?>

            <form method="post">
                <?php foreach ($fields as $field): ?>
                    <div class="field-block">
                        <label class="field-label"><?= htmlspecialchars($field['label']) ?></label>
                        <?php if ($field['field_type'] === 'textarea'): ?>
                            <textarea name="<?= htmlspecialchars($field['field_key']) ?>" <?= (int) $field['required'] === 1 ? 'required' : '' ?>><?= htmlspecialchars($field['placeholder'] ?? '') ?></textarea>
                        <?php elseif ($field['field_type'] === 'select'): ?>
                            <select name="<?= htmlspecialchars($field['field_key']) ?>" <?= (int) $field['required'] === 1 ? 'required' : '' ?>>
                                <option value="">Select an option</option>
                                <?php foreach (parse_field_options($field['options_json'] ?? null) as $option): ?>
                                    <option value="<?= htmlspecialchars($option) ?>"><?= htmlspecialchars($option) ?></option>
                                <?php endforeach; ?>
                            </select>
                        <?php elseif ($field['field_type'] === 'radio'): ?>
                            <div class="choice-group">
                                <?php foreach (parse_field_options($field['options_json'] ?? null) as $option): ?>
                                    <label class="choice-option">
                                        <input type="radio" name="<?= htmlspecialchars($field['field_key']) ?>" value="<?= htmlspecialchars($option) ?>" <?= (int) $field['required'] === 1 ? 'required' : '' ?>>
                                        <span><?= htmlspecialchars($option) ?></span>
                                    </label>
                                <?php endforeach; ?>
                                <?php if ((int) ($field['allow_other'] ?? 0) === 1): ?>
                                    <label class="choice-option">
                                        <input type="radio" name="<?= htmlspecialchars($field['field_key']) ?>" value="other" <?= (int) $field['required'] === 1 ? 'required' : '' ?>>
                                        <span>Other</span>
                                    </label>
                                    <input type="text" name="<?= htmlspecialchars($field['field_key']) ?>_other" class="other-input" placeholder="Please specify">
                                <?php endif; ?>
                            </div>
                        <?php elseif ($field['field_type'] === 'checkbox'): ?>
                            <div class="choice-group">
                                <?php foreach (parse_field_options($field['options_json'] ?? null) as $option): ?>
                                    <label class="choice-option">
                                        <input type="checkbox" name="<?= htmlspecialchars($field['field_key']) ?>[]" value="<?= htmlspecialchars($option) ?>">
                                        <span><?= htmlspecialchars($option) ?></span>
                                    </label>
                                <?php endforeach; ?>
                                <?php if ((int) ($field['allow_other'] ?? 0) === 1): ?>
                                    <label class="choice-option">
                                        <input type="checkbox" name="<?= htmlspecialchars($field['field_key']) ?>[]" value="other">
                                        <span>Other</span>
                                    </label>
                                    <input type="text" name="<?= htmlspecialchars($field['field_key']) ?>_other" class="other-input" placeholder="Please specify">
                                <?php endif; ?>
                            </div>
                        <?php elseif ($field['field_type'] === 'date'): ?>
                            <input type="date" name="<?= htmlspecialchars($field['field_key']) ?>" <?= (int) $field['required'] === 1 ? 'required' : '' ?>>
                        <?php elseif ($field['field_type'] === 'number'): ?>
                            <input type="number" name="<?= htmlspecialchars($field['field_key']) ?>" <?= (int) $field['required'] === 1 ? 'required' : '' ?>>
                        <?php elseif ($field['field_type'] === 'email'): ?>
                            <input type="email" name="<?= htmlspecialchars($field['field_key']) ?>" <?= (int) $field['required'] === 1 ? 'required' : '' ?>>
                        <?php else: ?>
                            <input type="text" name="<?= htmlspecialchars($field['field_key']) ?>" placeholder="<?= htmlspecialchars($field['placeholder'] ?? '') ?>" <?= (int) $field['required'] === 1 ? 'required' : '' ?>>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>

                <?php if ($fields): ?>
                    <button class="button" type="submit">Submit</button>
                <?php else: ?>
                    <p>No fields added yet.</p>
                <?php endif; ?>
            </form>
        </section>
    </main>
</body>

</html>