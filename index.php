<?php
declare(strict_types=1);
require_once __DIR__ . '/app/bootstrap.php';

$pageTitle = 'SWD Blog';
$statement = db()->query(
    "SELECT p.id, p.title, p.slug, p.body, p.cover_image, p.published_at, c.name AS category_name, c.slug AS category_slug, a.name AS author_name
     FROM posts p
     JOIN categories c ON c.id = p.category_id
     JOIN admins a ON a.id = p.author_id
     WHERE p.status = 'published'
     ORDER BY p.published_at DESC, p.id DESC
     LIMIT 20"
);
$posts = $statement->fetchAll();
$categories = db()->query('SELECT name, slug FROM categories ORDER BY name')->fetchAll();
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
      <a class="pill" href="<?= e(url('category.php?slug=' . rawurlencode($category['slug']))) ?>"><?= e($category['name']) ?></a>
    <?php endforeach; ?>
  </nav>
<?php endif; ?>
<section class="section-heading"><div><p class="eyebrow">The journal</p><h2>Latest stories</h2></div></section>
<?php if (!$posts): ?>
  <div class="empty-state"><h3>No published posts yet</h3><p>Check back soon for new stories.</p></div>
<?php else: ?>
  <div class="post-grid">
    <?php foreach ($posts as $post): ?>
      <article class="post-card">
        <?php if ($post['cover_image']): ?>
          <a class="cover-link" href="<?= e(url('post.php?slug=' . rawurlencode($post['slug']))) ?>">
            <img class="post-cover" src="<?= e(url('uploads/' . rawurlencode($post['cover_image']))) ?>" alt="">
          </a>
        <?php else: ?>
          <div class="cover-placeholder" aria-hidden="true"></div>
        <?php endif; ?>
        <div class="post-card-content">
          <a class="category-label" href="<?= e(url('category.php?slug=' . rawurlencode($post['category_slug']))) ?>"><?= e($post['category_name']) ?></a>
          <h3><a href="<?= e(url('post.php?slug=' . rawurlencode($post['slug']))) ?>"><?= e($post['title']) ?></a></h3>
          <p><?= e(excerpt($post['body'])) ?></p>
          <div class="post-meta"><?= e($post['author_name']) ?> <span>·</span> <?= e(date('M j, Y', strtotime($post['published_at']))) ?></div>
        </div>
      </article>
    <?php endforeach; ?>
  </div>
<?php endif; ?>
<?php require __DIR__ . '/includes/public_footer.php'; ?>
