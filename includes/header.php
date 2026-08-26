<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= isset($pageTitle) ? h($pageTitle) . ' - ' : '' ?>Website Monitor</title>
<link rel="stylesheet" href="assets/style.css">
</head>
<body>
<header class="topbar">
    <div class="brand">🌐 Website Monitor</div>
    <?php if (!empty($_SESSION['admin_id'])): ?>
    <nav class="nav">
        <a href="dashboard.php" class="<?= ($active ?? '') === 'dashboard' ? 'active' : '' ?>">Dashboard</a>
        <a href="websites.php"  class="<?= ($active ?? '') === 'websites'  ? 'active' : '' ?>">Websites</a>
        <a href="logs.php"      class="<?= ($active ?? '') === 'logs'      ? 'active' : '' ?>">Logs</a>
        <a href="settings.php"  class="<?= ($active ?? '') === 'settings'  ? 'active' : '' ?>">⚙️ Settings</a>
        <a href="logout.php" class="logout">Logout (<?= h($_SESSION['admin_username'] ?? '') ?>)</a>
    </nav>
    <?php endif; ?>
</header>
<main class="container">
<?php if (!empty($_SESSION['flash'])): ?>
    <div class="flash <?= h($_SESSION['flash']['type']) ?>"><?= h($_SESSION['flash']['message']) ?></div>
    <?php unset($_SESSION['flash']); ?>
<?php endif; ?>
