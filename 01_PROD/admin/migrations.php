<?php
/**
 * Migration de la base de données (super administrateur uniquement).
 * Exécute les fichiers de sql/migrations/ ou du SQL saisi / envoyé, avec sauvegarde préalable.
 */
require dirname(__DIR__) . '/includes/bootstrap.php';
require __DIR__ . '/_inc/helpers.php';
$admin = AdminAuth::require('migrations');

if (!env('MIGRATIONS_ENABLED', true)) {
    flash('error', 'La page de migration est désactivée (MIGRATIONS_ENABLED=false dans le fichier .env).');
    redirect(admin_url('index.php'));
}
Migrator::ensureTable();

// Téléchargement d'une sauvegarde
if (query_param('download') !== '') {
    $path = Migrator::backupPath((string)query_param('download'));
    if (!$path) {
        http_response_code(404);
        exit('Sauvegarde introuvable.');
    }
    AdminAuth::log('backup_download', 'migration', null, basename($path));
    header('Content-Type: application/gzip');
    header('Content-Disposition: attachment; filename="' . basename($path) . '"');
    header('Content-Length: ' . filesize($path));
    readfile($path);
    exit;
}

/** Sauvegarde préalable si demandée ; retourne le nom du fichier ou null. */
$doBackup = function (): ?string {
    if (!post_bool('backup')) {
        return null;
    }
    $name = Migrator::backup();
    AdminAuth::log('backup_create', 'migration', null, $name);
    return $name;
};

