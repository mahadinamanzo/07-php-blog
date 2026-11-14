<?php
declare(strict_types=1);
require_once dirname(__DIR__, 2) . '/app/bootstrap.php';
require_admin();

$id = isset($_GET['id']) ? filter_var($_GET['id'], FILTER_VALIDATE_INT) : null;
$category = null;
if ($id !== null && $id !== false) {
    $query = db()->prepare('SELECT * FROM categories WHERE id = :id');
    $query->execute(['id' => $id]);
    $category = $query->fetch();
    if (!$category) {
        http_response_code(404);
        exit('Category not found.');
    }
}
$form = ['name' => $category['name'] ?? '', 'description' => $category['description'] ?? ''];
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $form = ['name' => posted('name'), 'description' => posted('description')];
    if ($form['name'] === '' || mb_strlen($form['name'], 'UTF-8') > 120) {
        $error = 'Enter a category name of 1–120 characters.';
    } else {
        $slug = unique_slug($form['name'], 'categories', $category ? (int) $category['id'] : null);
        try {
            if ($category) {
                $query = db()->prepare('UPDATE categories SET name = :name, slug = :slug, description = :description WHERE id = :id');
                $query->execute(['name' => $form['name'], 'slug' => $slug, 'description' => $form['description'] ?: null, 'id' => $category['id']]);
                flash('success', 'Category updated.');
            } else {
                $query = db()->prepare('INSERT INTO categories (name, slug, description) VALUES (:name, :slug, :description)');
                $query->execute(['name' => $form['name'], 'slug' => $slug, 'description' => $form['description'] ?: null]);
                flash('success', 'Category created.');
            }
            redirect('admin/categories/');
        } catch (PDOException $exception) {
            if ((string) $exception->getCode() === '23000') {
                $error = 'A category with that name already exists.';
            } else {
                throw $exception;
            }
        }
    }
}
$adminTitle = $category ? 'Edit category' : 'New category';
require dirname(__DIR__) . '/includes/header.php';
?>
<section class="admin-panel form-panel">
  <?php if ($error !== ''): ?><div class="alert error"><?= e($error) ?></div><?php endif; ?>
  <form class="stack-form" method="post">
    <?= csrf_field() ?>
    <label>Category name<input type="text" name="name" maxlength="120" required value="<?= e($form['name']) ?>"></label>
    <label>Description <span class="field-hint">(optional)</span><textarea name="description" rows="5"><?= e($form['description']) ?></textarea></label>
    <div class="form-actions"><a class="button button-light" href="<?= e(url('admin/categories/')) ?>">Cancel</a><button class="button" type="submit"><?= $category ? 'Save changes' : 'Create category' ?></button></div>
  </form>
</section>
<?php require dirname(__DIR__) . '/includes/footer.php'; ?>
