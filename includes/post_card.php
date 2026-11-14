<article class="post-card">
  <?php if ($post['cover_image']): ?>
    <a class="cover-link" href="<?= e(url('post/' . rawurlencode($post['slug']))) ?>">
      <img class="post-cover" src="<?= e(url('uploads/' . rawurlencode($post['cover_image']))) ?>" alt="<?= e($post['title']) ?>">
    </a>
  <?php else: ?>
    <div class="cover-placeholder" aria-hidden="true"></div>
  <?php endif; ?>
  <div class="post-card-content">
    <a class="category-label" href="<?= e(url('category/' . rawurlencode($post['category_slug']))) ?>"><?= e($post['category_name']) ?></a>
    <h2><a href="<?= e(url('post/' . rawurlencode($post['slug']))) ?>"><?= e($post['title']) ?></a></h2>
    <p><?= e($post['excerpt']) ?></p>
    <div class="post-meta">
      <span><?= e($post['author_name']) ?></span><span aria-hidden="true">·</span>
      <time datetime="<?= e(date(DATE_ATOM, strtotime($post['published_at']))) ?>"><?= e(date('M j, Y', strtotime($post['published_at']))) ?></time>
    </div>
  </div>
</article>