if (is_post()) {
    require_csrf();
    $action = (string)post('action');
    $report = null;
    try {
        switch ($action) {
            // Toutes les migrations en attente, dans l'ordre
            case 'run_pending':
                $pending = Migrator::pending();
                if (!$pending) {
                    flash('info', 'Aucune migration en attente.');
                    break;
                }
                $backup = $doBackup();
                $report = ['title' => 'Migrations en attente', 'backup' => $backup, 'runs' => []];
                foreach ($pending as $name => $file) {
                    $res = Migrator::execute((string)file_get_contents($file), $name, 'file', $backup);
                    $report['runs'][] = ['name' => $name] + $res;
                    AdminAuth::log($res['ok'] ? 'migration_run' : 'migration_error', 'migration', null, $name . ($res['error'] ? ' — ' . $res['error'] : ''));
                    if (!$res['ok']) break; // on n'enchaîne pas après une erreur
                }
                break;

            // Un seul fichier
            case 'run_file':
                $name = (string)post('name');
                $file = Migrator::files()[$name] ?? null;
                if (!$file) throw new RuntimeException('Fichier de migration introuvable.');
                $backup = $doBackup();
                $res = Migrator::execute((string)file_get_contents($file), $name, 'file', $backup);
                $report = ['title' => $name, 'backup' => $backup, 'runs' => [['name' => $name] + $res]];
                AdminAuth::log($res['ok'] ? 'migration_run' : 'migration_error', 'migration', null, $name . ($res['error'] ? ' — ' . $res['error'] : ''));
                break;

            case 'mark_file':
                $name = (string)post('name');
                Migrator::markApplied($name);
                AdminAuth::log('migration_mark', 'migration', null, $name);
                flash('success', '« ' . $name . ' » est marquée comme exécutée.');
                break;

            // SQL saisi ou fichier .sql envoyé
            case 'run_sql':
                $sql = (string)($_POST['sql'] ?? '');
                $label = trim((string)post('label'));
                $upload = $_FILES['sql_file'] ?? null;
                if ($upload && ($upload['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
                    if ($upload['error'] !== UPLOAD_ERR_OK || !is_uploaded_file($upload['tmp_name'])) throw new RuntimeException('Erreur lors de l\'envoi du fichier.');
                    if (!preg_match('/\.sql$/i', $upload['name'])) throw new RuntimeException('Seuls les fichiers .sql sont acceptés.');
                    if ($upload['size'] > 20 * 1024 * 1024) throw new RuntimeException('Fichier trop volumineux (20 Mo maximum).');
                    $sql = (string)file_get_contents($upload['tmp_name']);
                    $label = $label ?: $upload['name'];
                }
                if (trim($sql) === '' || !Migrator::split($sql)) throw new RuntimeException('Aucune requête SQL à exécuter.');
                $label = $label ?: 'SQL manuel du ' . date('d/m/Y H:i');
                $backup = $doBackup();
                $res = Migrator::execute($sql, $label, 'manual', $backup);
                $report = ['title' => $label, 'backup' => $backup, 'runs' => [['name' => $label] + $res]];
                AdminAuth::log($res['ok'] ? 'migration_sql' : 'migration_error', 'migration', null, $label . ($res['error'] ? ' — ' . $res['error'] : ''));
                if (!$res['ok']) $_SESSION['_migration_sql'] = $sql; // conserve la saisie pour correction
                break;

            case 'backup':
                $name = Migrator::backup();
                AdminAuth::log('backup_create', 'migration', null, $name);
                flash('success', 'Sauvegarde créée : ' . $name);
                break;
        }
    } catch (Throwable $e) {
        flash('error', $e->getMessage());
    }
    settings_all(true);
    if ($report) {
        $_SESSION['_migration_report'] = $report;
        $failed = array_filter($report['runs'], fn($r) => !$r['ok']);
        flash($failed ? 'error' : 'success', $failed
            ? 'La migration s\'est arrêtée sur une erreur (détails ci-dessous).'
            : count($report['runs']) . ' migration(s) exécutée(s) avec succès.');
    }
    redirect(admin_url('migrations.php'));
}

$report = $_SESSION['_migration_report'] ?? null;
$draftSql = $_SESSION['_migration_sql'] ?? '';
unset($_SESSION['_migration_report'], $_SESSION['_migration_sql']);

$files = Migrator::files();
$pending = Migrator::pending();
$applied = [];
foreach (DB::all("SELECT name, status, executed_at FROM migrations WHERE source = 'file' AND status IN ('success','marked') ORDER BY id") as $r) {
    $applied[$r['name']] = $r;
}
$total = (int)DB::val('SELECT COUNT(*) FROM migrations');
$pg = paginate($total, 20, (int)query_param('page', 1));
$history = DB::all("SELECT m.*, a.name AS admin_name FROM migrations m LEFT JOIN admins a ON a.id = m.admin_id ORDER BY m.id DESC LIMIT {$pg['per_page']} OFFSET {$pg['offset']}");
$backups = Migrator::backups();

$statusBadge = fn(string $s) => match ($s) {
    'success' => badge('Exécutée', 'success'),
    'marked' => badge('Marquée', 'info'),
    default => badge('Erreur', 'danger'),
};
$size = fn(int $b) => $b > 1048576 ? number_format($b / 1048576, 1, ',', ' ') . ' Mo' : number_format($b / 1024, 0, ',', ' ') . ' Ko';

$pageTitle = 'Migration';
require __DIR__ . '/_inc/layout_top.php';
?>
<p class="muted">Exécutez ici les scripts SQL de mise à jour, sans passer par phpMyAdmin. Une sauvegarde complète de la base est créée avant chaque migration (cochée par défaut) : en cas de problème, téléchargez-la et réimportez-la.</p>

<?php if ($report): ?>
<div class="card">
  <div class="card-head"><h2>Résultat : <?= e($report['title']) ?></h2><?php if ($report['backup']): ?><a class="btn btn-sm" href="<?= e(admin_url('migrations.php', ['download' => $report['backup']])) ?>"><?= aicon('download') ?> Sauvegarde préalable</a><?php endif; ?></div>
  <?php foreach ($report['runs'] as $run): ?>
    <h3 class="mt"><?= e($run['name']) ?> <?= $run['ok'] ? badge('Succès', 'success') : badge('Erreur', 'danger') ?> <span class="muted small"><?= (int)$run['statements'] ?> requête(s) · <?= (int)$run['duration_ms'] ?> ms</span></h3>
    <?php if ($run['error']): ?><div class="alert alert-danger"><?= e($run['error']) ?></div><?php endif; ?>
    <div class="table-wrap">
      <table class="table">
        <thead><tr><th>#</th><th>Requête</th><th>Résultat</th></tr></thead>
        <tbody>
        <?php foreach ($run['results'] as $i => $r): ?>
          <tr>
            <td class="nowrap"><?= $i + 1 ?></td>
            <td><code class="small" style="white-space:pre-wrap;overflow-wrap:anywhere"><?= e(truncate(preg_replace('/\s+/', ' ', $r['sql']), 220)) ?></code></td>
            <td class="nowrap">
              <?php if ($r['error']): ?><?= badge('Erreur', 'danger') ?>
              <?php elseif ($r['rows'] !== null): ?><?= badge($r['count'] . ' ligne(s)', 'info') ?>
              <?php else: ?><?= badge($r['affected'] . ' ligne(s) modifiée(s)', 'success') ?><?php endif; ?>
            </td>
          </tr>
          <?php if ($r['rows']): ?>
          <tr><td></td><td colspan="2">
            <div class="table-wrap"><table class="table small">
              <thead><tr><?php foreach (array_keys($r['rows'][0]) as $col): ?><th><?= e($col) ?></th><?php endforeach; ?></tr></thead>
              <tbody><?php foreach ($r['rows'] as $row): ?><tr><?php foreach ($row as $v): ?><td><?= $v === null ? '<span class="muted">NULL</span>' : e(truncate((string)$v, 120)) ?></td><?php endforeach; ?></tr><?php endforeach; ?></tbody>
            </table></div>
            <?php if ($r['count'] > 200): ?><p class="muted small">200 premières lignes affichées sur <?= (int)$r['count'] ?>.</p><?php endif; ?>
          </td></tr>
          <?php endif; ?>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endforeach; ?>
</div>
<?php endif; ?>

<div class="grid grid-2">
  <div class="card">
    <div class="card-head"><h2>Fichiers de migration</h2><span class="muted small">Dossier <code>sql/migrations/</code></span></div>
    <?php if (!$files): ?>
      <p class="muted">Aucun fichier. Déposez vos scripts <code>.sql</code> dans <code>sql/migrations/</code> (ils s'exécutent par ordre alphabétique : préfixez-les par la date, ex. <code>2026-11-15_ajout-colonne.sql</code>).</p>
    <?php else: ?>
      <div class="table-wrap">
        <table class="table">
          <thead><tr><th>Fichier</th><th>État</th><th></th></tr></thead>
          <tbody>
          <?php foreach ($files as $name => $file): $done = $applied[$name] ?? null; ?>
            <tr>
              <td>
                <strong><?= e($name) ?></strong>
                <details><summary class="small muted">Voir le SQL (<?= e($size((int)filesize($file))) ?>)</summary><pre class="small" style="max-height:300px;overflow:auto;white-space:pre-wrap"><?= e(mb_substr((string)file_get_contents($file), 0, 20000)) ?></pre></details>
              </td>
              <td class="nowrap"><?= $done ? $statusBadge($done['status']) . '<br><span class="muted small">' . e(format_date($done['executed_at'], true)) . '</span>' : badge('En attente', 'warning') ?></td>
              <td class="nowrap right">
                <form method="post" class="inline" data-confirm="Exécuter « <?= e($name) ?> » ?">
                  <?= csrf_field() ?><input type="hidden" name="action" value="run_file"><input type="hidden" name="name" value="<?= e($name) ?>"><input type="hidden" name="backup" value="1">
                  <button class="btn btn-sm<?= $done ? '' : ' btn-primary' ?>" type="submit"><?= $done ? 'Relancer' : 'Exécuter' ?></button>
                </form>
                <?php if (!$done): ?>
                <form method="post" class="inline" data-confirm="Marquer « <?= e($name) ?> » comme déjà exécutée (sans la lancer) ?">
                  <?= csrf_field() ?><input type="hidden" name="action" value="mark_file"><input type="hidden" name="name" value="<?= e($name) ?>">
                  <button class="btn btn-sm btn-link" type="submit">Marquer exécutée</button>
                </form>
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>
    <form method="post" class="form-actions" data-confirm="Exécuter les <?= count($pending) ?> migration(s) en attente ?">
      <?= csrf_field() ?><input type="hidden" name="action" value="run_pending">
      <label class="check"><input type="checkbox" name="backup" value="1" checked> Sauvegarder la base avant</label>
      <button class="btn btn-primary" type="submit"<?= $pending ? '' : ' disabled' ?>><?= aicon('upload') ?> Migrer (<?= count($pending) ?> en attente)</button>
    </form>
  </div>

  <div class="card">
    <div class="card-head"><h2>Exécuter du SQL</h2></div>
    <form method="post" enctype="multipart/form-data" data-confirm="Exécuter ce SQL sur la base de production ?">
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="run_sql">
      <?= field_input('label', 'Nom de la migration (facultatif)', '', ['attrs' => ['maxlength' => 190, 'placeholder' => 'ex. Ajout des zones de livraison Thiès']]) ?>
      <?= field_textarea('sql', 'Requêtes SQL (séparées par ;)', $draftSql, ['rows' => 12, 'attrs' => ['spellcheck' => 'false', 'style' => 'font-family:monospace;font-size:13px', 'placeholder' => "UPDATE settings SET svalue = '...' WHERE skey = '...';"]]) ?>
      <div class="field">
        <label for="sql_file">… ou envoyer un fichier .sql</label>
        <input type="file" id="sql_file" name="sql_file" accept=".sql">
        <small class="help">S'il est fourni, le fichier remplace le texte saisi. 20 Mo maximum.</small>
      </div>
      <div class="form-actions">
        <label class="check"><input type="checkbox" name="backup" value="1" checked> Sauvegarder la base avant</label>
        <button class="btn btn-primary" type="submit"><?= aicon('upload') ?> Migrer</button>
      </div>
      <p class="muted small">Les requêtes s'exécutent une par une et s'arrêtent à la première erreur. Les requêtes de lecture (SELECT, SHOW…) affichent leur résultat.</p>
    </form>
  </div>
</div>

<div class="card flush">
  <div class="card-head"><h2>Historique (<?= $total ?>)</h2></div>
  <div class="table-wrap">
    <table class="table">
      <thead><tr><th>Date</th><th>Migration</th><th>Origine</th><th>État</th><th>Requêtes</th><th>Durée</th><th>Par</th><th>Sauvegarde</th></tr></thead>
      <tbody>
      <?php foreach ($history as $h): ?>
        <tr>
          <td class="nowrap small"><?= e(format_date($h['executed_at'], true)) ?></td>
          <td>
            <strong><?= e($h['name']) ?></strong>
            <?php if ($h['error']): ?><div class="small" style="color:var(--danger,#b91c1c)"><?= e(truncate($h['error'], 300)) ?></div><?php endif; ?>
            <?php if ($h['sql_content']): ?><details><summary class="small muted">SQL</summary><pre class="small" style="max-height:240px;overflow:auto;white-space:pre-wrap"><?= e(mb_substr($h['sql_content'], 0, 20000)) ?></pre></details><?php endif; ?>
          </td>
          <td><?= $h['source'] === 'file' ? badge('Fichier', 'muted') : badge('Manuel', 'primary') ?></td>
          <td><?= $statusBadge($h['status']) ?></td>
          <td><?= (int)$h['statements'] ?></td>
          <td class="nowrap"><?= (int)$h['duration_ms'] ?> ms</td>
          <td><?= e($h['admin_name'] ?? '—') ?></td>
          <td><?php if ($h['backup_file'] && Migrator::backupPath($h['backup_file'])): ?><a href="<?= e(admin_url('migrations.php', ['download' => $h['backup_file']])) ?>"><?= aicon('download') ?></a><?php else: ?><span class="muted">—</span><?php endif; ?></td>
        </tr>
      <?php endforeach; ?>
      <?php if (!$history): ?><tr><td colspan="8" class="table-empty">Aucune migration exécutée pour le moment.</td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>
  <?= pagination_links($pg) ?>
</div>

<div class="card">
  <div class="card-head">
    <h2>Sauvegardes de la base</h2>
    <form method="post" class="inline"><?= csrf_field() ?><input type="hidden" name="action" value="backup"><button class="btn btn-sm" type="submit"><?= aicon('download') ?> Créer une sauvegarde maintenant</button></form>
  </div>
  <?php if (!$backups): ?>
    <p class="muted">Aucune sauvegarde pour le moment.</p>
  <?php else: ?>
    <p class="muted small">Les <?= Migrator::KEEP_BACKUPS ?> dernières sauvegardes sont conservées. Pour restaurer : décompresser le fichier puis l'importer dans phpMyAdmin (ou le coller ci-dessus).</p>
    <div class="table-wrap">
      <table class="table">
        <thead><tr><th>Fichier</th><th>Date</th><th>Taille</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($backups as $b): ?>
          <tr>
            <td><code class="small"><?= e($b['name']) ?></code></td>
            <td class="nowrap small"><?= e(date('d/m/Y H:i', $b['time'])) ?></td>
            <td class="nowrap"><?= e($size($b['size'])) ?></td>
            <td class="right"><a class="btn btn-sm" href="<?= e(admin_url('migrations.php', ['download' => $b['name']])) ?>"><?= aicon('download') ?> Télécharger</a></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</div>
<?php require __DIR__ . '/_inc/layout_bottom.php'; ?>
