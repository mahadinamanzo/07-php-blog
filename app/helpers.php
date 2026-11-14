<?php
declare(strict_types=1);

function e(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function url(string $path = ''): string
{
    return rtrim(BASE_URL, '/') . ($path === '' ? '/' : '/' . ltrim($path, '/'));
}

function redirect(string $path): void
{
    header('Location: ' . url($path), true, 303);
    exit;
}

function posted(string $key, string $default = ''): string
{
    return isset($_POST[$key]) && is_string($_POST[$key]) ? trim($_POST[$key]) : $default;
}

function slugify(string $title): string
{
    $text = mb_strtolower(trim($title), 'UTF-8');
    $text = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $text) ?: $text;
    $text = preg_replace('/[^a-z0-9]+/', '-', strtolower($text)) ?? '';
    return trim($text, '-') ?: 'post';
}

function unique_slug(string $title, string $table, ?int $excludeId = null): string
{
    if (!in_array($table, ['posts', 'categories'], true)) {
        throw new InvalidArgumentException('Unsupported slug table.');
    }
    $base = slugify($title);
    $candidate = $base;
    $suffix = 2;
    do {
        $sql = "SELECT id FROM {$table} WHERE slug = :slug";
        $params = ['slug' => $candidate];
        if ($excludeId !== null) {
            $sql .= ' AND id <> :id';
            $params['id'] = $excludeId;
        }
        $statement = db()->prepare($sql);
        $statement->execute($params);
        if (!$statement->fetch()) {
            return $candidate;
        }
        $candidate = $base . '-' . $suffix++;
    } while (true);
}

function flash(string $type, string $message): void
{
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function take_flash(): ?array
{
    $flash = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);
    return is_array($flash) ? $flash : null;
}

function excerpt(string $text, int $length = 180): string
{
    $text = trim(preg_replace('/\s+/', ' ', strip_tags($text)) ?? '');
    return mb_strlen($text, 'UTF-8') > $length
        ? mb_substr($text, 0, $length, 'UTF-8') . '…'
        : $text;
}

function upload_cover(array $file): ?string
{
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return null;
    }
    if (($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
        throw new RuntimeException('The image upload failed. Please try again.');
    }
    $temporaryPath = $file['tmp_name'] ?? '';
    if (!is_string($temporaryPath) || !is_uploaded_file($temporaryPath)) {
        throw new RuntimeException('Invalid image upload.');
    }
    $actualSize = filesize($temporaryPath);
    if (!is_int($actualSize) || $actualSize > 2 * 1024 * 1024) {
        throw new RuntimeException('Cover images must be 2 MB or smaller.');
    }

    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($temporaryPath);
    $extensions = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
    $imageInfo = @getimagesize($temporaryPath);
    if (
        !is_string($mime)
        || !isset($extensions[$mime])
        || $imageInfo === false
        || ($imageInfo['mime'] ?? null) !== $mime
    ) {
        throw new RuntimeException('Choose a valid JPG, PNG, or WebP image.');
    }

    $directory = APP_ROOT . DIRECTORY_SEPARATOR . 'uploads';
    if (is_link($directory)) {
        throw new RuntimeException('The uploads directory must not be a symbolic link.');
    }
    if (!is_dir($directory) && !mkdir($directory, 0755, true) && !is_dir($directory)) {
        throw new RuntimeException('The uploads directory could not be created.');
    }
    $filename = bin2hex(random_bytes(16)) . '.' . $extensions[$mime];
    if (!move_uploaded_file($file['tmp_name'], $directory . DIRECTORY_SEPARATOR . $filename)) {
        throw new RuntimeException('The uploaded image could not be saved.');
    }
    return $filename;
}

function delete_cover_image(?string $filename): void
{
    if ($filename === null || $filename === '') {
        return;
    }
    if (preg_match('/\A[a-f0-9]{32}\.(?:jpg|png|webp|gif)\z/i', $filename) !== 1) {
        throw new RuntimeException('The stored cover image filename is invalid; it was not deleted.');
    }

    $path = APP_ROOT . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . $filename;
    if (is_file($path) && !unlink($path)) {
        throw new RuntimeException('The cover image could not be deleted from uploads.');
    }
}
