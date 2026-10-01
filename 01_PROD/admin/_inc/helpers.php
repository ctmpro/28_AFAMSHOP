<?php
/**
 * Fonctions utilitaires propres au back-office :
 * menu, champs de formulaire, tri, graphiques SVG, conversions de saisie.
 */
// Inclusion uniquement (pas d'accès direct)
if (!defined('ROOT_PATH')) {
    http_response_code(404);
    exit;
}


// ---------------------------------------------------------------------
// Menu du back-office (module => fichier, libellé, icône)
// ---------------------------------------------------------------------
function admin_menu(): array
{
    return [
        'Pilotage' => [
            ['dashboard', 'index.php', 'Tableau de bord', 'dashboard'],
            ['stats', 'stats.php', 'Statistiques', 'chart'],
        ],
        'Catalogue' => [
            ['products', 'products.php', 'Produits', 'box'],
            ['categories', 'categories.php', 'Catégories', 'folder'],
            ['brands', 'brands.php', 'Marques', 'tag'],
            ['compatibility', 'compatibility.php', 'Compatibilités', 'printer'],
            ['stock', 'stock.php', 'Stock', 'layers'],
            ['import', 'import.php', 'Import / export', 'upload'],
            ['reviews', 'reviews.php', 'Avis clients', 'star'],
        ],
        'Ventes' => [
            ['orders', 'orders.php', 'Commandes', 'cart'],
            ['payments', 'payments.php', 'Paiements', 'card'],
            ['customers', 'customers.php', 'Clients', 'users'],
            ['requests', 'requests.php', 'Demandes', 'inbox'],
            ['promotions', 'promotions.php', 'Promotions', 'percent'],
            ['coupons', 'coupons.php', 'Codes promo', 'ticket'],
            ['delivery', 'delivery.php', 'Livraison & devises', 'truck'],
        ],
        'Contenu' => [
            ['content', 'content.php', 'Contenus du site', 'edit'],
            ['banners', 'banners.php', 'Bannières', 'image'],
            ['pages', 'pages.php', 'Pages', 'file'],
            ['services', 'services.php', 'Services', 'briefcase'],
            ['media', 'media.php', 'Médiathèque', 'photo'],
        ],
        'Administration' => [
            ['admins', 'admins.php', 'Administrateurs', 'shield'],
            ['logs', 'logs.php', 'Journal', 'list'],
            ['migrations', 'migrations.php', 'Migration', 'database'],
        ],
    ];
}

/** Fichier de menu actif (les pages de détail sont rattachées à leur liste). */
function admin_active_file(): string
{
    $f = basename($_SERVER['SCRIPT_NAME'] ?? 'index.php');
    return [
        'product_edit.php' => 'products.php',
        'order.php' => 'orders.php',
        'customer.php' => 'customers.php',
    ][$f] ?? $f;
}

