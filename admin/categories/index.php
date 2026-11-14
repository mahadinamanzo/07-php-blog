<?php
declare(strict_types=1);
require_once dirname(__DIR__, 2) . '/app/bootstrap.php';
require_admin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT);
    if (!$id || $id < 1) {
        http_response_code(400);
        exit('Invalid category identifier.');
    }
    $used = db()->prepare('SELECT COUNT(*) FROM posts WHERE category_id = :id');
    $used->execute(['id' => $id]);
    if ((int) $used->fetchColumn() > 0) {
        flash('error', 'This category has posts. Move or delete those posts before deleting the category.');
    } else {
        $delete = db()->prepare('DELETE FROM categories WHERE id = :id');
        $delete->execute(['id' => $id]);
        flash('success', $delete->rowCount() ? 'Category deleted.' : 'Category was already deleted.');
    }
    redirect('admin/categories/');
}

$categories = db()->query(
    'SELECT c.id, c.name, c.slug, c.description, COUNT(p.id) AS post_count
     FROM categories c LEFT JOIN posts p ON p.category_id = c.id
     GROUP BY c.id ORDER BY c.name'
)->fetchAll();
$adminTitle = 'Categories';
require dirname(__DIR__) . '/includes/header.php';
?>
<section class="admin-panel">
  <div class="panel-heading"><div><h2>All categories</h2><p>Organize your posts by topic.</p></div><a class="button" href="<?= e(url('admin/categories/edit.php')) ?>">New category</a></div>
  <?php if (!$categories): ?><div class="empty-state compact"><p>No categories yet.</p><a href="<?= e(url('admin/categories/edit.php')) ?>">Create a category</a></div>
  <?php else: ?><div class="table-wrap"><table><thead><tr><th>Name</th><th>Slug</th><th>Posts</th><th>Actions</th></tr></thead><tbody>
    <?php foreach ($categories as $category): ?><tr><td class="table-title"><?= e($category['name']) ?></td><td><?= e($category['slug']) ?></td><td><?= (int) $category['post_count'] ?></td><td class="actions"><a class="text-link" href="<?= e(url('admin/categories/edit.php?id=' . (int) $category['id'])) ?>">Edit</a><form method="post" onsubmit="return confirm('Delete this category?')"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $category['id'] ?>"><button class="text-button danger-text" type="submit">Delete</button></form></td></tr><?php endforeach; ?>
  </tbody></table></div><?php endif; ?>
</section>
<?php require dirname(__DIR__) . '/includes/footer.php'; ?>
