<?php
/**
 * Statistiques : chiffre d'affaires mensuel, statuts, meilleures ventes, meilleurs clients,
 * ventes par catégorie, moyens de paiement, export CSV des commandes.
 */
require dirname(__DIR__) . '/includes/bootstrap.php';
require __DIR__ . '/_inc/helpers.php';
$admin = AdminAuth::require('stats');

// --- Période
$presets = [
    '30d' => ['30 derniers jours', date('Y-m-d', strtotime('-29 days')), date('Y-m-d')],
    'month' => ['Ce mois-ci', date('Y-m-01'), date('Y-m-d')],
    'last_month' => ['Mois dernier', date('Y-m-01', strtotime('first day of last month')), date('Y-m-t', strtotime('last day of last month'))],
    'year' => ['Cette année', date('Y-01-01'), date('Y-m-d')],
    '12m' => ['12 derniers mois', date('Y-m-01', strtotime('-11 months')), date('Y-m-d')],
];
$preset = (string)query_param('preset');
$from = date_param('from');
$to = date_param('to');
if (isset($presets[$preset])) {
    [, $from, $to] = $presets[$preset];
}
if (!$from || !$to) {
    [, $from, $to] = $presets['12m'];
    $preset = $preset ?: '12m';
}
if ($from > $to) {
    [$from, $to] = [$to, $from];
}
$p = ['f' => $from . ' 00:00:00', 't' => $to . ' 23:59:59'];
$periodSql = 'o.created_at BETWEEN :f AND :t';

