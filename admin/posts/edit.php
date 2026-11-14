<?php
declare(strict_types=1);
require_once dirname(__DIR__, 2) . '/app/bootstrap.php';
require_admin();

$id = isset($_GET['id']) ? filter_var($_GET['id'], FILTER_VALIDATE_INT) : null;
$post = null;
if ($id !== null && $id !== false) {
    $statement = db()->prepare('SELECT * FROM posts WHERE id = :id');
    $statement->execute(['id' => $id]);
    $post = $statement->fetch();
    if (!$post) {
        http_response_code(404);
        exit('Post not found.');
    }
}
$categories = db()->query('SELECT id, name FROM categories ORDER BY name')->fetchAll();
$error = '';
$form = [
    'title' => $post['title'] ?? '',
    'category_id' => (string) ($post['category_id'] ?? ''),
    'excerpt' => $post['excerpt'] ?? ($post ? excerpt($post['body'], 300) : ''),
    'body' => $post['body'] ?? '',
    'status' => $post['status'] ?? 'draft',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $form['title'] = posted('title');
    $form['category_id'] = posted('category_id');
    $form['excerpt'] = posted('excerpt');
    $form['body'] = posted('body');
    $form['status'] = posted('status', 'draft');
    $categoryId = filter_var($form['category_id'], FILTER_VALIDATE_INT);
    if ($form['title'] === '' || mb_strlen($form['title'], 'UTF-8') > 240) {
        $error = 'Enter a title of 1–240 characters.';
    } elseif (!$categoryId || !in_array((int) $categoryId, array_map(static fn(array $category): int => (int) $category['id'], $categories), true)) {
        $error = 'Choose a valid category.';
    } elseif ($form['excerpt'] === '' || mb_strlen($form['excerpt'], 'UTF-8') > 300) {
        $error = 'Enter an excerpt of 1–300 characters.';
    } elseif ($form['body'] === '') {
        $error = 'Post content cannot be empty.';
    } elseif (!in_array($form['status'], ['draft', 'published'], true)) {
        $error = 'Choose a valid post status.';
    } else {
        $slug = unique_slug($form['title'], 'posts', $post ? (int) $post['id'] : null);
        $uploadedImage = null;
        try {
            $uploadedImage = isset($_FILES['cover_image']) ? upload_cover($_FILES['cover_image']) : null;
        } catch (RuntimeException $exception) {
            $error = $exception->getMessage();
        }
        if ($error === '') {
            $coverImage = $uploadedImage ?? ($post['cover_image'] ?? null);
            $pdo = db();
            try {
                if ($post) {
                    $query = $pdo->prepare(
                        'UPDATE posts SET title = :title, slug = :slug, category_id = :category_id, excerpt = :excerpt, body = :body,
                         status = :status, cover_image = :cover_image,
                         published_at = CASE WHEN :published = 1 THEN COALESCE(published_at, NOW()) ELSE NULL END
                         WHERE id = :id'
                    );
                    $query->execute([
                        'title' => $form['title'], 'slug' => $slug, 'category_id' => $categoryId,
                        'excerpt' => $form['excerpt'],
                        'body' => $form['body'], 'status' => $form['status'], 'cover_image' => $coverImage,
                        'published' => $form['status'] === 'published' ? 1 : 0, 'id' => $post['id'],
                    ]);
                } else {
                    $query = $pdo->prepare(
                        'INSERT INTO posts (title, slug, category_id, author_id, excerpt, body, status, cover_image, published_at)
                         VALUES (:title, :slug, :category_id, :author_id, :excerpt, :body, :status, :cover_image,
                           CASE WHEN :published = 1 THEN NOW() ELSE NULL END)'
                    );
                    $query->execute([
                        'title' => $form['title'], 'slug' => $slug, 'category_id' => $categoryId,
                        'author_id' => current_admin()['id'], 'body' => $form['body'],
                        'excerpt' => $form['excerpt'],
                        'status' => $form['status'], 'cover_image' => $coverImage,
                        'published' => $form['status'] === 'published' ? 1 : 0,
                    ]);
                }
            } catch (PDOException $exception) {
                if ($uploadedImage !== null) {
                    delete_cover_image($uploadedImage);
                }
                throw $exception;
            }

            if ($post && $uploadedImage !== null && $post['cover_image'] !== $uploadedImage) {
                try {
                    delete_cover_image($post['cover_image']);
                } catch (RuntimeException $exception) {
                    flash('error', 'Post saved, but the previous cover image could not be removed: ' . $exception->getMessage());
                    redirect('admin/posts/');
                }
            }
            flash('success', $post ? 'Post updated.' : 'Post created.');
            redirect('admin/posts/');
        }
    }
}

$adminTitle = $post ? 'Edit post' : 'New post';
require dirname(__DIR__) . '/includes/header.php';
?>
<section class="admin-panel form-panel">
  <?php if (!$categories): ?><div class="alert error">Create a category before writing a post. <a href="<?= e(url('admin/categories/edit.php')) ?>">Add a category</a></div><?php endif; ?>
  <?php if ($error !== ''): ?><div class="alert error"><?= e($error) ?></div><?php endif; ?>
  <form class="stack-form" method="post" enctype="multipart/form-data">
    <?= csrf_field() ?>
    <label>Post title<input type="text" name="title" maxlength="240" required value="<?= e($form['title']) ?>"></label>
    <div class="form-row">
      <label>Category<select name="category_id" required><option value="">Select a category</option><?php foreach ($categories as $category): ?><option value="<?= (int) $category['id'] ?>" <?= (int) $form['category_id'] === (int) $category['id'] ? 'selected' : '' ?>><?= e($category['name']) ?></option><?php endforeach; ?></select></label>
      <label>Status<select name="status"><option value="draft" <?= $form['status'] === 'draft' ? 'selected' : '' ?>>Draft</option><option value="published" <?= $form['status'] === 'published' ? 'selected' : '' ?>>Published</option></select></label>
    </div>
    <label>Excerpt<input type="text" name="excerpt" maxlength="300" required value="<?= e($form['excerpt']) ?>"></label>
    <label>Cover image <span class="field-hint">(JPG, PNG or WebP; max 2 MB)</span><input type="file" name="cover_image" accept="image/jpeg,image/png,image/webp"></label>
    <?php if (!empty($post['cover_image'])): ?><p class="current-image">Current image: <?= e($post['cover_image']) ?></p><?php endif; ?>
    <label>Content<textarea name="body" rows="16" required><?= e($form['body']) ?></textarea></label>
    <div class="form-actions"><a class="button button-light" href="<?= e(url('admin/posts/')) ?>">Cancel</a><button class="button" type="submit"><?= $post ? 'Save changes' : 'Create post' ?></button></div>
  </form>
</section>
<?php require dirname(__DIR__) . '/includes/footer.php'; ?>
