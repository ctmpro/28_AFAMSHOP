<?php
/**
 * Fonctions utilitaires communes (présentation, sécurité, formatage).
 */

// ---------------------------------------------------------------------
// Échappement / URLs
// ---------------------------------------------------------------------
function e($value): string
{
    return htmlspecialchars((string)($value ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function url(string $path = '', array $params = []): string
{
    $u = APP_URL . '/' . ltrim($path, '/');
    if ($params) {
        $params = array_filter($params, fn($v) => $v !== null && $v !== '' && $v !== []);
        if ($params) {
            $u .= (str_contains($u, '?') ? '&' : '?') . http_build_query($params);
        }
    }
    return $u;
}

function admin_url(string $path = '', array $params = []): string
{
    return url('admin/' . ltrim($path, '/'), $params);
}

function asset(string $path): string
{
    $file = ROOT_PATH . '/assets/' . ltrim($path, '/');
    $v = is_file($file) ? filemtime($file) : '1';
    return APP_URL . '/assets/' . ltrim($path, '/') . '?v=' . $v;
}

/** URL publique d'un fichier uploadé (ou d'une URL absolue / chemin assets). */
function media_url(?string $path, string $fallback = 'img/placeholder.svg'): string
{
    if (!$path) {
        return asset($fallback);
    }
    if (preg_match('#^https?://#i', $path)) {
        return $path;
    }
    if (str_starts_with($path, 'assets/')) {
        return APP_URL . '/' . $path;
    }
    return APP_URL . '/uploads/' . ltrim($path, '/');
}

function redirect(string $to, int $code = 302): never
{
    if (!preg_match('#^https?://#', $to)) {
        $to = url($to);
    }
    header('Location: ' . $to, true, $code);
    exit;
}

function back(string $fallback = ''): never
{
    $ref = $_SERVER['HTTP_REFERER'] ?? '';
    if ($ref && str_starts_with($ref, APP_URL)) {
        redirect($ref);
    }
    redirect($fallback);
}

function current_url(): string
{
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    return $scheme . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . ($_SERVER['REQUEST_URI'] ?? '/');
}

/** Ne garde qu'une URL de retour locale (évite les redirections ouvertes). */
function safe_return(?string $to, string $fallback = ''): string
{
    if ($to && str_starts_with($to, APP_URL)) {
        return $to;
    }
    if ($to && str_starts_with($to, '/') && !str_starts_with($to, '//')) {
        return $to;
    }
    return url($fallback);
}

// ---------------------------------------------------------------------
// Requête
// ---------------------------------------------------------------------
function is_post(): bool
{
    return ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST';
}

function input(string $key, $default = '')
{
    $v = $_POST[$key] ?? $_GET[$key] ?? $default;
    return is_string($v) ? trim($v) : $v;
}

function post(string $key, $default = '')
{
    $v = $_POST[$key] ?? $default;
    return is_string($v) ? trim($v) : $v;
}

function query_param(string $key, $default = '')
{
    $v = $_GET[$key] ?? $default;
    return is_string($v) ? trim($v) : $v;
}

function int_param(string $key, int $default = 0): int
{
    $v = $_POST[$key] ?? $_GET[$key] ?? null;
    return is_numeric($v) ? (int)$v : $default;
}

function client_ip(): string
{
    return substr($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0', 0, 45);
}

function is_ajax(): bool
{
    return strtolower($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'xmlhttprequest'
        || str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json');
}

function json_response($data, int $code = 200): never
{
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    header('X-Content-Type-Options: nosniff');
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

// ---------------------------------------------------------------------
// CSRF
// ---------------------------------------------------------------------
function csrf_token(): string
{
    if (empty($_SESSION['_csrf'])) {
        $_SESSION['_csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['_csrf'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="_csrf" value="' . e(csrf_token()) . '">';
}

function csrf_valid(): bool
{
    $sent = $_POST['_csrf'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    return is_string($sent) && $sent !== '' && hash_equals(csrf_token(), $sent);
}

function require_csrf(): void
{
    if (!csrf_valid()) {
        if (is_ajax()) {
            json_response(['ok' => false, 'error' => __('csrf_error')], 419);
        }
        http_response_code(419);
        flash('error', __('csrf_error'));
        back();
    }
}

// ---------------------------------------------------------------------
// Messages flash et anciennes valeurs de formulaire
// ---------------------------------------------------------------------
function flash(string $type, string $message): void
{
    $_SESSION['_flash'][] = ['type' => $type, 'message' => $message];
}

function get_flashes(): array
{
    $f = $_SESSION['_flash'] ?? [];
    unset($_SESSION['_flash']);
    return $f;
}

function keep_old(array $data): void
{
    unset($data['_csrf'], $data['password'], $data['password_confirm'], $data['current_password']);
    $_SESSION['_old'] = $data;
}

function old(string $key, $default = '')
{
    return $_SESSION['_old'][$key] ?? $default;
}

function clear_old(): void
{
    unset($_SESSION['_old']);
}

// ---------------------------------------------------------------------
// Paramètres du site (table settings)
// ---------------------------------------------------------------------
function settings_all(bool $refresh = false): array
{
    static $cache = null;
    if ($cache === null || $refresh) {
        $cache = [];
        try {
            foreach (DB::all('SELECT skey, svalue FROM settings') as $r) {
                $cache[$r['skey']] = $r['svalue'];
            }
        } catch (Throwable $e) {
            error_log('AFAMSHOP settings: ' . $e->getMessage());
        }
    }
    return $cache;
}

function setting(string $key, $default = '')
{
    $all = settings_all();
    return (isset($all[$key]) && $all[$key] !== '') ? $all[$key] : $default;
}

function setting_bool(string $key, bool $default = false): bool
{
    $v = setting($key, $default ? '1' : '0');
    return in_array(strtolower((string)$v), ['1', 'true', 'on', 'yes', 'oui'], true);
}

function set_setting(string $key, $value): void
{
    DB::exec(
        'INSERT INTO settings (skey, svalue) VALUES (:k, :v) ON DUPLICATE KEY UPDATE svalue = VALUES(svalue)',
        ['k' => $key, 'v' => $value]
    );
    settings_all(true);
}

// ---------------------------------------------------------------------
// Traductions (français au lancement, structure prête pour l'anglais)
// ---------------------------------------------------------------------
function current_lang(): string
{
    $lang = $_SESSION['lang'] ?? APP_LANG;
    return is_file(LANG_PATH . '/' . $lang . '.php') ? $lang : 'fr';
}

function __(string $key, array $replace = []): string
{
    static $strings = [];
    $lang = current_lang();
    if (!isset($strings[$lang])) {
        $strings[$lang] = require LANG_PATH . '/' . $lang . '.php';
    }
    $s = $strings[$lang][$key] ?? $key;
    foreach ($replace as $k => $v) {
        $s = str_replace(':' . $k, (string)$v, $s);
    }
    return $s;
}

// ---------------------------------------------------------------------
// Monnaie
// ---------------------------------------------------------------------
function currencies(): array
{
    static $list = null;
    if ($list === null) {
        $list = [];
        try {
            foreach (DB::all('SELECT * FROM currencies WHERE active = 1 ORDER BY is_default DESC, code') as $c) {
                $list[$c['code']] = $c;
            }
        } catch (Throwable $e) {
        }
        if (!$list) {
            $list['XOF'] = ['code' => 'XOF', 'symbol' => 'FCFA', 'rate' => 1, 'decimals' => 0, 'symbol_after' => 1, 'is_default' => 1];
        }
    }
    return $list;
}

function base_currency(): string
{
    foreach (currencies() as $c) {
        if ($c['is_default']) {
            return $c['code'];
        }
    }
    return 'XOF';
}

function display_currency(): string
{
    $c = $_SESSION['currency'] ?? base_currency();
    return isset(currencies()[$c]) ? $c : base_currency();
}

/** Formate un montant exprimé en devise de base, converti dans la devise d'affichage. */
function money($amount, ?string $currency = null, bool $convert = true): string
{
    $code = $currency ?? display_currency();
    $c = currencies()[$code] ?? currencies()[base_currency()];
    $value = (float)$amount;
    if ($convert && $code !== base_currency()) {
        $value *= (float)$c['rate'];
    }
    $formatted = number_format($value, (int)$c['decimals'], ',', ' ');
    return $c['symbol_after'] ? $formatted . ' ' . $c['symbol'] : $c['symbol'] . ' ' . $formatted;
}

/** Arrondi selon la précision de la devise de base (0 décimale pour le FCFA). */
function round_money($amount): float
{
    $c = currencies()[base_currency()] ?? ['decimals' => 0];
    return round((float)$amount, (int)$c['decimals']);
}

/** Montant pour PDF / emails (sans espace insécable). */
function money_plain($amount, ?string $currency = null): string
{
    return str_replace("\u{202F}", ' ', money($amount, $currency ?? base_currency(), false));
}

// ---------------------------------------------------------------------
// Divers
// ---------------------------------------------------------------------
function slugify(string $text): string
{
    $text = trim($text);
    if (function_exists('transliterator_transliterate')) {
        $text = transliterator_transliterate('Any-Latin; Latin-ASCII; Lower()', $text);
    } else {
        $text = strtolower(iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $text));
    }
    $text = preg_replace('/[^a-z0-9]+/', '-', $text);
    return trim($text, '-') ?: 'item';
}

function unique_slug(string $table, string $base, ?int $exceptId = null): string
{
    $slug = slugify($base);
    $candidate = $slug;
    $i = 2;
    while (true) {
        $params = ['s' => $candidate];
        $sql = "SELECT COUNT(*) FROM `$table` WHERE slug = :s";
        if ($exceptId) {
            $sql .= ' AND id <> :id';
            $params['id'] = $exceptId;
        }
        if ((int)DB::val($sql, $params) === 0) {
            return $candidate;
        }
        $candidate = $slug . '-' . $i++;
    }
}

function format_date(?string $date, bool $withTime = false): string
{
    if (!$date) {
        return '';
    }
    $ts = strtotime($date);
    return $withTime ? date('d/m/Y H:i', $ts) : date('d/m/Y', $ts);
}

function truncate(?string $text, int $len = 120): string
{
    $text = trim(strip_tags((string)$text));
    return mb_strlen($text) > $len ? rtrim(mb_substr($text, 0, $len - 1)) . '…' : $text;
}

function valid_email(string $email): bool
{
    return (bool)filter_var($email, FILTER_VALIDATE_EMAIL);
}

function random_token(int $bytes = 32): string
{
    return bin2hex(random_bytes($bytes));
}

/** Pagination : renvoie offset, pages, etc. */
function paginate(int $total, int $perPage, int $page): array
{
    $pages = max(1, (int)ceil($total / max(1, $perPage)));
    $page = max(1, min($page, $pages));
    return [
        'total'    => $total,
        'per_page' => $perPage,
        'page'     => $page,
        'pages'    => $pages,
        'offset'   => ($page - 1) * $perPage,
    ];
}

/** Liens de pagination conservant les paramètres GET courants. */
function pagination_links(array $p, string $baseUrl = ''): string
{
    if ($p['pages'] <= 1) {
        return '';
    }
    $params = $_GET;
    unset($params['_route']);
    $base = $baseUrl ?: strtok(current_url(), '?');
    $html = '<nav class="pagination" aria-label="Pagination">';
    $mk = function (int $n, string $label, bool $active = false, bool $disabled = false) use ($params, $base) {
        if ($disabled) {
            return '<span class="page disabled">' . $label . '</span>';
        }
        $params['page'] = $n;
        $cls = $active ? 'page active' : 'page';
        return '<a class="' . $cls . '" href="' . e($base . '?' . http_build_query($params)) . '">' . $label . '</a>';
    };
    $html .= $mk($p['page'] - 1, '‹', false, $p['page'] <= 1);
    $start = max(1, $p['page'] - 2);
    $end = min($p['pages'], $p['page'] + 2);
    if ($start > 1) {
        $html .= $mk(1, '1');
        if ($start > 2) $html .= '<span class="page dots">…</span>';
    }
    for ($i = $start; $i <= $end; $i++) {
        $html .= $mk($i, (string)$i, $i === $p['page']);
    }
    if ($end < $p['pages']) {
        if ($end < $p['pages'] - 1) $html .= '<span class="page dots">…</span>';
        $html .= $mk($p['pages'], (string)$p['pages']);
    }
    $html .= $mk($p['page'] + 1, '›', false, $p['page'] >= $p['pages']);
    return $html . '</nav>';
}

/**
 * Nettoyage HTML minimal pour les contenus saisis dans l'admin
 * (supprime scripts, iframes non autorisées, attributs on*, javascript:).
 */
function clean_html(?string $html): string
{
    $html = (string)$html;
    $html = preg_replace('#<(script|style|object|embed|form)[^>]*>.*?</\1>#is', '', $html);
    $html = preg_replace('#<(script|object|embed|form|meta|link)[^>]*/?>#i', '', $html);
    // iframes : uniquement YouTube / Vimeo / Google Maps
    $html = preg_replace_callback('#<iframe[^>]*src=["\']([^"\']+)["\'][^>]*>.*?</iframe>#is', function ($m) {
        return preg_match('#^https://(www\.)?(youtube\.com|youtube-nocookie\.com|player\.vimeo\.com|google\.com/maps)#i', $m[1]) ? $m[0] : '';
    }, $html);
    $html = preg_replace('#\son\w+\s*=\s*("[^"]*"|\'[^\']*\'|[^\s>]+)#i', '', $html);
    $html = preg_replace('#(href|src)\s*=\s*(["\'])\s*javascript:[^"\']*\2#i', '$1="#"', $html);
    return $html;
}

/** Remplace les variables {site_name}, {phone}... dans les contenus administrables. */
function render_vars(?string $text): string
{
    $vars = [
        '{site_name}' => setting('site_name', 'AFAMSHOP'),
        '{company_name}' => setting('company_name', 'AFAM'),
        '{phone}' => setting('contact_phone'),
        '{email}' => setting('contact_email'),
        '{address}' => setting('contact_address'),
        '{domain}' => setting('site_domain'),
        '{year}' => date('Y'),
    ];
    return strtr((string)$text, $vars);
}

function whatsapp_link(string $text = ''): string
{
    $num = preg_replace('/\D+/', '', setting('whatsapp_number'));
    return 'https://wa.me/' . $num . ($text ? '?text=' . rawurlencode($text) : '');
}

/** Icônes SVG inline (jeu restreint, sans dépendance externe). */
function icon(string $name, string $class = 'icon'): string
{
    static $icons = [
        'search' => '<circle cx="11" cy="11" r="7"/><path d="m21 21-4.3-4.3"/>',
        'cart' => '<circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.7 13.4a2 2 0 0 0 2 1.6h9.7a2 2 0 0 0 2-1.6L23 6H6"/>',
        'user' => '<path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/>',
        'heart' => '<path d="M20.8 4.6a5.5 5.5 0 0 0-7.8 0L12 5.7l-1-1.1a5.5 5.5 0 0 0-7.8 7.8l1 1.1L12 21l7.8-7.5 1-1.1a5.5 5.5 0 0 0 0-7.8z"/>',
        'menu' => '<path d="M3 6h18M3 12h18M3 18h18"/>',
        'close' => '<path d="M18 6 6 18M6 6l12 12"/>',
        'printer' => '<path d="M6 9V2h12v7"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><path d="M6 14h12v8H6z"/>',
        'drop' => '<path d="M12 2.7s-6 6.6-6 11.3a6 6 0 0 0 12 0c0-4.7-6-11.3-6-11.3z"/>',
        'laptop' => '<rect x="3" y="4" width="18" height="12" rx="2"/><path d="M2 20h20"/>',
        'pen' => '<path d="M12 20h9"/><path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4z"/>',
        'tag' => '<path d="M20.6 13.4 13.4 20.6a2 2 0 0 1-2.8 0L2 12V2h10l8.6 8.6a2 2 0 0 1 0 2.8z"/><circle cx="7" cy="7" r="1.5"/>',
        'wrench' => '<path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.8-3.8a6 6 0 0 1-7.9 7.9l-6.9 6.9a2.1 2.1 0 0 1-3-3l6.9-6.9a6 6 0 0 1 7.9-7.9z"/>',
        'key' => '<circle cx="7.5" cy="15.5" r="5.5"/><path d="m21 2-9.6 9.6M15.5 7.5l3 3L22 7l-3-3"/>',
        'briefcase' => '<rect x="2" y="7" width="20" height="14" rx="2"/><path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"/>',
        'truck' => '<path d="M1 3h15v13H1z"/><path d="M16 8h4l3 3v5h-7z"/><circle cx="5.5" cy="18.5" r="2.5"/><circle cx="18.5" cy="18.5" r="2.5"/>',
        'shield' => '<path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>',
        'phone' => '<path d="M22 16.9v3a2 2 0 0 1-2.2 2 19.8 19.8 0 0 1-8.6-3.1 19.5 19.5 0 0 1-6-6A19.8 19.8 0 0 1 2.1 4.2 2 2 0 0 1 4.1 2h3a2 2 0 0 1 2 1.7c.1.9.4 1.8.7 2.7a2 2 0 0 1-.5 2.1L8 9.8a16 16 0 0 0 6 6l1.3-1.3a2 2 0 0 1 2.1-.4c.9.3 1.8.6 2.7.7a2 2 0 0 1 1.7 2z"/>',
        'mail' => '<rect x="2" y="4" width="20" height="16" rx="2"/><path d="m22 6-10 7L2 6"/>',
        'pin' => '<path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/>',
        'whatsapp' => '<path d="M3 21l1.7-5A9 9 0 1 1 8 19.3z"/><path d="M9 10c0 3 2 5 5 5l1.5-1.5-2-1-1 1c-1 0-2.5-1.5-2.5-2.5l1-1-1-2z"/>',
        'compare' => '<path d="M16 3h5v5M4 20 21 3M21 16v5h-5M15 15l6 6M4 4l5 5"/>',
        'check' => '<path d="M20 6 9 17l-5-5"/>',
        'star' => '<path d="m12 2 3.1 6.3 6.9 1-5 4.9 1.2 6.8L12 17.8 5.8 21l1.2-6.8-5-4.9 6.9-1z"/>',
        'filter' => '<path d="M22 3H2l8 9.5V19l4 2v-8.5z"/>',
        'chevron' => '<path d="m9 18 6-6-6-6"/>',
        'leaf' => '<path d="M11 20A7 7 0 0 1 9.8 6.1C15.5 5 17 4.5 19 2c1 2 2 4.2 2 8 0 5.5-4.8 10-10 10z"/><path d="M2 21c0-3 1.9-5.4 5.1-6"/>',
        'clock' => '<circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/>',
        'box' => '<path d="M21 16V8l-9-5-9 5v8l9 5z"/><path d="m3.3 7 8.7 5 8.7-5M12 22V12"/>',
        'logout' => '<path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4M16 17l5-5-5-5M21 12H9"/>',
        'upload' => '<path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4M17 8l-5-5-5 5M12 3v12"/>',
        'file' => '<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6"/>',
    ];
    $path = $icons[$name] ?? $icons['box'];
    return '<svg class="' . e($class) . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . $path . '</svg>';
}

// ---------------------------------------------------------------------
// Uploads sécurisés
// ---------------------------------------------------------------------
const UPLOAD_IMAGE_TYPES = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp', 'image/gif' => 'gif'];
const UPLOAD_VIDEO_TYPES = ['video/mp4' => 'mp4', 'video/webm' => 'webm'];
const UPLOAD_DOC_TYPES = [
    'application/pdf' => 'pdf',
    'image/jpeg' => 'jpg',
    'image/png' => 'png',
    'image/webp' => 'webp',
    'application/msword' => 'doc',
    'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'docx',
    'application/vnd.ms-excel' => 'xls',
    'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => 'xlsx',
];

/**
 * Enregistre un fichier uploadé de manière sécurisée.
 * Retourne le chemin relatif à uploads/ ou null. Lève une exception en cas d'erreur.
 */
function handle_upload(?array $file, string $subdir, array $allowed = UPLOAD_IMAGE_TYPES, ?int $maxSize = null): ?string
{
    if (!$file || ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return null;
    }
    if ($file['error'] !== UPLOAD_ERR_OK || !is_uploaded_file($file['tmp_name'])) {
        throw new RuntimeException(__('upload_error'));
    }
    $maxSize = $maxSize ?? UPLOAD_MAX_SIZE;
    if ($file['size'] > $maxSize) {
        throw new RuntimeException(__('upload_too_big', ['size' => round($maxSize / 1048576) . ' Mo']));
    }
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
    if (!isset($allowed[$mime])) {
        throw new RuntimeException(__('upload_bad_type'));
    }
    if (str_starts_with($mime, 'image/') && @getimagesize($file['tmp_name']) === false) {
        throw new RuntimeException(__('upload_bad_type'));
    }
    $subdir = trim(preg_replace('#[^a-z0-9_/-]#i', '', $subdir), '/');
    $dir = UPLOADS_PATH . '/' . $subdir . '/' . date('Y/m');
    if (!is_dir($dir) && !mkdir($dir, 0755, true)) {
        throw new RuntimeException(__('upload_error'));
    }
    $name = bin2hex(random_bytes(12)) . '.' . $allowed[$mime];
    if (!move_uploaded_file($file['tmp_name'], $dir . '/' . $name)) {
        throw new RuntimeException(__('upload_error'));
    }
    @chmod($dir . '/' . $name, 0644);
    return $subdir . '/' . date('Y/m') . '/' . $name;
}

/** Normalise $_FILES['x'] multiple en liste de fichiers. */
function files_list(?array $files): array
{
    if (!$files || !is_array($files['name'] ?? null)) {
        return $files ? [$files] : [];
    }
    $out = [];
    foreach ($files['name'] as $i => $n) {
        $out[] = [
            'name' => $n,
            'type' => $files['type'][$i],
            'tmp_name' => $files['tmp_name'][$i],
            'error' => $files['error'][$i],
            'size' => $files['size'][$i],
        ];
    }
    return $out;
}

function delete_upload(?string $path): void
{
    if (!$path || preg_match('#^(https?://|assets/)#', $path) || str_contains($path, '..')) {
        return;
    }
    $full = UPLOADS_PATH . '/' . $path;
    if (is_file($full)) {
        @unlink($full);
    }
}

// ---------------------------------------------------------------------
// Limitation des tentatives de connexion
// ---------------------------------------------------------------------
function too_many_attempts(string $scope, string $identifier, int $max = 5, int $minutes = 15): bool
{
    $since = date('Y-m-d H:i:s', time() - $minutes * 60);
    $byIdent = (int)DB::val(
        'SELECT COUNT(*) FROM login_attempts WHERE scope = :s AND identifier = :i AND attempted_at > :t',
        ['s' => $scope, 'i' => strtolower($identifier), 't' => $since]
    );
    $byIp = (int)DB::val(
        'SELECT COUNT(*) FROM login_attempts WHERE scope = :s AND ip = :ip AND attempted_at > :t',
        ['s' => $scope, 'ip' => client_ip(), 't' => $since]
    );
    return $byIdent >= $max || $byIp >= $max * 4;
}

function record_attempt(string $scope, string $identifier): void
{
    DB::insert('login_attempts', ['scope' => $scope, 'identifier' => strtolower($identifier), 'ip' => client_ip()]);
}

function clear_attempts(string $scope, string $identifier): void
{
    DB::exec('DELETE FROM login_attempts WHERE scope = :s AND identifier = :i', ['s' => $scope, 'i' => strtolower($identifier)]);
}

// ---------------------------------------------------------------------
// Libellés de statuts
// ---------------------------------------------------------------------
function order_statuses(): array
{
    return [
        'received'  => __('status_received'),
        'paid'      => __('status_paid'),
        'preparing' => __('status_preparing'),
        'shipped'   => __('status_shipped'),
        'delivered' => __('status_delivered'),
        'cancelled' => __('status_cancelled'),
        'refunded'  => __('status_refunded'),
    ];
}

function payment_statuses(): array
{
    return [
        'pending'   => __('pay_pending'),
        'paid'      => __('pay_paid'),
        'failed'    => __('pay_failed'),
        'cancelled' => __('pay_cancelled'),
        'refunded'  => __('pay_refunded'),
    ];
}

function payment_method_label(string $m): string
{
    return [
        'stripe' => __('pm_stripe'),
        'paydunya' => __('pm_paydunya'),
        'cod' => __('pm_cod'),
        'transfer' => __('pm_transfer'),
    ][$m] ?? $m;
}

function request_types(): array
{
    return [
        'quote' => __('req_quote'),
        'rental' => __('req_rental'),
        'maintenance' => __('req_maintenance'),
        'contact' => __('req_contact'),
    ];
}

function request_statuses(): array
{
    return [
        'new' => __('rs_new'),
        'in_progress' => __('rs_in_progress'),
        'done' => __('rs_done'),
        'closed' => __('rs_closed'),
    ];
}
