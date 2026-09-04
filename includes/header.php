<?php
require_once __DIR__ . '/auth.php';
require_login();
$pageTitle = $pageTitle ?? 'MEP Projects Portal';
$flash = pull_flash();
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title><?= e($pageTitle) ?></title>
<link rel="icon" href="favicon.ico">
<link rel="stylesheet" href="style.css">
</head>
<body class="app-body">
<div class="app-shell">
<aside class="sidebar">
    <div class="brand-block">
        <img src="assets/img/mark-64.png" alt="MEP" class="brand-mark-img">
        <div><strong>Projects Portal</strong><small>MEP Progress Management</small><small class="app-version-badge"><?= e(app_version_label()) ?></small></div>
    </div>
    <nav>
        <a href="dashboard.php">Dashboard</a>
        <a href="projects.php">Projects</a>
        <a href="select_project.php">MEP Progress Tracker</a>
        <a href="submittals.php">Material Submittals</a>
        <a href="procurement.php">Procurement</a>
        <a href="workplan.php">MEP Work Plan</a>
        <a href="suppliers.php">Suppliers</a>
        <?php if (has_role('admin')): ?>
        <a href="users.php">User Management</a>
        <?php else: ?>
        <a href="users.php">My Profile</a>
        <?php endif; ?>
        <?php if (has_role('admin','project_manager')): ?><a href="mvc.php?route=audit">Audit History</a><?php endif; ?>
    </nav>
    <div class="sidebar-user">
        <span><?= e($_SESSION['name'] ?? '') ?></span>
        <small class="role-badge role-<?= e($_SESSION['role'] ?? 'user') ?>"><?= e(role_label($_SESSION['role'] ?? 'user')) ?></small>
        <form method="post" action="logout.php" class="logout-form">
            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
            <button type="submit" class="logout-link">Sign out</button>
        </form>
    </div>
</aside>
<main class="main-content">
<header class="page-header"><div><p class="eyebrow">MEP MANAGEMENT SYSTEM · <?= e(app_version_label()) ?></p><h1><?= e($pageTitle) ?></h1></div></header>
<?php if ($flash): ?><div class="alert <?= e($flash['type']) ?>"><?= e($flash['message']) ?></div><?php endif; ?>
