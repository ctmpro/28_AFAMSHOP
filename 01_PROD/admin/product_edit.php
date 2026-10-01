<?php
/**
 * Création / modification d'un produit : fiche complète, images, compatibilités,
 * stock (historisé via Stock::set), duplication et suppression.
 */
require dirname(__DIR__) . '/includes/bootstrap.php';
require __DIR__ . '/_inc/helpers.php';
$admin = AdminAuth::require('products');

$id = int_param('id');
$product = $id ? DB::one('SELECT * FROM products WHERE id = :id', ['id' => $id]) : null;
if ($id && !$product) {
    flash('error', 'Produit introuvable.');
    redirect(admin_url('products.php'));
}

$defaults = [
    'sku' => '', 'manufacturer_ref' => '', 'name' => '', 'slug' => '', 'brand_id' => '', 'category_id' => '',
    'product_type' => '', 'color' => '', 'short_description' => '', 'description' => '', 'specs' => '',
    'price' => '', 'promo_price' => '', 'promo_start' => null, 'promo_end' => null, 'stock' => 0,
    'low_stock_threshold' => '', 'on_order' => 0, 'weight' => '', 'dimensions' => '',
    'warranty' => setting('default_warranty'), 'delivery_delay' => setting('default_delivery_delay'),
    'is_featured' => 0, 'published' => 1, 'meta_title' => '', 'meta_description' => '',
];
$form = $product ?: $defaults;
$compatIds = $product ? array_map('intval', DB::col('SELECT printer_model_id FROM product_compatibility WHERE product_id = :id', ['id' => $id])) : [];
$errors = [];