// ---------------------------------------------------------------------
// Icônes du back-office (SVG inline, complète icon())
// ---------------------------------------------------------------------
function aicon(string $name, string $class = 'aicon'): string
{
    static $icons = [
        'dashboard' => '<rect x="3" y="3" width="7" height="9" rx="1"/><rect x="14" y="3" width="7" height="5" rx="1"/><rect x="14" y="12" width="7" height="9" rx="1"/><rect x="3" y="16" width="7" height="5" rx="1"/>',
        'chart' => '<path d="M3 3v18h18"/><path d="M7 15l4-4 3 3 5-6"/>',
        'box' => '<path d="M21 16V8l-9-5-9 5v8l9 5z"/><path d="m3.3 7 8.7 5 8.7-5M12 22V12"/>',
        'folder' => '<path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"/>',
        'tag' => '<path d="M20.6 13.4 13.4 20.6a2 2 0 0 1-2.8 0L2 12V2h10l8.6 8.6a2 2 0 0 1 0 2.8z"/><circle cx="7" cy="7" r="1.5"/>',
        'printer' => '<path d="M6 9V2h12v7"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><path d="M6 14h12v8H6z"/>',
        'layers' => '<path d="m12 2 10 5-10 5L2 7z"/><path d="m2 17 10 5 10-5M2 12l10 5 10-5"/>',
        'upload' => '<path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4M17 8l-5-5-5 5M12 3v12"/>',
        'download' => '<path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4M7 10l5 5 5-5M12 15V3"/>',
        'star' => '<path d="m12 2 3.1 6.3 6.9 1-5 4.9 1.2 6.8L12 17.8 5.8 21l1.2-6.8-5-4.9 6.9-1z"/>',
        'cart' => '<circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.7 13.4a2 2 0 0 0 2 1.6h9.7a2 2 0 0 0 2-1.6L23 6H6"/>',
        'card' => '<rect x="1" y="4" width="22" height="16" rx="2"/><path d="M1 10h22"/>',
        'users' => '<path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.9M16 3.1a4 4 0 0 1 0 7.8"/>',
        'user' => '<path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/>',
        'inbox' => '<path d="M22 12h-6l-2 3h-4l-2-3H2"/><path d="M5.5 5.1 2 12v6a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2v-6l-3.5-6.9A2 2 0 0 0 16.8 4H7.2a2 2 0 0 0-1.7 1.1z"/>',
        'percent' => '<path d="M19 5 5 19"/><circle cx="6.5" cy="6.5" r="2.5"/><circle cx="17.5" cy="17.5" r="2.5"/>',
        'ticket' => '<path d="M3 7a2 2 0 0 0 2-2h14a2 2 0 0 0 2 2v3a2 2 0 0 0 0 4v3a2 2 0 0 0-2 2H5a2 2 0 0 0-2-2v-3a2 2 0 0 0 0-4z"/><path d="M13 5v14" stroke-dasharray="2 2"/>',
        'truck' => '<path d="M1 3h15v13H1z"/><path d="M16 8h4l3 3v5h-7z"/><circle cx="5.5" cy="18.5" r="2.5"/><circle cx="18.5" cy="18.5" r="2.5"/>',
        'edit' => '<path d="M12 20h9"/><path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4z"/>',
        'image' => '<rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><path d="m21 15-5-5L5 21"/>',
        'photo' => '<path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"/><circle cx="12" cy="13" r="4"/>',
        'file' => '<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6M8 13h8M8 17h5"/>',
        'briefcase' => '<rect x="2" y="7" width="20" height="14" rx="2"/><path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"/>',
        'shield' => '<path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>',
        'list' => '<path d="M8 6h13M8 12h13M8 18h13M3 6h.01M3 12h.01M3 18h.01"/>',
        'database' => '<ellipse cx="12" cy="5" rx="9" ry="3"/><path d="M3 5v14c0 1.7 4 3 9 3s9-1.3 9-3V5"/><path d="M3 12c0 1.7 4 3 9 3s9-1.3 9-3"/>',
        'logout' => '<path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4M16 17l5-5-5-5M21 12H9"/>',
        'external' => '<path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6M15 3h6v6M10 14 21 3"/>',
        'menu' => '<path d="M3 6h18M3 12h18M3 18h18"/>',
        'search' => '<circle cx="11" cy="11" r="7"/><path d="m21 21-4.3-4.3"/>',
        'plus' => '<path d="M12 5v14M5 12h14"/>',
        'trash' => '<path d="M3 6h18M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/>',
        'eye' => '<path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8S1 12 1 12z"/><circle cx="12" cy="12" r="3"/>',
        'copy' => '<rect x="9" y="9" width="13" height="13" rx="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/>',
        'check' => '<path d="M20 6 9 17l-5-5"/>',
        'x' => '<path d="M18 6 6 18M6 6l12 12"/>',
        'alert' => '<path d="M10.3 3.9 1.8 18a2 2 0 0 0 1.7 3h17a2 2 0 0 0 1.7-3L13.7 3.9a2 2 0 0 0-3.4 0z"/><path d="M12 9v4M12 17h.01"/>',
        'money' => '<rect x="2" y="6" width="20" height="12" rx="2"/><circle cx="12" cy="12" r="2.5"/><path d="M6 12h.01M18 12h.01"/>',
        'settings' => '<circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.7 1.7 0 0 0 .3 1.8l.1.1a2 2 0 1 1-2.8 2.8l-.1-.1a1.7 1.7 0 0 0-1.8-.3 1.7 1.7 0 0 0-1 1.5V21a2 2 0 1 1-4 0v-.1a1.7 1.7 0 0 0-1.1-1.5 1.7 1.7 0 0 0-1.8.3l-.1.1a2 2 0 1 1-2.8-2.8l.1-.1a1.7 1.7 0 0 0 .3-1.8 1.7 1.7 0 0 0-1.5-1H3a2 2 0 1 1 0-4h.1a1.7 1.7 0 0 0 1.5-1.1 1.7 1.7 0 0 0-.3-1.8l-.1-.1a2 2 0 1 1 2.8-2.8l.1.1a1.7 1.7 0 0 0 1.8.3H9a1.7 1.7 0 0 0 1-1.5V3a2 2 0 1 1 4 0v.1a1.7 1.7 0 0 0 1 1.5 1.7 1.7 0 0 0 1.8-.3l.1-.1a2 2 0 1 1 2.8 2.8l-.1.1a1.7 1.7 0 0 0-.3 1.8V9a1.7 1.7 0 0 0 1.5 1H21a2 2 0 1 1 0 4h-.1a1.7 1.7 0 0 0-1.5 1z"/>',
        'refresh' => '<path d="M23 4v6h-6M1 20v-6h6"/><path d="M3.5 9a9 9 0 0 1 14.9-3.4L23 10M1 14l4.6 4.4A9 9 0 0 0 20.5 15"/>',
        'lock' => '<rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/>',
    ];
    $path = $icons[$name] ?? $icons['box'];
    return '<svg class="' . e($class) . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . $path . '</svg>';
}

