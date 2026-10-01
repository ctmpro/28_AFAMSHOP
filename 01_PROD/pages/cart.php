<?php
/**
 * Panier.
 */
$t = Cart::totals();
if ($t['coupon_error'] && Cart::couponCode()) {
    flash('warning', $t['coupon_error']);
    Cart::setCoupon(null);
    redirect('panier');
}
$pageTitle = __('your_cart');
$noIndex = true;
$bodyClass = 'page-cart';
require INCLUDES_PATH . '/layout/header.php';
$freeThreshold = (float)setting('free_shipping_threshold', 0);
?>
<div class="container">
  <h1><?= e(__('your_cart')) ?></h1>
  <?php if (!$t['items']): ?>
    <div class="empty-state">
      <?= icon('cart', 'icon icon-xl') ?>
      <p><?= e(__('cart_empty')) ?></p>
      <a class="btn btn-primary" href="<?= e(url()) ?>"><?= e(__('continue_shopping')) ?></a>
    </div>
  <?php else: ?>
  <div class="cart-layout">
    <div class="cart-lines card">
      <?php foreach ($t['items'] as $it): $p = $it['product']; ?>
      <div class="cart-line" data-line="<?= (int)$p['id'] ?>">
        <a class="cart-thumb" href="<?= e(url('produit/' . $p['slug'])) ?>"><img src="<?= e(media_url($p['main_image'])) ?>" alt=""></a>
        <div class="cart-desc">
          <a href="<?= e(url('produit/' . $p['slug'])) ?>"><strong><?= e($p['name']) ?></strong></a>
          <small><?= e($p['sku']) ?><?= $p['manufacturer_ref'] ? ' · ' . e($p['manufacturer_ref']) : '' ?></small>
          <span class="stock stock-<?= e($p['stock_status']) ?>"><?= e(Catalog::stockLabel($p['stock_status'])) ?></span>
          <?php if (!$it['available']): ?><span class="alert-inline"><?= e(__('qty_limited', ['n' => max(0, (int)$p['stock'])])) ?></span><?php endif; ?>
        </div>
        <div class="cart-unit"><?= e(money($it['unit_price'])) ?></div>
        <form method="post" action="<?= e(url('api/cart.php')) ?>" class="cart-qty" data-cart-update>
          <?= csrf_field() ?>
          <input type="hidden" name="action" value="update">
          <input type="hidden" name="product_id" value="<?= (int)$p['id'] ?>">
          <div class="qty-input">
            <button type="button" data-qty="-1" aria-label="-">−</button>
            <input type="number" name="qty" value="<?= (int)$it['qty'] ?>" min="0" max="<?= Cart::MAX_QTY ?>" aria-label="<?= e(__('quantity')) ?>">
            <button type="button" data-qty="1" aria-label="+">+</button>
          </div>
          <noscript><button class="btn btn-link btn-sm"><?= e(__('update')) ?></button></noscript>
        </form>
        <strong class="cart-total" data-line-total><?= e(money($it['line_total'])) ?></strong>
        <form method="post" action="<?= e(url('api/cart.php')) ?>" data-cart-remove>
          <?= csrf_field() ?>
          <input type="hidden" name="action" value="remove">
          <input type="hidden" name="product_id" value="<?= (int)$p['id'] ?>">
          <button class="icon-btn" aria-label="<?= e(__('remove')) ?>"><?= icon('close', 'icon icon-sm') ?></button>
        </form>
      </div>
      <?php endforeach; ?>
      <a class="btn btn-link" href="<?= e(url()) ?>">← <?= e(__('continue_shopping')) ?></a>
    </div>

    <aside class="cart-summary card">
      <form method="post" action="<?= e(url('api/cart.php')) ?>" class="coupon-form">
        <?= csrf_field() ?>
        <?php if ($t['coupon']): ?>
          <input type="hidden" name="action" value="remove_coupon">
          <p><?= e(__('coupon')) ?> : <strong><?= e($t['coupon']['code']) ?></strong> <button class="btn btn-link btn-sm"><?= e(__('remove')) ?></button></p>
        <?php else: ?>
          <input type="hidden" name="action" value="coupon">
          <label for="code" class="sr-only"><?= e(__('coupon')) ?></label>
          <input type="text" id="code" name="code" placeholder="<?= e(__('coupon')) ?>" maxlength="50">
          <button class="btn btn-outline"><?= e(__('apply')) ?></button>
        <?php endif; ?>
      </form>
      <?= currency_note() ?>
      <dl class="totals">
        <div><dt><?= e(__('subtotal')) ?></dt><dd data-total="subtotal"><?= e(money($t['subtotal'])) ?></dd></div>
        <?php if ($t['discount'] > 0): ?><div class="discount"><dt><?= e(__('discount')) ?></dt><dd data-total="discount">-<?= e(money($t['discount'])) ?></dd></div><?php endif; ?>
        <div><dt><?= e(__('delivery')) ?></dt><dd><?= e(__('calculated_next_step')) ?></dd></div>
        <div class="grand"><dt><?= e(__('total')) ?></dt><dd data-total="total"><?= e(money($t['total'])) ?></dd></div>
      </dl>
      <?php if ($freeThreshold > 0): ?>
        <p class="muted small"><?= icon('truck', 'icon icon-sm') ?> <?= e(__('free_shipping_from', ['amount' => money($freeThreshold)])) ?></p>
      <?php endif; ?>
      <a class="btn btn-accent btn-lg btn-block" href="<?= e(url('commande')) ?>"><?= e(__('checkout')) ?></a>
      <p class="secure-note"><?= icon('shield', 'icon icon-sm') ?> <?= e(__('secure_payment')) ?></p>
    </aside>
  </div>
  <?php endif; ?>
</div>
<?php require INCLUDES_PATH . '/layout/footer.php'; ?>
