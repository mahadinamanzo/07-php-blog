<?php
declare(strict_types=1);
require_once __DIR__ . '/app/bootstrap.php';

$slug = isset($_GET['slug']) && is_string($_GET['slug']) ? trim($_GET['slug']) : '';
$statement = db()->prepare(
    "SELECT p.title, p.slug, p.body, p.cover_image, p.published_at, c.name AS category_name, c.slug AS category_slug, a.name AS author_name
     FROM posts p
     JOIN categories c ON c.id = p.category_id
     JOIN admins a ON a.id = p.author_id
     WHERE p.slug = :slug AND p.status = 'published'
     LIMIT 1"
);
$statement->execute(['slug' => $slug]);
$post = $statement->fetch();
if (!$post) {
    http_response_code(404);
}
$pageTitle = $post ? $post['title'] : 'Post not found';
require __DIR__ . '/includes/public_header.php';
?>
<?php if (!$post): ?>
  <div class="empty-state"><h1>Post not found</h1><p>This story may have been removed or is not published.</p><a class="button" href="<?= e(url()) ?>">Back to home</a></div>
<?php else: ?>
  <article class="article">
    <a class="category-label" href="<?= e(url('category.php?slug=' . rawurlencode($post['category_slug']))) ?>"><?= e($post['category_name']) ?></a>
    <h1><?= e($post['title']) ?></h1>
    <div class="post-meta">By <?= e($post['author_name']) ?> <span>·</span> <?= e(date('F j, Y', strtotime($post['published_at']))) ?></div>
    <?php if ($post['cover_image']): ?>
      <img class="article-cover" src="<?= e(url('uploads/' . rawurlencode($post['cover_image']))) ?>" alt="">
    <?php endif; ?>
    <div class="article-body"><?= nl2br(e($post['body'])) ?></div>
  </article>
<?php endif; ?>
<?php require __DIR__ . '/includes/public_footer.php'; ?>
