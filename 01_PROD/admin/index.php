<?php
/**
 * Tableau de bord : indicateurs clés, graphiques des 30 derniers jours, alertes.
 */
require dirname(__DIR__) . '/includes/bootstrap.php';
require __DIR__ . '/_inc/helpers.php';
$admin = AdminAuth::require('dashboard');

$today = date('Y-m-d');
$monthStart = date('Y-m-01');
$canOrders = AdminAuth::can('orders');
$canStock = AdminAuth::can('stock') || AdminAuth::can('products');
$canRequests = AdminAuth::can('requests');

// --- Indicateurs
$kpi = [
    'ca_day' => (float)DB::val('SELECT COALESCE(SUM(o.total),0) FROM orders o WHERE ' . ADMIN_REVENUE_SQL . ' AND DATE(o.created_at) = :d', ['d' => $today]),
    'ca_month' => (float)DB::val('SELECT COALESCE(SUM(o.total),0) FROM orders o WHERE ' . ADMIN_REVENUE_SQL . ' AND o.created_at >= :d', ['d' => $monthStart]),
    'orders_day' => (int)DB::val('SELECT COUNT(*) FROM orders WHERE DATE(created_at) = :d', ['d' => $today]),
    'orders_month' => (int)DB::val('SELECT COUNT(*) FROM orders WHERE created_at >= :d', ['d' => $monthStart]),
    'orders_todo' => (int)DB::val("SELECT COUNT(*) FROM orders WHERE status IN ('received','paid','preparing')"),
    'customers' => (int)DB::val('SELECT COUNT(*) FROM customers'),
    'products' => (int)DB::val('SELECT COUNT(*) FROM products'),
    'out' => (int)DB::val('SELECT COUNT(*) FROM products WHERE stock <= 0'),
    'low' => (int)DB::val('SELECT COUNT(*) FROM products WHERE stock > 0 AND stock <= COALESCE(low_stock_threshold, :t)', ['t' => (int)setting('low_stock_threshold', 5)]),
    'quotes' => (int)DB::val("SELECT COUNT(*) FROM requests WHERE status = 'new' AND type = 'quote'"),
    'requests_new' => (int)DB::val("SELECT COUNT(*) FROM requests WHERE status = 'new'"),
];

