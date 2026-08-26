<?php
require __DIR__ . '/includes/bootstrap.php';
require __DIR__ . '/includes/auth.php';

$pageTitle = 'Monitoring Logs';
$active = 'logs';

$search = trim($_GET['search'] ?? '');
$filter = $_GET['filter'] ?? 'all'; // all | up | down | today | week

$sql = '
    SELECT l.*, w.name, w.url
    FROM logs l
    JOIN websites w ON w.id = l.website_id
    WHERE 1=1
';
$params = [];

if ($search !== '') {
    $sql .= ' AND (w.name LIKE ? OR w.url LIKE ?)';
    $params[] = "%$search%";
    $params[] = "%$search%";
}

switch ($filter) {
    case 'up':
        $sql .= " AND l.status = 'UP'";
        break;
    case 'down':
        $sql .= " AND l.status = 'DOWN'";
        break;
    case 'today':
        $sql .= ' AND DATE(l.checked_at) = CURDATE()';
        break;
    case 'week':
        $sql .= ' AND l.checked_at >= (NOW() - INTERVAL 7 DAY)';
        break;
}

$sql .= ' ORDER BY l.checked_at DESC LIMIT 500';

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$logs = $stmt->fetchAll();

require __DIR__ . '/includes/header.php';
?>

<h1>Monitoring Logs</h1>

<form method="GET" action="logs.php" class="filter-bar">
    <input type="text" name="search" placeholder="Search by name or URL..." value="<?= h($search) ?>">
    <select name="filter">
        <option value="all"   <?= $filter === 'all' ? 'selected' : '' ?>>All logs</option>
        <option value="up"    <?= $filter === 'up' ? 'selected' : '' ?>>UP only</option>
        <option value="down"  <?= $filter === 'down' ? 'selected' : '' ?>>DOWN only</option>
        <option value="today" <?= $filter === 'today' ? 'selected' : '' ?>>Today</option>
        <option value="week"  <?= $filter === 'week' ? 'selected' : '' ?>>Last 7 days</option>
    </select>
    <button type="submit" class="btn">Filter</button>
    <a href="logs.php" class="btn btn-secondary">Reset</a>
</form>

<p class="hint">Showing latest 500 matching entries.</p>

<div class="table-wrap">
<table>
    <thead>
        <tr>
            <th>Website</th>
            <th>URL</th>
            <th>Status</th>
            <th>Response Time</th>
            <th>Checked At</th>
        </tr>
    </thead>
    <tbody>
        <?php if (empty($logs)): ?>
        <tr><td colspan="5" class="empty">No logs found.</td></tr>
        <?php endif; ?>
        <?php foreach ($logs as $log): ?>
        <tr>
            <td><?= h($log['name']) ?></td>
            <td><a href="<?= h($log['url']) ?>" target="_blank" rel="noopener"><?= h($log['url']) ?></a></td>
            <td>
                <?php if ($log['status'] === 'UP'): ?>
                    <span class="badge badge-up">🟢 UP</span>
                <?php else: ?>
                    <span class="badge badge-down">🔴 DOWN</span>
                <?php endif; ?>
            </td>
            <td><?= (int)$log['response_time'] ?> ms</td>
            <td><?= h($log['checked_at']) ?></td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
