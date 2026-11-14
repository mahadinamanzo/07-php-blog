<?php
declare(strict_types=1);
require_once __DIR__ . '/app/bootstrap.php';

$slug = isset($_GET['slug']) && is_string($_GET['slug']) ? trim($_GET['slug']) : '';
$statement = db()->prepare(
    "SELECT p.id, p.title, p.slug, p.body, p.excerpt, p.cover_image, COALESCE(p.published_at, p.created_at) AS published_at,
            p.category_id, c.name AS category_name, c.slug AS category_slug, a.name AS author_name
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

$relatedPosts = [];
$readingMinutes = 1;
if ($post) {
    $wordCount = preg_match_all('/[\p{L}\p{N}]+/u', $post['body']);
    $readingMinutes = max(1, (int) ceil(($wordCount === false ? 0 : $wordCount) / 200));
    $relatedQuery = db()->prepare(
        "SELECT p.id, p.title, p.slug, p.excerpt, p.cover_image, COALESCE(p.published_at, p.created_at) AS published_at,
                c.name AS category_name, c.slug AS category_slug, a.name AS author_name
         FROM posts p
         JOIN categories c ON c.id = p.category_id
         JOIN admins a ON a.id = p.author_id
         WHERE p.status = 'published' AND p.category_id = :category_id AND p.id <> :post_id
         ORDER BY COALESCE(p.published_at, p.created_at) DESC, p.id DESC
         LIMIT 3"
    );
    $relatedQuery->execute(['category_id' => $post['category_id'], 'post_id' => $post['id']]);
    $relatedPosts = $relatedQuery->fetchAll();
}

$pageTitle = $post ? $post['title'] : 'Post not found';
require __DIR__ . '/includes/public_header.php';
?>
<?php if (!$post): ?>
  <div class="empty-state"><p class="eyebrow">404 · Not found</p><h1>Post not found</h1><p>This story may have been removed or is not published.</p><a class="button" href="<?= e(url()) ?>">Back to home</a></div>
<?php else: ?>
  <article class="article">
    <a class="category-label" href="<?= e(url('category/' . rawurlencode($post['category_slug']))) ?>"><?= e($post['category_name']) ?></a>
    <h1><?= e($post['title']) ?></h1>
    <div class="article-meta">
      <span>By <?= e($post['author_name']) ?></span>
      <span aria-hidden="true">·</span>
      <time datetime="<?= e(date(DATE_ATOM, strtotime($post['published_at']))) ?>"><?= e(date('F j, Y', strtotime($post['published_at']))) ?></time>
      <span aria-hidden="true">·</span>
      <span><?= $readingMinutes ?> min read</span>
    </div>
    <?php if ($post['cover_image']): ?>
      <img class="article-cover" src="<?= e(url('uploads/' . rawurlencode($post['cover_image']))) ?>" alt="<?= e($post['title']) ?>">
    <?php endif; ?>
    <div class="article-body"><?= render_post_body($post['body']) ?></div>
  </article>
  <?php if ($relatedPosts): ?>
    <section class="related-section">
      <div class="section-heading"><div><p class="eyebrow">Keep reading</p><h2>Related posts</h2></div></div>
      <div class="post-grid">
        <?php foreach ($relatedPosts as $post): ?>
          <?php require __DIR__ . '/includes/post_card.php'; ?>
        <?php endforeach; ?>
      </div>
    </section>
  <?php endif; ?>
<?php endif; ?>
<?php require __DIR__ . '/includes/public_footer.php'; ?>
