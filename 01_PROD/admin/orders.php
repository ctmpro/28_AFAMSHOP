<?php
/**
 * Liste des commandes : recherche, filtres (statut, paiement, mode, dates), pagination.
 */
require dirname(__DIR__) . '/includes/bootstrap.php';
require __DIR__ . '/_inc/helpers.php';
$admin = AdminAuth::require('orders');

$q = (string)query_param('q');
$status = (string)query_param('status');
$payStatus = (string)query_param('payment_status');
$method = (string)query_param('method');
$from = date_param('from');
$to = date_param('to');

$where = ['1=1'];
$params = [];
if ($q !== '') {
    $where[] = "(o.order_number LIKE :q OR o.email LIKE :q OR o.phone LIKE :q OR o.invoice_number LIKE :q
        OR CONCAT(o.first_name, ' ', o.last_name) LIKE :q OR o.company LIKE :q)";
    $params['q'] = '%' . $q . '%';
}
if (isset(order_statuses()[$status])) {
    $where[] = 'o.status = :st';
    $params['st'] = $status;
}
if (isset(payment_statuses()[$payStatus])) {
    $where[] = 'o.payment_status = :ps';
    $params['ps'] = $payStatus;
}
if ($method !== '') {
    $where[] = 'o.payment_method = :pm';
    $params['pm'] = $method;
}
if ($from) {
    $where[] = 'o.created_at >= :from';
    $params['from'] = $from . ' 00:00:00';
}
if ($to) {
    $where[] = 'o.created_at <= :to';
    $params['to'] = $to . ' 23:59:59';
}
$w = implode(' AND ', $where);
$summary = DB::one('SELECT COUNT(*) n, COALESCE(SUM(o.total),0) total, COALESCE(SUM(CASE WHEN ' . ADMIN_REVENUE_SQL . ' THEN o.total ELSE 0 END),0) paid FROM orders o WHERE ' . $w, $params);
$pg = paginate((int)$summary['n'], 25, (int)query_param('page', 1));
$order = admin_sort_sql(['date' => 'o.created_at', 'number' => 'o.order_number', 'total' => 'o.total', 'customer' => 'o.last_name'], 'date');
$rows = DB::all('SELECT o.*, (SELECT SUM(qty) FROM order_items oi WHERE oi.order_id = o.id) AS n_items FROM orders o WHERE ' . $w
    . ' ORDER BY ' . $order . ', o.id DESC LIMIT ' . $pg['per_page'] . ' OFFSET ' . $pg['offset'], $params);

$methods = [];
foreach (array_unique(array_merge(['stripe', 'paydunya', 'cod', 'transfer'], DB::col('SELECT DISTINCT payment_method FROM orders'))) as $m) {
    $methods[$m] = payment_method_label($m);
}

$pageTitle = 'Commandes';
$exportParams = ['export' => 'orders', 'from' => $from ?: date('Y-m-01'), 'to' => $to ?: date('Y-m-d')];
$pageActions = AdminAuth::can('stats') ? '<a class="btn" href="' . e(admin_url('stats.php', $exportParams)) . '">' . aicon('download') . ' Export CSV</a>' : '';
require __DIR__ . '/_inc/layout_top.php';
?>
<div class="card">
  <form method="get" class="filters">
    <div class="field wide"><label for="q">Recherche</label><input type="search" id="q" name="q" value="<?= e($q) ?>" placeholder="N° de commande, nom, email, téléphone…"></div>
    <div class="field"><label for="status">Statut</label><select id="status" name="status"><?= select_options(order_statuses(), $status, 'Tous') ?></select></div>
    <div class="field"><label for="payment_status">Paiement</label><select id="payment_status" name="payment_status"><?= select_options(payment_statuses(), $payStatus, 'Tous') ?></select></div>
    <div class="field"><label for="method">Mode de paiement</label><select id="method" name="method"><?= select_options($methods, $method, 'Tous') ?></select></div>
    <div class="field"><label for="from">Du</label><input type="date" id="from" name="from" value="<?= e($from) ?>"></div>
    <div class="field"><label for="to">Au</label><input type="date" id="to" name="to" value="<?= e($to) ?>"></div>
    <button class="btn btn-primary" type="submit"><?= aicon('search') ?> Filtrer</button>
    <a class="btn" href="<?= e(admin_url('orders.php')) ?>">Réinitialiser</a>
  </form>
</div>

<div class="card flush">
  <div class="card-head">
    <h2><?= (int)$summary['n'] ?> commande(s)</h2>
    <span class="muted small">Montant total : <strong><?= e(admin_money($summary['total'])) ?></strong> · encaissé : <strong><?= e(admin_money($summary['paid'])) ?></strong></span>
  </div>
  <div class="table-wrap">
    <table class="table">
      <thead><tr>
        <th><?= sort_link('number', 'N°') ?></th>
        <th><?= sort_link('date', 'Date') ?></th>
        <th><?= sort_link('customer', 'Client') ?></th>
        <th>Livraison</th>
        <th class="num">Articles</th>
        <th class="num"><?= sort_link('total', 'Total') ?></th>
        <th>Paiement</th>
        <th>Statut</th>
      </tr></thead>
      <tbody>
      <?php foreach ($rows as $o): ?>
        <tr>
          <td class="nowrap"><a href="<?= e(admin_url('order.php', ['id' => $o['id']])) ?>"><strong><?= e($o['order_number']) ?></strong></a><?= $o['invoice_number'] ? '<br><span class="muted small">' . e($o['invoice_number']) . '</span>' : '' ?></td>
          <td class="nowrap"><?= e(format_date($o['created_at'], true)) ?></td>
          <td><?= e($o['first_name'] . ' ' . $o['last_name']) ?><?= $o['company'] ? ' <span class="muted small">(' . e($o['company']) . ')</span>' : '' ?><br><span class="muted small"><?= e($o['email']) ?> · <?= e($o['phone']) ?></span></td>
          <td><?= $o['delivery_method'] === 'pickup' ? 'Retrait' : e($o['zone_name'] ?? $o['ship_city'] ?? '') ?></td>
          <td class="num"><?= (int)$o['n_items'] ?></td>
          <td class="num"><strong><?= e(admin_money($o['total'])) ?></strong></td>
          <td><?= e(payment_method_label($o['payment_method'])) ?><br><?= payment_badge($o['payment_status']) ?></td>
          <td><?= Orders::statusBadge($o['status']) ?></td>
        </tr>
      <?php endforeach; ?>
      <?php if (!$rows): ?><tr><td colspan="8" class="table-empty">Aucune commande ne correspond à ces critères.</td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>
  <?= pagination_links($pg) ?>
</div>
<?php require __DIR__ . '/_inc/layout_bottom.php'; ?>
