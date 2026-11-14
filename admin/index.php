<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/app/bootstrap.php';
require_admin();

$counts = [
    'posts' => (int) db()->query('SELECT COUNT(*) FROM posts')->fetchColumn(),
    'published' => (int) db()->query("SELECT COUNT(*) FROM posts WHERE status = 'published'")->fetchColumn(),
    'drafts' => (int) db()->query("SELECT COUNT(*) FROM posts WHERE status = 'draft'")->fetchColumn(),
    'categories' => (int) db()->query('SELECT COUNT(*) FROM categories')->fetchColumn(),
];
$recentPosts = db()->query(
    'SELECT p.id, p.title, p.status, p.updated_at, c.name AS category_name
     FROM posts p JOIN categories c ON c.id = p.category_id
     ORDER BY p.updated_at DESC LIMIT 6'
)->fetchAll();
$adminTitle = 'Dashboard';
require __DIR__ . '/includes/header.php';
?>
<section class="stat-grid">
  <div class="stat-card"><span>All posts</span><strong><?= $counts['posts'] ?></strong></div>
  <div class="stat-card"><span>Published</span><strong><?= $counts['published'] ?></strong></div>
  <div class="stat-card"><span>Drafts</span><strong><?= $counts['drafts'] ?></strong></div>
  <div class="stat-card"><span>Categories</span><strong><?= $counts['categories'] ?></strong></div>
</section>
<section class="admin-panel">
  <div class="panel-heading"><div><h2>Recent posts</h2><p>Your latest content changes</p></div><a class="button" href="<?= e(url('admin/posts/edit.php')) ?>">New post</a></div>
  <?php if (!$recentPosts): ?><div class="empty-state compact"><p>Your posts will appear here once you create one.</p><a href="<?= e(url('admin/posts/edit.php')) ?>">Write your first post</a></div>
  <?php else: ?>
    <div class="table-wrap"><table><thead><tr><th>Title</th><th>Category</th><th>Status</th><th>Updated</th><th></th></tr></thead><tbody>
      <?php foreach ($recentPosts as $post): ?><tr><td class="table-title"><?= e($post['title']) ?></td><td><?= e($post['category_name']) ?></td><td><span class="status <?= e($post['status']) ?>"><?= e(ucfirst($post['status'])) ?></span></td><td><?= e(date('M j, Y', strtotime($post['updated_at']))) ?></td><td><a class="text-link" href="<?= e(url('admin/posts/edit.php?id=' . (int) $post['id'])) ?>">Edit</a></td></tr><?php endforeach; ?>
    </tbody></table></div>
  <?php endif; ?>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
