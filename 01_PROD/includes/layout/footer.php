<?php
/**
 * Pied de page commun du site public.
 */
$footerPages = DB::all('SELECT slug, title, footer_group FROM pages WHERE published = 1 AND footer_group IS NOT NULL ORDER BY sort, title');
$groups = ['company' => [], 'help' => [], 'legal' => []];
foreach ($footerPages as $fp) {
    $groups[$fp['footer_group']][] = $fp;
}
$socials = array_filter([
    'Facebook' => setting('social_facebook'),
    'Instagram' => setting('social_instagram'),
    'LinkedIn' => setting('social_linkedin'),
    'YouTube' => setting('social_youtube'),
    'TikTok' => setting('social_tiktok'),
]);
?>
</main>

<footer class="site-footer">
  <div class="container footer-grid">
    <div class="footer-col footer-about">
      <a class="logo logo-footer" href="<?= e(url()) ?>">
        <?php if (setting('logo_footer') || setting('logo')): ?>
          <img src="<?= e(media_url(setting('logo_footer', setting('logo')))) ?>" alt="<?= e(setting('site_name')) ?>">
        <?php else: ?>
          <span class="logo-mark">A</span><span class="logo-text"><?= e(setting('site_name', 'AFAMSHOP')) ?></span>
        <?php endif; ?>
      </a>
      <p><?= nl2br(e(render_vars(setting('footer_about')))) ?></p>
      <?php if ($socials): ?>
      <div class="socials">
        <?php foreach ($socials as $name => $link): ?><a href="<?= e($link) ?>" target="_blank" rel="noopener"><?= e($name) ?></a><?php endforeach; ?>
      </div>
      <?php endif; ?>
    </div>
    <div class="footer-col">
      <h3><?= e(setting('site_name', 'AFAMSHOP')) ?></h3>
      <ul>
        <?php foreach ($groups['company'] as $p): ?><li><a href="<?= e(url($p['slug'])) ?>"><?= e($p['title']) ?></a></li><?php endforeach; ?>
        <li><a href="<?= e(url('services')) ?>"><?= e(__('services')) ?></a></li>
        <li><a href="<?= e(url('service/location')) ?>"><?= e(__('req_rental')) ?></a></li>
        <li><a href="<?= e(url('service/maintenance')) ?>"><?= e(__('req_maintenance')) ?></a></li>
        <li><a href="<?= e(url('devis')) ?>"><?= e(__('request_quote')) ?></a></li>
      </ul>
    </div>
    <div class="footer-col">
      <h3>Aide</h3>
      <ul>
        <?php foreach ($groups['help'] as $p): ?><li><a href="<?= e(url($p['slug'])) ?>"><?= e($p['title']) ?></a></li><?php endforeach; ?>
        <li><a href="<?= e(url('compte')) ?>"><?= e(__('my_account')) ?></a></li>
        <li><a href="<?= e(url('recherche-imprimante')) ?>"><?= e(__('printer_finder')) ?></a></li>
        <li><a href="<?= e(url('contact')) ?>"><?= e(__('contact')) ?></a></li>
      </ul>
    </div>
    <div class="footer-col">
      <h3><?= e(__('contact')) ?></h3>
      <ul class="footer-contact">
        <?php if (setting('company_address')): ?><li><?= icon('pin', 'icon icon-sm') ?> <span><?= nl2br(e(setting('company_address'))) ?></span></li><?php endif; ?>
        <?php if (setting('contact_phone')): ?><li><?= icon('phone', 'icon icon-sm') ?> <a href="tel:<?= e(preg_replace('/[^\d+]/', '', setting('contact_phone'))) ?>"><?= e(setting('contact_phone')) ?></a></li><?php endif; ?>
        <?php if (setting('contact_email')): ?><li><?= icon('mail', 'icon icon-sm') ?> <a href="mailto:<?= e(setting('contact_email')) ?>"><?= e(setting('contact_email')) ?></a></li><?php endif; ?>
        <?php if (setting('opening_hours')): ?><li><?= icon('clock', 'icon icon-sm') ?> <span><?= nl2br(e(setting('opening_hours'))) ?></span></li><?php endif; ?>
      </ul>
      <div class="pay-badges" aria-label="<?= e(__('secure_payment')) ?>">
        <?php if (StripeGateway::enabled()): ?><span>Visa</span><span>Mastercard</span><?php endif; ?>
        <?php if (PayDunyaGateway::enabled()): ?><span>Wave</span><span>Orange Money</span><?php endif; ?>
        <?php if (setting_bool('payment_cod_enabled')): ?><span>À la livraison</span><?php endif; ?>
      </div>
    </div>
  </div>
  <div class="footer-bottom">
    <div class="container footer-bottom-inner">
      <span><?= e(render_vars(setting('footer_copyright', '© {year} {company_name}'))) ?></span>
      <nav class="legal-links">
        <?php foreach ($groups['legal'] as $p): ?><a href="<?= e(url($p['slug'])) ?>"><?= e($p['title']) ?></a><?php endforeach; ?>
      </nav>
    </div>
  </div>
</footer>

<?php if (setting_bool('whatsapp_enabled', true) && setting('whatsapp_number')): ?>
<a class="whatsapp-float" href="<?= e(whatsapp_link(setting('whatsapp_message'))) ?>" target="_blank" rel="noopener" aria-label="<?= e(__('whatsapp_chat')) ?>">
  <svg viewBox="0 0 32 32" aria-hidden="true"><path fill="currentColor" d="M16 3C9 3 3.3 8.7 3.3 15.7c0 2.5.7 4.9 2 7L3 29l6.5-2.2c2 1.1 4.2 1.7 6.5 1.7 7 0 12.7-5.7 12.7-12.7S23 3 16 3zm0 23.2c-2.1 0-4.1-.6-5.9-1.7l-.4-.3-3.9 1.3 1.3-3.8-.3-.4c-1.2-1.8-1.8-3.9-1.8-6 0-5.8 4.7-10.6 10.6-10.6s10.6 4.7 10.6 10.6S21.8 26.2 16 26.2zm5.8-7.9c-.3-.2-1.9-.9-2.2-1-.3-.1-.5-.2-.7.2-.2.3-.8 1-1 1.2-.2.2-.4.2-.7.1-.3-.2-1.3-.5-2.6-1.6-1-.9-1.6-1.9-1.8-2.2-.2-.3 0-.5.1-.7l.5-.6c.2-.2.2-.3.3-.5.1-.2 0-.4 0-.6l-1-2.4c-.3-.6-.5-.5-.7-.5h-.6c-.2 0-.6.1-.9.4-.3.3-1.2 1.1-1.2 2.8s1.2 3.2 1.4 3.5c.2.2 2.4 3.6 5.7 5 .8.3 1.4.5 1.9.7.8.3 1.5.2 2.1.1.6-.1 1.9-.8 2.2-1.5.3-.7.3-1.4.2-1.5-.1-.1-.3-.2-.6-.4z"/></svg>
</a>
<?php endif; ?>

<?php if (setting_bool('cookie_banner_enabled', true)): ?>
<div class="cookie-banner" data-cookie-banner hidden>
  <p><?= e(__('cookie_text')) ?> <a href="<?= e(url('cookies')) ?>"><?= e(__('learn_more')) ?></a></p>
  <button class="btn btn-primary btn-sm" data-cookie-accept><?= e(__('cookie_accept')) ?></button>
</div>
<?php endif; ?>

<div class="toast" data-toast role="status" aria-live="polite" hidden></div>
<script src="<?= e(asset('js/app.js')) ?>" defer></script>
</body>
</html>
