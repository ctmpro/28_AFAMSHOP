<?php
/**
 * Page d'accueil.
 */
$heroBanners = DB::all("SELECT * FROM banners WHERE position = 'home_hero' AND active = 1
    AND (start_at IS NULL OR start_at <= NOW()) AND (end_at IS NULL OR end_at >= NOW()) ORDER BY sort, id");
$promoBanners = DB::all("SELECT * FROM banners WHERE position = 'home_promo' AND active = 1
    AND (start_at IS NULL OR start_at <= NOW()) AND (end_at IS NULL OR end_at >= NOW()) ORDER BY sort, id LIMIT 3");
$categories = array_filter(Catalog::categoryTree(), fn($c) => $c['show_in_menu']);
$popular = Catalog::featured((int)setting('home_popular_count', 8));
$onSale = Catalog::onSale(8);
$brands = Catalog::brands();
$printerBrands = Catalog::printerBrands();
$services = DB::all('SELECT * FROM services WHERE active = 1 ORDER BY sort LIMIT 4');
$favoriteIds = Auth::id() ? array_map('intval', DB::col('SELECT product_id FROM favorites WHERE customer_id = :c', ['c' => Auth::id()])) : [];

$heroImage = setting('hero_image');
$heroVideo = setting('hero_video');
$bodyClass = 'page-home';
require INCLUDES_PATH . '/layout/header.php';

/** Bloc éditorial : titre, texte, image, lien. */
$block = function (string $key, string $link, string $btn, string $iconName, bool $reverse = false) {
    $img = setting('home_' . $key . '_image');
    ?>
    <section class="feature-block <?= $reverse ? 'reverse' : '' ?>">
      <div class="container feature-inner">
        <div class="feature-media">
          <?php if ($img): ?><img src="<?= e(media_url($img)) ?>" alt="" loading="lazy">
          <?php else: ?><div class="feature-illu"><?= icon($iconName, 'icon icon-xl') ?></div><?php endif; ?>
        </div>
        <div class="feature-text">
          <h2><?= e(setting('home_' . $key . '_title')) ?></h2>
          <p><?= nl2br(e(render_vars(setting('home_' . $key . '_text')))) ?></p>
          <a class="btn btn-primary" href="<?= e(url($link)) ?>"><?= e($btn) ?></a>
        </div>
      </div>
    </section>
    <?php
};
?>

<section class="hero <?= $heroImage || $heroVideo ? 'hero-media' : '' ?>" <?= $heroImage ? 'style="background-image:url(\'' . e(media_url($heroImage)) . '\')"' : '' ?>>
  <?php if ($heroVideo): ?>
    <video class="hero-video" autoplay muted loop playsinline <?= $heroImage ? 'poster="' . e(media_url($heroImage)) . '"' : '' ?>><source src="<?= e(media_url($heroVideo)) ?>"></video>
  <?php endif; ?>
  <div class="container hero-inner">
    <div class="hero-text">
      <h1><?= e(render_vars(setting('hero_title'))) ?></h1>
      <p><?= e(render_vars(setting('hero_subtitle'))) ?></p>
      <div class="hero-actions">
        <?php if (setting('hero_button_text')): ?><a class="btn btn-accent btn-lg" href="<?= e(url(setting('hero_button_link'))) ?>"><?= e(setting('hero_button_text')) ?></a><?php endif; ?>
        <?php if (setting('hero_button2_text')): ?><a class="btn btn-ghost btn-lg" href="<?= e(url(setting('hero_button2_link'))) ?>"><?= e(setting('hero_button2_text')) ?></a><?php endif; ?>
      </div>
    </div>
    <div class="hero-finder card">
      <h2><?= icon('printer') ?> <?= e(setting('home_finder_title', __('printer_finder'))) ?></h2>
      <p><?= e(setting('home_finder_text')) ?></p>
      <form action="<?= e(url('recherche-imprimante')) ?>" method="get" class="finder-form" data-finder>
        <select name="marque" data-finder-brand data-models-url="<?= e(url('api/printer-models.php')) ?>" required>
          <option value=""><?= e(__('choose_brand')) ?></option>
          <?php foreach ($printerBrands as $b): ?><option value="<?= (int)$b['id'] ?>"><?= e($b['name']) ?></option><?php endforeach; ?>
        </select>
        <select name="modele" data-finder-model disabled required>
          <option value=""><?= e(__('choose_model')) ?></option>
        </select>
        <button class="btn btn-primary" type="submit"><?= e(__('find')) ?></button>
      </form>
      <form action="<?= e(url('recherche')) ?>" method="get" class="finder-ref">
        <input type="text" name="q" placeholder="<?= e(__('search_by_ref')) ?>" maxlength="100">
        <button class="btn btn-outline" type="submit" aria-label="<?= e(__('search')) ?>"><?= icon('search', 'icon icon-sm') ?></button>
      </form>
    </div>
  </div>
</section>

<?php if ($heroBanners): ?>
<section class="container banner-slider" data-slider>
  <?php foreach ($heroBanners as $i => $b): ?>
  <a class="slide <?= $i === 0 ? 'active' : '' ?>" href="<?= e($b['link'] ? url($b['link']) : '#') ?>">
    <picture>
      <?php if ($b['image_mobile']): ?><source media="(max-width: 700px)" srcset="<?= e(media_url($b['image_mobile'])) ?>"><?php endif; ?>
      <img src="<?= e(media_url($b['image_desktop'])) ?>" alt="<?= e($b['title']) ?>">
    </picture>
    <?php if ($b['title']): ?><span class="slide-caption"><strong><?= e($b['title']) ?></strong><?= $b['subtitle'] ? '<span>' . e($b['subtitle']) . '</span>' : '' ?></span><?php endif; ?>
  </a>
  <?php endforeach; ?>
</section>
<?php endif; ?>

<section class="reassurance">
  <div class="container reassurance-grid">
    <?php foreach ([1 => 'truck', 2 => 'shield', 3 => 'check', 4 => 'wrench'] as $n => $ic):
      $parts = explode('|', setting('reassurance_' . $n), 2); if (!trim($parts[0])) continue; ?>
      <div class="reassurance-item"><?= icon($ic) ?><div><strong><?= e($parts[0]) ?></strong><span><?= e($parts[1] ?? '') ?></span></div></div>
    <?php endforeach; ?>
  </div>
</section>

<section class="section">
  <div class="container">
    <div class="section-head"><h2><?= e(setting('home_categories_title', __('categories'))) ?></h2></div>
    <div class="category-grid">
      <?php foreach ($categories as $c): ?>
      <a class="category-card" href="<?= e(url('categorie/' . $c['slug'])) ?>">
        <?php if ($c['image']): ?><img src="<?= e(media_url($c['image'])) ?>" alt="" loading="lazy"><?php else: ?><span class="category-icon"><?= icon($c['icon'] ?: 'box', 'icon icon-lg') ?></span><?php endif; ?>
        <strong><?= e($c['name']) ?></strong>
        <span><?= e(implode(' · ', array_slice(array_column($c['children'], 'name'), 0, 4))) ?></span>
      </a>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<?php if ($popular): ?>
<section class="section section-alt">
  <div class="container">
    <div class="section-head"><h2><?= e(setting('home_popular_title')) ?></h2><a href="<?= e(url('recherche', ['tri' => 'bestsellers'])) ?>"><?= e(__('see_all')) ?> →</a></div>
    <div class="product-grid">
      <?php foreach ($popular as $p) require INCLUDES_PATH . '/layout/product-card.php'; ?>
    </div>
  </div>
</section>
<?php endif; ?>

<?php if ($promoBanners || $onSale): ?>
<section class="section">
  <div class="container">
    <div class="section-head"><h2><?= e(setting('home_promo_title')) ?></h2><a href="<?= e(url('promotions')) ?>"><?= e(__('see_all')) ?> →</a></div>
    <?php if ($promoBanners): ?>
    <div class="promo-banners">
      <?php foreach ($promoBanners as $b): ?>
      <a class="promo-banner" href="<?= e($b['link'] ? url($b['link']) : url('promotions')) ?>" <?= $b['image_desktop'] ? 'style="background-image:url(\'' . e(media_url($b['image_desktop'])) . '\')"' : '' ?>>
        <strong><?= e($b['title']) ?></strong>
        <?php if ($b['subtitle']): ?><span><?= e($b['subtitle']) ?></span><?php endif; ?>
        <?php if ($b['button_text']): ?><em><?= e($b['button_text']) ?> →</em><?php endif; ?>
      </a>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
    <?php if ($onSale): ?>
    <div class="product-grid">
      <?php foreach ($onSale as $p) require INCLUDES_PATH . '/layout/product-card.php'; ?>
    </div>
    <?php endif; ?>
  </div>
</section>
<?php endif; ?>

<?php $block('pro', 'solutions-professionnelles', __('request_quote'), 'briefcase'); ?>

<section class="section section-alt">
  <div class="container duo">
    <article class="duo-card">
      <?= icon('key', 'icon icon-lg') ?>
      <h2><?= e(setting('home_rental_title')) ?></h2>
      <p><?= nl2br(e(render_vars(setting('home_rental_text')))) ?></p>
      <a class="btn btn-primary" href="<?= e(url('service/location')) ?>"><?= e(__('learn_more')) ?></a>
    </article>
    <article class="duo-card">
      <?= icon('wrench', 'icon icon-lg') ?>
      <h2><?= e(setting('home_maintenance_title')) ?></h2>
      <p><?= nl2br(e(render_vars(setting('home_maintenance_text')))) ?></p>
      <a class="btn btn-primary" href="<?= e(url('service/maintenance')) ?>"><?= e(__('learn_more')) ?></a>
    </article>
  </div>
</section>

<?php $block('sharp', 'marque/sharp', 'Découvrir Sharp', 'printer', true); ?>

<?php if ($services): ?>
<section class="section">
  <div class="container">
    <div class="section-head"><h2><?= e(__('services')) ?></h2><a href="<?= e(url('services')) ?>"><?= e(__('see_all')) ?> →</a></div>
    <div class="service-grid">
      <?php foreach ($services as $s): ?>
      <a class="service-card" href="<?= e(url('service/' . $s['slug'])) ?>">
        <?= icon($s['icon'] ?: 'briefcase', 'icon icon-lg') ?>
        <strong><?= e($s['title']) ?></strong>
        <span><?= e($s['short_desc']) ?></span>
      </a>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>

<?php if ($brands): ?>
<section class="section section-alt">
  <div class="container">
    <div class="section-head"><h2><?= e(setting('home_brands_title')) ?></h2><a href="<?= e(url('marques')) ?>"><?= e(__('see_all')) ?> →</a></div>
    <div class="brand-strip">
      <?php foreach ($brands as $b): ?>
      <a class="brand-logo <?= $b['featured'] ? 'featured' : '' ?>" href="<?= e(url('marque/' . $b['slug'])) ?>">
        <?php if ($b['logo']): ?><img src="<?= e(media_url($b['logo'])) ?>" alt="<?= e($b['name']) ?>" loading="lazy"><?php else: ?><span><?= e($b['name']) ?></span><?php endif; ?>
      </a>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>

<section class="contact-cta">
  <div class="container contact-cta-inner">
    <div>
      <h2><?= e(setting('home_contact_title')) ?></h2>
      <p><?= e(render_vars(setting('home_contact_text'))) ?></p>
    </div>
    <div class="contact-cta-actions">
      <?php if (setting('contact_phone')): ?><a class="btn btn-ghost" href="tel:<?= e(preg_replace('/[^\d+]/', '', setting('contact_phone'))) ?>"><?= icon('phone', 'icon icon-sm') ?> <?= e(setting('contact_phone')) ?></a><?php endif; ?>
      <?php if (setting('whatsapp_number')): ?><a class="btn btn-whatsapp" href="<?= e(whatsapp_link(setting('whatsapp_message'))) ?>" target="_blank" rel="noopener"><?= icon('whatsapp', 'icon icon-sm') ?> WhatsApp</a><?php endif; ?>
      <a class="btn btn-accent" href="<?= e(url('contact')) ?>"><?= icon('mail', 'icon icon-sm') ?> <?= e(__('contact_us')) ?></a>
    </div>
  </div>
</section>

<?php require INCLUDES_PATH . '/layout/footer.php'; ?>
