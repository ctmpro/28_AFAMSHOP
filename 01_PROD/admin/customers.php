<?php
/**
 * Clients : liste avec nombre de commandes, montant dépensé, inscription, statut.
 */
require dirname(__DIR__) . '/includes/bootstrap.php';
require __DIR__ . '/_inc/helpers.php';
$admin = AdminAuth::require('customers');

$q = (string)query_param('q');
$status = (string)query_param('status');
$type = (string)query_param('type');
$accountTypes = ['individual' => 'Particulier', 'business' => 'Entreprise', 'administration' => 'Administration'];

$where = ['1=1'];
$params = [];
if ($q !== '') {
    $where[] = "(c.email LIKE :q OR c.phone LIKE :q OR c.company LIKE :q OR CONCAT(c.first_name, ' ', c.last_name) LIKE :q OR CONCAT(c.last_name, ' ', c.first_name) LIKE :q)";
    $params['q'] = '%' . $q . '%';
}
if (in_array($status, ['active', 'blocked'], true)) {
    $where[] = 'c.status = :st';
    $params['st'] = $status;
}
if (isset($accountTypes[$type])) {
    $where[] = 'c.account_type = :ty';
    $params['ty'] = $type;
}
$w = implode(' AND ', $where);
$total = (int)DB::val("SELECT COUNT(*) FROM customers c WHERE $w", $params);
$pg = paginate($total, 30, (int)query_param('page', 1));
$order = admin_sort_sql(['name' => 'c.last_name', 'date' => 'c.created_at', 'orders' => 'n_orders', 'spent' => 'spent', 'login' => 'c.last_login'], 'date');
$rows = DB::all("SELECT c.*,
        (SELECT COUNT(*) FROM orders o WHERE o.customer_id = c.id) AS n_orders,
        (SELECT COALESCE(SUM(o.total),0) FROM orders o WHERE o.customer_id = c.id AND " . ADMIN_REVENUE_SQL . ") AS spent
    FROM customers c WHERE $w ORDER BY $order, c.id DESC LIMIT {$pg['per_page']} OFFSET {$pg['offset']}", $params);

$pageTitle = 'Clients';
require __DIR__ . '/_inc/layout_top.php';
?>
<div class="card">
  <form method="get" class="filters">
    <div class="field wide"><label for="q">Recherche</label><input type="search" id="q" name="q" value="<?= e($q) ?>" placeholder="Nom, email, téléphone, société"></div>
    <div class="field"><label for="type">Type de compte</label><select id="type" name="type"><?= select_options($accountTypes, $type, 'Tous') ?></select></div>
    <div class="field"><label for="status">Statut</label><select id="status" name="status"><?= select_options(['active' => 'Actif', 'blocked' => 'Bloqué'], $status, 'Tous') ?></select></div>
    <button class="btn btn-primary" type="submit"><?= aicon('search') ?> Filtrer</button>
  </form>
</div>
<div class="card flush">
  <div class="card-head"><h2><?= $total ?> client(s)</h2></div>
  <div class="table-wrap">
    <table class="table">
      <thead><tr>
        <th><?= sort_link('name', 'Client') ?></th><th>Contact</th><th>Type</th>
        <th class="num"><?= sort_link('orders', 'Commandes') ?></th><th class="num"><?= sort_link('spent', 'Montant dépensé') ?></th>
        <th><?= sort_link('date', 'Inscription') ?></th><th><?= sort_link('login', 'Dernière connexion') ?></th><th>Statut</th>
      </tr></thead>
      <tbody>
      <?php foreach ($rows as $c): ?>
        <tr>
          <td><a href="<?= e(admin_url('customer.php', ['id' => $c['id']])) ?>"><strong><?= e($c['first_name'] . ' ' . $c['last_name']) ?></strong></a><?= $c['company'] ? '<br><span class="muted small">' . e($c['company']) . '</span>' : '' ?></td>
          <td><?= e($c['email']) ?><br><span class="muted small"><?= e($c['phone'] ?? '') ?></span></td>
          <td><?= e($accountTypes[$c['account_type']] ?? $c['account_type']) ?></td>
          <td class="num"><?= (int)$c['n_orders'] ?></td>
          <td class="num"><?= e(admin_money($c['spent'])) ?></td>
          <td class="nowrap"><?= e(format_date($c['created_at'])) ?></td>
          <td class="nowrap"><?= e(format_date($c['last_login'], true)) ?: '<span class="muted">—</span>' ?></td>
          <td><?= $c['status'] === 'active' ? badge('Actif', 'success') : badge('Bloqué', 'danger') ?></td>
        </tr>
      <?php endforeach; ?>
      <?php if (!$rows): ?><tr><td colspan="8" class="table-empty">Aucun client.</td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>
  <?= pagination_links($pg) ?>
</div>
<?php require __DIR__ . '/_inc/layout_bottom.php'; ?>
