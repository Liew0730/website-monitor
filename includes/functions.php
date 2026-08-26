<?php
/**
 * Shared helper functions used across the admin panel and the cron monitor.
 * Expects $config to already be loaded by bootstrap.php
 */

/**
 * Escape a string for safe HTML output.
 */
function h($str)
{
    return htmlspecialchars($str ?? '', ENT_QUOTES, 'UTF-8');
}

/**
 * Send a message to the configured Telegram chat via the Bot API.
 * Returns true on apparent success, false otherwise.
 */
function send_telegram_message($text)
{
    global $config, $pdo;

    // Prefer DB-stored settings (set via the Settings page), fall back to config.php
    $token  = '';
    $chatId = '';
    if (isset($pdo)) {
        try {
            $s = $pdo->prepare("SELECT `key`, `value` FROM settings WHERE `key` IN ('telegram_bot_token','telegram_chat_id')");
            $s->execute();
            $rows = $s->fetchAll(PDO::FETCH_KEY_PAIR);
            $token  = $rows['telegram_bot_token'] ?? '';
            $chatId = $rows['telegram_chat_id']   ?? '';
        } catch (Exception $e) { /* settings table may not exist yet */ }
    }

    // Fall back to config.php
    if ($token  === '') $token  = $config['telegram']['bot_token'] ?? '';
    if ($chatId === '') $chatId = $config['telegram']['chat_id']   ?? '';

    if (empty($token) || empty($chatId) || $token === 'YOUR_BOT_TOKEN_HERE') {
        error_log('Telegram not configured - skipping alert: ' . $text);
        return false;
    }

    $url = "https://api.telegram.org/bot{$token}/sendMessage";

    $params = [
        'chat_id'    => $chatId,
        'text'       => $text,
        'parse_mode' => 'HTML',
    ];

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err      = curl_error($ch);
    curl_close($ch);

    if ($response === false || $httpCode !== 200) {
        error_log("Telegram send failed (HTTP $httpCode): $err");
        return false;
    }

    return true;
}

/**
 * Build a formatted Telegram alert message.
 *
 * @param string $type   ALERT | RECOVERY | WARNING
 * @param array  $site   website row (name, url)
 * @param string $status UP | DOWN
 * @param int    $responseTime in ms
 */
function build_alert_message($type, $site, $status, $responseTime)
{
    $icons = [
        'ALERT'    => '🔴',
        'RECOVERY' => '🟢',
        'WARNING'  => '🟡',
    ];
    $titles = [
        'ALERT'    => 'ALERT: Website DOWN',
        'RECOVERY' => 'RECOVERY: Website back UP',
        'WARNING'  => 'WARNING: Slow response detected',
    ];

    $icon  = $icons[$type] ?? 'ℹ️';
    $title = $titles[$type] ?? $type;
    $time  = date('Y-m-d H:i:s');

    $msg  = "{$icon} <b>{$title}</b>\n\n";
    $msg .= "Website: " . h($site['name']) . "\n";
    $msg .= "URL: " . h($site['url']) . "\n";
    $msg .= "Status: {$status}\n";
    $msg .= "Response Time: {$responseTime} ms\n";
    $msg .= "Time: {$time}";

    return $msg;
}

/**
 * Check a website's availability and measure response time.
 * Returns ['status' => 'UP'|'DOWN', 'response_time' => int(ms)]
 */
function check_website_status($url, $timeoutSeconds = 10)
{
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, $timeoutSeconds);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeoutSeconds);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_MAXREDIRS, 5);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (WebsiteMonitorBot/1.0)');

    $start = microtime(true);
    $result = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    $elapsedMs = (int) round((microtime(true) - $start) * 1000);

    $isUp = ($result !== false) && $httpCode >= 200 && $httpCode < 400;

    return [
        'status'        => $isUp ? 'UP' : 'DOWN',
        'response_time' => $elapsedMs,
        'http_code'     => $httpCode,
    ];
}

/**
 * Human friendly "time ago" formatting for the dashboard.
 */
function time_ago($datetime)
{
    if (empty($datetime)) {
        return 'Never';
    }
    $diff = time() - strtotime($datetime);
    if ($diff < 60) return $diff . 's ago';
    if ($diff < 3600) return floor($diff / 60) . 'm ago';
    if ($diff < 86400) return floor($diff / 3600) . 'h ago';
    return floor($diff / 86400) . 'd ago';
}
