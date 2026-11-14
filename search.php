<?php
declare(strict_types=1);
require_once __DIR__ . '/app/bootstrap.php';

$term = isset($_GET['q']) && is_string($_GET['q']) ? trim($_GET['q']) : '';
$posts = [];
if ($term !== '') {
    $query = db()->prepare(
        "SELECT p.title, p.slug, p.body, p.cover_image, p.published_at, c.name AS category_name, a.name AS author_name,
                MATCH(p.title, p.body) AGAINST (:term IN NATURAL LANGUAGE MODE) AS relevance
         FROM posts p
         JOIN categories c ON c.id = p.category_id
         JOIN admins a ON a.id = p.author_id
         WHERE p.status = 'published'
           AND MATCH(p.title, p.body) AGAINST (:term2 IN NATURAL LANGUAGE MODE)
         ORDER BY relevance DESC, p.published_at DESC LIMIT 50"
    );
    $query->execute(['term' => $term, 'term2' => $term]);
    $posts = $query->fetchAll();
}
$pageTitle = 'Search · SWD Blog';
require __DIR__ . '/includes/public_header.php';
?>
<section class="page-intro"><p class="eyebrow">Search</p><h1>Find a story</h1>
  <form class="search-form" action="<?= e(url('search.php')) ?>" method="get">
    <label class="sr-only" for="q">Search posts</label><input id="q" name="q" type="search" value="<?= e($term) ?>" placeholder="Search articles..." required><button class="button" type="submit">Search</button>
  </form>
</section>
<?php if ($term !== ''): ?>
  <p class="results-label">Results for <strong><?= e($term) ?></strong></p>
  <?php if (!$posts): ?><div class="empty-state"><p>No matching published stories were found.</p></div>
  <?php else: ?><div class="post-grid">
    <?php foreach ($posts as $post): ?>
      <article class="post-card">
        <?php if ($post['cover_image']): ?><a href="<?= e(url('post.php?slug=' . rawurlencode($post['slug']))) ?>"><img class="post-cover" src="<?= e(url('uploads/' . rawurlencode($post['cover_image']))) ?>" alt=""></a><?php else: ?><div class="cover-placeholder"></div><?php endif; ?>
        <div class="post-card-content"><span class="category-label"><?= e($post['category_name']) ?></span><h2><a href="<?= e(url('post.php?slug=' . rawurlencode($post['slug']))) ?>"><?= e($post['title']) ?></a></h2><p><?= e(excerpt($post['body'])) ?></p><div class="post-meta"><?= e($post['author_name']) ?> <span>·</span> <?= e(date('M j, Y', strtotime($post['published_at']))) ?></div></div>
      </article>
    <?php endforeach; ?>
  </div><?php endif; ?>
<?php endif; ?>
<?php require __DIR__ . '/includes/public_footer.php'; ?>
