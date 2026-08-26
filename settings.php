<?php
/**
 * ============================================================
 * SETTINGS PAGE — Telegram Bot Configuration
 * ============================================================
 */
require __DIR__ . '/includes/bootstrap.php';
require __DIR__ . '/includes/auth.php';

$pageTitle = 'Settings';
$active    = 'settings';

// ── Auto-create settings table if it doesn't exist ──────────
$pdo->exec("
    CREATE TABLE IF NOT EXISTS settings (
        `key`        VARCHAR(100)  NOT NULL PRIMARY KEY,
        `value`      VARCHAR(2000) NOT NULL DEFAULT '',
        updated_at   DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP
                     ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
");

// ── Helper: read a setting from DB ──────────────────────────
function get_setting(PDO $pdo, string $key, string $default = ''): string
{
    $stmt = $pdo->prepare("SELECT `value` FROM settings WHERE `key` = ?");
    $stmt->execute([$key]);
    $row = $stmt->fetch();
    return $row ? $row['value'] : $default;
}

// ── Helper: write a setting to DB ───────────────────────────
function set_setting(PDO $pdo, string $key, string $value): void
{
    $pdo->prepare("
        INSERT INTO settings (`key`, `value`)
        VALUES (?, ?)
        ON DUPLICATE KEY UPDATE `value` = VALUES(`value`)
    ")->execute([$key, $value]);
}

// ── Determine requested action ───────────────────────────────
$action   = $_POST['action'] ?? '';
$messages = [];  // ['type' => 'success'|'error', 'text' => '...']

// ── Action: Save Telegram settings ──────────────────────────
if ($action === 'save_telegram') {
    $bot_token = trim($_POST['bot_token'] ?? '');
    $chat_id   = trim($_POST['chat_id']   ?? '');

    if ($bot_token === '' || $chat_id === '') {
        $messages[] = ['type' => 'error', 'text' => 'Both Bot Token and Chat ID are required.'];
    } else {
        set_setting($pdo, 'telegram_bot_token', $bot_token);
        set_setting($pdo, 'telegram_chat_id',   $chat_id);
        $messages[] = ['type' => 'success', 'text' => '✅ Telegram settings saved successfully.'];
    }
}

// ── Action: Send test Telegram message ──────────────────────
if ($action === 'test_telegram') {
    $bot_token = trim($_POST['bot_token'] ?? '');
    $chat_id   = trim($_POST['chat_id']   ?? '');

    if ($bot_token === '' || $chat_id === '') {
        $messages[] = ['type' => 'error', 'text' => 'Please enter and save your Bot Token and Chat ID first.'];
    } elseif ($bot_token === 'YOUR_BOT_TOKEN_HERE') {
        $messages[] = ['type' => 'error', 'text' => 'Please replace the placeholder with a real Bot Token.'];
    } else {
        // Save whatever is in the form before testing
        set_setting($pdo, 'telegram_bot_token', $bot_token);
        set_setting($pdo, 'telegram_chat_id',   $chat_id);

        $text    = '🔔 <b>Website Monitor — Test Message</b>' . "\n\n" .
                   'Your Telegram bot is configured correctly! ✅' . "\n" .
                   'Time: ' . date('Y-m-d H:i:s');
        $url     = "https://api.telegram.org/bot{$bot_token}/sendMessage";
        $params  = ['chat_id' => $chat_id, 'text' => $text, 'parse_mode' => 'HTML'];

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => http_build_query($params),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 10,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
        ]);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlErr  = curl_error($ch);
        curl_close($ch);

        if ($response === false) {
            $messages[] = ['type' => 'error', 'text' => "❌ cURL error: {$curlErr}"];
        } else {
            $decoded = json_decode($response, true);
            if ($httpCode === 200 && !empty($decoded['ok'])) {
                $messages[] = ['type' => 'success', 'text' => '✅ Test message sent! Check your Telegram chat.'];
            } else {
                $errDesc = $decoded['description'] ?? $response;
                $messages[] = ['type' => 'error', 'text' => "❌ Telegram API error (HTTP {$httpCode}): {$errDesc}"];
            }
        }
    }
}

// ── Action: Clear Telegram settings ─────────────────────────
if ($action === 'clear_telegram') {
    set_setting($pdo, 'telegram_bot_token', '');
    set_setting($pdo, 'telegram_chat_id',   '');
    $messages[] = ['type' => 'success', 'text' => '🗑️ Telegram settings cleared.'];
}

// ── Load current values for display ─────────────────────────
$bot_token = get_setting($pdo, 'telegram_bot_token');
$chat_id   = get_setting($pdo, 'telegram_chat_id');

// Fall back to config.php values if DB is empty
if ($bot_token === '' && !empty($config['telegram']['bot_token']) && $config['telegram']['bot_token'] !== 'YOUR_BOT_TOKEN_HERE') {
    $bot_token = $config['telegram']['bot_token'];
}
if ($chat_id === '' && !empty($config['telegram']['chat_id']) && $config['telegram']['chat_id'] !== 'YOUR_CHAT_ID_HERE') {
    $chat_id = $config['telegram']['chat_id'];
}

$is_configured = ($bot_token !== '' && $bot_token !== 'YOUR_BOT_TOKEN_HERE' && $chat_id !== '');

require __DIR__ . '/includes/header.php';
?>

<div class="page-head">
    <h1>⚙️ Settings</h1>
</div>

<!-- ── Flash messages ── -->
<?php foreach ($messages as $msg): ?>
    <div class="flash <?= h($msg['type']) ?>"><?= h($msg['text']) ?></div>
<?php endforeach; ?>

<!-- ══════════════════════════════════════════════════════════
     TELEGRAM BOT CONFIGURATION
     ══════════════════════════════════════════════════════════ -->
<div class="settings-section">
    <div class="settings-section-header">
        <span class="settings-icon">📨</span>
        <div>
            <h2>Telegram Bot Notifications</h2>
            <p class="hint">Receive instant alerts when a website goes DOWN or recovers.</p>
        </div>
        <span class="badge <?= $is_configured ? 'badge-up' : 'badge-pending' ?>">
            <?= $is_configured ? '🟢 Configured' : '⚪ Not configured' ?>
        </span>
    </div>

    <form method="POST" action="settings.php" class="settings-form" id="telegram-form">
        <div class="form-grid">
            <!-- Bot Token -->
            <div class="form-group">
                <label for="bot_token">🤖 Bot Token</label>
                <div class="input-wrap">
                    <input
                        type="password"
                        id="bot_token"
                        name="bot_token"
                        value="<?= h($bot_token) ?>"
                        placeholder="1234567890:ABCDefgh..."
                        autocomplete="off"
                        spellcheck="false"
                    >
                    <button type="button" class="toggle-visibility" data-target="bot_token" title="Show/Hide">👁</button>
                </div>
                <p class="hint">Get this from <a href="https://t.me/BotFather" target="_blank" rel="noopener">@BotFather</a> on Telegram.</p>
            </div>

            <!-- Chat ID -->
            <div class="form-group">
                <label for="chat_id">💬 Chat ID</label>
                <div class="input-wrap">
                    <input
                        type="text"
                        id="chat_id"
                        name="chat_id"
                        value="<?= h($chat_id) ?>"
                        placeholder="-1001234567890 or 123456789"
                        autocomplete="off"
                    >
                </div>
                <p class="hint">
                    Message your bot, then visit:<br>
                    <code>https://api.telegram.org/bot<b>&lt;TOKEN&gt;</b>/getUpdates</code><br>
                    and look for <code>"chat":{"id": ...}</code>.
                </p>
            </div>
        </div>

        <!-- Action buttons -->
        <div class="settings-actions">
            <button type="submit" name="action" value="save_telegram" class="btn btn-primary">
                💾 Save Settings
            </button>
            <button type="submit" name="action" value="test_telegram" class="btn btn-test"
                    onclick="return confirmTest()">
                🚀 Send Test Message
            </button>
            <?php if ($is_configured): ?>
            <button type="submit" name="action" value="clear_telegram" class="btn btn-danger btn-small"
                    onclick="return confirm('Clear Telegram settings?')">
                🗑️ Clear
            </button>
            <?php endif; ?>
        </div>
    </form>
</div>

<!-- ══════════════════════════════════════════════════════════
     HOW TO SET UP GUIDE
     ══════════════════════════════════════════════════════════ -->
<div class="settings-section guide-section">
    <div class="settings-section-header">
        <span class="settings-icon">📖</span>
        <div>
            <h2>How to Set Up Telegram Alerts</h2>
            <p class="hint">Follow these steps to get your bot token and chat ID.</p>
        </div>
    </div>
    <ol class="guide-steps">
        <li>
            <strong>Create a bot</strong> — Open Telegram, search for
            <a href="https://t.me/BotFather" target="_blank" rel="noopener">@BotFather</a>
            and send <code>/newbot</code>. Follow the prompts and copy the <strong>Bot Token</strong>.
        </li>
        <li>
            <strong>Start the bot</strong> — Search for your new bot in Telegram and press <strong>Start</strong>.
        </li>
        <li>
            <strong>Get your Chat ID</strong> — Visit this URL in your browser (replace <code>&lt;TOKEN&gt;</code>):<br>
            <code>https://api.telegram.org/bot&lt;TOKEN&gt;/getUpdates</code><br>
            Find the <code>"chat":{"id": ...}</code> value in the JSON response.
        </li>
        <li>
            <strong>Paste both values above</strong> and click <em>Send Test Message</em> to verify.
        </li>
    </ol>
</div>

<script>
function confirmTest() {
    const token = document.getElementById('bot_token').value.trim();
    const chat  = document.getElementById('chat_id').value.trim();
    if (!token || !chat) {
        alert('Please enter both Bot Token and Chat ID before sending a test.');
        return false;
    }
    return confirm('Send a test message to your Telegram chat?');
}

// Toggle password visibility for bot_token
document.querySelectorAll('.toggle-visibility').forEach(btn => {
    btn.addEventListener('click', () => {
        const input = document.getElementById(btn.dataset.target);
        input.type  = input.type === 'password' ? 'text' : 'password';
        btn.textContent = input.type === 'password' ? '👁' : '🙈';
    });
});
</script>

<?php require __DIR__ . '/includes/footer.php'; ?>