if (is_post()) {
    require_csrf();
    $action = (string)post('action', 'save');

    // ----- Suppression
    if ($action === 'delete' && $product) {
        $paths = DB::col('SELECT path FROM product_images WHERE product_id = :id', ['id' => $id]);
        DB::exec('DELETE FROM products WHERE id = :id', ['id' => $id]);
        product_image_cleanup($paths);
        AdminAuth::log('product_delete', 'product', $id, $product['sku'] . ' — ' . $product['name']);
        flash('success', 'Produit « ' . $product['name'] . ' » supprimé.');
        redirect(admin_url('products.php'));
    }

    // ----- Duplication (non publiée, sans stock, images et compatibilités copiées)
    if ($action === 'duplicate' && $product) {
        $sku = substr($product['sku'], 0, 70) . '-COPY';
        $base = $sku;
        $i = 2;
        while (DB::val('SELECT id FROM products WHERE sku = :s', ['s' => $sku])) {
            $sku = $base . '-' . $i++;
        }
        $copy = $product;
        unset($copy['id'], $copy['created_at'], $copy['updated_at']);
        $copy['sku'] = $sku;
        $copy['name'] = $product['name'] . ' (copie)';
        $copy['slug'] = unique_slug('products', $copy['name']);
        $copy['published'] = 0;
        $copy['stock'] = 0;
        $copy['sales_count'] = 0;
        $copy['views'] = 0;
        DB::begin();
        try {
            $newId = DB::insert('products', $copy);
            DB::exec('INSERT INTO product_images (product_id, path, alt, is_main, sort) SELECT :n, path, alt, is_main, sort FROM product_images WHERE product_id = :o', ['n' => $newId, 'o' => $id]);
            DB::exec('INSERT INTO product_compatibility (product_id, printer_model_id) SELECT :n, printer_model_id FROM product_compatibility WHERE product_id = :o', ['n' => $newId, 'o' => $id]);
            DB::commit();
        } catch (Throwable $e) {
            DB::rollBack();
            throw $e;
        }
        AdminAuth::log('product_duplicate', 'product', $newId, 'Copie de #' . $id . ' (' . $product['sku'] . ')');
        flash('success', 'Produit dupliqué (non publié). Vérifiez le SKU et les informations avant publication.');
        redirect(admin_url('product_edit.php', ['id' => $newId]));
    }

    // ----- Enregistrement
    $in = [];
    foreach (['sku', 'manufacturer_ref', 'name', 'slug', 'product_type', 'color', 'short_description', 'specs',
              'weight', 'dimensions', 'warranty', 'delivery_delay', 'meta_title', 'meta_description'] as $f) {
        $in[$f] = (string)post($f);
    }
    $in['description'] = clean_html((string)($_POST['description'] ?? ''));
    $in['brand_id'] = (int)post('brand_id') ?: null;
    $in['category_id'] = (int)post('category_id') ?: null;
    $in['price'] = admin_dec(post('price'));
    $in['promo_price'] = admin_dec(post('promo_price'));
    $in['promo_start'] = admin_dt_in(post('promo_start'));
    $in['promo_end'] = admin_dt_in(post('promo_end'));
    $stockRaw = (string)post('stock', '0');
    $in['low_stock_threshold'] = admin_int_or_null(post('low_stock_threshold'));
    $in['on_order'] = post_bool('on_order');
    $in['is_featured'] = post_bool('is_featured');
    $in['published'] = post_bool('published');
    $compatPosted = array_values(array_unique(array_map('intval', (array)($_POST['compat'] ?? []))));

    // Validation
    if ($in['name'] === '') $errors[] = 'Le nom du produit est obligatoire.';
    if ($in['sku'] === '') {
        $errors[] = 'Le SKU est obligatoire.';
    } elseif (!preg_match('/^[A-Za-z0-9._\-\/]{1,80}$/', $in['sku'])) {
        $errors[] = 'Le SKU ne doit contenir que lettres, chiffres, points, tirets, « / » ou « _ » (80 caractères max).';
    } elseif (DB::val('SELECT id FROM products WHERE sku = :s AND id <> :id', ['s' => $in['sku'], 'id' => $id])) {
        $errors[] = 'Ce SKU est déjà utilisé par un autre produit.';
    }
    if ($in['price'] === null || $in['price'] < 0) $errors[] = 'Le prix doit être un nombre positif.';
    if (post('promo_price') !== '' && ($in['promo_price'] === null || $in['promo_price'] < 0)) $errors[] = 'Prix promotionnel invalide.';
    if ($in['promo_price'] !== null && $in['price'] !== null && $in['promo_price'] >= $in['price']) $errors[] = 'Le prix promotionnel doit être inférieur au prix normal.';
    if ($in['promo_start'] && $in['promo_end'] && $in['promo_end'] < $in['promo_start']) $errors[] = 'La fin de la promotion doit être postérieure à son début.';
    if (!preg_match('/^-?\d+$/', $stockRaw)) $errors[] = 'Le stock doit être un nombre entier.';
    if ($in['low_stock_threshold'] !== null && $in['low_stock_threshold'] < 0) $errors[] = 'Le seuil de stock faible doit être positif.';
    if ($in['brand_id'] && !DB::val('SELECT id FROM brands WHERE id = :id', ['id' => $in['brand_id']])) $errors[] = 'Marque invalide.';
    if ($in['category_id'] && !DB::val('SELECT id FROM categories WHERE id = :id', ['id' => $in['category_id']])) $errors[] = 'Catégorie invalide.';
    if (mb_strlen($in['name']) > 255 || mb_strlen($in['meta_title']) > 255 || mb_strlen($in['meta_description']) > 255) $errors[] = 'Un champ dépasse 255 caractères.';

    if ($errors) {
        $form = array_merge($form, $in, ['stock' => $stockRaw, 'price' => post('price'), 'promo_price' => post('promo_price')]);
        $compatIds = $compatPosted;
    } else {
        $data = $in;
        $data['slug'] = unique_slug('products', $in['slug'] !== '' ? $in['slug'] : $in['name'], $id ?: null);
        foreach (['manufacturer_ref', 'product_type', 'color', 'short_description', 'description', 'specs', 'weight',
                  'dimensions', 'warranty', 'delivery_delay', 'meta_title', 'meta_description'] as $f) {
            if ($data[$f] === '') $data[$f] = null;
        }
        $newStock = (int)$stockRaw;
        $warnings = [];

        DB::begin();
        try {
            if ($product) {
                DB::update('products', $data, 'id = :id', ['id' => $id]);
            } else {
                $data['stock'] = 0;
                $id = DB::insert('products', $data);
            }
            // Compatibilités (uniquement des modèles existants)
            DB::exec('DELETE FROM product_compatibility WHERE product_id = :id', ['id' => $id]);
            if ($compatPosted) {
                $p = [];
                $valid = DB::col('SELECT id FROM printer_models WHERE id IN ' . DB::in($compatPosted, $p), $p);
                foreach ($valid as $mid) {
                    DB::insert('product_compatibility', ['product_id' => $id, 'printer_model_id' => (int)$mid]);
                }
            }
            DB::commit();
        } catch (Throwable $e) {
            DB::rollBack();
            throw $e;
        }

        // Stock (historisé)
        Stock::set($id, $newStock, 'manual', $product ? 'Modification de la fiche produit' : 'Stock initial');

        // Images existantes : suppression, texte alternatif, ordre, image principale
        $removed = [];
        foreach (post_ids('delete_images') as $imgId) {
            $path = DB::val('SELECT path FROM product_images WHERE id = :i AND product_id = :p', ['i' => $imgId, 'p' => $id]);
            if ($path) {
                DB::exec('DELETE FROM product_images WHERE id = :i', ['i' => $imgId]);
                $removed[] = $path;
            }
        }
        product_image_cleanup($removed);
        foreach ((array)($_POST['img_alt'] ?? []) as $imgId => $alt) {
            DB::update('product_images', [
                'alt' => mb_substr(trim((string)$alt), 0, 255) ?: null,
                'sort' => (int)($_POST['img_sort'][$imgId] ?? 0),
            ], 'id = :i AND product_id = :p', ['i' => (int)$imgId, 'p' => $id]);
        }
        $mainId = (int)post('main_image');
        if ($mainId) {
            DB::exec('UPDATE product_images SET is_main = (id = :m) WHERE product_id = :p', ['m' => $mainId, 'p' => $id]);
        }

        // Nouvelles images
        $sort = (int)DB::val('SELECT COALESCE(MAX(sort), 0) FROM product_images WHERE product_id = :p', ['p' => $id]);
        foreach (files_list($_FILES['images'] ?? null) as $file) {
            try {
                $path = handle_upload($file, 'products', UPLOAD_IMAGE_TYPES);
                if ($path) {
                    DB::insert('product_images', ['product_id' => $id, 'path' => $path, 'alt' => $in['name'], 'is_main' => 0, 'sort' => ++$sort]);
                }
            } catch (RuntimeException $e) {
                $warnings[] = ($file['name'] ?? 'Image') . ' : ' . $e->getMessage();
            }
        }
        // Toujours exactement une image principale
        if (!DB::val('SELECT id FROM product_images WHERE product_id = :p AND is_main = 1', ['p' => $id])) {
            $first = DB::val('SELECT id FROM product_images WHERE product_id = :p ORDER BY sort, id LIMIT 1', ['p' => $id]);
            if ($first) DB::exec('UPDATE product_images SET is_main = 1 WHERE id = :i', ['i' => $first]);
        }

        AdminAuth::log($product ? 'product_update' : 'product_create', 'product', $id, $in['sku'] . ' — ' . $in['name']);
        foreach ($warnings as $w) flash('warning', $w);
        flash('success', $product ? 'Produit enregistré.' : 'Produit créé.');
        redirect(admin_url('product_edit.php', ['id' => $id]));
    }
}

