<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}
require_once __DIR__ . '/app/bootstrap.php';

if ($argc !== 3) {
    fwrite(STDERR, "Usage: php create-admin.php \"Full Name\" email@example.com\n");
    exit(1);
}
$name = trim($argv[1]);
$email = mb_strtolower(trim($argv[2]), 'UTF-8');
if ($name === '' || mb_strlen($name, 'UTF-8') > 120 || filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
    fwrite(STDERR, "Provide a valid name (up to 120 characters) and email address.\n");
    exit(1);
}
if ((int) db()->query('SELECT COUNT(*) FROM admins')->fetchColumn() > 0) {
    fwrite(STDERR, "An admin account already exists. This script only creates the first admin.\n");
    exit(1);
}

if (function_exists('readline')) {
    $password = readline('Password (minimum 12 characters): ');
    $confirmation = readline('Confirm password: ');
} else {
    fwrite(STDOUT, "Password (minimum 12 characters): ");
    $password = trim((string) fgets(STDIN));
    fwrite(STDOUT, "Confirm password: ");
    $confirmation = trim((string) fgets(STDIN));
}
if (strlen($password) < 12 || !hash_equals($password, $confirmation)) {
    fwrite(STDERR, "Passwords must match and be at least 12 characters long.\n");
    exit(1);
}

$statement = db()->prepare('INSERT INTO admins (name, email, password_hash) VALUES (:name, :email, :password_hash)');
$statement->execute(['name' => $name, 'email' => $email, 'password_hash' => password_hash($password, PASSWORD_DEFAULT)]);
fwrite(STDOUT, "Admin created for {$email}.\n");
