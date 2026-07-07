<?php

require_once dirname(__DIR__, 2) . '/config/config.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

ensure_form_tables($pdo);

function ensure_form_tables(PDO $pdo): void
{
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS forms (
            id INT AUTO_INCREMENT PRIMARY KEY,
            title VARCHAR(255) NOT NULL,
            slug VARCHAR(255) NOT NULL UNIQUE,
            description TEXT NULL,
            require_login TINYINT(1) NOT NULL DEFAULT 0,
            required_role VARCHAR(50) NULL,
            created_by INT NOT NULL DEFAULT 0,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS form_fields (
            id INT AUTO_INCREMENT PRIMARY KEY,
            form_id INT NOT NULL,
            field_key VARCHAR(100) NOT NULL,
            label VARCHAR(255) NOT NULL,
            field_type VARCHAR(50) NOT NULL,
            placeholder VARCHAR(255) NULL,
            required TINYINT(1) NOT NULL DEFAULT 0,
            sort_order INT NOT NULL DEFAULT 0,
            mapping_table VARCHAR(255) NULL,
            mapping_column VARCHAR(255) NULL,
            options_json TEXT NULL,
            allow_other TINYINT(1) NOT NULL DEFAULT 0,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_form_fields_form_id (form_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");

    try {
        $pdo->exec("ALTER TABLE form_fields ADD COLUMN options_json TEXT NULL");
    } catch (Throwable $e) {
    }

    try {
        $pdo->exec("ALTER TABLE form_fields ADD COLUMN allow_other TINYINT(1) NOT NULL DEFAULT 0");
    } catch (Throwable $e) {
    }

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS form_submissions (
            id INT AUTO_INCREMENT PRIMARY KEY,
            form_id INT NOT NULL,
            submitted_by INT NULL,
            submission_data LONGTEXT NOT NULL,
            mapped_values LONGTEXT NULL,
            submitted_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_form_submissions_form_id (form_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
}

function slugify(string $text): string
{
    $text = preg_replace('/[^\p{L}\p{N}]+/u', '-', strtolower(trim($text)));
    $text = trim($text, '-');

    return $text ?: 'form';
}

function generate_unique_slug(PDO $pdo, string $title): string
{
    $base = slugify($title);
    $slug = $base;
    $counter = 1;

    while (true) {
        $stmt = $pdo->prepare('SELECT id FROM forms WHERE slug = ?');
        $stmt->execute([$slug]);

        if (!$stmt->fetch()) {
            return $slug;
        }

        $slug = $base . '-' . $counter;
        $counter++;
    }
}

function get_role_options(): array
{
    return ['standard_user', 'client', 'coach', 'catalyst', 'superintendent', 'regional_director', 'admin'];
}

function normalize_role(string $role): string
{
    $role = strtolower(trim($role));
    return $role === 'regional_manager' ? 'regional_director' : $role;
}

function role_rank(string $role): int
{
    $roles = [
        'standard_user' => 1,
        'client' => 2,
        'coach' => 3,
        'catalyst' => 4,
        'superintendent' => 5,
        'regional_director' => 6,
        'admin' => 7,
    ];

    return $roles[normalize_role($role)] ?? 0;
}

function user_can_access_form(array $form, ?string $userRole = null): bool
{
    if ((int) $form['require_login'] === 0) {
        return true;
    }

    if (!$userRole) {
        return false;
    }

    if (!$form['required_role']) {
        return true;
    }

    return role_rank($userRole) >= role_rank($form['required_role']);
}

function get_mapping_options(PDO $pdo): array
{
    $stmt = $pdo->query("
        SELECT TABLE_NAME AS table_name, COLUMN_NAME AS column_name, DATA_TYPE AS data_type
        FROM information_schema.columns
        WHERE table_schema = DATABASE()
        ORDER BY table_name, ordinal_position
    ");

    $options = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $table = $row['table_name'] ?? '';
        $column = $row['column_name'] ?? '';
        $type = $row['data_type'] ?? '';

        if ($table === '' || $column === '') {
            continue;
        }

        if (in_array($column, ['id', 'created_at', 'updated_at', 'created_by'], true)) {
            continue;
        }

        $options[] = [
            'value' => $table . '.' . $column,
            'label' => $table . '.' . $column . ' (' . $type . ')',
        ];
    }

    return $options;
}

function get_form(PDO $pdo, int $id): ?array
{
    $stmt = $pdo->prepare('SELECT * FROM forms WHERE id = ?');
    $stmt->execute([$id]);

    $form = $stmt->fetch(PDO::FETCH_ASSOC);
    return $form ?: null;
}

function get_form_by_slug(PDO $pdo, string $slug): ?array
{
    $stmt = $pdo->prepare('SELECT * FROM forms WHERE slug = ?');
    $stmt->execute([$slug]);

    $form = $stmt->fetch(PDO::FETCH_ASSOC);
    return $form ?: null;
}

function get_form_fields(PDO $pdo, int $formId): array
{
    $stmt = $pdo->prepare('SELECT * FROM form_fields WHERE form_id = ? ORDER BY sort_order, id');
    $stmt->execute([$formId]);

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function parse_field_options(?string $optionsJson): array
{
    $decoded = json_decode($optionsJson ?? '[]', true);

    if (!is_array($decoded)) {
        return [];
    }

    $options = array_values(array_filter(array_map(static function ($value): string {
        return trim((string) $value);
    }, $decoded), static function (string $value): bool {
        return $value !== '';
    }));

    return $options;
}

function encode_field_options(array $options): string
{
    $clean = array_values(array_filter(array_map(static function ($value): string {
        return trim((string) $value);
    }, $options), static function (string $value): bool {
        return $value !== '';
    }));

    return json_encode($clean);
}

function get_form_submissions(PDO $pdo, int $formId): array
{
    $stmt = $pdo->prepare('SELECT * FROM form_submissions WHERE form_id = ? ORDER BY submitted_at DESC');
    $stmt->execute([$formId]);

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function get_all_forms(PDO $pdo): array
{
    $stmt = $pdo->query('SELECT * FROM forms ORDER BY created_at DESC');
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function get_all_submissions(PDO $pdo): array
{
    $stmt = $pdo->query('SELECT fs.*, f.title FROM form_submissions fs JOIN forms f ON f.id = fs.form_id ORDER BY fs.submitted_at DESC');
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function save_mapping_value(PDO $pdo, string $mappingTable, string $mappingColumn, mixed $value): bool
{
    if (!$mappingTable || !$mappingColumn) {
        return false;
    }

    $escapedTable = preg_replace('/[^a-zA-Z0-9_]/', '', $mappingTable);
    $escapedColumn = preg_replace('/[^a-zA-Z0-9_]/', '', $mappingColumn);

    if ($escapedTable === '' || $escapedColumn === '') {
        return false;
    }

    try {
        if (isset($_SESSION['uid']) && $escapedTable === 'Users' && $escapedColumn !== 'uid') {
            $stmt = $pdo->prepare("UPDATE `{$escapedTable}` SET `{$escapedColumn}` = ? WHERE uid = ?");
            $stmt->execute([$value, (int) $_SESSION['uid']]);
            return true;
        }

        $stmt = $pdo->prepare("INSERT INTO `{$escapedTable}` (`{$escapedColumn}`) VALUES (?)");
        $stmt->execute([$value]);
        return true;
    } catch (Throwable $e) {
        return false;
    }
}