// --- Données d'affichage
$images = $product ? Catalog::images($id) : [];
$models = DB::all('SELECT m.id, m.name, m.series, b.name AS brand_name FROM printer_models m JOIN brands b ON b.id = m.brand_id ORDER BY b.name, m.series, m.name');
$modelsByBrand = [];
foreach ($models as $m) {
    $modelsByBrand[$m['brand_name']][] = $m;
}
$movements = $product ? DB::all('SELECT sm.*, a.name AS admin_name FROM stock_movements sm LEFT JOIN admins a ON a.id = sm.admin_id WHERE sm.product_id = :id ORDER BY sm.id DESC LIMIT 8', ['id' => $id]) : [];
$decorated = $product ? Catalog::decorate([$product])[0] : null;

$pageTitle = $product ? 'Modifier : ' . $product['name'] : 'Nouveau produit';
$pageActions = '<a class="btn" href="' . e(admin_url('products.php')) . '">← Produits</a>';
if ($product) {
    $pageActions .= '<form method="post" class="inline">' . csrf_field() . '<input type="hidden" name="id" value="' . (int)$id . '"><input type="hidden" name="action" value="duplicate">'
        . '<button class="btn" type="submit" data-confirm="Dupliquer ce produit ?">' . aicon('copy') . ' Dupliquer</button></form>'
        . '<form method="post" class="inline">' . csrf_field() . '<input type="hidden" name="id" value="' . (int)$id . '"><input type="hidden" name="action" value="delete">'
        . '<button class="btn btn-danger" type="submit" data-confirm="Supprimer définitivement ce produit ?">' . aicon('trash') . ' Supprimer</button></form>';
    if ($product['published']) {
        $pageActions .= '<a class="btn" target="_blank" rel="noopener" href="' . e(url('produit/' . $product['slug'])) . '">' . aicon('eye') . ' Voir</a>';
    }
}
require __DIR__ . '/_inc/layout_top.php';
?>
<?php if ($errors): ?>
  <div class="alert alert-danger"><div><strong>Le produit n'a pas été enregistré :</strong><ul><?php foreach ($errors as $er): ?><li><?= e($er) ?></li><?php endforeach; ?></ul></div></div>
