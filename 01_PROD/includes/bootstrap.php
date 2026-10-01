<?php
/**
 * Initialisation commune à toutes les entrées (site, admin, api).
 */
require_once dirname(__DIR__) . '/config/config.php';

error_reporting(E_ALL);
ini_set('display_errors', APP_DEBUG ? '1' : '0');
ini_set('log_errors', '1');

spl_autoload_register(function (string $class) {
    $file = INCLUDES_PATH . '/classes/' . $class . '.php';
    if (is_file($file)) {
        require_once $file;
    }
});

require_once INCLUDES_PATH . '/DB.php';
require_once INCLUDES_PATH . '/functions.php';

if (PHP_SAPI !== 'cli') {
    // HTTPS obligatoire en production
    $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
    if (FORCE_HTTPS && !$isHttps) {
        header('Location: https://' . ($_SERVER['HTTP_HOST'] ?? '') . ($_SERVER['REQUEST_URI'] ?? '/'), true, 301);
        exit;
    }

    // En-têtes de sécurité
    header('X-Frame-Options: SAMEORIGIN');
    header('X-Content-Type-Options: nosniff');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header('Permissions-Policy: geolocation=(), camera=(), microphone=()');
    if ($isHttps) {
        header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
    }

    // Sessions sécurisées
    session_name('AFAMSESSID');
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => (parse_url(APP_URL, PHP_URL_PATH) ?: '') . '/',
        'secure'   => $isHttps,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');
    session_start();

    // Renouvellement périodique de l'identifiant de session
    if (empty($_SESSION['_created'])) {
        $_SESSION['_created'] = time();
    } elseif (time() - $_SESSION['_created'] > 1800) {
        session_regenerate_id(true);
        $_SESSION['_created'] = time();
    }

    // Changement de devise / langue
    if (isset($_GET['currency']) && isset(currencies()[$_GET['currency']])) {
        $_SESSION['currency'] = $_GET['currency'];
    }
    if (isset($_GET['lang']) && preg_match('/^[a-z]{2}$/', $_GET['lang']) && is_file(LANG_PATH . '/' . $_GET['lang'] . '.php')) {
        $_SESSION['lang'] = $_GET['lang'];
    }
}
