<?php
declare(strict_types=1);
require_once __DIR__ . '/app/bootstrap.php';

$perPage = 6;
$page = isset($_GET['page']) && is_string($_GET['page'])
    ? filter_var($_GET['page'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]])
    : 1;
$page = $page === false ? 1 : (int) $page;

$totalPosts = (int) db()->query("SELECT COUNT(*) FROM posts WHERE status = 'published'")->fetchColumn();
$totalPages = max(1, (int) ceil($totalPosts / $perPage));
$page = min($page, $totalPages);
$offset = ($page - 1) * $perPage;

$query = db()->prepare(
    "SELECT p.id, p.title, p.slug, p.excerpt, p.cover_image, COALESCE(p.published_at, p.created_at) AS published_at,
            c.name AS category_name, c.slug AS category_slug, a.name AS author_name
     FROM posts p
     JOIN categories c ON c.id = p.category_id
     JOIN admins a ON a.id = p.author_id
     WHERE p.status = 'published'
     ORDER BY COALESCE(p.published_at, p.created_at) DESC, p.id DESC
     LIMIT :limit OFFSET :offset"
);
$query->bindValue('limit', $perPage, PDO::PARAM_INT);
$query->bindValue('offset', $offset, PDO::PARAM_INT);
$query->execute();
$posts = $query->fetchAll();
$categories = db()->query(
    "SELECT DISTINCT c.name, c.slug
     FROM categories c
     JOIN posts p ON p.category_id = c.id AND p.status = 'published'
     ORDER BY c.name"
)->fetchAll();

$pageTitle = 'SWD Blog';
require __DIR__ . '/includes/public_header.php';
?>
<section class="hero">
  <p class="eyebrow">Ideas, stories, and perspectives</p>
  <h1>A place for good writing.</h1>
  <p>Discover the latest stories from the SWD community.</p>
  <form class="search-form" action="<?= e(url('search.php')) ?>" method="get">
    <label class="sr-only" for="q">Search posts</label>
    <input id="q" name="q" type="search" placeholder="Search articles..." required>
    <button class="button" type="submit">Search</button>
  </form>
</section>
<?php if ($categories): ?>
  <nav class="category-pills" aria-label="Categories">
    <?php foreach ($categories as $category): ?>
      <a class="pill" href="<?= e(url('category/' . rawurlencode($category['slug']))) ?>"><?= e($category['name']) ?></a>
    <?php endforeach; ?>
  </nav>
<?php endif; ?>
<section class="section-heading"><div><p class="eyebrow">The journal</p><h2>Latest stories</h2></div></section>
<?php if (!$posts): ?>
  <div class="empty-state"><h3>No published posts yet</h3><p>Check back soon for new stories.</p></div>
<?php else: ?>
  <div class="post-grid">
    <?php foreach ($posts as $post): ?>
      <?php require __DIR__ . '/includes/post_card.php'; ?>
    <?php endforeach; ?>
  </div>
  <?php if ($totalPages > 1): ?>
    <nav class="pagination" aria-label="Post pages">
      <?php if ($page > 1): ?><a class="pagination-link" href="<?= e(url('?page=' . ($page - 1))) ?>" rel="prev">← Newer</a><?php endif; ?>
      <div class="pagination-pages">
        <?php for ($pageNumber = 1; $pageNumber <= $totalPages; $pageNumber++): ?>
          <?php if ($pageNumber === $page): ?>
            <span class="pagination-current" aria-current="page"><?= $pageNumber ?></span>
          <?php else: ?>
            <a class="pagination-page" href="<?= e(url('?page=' . $pageNumber)) ?>"><?= $pageNumber ?></a>
          <?php endif; ?>
        <?php endfor; ?>
      </div>
      <?php if ($page < $totalPages): ?><a class="pagination-link" href="<?= e(url('?page=' . ($page + 1))) ?>" rel="next">Older →</a><?php endif; ?>
    </nav>
  <?php endif; ?>
<?php endif; ?>
<?php require __DIR__ . '/includes/public_footer.php'; ?>