// --- Export CSV des commandes de la période
if (query_param('export') === 'orders') {
    AdminAuth::log('orders_export', 'order', null, ['from' => $from, 'to' => $to]);
    $rows = DB::query("SELECT o.order_number, o.created_at, o.first_name, o.last_name, o.company, o.email, o.phone,
            o.delivery_method, o.zone_name, o.ship_city, o.subtotal, o.discount, o.coupon_code, o.delivery_fee, o.tax_amount, o.total,
            o.currency, o.payment_method, o.payment_status, o.status, o.invoice_number,
            (SELECT SUM(qty) FROM order_items oi WHERE oi.order_id = o.id) AS items
        FROM orders o WHERE $periodSql ORDER BY o.created_at", $p);
    $gen = (function () use ($rows) {
        while ($r = $rows->fetch()) {
            $r['payment_method'] = payment_method_label($r['payment_method']);
            $r['payment_status'] = payment_statuses()[$r['payment_status']] ?? $r['payment_status'];
            $r['status'] = order_statuses()[$r['status']] ?? $r['status'];
            $r['delivery_method'] = $r['delivery_method'] === 'pickup' ? 'Retrait' : 'Livraison';
            yield $r;
        }
    })();
    csv_download('commandes-' . $from . '-au-' . $to . '.csv', [
        'N° commande', 'Date', 'Prénom', 'Nom', 'Société', 'Email', 'Téléphone', 'Mode de livraison', 'Zone', 'Ville',
        'Sous-total', 'Remise', 'Code promo', 'Livraison', 'TVA', 'Total', 'Devise', 'Moyen de paiement', 'Statut paiement',
        'Statut commande', 'N° facture', 'Articles',
    ], $gen);
}

// --- Indicateurs de la période
$kpi = DB::one("SELECT COUNT(*) n,
        COALESCE(SUM(CASE WHEN " . ADMIN_REVENUE_SQL . " THEN o.total ELSE 0 END),0) ca,
        SUM(CASE WHEN " . ADMIN_REVENUE_SQL . " THEN 1 ELSE 0 END) n_paid,
        SUM(o.status IN ('cancelled','refunded')) n_cancelled
    FROM orders o WHERE $periodSql", $p);
$itemsSold = (int)DB::val("SELECT COALESCE(SUM(oi.qty),0) FROM order_items oi JOIN orders o ON o.id = oi.order_id WHERE $periodSql AND " . ADMIN_REVENUE_SQL, $p);
$newCustomers = (int)DB::val('SELECT COUNT(*) FROM customers WHERE created_at BETWEEN :f AND :t', $p);
$avgBasket = $kpi['n_paid'] ? (float)$kpi['ca'] / (int)$kpi['n_paid'] : 0;

// --- CA des 12 mois se terminant au mois de fin
$months = [];
$endMonth = date('Y-m-01', strtotime($to));
for ($i = 11; $i >= 0; $i--) {
    $months[date('Y-m', strtotime("$endMonth -$i months"))] = 0;
}
$startMonth = array_key_first($months) . '-01 00:00:00';
foreach (DB::all("SELECT DATE_FORMAT(o.created_at, '%Y-%m') m, SUM(o.total) ca FROM orders o
        WHERE o.created_at >= :s AND o.created_at <= :e AND " . ADMIN_REVENUE_SQL . " GROUP BY m",
        ['s' => $startMonth, 'e' => date('Y-m-t 23:59:59', strtotime($endMonth))]) as $r) {
    if (isset($months[$r['m']])) $months[$r['m']] = (float)$r['ca'];
}
$monthNames = ['01' => 'janv.', '02' => 'févr.', '03' => 'mars', '04' => 'avr.', '05' => 'mai', '06' => 'juin', '07' => 'juil.', '08' => 'août', '09' => 'sept.', '10' => 'oct.', '11' => 'nov.', '12' => 'déc.'];
$monthChart = [];
foreach ($months as $m => $v) {
    $monthChart[$monthNames[substr($m, 5, 2)] . ' ' . substr($m, 2, 2)] = $v;
}

// --- Répartitions
$byStatus = DB::all("SELECT o.status, COUNT(*) n, SUM(o.total) amount FROM orders o WHERE $periodSql GROUP BY o.status ORDER BY n DESC", $p);
$topProducts = DB::all("SELECT oi.sku, oi.name, MAX(oi.product_id) product_id, SUM(oi.qty) qty, SUM(oi.line_total) amount
    FROM order_items oi JOIN orders o ON o.id = oi.order_id
    WHERE $periodSql AND " . ADMIN_REVENUE_SQL . " GROUP BY oi.sku, oi.name ORDER BY qty DESC, amount DESC LIMIT 20", $p);
$topCustomers = DB::all("SELECT o.email, MAX(o.customer_id) customer_id, MAX(CONCAT(o.first_name, ' ', o.last_name)) name, MAX(o.company) company,
        COUNT(*) n, SUM(o.total) amount
    FROM orders o WHERE $periodSql AND " . ADMIN_REVENUE_SQL . " GROUP BY o.email ORDER BY amount DESC LIMIT 10", $p);
$byCategory = DB::all("SELECT COALESCE(c.name, 'Sans catégorie') name, SUM(oi.qty) qty, SUM(oi.line_total) amount
    FROM order_items oi JOIN orders o ON o.id = oi.order_id
    LEFT JOIN products pr ON pr.id = oi.product_id LEFT JOIN categories c ON c.id = pr.category_id
    WHERE $periodSql AND " . ADMIN_REVENUE_SQL . " GROUP BY c.id, c.name ORDER BY amount DESC LIMIT 15", $p);
$byMethod = DB::all("SELECT o.payment_method, COUNT(*) n, SUM(CASE WHEN " . ADMIN_REVENUE_SQL . " THEN o.total ELSE 0 END) amount
    FROM orders o WHERE $periodSql GROUP BY o.payment_method ORDER BY amount DESC", $p);

$pageTitle = 'Statistiques';
$pageActions = '<a class="btn" href="' . e(admin_url('stats.php', ['export' => 'orders', 'from' => $from, 'to' => $to])) . '">' . aicon('download') . ' Exporter les commandes (CSV)</a>';
require __DIR__ . '/_inc/layout_top.php';
?>
<div class="card">
  <form method="get" class="filters">
    <div class="field"><label for="preset">Période</label>
      <select id="preset" name="preset" data-autosubmit>
        <?php foreach ($presets as $k => [$label]): ?><option value="<?= e($k) ?>"<?= $preset === $k ? ' selected' : '' ?>><?= e($label) ?></option><?php endforeach; ?>
        <option value=""<?= !isset($presets[$preset]) ? ' selected' : '' ?>>Personnalisée</option>
      </select>
    </div>
    <div class="field"><label for="from">Du</label><input type="date" id="from" name="from" value="<?= e($from) ?>"></div>
    <div class="field"><label for="to">Au</label><input type="date" id="to" name="to" value="<?= e($to) ?>"></div>
    <button class="btn btn-primary" type="submit" onclick="this.form.preset.value=''">Appliquer</button>
  </form>
  <p class="muted small mt">Du <?= e(format_date($from)) ?> au <?= e(format_date($to)) ?>. Le chiffre d'affaires inclut les commandes payées ou livrées.</p>
</div>

<div class="kpis">
  <div class="kpi"><div class="kpi-label">Chiffre d'affaires</div><div class="kpi-value"><?= e(admin_money($kpi['ca'])) ?></div></div>
  <div class="kpi"><div class="kpi-label">Commandes (toutes)</div><div class="kpi-value"><?= (int)$kpi['n'] ?></div></div>
  <div class="kpi"><div class="kpi-label">Commandes encaissées</div><div class="kpi-value"><?= (int)$kpi['n_paid'] ?></div></div>
  <div class="kpi"><div class="kpi-label">Panier moyen</div><div class="kpi-value"><?= e(admin_money($avgBasket)) ?></div></div>
  <div class="kpi"><div class="kpi-label">Articles vendus</div><div class="kpi-value"><?= $itemsSold ?></div></div>
  <div class="kpi"><div class="kpi-label">Nouveaux clients</div><div class="kpi-value"><?= $newCustomers ?></div></div>
  <div class="kpi<?= $kpi['n_cancelled'] ? ' warn' : '' ?>"><div class="kpi-label">Annulées / remboursées</div><div class="kpi-value"><?= (int)$kpi['n_cancelled'] ?></div></div>
</div>

<div class="card">
  <div class="card-head"><h2>Chiffre d'affaires par mois (12 mois)</h2><span class="muted small">Total : <?= e(admin_money(array_sum($months))) ?></span></div>
  <?= svg_chart($monthChart, ['type' => 'bar', 'height' => 260, 'label' => "Chiffre d'affaires mensuel", 'format' => fn($v) => admin_money($v)]) ?>
</div>

<div class="grid grid-2">
  <div class="card">
    <h2>Commandes par statut</h2>
    <?= hbar_list(array_map(fn($r) => ['label' => order_statuses()[$r['status']] ?? $r['status'], 'value' => (int)$r['n'], 'display' => $r['n'] . ' · ' . admin_money($r['amount'])], $byStatus)) ?>
  </div>
  <div class="card">
    <h2>Moyens de paiement</h2>
    <?= hbar_list(array_map(fn($r) => ['label' => payment_method_label($r['payment_method']), 'value' => (float)$r['amount'] ?: (int)$r['n'] / 1000, 'display' => $r['n'] . ' commande(s) · ' . admin_money($r['amount'])], $byMethod)) ?>
  </div>
  <div class="card">
    <h2>Ventes par catégorie</h2>
    <?= hbar_list(array_map(fn($r) => ['label' => $r['name'], 'value' => (float)$r['amount'], 'display' => admin_money($r['amount']) . ' · ' . $r['qty'] . ' art.'], $byCategory)) ?>
  </div>
  <div class="card flush">
    <div class="card-head"><h2>Meilleurs clients</h2></div>
    <div class="table-wrap">
      <table class="table">
        <thead><tr><th>Client</th><th class="num">Commandes</th><th class="num">Montant</th></tr></thead>
        <tbody>
        <?php foreach ($topCustomers as $c): ?>
          <tr>
            <td><?= $c['customer_id'] && AdminAuth::can('customers') ? '<a href="' . e(admin_url('customer.php', ['id' => $c['customer_id']])) . '">' . e($c['name']) . '</a>' : e($c['name']) ?><?= $c['company'] ? ' <span class="muted small">(' . e($c['company']) . ')</span>' : '' ?><br><span class="muted small"><?= e($c['email']) ?></span></td>
            <td class="num"><?= (int)$c['n'] ?></td>
            <td class="num"><strong><?= e(admin_money($c['amount'])) ?></strong></td>
          </tr>
        <?php endforeach; ?>
        <?php if (!$topCustomers): ?><tr><td colspan="3" class="table-empty">Aucune donnée sur la période.</td></tr><?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<div class="card flush mt">
  <div class="card-head"><h2>Top 20 des produits vendus</h2></div>
  <div class="table-wrap">
    <table class="table">
      <thead><tr><th>#</th><th>SKU</th><th>Produit</th><th class="num">Quantité</th><th class="num">Chiffre d'affaires</th></tr></thead>
      <tbody>
      <?php foreach ($topProducts as $i => $r): ?>
        <tr>
          <td><?= $i + 1 ?></td>
          <td><code><?= e($r['sku']) ?></code></td>
          <td><?= $r['product_id'] && AdminAuth::can('products') ? '<a href="' . e(admin_url('product_edit.php', ['id' => $r['product_id']])) . '">' . e($r['name']) . '</a>' : e($r['name']) ?></td>
          <td class="num"><?= (int)$r['qty'] ?></td>
          <td class="num"><?= e(admin_money($r['amount'])) ?></td>
        </tr>
      <?php endforeach; ?>
      <?php if (!$topProducts): ?><tr><td colspan="5" class="table-empty">Aucune vente sur la période.</td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
<?php require __DIR__ . '/_inc/layout_bottom.php'; ?>
