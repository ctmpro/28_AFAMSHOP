<?php
/**
 * Fiche produit.
 */
$p = Catalog::product($slug);
if (!$p) {
    http_response_code(404);
    require PAGES_PATH . '/404.php';
    return;
}

// Dépôt d'un avis client (validation par l'administrateur)
if (is_post() && post('action') === 'review' && setting_bool('reviews_enabled', true)) {
    require_csrf();
    $rating = max(1, min(5, int_param('rating', 5)));
    $name = Auth::user() ? Auth::user()['first_name'] . ' ' . mb_substr(Auth::user()['last_name'], 0, 1) . '.' : mb_substr(post('name'), 0, 120);
    $body = mb_substr(post('body'), 0, 3000);
    if ($name === '' || mb_strlen($body) < 5) {
        flash('error', __('field_required', ['field' => __('message')]));
    } elseif (!Recaptcha::verify()) {
        flash('error', __('captcha_error'));
    } else {
        DB::insert('reviews', [
            'product_id' => $p['id'], 'customer_id' => Auth::id(), 'name' => $name,
            'rating' => $rating, 'title' => mb_substr(post('title'), 0, 190) ?: null, 'body' => $body,
        ]);
        Mailer::notifyAdmin('new_review', ['product' => $p['name'], 'rating' => $rating . '/5', 'name' => $name, 'link' => admin_url('reviews.php')]);
        flash('success', __('review_sent'));
    }
    redirect('produit/' . $p['slug'] . '#avis');
}

DB::exec('UPDATE products SET views = views + 1 WHERE id = :id', ['id' => $p['id']]);
$images = Catalog::images((int)$p['id']);
$specs = Catalog::parseSpecs($p['specs']);
$models = Catalog::compatibleModels((int)$p['id']);
$reviews = setting_bool('reviews_enabled', true) ? Catalog::reviews((int)$p['id']) : [];
$rating = Catalog::rating((int)$p['id']);
$related = Catalog::related($p, 8);
$isFav = Auth::id() && DB::val('SELECT 1 FROM favorites WHERE customer_id = :c AND product_id = :p', ['c' => Auth::id(), 'p' => $p['id']]);
$favoriteIds = Auth::id() ? array_map('intval', DB::col('SELECT product_id FROM favorites WHERE customer_id = :c', ['c' => Auth::id()])) : [];
$canBuy = Catalog::canBuy($p);
$maxQty = ($p['stock'] > 0 && !$p['on_order'] && !setting_bool('allow_backorders')) ? (int)$p['stock'] : Cart::MAX_QTY;

