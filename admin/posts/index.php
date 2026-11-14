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
    $statement = db()->prepare('DELETE FROM posts WHERE id = :id');
    $statement->execute(['id' => $id]);
    flash('success', $statement->rowCount() ? 'Post deleted.' : 'Post was already deleted.');
    redirect('admin/posts/');
}

$posts = db()->query(
    'SELECT p.id, p.title, p.status, p.updated_at, c.name AS category_name, a.name AS author_name
     FROM posts p JOIN categories c ON c.id = p.category_id JOIN admins a ON a.id = p.author_id
     ORDER BY p.updated_at DESC'
)->fetchAll();
$adminTitle = 'Posts';
require dirname(__DIR__) . '/includes/header.php';
?>
<section class="admin-panel">
  <div class="panel-heading"><div><h2>All posts</h2><p>Create, edit, and publish your stories.</p></div><a class="button" href="<?= e(url('admin/posts/edit.php')) ?>">New post</a></div>
  <?php if (!$posts): ?><div class="empty-state compact"><p>No posts yet.</p><a href="<?= e(url('admin/posts/edit.php')) ?>">Create your first post</a></div>
  <?php else: ?><div class="table-wrap"><table><thead><tr><th>Title</th><th>Category</th><th>Author</th><th>Status</th><th>Updated</th><th>Actions</th></tr></thead><tbody>
    <?php foreach ($posts as $post): ?><tr><td class="table-title"><?= e($post['title']) ?></td><td><?= e($post['category_name']) ?></td><td><?= e($post['author_name']) ?></td><td><span class="status <?= e($post['status']) ?>"><?= e(ucfirst($post['status'])) ?></span></td><td><?= e(date('M j, Y', strtotime($post['updated_at']))) ?></td><td class="actions"><a class="text-link" href="<?= e(url('admin/posts/edit.php?id=' . (int) $post['id'])) ?>">Edit</a><form method="post" onsubmit="return confirm('Delete this post?')"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $post['id'] ?>"><button class="text-button danger-text" type="submit">Delete</button></form></td></tr><?php endforeach; ?>
  </tbody></table></div><?php endif; ?>
</section>
<?php require dirname(__DIR__) . '/includes/footer.php'; ?>
