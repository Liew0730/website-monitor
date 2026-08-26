<?php
/**
 * Establishes a PDO database connection using settings from config.php
 * Expects $config to already be loaded by bootstrap.php
 */

if (!isset($config)) {
    $config = require __DIR__ . '/../config/config.php';
}

$dbConf = $config['db'];

try {
    $port = $dbConf['port'] ?? 3306;
    $dsn = "mysql:host={$dbConf['host']};port={$port};dbname={$dbConf['name']};charset={$dbConf['charset']}";
    $pdo = new PDO($dsn, $dbConf['user'], $dbConf['pass'], [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ]);
    $tzOffset = date('P');
    $pdo->exec("SET time_zone = '{$tzOffset}'");
} catch (PDOException $e) {
    die('Database connection failed: ' . $e->getMessage());
}