// ---------------------------------------------------------------------
// Conversions de saisie
// ---------------------------------------------------------------------
/** "2026-10-01T14:30" (datetime-local) → "2026-10-01 14:30:00" ou null. */
function admin_dt_in($value): ?string
{
    $value = trim((string)$value);
    if ($value === '') {
        return null;
    }
    $ts = strtotime(str_replace('T', ' ', $value));
    return $ts ? date('Y-m-d H:i:s', $ts) : null;
}

/** Valeur DATETIME → format attendu par <input type="datetime-local">. */
function admin_dt_out(?string $value): string
{
    return $value ? date('Y-m-d\TH:i', strtotime($value)) : '';
}

/** Nombre décimal saisi (virgule ou point, espaces tolérés) ou null. */
function admin_dec($value): ?float
{
    $v = str_replace([' ', "\u{00A0}", "\u{202F}"], '', trim((string)$value));
    if ($v === '') {
        return null;
    }
    $v = str_replace(',', '.', $v);
    return is_numeric($v) ? (float)$v : null;
}

/** Entier saisi ou null si vide. */
function admin_int_or_null($value): ?int
{
    $v = trim((string)$value);
    return ($v === '' || !is_numeric($v)) ? null : (int)$v;
}

/** Valeur d'une case à cocher postée (0/1). */
function post_bool(string $key): int
{
    return !empty($_POST[$key]) ? 1 : 0;
}

/** Liste d'identifiants entiers postés (cases à cocher multiples). */
function post_ids(string $key = 'ids'): array
{
    $ids = $_POST[$key] ?? [];
    if (!is_array($ids)) {
        return [];
    }
    return array_values(array_unique(array_filter(array_map('intval', $ids), fn($i) => $i > 0)));
}

/** Date (Y-m-d) valide issue d'un filtre GET, sinon chaîne vide. */
function date_param(string $key): string
{
    $v = (string)query_param($key);
    return preg_match('/^\d{4}-\d{2}-\d{2}$/', $v) && strtotime($v) ? $v : '';
}

/** Règles de mot de passe administrateur. Retourne la liste des erreurs. */
function admin_password_errors(string $pwd, string $confirm): array
{
    $errors = [];
    if (mb_strlen($pwd) < 8) $errors[] = 'Le mot de passe doit contenir au moins 8 caractères.';
    if (!preg_match('/[A-Z]/', $pwd)) $errors[] = 'Le mot de passe doit contenir au moins une majuscule.';
    if (!preg_match('/[a-z]/', $pwd)) $errors[] = 'Le mot de passe doit contenir au moins une minuscule.';
    if (!preg_match('/\d/', $pwd)) $errors[] = 'Le mot de passe doit contenir au moins un chiffre.';
    if ($pwd !== $confirm) $errors[] = 'Les deux mots de passe ne correspondent pas.';
    return $errors;
}

/** URL de retour après connexion : uniquement un chemin local. */
function admin_safe_return(?string $to): string
{
    $to = (string)$to;
    if ($to !== '' && str_starts_with($to, APP_URL . '/')) {
        return $to;
    }
    if (preg_match('#^/(?![/\\\\])[^\s\x00-\x1f]*$#', $to)) {
        // Chemin relatif à l'hôte : on reconstruit avec l'origine de APP_URL
        $origin = preg_replace('#^(https?://[^/]+).*$#', '$1', APP_URL);
        return $origin . $to;
    }
    return admin_url('index.php');
}

