<?php
$userColumns = $conn->query('SHOW COLUMNS FROM users')->fetchAll(PDO::FETCH_COLUMN);

foreach ([
    'gender' => 'VARCHAR(20) NULL AFTER address',
    'birth_date' => 'DATE NULL AFTER gender',
] as $column => $definition) {
    if (!in_array($column, $userColumns, true)) {
        $conn->exec("ALTER TABLE users ADD COLUMN {$column} {$definition}");
    }
}
