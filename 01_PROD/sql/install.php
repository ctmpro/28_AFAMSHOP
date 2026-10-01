<?php
/**
 * Installation de la base de données (ligne de commande uniquement).
 *
 *   php sql/install.php                      → schéma + paramètres + catalogue de démonstration
 *   php sql/install.php --no-demo            → schéma + paramètres + catégories/marques (sans produits)
 *   php sql/install.php --admin=email@x.sn --password=MotDePasse1
 *   php sql/install.php --settings-only      → ajoute uniquement les paramètres manquants (mise à jour)
 *
 * Sans CLI (hébergement mutualisé) : importer sql/afamshop.sql via phpMyAdmin,
 * puis ouvrir /admin/setup.php pour créer le premier Super Admin.
 */
if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('CLI uniquement');
}
require dirname(__DIR__) . '/includes/bootstrap.php';
require __DIR__ . '/seed.php';

$opts = getopt('', ['no-demo', 'admin:', 'password:', 'settings-only']);

function insertSettings(array $settings): void
{
    foreach ($settings as $i => [$k, $v, $g, $l, $t]) {
        DB::exec(
            'INSERT INTO settings (skey, svalue, sgroup, label, type, sort) VALUES (:k, :v, :g, :l, :t, :s)
             ON DUPLICATE KEY UPDATE sgroup = VALUES(sgroup), label = VALUES(label), type = VALUES(type), sort = VALUES(sort)',
            ['k' => $k, 'v' => $v, 'g' => $g, 'l' => $l, 't' => $t, 's' => $i]
        );
    }
}

if (isset($opts['settings-only'])) {
    insertSettings($settings);
    echo "Paramètres mis à jour.\n";
    exit;
}

echo "Création du schéma...\n";
$sql = file_get_contents(__DIR__ . '/schema.sql');
foreach (array_filter(array_map('trim', preg_split('/;\s*\n/', preg_replace('/^--.*$/m', '', $sql)))) as $stmt) {
    DB::pdo()->exec($stmt);
}

echo "Paramètres et contenus...\n";
insertSettings($settings);
DB::exec("INSERT INTO settings (skey, svalue, sgroup, label, type) VALUES ('invoice_counter', '0', 'system', 'Compteur de factures', 'number')");

foreach ($currencies as [$code, $name, $symbol, $rate, $dec, $after, $default, $active]) {
    DB::insert('currencies', ['code' => $code, 'name' => $name, 'symbol' => $symbol, 'rate' => $rate, 'decimals' => $dec, 'symbol_after' => $after, 'is_default' => $default, 'active' => $active]);
}

$catIds = [];
foreach ($categories as $i => [$name, $slug, $icon, $image, $children]) {
    $pid = DB::insert('categories', ['name' => $name, 'slug' => $slug, 'icon' => $icon, 'image' => $image, 'sort' => $i, 'description' => null]);
    $catIds[$slug] = $pid;
    foreach ($children as $j => $child) {
        $s = slugify($child);
        $catIds[$s] = DB::insert('categories', ['parent_id' => $pid, 'name' => $child, 'slug' => $s, 'sort' => $j]);
    }
}

$brandIds = [];
foreach ($brands as $i => [$name, $featured]) {
    $brandIds[$name] = DB::insert('brands', ['name' => $name, 'slug' => slugify($name), 'featured' => $featured, 'sort' => $i]);
}

$modelIds = [];
foreach ($printerModels as $brand => $models) {
    foreach ($models as $name => $series) {
        $modelIds[$name] = DB::insert('printer_models', ['brand_id' => $brandIds[$brand], 'name' => $name, 'series' => $series, 'slug' => slugify($brand . ' ' . $name)]);
    }
}

foreach ($zones as [$name, $fee, $free, $delay, $sort]) {
    DB::insert('delivery_zones', ['name' => $name, 'fee' => $fee, 'free_threshold' => $free, 'delay' => $delay, 'sort' => $sort]);
}
foreach ($pages as [$slug, $title, $content, $group, $sort]) {
    DB::insert('pages', ['slug' => $slug, 'title' => $title, 'content' => $content, 'footer_group' => $group, 'sort' => $sort]);
}
foreach ($services as [$slug, $title, $icon, $short, $content, $form, $sort, $image]) {
    DB::insert('services', ['slug' => $slug, 'title' => $title, 'icon' => $icon, 'short_desc' => $short, 'content' => $content, 'form_type' => $form, 'sort' => $sort, 'image' => $image]);
}
foreach ($banners as [$pos, $title, $sub, $btn, $link, $sort, $image]) {
    DB::insert('banners', ['position' => $pos, 'title' => $title, 'subtitle' => $sub, 'button_text' => $btn, 'link' => $link, 'sort' => $sort, 'image_desktop' => $image]);
}

if (!isset($opts['no-demo'])) {
    echo "Catalogue de démonstration...\n";
    foreach ($products as $i => [$sku, $ref, $name, $brand, $cat, $type, $color, $price, $promo, $stock, $short, $compat, $featured]) {
        $pid = DB::insert('products', [
            'sku' => $sku,
            'manufacturer_ref' => $ref,
            'name' => $name,
            'slug' => unique_slug('products', $name),
            'brand_id' => $brand ? $brandIds[$brand] : null,
            'category_id' => $catIds[$cat] ?? null,
            'product_type' => $type,
            'color' => $color,
            'short_description' => $short,
            'description' => '<p>' . htmlspecialchars($short) . '</p><p>Produit garanti, livré par ' . 'AFAM' . '. Contactez-nous pour un tarif professionnel ou un achat en volume.</p>',
            'specs' => "Marque: " . ($brand ?: 'Générique') . "\nRéférence: $ref" . ($color ? "\nCouleur: $color" : '') . "\nType: $type",
            'price' => $price,
            'promo_price' => $promo,
            'stock' => $stock,
            'warranty' => '1 an',
            'delivery_delay' => '24 à 72 h à Dakar',
            'is_featured' => $featured,
            'sales_count' => random_int(0, 60),
        ]);
        if ($stock > 0) {
            DB::insert('stock_movements', ['product_id' => $pid, 'qty_change' => $stock, 'stock_after' => $stock, 'reason' => 'import', 'note' => 'Stock initial']);
        }
        foreach ($compat as $m) {
            DB::insert('product_compatibility', ['product_id' => $pid, 'printer_model_id' => $modelIds[$m]]);
        }
    }
    DB::insert('coupons', ['code' => 'BIENVENUE10', 'discount_type' => 'percent', 'value' => 10, 'min_amount' => 20000, 'per_customer_limit' => 1]);
    DB::insert('promotions', ['name' => 'Promo papeterie', 'discount_type' => 'percent', 'value' => 5, 'scope' => 'category', 'target_id' => $catIds['papeterie']]);
}

// Une installation neuve intègre déjà le contenu des migrations existantes
foreach (Migrator::files() as $name => $file) {
    Migrator::markApplied($name);
}

if (!empty($opts['admin']) && !empty($opts['password'])) {
    DB::insert('admins', [
        'name' => 'Super Admin',
        'email' => strtolower($opts['admin']),
        'password_hash' => password_hash($opts['password'], PASSWORD_DEFAULT),
        'role' => 'super_admin',
    ]);
    echo "Super Admin créé : {$opts['admin']}\n";
} else {
    echo "Aucun administrateur créé : ouvrez /admin/setup.php pour créer le premier Super Admin.\n";
}
echo "Installation terminée.\n";
