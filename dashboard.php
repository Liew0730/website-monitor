<?php
require __DIR__ . '/includes/bootstrap.php';
require __DIR__ . '/includes/auth.php';

$pageTitle = 'Dashboard';
$active = 'dashboard';

// ---- Stats ----
$total = $pdo->query('SELECT COUNT(*) c FROM websites')->fetch()['c'];
$up    = $pdo->query("SELECT COUNT(*) c FROM websites WHERE status = 'UP'")->fetch()['c'];
$down  = $pdo->query("SELECT COUNT(*) c FROM websites WHERE status = 'DOWN'")->fetch()['c'];

// ---- Website status table ----
$websites = $pdo->query('SELECT * FROM websites ORDER BY name ASC')->fetchAll();

// ---- Recent alerts (status-change events) derived from logs ----
$recentLogs = $pdo->query('
    SELECT l.*, w.name, w.url
    FROM logs l
    JOIN websites w ON w.id = l.website_id
    ORDER BY l.checked_at DESC
    LIMIT 10
')->fetchAll();

require __DIR__ . '/includes/header.php';
?>

<h1>Dashboard</h1>

<div class="stat-grid">
    <div class="stat-card">
        <div class="stat-value"><?= (int)$total ?></div>
        <div class="stat-label">Total Websites</div>
    </div>
    <div class="stat-card up">
        <div class="stat-value"><?= (int)$up ?></div>
        <div class="stat-label">UP</div>
    </div>
    <div class="stat-card down">
        <div class="stat-value"><?= (int)$down ?></div>
        <div class="stat-label">DOWN</div>
    </div>
</div>

<h2>Live Status</h2>
<div class="table-wrap">
<table>
    <thead>
        <tr>
            <th>Name</th>
            <th>URL</th>
            <th>Status</th>
            <th>Response Time</th>
            <th>Interval</th>
            <th>Last Checked</th>
        </tr>
    </thead>
    <tbody>
        <?php if (empty($websites)): ?>
        <tr><td colspan="6" class="empty">No websites added yet. <a href="websites.php">Add one</a>.</td></tr>
        <?php endif; ?>
        <?php foreach ($websites as $w): ?>
        <tr>
            <td><?= h($w['name']) ?></td>
            <td><a href="<?= h($w['url']) ?>" target="_blank" rel="noopener"><?= h($w['url']) ?></a></td>
            <td>
                <?php if ($w['status'] === 'UP'): ?>
                    <span class="badge badge-up">🟢 UP</span>
                <?php elseif ($w['status'] === 'DOWN'): ?>
                    <span class="badge badge-down">🔴 DOWN</span>
                <?php else: ?>
                    <span class="badge badge-pending">⚪ PENDING</span>
                <?php endif; ?>
            </td>
            <td><?= $w['response_time'] !== null ? (int)$w['response_time'] . ' ms' : '-' ?></td>
            <td><?= (int)$w['interval_minutes'] ?> min</td>
            <td><?php if ($w['last_checked']): ?><span class="live-time" data-datetime="<?= h($w['last_checked']) ?>" title="<?= h($w['last_checked']) ?>"><?= h($w['last_checked']) ?></span><?php else: ?>Never<?php endif; ?></td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>
</div>

<h2>Recent Monitoring Activity</h2>
<div class="table-wrap">
<table>
    <thead>
        <tr>
            <th>Website</th>
            <th>Status</th>
            <th>Response Time</th>
            <th>Checked At</th>
        </tr>
    </thead>
    <tbody>
        <?php if (empty($recentLogs)): ?>
        <tr><td colspan="4" class="empty">No monitoring activity yet.</td></tr>
        <?php endif; ?>
        <?php foreach ($recentLogs as $log): ?>
        <tr>
            <td><?= h($log['name']) ?></td>
            <td>
                <?php if ($log['status'] === 'UP'): ?>
                    <span class="badge badge-up">🟢 UP</span>
                <?php else: ?>
                    <span class="badge badge-down">🔴 DOWN</span>
                <?php endif; ?>
            </td>
            <td><?= (int)$log['response_time'] ?> ms</td>
            <td><span class="live-time" data-datetime="<?= h($log['checked_at']) ?>" title="<?= h($log['checked_at']) ?>"><?= h($log['checked_at']) ?></span></td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>
</div>

<script>
/**
 * Live relative-time updater
 * Finds all <span class="live-time" data-datetime="YYYY-MM-DD HH:MM:SS"> elements
 * and continuously updates their text to a human-friendly "X ago" string.
 */
function timeAgo(dateStr) {
    const now = new Date();
    // MySQL stores local time (same timezone as browser), parse as local time
    const past = new Date(dateStr.replace(' ', 'T'));
    const diff = Math.floor((now - past) / 1000);
    if (isNaN(diff) || diff < 0) return dateStr;
    if (diff < 60)  return diff + 's ago';
    if (diff < 3600) return Math.floor(diff / 60) + ' min ago';
    if (diff < 86400) return Math.floor(diff / 3600) + 'h ago';
    return Math.floor(diff / 86400) + 'd ago';
}

function refreshLiveTimes() {
    document.querySelectorAll('.live-time[data-datetime]').forEach(el => {
        el.textContent = timeAgo(el.dataset.datetime);
    });
}

// Run immediately, then every 10 seconds
refreshLiveTimes();
setInterval(refreshLiveTimes, 10000);

/**
 * Auto-reload the dashboard data every 30 seconds
 * by doing a full page reload (simple & reliable for PHP pages).
 */
setInterval(() => location.reload(), 30000);
</script>
<?php require __DIR__ . '/includes/footer.php'; ?>
