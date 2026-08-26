<?php
/**
 * ============================================================
 * CORE MONITORING ENGINE
 * ============================================================
 * Run this script via cron (CLI), e.g. every 1 minute:
 *   * * * * * /usr/bin/php /path/to/website-monitor/cron/monitor.php
 *
 * For each website whose check is "due" (based on its own
 * interval_minutes), this script:
 *   1. Sends an HTTP request and measures response time
 *   2. Determines UP / DOWN status
 *   3. Saves the result into the logs table
 *   4. Compares with the website's previous status
 *   5. Sends a Telegram alert ONLY when the status changes
 *      (or when response time crosses the slow threshold)
 *   6. Updates the website's current status/last_checked/response_time
 *
 * NOTE: This script does NOT start a session and must remain
 * accessible without login, since it is meant to run from the
 * command line via cron, not from a browser.
 */

// Restrict to CLI execution only, for safety. Comment this out
// temporarily if you need to test by visiting it in a browser.
if (php_sapi_name() !== 'cli') {
    http_response_code(403);
    die('This script can only be run from the command line (cron).');
}

$config = require __DIR__ . '/../config/config.php';
date_default_timezone_set($config['timezone'] ?? 'Asia/Kuala_Lumpur');
require __DIR__ . '/../includes/db.php';
require __DIR__ . '/../includes/functions.php';

$timeout   = $config['monitor']['timeout_seconds'] ?? 10;
$slowMs    = $config['monitor']['slow_threshold_ms'] ?? 3000;

echo "[" . date('Y-m-d H:i:s') . "] Monitoring run started.\n";

// Fetch websites that are due for a check:
// either never checked, or last_checked is older than their own interval.
$stmt = $pdo->query('
    SELECT * FROM websites
    WHERE last_checked IS NULL
       OR last_checked <= (NOW() - INTERVAL interval_minutes MINUTE)
');
$websites = $stmt->fetchAll();

if (empty($websites)) {
    echo "No websites due for checking right now.\n";
    exit(0);
}

foreach ($websites as $site) {
    echo "Checking: {$site['name']} ({$site['url']})... ";

    $result = check_website_status($site['url'], $timeout);
    $newStatus = $result['status'];
    $responseTime = $result['response_time'];
    $previousStatus = $site['status'];

    echo "$newStatus ({$responseTime} ms)\n";

    // 1. Insert into logs (full history)
    $logStmt = $pdo->prepare('
        INSERT INTO logs (website_id, status, response_time, checked_at)
        VALUES (?, ?, ?, NOW())
    ');
    $logStmt->execute([$site['id'], $newStatus, $responseTime]);

    // 2. Update the website's current snapshot
    $updateStmt = $pdo->prepare('
        UPDATE websites
        SET status = ?, response_time = ?, last_checked = NOW()
        WHERE id = ?
    ');
    $updateStmt->execute([$newStatus, $responseTime, $site['id']]);

    // 3. Compare with previous status -> alert only on change
    if ($previousStatus !== 'PENDING' && $newStatus !== $previousStatus) {
        if ($newStatus === 'DOWN') {
            $message = build_alert_message('ALERT', $site, $newStatus, $responseTime);
        } else {
            $message = build_alert_message('RECOVERY', $site, $newStatus, $responseTime);
        }
        send_telegram_message($message);
        echo "  -> Status changed ({$previousStatus} -> {$newStatus}). Telegram alert sent.\n";
    } elseif ($previousStatus === 'PENDING') {
        echo "  -> First check recorded, no alert sent.\n";
    } else {
        echo "  -> Status unchanged, no alert sent.\n";
    }

    // 4. Optional: slow response threshold warning (independent of status change)
    if ($newStatus === 'UP' && $responseTime >= $slowMs) {
        $message = build_alert_message('WARNING', $site, $newStatus, $responseTime);
        send_telegram_message($message);
        echo "  -> Slow response detected ({$responseTime} ms >= {$slowMs} ms). Warning sent.\n";
    }
}

echo "[" . date('Y-m-d H:i:s') . "] Monitoring run finished.\n";
