<?php
/**
 * Espace client : historique des commandes et factures.
 */
$u = Auth::require();
$total = (int)DB::val('SELECT COUNT(*) FROM orders WHERE customer_id = :c', ['c' => $u['id']]);
$pg = paginate($total, 15, int_param('page', 1));
$orders = DB::all('SELECT * FROM orders WHERE customer_id = :c ORDER BY created_at DESC LIMIT ' . $pg['per_page'] . ' OFFSET ' . $pg['offset'], ['c' => $u['id']]);
$active = 'orders';
$pageTitle = __('my_orders');
$noIndex = true;
require INCLUDES_PATH . '/layout/header.php';
?>
<div class="container account-layout">
  <?php require INCLUDES_PATH . '/layout/account-nav.php'; ?>
  <section class="account-main">
    <h1><?= e(__('my_orders')) ?></h1>
    <div class="card">
      <?php if (!$orders): ?><p class="muted"><?= e(__('no_orders')) ?></p><?php else: require INCLUDES_PATH . '/layout/orders-table.php'; endif; ?>
      <?= pagination_links($pg) ?>
    </div>
  </section>
</div>
<?php require INCLUDES_PATH . '/layout/footer.php'; ?>