// ---------------------------------------------------------------------
// Uploads : champ image/vidéo avec remplacement et suppression
// ---------------------------------------------------------------------
/**
 * Traite un champ fichier d'un formulaire d'édition.
 * - nouveau fichier envoyé : remplace l'ancien
 * - case "<champ>_remove" cochée : retire le fichier
 * Retourne le chemin à enregistrer. Lève RuntimeException en cas d'erreur.
 * Les suppressions physiques sont différées : appeler admin_files_commit() après
 * l'enregistrement en base, ou admin_files_rollback() si le formulaire est refusé.
 */
function admin_process_file(string $field, string $subdir, ?string $current, array $types = UPLOAD_IMAGE_TYPES, ?int $maxSize = null): ?string
{
    $new = handle_upload($_FILES[$field] ?? null, $subdir, $types, $maxSize);
    if ($new) {
        $GLOBALS['__admin_files']['new'][] = $new;
        if ($current) $GLOBALS['__admin_files']['old'][] = $current;
        return $new;
    }
    if (!empty($_POST[$field . '_remove'])) {
        if ($current) $GLOBALS['__admin_files']['old'][] = $current;
        return null;
    }
    return $current ?: null;
}

/** Supprime les anciens fichiers remplacés (après enregistrement réussi). */
function admin_files_commit(): void
{
    foreach ($GLOBALS['__admin_files']['old'] ?? [] as $p) delete_upload($p);
    $GLOBALS['__admin_files'] = [];
}

/** Supprime les fichiers fraîchement envoyés (formulaire refusé). */
function admin_files_rollback(): void
{
    foreach ($GLOBALS['__admin_files']['new'] ?? [] as $p) delete_upload($p);
    $GLOBALS['__admin_files'] = [];
}

// ---------------------------------------------------------------------
// Champs de formulaire
// ---------------------------------------------------------------------
function attrs(array $attrs): string
{
    $out = '';
    foreach ($attrs as $k => $v) {
        if ($v === false || $v === null) continue;
        $out .= $v === true ? ' ' . e($k) : ' ' . e($k) . '="' . e($v) . '"';
    }
    return $out;
}

/** Champ texte générique (type text, email, number, url, color, date, datetime-local, password). */
function field_input(string $name, string $label, $value = '', array $opt = []): string
{
    $id = $opt['id'] ?? 'f_' . preg_replace('/[^a-z0-9_]/i', '_', $name);
    $type = $opt['type'] ?? 'text';
    $a = array_merge(['type' => $type, 'name' => $name, 'id' => $id, 'value' => (string)($value ?? '')], $opt['attrs'] ?? []);
    if (!empty($opt['required'])) $a['required'] = true;
    $html = '<div class="field' . (!empty($opt['class']) ? ' ' . e($opt['class']) : '') . '">';
    $html .= '<label for="' . e($id) . '">' . e($label) . (!empty($opt['required']) ? ' <span class="req">*</span>' : '') . '</label>';
    $html .= '<input' . attrs($a) . '>';
    if (!empty($opt['help'])) $html .= '<small class="help">' . e($opt['help']) . '</small>';
    return $html . '</div>';
}

function field_textarea(string $name, string $label, $value = '', array $opt = []): string
{
    $id = $opt['id'] ?? 'f_' . preg_replace('/[^a-z0-9_]/i', '_', $name);
    $a = array_merge(['name' => $name, 'id' => $id, 'rows' => $opt['rows'] ?? 4], $opt['attrs'] ?? []);
    if (!empty($opt['required'])) $a['required'] = true;
    $html = '<div class="field' . (!empty($opt['class']) ? ' ' . e($opt['class']) : '') . '">';
    $html .= '<label for="' . e($id) . '">' . e($label) . (!empty($opt['required']) ? ' <span class="req">*</span>' : '') . '</label>';
    if (!empty($opt['html'])) {
        $html .= html_toolbar($id);
        $a['class'] = trim(($a['class'] ?? '') . ' code');
    }
    $html .= '<textarea' . attrs($a) . ">\n" . e($value) . '</textarea>'; // le saut de ligne initial est ignoré par le navigateur
    if (!empty($opt['help'])) $html .= '<small class="help">' . e($opt['help']) . '</small>';
    return $html . '</div>';
}

