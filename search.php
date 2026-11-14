<?php
declare(strict_types=1);
require_once __DIR__ . '/app/bootstrap.php';

$term = isset($_GET['q']) && is_string($_GET['q']) ? trim($_GET['q']) : '';
$posts = [];
$usedLikeFallback = $term !== '' && mb_strlen($term, 'UTF-8') < 3;
if ($term !== '') {
    $select = "SELECT p.id, p.title, p.slug, p.excerpt, p.cover_image, COALESCE(p.published_at, p.created_at) AS published_at,
                      c.name AS category_name, c.slug AS category_slug, a.name AS author_name";
    $from = ' FROM posts p
              JOIN categories c ON c.id = p.category_id
              JOIN admins a ON a.id = p.author_id
              WHERE p.status = \'published\' AND ';

    if ($usedLikeFallback) {
        $likeTerm = '%' . strtr($term, ['!' => '!!', '%' => '!%', '_' => '!_']) . '%';
        $query = db()->prepare(
            $select . $from . '(p.title LIKE :title_pattern ESCAPE \'!\' OR p.body LIKE :body_pattern ESCAPE \'!\')
             ORDER BY COALESCE(p.published_at, p.created_at) DESC, p.id DESC LIMIT 50'
        );
        $query->execute(['title_pattern' => $likeTerm, 'body_pattern' => $likeTerm]);
    } else {
        $query = db()->prepare(
            $select . ', MATCH(p.title, p.body) AGAINST (:relevance_term IN NATURAL LANGUAGE MODE) AS relevance'
            . $from . 'MATCH(p.title, p.body) AGAINST (:search_term IN NATURAL LANGUAGE MODE)
             ORDER BY relevance DESC, COALESCE(p.published_at, p.created_at) DESC, p.id DESC LIMIT 50'
        );
        $query->execute(['relevance_term' => $term, 'search_term' => $term]);
    }
    $posts = $query->fetchAll();
}

$pageTitle = 'Search · SWD Blog';
require __DIR__ . '/includes/public_header.php';
?>
<section class="page-intro">
  <p class="eyebrow">Search</p>
  <h1>Find a story</h1>
  <form class="search-form" action="<?= e(url('search.php')) ?>" method="get">
    <label class="sr-only" for="q">Search posts</label>
    <input id="q" name="q" type="search" value="<?= e($term) ?>" placeholder="Search articles..." required>
    <button class="button" type="submit">Search</button>
  </form>
</section>
<?php if ($term !== ''): ?>
  <p class="results-label">Results for <strong><?= e($term) ?></strong><?php if ($usedLikeFallback): ?> <span class="muted">(short-query search)</span><?php endif; ?></p>
  <?php if (!$posts): ?>
    <div class="empty-state"><p>No matching published stories were found.</p></div>
  <?php else: ?>
    <div class="post-grid">
      <?php foreach ($posts as $post): ?>
        <?php require __DIR__ . '/includes/post_card.php'; ?>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
<?php endif; ?>
<?php require __DIR__ . '/includes/public_footer.php'; ?>
