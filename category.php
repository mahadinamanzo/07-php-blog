<?php
declare(strict_types=1);
require_once __DIR__ . '/app/bootstrap.php';

$slug = isset($_GET['slug']) && is_string($_GET['slug']) ? trim($_GET['slug']) : '';
$categoryQuery = db()->prepare('SELECT id, name, description FROM categories WHERE slug = :slug LIMIT 1');
$categoryQuery->execute(['slug' => $slug]);
$category = $categoryQuery->fetch();
if (!$category) {
    http_response_code(404);
}
$posts = [];
if ($category) {
    $query = db()->prepare(
        "SELECT p.title, p.slug, p.body, p.cover_image, p.published_at, a.name AS author_name
         FROM posts p JOIN admins a ON a.id = p.author_id
         WHERE p.category_id = :category_id AND p.status = 'published'
         ORDER BY p.published_at DESC, p.id DESC LIMIT 50"
    );
    $query->execute(['category_id' => $category['id']]);
    $posts = $query->fetchAll();
}
$pageTitle = $category ? $category['name'] . ' · SWD Blog' : 'Category not found';
require __DIR__ . '/includes/public_header.php';
?>
<?php if (!$category): ?>
  <div class="empty-state"><h1>Category not found</h1><a class="button" href="<?= e(url()) ?>">Back to home</a></div>
<?php else: ?>
  <section class="page-intro"><p class="eyebrow">Category</p><h1><?= e($category['name']) ?></h1><?php if ($category['description']): ?><p><?= e($category['description']) ?></p><?php endif; ?></section>
  <?php if (!$posts): ?>
    <div class="empty-state"><p>No published posts in this category yet.</p></div>
  <?php else: ?>
    <div class="post-grid">
      <?php foreach ($posts as $post): ?>
        <article class="post-card">
          <?php if ($post['cover_image']): ?><a href="<?= e(url('post.php?slug=' . rawurlencode($post['slug']))) ?>"><img class="post-cover" src="<?= e(url('uploads/' . rawurlencode($post['cover_image']))) ?>" alt=""></a><?php else: ?><div class="cover-placeholder"></div><?php endif; ?>
          <div class="post-card-content"><h2><a href="<?= e(url('post.php?slug=' . rawurlencode($post['slug']))) ?>"><?= e($post['title']) ?></a></h2><p><?= e(excerpt($post['body'])) ?></p><div class="post-meta"><?= e($post['author_name']) ?> <span>·</span> <?= e(date('M j, Y', strtotime($post['published_at']))) ?></div></div>
        </article>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
<?php endif; ?>
<?php require __DIR__ . '/includes/public_footer.php'; ?>
