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
  <div class="split">
    <article class="rich-text"><?= clean_html(render_vars($service['content'])) ?></article>
    <?php if ($formType): ?>
    <div>
      <h2 id="formulaire"><?= e($titles[$formType]) ?></h2>
      <?php require INCLUDES_PATH . '/layout/request-form.php'; ?>
    </div>
    <?php endif; ?>
  </div>
</div>
<?php require INCLUDES_PATH . '/layout/footer.php'; ?>
