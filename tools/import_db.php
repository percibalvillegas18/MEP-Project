<?php
declare(strict_types=1);

/*
 * Optional CLI installer for environments where phpMyAdmin is not available.
 * The normal XAMPP installation path is still documented in INSTALL.txt.
 */
$db = require __DIR__ . '/../config.php';

try {
    $sqlFile = dirname(__DIR__) . '/database.sql';
    if (!is_readable($sqlFile)) {
        throw new RuntimeException('database.sql file not found in the project root.');
    }
    $db->exec((string)file_get_contents($sqlFile));
    echo "Version 007.4 MySQL/MariaDB schema imported successfully.\n";
} catch (Throwable $e) {
    fwrite(STDERR, "Database import failed: {$e->getMessage()}\n");
    exit(1);
}