/** Barre d'outils HTML minimale pour un textarea (gérée par admin.js). */
function html_toolbar(string $targetId): string
{
    $btns = [
        ['b', '<b>G</b>', 'Gras'], ['i', '<i>I</i>', 'Italique'], ['h2', 'H2', 'Titre'], ['h3', 'H3', 'Sous-titre'],
        ['p', '¶', 'Paragraphe'], ['ul', '• Liste', 'Liste à puces'], ['a', 'Lien', 'Lien'], ['img', 'Image', 'Image (URL)'],
    ];
    $html = '<div class="html-toolbar" data-target="' . e($targetId) . '">';
    foreach ($btns as [$cmd, $label, $title]) {
        $html .= '<button type="button" class="tb-btn" data-cmd="' . $cmd . '" title="' . e($title) . '">' . $label . '</button>';
    }
    $html .= '<button type="button" class="tb-btn tb-preview" data-cmd="preview" title="Aperçu">Aperçu</button>';
    return $html . '</div><div class="html-preview" id="' . e($targetId) . '_preview" hidden></div>';
}

/** Liste déroulante. $options : valeur => libellé (ou groupes : libellé => [valeur => libellé]). */
function field_select(string $name, string $label, array $options, $selected = '', array $opt = []): string
{
    $id = $opt['id'] ?? 'f_' . preg_replace('/[^a-z0-9_]/i', '_', $name);
    $a = array_merge(['name' => $name, 'id' => $id], $opt['attrs'] ?? []);
    if (!empty($opt['required'])) $a['required'] = true;
    $html = '<div class="field' . (!empty($opt['class']) ? ' ' . e($opt['class']) : '') . '">';
    $html .= '<label for="' . e($id) . '">' . e($label) . (!empty($opt['required']) ? ' <span class="req">*</span>' : '') . '</label>';
    $html .= '<select' . attrs($a) . '>' . select_options($options, $selected, $opt['placeholder'] ?? null) . '</select>';
    if (!empty($opt['help'])) $html .= '<small class="help">' . e($opt['help']) . '</small>';
    return $html . '</div>';
}

function select_options(array $options, $selected = '', ?string $placeholder = null): string
{
    $html = $placeholder !== null ? '<option value="">' . e($placeholder) . '</option>' : '';
    $sel = (string)$selected;
    foreach ($options as $v => $l) {
        if (is_array($l)) {
            $html .= '<optgroup label="' . e($v) . '">' . select_options($l, $selected) . '</optgroup>';
            continue;
        }
        $html .= '<option value="' . e($v) . '"' . ((string)$v === $sel ? ' selected' : '') . '>' . e($l) . '</option>';
    }
    return $html;
}

function field_checkbox(string $name, string $label, $checked = false, array $opt = []): string
{
    $id = $opt['id'] ?? 'f_' . preg_replace('/[^a-z0-9_]/i', '_', $name);
    $html = '<div class="field field-check' . (!empty($opt['class']) ? ' ' . e($opt['class']) : '') . '">';
    $html .= '<label class="check"><input type="checkbox" name="' . e($name) . '" id="' . e($id) . '" value="1"' . ($checked ? ' checked' : '') . '> <span>' . e($label) . '</span></label>';
    if (!empty($opt['help'])) $html .= '<small class="help">' . e($opt['help']) . '</small>';
    return $html . '</div>';
}

/** Champ fichier image (ou vidéo) avec aperçu et case "supprimer". */
function field_file(string $name, string $label, ?string $current = null, array $opt = []): string
{
    $id = $opt['id'] ?? 'f_' . preg_replace('/[^a-z0-9_]/i', '_', $name);
    $video = !empty($opt['video']);
    $accept = $opt['accept'] ?? ($video ? 'video/mp4,video/webm' : 'image/jpeg,image/png,image/webp,image/gif');
    $html = '<div class="field field-file' . (!empty($opt['class']) ? ' ' . e($opt['class']) : '') . '">';
    $html .= '<label for="' . e($id) . '">' . e($label) . '</label>';
    $html .= '<div class="file-box">';
    if ($video) {
        $html .= '<div class="file-preview">' . ($current ? '<video src="' . e(media_url($current)) . '" controls muted preload="metadata"></video>' : '<span class="muted">Aucune vidéo</span>') . '</div>';
    } else {
        $html .= '<div class="file-preview"><img src="' . e($current ? media_url($current) : '') . '" alt="" data-preview-for="' . e($id) . '"' . ($current ? '' : ' hidden') . '>'
            . ($current ? '' : '<span class="muted" data-empty-for="' . e($id) . '">Aucune image</span>') . '</div>';
    }
    $html .= '<div class="file-actions"><input type="file" name="' . e($name) . '" id="' . e($id) . '" accept="' . e($accept) . '" data-preview="' . ($video ? '' : '1') . '">';
    if ($current) {
        $html .= '<label class="check small"><input type="checkbox" name="' . e($name) . '_remove" value="1"> Supprimer le fichier actuel</label>';
    }
    if (!empty($opt['help'])) $html .= '<small class="help">' . e($opt['help']) . '</small>';
    return $html . '</div></div></div>';
}

