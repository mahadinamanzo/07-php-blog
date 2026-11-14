<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/app/bootstrap.php';
require_admin();

$counts = db()->query(
    "SELECT
        (SELECT COUNT(*) FROM posts) AS posts,
        (SELECT COUNT(*) FROM posts WHERE status = 'published') AS published,
        (SELECT COUNT(*) FROM posts WHERE status = 'draft') AS drafts,
        (SELECT COUNT(*) FROM categories) AS categories"
)->fetch();

$categoryCounts = db()->query(
    'SELECT c.id, c.name, COUNT(p.id) AS post_count
     FROM categories c
     LEFT JOIN posts p ON p.category_id = c.id
     GROUP BY c.id, c.name
     ORDER BY post_count DESC, c.name ASC'
)->fetchAll();

$recentPosts = db()->query(
    'SELECT p.id, p.title, p.status, p.updated_at, c.name AS category_name
     FROM posts p
     JOIN categories c ON c.id = p.category_id
     ORDER BY p.updated_at DESC, p.id DESC
     LIMIT 5'
)->fetchAll();

$maxCategoryPosts = max(1, ...array_map(static fn(array $category): int => (int) $category['post_count'], $categoryCounts));
$adminTitle = 'Dashboard';
require __DIR__ . '/includes/header.php';
?>
<section class="stat-grid">
  <div class="stat-card"><span>All posts</span><strong><?= (int) $counts['posts'] ?></strong></div>
  <div class="stat-card"><span>Published</span><strong><?= (int) $counts['published'] ?></strong></div>
  <div class="stat-card"><span>Drafts</span><strong><?= (int) $counts['drafts'] ?></strong></div>
  <div class="stat-card"><span>Categories</span><strong><?= (int) $counts['categories'] ?></strong></div>
</section>
<div class="dashboard-grid">
  <section class="admin-panel">
    <div class="panel-heading"><div><h2>Latest posts</h2><p>Your five most recently updated stories</p></div><a class="button" href="<?= e(url('admin/posts/edit.php')) ?>">New post</a></div>
    <?php if (!$recentPosts): ?>
      <div class="empty-state compact"><p>Your posts will appear here once you create one.</p><a href="<?= e(url('admin/posts/edit.php')) ?>">Write your first post</a></div>
    <?php else: ?>
      <div class="table-wrap"><table><thead><tr><th>Title</th><th>Category</th><th>Status</th><th>Updated</th><th></th></tr></thead><tbody>
        <?php foreach ($recentPosts as $post): ?>
          <tr><td class="table-title"><?= e($post['title']) ?></td><td><?= e($post['category_name']) ?></td><td><span class="status <?= e($post['status']) ?>"><?= e(ucfirst($post['status'])) ?></span></td><td><?= e(date('M j, Y', strtotime($post['updated_at']))) ?></td><td><a class="text-link" href="<?= e(url('admin/posts/edit.php?id=' . (int) $post['id'])) ?>">Edit</a></td></tr>
        <?php endforeach; ?>
      </tbody></table></div>
    <?php endif; ?>
  </section>
  <section class="admin-panel category-stats">
    <div class="panel-heading"><div><h2>Posts by category</h2><p>Content distribution</p></div><a class="text-link" href="<?= e(url('admin/categories/')) ?>">Manage</a></div>
    <?php if (!$categoryCounts): ?>
      <div class="empty-state compact"><p>No categories yet.</p><a href="<?= e(url('admin/categories/edit.php')) ?>">Create a category</a></div>
    <?php else: ?>
      <ul class="category-stat-list">
        <?php foreach ($categoryCounts as $category): ?>
          <?php $categoryPostCount = (int) $category['post_count']; ?>
          <li>
            <div class="category-stat-heading"><span><?= e($category['name']) ?></span><strong><?= $categoryPostCount ?></strong></div>
            <div class="category-stat-track"><span style="width: <?= (int) round($categoryPostCount / $maxCategoryPosts * 100) ?>%"></span></div>
          </li>
        <?php endforeach; ?>
      </ul>
    <?php endif; ?>
  </section>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