$pageTitle = $p['meta_title'] ?: $p['name'];
$metaDescription = $p['meta_description'] ?: truncate($p['short_description'] ?: $p['description'], 160);
$ogImage = $images ? media_url($images[0]['path']) : null;
$bodyClass = 'page-product';
require INCLUDES_PATH . '/layout/header.php';
$crumbs = $p['category_id'] ? Catalog::breadcrumb((int)$p['category_id']) : [];
?>
<div class="container">
  <nav class="breadcrumb" aria-label="Fil d'Ariane">
    <a href="<?= e(url()) ?>"><?= e(__('home')) ?></a>
    <?php foreach ($crumbs as $c): ?><span>›</span><a href="<?= e(url('categorie/' . $c['slug'])) ?>"><?= e($c['name']) ?></a><?php endforeach; ?>
    <span>›</span><span aria-current="page"><?= e(truncate($p['name'], 60)) ?></span>
  </nav>

  <div class="product-layout">
    <div class="gallery" data-gallery>
      <div class="gallery-main" data-zoom>
        <img src="<?= e(media_url($images[0]['path'] ?? null)) ?>" alt="<?= e($images[0]['alt'] ?? $p['name']) ?>" data-gallery-main>
        <?php if ($p['discount_percent']): ?><span class="badge badge-accent gallery-badge">-<?= (int)$p['discount_percent'] ?>%</span><?php endif; ?>
      </div>
      <?php if (count($images) > 1): ?>
      <div class="gallery-thumbs">
        <?php foreach ($images as $i => $img): ?>
        <button class="thumb <?= $i === 0 ? 'active' : '' ?>" data-gallery-thumb="<?= e(media_url($img['path'])) ?>" aria-label="Image <?= $i + 1 ?>">
          <img src="<?= e(media_url($img['path'])) ?>" alt="" loading="lazy">
        </button>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>
    </div>

    <div class="product-info">
      <?php if ($p['brand_name']): ?><a class="product-brand" href="<?= e(url('marque/' . $p['brand_slug'])) ?>"><?= e($p['brand_name']) ?></a><?php endif; ?>
      <h1><?= e($p['name']) ?></h1>
      <?php if ($rating['count']): ?>
        <a class="rating-line" href="#avis"><span class="stars" style="--rating:<?= $rating['avg'] ?>"></span> <?= e(number_format($rating['avg'], 1, ',', '')) ?>/5 (<?= $rating['count'] ?>)</a>
      <?php endif; ?>
      <dl class="product-refs">
        <div><dt><?= e(__('manufacturer_ref')) ?></dt><dd><?= e($p['manufacturer_ref'] ?: '—') ?></dd></div>
        <div><dt><?= e(__('sku')) ?></dt><dd><?= e($p['sku']) ?></dd></div>
      </dl>
      <?php if ($p['short_description']): ?><p class="product-short"><?= nl2br(e($p['short_description'])) ?></p><?php endif; ?>

      <div class="buy-box card">
        <div class="price price-lg">
          <?php if ($p['old_price']): ?><del><?= e(money($p['old_price'])) ?></del><?php endif; ?>
          <strong><?= e(money($p['final_price'])) ?></strong>
          <small><?= setting_bool('prices_include_tax', true) ? 'TTC' : 'HT' ?></small>
        </div>
        <?php if ($p['old_price'] && $p['promo_end'] && (float)$p['promo_price'] === (float)$p['final_price']): ?>
          <p class="promo-end"><?= e(__('promo')) ?> jusqu'au <?= e(format_date($p['promo_end'])) ?></p>
        <?php endif; ?>
        <p class="stock stock-<?= e($p['stock_status']) ?>">
          <?= e(Catalog::stockLabel($p['stock_status'])) ?>
          <?php if (setting_bool('show_stock_qty', true) && $p['stock'] > 0): ?> — <?= e(__('in_stock_qty', ['n' => (int)$p['stock']])) ?><?php endif; ?>
        </p>
        <ul class="buy-meta">
          <li><?= icon('truck', 'icon icon-sm') ?> <?= e(__('delivery_delay')) ?> : <?= e($p['delivery_delay'] ?: setting('default_delivery_delay')) ?></li>
          <li><?= icon('shield', 'icon icon-sm') ?> <?= e(__('warranty')) ?> : <?= e($p['warranty'] ?: setting('default_warranty')) ?></li>
        </ul>
        <?php if ($canBuy): ?>
        <form class="buy-form" method="post" action="<?= e(url('api/cart.php')) ?>" data-buy-form>
          <?= csrf_field() ?>
          <input type="hidden" name="action" value="add">
          <input type="hidden" name="product_id" value="<?= (int)$p['id'] ?>">
          <div class="qty-input">
            <button type="button" data-qty="-1" aria-label="-">−</button>
            <input type="number" name="qty" value="1" min="1" max="<?= $maxQty ?>" aria-label="<?= e(__('quantity')) ?>">
            <button type="button" data-qty="1" aria-label="+">+</button>
          </div>
          <button class="btn btn-accent btn-lg" type="submit" name="go" value="cart"><?= icon('cart', 'icon icon-sm') ?> <?= e(__('add_to_cart')) ?></button>
          <button class="btn btn-primary btn-lg" type="submit" name="go" value="checkout" data-buy-now><?= e(__('buy_now')) ?></button>
        </form>
        <?php endif; ?>
        <div class="buy-secondary">
          <a class="btn btn-outline" href="<?= e(url('devis', ['produit' => $p['id']])) ?>"><?= icon('file', 'icon icon-sm') ?> <?= e(__('request_quote')) ?></a>
          <button class="btn btn-outline <?= $isFav ? 'active' : '' ?>" data-favorite="<?= (int)$p['id'] ?>"><?= icon('heart', 'icon icon-sm') ?> <span><?= e($isFav ? __('remove_from_favorites') : __('add_to_favorites')) ?></span></button>
          <label class="btn btn-outline"><input type="checkbox" data-compare="<?= (int)$p['id'] ?>" <?= in_array((int)$p['id'], $_SESSION['compare'] ?? [], true) ? 'checked' : '' ?>> <?= e(__('compare')) ?></label>
        </div>
        <?php if (setting('whatsapp_number')): ?>
          <a class="whatsapp-line" href="<?= e(whatsapp_link('Bonjour, je suis intéressé(e) par : ' . $p['name'] . ' (' . $p['sku'] . ') ' . url('produit/' . $p['slug']))) ?>" target="_blank" rel="noopener"><?= icon('whatsapp', 'icon icon-sm') ?> Une question ? Écrivez-nous sur WhatsApp</a>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <div class="tabs" data-tabs>
    <div class="tab-list" role="tablist">
      <button role="tab" class="active" data-tab="desc"><?= e(__('description')) ?></button>
      <?php if ($specs || $p['weight'] || $p['dimensions']): ?><button role="tab" data-tab="specs"><?= e(__('specifications')) ?></button><?php endif; ?>
      <?php if ($models): ?><button role="tab" data-tab="compat"><?= e(__('compatibility')) ?> (<?= count($models) ?>)</button><?php endif; ?>
      <?php if (setting_bool('reviews_enabled', true)): ?><button role="tab" data-tab="reviews" id="avis"><?= e(__('reviews')) ?> (<?= $rating['count'] ?>)</button><?php endif; ?>
    </div>
    <section class="tab-panel active rich-text" data-panel="desc">
      <?= $p['description'] ? clean_html($p['description']) : '<p>' . nl2br(e($p['short_description'])) . '</p>' ?>
    </section>
    <?php if ($specs || $p['weight'] || $p['dimensions']): ?>
    <section class="tab-panel" data-panel="specs">
      <table class="spec-table">
        <?php foreach ($specs as $k => $v): ?><tr><th><?= e($k) ?></th><td><?= e($v) ?></td></tr><?php endforeach; ?>
        <?php if ($p['color']): ?><tr><th><?= e(__('color')) ?></th><td><?= e($p['color']) ?></td></tr><?php endif; ?>
        <?php if ($p['weight']): ?><tr><th><?= e(__('weight')) ?></th><td><?= e($p['weight']) ?></td></tr><?php endif; ?>
        <?php if ($p['dimensions']): ?><tr><th><?= e(__('dimensions')) ?></th><td><?= e($p['dimensions']) ?></td></tr><?php endif; ?>
        <tr><th><?= e(__('warranty')) ?></th><td><?= e($p['warranty'] ?: setting('default_warranty')) ?></td></tr>
      </table>
    </section>
    <?php endif; ?>
    <?php if ($models): ?>
    <section class="tab-panel" data-panel="compat">
      <p><?= e(__('compatible_with')) ?> :</p>
      <ul class="compat-list">
        <?php foreach ($models as $m): ?><li><a href="<?= e(url('recherche-imprimante', ['modele' => $m['slug']])) ?>"><?= e($m['brand_name'] . ' ' . $m['name']) ?></a></li><?php endforeach; ?>
      </ul>
    </section>
    <?php endif; ?>
    <?php if (setting_bool('reviews_enabled', true)): ?>
    <section class="tab-panel" data-panel="reviews">
      <div class="reviews-layout">
        <div class="reviews-list">
          <?php if (!$reviews): ?><p class="muted"><?= e(__('no_reviews')) ?></p><?php endif; ?>
          <?php foreach ($reviews as $r): ?>
          <article class="review">
            <header><span class="stars" style="--rating:<?= (int)$r['rating'] ?>"></span> <strong><?= e($r['title']) ?></strong></header>
            <p><?= nl2br(e($r['body'])) ?></p>
            <footer><?= e($r['name']) ?> · <?= e(format_date($r['created_at'])) ?></footer>
          </article>
          <?php endforeach; ?>
        </div>
        <form method="post" class="review-form card">
          <h3><?= e(__('write_review')) ?></h3>
          <?= csrf_field() ?>
          <input type="hidden" name="action" value="review">
          <?php if (!Auth::check()): ?>
          <label><?= e(__('full_name')) ?> *<input type="text" name="name" maxlength="120" required></label>
          <?php endif; ?>
          <label><?= e(__('rating')) ?> *
            <select name="rating"><?php for ($i = 5; $i >= 1; $i--): ?><option value="<?= $i ?>"><?= str_repeat('★', $i) . str_repeat('☆', 5 - $i) ?></option><?php endfor; ?></select>
          </label>
          <label>Titre<input type="text" name="title" maxlength="190"></label>
          <label><?= e(__('message')) ?> *<textarea name="body" rows="4" maxlength="3000" required></textarea></label>
          <?= Recaptcha::widget('review') ?>
          <button class="btn btn-primary" type="submit"><?= e(__('send')) ?></button>
        </form>
      </div>
    </section>
    <?php endif; ?>
  </div>

  <?php if ($related): ?>
  <section class="section">
    <div class="section-head"><h2><?= e(__('related_products')) ?></h2></div>
    <div class="product-grid">
      <?php foreach ($related as $p) require INCLUDES_PATH . '/layout/product-card.php'; ?>
    </div>
  </section>
  <?php endif; ?>
</div>
<?php require INCLUDES_PATH . '/layout/footer.php'; ?>
