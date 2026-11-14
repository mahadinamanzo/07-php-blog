<?php
$admin = current_admin();
$adminTitle = $adminTitle ?? 'Admin';
$flashMessage = take_flash();
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= e($adminTitle) ?> · SWD Blog</title>
  <link rel="stylesheet" href="<?= e(url('assets/style.css')) ?>">
</head>
<body class="admin-body">
<aside class="sidebar">
  <a class="brand sidebar-brand" href="<?= e(url('admin/')) ?>">SWD<span>Blog</span></a>
  <p class="sidebar-caption">Publishing</p>
  <nav class="sidebar-nav" aria-label="Admin navigation">
    <a href="<?= e(url('admin/')) ?>">Dashboard</a>
    <a href="<?= e(url('admin/posts/')) ?>">Posts</a>
    <a href="<?= e(url('admin/categories/')) ?>">Categories</a>
  </nav>
  <div class="sidebar-bottom">
    <a href="<?= e(url()) ?>" target="_blank" rel="noopener">View site ↗</a>
    <form action="<?= e(url('admin/logout.php')) ?>" method="post">
      <?= csrf_field() ?><button class="sidebar-logout" type="submit">Logout</button>
    </form>
  </div>
</aside>
<main class="admin-main">
  <header class="admin-topbar"><div><p class="eyebrow">Content management</p><h1><?= e($adminTitle) ?></h1></div><div class="admin-user"><?= e($admin['name'] ?? '') ?></div></header>
  <?php if ($flashMessage): ?><div class="alert <?= e($flashMessage['type']) ?>"><?= e($flashMessage['message']) ?></div><?php endif; ?>
