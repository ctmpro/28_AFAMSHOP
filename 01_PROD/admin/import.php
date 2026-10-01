<?php
/**
 * Import / export du catalogue (CSV ou XLSX) :
 * 1. envoi du fichier (conservé dans uploads/private/import sous un nom aléatoire),
 * 2. validation à blanc avec rapport ligne par ligne,
 * 3. confirmation de l'import. Export CSV et modèle téléchargeable.
 */
require dirname(__DIR__) . '/includes/bootstrap.php';
require __DIR__ . '/_inc/helpers.php';
$admin = AdminAuth::require('import');

$importDir = UPLOADS_PATH . '/private/import';
if (!is_dir($importDir)) {
    @mkdir($importDir, 0750, true);
}
// Nettoyage des fichiers abandonnés (plus de 24 h)
foreach (glob($importDir . '/*.{csv,txt,xlsx}', GLOB_BRACE) ?: [] as $old) {
    if (filemtime($old) < time() - 86400) @unlink($old);
}

/** Fichier d'import en attente pour cette session (chemin absolu sûr) ou null. */
function pending_import(string $dir): ?array
{
    $p = $_SESSION['import_pending'] ?? null;
    if (!$p || !preg_match('/^[a-f0-9]{32}\.(csv|txt|xlsx)$/', $p['file'] ?? '')) {
        return null;
    }
    $full = $dir . '/' . $p['file'];
    if (!is_file($full)) {
        unset($_SESSION['import_pending']);
        return null;
    }
    $p['path'] = $full;
    return $p;
}

// --- Téléchargements (lecture seule)
$action = (string)query_param('action');
if ($action === 'export') {
    AdminAuth::log('catalog_export', 'product');
    Importer::exportCsv();
}
if ($action === 'template') {
    $example = [
        'sku' => 'AF-TON-MX23', 'reference' => 'MX-23GTBA', 'nom' => 'Toner Sharp MX-23GTBA noir', 'marque' => 'Sharp',
        'categorie' => 'Toners', 'prix' => '45000', 'prix_promo' => '', 'stock' => '12', 'type' => 'Toner', 'couleur' => 'Noir',
        'description_courte' => 'Toner d\'origine Sharp, 18 000 pages.', 'description' => '<p>Toner d\'origine pour copieurs Sharp MX.</p>',
        'compatibilites' => 'Sharp MX-2310U|Sharp MX-3111U', 'poids' => '0,5 kg', 'dimensions' => '', 'garantie' => '6 mois',
        'delai_livraison' => '24 à 48 h', 'publie' => 'oui',
    ];
    $row = [];
    foreach (Importer::EXPORT_HEADERS as $h) {
        $row[] = $example[$h] ?? '';
    }
    csv_download('modele-import-catalogue.csv', Importer::EXPORT_HEADERS, [$row]);
}

// --- Actions
if (is_post()) {
    require_csrf();
    $do = (string)post('do');

    if ($do === 'upload') {
        $file = $_FILES['file'] ?? null;
        $ext = strtolower(pathinfo((string)($file['name'] ?? ''), PATHINFO_EXTENSION));
        if (!$file || ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK || !is_uploaded_file($file['tmp_name'])) {
            flash('error', 'Veuillez choisir un fichier à importer.');
        } elseif (!in_array($ext, ['csv', 'txt', 'xlsx'], true)) {
            flash('error', 'Format non supporté : utilisez un fichier .csv ou .xlsx.');
        } elseif ($file['size'] > 20 * 1024 * 1024) {
            flash('error', 'Fichier trop volumineux (20 Mo maximum).');
        } else {
            // Ancien fichier en attente : supprimé
            if ($old = pending_import($importDir)) @unlink($old['path']);
            $name = bin2hex(random_bytes(16)) . '.' . $ext;
            if (move_uploaded_file($file['tmp_name'], $importDir . '/' . $name)) {
                @chmod($importDir . '/' . $name, 0640);
                $_SESSION['import_pending'] = ['file' => $name, 'original' => mb_substr(basename((string)$file['name']), 0, 190), 'at' => time()];
                unset($_SESSION['import_result']);
                AdminAuth::log('import_upload', 'product', null, $file['name']);
            } else {
                flash('error', 'Impossible d\'enregistrer le fichier.');
            }
        }
        redirect(admin_url('import.php'));
    }

    if ($do === 'cancel') {
        if ($p = pending_import($importDir)) @unlink($p['path']);
        unset($_SESSION['import_pending']);
        flash('info', 'Import annulé.');
        redirect(admin_url('import.php'));
    }

    if ($do === 'confirm') {
        $p = pending_import($importDir);
        if (!$p) {
            flash('error', 'Aucun fichier en attente : recommencez l\'envoi.');
            redirect(admin_url('import.php'));
        }
        @set_time_limit(300);
        try {
            $rows = Importer::readFile($p['path'], $p['original']);
            $report = Importer::import($rows, false, post_bool('create_refs') === 1);
        } catch (Throwable $e) {
            $report = ['created' => 0, 'updated' => 0, 'skipped' => 0, 'total' => 0, 'errors' => [[0, $e->getMessage()]]];
        }
        @unlink($p['path']);
        unset($_SESSION['import_pending']);
        $_SESSION['import_result'] = ['report' => $report, 'original' => $p['original']];
        AdminAuth::log('import_run', 'product', null, ['file' => $p['original'], 'created' => $report['created'], 'updated' => $report['updated'], 'errors' => count($report['errors'])]);
        flash($report['errors'] ? 'warning' : 'success', 'Import terminé : ' . $report['created'] . ' produit(s) créé(s), ' . $report['updated'] . ' mis à jour, ' . count($report['errors']) . ' erreur(s).');
        redirect(admin_url('import.php'));
    }
    redirect(admin_url('import.php'));
}

