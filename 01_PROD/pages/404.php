<?php
$pageTitle = __('page_not_found');
$noIndex = true;
require INCLUDES_PATH . '/layout/header.php';
?>
<div class="container narrow empty-state">
  <h1>404</h1>
  <p><?= e(__('page_not_found_text')) ?></p>
  <form action="<?= e(url('recherche')) ?>" class="inline-search"><input type="search" name="q" placeholder="<?= e(__('search_placeholder')) ?>"><button class="btn btn-primary"><?= e(__('search')) ?></button></form>
  <a class="btn btn-link" href="<?= e(url()) ?>"><?= e(__('home')) ?></a>
</div>
<?php require INCLUDES_PATH . '/layout/footer.php'; ?>
