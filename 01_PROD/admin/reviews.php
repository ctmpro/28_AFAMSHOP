<?php
/**
 * Modération des avis clients : approuver, rejeter, supprimer (unitaire ou groupé).
 */
require dirname(__DIR__) . '/includes/bootstrap.php';
require __DIR__ . '/_inc/helpers.php';
$admin = AdminAuth::require('reviews');

$statuses = ['pending' => 'En attente', 'approved' => 'Approuvé', 'rejected' => 'Rejeté'];
$statusClass = ['pending' => 'warning', 'approved' => 'success', 'rejected' => 'muted'];

if (is_post()) {
    require_csrf();
    $ids = post_ids('ids');
    if (!$ids && (int)post('id')) {
        $ids = [(int)post('id')];
    }
    $action = (string)post('action');
    if (!$ids) {
        flash('warning', 'Aucun avis sélectionné.');
    } elseif (in_array($action, ['approved', 'rejected', 'pending'], true)) {
        $p = ['st' => $action];
        $n = DB::exec('UPDATE reviews SET status = :st WHERE id IN ' . DB::in($ids, $p), $p);
        AdminAuth::log('review_' . $action, 'review', count($ids) === 1 ? $ids[0] : null, ['ids' => $ids]);
        flash('success', $n . ' avis mis à jour (' . mb_strtolower($statuses[$action]) . ').');
    } elseif ($action === 'delete') {
        $p = [];
        $n = DB::exec('DELETE FROM reviews WHERE id IN ' . DB::in($ids, $p), $p);
        AdminAuth::log('review_delete', 'review', count($ids) === 1 ? $ids[0] : null, ['ids' => $ids]);
        flash('success', $n . ' avis supprimé(s).');
    } else {
        flash('warning', 'Action inconnue.');
    }
    back(admin_url('reviews.php'));
}

$status = (string)query_param('status', 'pending');
$q = (string)query_param('q');
$where = ['1=1'];
$params = [];
if (isset($statuses[$status])) {
    $where[] = 'r.status = :st';
    $params['st'] = $status;
}
if ($q !== '') {
    $where[] = '(r.name LIKE :q OR r.title LIKE :q OR r.body LIKE :q OR p.name LIKE :q OR p.sku LIKE :q)';
    $params['q'] = '%' . $q . '%';
}
$w = implode(' AND ', $where);
$total = (int)DB::val("SELECT COUNT(*) FROM reviews r JOIN products p ON p.id = r.product_id WHERE $w", $params);
$pg = paginate($total, 25, (int)query_param('page', 1));
$rows = DB::all("SELECT r.*, p.name AS product_name, p.slug AS product_slug, p.sku FROM reviews r JOIN products p ON p.id = r.product_id
    WHERE $w ORDER BY r.created_at DESC LIMIT {$pg['per_page']} OFFSET {$pg['offset']}", $params);
$counts = array_column(DB::all('SELECT status, COUNT(*) n FROM reviews GROUP BY status'), 'n', 'status');

$pageTitle = 'Avis clients';
require __DIR__ . '/_inc/layout_top.php';
?>
<?php if (!setting_bool('reviews_enabled', true)): ?>
  <div class="alert alert-warning"><span>Les avis sont actuellement désactivés sur le site (Contenus du site → Boutique).</span></div>
<?php endif; ?>
<div class="tabs">
  <?php foreach ($statuses + ['all' => 'Tous'] as $k => $label): ?>
    <a class="tab<?= $status === $k || ($k === 'all' && !isset($statuses[$status])) ? ' active' : '' ?>" href="<?= e(admin_url('reviews.php', ['status' => $k])) ?>"><?= e($label) ?><?= isset($counts[$k]) ? ' (' . (int)$counts[$k] . ')' : '' ?></a>
  <?php endforeach; ?>
</div>
<div class="card">
  <form method="get" class="filters">
    <input type="hidden" name="status" value="<?= e($status) ?>">
    <div class="field wide"><label for="q">Recherche</label><input type="search" id="q" name="q" value="<?= e($q) ?>" placeholder="Auteur, texte, produit, SKU"></div>
    <button class="btn btn-primary" type="submit"><?= aicon('search') ?> Filtrer</button>
  </form>
</div>
<form method="post" class="card flush">
  <?= csrf_field() ?>
  <div class="card-head"><h2><?= $total ?> avis</h2></div>
  <div class="table-wrap">
    <table class="table">
      <thead><tr><th><input type="checkbox" data-check-all="ids[]" aria-label="Tout sélectionner"></th><th>Date</th><th>Produit</th><th>Note</th><th>Avis</th><th>Statut</th><th></th></tr></thead>
      <tbody>
      <?php foreach ($rows as $r): ?>
        <tr>
          <td><input type="checkbox" name="ids[]" value="<?= (int)$r['id'] ?>" aria-label="Sélectionner"></td>
          <td class="nowrap small"><?= e(format_date($r['created_at'], true)) ?></td>
          <td><?= AdminAuth::can('products') ? '<a href="' . e(admin_url('product_edit.php', ['id' => $r['product_id']])) . '">' . e($r['product_name']) . '</a>' : e($r['product_name']) ?><br><code class="muted small"><?= e($r['sku']) ?></code></td>
          <td class="nowrap" title="<?= (int)$r['rating'] ?>/5" style="color:#f59e0b"><?= str_repeat('★', (int)$r['rating']) ?><span class="muted"><?= str_repeat('☆', 5 - (int)$r['rating']) ?></span></td>
          <td style="max-width:420px">
            <strong><?= e($r['title'] ?: '') ?></strong> <span class="muted small">par <?= e($r['name']) ?><?= $r['customer_id'] ? ' (client)' : '' ?></span><br>
            <?= nl2br(e(truncate($r['body'], 400))) ?>
          </td>
          <td><?= badge($statuses[$r['status']] ?? $r['status'], $statusClass[$r['status']] ?? 'muted') ?></td>
          <td class="actions">
            <?php if ($r['status'] !== 'approved'): ?><button class="btn btn-sm btn-success" type="submit" name="action" value="approved" form="rv-<?= (int)$r['id'] ?>"><?= aicon('check') ?></button><?php endif; ?>
            <?php if ($r['status'] !== 'rejected'): ?><button class="btn btn-sm" type="submit" name="action" value="rejected" form="rv-<?= (int)$r['id'] ?>" title="Rejeter"><?= aicon('x') ?></button><?php endif; ?>
            <button class="btn btn-sm btn-danger" type="submit" name="action" value="delete" form="rv-<?= (int)$r['id'] ?>" data-confirm="Supprimer définitivement cet avis ?" title="Supprimer"><?= aicon('trash') ?></button>
          </td>
        </tr>
      <?php endforeach; ?>
      <?php if (!$rows): ?><tr><td colspan="7" class="table-empty">Aucun avis.</td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>
  <div class="bulk-bar">
    <select name="action" aria-label="Action groupée">
      <option value="">Action groupée…</option>
      <option value="approved">Approuver</option>
      <option value="rejected">Rejeter</option>
      <option value="pending">Remettre en attente</option>
      <option value="delete">Supprimer</option>
    </select>
    <button class="btn" type="submit" data-confirm="Appliquer l'action aux avis sélectionnés ?">Appliquer</button>
  </div>
  <?= pagination_links($pg) ?>
</form>
<?php foreach ($rows as $r): ?>
  <form method="post" id="rv-<?= (int)$r['id'] ?>" hidden><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$r['id'] ?>"></form>
<?php endforeach; ?>
<?php require __DIR__ . '/_inc/layout_bottom.php'; ?>
