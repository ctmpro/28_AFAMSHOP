<?php
/**
 * Stock : vue d'ensemble filtrable, ajustements rapides (Stock::adjust) et historique des mouvements.
 */
require dirname(__DIR__) . '/includes/bootstrap.php';
require __DIR__ . '/_inc/helpers.php';
$admin = AdminAuth::require('stock');

$reasons = [
    'restock' => 'Réapprovisionnement',
    'manual' => 'Ajustement manuel',
    'return' => 'Retour client',
    'loss' => 'Perte / casse',
    'order' => 'Commande',
    'cancel' => 'Annulation commande',
    'import' => 'Import',
];
$manualReasons = array_intersect_key($reasons, array_flip(['restock', 'manual', 'return', 'loss']));

// --- Ajustement rapide
if (is_post()) {
    require_csrf();
    $pid = (int)post('product_id');
    $delta = (int)post('delta');
    $reason = (string)post('reason');
    $note = mb_substr((string)post('note'), 0, 255) ?: null;
    $product = DB::one('SELECT id, name, stock FROM products WHERE id = :id', ['id' => $pid]);
    if (!$product) {
        flash('error', 'Produit introuvable.');
    } elseif ($delta === 0) {
        flash('warning', 'Indiquez une quantité positive (entrée) ou négative (sortie).');
    } elseif (!isset($manualReasons[$reason])) {
        flash('error', 'Motif invalide.');
    } else {
        $after = Stock::adjust($pid, $delta, $reason, null, $note);
        AdminAuth::log('stock_adjust', 'product', $pid, ['delta' => $delta, 'reason' => $reason, 'after' => $after, 'note' => $note]);
        flash('success', $product['name'] . ' : ' . ($delta > 0 ? '+' : '') . $delta . ' → stock ' . $after . '.');
    }
    back(admin_url('stock.php'));
}