// ---------------------------------------------------------------------
// Tableaux : tri par colonne
// ---------------------------------------------------------------------
/**
 * Renvoie la clause ORDER BY à partir de ?sort=&dir= limitée aux colonnes autorisées.
 * $allowed : clé GET => expression SQL.
 */
function admin_sort_sql(array $allowed, string $defaultKey, string $defaultDir = 'desc'): string
{
    $key = (string)query_param('sort', $defaultKey);
    if (!isset($allowed[$key])) $key = $defaultKey;
    $dir = strtolower((string)query_param('dir', $defaultDir)) === 'asc' ? 'ASC' : 'DESC';
    return $allowed[$key] . ' ' . $dir;
}

/** En-tête de colonne cliquable pour le tri. */
function sort_link(string $key, string $label, string $defaultKey = '', string $defaultDir = 'desc'): string
{
    $current = (string)query_param('sort', $defaultKey);
    $dir = strtolower((string)query_param('dir', $defaultDir)) === 'asc' ? 'asc' : 'desc';
    $params = $_GET;
    unset($params['page']);
    $params['sort'] = $key;
    $params['dir'] = ($current === $key && $dir === 'asc') ? 'desc' : 'asc';
    $arrow = $current === $key ? ($dir === 'asc' ? ' ▲' : ' ▼') : '';
    $self = strtok($_SERVER['REQUEST_URI'] ?? '', '?');
    return '<a class="sort' . ($current === $key ? ' active' : '') . '" href="' . e($self . '?' . http_build_query($params)) . '">' . e($label) . $arrow . '</a>';
}

// ---------------------------------------------------------------------
// Badges
// ---------------------------------------------------------------------
function badge(string $text, string $type = 'muted'): string
{
    return '<span class="badge badge-' . e($type) . '">' . e($text) . '</span>';
}

function bool_badge($on, string $yes = 'Actif', string $no = 'Inactif'): string
{
    return $on ? badge($yes, 'success') : badge($no, 'muted');
}

function stock_badge(string $status): string
{
    $map = ['in_stock' => 'success', 'low_stock' => 'warning', 'out_of_stock' => 'danger', 'on_order' => 'info'];
    return badge(Catalog::stockLabel($status), $map[$status] ?? 'muted');
}

function request_status_badge(string $status): string
{
    $map = ['new' => 'info', 'in_progress' => 'warning', 'done' => 'success', 'closed' => 'muted'];
    return badge(request_statuses()[$status] ?? $status, $map[$status] ?? 'muted');
}

function payment_badge(string $status): string
{
    $map = ['pending' => 'warning', 'paid' => 'success', 'failed' => 'danger', 'cancelled' => 'muted', 'refunded' => 'danger'];
    return badge(payment_statuses()[$status] ?? $status, $map[$status] ?? 'muted');
}

/** Montant en devise de base, sans conversion d'affichage. */
function admin_money($amount): string
{
    return money($amount, base_currency(), false);
}

/** Condition SQL d'une commande comptée dans le chiffre d'affaires. */
const ADMIN_REVENUE_SQL = "((o.payment_status = 'paid' OR o.status = 'delivered') AND o.status NOT IN ('cancelled', 'refunded'))";

// ---------------------------------------------------------------------
// Export CSV générique
// ---------------------------------------------------------------------
function csv_download(string $filename, array $headers, iterable $rows): never
{
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . preg_replace('/[^A-Za-z0-9._-]/', '_', $filename) . '"');
    $out = fopen('php://output', 'w');
    fwrite($out, "\xEF\xBB\xBF");
    fputcsv($out, $headers, ';', '"', '\\');
    foreach ($rows as $r) {
        fputcsv($out, array_values($r), ';', '"', '\\');
    }
    fclose($out);
    exit;
}

