<?php
/**
 * Require an authenticated admin session.
 * Must be included AFTER bootstrap.php (needs session_start already called).
 */

if (empty($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}