// --- Validation à blanc du fichier en attente
$pending = pending_import($importDir);
$preview = null;
$sample = [];
if ($pending) {
    try {
        $rows = Importer::readFile($pending['path'], $pending['original']);
        $preview = Importer::import($rows, true, true);
        $sample = array_slice($rows, 0, 6);
    } catch (Throwable $e) {
        $preview = ['created' => 0, 'updated' => 0, 'skipped' => 0, 'total' => 0, 'errors' => [[0, $e->getMessage()]]];
    }
}
$result = $_SESSION['import_result'] ?? null;
unset($_SESSION['import_result']);

$pageTitle = 'Import / export du catalogue';
$pageActions = '<a class="btn" href="' . e(admin_url('import.php', ['action' => 'template'])) . '">' . aicon('file') . ' Modèle CSV</a>'
    . '<a class="btn" href="' . e(admin_url('import.php', ['action' => 'export'])) . '">' . aicon('download') . ' Exporter le catalogue</a>';
require __DIR__ . '/_inc/layout_top.php';
?>
<?php if ($result): $r = $result['report']; ?>
  <div class="card">
    <h2>Résultat de l'import — <?= e($result['original']) ?></h2>
    <div class="kpis">
      <div class="kpi"><div class="kpi-label">Lignes traitées</div><div class="kpi-value"><?= (int)$r['total'] ?></div></div>
      <div class="kpi"><div class="kpi-label">Produits créés</div><div class="kpi-value"><?= (int)$r['created'] ?></div></div>
      <div class="kpi"><div class="kpi-label">Produits mis à jour</div><div class="kpi-value"><?= (int)$r['updated'] ?></div></div>
      <div class="kpi<?= $r['errors'] ? ' danger' : '' ?>"><div class="kpi-label">Erreurs</div><div class="kpi-value"><?= count($r['errors']) ?></div></div>
    </div>
    <?php if ($r['errors']): ?>
      <div class="table-wrap"><table class="table"><thead><tr><th>Ligne</th><th>Erreur</th></tr></thead><tbody>
        <?php foreach ($r['errors'] as [$line, $msg]): ?><tr><td><?= (int)$line ?></td><td><?= e($msg) ?></td></tr><?php endforeach; ?>
      </tbody></table></div>
    <?php endif; ?>
  </div>
<?php endif; ?>

