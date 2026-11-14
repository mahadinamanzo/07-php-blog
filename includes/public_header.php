<?php
$pageTitle = $pageTitle ?? 'SWD Blog';
$flashMessage = take_flash();
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= e($pageTitle) ?></title>
  <link rel="stylesheet" href="<?= e(url('assets/style.css')) ?>">
</head>
<body>
<header class="site-header">
  <div class="container nav-row">
    <a class="brand" href="<?= e(url()) ?>">SWD<span>Blog</span></a>
    <nav aria-label="Main navigation">
      <a href="<?= e(url()) ?>">Home</a>
      <a href="<?= e(url('admin/login.php')) ?>">Admin</a>
    </nav>
  </div>
</header>
<main class="container main-content">
  <?php if ($flashMessage): ?>
    <div class="alert <?= e($flashMessage['type']) ?>"><?= e($flashMessage['message']) ?></div>
  <?php endif; ?>
