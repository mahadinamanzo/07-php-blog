<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/app/bootstrap.php';

if (current_admin() !== null) {
    redirect('admin/');
}
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $identifier = posted('identifier');
    $password = is_string($_POST['password'] ?? null) ? $_POST['password'] : '';
    if (authenticate_admin($identifier, $password)) {
        redirect('admin/');
    }
    $error = 'The email or password you entered is incorrect.';
}
$flashMessage = take_flash();
?>
<!doctype html>
<html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Admin sign in · SWD Blog</title><link rel="stylesheet" href="<?= e(url('assets/style.css')) ?>"></head>
<body class="login-page">
  <main class="login-card">
    <a class="brand" href="<?= e(url()) ?>">SWD<span>Blog</span></a>
    <p class="eyebrow">Welcome back</p><h1>Admin sign in</h1>
    <p class="muted">Sign in to manage your blog.</p>
    <?php if ($flashMessage): ?><div class="alert <?= e($flashMessage['type']) ?>"><?= e($flashMessage['message']) ?></div><?php endif; ?>
    <?php if ($error !== ''): ?><div class="alert error"><?= e($error) ?></div><?php endif; ?>
    <form class="stack-form" method="post" action="<?= e(url('admin/login.php')) ?>">
      <?= csrf_field() ?>
      <label>Username or email<input type="text" name="identifier" autocomplete="username" required value="<?= e(posted('identifier')) ?>"></label>
      <label>Password<input type="password" name="password" autocomplete="current-password" required></label>
      <button class="button full-width" type="submit">Sign in</button>
    </form>
    <a class="back-link" href="<?= e(url()) ?>">← Back to site</a>
  </main>
</body></html>
