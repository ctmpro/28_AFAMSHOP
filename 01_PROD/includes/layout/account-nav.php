<?php
/** Menu de l'espace client. $active = clé de la page courante. */
$links = [
    'dashboard' => ['compte', __('dashboard'), 'user'],
    'orders' => ['compte/commandes', __('my_orders'), 'box'],
    'addresses' => ['compte/adresses', __('my_addresses'), 'pin'],
    'favorites' => ['compte/favoris', __('my_favorites'), 'heart'],
    'requests' => ['compte/demandes', __('my_quotes'), 'file'],
    'profile' => ['compte/profil', __('my_profile'), 'user'],
    'password' => ['compte/mot-de-passe', __('change_password'), 'key'],
];
?>
<aside class="account-nav card">
  <p class="account-hello"><?= e(__('welcome_back', ['name' => Auth::user()['first_name']])) ?></p>
  <nav>
    <?php foreach ($links as $k => [$path, $label, $ic]): ?>
      <a href="<?= e(url($path)) ?>" class="<?= ($active ?? '') === $k ? 'active' : '' ?>"><?= icon($ic, 'icon icon-sm') ?> <?= e($label) ?></a>
    <?php endforeach; ?>
    <form method="post" action="<?= e(url('compte/deconnexion')) ?>"><?= csrf_field() ?><button class="link-btn"><?= icon('logout', 'icon icon-sm') ?> <?= e(__('logout')) ?></button></form>
  </nav>
</aside>
