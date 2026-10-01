<?php
/**
 * Demande de devis.
 */
$formType = 'quote';
$formErrors = [];
if (is_post()) {
    require_csrf();
    $formErrors = Requests::handle('quote');
    if (!$formErrors) {
        flash('success', __('request_sent'));
        redirect('devis');
    }
}
$formProduct = int_param('produit') ? Catalog::productById(int_param('produit')) : null;
$pageTitle = __('quote_request');
require INCLUDES_PATH . '/layout/header.php';
?>
<div class="container">
  <nav class="breadcrumb"><a href="<?= e(url()) ?>"><?= e(__('home')) ?></a><span>›</span><span><?= e(__('quote_request')) ?></span></nav>
  <div class="split">
    <div class="split-text">
      <h1><?= e(__('quote_request')) ?></h1>
      <p>Entreprises, administrations, revendeurs : recevez une offre personnalisée sous 24 h ouvrées pour vos achats en volume, vos projets d'équipement ou vos contrats de service.</p>
      <ul class="check-list">
        <li><?= icon('check', 'icon icon-sm') ?> Tarifs dégressifs selon les quantités</li>
        <li><?= icon('check', 'icon icon-sm') ?> Conseil sur le matériel adapté</li>
        <li><?= icon('check', 'icon icon-sm') ?> Paiement par virement, chèque ou mobile money</li>
      </ul>
    </div>
    <?php require INCLUDES_PATH . '/layout/request-form.php'; ?>
  </div>
</div>
<?php require INCLUDES_PATH . '/layout/footer.php'; ?>
