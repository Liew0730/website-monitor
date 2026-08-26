<?php
require __DIR__ . '/includes/bootstrap.php';
require __DIR__ . '/includes/auth.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : (isset($_POST['id']) ? (int)$_POST['id'] : 0);
$isEdit = $id > 0;
$pageTitle = $isEdit ? 'Edit Website' : 'Add Website';
$active = 'websites';

$errors = [];
$website = ['name' => '', 'url' => '', 'interval_minutes' => 5];

if ($isEdit) {
    $stmt = $pdo->prepare('SELECT * FROM websites WHERE id = ?');
    $stmt->execute([$id]);
    $website = $stmt->fetch();
    if (!$website) {
        $_SESSION['flash'] = ['type' => 'error', 'message' => 'Website not found.'];
        header('Location: websites.php');
        exit;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $url = trim($_POST['url'] ?? '');
    $interval = (int)($_POST['interval_minutes'] ?? 5);

    if ($name === '') $errors[] = 'Website name is required.';
    if ($url === '' || !filter_var($url, FILTER_VALIDATE_URL)) $errors[] = 'A valid URL is required (include http:// or https://).';
    if ($interval < 1) $errors[] = 'Interval must be at least 1 minute.';

    $website = ['name' => $name, 'url' => $url, 'interval_minutes' => $interval];

    if (empty($errors)) {
        if ($isEdit) {
            $stmt = $pdo->prepare('UPDATE websites SET name = ?, url = ?, interval_minutes = ? WHERE id = ?');
            $stmt->execute([$name, $url, $interval, $id]);
            $_SESSION['flash'] = ['type' => 'success', 'message' => 'Website updated.'];
        } else {
            $stmt = $pdo->prepare('INSERT INTO websites (name, url, interval_minutes, status) VALUES (?, ?, ?, "PENDING")');
            $stmt->execute([$name, $url, $interval]);
            $_SESSION['flash'] = ['type' => 'success', 'message' => 'Website added.'];
        }
        header('Location: websites.php');
        exit;
    }
}

require __DIR__ . '/includes/header.php';
?>

<h1><?= $isEdit ? 'Edit Website' : 'Add Website' ?></h1>

<?php if ($errors): ?>
    <div class="flash error">
        <ul>
            <?php foreach ($errors as $e): ?><li><?= h($e) ?></li><?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<form method="POST" action="website_form.php<?= $isEdit ? '?id=' . $id : '' ?>" class="card-form">
    <?php if ($isEdit): ?><input type="hidden" name="id" value="<?= $id ?>"><?php endif; ?>

    <label for="name">Website Name</label>
    <input type="text" id="name" name="name" required value="<?= h($website['name']) ?>" placeholder="My Website">

    <label for="url">Website URL</label>
    <input type="url" id="url" name="url" required value="<?= h($website['url']) ?>" placeholder="https://example.com">

    <label for="interval_minutes">Monitoring Interval (minutes)</label>
    <input type="number" id="interval_minutes" name="interval_minutes" min="1" required value="<?= (int)$website['interval_minutes'] ?>">

    <div class="form-actions">
        <button type="submit" class="btn btn-primary"><?= $isEdit ? 'Update Website' : 'Add Website' ?></button>
        <a href="websites.php" class="btn btn-secondary">Cancel</a>
    </div>
</form>

<?php require __DIR__ . '/includes/footer.php'; ?>
