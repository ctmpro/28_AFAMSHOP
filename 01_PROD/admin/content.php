<?php
/**
 * Contenus du site : édition de tous les paramètres de la table `settings`,
 * regroupés par onglet (sgroup) avec le champ adapté à chaque type.
 * Les groupes « shop » et « payment » sont réservés au super administrateur (module settings).
 */
require dirname(__DIR__) . '/includes/bootstrap.php';
require __DIR__ . '/_inc/helpers.php';
$admin = AdminAuth::require('content');

/** Libellés des onglets (ordre d'affichage). */
$groupLabels = [
    'general' => 'Général',
    'appearance' => 'Apparence',
    'header' => 'En-tête & menu',
    'home' => "Page d'accueil",
    'footer' => 'Pied de page',
    'shop' => 'Boutique',
    'payment' => 'Paiement',
    'seo' => 'Référencement',
    'emails' => 'Emails',
];
/** Groupes nécessitant le module « settings ». Le groupe « system » n'est jamais éditable. */
$restricted = ['shop', 'payment'];
$hidden = ['system'];

/** Listes de choix pour certaines clés stockées en texte. */
$choices = [
    'stock_decrement_on' => ['paid' => 'Au paiement confirmé', 'order' => 'Dès la commande'],
];

// Groupes accessibles par l'administrateur connecté
$all = DB::all('SELECT * FROM settings ORDER BY sort, skey');
$byGroup = [];
foreach ($all as $s) {
    if (in_array($s['sgroup'], $hidden, true)) continue;
    if (in_array($s['sgroup'], $restricted, true) && !AdminAuth::can('settings')) continue;
    $byGroup[$s['sgroup']][] = $s;
}
// Ordre : groupes connus puis groupes supplémentaires éventuels
$ordered = [];
foreach (array_keys($groupLabels) as $g) {
    if (isset($byGroup[$g])) $ordered[$g] = $byGroup[$g];
}
foreach ($byGroup as $g => $rows) {
    $ordered[$g] ??= $rows;
}
$byGroup = $ordered;

if (is_post()) {
    require_csrf();
    $posted = (array)($_POST['s'] ?? []);
    $saveGroup = (string)post('active_tab');
    $saveGroup = str_starts_with($saveGroup, 'tab-') ? substr($saveGroup, 4) : '';
    $changed = [];
    $errors = [];
    foreach ($byGroup as $group => $rows) {
        // Seul l'onglet soumis est enregistré (évite d'écraser les autres onglets)
        if ($group !== $saveGroup) continue;
        foreach ($rows as $s) {
            $key = $s['skey'];
            $old = (string)$s['svalue'];
            $value = $old;
            $isFile = in_array($s['type'], ['image', 'video'], true);
            // Clé absente du formulaire soumis : valeur conservée (les cases à cocher envoient toujours un champ caché « 0 »)
            if (!$isFile && !array_key_exists($key, $posted)) {
                continue;
            }
            try {
                switch ($s['type']) {
                    case 'bool':
                        $value = !empty($posted[$key]) ? '1' : '0';
                        break;
                    case 'image':
                        $value = (string)admin_process_file('file_' . $key, 'content', $old ?: null, UPLOAD_IMAGE_TYPES);
                        break;
                    case 'video':
                        $value = (string)admin_process_file('file_' . $key, 'content', $old ?: null, UPLOAD_VIDEO_TYPES, 64 * 1024 * 1024);
                        break;
                    case 'number':
                        $v = trim((string)($posted[$key] ?? ''));
                        if ($v !== '' && admin_dec($v) === null) throw new RuntimeException('nombre attendu');
                        $value = $v === '' ? '' : (string)(admin_dec($v) + 0);
                        break;
                    case 'email':
                        $v = trim((string)($posted[$key] ?? ''));
                        if ($v !== '' && !valid_email($v)) throw new RuntimeException('adresse email invalide');
                        $value = $v;
                        break;
                    case 'url':
                        $v = trim((string)($posted[$key] ?? ''));
                        if ($v !== '' && !preg_match('#^https?://[^\s<>"]+$#i', $v)) throw new RuntimeException('URL invalide (http:// ou https://)');
                        $value = $v;
                        break;
                    case 'color':
                        $v = trim((string)($posted[$key] ?? ''));
                        if ($v !== '' && !preg_match('/^#[0-9a-f]{6}$/i', $v)) throw new RuntimeException('couleur au format #RRGGBB attendue');
                        $value = strtolower($v);
                        break;
                    case 'html':
                        $value = clean_html((string)($posted[$key] ?? ''));
                        break;
                    default: // text, textarea
                        $value = (string)$posted[$key];
                        $value = $s['type'] === 'textarea' ? str_replace("\r\n", "\n", $value) : trim($value);
                        if (isset($choices[$key]) && !isset($choices[$key][$value])) throw new RuntimeException('valeur non autorisée');
                        if ($key === 'custom_css') $value = str_ireplace('</style', '', $value);
                }
            } catch (RuntimeException $e) {
                $errors[] = $s['label'] . ' : ' . $e->getMessage();
                continue;
            }
            if ($value !== $old) {
                DB::update('settings', ['svalue' => $value], 'skey = :k', ['k' => $key]);
                $changed[] = $key;
            }
        }
    }
    admin_files_commit();
    settings_all(true);
    if ($changed) {
        AdminAuth::log('settings_update', 'settings', null, ['group' => $saveGroup, 'keys' => $changed]);
        flash('success', count($changed) . ' paramètre(s) mis à jour.');
    } elseif (!$errors) {
        flash('info', 'Aucune modification.');
    }
    foreach ($errors as $er) flash('error', $er);
    redirect(admin_url('content.php', ['tab' => $saveGroup]));
}

