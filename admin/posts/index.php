<?php
declare(strict_types=1);
require_once dirname(__DIR__, 2) . '/app/bootstrap.php';
require_admin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT);
    if (!$id || $id < 1) {
        http_response_code(400);
        exit('Invalid post identifier.');
    }
    $find = db()->prepare('SELECT cover_image FROM posts WHERE id = :id');
    $find->execute(['id' => $id]);
    $coverImage = $find->fetchColumn();

    $statement = db()->prepare('DELETE FROM posts WHERE id = :id');
    $statement->execute(['id' => $id]);
    if ($statement->rowCount()) {
        try {
            delete_cover_image(is_string($coverImage) ? $coverImage : null);
        } catch (RuntimeException $exception) {
            flash('error', 'Post deleted, but its cover image could not be removed: ' . $exception->getMessage());
            redirect('admin/posts/');
        }
    }
    flash('success', $statement->rowCount() ? 'Post deleted.' : 'Post was already deleted.');
    redirect('admin/posts/');
}

$status = isset($_GET['status']) && is_string($_GET['status']) ? $_GET['status'] : '';
if (!in_array($status, ['', 'draft', 'published'], true)) {
    $status = '';
}
$sql = 'SELECT p.id, p.title, p.status, p.updated_at, c.name AS category_name, a.name AS author_name
        FROM posts p JOIN categories c ON c.id = p.category_id JOIN admins a ON a.id = p.author_id';
if ($status !== '') {
    $sql .= ' WHERE p.status = :status';
}
$sql .= ' ORDER BY p.updated_at DESC';
$query = db()->prepare($sql);
$query->execute($status === '' ? [] : ['status' => $status]);
$posts = $query->fetchAll();
$adminTitle = 'Posts';
require dirname(__DIR__) . '/includes/header.php';
?>
<section class="admin-panel">
  <div class="panel-heading"><div><h2>All posts</h2><p>Create, edit, and publish your stories.</p></div><a class="button" href="<?= e(url('admin/posts/edit.php')) ?>">New post</a></div>
  <form class="filter-form" method="get" action="<?= e(url('admin/posts/')) ?>">
    <label for="status-filter">Filter by status</label>
    <select id="status-filter" name="status">
      <option value="" <?= $status === '' ? 'selected' : '' ?>>All statuses</option>
      <option value="draft" <?= $status === 'draft' ? 'selected' : '' ?>>Drafts</option>
      <option value="published" <?= $status === 'published' ? 'selected' : '' ?>>Published</option>
    </select>
    <button class="button button-light" type="submit">Apply</button>
  </form>
  <?php if (!$posts): ?><div class="empty-state compact"><p><?= $status === '' ? 'No posts yet.' : 'No posts match this status.' ?></p><a href="<?= e(url('admin/posts/edit.php')) ?>">Create a post</a></div>
  <?php else: ?><div class="table-wrap"><table><thead><tr><th>Title</th><th>Category</th><th>Author</th><th>Status</th><th>Updated</th><th>Actions</th></tr></thead><tbody>
    <?php foreach ($posts as $post): ?><tr><td class="table-title"><?= e($post['title']) ?></td><td><?= e($post['category_name']) ?></td><td><?= e($post['author_name']) ?></td><td><span class="status <?= e($post['status']) ?>"><?= e(ucfirst($post['status'])) ?></span></td><td><?= e(date('M j, Y', strtotime($post['updated_at']))) ?></td><td class="actions"><a class="text-link" href="<?= e(url('admin/posts/edit.php?id=' . (int) $post['id'])) ?>">Edit</a><form method="post" onsubmit="return confirm('Delete this post?')"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $post['id'] ?>"><button class="text-button danger-text" type="submit">Delete</button></form></td></tr><?php endforeach; ?>
  </tbody></table></div><?php endif; ?>
</section>
<?php require dirname(__DIR__) . '/includes/footer.php'; ?>
