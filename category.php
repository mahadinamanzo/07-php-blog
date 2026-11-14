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
        "SELECT p.id, p.title, p.slug, p.excerpt, p.cover_image, COALESCE(p.published_at, p.created_at) AS published_at,
                c.name AS category_name, c.slug AS category_slug, a.name AS author_name
         FROM posts p
         JOIN categories c ON c.id = p.category_id
         JOIN admins a ON a.id = p.author_id
         WHERE p.category_id = :category_id AND p.status = 'published'
         ORDER BY COALESCE(p.published_at, p.created_at) DESC, p.id DESC"
    );
    $query->execute(['category_id' => $category['id']]);
    $posts = $query->fetchAll();
}

$pageTitle = $category ? $category['name'] . ' · SWD Blog' : 'Category not found';
require __DIR__ . '/includes/public_header.php';
?>
<?php if (!$category): ?>
  <div class="empty-state"><p class="eyebrow">404 · Not found</p><h1>Category not found</h1><a class="button" href="<?= e(url()) ?>">Back to home</a></div>
<?php else: ?>
  <section class="page-intro">
    <p class="eyebrow">Category</p>
    <h1><?= e($category['name']) ?></h1>
    <?php if ($category['description']): ?><p><?= e($category['description']) ?></p><?php endif; ?>
  </section>
  <?php if (!$posts): ?>
    <div class="empty-state"><p>No published posts in this category yet.</p></div>
  <?php else: ?>
    <div class="post-grid">
      <?php foreach ($posts as $post): ?>
        <?php require __DIR__ . '/includes/post_card.php'; ?>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
<?php endif; ?>
<?php require __DIR__ . '/includes/public_footer.php'; ?>
