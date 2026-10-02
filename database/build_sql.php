<?php
/** Generates database/manbar.sql (schema + seed) for importing through phpMyAdmin in XAMPP. */
require_once __DIR__ . '/../app/helpers.php';
require __DIR__ . '/lib.php';
$sql = "-- MANBAR database (MySQL / MariaDB). Import with phpMyAdmin -> Import.\n"
     . "CREATE DATABASE IF NOT EXISTS `manbar` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;\nUSE `manbar`;\nSET FOREIGN_KEY_CHECKS = 0;\n\n";
foreach (schema_statements('mysql') as $s) $sql .= $s . ";\n\n";
foreach (seed_statements() as $s) $sql .= $s . ";\n";
$sql .= "\nSET FOREIGN_KEY_CHECKS = 1;\n";
file_put_contents(__DIR__ . '/manbar.sql', $sql);
echo "Wrote database/manbar.sql\n";
