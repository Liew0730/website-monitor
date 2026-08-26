<?php
/**
 * Bootstrap: session start + config + db connection + helper functions.
 * Every admin-panel page should require this file first.
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$config = require __DIR__ . '/../config/config.php';
date_default_timezone_set($config['timezone'] ?? 'Asia/Kuala_Lumpur');
require __DIR__ . '/db.php';
require __DIR__ . '/functions.php';