// ---------------------------------------------------------------------
// Graphiques SVG (générés côté serveur, sans dépendance)
// ---------------------------------------------------------------------
/** Arrondi "joli" pour l'axe des ordonnées. */
function chart_nice_max(float $max): float
{
    if ($max <= 0) return 1;
    $exp = pow(10, floor(log10($max)));
    foreach ([1, 2, 2.5, 5, 10] as $m) {
        if ($max <= $m * $exp) return $m * $exp;
    }
    return 10 * $exp;
}

function chart_short_number(float $v): string
{
    if ($v >= 1000000) return rtrim(rtrim(number_format($v / 1000000, 1, ',', ''), '0'), ',') . ' M';
    if ($v >= 1000) return rtrim(rtrim(number_format($v / 1000, 1, ',', ''), '0'), ',') . ' k';
    return number_format($v, 0, ',', ' ');
}

/**
 * Graphique en barres ou en courbe.
 * $data : libellé => valeur. $opt : type (bar|line), height, format (callable pour l'infobulle).
 */
function svg_chart(array $data, array $opt = []): string
{
    $type = $opt['type'] ?? 'bar';
    $W = 760;
    $H = $opt['height'] ?? 240;
    $padL = 48; $padR = 12; $padT = 14; $padB = 30;
    $plotW = $W - $padL - $padR;
    $plotH = $H - $padT - $padB;
    $labels = array_keys($data);
    $values = array_map('floatval', array_values($data));
    $n = max(1, count($values));
    $max = chart_nice_max($values ? max($values) : 0);
    $fmt = $opt['format'] ?? fn($v) => number_format($v, 0, ',', ' ');

    $svg = '<svg class="chart" viewBox="0 0 ' . $W . ' ' . $H . '" preserveAspectRatio="xMidYMid meet" role="img" aria-label="' . e($opt['label'] ?? 'Graphique') . '">';
    // Grille et axe Y
    for ($i = 0; $i <= 4; $i++) {
        $y = $padT + $plotH - $plotH * $i / 4;
        $svg .= '<line class="gridline" x1="' . $padL . '" x2="' . ($W - $padR) . '" y1="' . round($y, 1) . '" y2="' . round($y, 1) . '"/>';
        $svg .= '<text class="axis" x="' . ($padL - 6) . '" y="' . round($y + 4, 1) . '" text-anchor="end">' . e(chart_short_number($max * $i / 4)) . '</text>';
    }
    // Libellés X (au plus ~10 visibles)
    $step = max(1, (int)ceil($n / 10));
    $slot = $plotW / $n;
    foreach ($labels as $i => $l) {
        if ($i % $step !== 0 && $i !== $n - 1) continue;
        $x = $padL + $slot * $i + $slot / 2;
        $svg .= '<text class="axis" x="' . round($x, 1) . '" y="' . ($H - 8) . '" text-anchor="middle">' . e($l) . '</text>';
    }
    if ($type === 'line') {
        $pts = [];
        foreach ($values as $i => $v) {
            $pts[] = [round($padL + $slot * $i + $slot / 2, 1), round($padT + $plotH - ($v / $max) * $plotH, 1)];
        }
        if ($pts) {
            $line = implode(' ', array_map(fn($p) => $p[0] . ',' . $p[1], $pts));
            $area = $pts[0][0] . ',' . ($padT + $plotH) . ' ' . $line . ' ' . end($pts)[0] . ',' . ($padT + $plotH);
            $svg .= '<polygon class="area" points="' . $area . '"/>';
            $svg .= '<polyline class="line" points="' . $line . '"/>';
            foreach ($pts as $i => $p) {
                $svg .= '<circle class="dot" cx="' . $p[0] . '" cy="' . $p[1] . '" r="3"><title>' . e($labels[$i] . ' : ' . $fmt($values[$i])) . '</title></circle>';
            }
        }
    } else {
        $bw = max(2, $slot * 0.68);
        foreach ($values as $i => $v) {
            $h = $max > 0 ? ($v / $max) * $plotH : 0;
            $x = $padL + $slot * $i + ($slot - $bw) / 2;
            $y = $padT + $plotH - $h;
            $svg .= '<rect class="bar" x="' . round($x, 1) . '" y="' . round($y, 1) . '" width="' . round($bw, 1) . '" height="' . round(max($h, 0), 1) . '" rx="2"><title>' . e($labels[$i] . ' : ' . $fmt($v)) . '</title></rect>';
        }
    }
    $svg .= '<line class="axis-line" x1="' . $padL . '" x2="' . ($W - $padR) . '" y1="' . ($padT + $plotH) . '" y2="' . ($padT + $plotH) . '"/>';
    return $svg . '</svg>';
}

