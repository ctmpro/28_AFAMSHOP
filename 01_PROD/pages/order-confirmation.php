<?php
/**
 * Confirmation / suivi de commande (accès par jeton secret ou compte client).
 */
$order = Orders::findByNumber((string)query_param('n'));
if (!$order || !Orders::canView($order, (string)query_param('t'))) {
    http_response_code(404);
    require PAGES_PATH . '/404.php';
    return;
}
$items = Orders::items((int)$order['id']);
$history = Orders::history((int)$order['id']);
$back = ['n' => $order['order_number'], 't' => $order['access_token']];
$isNew = ($_SESSION['last_order'] ?? null) === $order['order_number'];
$canRetry = in_array($order['payment_method'], ['stripe', 'paydunya'], true)
    && in_array($order['payment_status'], ['pending', 'failed', 'cancelled'], true)
    && !in_array($order['status'], ['cancelled', 'refunded'], true);

$pageTitle = __('order') . ' ' . $order['order_number'];
$noIndex = true;
$bodyClass = 'page-order';
require INCLUDES_PATH . '/layout/header.php';
$steps = ['received', 'paid', 'preparing', 'shipped', 'delivered'];
$currentStep = array_search($order['status'], $steps, true);
?>
<div class="container narrow">
  <?php if ($isNew): ?>
  <div class="success-hero">
    <?= icon('check', 'icon icon-xl') ?>
    <h1><?= e(__('order_thanks')) ?></h1>
    <p><?= e(__('order_received_text', ['n' => $order['order_number']])) ?></p>
  </div>
  <?php else: ?>
  <h1><?= e(__('order')) ?> <?= e($order['order_number']) ?></h1>
  <?php endif; ?>

  <?php if ($order['payment_status'] === 'paid'): ?>
    <div class="alert alert-success"><?= e(__('payment_success_text')) ?></div>
  <?php elseif ($canRetry): ?>
    <div class="alert alert-warning">
      <?= e($order['payment_status'] === 'pending' ? __('payment_pending_text') : __('payment_cancelled_text')) ?>
      <a class="btn btn-accent btn-sm" href="<?= e(url('commande/payer', $back)) ?>"><?= e(__('retry_payment')) ?></a>
    </div>
  <?php elseif ($order['payment_method'] === 'transfer' && $order['payment_status'] === 'pending'): ?>
    <div class="alert alert-info"><strong><?= e(__('pm_transfer')) ?></strong><br><?= nl2br(e(setting('transfer_instructions'))) ?></div>
  <?php endif; ?>

  <?php if ($currentStep !== false): ?>
  <ol class="order-steps">
    <?php foreach ($steps as $i => $s): ?>
      <li class="<?= $i <= $currentStep ? 'done' : '' ?>"><span><?= $i + 1 ?></span><?= e(order_statuses()[$s]) ?></li>
    <?php endforeach; ?>
  </ol>
  <?php else: ?>
    <p><?= Orders::statusBadge($order['status']) ?></p>
  <?php endif; ?>

  <div class="order-grid">
    <section class="card">
      <h2><?= e(__('details')) ?></h2>
      <dl class="kv">
        <div><dt><?= e(__('date')) ?></dt><dd><?= e(format_date($order['created_at'], true)) ?></dd></div>
        <div><dt><?= e(__('order_status')) ?></dt><dd><?= Orders::statusBadge($order['status']) ?></dd></div>
        <div><dt><?= e(__('payment_method')) ?></dt><dd><?= e(payment_method_label($order['payment_method'])) ?></dd></div>
        <div><dt><?= e(__('payment_status')) ?></dt><dd><?= Orders::statusBadge($order['payment_status']) ?></dd></div>
        <div><dt><?= e(__('delivery')) ?></dt><dd><?= e($order['delivery_method'] === 'pickup' ? __('pickup') . ' — ' . setting('pickup_address') : $order['ship_address'] . ', ' . $order['ship_city'] . ' (' . $order['zone_name'] . ')') ?></dd></div>
      </dl>
      <?php if ($order['invoice_number']): ?>
        <a class="btn btn-outline btn-sm" href="<?= e(url('commande/facture', $back)) ?>" target="_blank"><?= icon('file', 'icon icon-sm') ?> <?= e(__('download_invoice')) ?> <?= e($order['invoice_number']) ?></a>
      <?php endif; ?>
    </section>
    <section class="card">
      <h2><?= e(__('your_cart')) ?></h2>
      <ul class="mini-lines">
        <?php foreach ($items as $it): ?><li><span><?= (int)$it['qty'] ?> × <?= e($it['name']) ?></span><strong><?= e(money($it['line_total'], $order['currency'], false)) ?></strong></li><?php endforeach; ?>
      </ul>
      <dl class="totals">
        <div><dt><?= e(__('subtotal')) ?></dt><dd><?= e(money($order['subtotal'], $order['currency'], false)) ?></dd></div>
        <?php if ((float)$order['discount'] > 0): ?><div class="discount"><dt><?= e(__('discount')) ?></dt><dd>-<?= e(money($order['discount'], $order['currency'], false)) ?></dd></div><?php endif; ?>
        <div><dt><?= e(__('delivery')) ?></dt><dd><?= e((float)$order['delivery_fee'] > 0 ? money($order['delivery_fee'], $order['currency'], false) : __('free')) ?></dd></div>
        <div class="grand"><dt><?= e(__('total')) ?></dt><dd><?= e(money($order['total'], $order['currency'], false)) ?></dd></div>
      </dl>
    </section>
  </div>

  <?php if ($history): ?>
  <section class="card">
    <h2><?= e(__('order_status')) ?></h2>
    <ul class="timeline">
      <?php foreach (array_reverse($history) as $h): ?>
        <li><strong><?= e(order_statuses()[$h['status']] ?? $h['status']) ?></strong> <small><?= e(format_date($h['created_at'], true)) ?></small><?= $h['comment'] ? '<p>' . e($h['comment']) . '</p>' : '' ?></li>
      <?php endforeach; ?>
    </ul>
  </section>
  <?php endif; ?>
  <p><a class="btn btn-primary" href="<?= e(url()) ?>"><?= e(__('continue_shopping')) ?></a>
  <?php if (Auth::check()): ?> <a class="btn btn-outline" href="<?= e(url('compte/commandes')) ?>"><?= e(__('my_orders')) ?></a><?php endif; ?></p>
</div>
<?php require INCLUDES_PATH . '/layout/footer.php'; ?>
