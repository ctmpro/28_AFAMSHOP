<?php
/**
 * Espace client : tableau de bord.
 */
$u = Auth::require();
$orders = DB::all('SELECT * FROM orders WHERE customer_id = :c ORDER BY created_at DESC LIMIT 5', ['c' => $u['id']]);
$stats = DB::one("SELECT COUNT(*) n, COALESCE(SUM(CASE WHEN payment_status = 'paid' THEN total END), 0) spent FROM orders WHERE customer_id = :c", ['c' => $u['id']]);
$favCount = (int)DB::val('SELECT COUNT(*) FROM favorites WHERE customer_id = :c', ['c' => $u['id']]);
$reqCount = (int)DB::val('SELECT COUNT(*) FROM requests WHERE customer_id = :c OR email = :e', ['c' => $u['id'], 'e' => $u['email']]);
$active = 'dashboard';
$pageTitle = __('my_account');
$noIndex = true;
require INCLUDES_PATH . '/layout/header.php';
?>
<div class="container account-layout">
  <?php require INCLUDES_PATH . '/layout/account-nav.php'; ?>
  <section class="account-main">
    <h1><?= e(__('my_account')) ?></h1>
    <div class="stat-cards">
      <a class="stat-card" href="<?= e(url('compte/commandes')) ?>"><strong><?= (int)$stats['n'] ?></strong><span><?= e(__('orders')) ?></span></a>
      <a class="stat-card" href="<?= e(url('compte/favoris')) ?>"><strong><?= $favCount ?></strong><span><?= e(__('my_favorites')) ?></span></a>
      <a class="stat-card" href="<?= e(url('compte/demandes')) ?>"><strong><?= $reqCount ?></strong><span><?= e(__('my_quotes')) ?></span></a>
      <div class="stat-card"><strong><?= e(money($stats['spent'])) ?></strong><span>Total payé</span></div>
    </div>
    <div class="card">
      <h2><?= e(__('my_orders')) ?></h2>
      <?php if (!$orders): ?><p class="muted"><?= e(__('no_orders')) ?></p><?php else: require INCLUDES_PATH . '/layout/orders-table.php'; endif; ?>
    </div>
  </section>
</div>
<?php require INCLUDES_PATH . '/layout/footer.php'; ?>
