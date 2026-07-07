<?php

require __DIR__ . '/common.php';

if (!isset($_SESSION['uid'])) {
    header('Location: ../auth/login.php');
    exit;
}

$editId = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$editingForm = $editId ? get_form($pdo, $editId) : null;
$existingFields = $editingForm ? get_form_fields($pdo, $editId) : [];
$mappingOptions = get_mapping_options($pdo);
$roleOptions = get_role_options();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $formId = isset($_POST['form_id']) ? (int) $_POST['form_id'] : 0;
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $requireLogin = isset($_POST['require_login']) ? 1 : 0;
    $requiredRole = trim($_POST['required_role'] ?? '');
    $fieldLabels = $_POST['field_label'] ?? [];
    $fieldTypes = $_POST['field_type'] ?? [];
    $fieldKeys = $_POST['field_key'] ?? [];
    $fieldRequired = $_POST['field_required'] ?? [];
    $fieldMappings = $_POST['field_mapping'] ?? [];
    $fieldOptionValues = $_POST['field_option_values'] ?? [];
    $fieldAllowOther = $_POST['field_allow_other'] ?? [];

    if ($formId) {
        $stmt = $pdo->prepare('UPDATE forms SET title = ?, description = ?, require_login = ?, required_role = ? WHERE id = ?');
        $stmt->execute([$title, $description, $requireLogin, $requiredRole ?: null, $formId]);
    } else {
        $slug = generate_unique_slug($pdo, $title);
        $stmt = $pdo->prepare('INSERT INTO forms (title, slug, description, require_login, required_role, created_by) VALUES (?, ?, ?, ?, ?, ?)');
        $stmt->execute([$title, $slug, $description, $requireLogin, $requiredRole ?: null, (int) ($_SESSION['uid'] ?? 0)]);
        $formId = (int) $pdo->lastInsertId();
    }

    $pdo->prepare('DELETE FROM form_fields WHERE form_id = ?')->execute([$formId]);

    foreach ($fieldLabels as $index => $label) {
        $label = trim((string) $label);
        if ($label === '') {
            continue;
        }

        $key = trim((string) ($fieldKeys[$index] ?? '')) ?: slugify($label);
        $mapping = trim((string) ($fieldMappings[$index] ?? ''));
        $mappingParts = $mapping !== '' ? explode('.', $mapping, 2) : [null, null];
        $optionsJson = null;
        $allowOther = isset($fieldAllowOther[$index]) ? 1 : 0;

        if (in_array(($fieldTypes[$index] ?? 'text'), ['radio', 'checkbox'], true)) {
            $optionValues = [];
            foreach ($fieldOptionValues[$index] ?? [] as $value) {
                $cleanValue = trim((string) $value);
                if ($cleanValue !== '') {
                    $optionValues[] = $cleanValue;
                }
            }
            $optionsJson = $optionValues ? encode_field_options($optionValues) : null;
        }

        $stmt = $pdo->prepare('INSERT INTO form_fields (form_id, field_key, label, field_type, placeholder, required, sort_order, mapping_table, mapping_column, options_json, allow_other) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
        $stmt->execute([
            $formId,
            $key,
            $label,
            $fieldTypes[$index] ?? 'text',
            $_POST['field_placeholder'][$index] ?? '',
            isset($fieldRequired[$index]) ? 1 : 0,
            $index,
            $mappingParts[0] ?? null,
            $mappingParts[1] ?? null,
            $optionsJson,
            $allowOther,
        ]);
    }

    header('Location: index.php');
    exit;
}

