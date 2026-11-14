<?php
declare(strict_types=1);

define('APP_ROOT', dirname(__DIR__));
define('BASE_URL', '/swdmm/07-php-blog');
define('APP_ENV', getenv('APP_ENV') ?: 'development');

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/csrf.php';
require_once __DIR__ . '/auth.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    $isHttps = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
    if (APP_ENV === 'production' && !$isHttps) {
        http_response_code(400);
        exit('HTTPS is required in production. Configure Apache to serve this site over HTTPS.');
    }

    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');
    ini_set('session.use_trans_sid', '0');
    ini_set('session.cookie_httponly', '1');
    ini_set('session.cookie_samesite', 'Lax');
    session_name('swd_blog_session');
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => BASE_URL . '/',
        'secure' => $isHttps || APP_ENV === 'production',
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}