$tab = query_param('tab') === 'movements' ? 'movements' : 'overview';
$threshold = (int)setting('low_stock_threshold', 5);
$counts = DB::one('SELECT
    SUM(stock > COALESCE(low_stock_threshold, :t1)) AS in_stock,
    SUM(stock > 0 AND stock <= COALESCE(low_stock_threshold, :t2)) AS low,
    SUM(stock <= 0 AND on_order = 0) AS out_stock,
    SUM(stock <= 0 AND on_order = 1) AS on_order,
    COUNT(*) AS total FROM products', ['t1' => $threshold, 't2' => $threshold]);

if ($tab === 'overview') {
    $status = (string)query_param('status');
    $q = (string)query_param('q');
    $where = ['1=1'];
    $params = [];
    if ($q !== '') {
        $where[] = '(p.name LIKE :q OR p.sku LIKE :q OR p.manufacturer_ref LIKE :q)';
        $params['q'] = '%' . $q . '%';
    }
    switch ($status) {
        case 'in': $where[] = 'p.stock > COALESCE(p.low_stock_threshold, :t)'; $params['t'] = $threshold; break;
        case 'low': $where[] = 'p.stock > 0 AND p.stock <= COALESCE(p.low_stock_threshold, :t)'; $params['t'] = $threshold; break;
        case 'out': $where[] = 'p.stock <= 0 AND p.on_order = 0'; break;
        case 'on_order': $where[] = 'p.stock <= 0 AND p.on_order = 1'; break;
    }
    $w = implode(' AND ', $where);
    $total = (int)DB::val('SELECT COUNT(*) FROM products p WHERE ' . $w, $params);
    $pg = paginate($total, 30, (int)query_param('page', 1));
    $order = admin_sort_sql(['name' => 'p.name', 'sku' => 'p.sku', 'stock' => 'p.stock'], 'stock', 'asc');
    $rows = DB::all('SELECT p.id, p.sku, p.name, p.stock, p.low_stock_threshold, p.on_order, p.published FROM products p WHERE ' . $w
        . ' ORDER BY ' . $order . ', p.name LIMIT ' . $pg['per_page'] . ' OFFSET ' . $pg['offset'], $params);
} else {
    $productId = (int)query_param('product', 0);
    $reasonFilter = (string)query_param('reason');
    $q = (string)query_param('q');
    $where = ['1=1'];
    $params = [];
    if ($productId) {
        $where[] = 'sm.product_id = :p';
        $params['p'] = $productId;
    }
    if ($q !== '') {
        $where[] = '(p.name LIKE :q OR p.sku LIKE :q)';
        $params['q'] = '%' . $q . '%';
    }
    if (isset($reasons[$reasonFilter])) {
        $where[] = 'sm.reason = :r';
        $params['r'] = $reasonFilter;
    }
    $w = implode(' AND ', $where);
    $total = (int)DB::val('SELECT COUNT(*) FROM stock_movements sm JOIN products p ON p.id = sm.product_id WHERE ' . $w, $params);
    $pg = paginate($total, 50, (int)query_param('page', 1));
    $rows = DB::all('SELECT sm.*, p.name, p.sku, a.name AS admin_name, o.order_number FROM stock_movements sm
        JOIN products p ON p.id = sm.product_id LEFT JOIN admins a ON a.id = sm.admin_id LEFT JOIN orders o ON o.id = sm.order_id
        WHERE ' . $w . ' ORDER BY sm.id DESC LIMIT ' . $pg['per_page'] . ' OFFSET ' . $pg['offset'], $params);
    $productName = $productId ? DB::val('SELECT name FROM products WHERE id = :id', ['id' => $productId]) : null;
}

$pageTitle = 'Stock';
require __DIR__ . '/_inc/layout_top.php';
?>
<div class="kpis">
  <a class="kpi" href="<?= e(admin_url('stock.php', ['status' => 'in'])) ?>"><div class="kpi-label">En stock</div><div class="kpi-value"><?= (int)$counts['in_stock'] ?></div></a>
  <a class="kpi<?= $counts['low'] ? ' warn' : '' ?>" href="<?= e(admin_url('stock.php', ['status' => 'low'])) ?>"><div class="kpi-label">Stock faible</div><div class="kpi-value"><?= (int)$counts['low'] ?></div></a>
  <a class="kpi<?= $counts['out_stock'] ? ' danger' : '' ?>" href="<?= e(admin_url('stock.php', ['status' => 'out'])) ?>"><div class="kpi-label">Rupture</div><div class="kpi-value"><?= (int)$counts['out_stock'] ?></div></a>
  <a class="kpi" href="<?= e(admin_url('stock.php', ['status' => 'on_order'])) ?>"><div class="kpi-label">Sur commande</div><div class="kpi-value"><?= (int)$counts['on_order'] ?></div></a>
</div>

<div class="tabs">
  <a class="tab<?= $tab === 'overview' ? ' active' : '' ?>" href="<?= e(admin_url('stock.php')) ?>">Vue d'ensemble</a>
  <a class="tab<?= $tab === 'movements' ? ' active' : '' ?>" href="<?= e(admin_url('stock.php', ['tab' => 'movements'])) ?>">Historique des mouvements</a>
</div>

<?php if ($tab === 'overview'): ?>
  <div class="card">
    <form method="get" class="filters">
      <div class="field wide"><label for="q">Recherche</label><input type="search" id="q" name="q" value="<?= e($q) ?>" placeholder="Nom, SKU, référence"></div>
      <div class="field"><label for="status">Statut</label><select id="status" name="status"><?= select_options(['in' => 'En stock', 'low' => 'Stock faible', 'out' => 'Rupture', 'on_order' => 'Sur commande'], $status, 'Tous') ?></select></div>
      <button class="btn btn-primary" type="submit"><?= aicon('search') ?> Filtrer</button>
    </form>
  </div>
  <div class="card flush">
    <div class="card-head"><h2><?= $total ?> produit(s)</h2><span class="muted small">Seuil global de stock faible : <?= $threshold ?></span></div>
    <div class="table-wrap">
      <table class="table">
        <thead><tr><th><?= sort_link('sku', 'SKU', 'stock', 'asc') ?></th><th><?= sort_link('name', 'Produit', 'stock', 'asc') ?></th><th class="num"><?= sort_link('stock', 'Stock', 'stock', 'asc') ?></th><th class="num">Seuil</th><th>Statut</th><th>Ajustement rapide</th></tr></thead>
        <tbody>
        <?php foreach ($rows as $p): ?>
          <tr>
            <td><code><?= e($p['sku']) ?></code></td>
            <td><a href="<?= e(admin_url('product_edit.php', ['id' => $p['id']])) ?>"><?= e($p['name']) ?></a><?= $p['published'] ? '' : ' ' . badge('Brouillon') ?></td>
            <td class="num"><strong><?= (int)$p['stock'] ?></strong></td>
            <td class="num"><?= e((string)Catalog::lowThreshold($p)) ?></td>
            <td><?= stock_badge(Catalog::stockStatus($p)) ?></td>
            <td>
              <form method="post" class="btn-group">
                <?= csrf_field() ?>
                <input type="hidden" name="product_id" value="<?= (int)$p['id'] ?>">
                <input type="number" name="delta" step="1" placeholder="+/-" style="width:80px" required aria-label="Quantité (+ ou -)">
                <select name="reason" style="width:auto" aria-label="Motif"><?= select_options($manualReasons, 'restock') ?></select>
                <input type="text" name="note" placeholder="Note" style="width:140px" maxlength="255" aria-label="Note">
                <button class="btn btn-sm btn-primary" type="submit">OK</button>
                <a class="btn btn-sm" href="<?= e(admin_url('stock.php', ['tab' => 'movements', 'product' => $p['id']])) ?>" title="Historique"><?= aicon('list') ?></a>
              </form>
            </td>
          </tr>
        <?php endforeach; ?>
        <?php if (!$rows): ?><tr><td colspan="6" class="table-empty">Aucun produit.</td></tr><?php endif; ?>
        </tbody>
      </table>
    </div>
    <?= pagination_links($pg) ?>
  </div>
<?php else: ?>
  <div class="card">
    <form method="get" class="filters">
      <input type="hidden" name="tab" value="movements">
      <?php if ($productId): ?><input type="hidden" name="product" value="<?= $productId ?>"><?php endif; ?>
      <div class="field wide"><label for="q">Produit</label><input type="search" id="q" name="q" value="<?= e($q) ?>" placeholder="Nom ou SKU"></div>
      <div class="field"><label for="reason">Motif</label><select id="reason" name="reason"><?= select_options($reasons, $reasonFilter, 'Tous') ?></select></div>
      <button class="btn btn-primary" type="submit"><?= aicon('search') ?> Filtrer</button>
      <?php if ($productId): ?><a class="btn" href="<?= e(admin_url('stock.php', ['tab' => 'movements'])) ?>">Tous les produits</a><?php endif; ?>
    </form>
    <?php if ($productId && $productName): ?><p class="mt mb-0">Produit filtré : <strong><?= e($productName) ?></strong></p><?php endif; ?>
  </div>
  <div class="card flush">
    <div class="card-head"><h2><?= $total ?> mouvement(s)</h2></div>
    <div class="table-wrap">
      <table class="table">
        <thead><tr><th>Date</th><th>Produit</th><th class="num">Variation</th><th class="num">Stock après</th><th>Motif</th><th>Commande</th><th>Par</th><th>Note</th></tr></thead>
        <tbody>
        <?php foreach ($rows as $m): ?>
          <tr>
            <td class="nowrap"><?= e(format_date($m['created_at'], true)) ?></td>
            <td><a href="<?= e(admin_url('stock.php', ['tab' => 'movements', 'product' => $m['product_id']])) ?>"><?= e($m['name']) ?></a><br><code class="muted small"><?= e($m['sku']) ?></code></td>
            <td class="num"><?= $m['qty_change'] > 0 ? badge('+' . $m['qty_change'], 'success') : badge((string)$m['qty_change'], 'danger') ?></td>
            <td class="num"><?= (int)$m['stock_after'] ?></td>
            <td><?= e($reasons[$m['reason']] ?? $m['reason']) ?></td>
            <td><?= $m['order_number'] ? '<a href="' . e(admin_url('order.php', ['id' => $m['order_id']])) . '">' . e($m['order_number']) . '</a>' : '' ?></td>
            <td><?= e($m['admin_name'] ?? ($m['order_id'] ? 'Système' : '—')) ?></td>
            <td><?= e($m['note'] ?? '') ?></td>
          </tr>
        <?php endforeach; ?>
        <?php if (!$rows): ?><tr><td colspan="8" class="table-empty">Aucun mouvement.</td></tr><?php endif; ?>
        </tbody>
      </table>
    </div>
    <?= pagination_links($pg) ?>
  </div>
<?php endif; ?>
<?php require __DIR__ . '/_inc/layout_bottom.php'; ?>