// --- Séries des 30 derniers jours
$caByDay = day_series(30);
$ordersByDay = day_series(30);
$since = array_key_first($caByDay);
foreach (DB::all('SELECT DATE(o.created_at) d, COUNT(*) n, SUM(CASE WHEN ' . ADMIN_REVENUE_SQL . ' THEN o.total ELSE 0 END) ca
                  FROM orders o WHERE o.created_at >= :s GROUP BY DATE(o.created_at)', ['s' => $since]) as $r) {
    if (isset($caByDay[$r['d']])) {
        $caByDay[$r['d']] = (float)$r['ca'];
        $ordersByDay[$r['d']] = (int)$r['n'];
    }
}
$fmtLabels = function (array $series): array {
    $out = [];
    foreach ($series as $d => $v) $out[date('d/m', strtotime($d))] = $v;
    return $out;
};

// --- Meilleures ventes (30 jours)
$topProducts = DB::all(
    "SELECT oi.name, SUM(oi.qty) qty, SUM(oi.line_total) amount FROM order_items oi
     JOIN orders o ON o.id = oi.order_id
     WHERE o.created_at >= :s AND o.status NOT IN ('cancelled','refunded')
     GROUP BY oi.sku, oi.name ORDER BY qty DESC LIMIT 8",
    ['s' => date('Y-m-d', strtotime('-29 days'))]
);

$latestOrders = $canOrders ? DB::all('SELECT * FROM orders ORDER BY created_at DESC LIMIT 8') : [];
$latestRequests = $canRequests ? DB::all('SELECT * FROM requests ORDER BY created_at DESC LIMIT 6') : [];
$lowStock = $canStock ? Stock::lowStockProducts(8) : [];
$outStock = $canStock ? Stock::outOfStockProducts(8) : [];

$pageTitle = 'Tableau de bord';
require __DIR__ . '/_inc/layout_top.php';
?>
<div class="kpis">
  <div class="kpi"><div class="kpi-label"><?= aicon('money') ?> CA du jour</div><div class="kpi-value"><?= e(admin_money($kpi['ca_day'])) ?></div></div>
  <div class="kpi"><div class="kpi-label"><?= aicon('chart') ?> CA du mois</div><div class="kpi-value"><?= e(admin_money($kpi['ca_month'])) ?></div></div>
  <a class="kpi" href="<?= e(admin_url('orders.php')) ?>"><div class="kpi-label"><?= aicon('cart') ?> Commandes (jour / mois)</div><div class="kpi-value"><?= $kpi['orders_day'] ?> / <?= $kpi['orders_month'] ?></div></a>
  <a class="kpi<?= $kpi['orders_todo'] ? ' warn' : '' ?>" href="<?= e(admin_url('orders.php', ['status' => 'received'])) ?>"><div class="kpi-label"><?= aicon('alert') ?> Commandes à traiter</div><div class="kpi-value"><?= $kpi['orders_todo'] ?></div></a>
  <a class="kpi" href="<?= e(admin_url('customers.php')) ?>"><div class="kpi-label"><?= aicon('users') ?> Clients</div><div class="kpi-value"><?= $kpi['customers'] ?></div></a>
  <a class="kpi" href="<?= e(admin_url('products.php')) ?>"><div class="kpi-label"><?= aicon('box') ?> Produits</div><div class="kpi-value"><?= $kpi['products'] ?></div></a>
  <a class="kpi<?= $kpi['out'] ? ' danger' : '' ?>" href="<?= e(admin_url('stock.php', ['status' => 'out'])) ?>"><div class="kpi-label"><?= aicon('x') ?> En rupture</div><div class="kpi-value"><?= $kpi['out'] ?></div></a>
  <a class="kpi<?= $kpi['low'] ? ' warn' : '' ?>" href="<?= e(admin_url('stock.php', ['status' => 'low'])) ?>"><div class="kpi-label"><?= aicon('layers') ?> Stock faible</div><div class="kpi-value"><?= $kpi['low'] ?></div></a>
  <a class="kpi<?= $kpi['quotes'] ? ' warn' : '' ?>" href="<?= e(admin_url('requests.php', ['type' => 'quote', 'status' => 'new'])) ?>"><div class="kpi-label"><?= aicon('inbox') ?> Devis à traiter</div><div class="kpi-value"><?= $kpi['quotes'] ?></div></a>
</div>

<div class="grid grid-2">
  <div class="card">
    <div class="card-head"><h2>Chiffre d'affaires — 30 derniers jours</h2><span class="muted small"><?= e(admin_money(array_sum($caByDay))) ?></span></div>
    <?= svg_chart($fmtLabels($caByDay), ['type' => 'line', 'label' => "Chiffre d'affaires par jour", 'format' => fn($v) => admin_money($v)]) ?>
  </div>
  <div class="card">
    <div class="card-head"><h2>Commandes — 30 derniers jours</h2><span class="muted small"><?= array_sum($ordersByDay) ?> commande(s)</span></div>
    <?= svg_chart($fmtLabels($ordersByDay), ['type' => 'bar', 'label' => 'Commandes par jour', 'format' => fn($v) => $v . ' commande(s)']) ?>
  </div>
</div>

<div class="grid grid-main mt">
  <div class="stack">
    <?php if ($canOrders): ?>
    <div class="card flush">
      <div class="card-head"><h2>Dernières commandes</h2><a class="btn btn-sm" href="<?= e(admin_url('orders.php')) ?>">Toutes les commandes</a></div>
      <div class="table-wrap">
        <table class="table">
          <thead><tr><th>N°</th><th>Client</th><th>Date</th><th class="num">Total</th><th>Statut</th><th>Paiement</th></tr></thead>
          <tbody>
          <?php foreach ($latestOrders as $o): ?>
            <tr>
              <td><a href="<?= e(admin_url('order.php', ['id' => $o['id']])) ?>"><strong><?= e($o['order_number']) ?></strong></a></td>
              <td><?= e($o['first_name'] . ' ' . $o['last_name']) ?></td>
              <td class="nowrap"><?= e(format_date($o['created_at'], true)) ?></td>
              <td class="num"><?= e(admin_money($o['total'])) ?></td>
              <td><?= Orders::statusBadge($o['status']) ?></td>
              <td><?= payment_badge($o['payment_status']) ?></td>
            </tr>
          <?php endforeach; ?>
          <?php if (!$latestOrders): ?><tr><td colspan="6" class="table-empty">Aucune commande pour le moment.</td></tr><?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
    <?php endif; ?>

    <?php if ($canRequests): ?>
    <div class="card flush">
      <div class="card-head"><h2>Dernières demandes</h2><a class="btn btn-sm" href="<?= e(admin_url('requests.php')) ?>">Toutes les demandes</a></div>
      <div class="table-wrap">
        <table class="table">
          <thead><tr><th>Type</th><th>Nom</th><th>Objet</th><th>Date</th><th>Statut</th></tr></thead>
          <tbody>
          <?php foreach ($latestRequests as $r): ?>
            <tr>
              <td><?= badge(request_types()[$r['type']] ?? $r['type'], 'primary') ?></td>
              <td><a href="<?= e(admin_url('requests.php', ['id' => $r['id']])) ?>"><?= e($r['name']) ?></a><?= $r['company'] ? '<br><span class="muted small">' . e($r['company']) . '</span>' : '' ?></td>
              <td><?= e(truncate($r['subject'] ?: ($r['product_label'] ?: $r['message']), 60)) ?></td>
              <td class="nowrap"><?= e(format_date($r['created_at'], true)) ?></td>
              <td><?= request_status_badge($r['status']) ?></td>
            </tr>
          <?php endforeach; ?>
          <?php if (!$latestRequests): ?><tr><td colspan="5" class="table-empty">Aucune demande.</td></tr><?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
    <?php endif; ?>
  </div>

  <div class="stack">
    <div class="card">
      <div class="card-head"><h2>Meilleures ventes (30 j)</h2></div>
      <?= hbar_list(array_map(fn($p) => ['label' => $p['name'], 'value' => (int)$p['qty'], 'display' => $p['qty'] . ' vendu(s)'], $topProducts)) ?>
    </div>

    <?php if ($canStock): ?>
    <div class="card">
      <div class="card-head"><h2>Alertes stock</h2><a class="btn btn-sm" href="<?= e(admin_url('stock.php')) ?>">Gérer</a></div>
      <?php if (!$lowStock && !$outStock): ?>
        <p class="muted">Aucune alerte : tous les stocks sont au-dessus du seuil.</p>
      <?php endif; ?>
      <?php foreach ($outStock as $p): ?>
        <div class="info-row"><span class="info-value"><a href="<?= e(admin_url('product_edit.php', ['id' => $p['id']])) ?>"><?= e($p['name']) ?></a> <span class="muted small"><?= e($p['sku']) ?></span></span><span style="margin-left:auto"><?= $p['on_order'] ? badge('Sur commande', 'info') : badge('Rupture', 'danger') ?></span></div>
      <?php endforeach; ?>
      <?php foreach ($lowStock as $p): ?>
        <div class="info-row"><span class="info-value"><a href="<?= e(admin_url('product_edit.php', ['id' => $p['id']])) ?>"><?= e($p['name']) ?></a> <span class="muted small"><?= e($p['sku']) ?></span></span><span style="margin-left:auto"><?= badge($p['stock'] . ' restant(s)', 'warning') ?></span></div>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
  </div>
</div>
<?php require __DIR__ . '/_inc/layout_bottom.php'; ?>
