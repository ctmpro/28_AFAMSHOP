<?php
/**
 * Détail d'un service (location, maintenance...) avec formulaire de demande associé.
 */
$service = DB::one('SELECT * FROM services WHERE slug = :s AND active = 1', ['s' => $slug]);
if (!$service) {
    http_response_code(404);
    require PAGES_PATH . '/404.php';
    return;
}
$formType = in_array($service['form_type'], ['rental', 'maintenance', 'quote'], true) ? $service['form_type'] : null;
$formErrors = [];
if ($formType && is_post()) {
    require_csrf();
    $formErrors = Requests::handle($formType);
    if (!$formErrors) {
        flash('success', __('request_sent'));
        redirect('service/' . $service['slug']);
    }
}
// Contenu riche (grilles de cartes) : affichage pleine largeur, formulaire en dessous
$wide = (bool)preg_match('/class="(svc-grid|about-grid)/', (string)$service['content']);
$titles = ['rental' => __('rental_request'), 'maintenance' => __('maintenance_request'), 'quote' => __('quote_request')];
$pageTitle = $service['title'];
$metaDescription = $service['short_desc'];
require INCLUDES_PATH . '/layout/header.php';
?>
<div class="container">
  <nav class="breadcrumb"><a href="<?= e(url()) ?>"><?= e(__('home')) ?></a><span>›</span><a href="<?= e(url('services')) ?>"><?= e(__('services')) ?></a><span>›</span><span><?= e($service['title']) ?></span></nav>
  <header class="page-hero">
    <div>
      <h1><?= e($service['title']) ?></h1>
      <p class="lead"><?= e($service['short_desc']) ?></p>
    </div>
    <?php if ($service['image']): ?><img src="<?= e(media_url($service['image'])) ?>" alt=""><?php else: ?><span class="page-hero-icon"><?= icon($service['icon'] ?: 'briefcase', 'icon icon-xl') ?></span><?php endif; ?>
  </header>
  <?php if ($wide): ?>
  <article class="rich-text"><?= clean_html(render_vars($service['content'])) ?></article>
  <?php if ($formType): ?>
  <section class="split service-form-section">
    <div>
      <h2 id="formulaire"><?= e($titles[$formType]) ?></h2>
      <p><?= e(__('service_form_intro')) ?></p>
      <ul class="contact-list">
        <?php if (setting('contact_phone')): ?><li><?= icon('phone') ?><div><strong><?= e(__('phone')) ?></strong><a href="tel:<?= e(preg_replace('/[^\d+]/', '', setting('contact_phone'))) ?>"><?= e(setting('contact_phone')) ?></a><?php if (setting('contact_phone2')): ?><a href="tel:<?= e(preg_replace('/[^\d+]/', '', setting('contact_phone2'))) ?>"><?= e(setting('contact_phone2')) ?></a><?php endif; ?></div></li><?php endif; ?>
        <?php if (setting('contact_email')): ?><li><?= icon('mail') ?><div><strong><?= e(__('email')) ?></strong><a href="mailto:<?= e(setting('contact_email')) ?>"><?= e(setting('contact_email')) ?></a></div></li><?php endif; ?>
        <?php if (setting('whatsapp_number')): ?><li><?= icon('whatsapp') ?><div><strong>WhatsApp</strong><a href="<?= e(whatsapp_link('Bonjour, je souhaite un renseignement sur : ' . $service['title'])) ?>" target="_blank" rel="noopener"><?= e(setting('whatsapp_number')) ?></a></div></li><?php endif; ?>
      </ul>
    </div>
    <?php require INCLUDES_PATH . '/layout/request-form.php'; ?>
  </section>
  <?php endif; ?>
  <?php else: ?>
  <div class="split">
    <article class="rich-text"><?= clean_html(render_vars($service['content'])) ?></article>
    <?php if ($formType): ?>
    <div>
      <h2 id="formulaire"><?= e($titles[$formType]) ?></h2>
      <?php require INCLUDES_PATH . '/layout/request-form.php'; ?>
    </div>
    <?php endif; ?>
  </div>
  <?php endif; ?>
</div>
<?php require INCLUDES_PATH . '/layout/footer.php'; ?>