/** Barres horizontales HTML (classements). $rows : [['label'=>..., 'value'=>..., 'display'=>...]] */
function hbar_list(array $rows, string $empty = 'Aucune donnée sur la période.'): string
{
    if (!$rows) {
        return '<p class="muted">' . e($empty) . '</p>';
    }
    $max = max(array_map(fn($r) => (float)$r['value'], $rows)) ?: 1;
    $html = '<ul class="hbars">';
    foreach ($rows as $r) {
        $pct = round((float)$r['value'] / $max * 100, 1);
        $html .= '<li><div class="hbar-head"><span class="hbar-label">' . e($r['label']) . '</span><span class="hbar-value">' . e($r['display'] ?? $r['value']) . '</span></div>'
            . '<div class="hbar-track"><div class="hbar-fill" style="width:' . $pct . '%"></div></div></li>';
    }
    return $html . '</ul>';
}

/** Série journalière (Y-m-d => 0) pour les N derniers jours. */
function day_series(int $days): array
{
    $out = [];
    for ($i = $days - 1; $i >= 0; $i--) {
        $out[date('Y-m-d', strtotime("-$i days"))] = 0;
    }
    return $out;
}

/** Bloc d'aide pour afficher une ligne "libellé : valeur" dans une fiche. */
function info_row(string $label, ?string $valueHtml): string
{
    return '<div class="info-row"><span class="info-label">' . e($label) . '</span><span class="info-value">' . ($valueHtml !== null && $valueHtml !== '' ? $valueHtml : '<span class="muted">—</span>') . '</span></div>';
}

// ---------------------------------------------------------------------
// Catalogue
// ---------------------------------------------------------------------
/** Supprime le fichier d'une image produit s'il n'est plus utilisé par aucun produit. */
function product_image_cleanup(array $paths): void
{
    foreach (array_unique($paths) as $path) {
        if (!(int)DB::val('SELECT COUNT(*) FROM product_images WHERE path = :p', ['p' => $path])) {
            delete_upload($path);
        }
    }
}

/** Options de catégories indentées selon l'arbre (id => "— Nom"). $excludeId exclut une branche. */
function category_options(?int $excludeId = null): array
{
    $out = [];
    $walk = function (array $nodes, int $depth) use (&$walk, &$out, $excludeId) {
        foreach ($nodes as $n) {
            if ($excludeId && (int)$n['id'] === $excludeId) continue;
            $out[$n['id']] = str_repeat('— ', $depth) . $n['name'];
            $walk($n['children'], $depth + 1);
        }
    };
    $walk(Catalog::categoryTree(false), 0);
    return $out;
}

/** Options de marques (id => nom). */
function brand_options(): array
{
    return array_column(DB::all('SELECT id, name FROM brands ORDER BY name'), 'name', 'id');
}

// ---------------------------------------------------------------------
// Pièces jointes privées des demandes (uploads/private/requests)
// ---------------------------------------------------------------------
/**
 * Chemin absolu sûr d'une pièce jointe de demande, ou null.
 * Accepte un chemin relatif à uploads/ (« private/requests/2026/10/x.pdf »)
 * ou relatif au dossier des demandes (« 2026/10/x.pdf »).
 */
function request_attachment_path(?string $attachment): ?string
{
    if (!$attachment || str_contains($attachment, "\0")) {
        return null;
    }
    $base = realpath(UPLOADS_PATH . '/private/requests');
    if (!$base) {
        return null;
    }
    $rel = ltrim($attachment, '/');
    foreach ([UPLOADS_PATH . '/' . $rel, $base . '/' . $rel, $base . '/' . basename($rel)] as $candidate) {
        $real = realpath($candidate);
        if ($real && is_file($real) && str_starts_with($real, $base . DIRECTORY_SEPARATOR)) {
            return $real;
        }
    }
    return null;
}

// En-têtes communs du back-office : pas de cache, pas d'indexation
if (PHP_SAPI !== 'cli' && !headers_sent()) {
    header('Cache-Control: no-store, no-cache, must-revalidate');
    header('X-Robots-Tag: noindex, nofollow');
}
