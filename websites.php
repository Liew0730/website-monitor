<?php
require __DIR__ . '/includes/bootstrap.php';
require __DIR__ . '/includes/auth.php';

$pageTitle = 'Manage Websites';
$active = 'websites';

// ---- Handle delete ----
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    $stmt = $pdo->prepare('DELETE FROM websites WHERE id = ?');
    $stmt->execute([(int)$_POST['delete_id']]);
    $_SESSION['flash'] = ['type' => 'success', 'message' => 'Website deleted.'];
    header('Location: websites.php');
    exit;
}

// ---- Search & filter ----
$search = trim($_GET['search'] ?? '');
$filter = $_GET['filter'] ?? 'all'; // all | up | down | today | week

$sql = 'SELECT * FROM websites WHERE 1=1';
$params = [];

if ($search !== '') {
    $sql .= ' AND (name LIKE ? OR url LIKE ?)';
    $params[] = "%$search%";
    $params[] = "%$search%";
}

switch ($filter) {
    case 'up':
        $sql .= " AND status = 'UP'";
        break;
    case 'down':
        $sql .= " AND status = 'DOWN'";
        break;
    case 'today':
        $sql .= ' AND DATE(last_checked) = CURDATE()';
        break;
    case 'week':
        $sql .= ' AND last_checked >= (NOW() - INTERVAL 7 DAY)';
        break;
}

$sql .= ' ORDER BY name ASC';

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$websites = $stmt->fetchAll();

require __DIR__ . '/includes/header.php';
?>

<div class="page-head">
    <h1>Manage Websites</h1>
    <a href="website_form.php" class="btn btn-primary">+ Add Website</a>
</div>

<form method="GET" action="websites.php" class="filter-bar">
    <input type="text" name="search" placeholder="Search by name or URL..." value="<?= h($search) ?>">
    <select name="filter">
        <option value="all"   <?= $filter === 'all' ? 'selected' : '' ?>>All websites</option>
        <option value="up"    <?= $filter === 'up' ? 'selected' : '' ?>>UP only</option>
        <option value="down"  <?= $filter === 'down' ? 'selected' : '' ?>>DOWN only</option>
        <option value="today" <?= $filter === 'today' ? 'selected' : '' ?>>Checked today</option>
        <option value="week"  <?= $filter === 'week' ? 'selected' : '' ?>>Checked last 7 days</option>
    </select>
    <button type="submit" class="btn">Filter</button>
    <a href="websites.php" class="btn btn-secondary">Reset</a>
</form>

<div class="table-wrap">
<table>
    <thead>
        <tr>
            <th>Name</th>
            <th>URL</th>
            <th>Interval</th>
            <th>Status</th>
            <th>Response Time</th>
            <th>Last Checked</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
        <?php if (empty($websites)): ?>
        <tr><td colspan="7" class="empty">No websites found.</td></tr>
        <?php endif; ?>
        <?php foreach ($websites as $w): ?>
        <tr>
            <td><?= h($w['name']) ?></td>
            <td><a href="<?= h($w['url']) ?>" target="_blank" rel="noopener"><?= h($w['url']) ?></a></td>
            <td><?= (int)$w['interval_minutes'] ?> min</td>
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
            <td><?= $w['last_checked'] ? h($w['last_checked']) : 'Never' ?></td>
            <td class="actions">
                <a href="website_form.php?id=<?= (int)$w['id'] ?>" class="btn btn-small">Edit</a>
                <form method="POST" action="websites.php" onsubmit="return confirm('Delete this website?');" style="display:inline;">
                    <input type="hidden" name="delete_id" value="<?= (int)$w['id'] ?>">
                    <button type="submit" class="btn btn-small btn-danger">Delete</button>
                </form>
            </td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
