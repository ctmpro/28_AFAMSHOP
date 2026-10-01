<?php
/**
 * Page contact.
 */
$formType = 'contact';
$formErrors = [];
if (is_post()) {
    require_csrf();
    $formErrors = Requests::handle('contact');
    if (!$formErrors) {
        flash('success', __('request_sent'));
        redirect('contact');
    }
}
$pageTitle = __('contact');
require INCLUDES_PATH . '/layout/header.php';
?>
<div class="container">
  <nav class="breadcrumb"><a href="<?= e(url()) ?>"><?= e(__('home')) ?></a><span>›</span><span><?= e(__('contact')) ?></span></nav>
  <h1><?= e(__('contact_us')) ?></h1>
  <div class="split">
    <div class="contact-info">
      <ul class="contact-list">
        <?php if (setting('company_address')): ?><li><?= icon('pin') ?><div><strong><?= e(__('address')) ?></strong><span><?= nl2br(e(setting('company_address'))) ?></span></div></li><?php endif; ?>
        <?php if (setting('contact_phone')): ?><li><?= icon('phone') ?><div><strong><?= e(__('phone')) ?></strong><a href="tel:<?= e(preg_replace('/[^\d+]/', '', setting('contact_phone'))) ?>"><?= e(setting('contact_phone')) ?></a><?php if (setting('contact_phone2')): ?><a href="tel:<?= e(preg_replace('/[^\d+]/', '', setting('contact_phone2'))) ?>"><?= e(setting('contact_phone2')) ?></a><?php endif; ?></div></li><?php endif; ?>
        <?php if (setting('contact_email')): ?><li><?= icon('mail') ?><div><strong><?= e(__('email')) ?></strong><a href="mailto:<?= e(setting('contact_email')) ?>"><?= e(setting('contact_email')) ?></a></div></li><?php endif; ?>
        <?php if (setting('whatsapp_number')): ?><li><?= icon('whatsapp') ?><div><strong>WhatsApp</strong><a href="<?= e(whatsapp_link(setting('whatsapp_message'))) ?>" target="_blank" rel="noopener"><?= e(setting('whatsapp_number')) ?></a></div></li><?php endif; ?>
        <?php if (setting('opening_hours')): ?><li><?= icon('clock') ?><div><strong>Horaires</strong><span><?= nl2br(e(setting('opening_hours'))) ?></span></div></li><?php endif; ?>
      </ul>
      <?php if (preg_match('#^https://(www\.)?google\.[a-z.]+/maps/embed#', setting('map_embed'))): ?>
        <iframe class="map" src="<?= e(setting('map_embed')) ?>" loading="lazy" referrerpolicy="no-referrer-when-downgrade" title="Carte"></iframe>
      <?php endif; ?>
    </div>
    <?php require INCLUDES_PATH . '/layout/request-form.php'; ?>
  </div>
</div>
<?php require INCLUDES_PATH . '/layout/footer.php'; ?>