?>
<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= $editingForm ? 'Edit Form' : 'Create Form' ?></title>
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
            <a class="nav-link" href="../settings.php">Settings</a>
            <a class="nav-link" href="../auth/logout.php">Logout</a>
        </nav>
    </header>

    <main class="container">
        <section class="page-header">
            <div>
                <p class="eyebrow">Form Builder</p>
                <h1><?= $editingForm ? 'Edit Form' : 'Create Form' ?></h1>
                <p class="subtitle">Build a form and map each field to an existing MySQL column.</p>
            </div>
            <div class="button-row">
                <a class="button secondary" href="index.php">Back to Forms</a>
            </div>
        </section>

        <form method="post" class="panel">
            <input type="hidden" name="form_id" value="<?= $editingForm ? (int) $editingForm['id'] : 0 ?>">

            <div class="grid two">
                <label>
                    Form title
                    <input type="text" name="title" value="<?= htmlspecialchars($editingForm['title'] ?? '') ?>" required>
                </label>
                <label>
                    Description
                    <textarea name="description"><?= htmlspecialchars($editingForm['description'] ?? '') ?></textarea>
                </label>
            </div>

            <div class="grid two">
                <label class="checkbox-row">
                    <input type="checkbox" name="require_login" value="1" <?= (!empty($editingForm['require_login'])) ? 'checked' : '' ?>>
                    Require login to submit
                </label>

                <label>
                    Minimum role to access
                    <select name="required_role">
                        <option value="">No minimum role</option>
                        <?php foreach ($roleOptions as $role): ?>
                            <option value="<?= htmlspecialchars($role) ?>" <?= (($editingForm['required_role'] ?? '') === $role) ? 'selected' : '' ?>><?= htmlspecialchars($role) ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
            </div>

            <div class="section-title">
                <h2>Fields</h2>
            </div>
            <div id="field-list">
                <?php $defaultFieldIndex = count($existingFields); ?>
                <?php if ($existingFields): ?>
                    <?php $fieldIndex = 0; ?>
                    <?php foreach ($existingFields as $field): ?>
                        <div class="field-card">
                            <div class="grid two">
                                <label>
                                    Field label
                                    <input type="text" name="field_label[]" value="<?= htmlspecialchars($field['label']) ?>" required>
                                </label>
                                <label>
                                    Field key
                                    <input type="text" name="field_key[]" value="<?= htmlspecialchars($field['field_key']) ?>">
                                </label>
                            </div>
                            <div class="grid two">
                                <label>
                                    Field type
                                    <select name="field_type[]" onchange="toggleChoiceOptions(this.closest('.field-card'))">
                                        <?php foreach (['text', 'email', 'number', 'textarea', 'select', 'radio', 'checkbox', 'date'] as $type): ?>
                                            <option value="<?= $type ?>" <?= $field['field_type'] === $type ? 'selected' : '' ?>><?= ucfirst($type) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </label>
                                <label>
                                    Placeholder
                                    <input type="text" name="field_placeholder[]" value="<?= htmlspecialchars($field['placeholder'] ?? '') ?>">
                                </label>
                            </div>
                            <label>
                                Database mapping
                                <select name="field_mapping[]">
                                    <option value="">No mapping</option>
                                    <?php foreach ($mappingOptions as $option): ?>
                                        <option value="<?= htmlspecialchars($option['value']) ?>" <?= ($field['mapping_table'] . '.' . $field['mapping_column']) === $option['value'] ? 'selected' : '' ?>><?= htmlspecialchars($option['label']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </label>
                            <div class="choice-editor" style="display: <?= in_array($field['field_type'], ['radio', 'checkbox'], true) ? 'block' : 'none' ?>;">
                                <div class="choice-options-list" data-field-index="<?= $fieldIndex ?>">
                                    <?php $fieldOptionList = parse_field_options($field['options_json'] ?? null); ?>
                                    <?php if (!$fieldOptionList): $fieldOptionList = [''];
                                    endif; ?>
                                    <?php foreach ($fieldOptionList as $optionValue): ?>
                                        <div class="option-row">
                                            <input type="text" name="field_option_values[<?= $fieldIndex ?>][]" value="<?= htmlspecialchars($optionValue) ?>" placeholder="Option value">
                                            <button class="button secondary small" type="button" onclick="removeOptionRow(this)">Remove</button>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                                <button class="button secondary small" type="button" onclick="addOptionRow(this)">Add option</button>
                                <label class="checkbox-row">
                                    <input type="checkbox" name="field_allow_other[]" value="1" <?= (int) ($field['allow_other'] ?? 0) === 1 ? 'checked' : '' ?>>
                                    Allow “Other” response
                                </label>
                            </div>
                            <label class="checkbox-row">
                                <input type="checkbox" name="field_required[]" value="1" <?= (int) $field['required'] === 1 ? 'checked' : '' ?>>
                                Required field
                            </label>
                        </div>
                        <?php $fieldIndex++; ?>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="field-card">
                        <div class="grid two">
                            <label>
                                Field label
                                <input type="text" name="field_label[]" required>
                            </label>
                            <label>
                                Field key
                                <input type="text" name="field_key[]">
                            </label>
                        </div>
                        <div class="grid two">
                            <label>
                                Field type
                                <select name="field_type[]" onchange="toggleChoiceOptions(this.closest('.field-card'))">
                                    <?php foreach (['text', 'email', 'number', 'textarea', 'select', 'radio', 'checkbox', 'date'] as $type): ?>
                                        <option value="<?= $type ?>"><?= ucfirst($type) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </label>
                            <label>
                                Placeholder
                                <input type="text" name="field_placeholder[]">
                            </label>
                        </div>
                        <label>
                            Database mapping
                            <select name="field_mapping[]">
                                <option value="">No mapping</option>
                                <?php foreach ($mappingOptions as $option): ?>
                                    <option value="<?= htmlspecialchars($option['value']) ?>"><?= htmlspecialchars($option['label']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </label>
                        <div class="choice-editor" style="display:none;">
                            <div class="choice-options-list" data-field-index="<?= $defaultFieldIndex ?>">
                                <div class="option-row">
                                    <input type="text" name="field_option_values[<?= $defaultFieldIndex ?>][]" value="" placeholder="Option value">
                                    <button class="button secondary small" type="button" onclick="removeOptionRow(this)">Remove</button>
                                </div>
                            </div>
                            <button class="button secondary small" type="button" onclick="addOptionRow(this)">Add option</button>
                            <label class="checkbox-row">
                                <input type="checkbox" name="field_allow_other[]" value="1">
                                Allow “Other” response
                            </label>
                        </div>
                        <label class="checkbox-row">
                            <input type="checkbox" name="field_required[]" value="1">
                            Required field
                        </label>
                    </div>
                <?php endif; ?>
            </div>

            <div class="button-row">
                <button class="button secondary" type="button" onclick="addField()">Add Field</button>
                <button class="button" type="submit">Save Form</button>
            </div>
        </form>
    </main>

    <script>
        let fieldCounter = <?= $defaultFieldIndex ?>;

        function toggleChoiceOptions(card) {
            const select = card.querySelector('select[name="field_type[]"]');
            const editor = card.querySelector('.choice-editor');
            if (!editor) {
                return;
            }
            const show = select && ['radio', 'checkbox'].includes(select.value);
            editor.style.display = show ? 'block' : 'none';
        }

        function addOptionRow(button) {
            const list = button.previousElementSibling;
            const row = document.createElement('div');
            row.className = 'option-row';
            const fieldIndex = list.dataset.fieldIndex;
            row.innerHTML = `<input type="text" name="field_option_values[${fieldIndex}][]" value="" placeholder="Option value"><button class="button secondary small" type="button" onclick="removeOptionRow(this)">Remove</button>`;
            list.appendChild(row);
        }

        function removeOptionRow(button) {
            button.parentElement.remove();
        }

        function addField() {
            const list = document.getElementById('field-list');
            const card = document.createElement('div');
            card.className = 'field-card';
            const fieldIndex = fieldCounter++;
            card.innerHTML = `
                <div class="grid two">
                    <label>
                        Field label
                        <input type="text" name="field_label[]" required>
                    </label>
                    <label>
                        Field key
                        <input type="text" name="field_key[]">
                    </label>
                </div>
                <div class="grid two">
                    <label>
                        Field type
                        <select name="field_type[]" onchange="toggleChoiceOptions(this.closest('.field-card'))">
                            <option value="text">Text</option>
                            <option value="email">Email</option>
                            <option value="number">Number</option>
                            <option value="textarea">Textarea</option>
                            <option value="select">Select</option>
                            <option value="radio">Radio</option>
                            <option value="checkbox">Checkbox</option>
                            <option value="date">Date</option>
                        </select>
                    </label>
                    <label>
                        Placeholder
                        <input type="text" name="field_placeholder[]">
                    </label>
                </div>
                <label>
                    Database mapping
                    <select name="field_mapping[]">
                        <option value="">No mapping</option>
                        <?php foreach ($mappingOptions as $option): ?>
                            <option value="<?= htmlspecialchars($option['value']) ?>"><?= htmlspecialchars($option['label']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <div class="choice-editor" style="display:none;">
                    <div class="choice-options-list" data-field-index="${fieldIndex}">
                        <div class="option-row">
                            <input type="text" name="field_option_values[${fieldIndex}][]" value="" placeholder="Option value">
                            <button class="button secondary small" type="button" onclick="removeOptionRow(this)">Remove</button>
                        </div>
                    </div>
                    <button class="button secondary small" type="button" onclick="addOptionRow(this)">Add option</button>
                    <label class="checkbox-row">
                        <input type="checkbox" name="field_allow_other[]" value="1">
                        Allow “Other” response
                    </label>
                </div>
                <label class="checkbox-row">
                    <input type="checkbox" name="field_required[]" value="1">
                    Required field
                </label>`;
            list.appendChild(card);
        }

        document.querySelectorAll('.field-card').forEach(toggleChoiceOptions);
    </script>
</body>

</html>