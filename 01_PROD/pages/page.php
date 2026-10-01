<?php
/**
 * Page institutionnelle administrable (À propos, CGV, FAQ...).
 */
$cms = DB::one('SELECT * FROM pages WHERE slug = :s AND published = 1', ['s' => $slug]);
if (!$cms) {
    http_response_code(404);
    require PAGES_PATH . '/404.php';
    return;
}
$pageTitle = $cms['meta_title'] ?: $cms['title'];
$metaDescription = $cms['meta_description'] ?: truncate($cms['content'], 160);
require INCLUDES_PATH . '/layout/header.php';
?>
<div class="container narrow">
  <nav class="breadcrumb"><a href="<?= e(url()) ?>"><?= e(__('home')) ?></a><span>›</span><span><?= e($cms['title']) ?></span></nav>
  <h1><?= e($cms['title']) ?></h1>
  <article class="rich-text"><?= clean_html(render_vars($cms['content'])) ?></article>
  <p class="muted small">Dernière mise à jour : <?= e(format_date($cms['updated_at'])) ?></p>
</div>
<?php require INCLUDES_PATH . '/layout/footer.php'; ?>