<?php if ($pending && $preview !== null): ?>
  <div class="card">
    <div class="card-head"><h2>Vérification du fichier « <?= e($pending['original']) ?> »</h2><span class="muted small">Envoyé le <?= e(date('d/m/Y H:i', $pending['at'])) ?> — aucune donnée n'a encore été modifiée.</span></div>
    <div class="kpis">
      <div class="kpi"><div class="kpi-label">Lignes de données</div><div class="kpi-value"><?= (int)$preview['total'] ?></div></div>
      <div class="kpi"><div class="kpi-label">À créer</div><div class="kpi-value"><?= (int)$preview['created'] ?></div></div>
      <div class="kpi"><div class="kpi-label">À mettre à jour</div><div class="kpi-value"><?= (int)$preview['updated'] ?></div></div>
      <div class="kpi<?= $preview['errors'] ? ' danger' : '' ?>"><div class="kpi-label">Lignes en erreur</div><div class="kpi-value"><?= count($preview['errors']) ?></div></div>
    </div>

    <?php if ($sample): ?>
      <h3>Aperçu des premières lignes</h3>
      <div class="table-wrap mb"><table class="table">
        <?php foreach ($sample as $i => $line): ?>
          <tr><?php foreach (array_slice($line, 0, 10) as $cell): ?><<?= $i === 0 ? 'th' : 'td' ?> class="small"><?= e(truncate((string)$cell, 40)) ?></<?= $i === 0 ? 'th' : 'td' ?>><?php endforeach; ?></tr>
        <?php endforeach; ?>
      </table></div>
    <?php endif; ?>

    <?php if ($preview['errors']): ?>
      <h3>Erreurs détectées (ces lignes seront ignorées)</h3>
      <div class="table-wrap mb"><table class="table"><thead><tr><th>Ligne</th><th>Erreur</th></tr></thead><tbody>
        <?php foreach (array_slice($preview['errors'], 0, 200) as [$line, $msg]): ?><tr><td><?= (int)$line ?></td><td><?= e($msg) ?></td></tr><?php endforeach; ?>
        <?php if (count($preview['errors']) > 200): ?><tr><td colspan="2" class="muted">… et <?= count($preview['errors']) - 200 ?> autre(s).</td></tr><?php endif; ?>
      </tbody></table></div>
    <?php else: ?>
      <div class="alert alert-success"><span>Aucune erreur détectée.</span></div>
    <?php endif; ?>

    <div class="form-actions">
      <?php if ($preview['created'] + $preview['updated'] > 0): ?>
        <form method="post" class="btn-group">
          <?= csrf_field() ?>
          <input type="hidden" name="do" value="confirm">
          <label class="check small"><input type="checkbox" name="create_refs" value="1" checked> Créer automatiquement les marques et catégories inconnues</label>
          <button class="btn btn-primary" type="submit" data-confirm="Lancer l'import de <?= (int)($preview['created'] + $preview['updated']) ?> produit(s) ?"><?= aicon('check') ?> Confirmer l'import</button>
        </form>
      <?php endif; ?>
      <form method="post" class="inline"><?= csrf_field() ?><input type="hidden" name="do" value="cancel"><button class="btn" type="submit">Annuler</button></form>
    </div>
  </div>
<?php endif; ?>

<div class="grid grid-2">
  <form method="post" enctype="multipart/form-data" class="card">
    <?= csrf_field() ?>
    <input type="hidden" name="do" value="upload">
    <h2><?= $pending ? 'Remplacer le fichier' : 'Importer un fichier' ?></h2>
    <div class="field">
      <label for="file">Fichier CSV ou Excel (.xlsx)</label>
      <input type="file" id="file" name="file" accept=".csv,.txt,.xlsx,text/csv,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet" required>
      <small class="help">Le fichier est d'abord vérifié : rien n'est modifié avant votre confirmation.</small>
    </div>
    <button class="btn btn-primary" type="submit"><?= aicon('upload') ?> Envoyer et vérifier</button>
  </form>
  <div class="card">
    <h2>Format attendu</h2>
    <p class="small">Première ligne = en-têtes. Colonnes obligatoires : <code>sku</code> et <code>nom</code>. Séparateur « ; » ou « , » (détecté automatiquement), encodage UTF-8 ou Windows-1252.</p>
    <p class="small">Colonnes reconnues : <code><?= e(implode(', ', Importer::EXPORT_HEADERS)) ?></code>, ainsi que <code>image</code> (URL).</p>
    <ul class="small">
      <li>Un SKU existant met à jour le produit ; un nouveau SKU crée le produit.</li>
      <li><code>compatibilites</code> : modèles séparés par « | » (ex. <code>Sharp MX-2310U|Sharp MX-3111U</code>).</li>
      <li><code>publie</code> : oui / non. Le stock importé est historisé dans les mouvements.</li>
    </ul>
    <a class="btn btn-sm" href="<?= e(admin_url('import.php', ['action' => 'template'])) ?>"><?= aicon('download') ?> Télécharger le modèle</a>
  </div>
</div>
<?php require __DIR__ . '/_inc/layout_bottom.php'; ?>
