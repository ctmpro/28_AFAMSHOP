<?php
/**
 * Journal d'administration et tentatives de connexion (super administrateur).
 */
require dirname(__DIR__) . '/includes/bootstrap.php';
require __DIR__ . '/_inc/helpers.php';
$admin = AdminAuth::require('logs');

$tab = query_param('tab') === 'attempts' ? 'attempts' : 'logs';
$q = (string)query_param('q');
$from = date_param('from');
$to = date_param('to');

if ($tab === 'logs') {
    $adminFilter = (int)query_param('admin', 0);
    $actionFilter = (string)query_param('action');
    $entityFilter = (string)query_param('entity');
    $where = ['1=1'];
    $params = [];
    if ($adminFilter) {
        $where[] = 'l.admin_id = :a';
        $params['a'] = $adminFilter;
    }
    if ($actionFilter !== '') {
        $where[] = 'l.action = :ac';
        $params['ac'] = $actionFilter;
    }
    if ($entityFilter !== '') {
        $where[] = 'l.entity = :en';
        $params['en'] = $entityFilter;
    }
    if ($q !== '') {
        $where[] = '(l.details LIKE :q OR l.ip LIKE :q OR l.entity_id = :qid)';
        $params['q'] = '%' . $q . '%';
        $params['qid'] = ctype_digit($q) ? (int)$q : -1;
    }
    if ($from) {
        $where[] = 'l.created_at >= :from';
        $params['from'] = $from . ' 00:00:00';
    }
    if ($to) {
        $where[] = 'l.created_at <= :to';
        $params['to'] = $to . ' 23:59:59';
    }
    $w = implode(' AND ', $where);
    $total = (int)DB::val("SELECT COUNT(*) FROM admin_logs l WHERE $w", $params);
    $pg = paginate($total, 50, (int)query_param('page', 1));
    $rows = DB::all("SELECT l.*, a.name AS admin_name, a.email AS admin_email FROM admin_logs l LEFT JOIN admins a ON a.id = l.admin_id
        WHERE $w ORDER BY l.id DESC LIMIT {$pg['per_page']} OFFSET {$pg['offset']}", $params);
    $actions = DB::col('SELECT DISTINCT action FROM admin_logs ORDER BY action');
    $entities = DB::col('SELECT DISTINCT entity FROM admin_logs WHERE entity IS NOT NULL ORDER BY entity');
    $admins = array_column(DB::all('SELECT id, name FROM admins ORDER BY name'), 'name', 'id');
} else {
    $scope = (string)query_param('scope');
    $where = ['1=1'];
    $params = [];
    if (in_array($scope, ['admin', 'customer'], true)) {
        $where[] = 'scope = :s';
        $params['s'] = $scope;
    }
    if ($q !== '') {
        $where[] = '(identifier LIKE :q OR ip LIKE :q)';
        $params['q'] = '%' . $q . '%';
    }
    if ($from) {
        $where[] = 'attempted_at >= :from';
        $params['from'] = $from . ' 00:00:00';
    }
    if ($to) {
        $where[] = 'attempted_at <= :to';
        $params['to'] = $to . ' 23:59:59';
    }
    $w = implode(' AND ', $where);
    $total = (int)DB::val("SELECT COUNT(*) FROM login_attempts WHERE $w", $params);
    $pg = paginate($total, 50, (int)query_param('page', 1));
    $rows = DB::all("SELECT * FROM login_attempts WHERE $w ORDER BY id DESC LIMIT {$pg['per_page']} OFFSET {$pg['offset']}", $params);
    // IP les plus actives sur les dernières 24 h
    $topIps = DB::all('SELECT ip, COUNT(*) n, COUNT(DISTINCT identifier) idents FROM login_attempts WHERE attempted_at > :t GROUP BY ip ORDER BY n DESC LIMIT 10', ['t' => date('Y-m-d H:i:s', time() - 86400)]);
}

$pageTitle = 'Journal';
require __DIR__ . '/_inc/layout_top.php';
?>
<div class="tabs">
  <a class="tab<?= $tab === 'logs' ? ' active' : '' ?>" href="<?= e(admin_url('logs.php')) ?>">Actions des administrateurs</a>
  <a class="tab<?= $tab === 'attempts' ? ' active' : '' ?>" href="<?= e(admin_url('logs.php', ['tab' => 'attempts'])) ?>">Tentatives de connexion échouées</a>
</div>

<div class="card">
  <form method="get" class="filters">
    <?php if ($tab === 'attempts'): ?><input type="hidden" name="tab" value="attempts"><?php endif; ?>
    <div class="field wide"><label for="q">Recherche</label><input type="search" id="q" name="q" value="<?= e($q) ?>" placeholder="<?= $tab === 'logs' ? 'Détails, IP, n° d\'élément' : 'Identifiant ou IP' ?>"></div>
    <?php if ($tab === 'logs'): ?>
      <div class="field"><label for="admin">Administrateur</label><select id="admin" name="admin"><?= select_options($admins, $adminFilter ?: '', 'Tous') ?></select></div>
      <div class="field"><label for="action">Action</label><select id="action" name="action"><?= select_options(array_combine($actions, $actions) ?: [], $actionFilter, 'Toutes') ?></select></div>
      <div class="field"><label for="entity">Élément</label><select id="entity" name="entity"><?= select_options(array_combine($entities, $entities) ?: [], $entityFilter, 'Tous') ?></select></div>
    <?php else: ?>
      <div class="field"><label for="scope">Espace</label><select id="scope" name="scope"><?= select_options(['admin' => 'Administration', 'customer' => 'Clients'], $scope, 'Tous') ?></select></div>
    <?php endif; ?>
    <div class="field"><label for="from">Du</label><input type="date" id="from" name="from" value="<?= e($from) ?>"></div>
    <div class="field"><label for="to">Au</label><input type="date" id="to" name="to" value="<?= e($to) ?>"></div>
    <button class="btn btn-primary" type="submit"><?= aicon('search') ?> Filtrer</button>
  </form>
</div>

<?php if ($tab === 'logs'): ?>
  <div class="card flush">
    <div class="card-head"><h2><?= $total ?> entrée(s)</h2></div>
    <div class="table-wrap">
      <table class="table">
        <thead><tr><th>Date</th><th>Administrateur</th><th>Action</th><th>Élément</th><th>Détails</th><th>IP</th></tr></thead>
        <tbody>
        <?php foreach ($rows as $l): ?>
          <tr>
            <td class="nowrap small"><?= e(format_date($l['created_at'], true)) ?></td>
            <td><?= $l['admin_name'] ? e($l['admin_name']) : '<span class="muted">—</span>' ?></td>
            <td><code><?= e($l['action']) ?></code></td>
            <td class="nowrap"><?= e($l['entity'] ?? '') ?><?= $l['entity_id'] ? ' #' . (int)$l['entity_id'] : '' ?></td>
            <td class="small" style="max-width:420px;overflow-wrap:anywhere"><?= e(truncate($l['details'], 300)) ?></td>
            <td class="small"><code><?= e($l['ip']) ?></code></td>
          </tr>
        <?php endforeach; ?>
        <?php if (!$rows): ?><tr><td colspan="6" class="table-empty">Aucune entrée.</td></tr><?php endif; ?>
        </tbody>
      </table>
    </div>
    <?= pagination_links($pg) ?>
  </div>
<?php else: ?>
  <div class="grid grid-main">
    <div class="card flush">
      <div class="card-head"><h2><?= $total ?> tentative(s) échouée(s)</h2><span class="muted small">Les tentatives sont effacées après une connexion réussie.</span></div>
      <div class="table-wrap">
        <table class="table">
          <thead><tr><th>Date</th><th>Espace</th><th>Identifiant</th><th>IP</th></tr></thead>
          <tbody>
          <?php foreach ($rows as $r): ?>
            <tr>
              <td class="nowrap small"><?= e(format_date($r['attempted_at'], true)) ?></td>
              <td><?= $r['scope'] === 'admin' ? badge('Admin', 'warning') : badge('Client', 'muted') ?></td>
              <td><?= e($r['identifier']) ?></td>
              <td><code><?= e($r['ip']) ?></code></td>
            </tr>
          <?php endforeach; ?>
          <?php if (!$rows): ?><tr><td colspan="4" class="table-empty">Aucune tentative échouée.</td></tr><?php endif; ?>
          </tbody>
        </table>
      </div>
      <?= pagination_links($pg) ?>
    </div>
    <div class="card">
      <h2>IP les plus actives (24 h)</h2>
      <?php foreach ($topIps as $ip): ?>
        <?= info_row($ip['ip'], (int)$ip['n'] . ' tentative(s) · ' . (int)$ip['idents'] . ' identifiant(s)') ?>
      <?php endforeach; ?>
      <?php if (!$topIps): ?><p class="muted">Aucune activité suspecte.</p><?php endif; ?>
    </div>
  </div>
<?php endif; ?>
<?php require __DIR__ . '/_inc/layout_bottom.php'; ?>
