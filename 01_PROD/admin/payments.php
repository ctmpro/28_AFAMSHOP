<?php
/**
 * Paiements : liste des transactions avec filtres (prestataire, statut, recherche).
 */
require dirname(__DIR__) . '/includes/bootstrap.php';
require __DIR__ . '/_inc/helpers.php';
$admin = AdminAuth::require('payments');

$provider = (string)query_param('provider');
$status = (string)query_param('status');
$q = (string)query_param('q');
$from = date_param('from');
$to = date_param('to');

$where = ['1=1'];
$params = [];
if ($provider !== '') {
    $where[] = 'p.provider = :pr';
    $params['pr'] = $provider;
}
if (isset(payment_statuses()[$status])) {
    $where[] = 'p.status = :st';
    $params['st'] = $status;
}
if ($q !== '') {
    $where[] = '(o.order_number LIKE :q OR p.reference LIKE :q OR p.transaction_id LIKE :q OR o.email LIKE :q)';
    $params['q'] = '%' . $q . '%';
}
if ($from) {
    $where[] = 'p.created_at >= :from';
    $params['from'] = $from . ' 00:00:00';
}
if ($to) {
    $where[] = 'p.created_at <= :to';
    $params['to'] = $to . ' 23:59:59';
}
$w = implode(' AND ', $where);
$summary = DB::one("SELECT COUNT(*) n, COALESCE(SUM(CASE WHEN p.status = 'paid' THEN p.amount ELSE 0 END),0) paid
    FROM payments p JOIN orders o ON o.id = p.order_id WHERE $w", $params);
$pg = paginate((int)$summary['n'], 30, (int)query_param('page', 1));
$rows = DB::all("SELECT p.*, o.order_number, o.first_name, o.last_name FROM payments p JOIN orders o ON o.id = p.order_id
    WHERE $w ORDER BY p.created_at DESC, p.id DESC LIMIT {$pg['per_page']} OFFSET {$pg['offset']}", $params);

$providers = [];
foreach (array_unique(array_merge(['stripe', 'paydunya', 'cod', 'transfer'], DB::col('SELECT DISTINCT provider FROM payments'))) as $pr) {
    $providers[$pr] = payment_method_label($pr);
}

$pageTitle = 'Paiements';
require __DIR__ . '/_inc/layout_top.php';
?>
<div class="card">
  <form method="get" class="filters">
    <div class="field wide"><label for="q">Recherche</label><input type="search" id="q" name="q" value="<?= e($q) ?>" placeholder="N° de commande, référence, transaction, email"></div>
    <div class="field"><label for="provider">Prestataire</label><select id="provider" name="provider"><?= select_options($providers, $provider, 'Tous') ?></select></div>
    <div class="field"><label for="status">Statut</label><select id="status" name="status"><?= select_options(payment_statuses(), $status, 'Tous') ?></select></div>
    <div class="field"><label for="from">Du</label><input type="date" id="from" name="from" value="<?= e($from) ?>"></div>
    <div class="field"><label for="to">Au</label><input type="date" id="to" name="to" value="<?= e($to) ?>"></div>
    <button class="btn btn-primary" type="submit"><?= aicon('search') ?> Filtrer</button>
  </form>
</div>
<div class="card flush">
  <div class="card-head"><h2><?= (int)$summary['n'] ?> transaction(s)</h2><span class="muted small">Total encaissé : <strong><?= e(admin_money($summary['paid'])) ?></strong></span></div>
  <div class="table-wrap">
    <table class="table">
      <thead><tr><th>Date</th><th>Commande</th><th>Client</th><th>Prestataire</th><th>Référence / transaction</th><th class="num">Montant</th><th>Statut</th></tr></thead>
      <tbody>
      <?php foreach ($rows as $p): ?>
        <tr>
          <td class="nowrap"><?= e(format_date($p['created_at'], true)) ?></td>
          <td><?= AdminAuth::can('orders') ? '<a href="' . e(admin_url('order.php', ['id' => $p['order_id']])) . '"><strong>' . e($p['order_number']) . '</strong></a>' : e($p['order_number']) ?></td>
          <td><?= e($p['first_name'] . ' ' . $p['last_name']) ?></td>
          <td><?= e(payment_method_label($p['provider'])) ?></td>
          <td class="small"><?= $p['reference'] ? '<code>' . e(truncate($p['reference'], 40)) . '</code>' : '' ?><?= $p['transaction_id'] ? '<br><code>' . e($p['transaction_id']) . '</code>' : '' ?></td>
          <td class="num"><?= e(money($p['amount'], $p['currency'], false)) ?></td>
          <td><?= payment_badge($p['status']) ?></td>
        </tr>
      <?php endforeach; ?>
      <?php if (!$rows): ?><tr><td colspan="7" class="table-empty">Aucune transaction.</td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>
  <?= pagination_links($pg) ?>
</div>
<?php require __DIR__ . '/_inc/layout_bottom.php'; ?>