/** Rendu d'un champ selon son type. */
function setting_field(array $s, array $choices): string
{
    $key = $s['skey'];
    $name = 's[' . $key . ']';
    $id = 'set_' . $key;
    $label = $s['label'] ?: $key;
    $val = (string)$s['svalue'];
    $help = 'Clé : ' . $key;
    if (isset($choices[$key])) {
        return field_select($name, $label, $choices[$key], $val, ['id' => $id, 'help' => $help]);
    }
    switch ($s['type']) {
        case 'bool':
            return '<input type="hidden" name="' . e($name) . '" value="0">' . field_checkbox($name, $label, $val === '1', ['id' => $id]);
        case 'textarea':
            $rows = max(3, min(14, substr_count($val, "\n") + 2));
            return field_textarea($name, $label, $val, ['id' => $id, 'rows' => $rows, 'help' => $help, 'class' => 'span-2']);
        case 'html':
            return field_textarea($name, $label, $val, ['id' => $id, 'rows' => 10, 'html' => true, 'help' => $help, 'class' => 'span-2']);
        case 'image':
            return field_file('file_' . $key, $label, $val ?: null, ['id' => $id, 'help' => $help]);
        case 'video':
            return field_file('file_' . $key, $label, $val ?: null, ['id' => $id, 'video' => true, 'help' => 'MP4 ou WebM. ' . $help]);
        case 'number':
            return field_input($name, $label, $val, ['id' => $id, 'type' => 'number', 'attrs' => ['step' => 'any'], 'help' => $help]);
        case 'email':
            return field_input($name, $label, $val, ['id' => $id, 'type' => 'email', 'help' => $help]);
        case 'url':
            return field_input($name, $label, $val, ['id' => $id, 'type' => 'url', 'attrs' => ['placeholder' => 'https://'], 'help' => $help]);
        case 'color':
            $safe = preg_match('/^#[0-9a-f]{6}$/i', $val) ? $val : '#000000';
            return '<div class="field"><label for="' . e($id) . '">' . e($label) . '</label><div class="color-field">'
                . '<input type="color" value="' . e($safe) . '" data-color-sync="' . e($id) . '" aria-label="Sélecteur de couleur">'
                . '<input type="text" name="' . e($name) . '" id="' . e($id) . '" value="' . e($val) . '" pattern="#[0-9a-fA-F]{6}" maxlength="7">'
                . '</div><small class="help">' . e($help) . '</small></div>';
        default:
            return field_input($name, $label, $val, ['id' => $id, 'help' => $help]);
    }
}

$activeTab = (string)query_param('tab');
if (!isset($byGroup[$activeTab])) $activeTab = (string)array_key_first($byGroup);

$pageTitle = 'Contenus du site';
$pageActions = '<a class="btn" href="' . e(admin_url('media.php')) . '">' . aicon('photo') . ' Médiathèque</a>';
require __DIR__ . '/_inc/layout_top.php';
?>
<p class="muted">Tous les textes, images et réglages du site public. Chaque onglet s'enregistre séparément.
  <?php if (!AdminAuth::can('settings')): ?>Les réglages Boutique et Paiement sont réservés au super administrateur.<?php endif; ?></p>

<div class="tabs" data-tabs="content" data-active="tab-<?= e($activeTab) ?>">
  <?php foreach ($byGroup as $g => $rows): ?>
    <button type="button" class="tab" data-tab="tab-<?= e($g) ?>"><?= e($groupLabels[$g] ?? ucfirst($g)) ?></button>
  <?php endforeach; ?>
</div>

<?php foreach ($byGroup as $g => $rows): ?>
  <form method="post" enctype="multipart/form-data" class="tab-panel" id="tab-<?= e($g) ?>" action="<?= e(admin_url('content.php')) ?>">
    <?= csrf_field() ?>
    <input type="hidden" name="active_tab" value="tab-<?= e($g) ?>">
    <div class="card">
      <div class="card-head"><h2><?= e($groupLabels[$g] ?? ucfirst($g)) ?></h2><span class="muted small"><?= count($rows) ?> paramètre(s)</span></div>
      <?php if ($g === 'emails'): ?>
        <p class="help mb">Variables des emails de commande : {first_name}, {last_name}, {order_number}, {total}, {payment_method}, {status}, {link}, {comment}. Variables générales : {site_name}, {company_name}, {phone}, {email}, {domain}, {year}.</p>
      <?php endif; ?>
      <div class="form-grid">
        <?php foreach ($rows as $s): ?>
          <?= setting_field($s, $choices) ?>
        <?php endforeach; ?>
      </div>
    </div>
    <div class="form-actions sticky">
      <button class="btn btn-primary" type="submit"><?= aicon('check') ?> Enregistrer l'onglet « <?= e($groupLabels[$g] ?? $g) ?> »</button>
    </div>
  </form>
<?php endforeach; ?>
<?php require __DIR__ . '/_inc/layout_bottom.php'; ?>
