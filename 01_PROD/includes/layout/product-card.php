<?php
/** Carte produit — attend $p (produit décoré par Catalog::decorate). */
$fav = $favoriteIds ?? [];
?>
<article class="product-card">
  <a class="pc-image" href="<?= e(url('produit/' . $p['slug'])) ?>">
    <img src="<?= e(media_url($p['main_image'])) ?>" alt="<?= e($p['name']) ?>" loading="lazy">
    <span class="pc-badges">
      <?php if ($p['discount_percent']): ?><span class="badge badge-accent">-<?= (int)$p['discount_percent'] ?>%</span><?php endif; ?>
      <?php if (strtotime($p['created_at']) > time() - 30 * 86400): ?><span class="badge badge-primary"><?= e(__('new')) ?></span><?php endif; ?>
    </span>
  </a>
  <button class="pc-fav <?= in_array((int)$p['id'], $fav, true) ? 'active' : '' ?>" data-favorite="<?= (int)$p['id'] ?>" aria-label="<?= e(__('add_to_favorites')) ?>"><?= icon('heart', 'icon icon-sm') ?></button>
  <div class="pc-body">
    <?php if ($p['brand_name']): ?><span class="pc-brand"><?= e($p['brand_name']) ?></span><?php endif; ?>
    <h3 class="pc-title"><a href="<?= e(url('produit/' . $p['slug'])) ?>"><?= e($p['name']) ?></a></h3>
    <span class="pc-ref"><?= e(__('manufacturer_ref')) ?> : <?= e($p['manufacturer_ref'] ?: '—') ?> · <?= e($p['sku']) ?></span>
    <span class="stock stock-<?= e($p['stock_status']) ?>"><?= e(Catalog::stockLabel($p['stock_status'])) ?></span>
    <div class="pc-footer">
      <div class="price">
        <?php if ($p['old_price']): ?><del><?= e(money($p['old_price'])) ?></del><?php endif; ?>
        <strong><?= e(money($p['final_price'])) ?></strong>
      </div>
      <?php if (Catalog::canBuy($p)): ?>
        <button class="btn btn-accent btn-icon" data-add-to-cart="<?= (int)$p['id'] ?>" aria-label="<?= e(__('add_to_cart')) ?>"><?= icon('cart', 'icon icon-sm') ?></button>
      <?php else: ?>
        <a class="btn btn-outline btn-sm" href="<?= e(url('devis', ['produit' => $p['id']])) ?>"><?= e(__('request_quote')) ?></a>
      <?php endif; ?>
    </div>
    <label class="pc-compare"><input type="checkbox" data-compare="<?= (int)$p['id'] ?>" <?= in_array((int)$p['id'], $_SESSION['compare'] ?? [], true) ? 'checked' : '' ?>> <?= e(__('compare')) ?></label>
  </div>
</article>