<?php endif; ?>

<form method="post" enctype="multipart/form-data" action="<?= e(admin_url('product_edit.php', ['id' => $id ?: null])) ?>">
  <?= csrf_field() ?>
  <input type="hidden" name="action" value="save">
  <div class="grid grid-main">
    <div class="stack">
      <div class="card">
        <h2>Informations générales</h2>
        <div class="form-grid">
          <?= field_input('name', 'Nom du produit', $form['name'], ['required' => true, 'class' => 'span-2', 'attrs' => ['maxlength' => 255]]) ?>
          <?= field_input('sku', 'SKU AFAM', $form['sku'], ['required' => true, 'attrs' => ['maxlength' => 80]]) ?>
          <?= field_input('manufacturer_ref', 'Référence constructeur', $form['manufacturer_ref'], ['attrs' => ['maxlength' => 120]]) ?>
          <?= field_select('brand_id', 'Marque', brand_options(), $form['brand_id'], ['placeholder' => '— Aucune —']) ?>
          <?= field_select('category_id', 'Catégorie', category_options(), $form['category_id'], ['placeholder' => '— Aucune —']) ?>
          <?= field_input('product_type', 'Type de produit', $form['product_type'], ['help' => 'Ex. : Toner, Cartouche, Imprimante laser…', 'attrs' => ['maxlength' => 80, 'list' => 'types-list']]) ?>
          <?= field_input('color', 'Couleur', $form['color'], ['attrs' => ['maxlength' => 60, 'list' => 'colors-list']]) ?>
          <?= field_input('slug', 'Adresse (slug)', $form['slug'], ['class' => 'span-2', 'help' => 'Laisser vide pour la générer à partir du nom.', 'attrs' => ['data-slug-from' => 'f_name', 'maxlength' => 260]]) ?>
        </div>
        <datalist id="types-list"><?php foreach (DB::col("SELECT DISTINCT product_type FROM products WHERE product_type IS NOT NULL AND product_type <> '' ORDER BY product_type") as $t): ?><option value="<?= e($t) ?>"><?php endforeach; ?></datalist>
        <datalist id="colors-list"><?php foreach (DB::col("SELECT DISTINCT color FROM products WHERE color IS NOT NULL AND color <> '' ORDER BY color") as $c): ?><option value="<?= e($c) ?>"><?php endforeach; ?></datalist>
      </div>

      <div class="card">
        <h2>Descriptions</h2>
        <?= field_textarea('short_description', 'Description courte', $form['short_description'], ['rows' => 3]) ?>
        <?= field_textarea('description', 'Description détaillée (HTML)', $form['description'], ['rows' => 10, 'html' => true]) ?>
        <?= field_textarea('specs', 'Caractéristiques techniques', $form['specs'], ['rows' => 8, 'help' => 'Une caractéristique par ligne, au format « Clé: valeur » (ex. « Rendement: 2 500 pages »).', 'attrs' => ['class' => 'code']]) ?>
      </div>

      <div class="card">
        <div class="card-head"><h2>Images</h2><span class="muted small">JPEG, PNG, WebP ou GIF</span></div>
        <?php if ($images): ?>
          <div class="img-grid mb">
            <?php foreach ($images as $img): ?>
              <div class="img-card<?= $img['is_main'] ? ' is-main' : '' ?>">
                <img src="<?= e(media_url($img['path'])) ?>" alt="<?= e($img['alt']) ?>">
                <input type="text" name="img_alt[<?= (int)$img['id'] ?>]" value="<?= e($img['alt']) ?>" placeholder="Texte alternatif" aria-label="Texte alternatif">
                <input type="number" name="img_sort[<?= (int)$img['id'] ?>]" value="<?= (int)$img['sort'] ?>" aria-label="Ordre" title="Ordre d'affichage">
                <label class="check small"><input type="radio" name="main_image" value="<?= (int)$img['id'] ?>"<?= $img['is_main'] ? ' checked' : '' ?>> Image principale</label><br>
                <label class="check small"><input type="checkbox" name="delete_images[]" value="<?= (int)$img['id'] ?>"> Supprimer</label>
              </div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
        <div class="field">
          <label for="images">Ajouter des images</label>
          <input type="file" id="images" name="images[]" multiple accept="image/jpeg,image/png,image/webp,image/gif" data-multi-preview="new-images-preview">
          <div class="upload-previews" id="new-images-preview"></div>
        </div>
      </div>

      <div class="card">
        <div class="card-head"><h2>Modèles d'imprimantes compatibles</h2><span class="muted small"><?= count($compatIds) ?> sélectionné(s)</span></div>
        <?php if (!$models): ?>
          <p class="muted">Aucun modèle d'imprimante. <a href="<?= e(admin_url('compatibility.php')) ?>">Créer des modèles</a></p>
        <?php else: ?>
          <div class="filters mb">
            <div class="field wide"><input type="search" placeholder="Filtrer les modèles (marque, série, nom)…" data-filter="#compat-box" aria-label="Filtrer les modèles"></div>
            <label class="check small"><input type="checkbox" data-show-checked="#compat-box"> Afficher uniquement la sélection</label>
          </div>
          <div class="compat-box" id="compat-box">
            <?php foreach ($modelsByBrand as $brandName => $list): ?>
              <div class="compat-group" data-filter-group>
                <h4><?= e($brandName) ?></h4>
                <div class="compat-list">
                  <?php foreach ($list as $m): ?>
                    <label class="check" data-filter-item="<?= e($brandName . ' ' . $m['series'] . ' ' . $m['name']) ?>">
                      <input type="checkbox" name="compat[]" value="<?= (int)$m['id'] ?>"<?= in_array((int)$m['id'], $compatIds, true) ? ' checked' : '' ?>>
                      <span><?= e($m['name']) ?><?= $m['series'] ? ' <span class="muted small">(' . e($m['series']) . ')</span>' : '' ?></span>
                    </label>
                  <?php endforeach; ?>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </div>

      <div class="card">
        <h2>Référencement (SEO)</h2>
        <?= field_input('meta_title', 'Titre SEO', $form['meta_title'], ['attrs' => ['maxlength' => 255], 'help' => 'Par défaut : nom du produit.']) ?>
        <?= field_textarea('meta_description', 'Méta-description', $form['meta_description'], ['rows' => 2, 'attrs' => ['maxlength' => 255]]) ?>
      </div>
    </div>

    <div class="stack">
      <div class="card">
        <h2>Publication</h2>
        <?= field_checkbox('published', 'Publié sur le site', (bool)$form['published']) ?>
        <?= field_checkbox('is_featured', 'Produit mis en avant (vedette)', (bool)$form['is_featured']) ?>
        <?php if ($product): ?>
          <div class="muted small mt">Créé le <?= e(format_date($product['created_at'], true)) ?> · modifié le <?= e(format_date($product['updated_at'], true)) ?><br>
          <?= (int)$product['sales_count'] ?> vente(s) · <?= (int)$product['views'] ?> vue(s)</div>
        <?php endif; ?>
      </div>

      <div class="card">
        <h2>Prix</h2>
        <?= field_input('price', 'Prix (' . base_currency() . ')', $form['price'], ['required' => true, 'attrs' => ['inputmode' => 'decimal']]) ?>
        <?= field_input('promo_price', 'Prix promotionnel', $form['promo_price'], ['attrs' => ['inputmode' => 'decimal'], 'help' => 'Laisser vide si aucune promotion.']) ?>
        <div class="form-grid">
          <?= field_input('promo_start', 'Début promo', admin_dt_out($form['promo_start']), ['type' => 'datetime-local']) ?>
          <?= field_input('promo_end', 'Fin promo', admin_dt_out($form['promo_end']), ['type' => 'datetime-local']) ?>
        </div>
        <?php if ($decorated && $decorated['old_price']): ?>
          <p class="small">Prix affiché actuellement : <strong><?= e(admin_money($decorated['final_price'])) ?></strong> (−<?= (int)$decorated['discount_percent'] ?> %)</p>
        <?php endif; ?>
      </div>

      <div class="card">
        <div class="card-head"><h2>Stock</h2><?= $decorated ? stock_badge($decorated['stock_status']) : '' ?></div>
        <?= field_input('stock', 'Quantité en stock', $form['stock'], ['type' => 'number', 'attrs' => ['step' => 1], 'help' => 'Toute modification est enregistrée dans l\'historique des mouvements.']) ?>
        <?= field_input('low_stock_threshold', 'Seuil de stock faible', $form['low_stock_threshold'], ['type' => 'number', 'attrs' => ['min' => 0, 'placeholder' => (string)setting('low_stock_threshold', 5)], 'help' => 'Vide = seuil global (' . (int)setting('low_stock_threshold', 5) . ').']) ?>
        <?= field_checkbox('on_order', 'Disponible sur commande quand le stock est épuisé', (bool)$form['on_order']) ?>
        <?php if ($movements): ?>
          <h3 class="mt">Derniers mouvements</h3>
          <?php foreach ($movements as $mv): ?>
            <div class="info-row small"><span class="info-label"><?= e(format_date($mv['created_at'], true)) ?></span><span class="info-value"><strong><?= $mv['qty_change'] > 0 ? '+' : '' ?><?= (int)$mv['qty_change'] ?></strong> → <?= (int)$mv['stock_after'] ?> · <?= e($mv['reason']) ?></span></div>
          <?php endforeach; ?>
          <a class="small" href="<?= e(admin_url('stock.php', ['tab' => 'movements', 'product' => $id])) ?>">Historique complet</a>
        <?php endif; ?>
      </div>

      <div class="card">
        <h2>Logistique</h2>
        <?= field_input('weight', 'Poids', $form['weight'], ['attrs' => ['maxlength' => 40, 'placeholder' => 'ex. 1,2 kg']]) ?>
        <?= field_input('dimensions', 'Dimensions', $form['dimensions'], ['attrs' => ['maxlength' => 80, 'placeholder' => 'ex. 40 × 30 × 20 cm']]) ?>
        <?= field_input('warranty', 'Garantie', $form['warranty'], ['attrs' => ['maxlength' => 120]]) ?>
        <?= field_input('delivery_delay', 'Délai de livraison', $form['delivery_delay'], ['attrs' => ['maxlength' => 120]]) ?>
      </div>
    </div>
  </div>
  <div class="form-actions sticky">
    <button type="submit" class="btn btn-primary"><?= aicon('check') ?> Enregistrer</button>
    <a class="btn" href="<?= e(admin_url('products.php')) ?>">Annuler</a>
  </div>
</form>
<?php require __DIR__ . '/_inc/layout_bottom.php'; ?>
