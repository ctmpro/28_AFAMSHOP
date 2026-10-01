<?php
/**
 * Configuration technique de l'application.
 * Les valeurs sensibles proviennent exclusivement du fichier .env.
 * Les contenus (nom de la plateforme, contacts, textes...) sont gérés
 * dans la table `settings` depuis le back-office.
 */
require_once __DIR__ . '/env.php';
loadEnvFile();

define('ROOT_PATH', dirname(__DIR__));
define('INCLUDES_PATH', ROOT_PATH . '/includes');
define('PAGES_PATH', ROOT_PATH . '/pages');
define('UPLOADS_PATH', ROOT_PATH . '/uploads');
define('UTILS_PATH', ROOT_PATH . '/utilitaires');
define('LANG_PATH', ROOT_PATH . '/lang');

define('APP_ENV', env('APP_ENV', 'production'));
define('APP_DEBUG', (bool)env('APP_DEBUG', false));
define('APP_KEY', (string)env('APP_KEY', ''));
define('APP_LANG', (string)env('APP_LANG', 'fr'));
define('APP_TIMEZONE', (string)env('APP_TIMEZONE', 'Africa/Dakar'));
define('FORCE_HTTPS', (bool)env('FORCE_HTTPS', APP_ENV === 'production'));

// URL publique : APP_URL dans .env, sinon détection automatique.
$__appUrl = (string)env('APP_URL', '');
if ($__appUrl === '' && PHP_SAPI !== 'cli') {
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $base = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/')), '/');
    // Les scripts admin/ et api/ remontent d'un niveau
    $base = preg_replace('#/(admin|api)(/.*)?$#', '', $base);
    $__appUrl = $scheme . '://' . $host . $base;
}
define('APP_URL', rtrim($__appUrl, '/'));

define('DB_HOST', (string)env('DB_HOST', 'localhost'));
define('DB_PORT', (string)env('DB_PORT', '3306'));
define('DB_NAME', (string)env('DB_NAME', 'afamshop'));
define('DB_USER', (string)env('DB_USER', 'root'));
define('DB_PASS', (string)env('DB_PASS', ''));

define('UPLOAD_MAX_SIZE', (int)env('UPLOAD_MAX_SIZE', 8 * 1024 * 1024));

date_default_timezone_set(APP_TIMEZONE);
