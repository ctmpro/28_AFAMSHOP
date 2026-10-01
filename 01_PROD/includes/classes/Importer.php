<?php
/**
 * Import / export massif du catalogue (CSV ou Excel .xlsx).
 */
class Importer
{
    /** Colonnes reconnues (en-têtes acceptés => champ). */
    public const COLUMNS = [
        'sku' => 'sku', 'reference' => 'manufacturer_ref', 'ref' => 'manufacturer_ref', 'reference_constructeur' => 'manufacturer_ref',
        'nom' => 'name', 'name' => 'name', 'designation' => 'name',
        'marque' => 'brand', 'brand' => 'brand',
        'categorie' => 'category', 'category' => 'category',
        'prix' => 'price', 'price' => 'price',
        'prix_promo' => 'promo_price', 'promo' => 'promo_price',
        'stock' => 'stock', 'quantite' => 'stock',
        'description' => 'description', 'description_courte' => 'short_description',
        'type' => 'product_type', 'couleur' => 'color', 'color' => 'color',
        'compatibilites' => 'compat', 'compatibilite' => 'compat', 'modeles' => 'compat',
        'poids' => 'weight', 'dimensions' => 'dimensions', 'garantie' => 'warranty', 'delai_livraison' => 'delivery_delay',
        'publie' => 'published', 'published' => 'published', 'image' => 'image',
    ];

    public const EXPORT_HEADERS = ['sku', 'reference', 'nom', 'marque', 'categorie', 'prix', 'prix_promo', 'stock', 'type', 'couleur', 'description_courte', 'description', 'compatibilites', 'poids', 'dimensions', 'garantie', 'delai_livraison', 'publie'];

    /** Lit un fichier et retourne les lignes (tableaux indexés). */
    public static function readFile(string $path, string $originalName): array
    {
        $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
        if ($ext === 'xlsx') {
            return self::readXlsx($path);
        }
        if (!in_array($ext, ['csv', 'txt'], true)) {
            throw new RuntimeException('Format non supporté : utilisez .csv ou .xlsx');
        }
        $content = file_get_contents($path);
        $content = preg_replace('/^\xEF\xBB\xBF/', '', $content);
        if (!mb_check_encoding($content, 'UTF-8')) {
            $content = mb_convert_encoding($content, 'UTF-8', 'Windows-1252');
        }
        $firstLine = strtok($content, "\n");
        $sep = substr_count($firstLine, ';') >= substr_count($firstLine, ',') ? ';' : ',';
        if (substr_count($firstLine, "\t") > substr_count($firstLine, $sep)) $sep = "\t";
        $fh = fopen('php://temp', 'r+');
        fwrite($fh, $content);
        rewind($fh);
        $rows = [];
        while (($r = fgetcsv($fh, 0, $sep, '"', '\\')) !== false) {
            if ($r === [null]) continue;
            $rows[] = $r;
        }
        fclose($fh);
        return $rows;
    }

    private static function readXlsx(string $path): array
    {
        $zip = new ZipArchive();
        if ($zip->open($path) !== true) {
            throw new RuntimeException('Fichier Excel illisible');
        }
        $shared = [];
        if (($xml = $zip->getFromName('xl/sharedStrings.xml')) !== false) {
            $sx = simplexml_load_string($xml);
            foreach ($sx->si as $si) {
                if (isset($si->t)) {
                    $shared[] = (string)$si->t;
                } else {
                    $s = '';
                    foreach ($si->r as $r) $s .= (string)$r->t;
                    $shared[] = $s;
                }
            }
        }
        $sheet = $zip->getFromName('xl/worksheets/sheet1.xml');
        $zip->close();
        if ($sheet === false) {
            throw new RuntimeException('Feuille Excel introuvable');
        }
        $sx = simplexml_load_string($sheet);
        $rows = [];
        foreach ($sx->sheetData->row as $row) {
            $r = [];
            foreach ($row->c as $c) {
                $ref = (string)$c['r'];
                $col = 0;
                foreach (str_split(preg_replace('/\d+/', '', $ref)) as $ch) {
                    $col = $col * 26 + (ord($ch) - 64);
                }
                $type = (string)$c['t'];
                if ($type === 's') {
                    $v = $shared[(int)$c->v] ?? '';
                } elseif ($type === 'inlineStr') {
                    $v = (string)$c->is->t;
                } else {
                    $v = (string)$c->v;
                }
                $r[$col - 1] = $v;
            }
            if ($r) {
                $max = max(array_keys($r));
                $full = [];
                for ($i = 0; $i <= $max; $i++) $full[] = $r[$i] ?? '';
                $rows[] = $full;
            }
        }
        return $rows;
    }

    private static function num($v): ?float
    {
        $v = trim((string)$v);
        if ($v === '') return null;
        $v = str_replace([' ', "\u{00A0}", "\u{202F}"], '', $v);
        if (str_contains($v, ',') && !str_contains($v, '.')) $v = str_replace(',', '.', $v);
        else $v = str_replace(',', '', $v);
        return is_numeric($v) ? (float)$v : null;
    }

    /**
     * Importe les lignes. $dryRun = validation seule.
     * Retourne ['created'=>n,'updated'=>n,'errors'=>[[ligne,message]], 'total'=>n]
     */
    public static function import(array $rows, bool $dryRun = false, bool $createRefs = true): array
    {
        $report = ['created' => 0, 'updated' => 0, 'skipped' => 0, 'errors' => [], 'total' => 0];
        if (count($rows) < 2) {
            $report['errors'][] = [0, 'Fichier vide ou sans ligne de données.'];
            return $report;
        }
        $map = [];
        foreach ($rows[0] as $i => $h) {
            $key = slugify((string)$h);
            $key = str_replace('-', '_', $key);
            if (isset(self::COLUMNS[$key])) $map[$i] = self::COLUMNS[$key];
        }
        if (!in_array('sku', $map, true) || !in_array('name', $map, true)) {
            $report['errors'][] = [1, 'Les colonnes "sku" et "nom" sont obligatoires.'];
            return $report;
        }
        $brands = [];
        foreach (DB::all('SELECT id, name FROM brands') as $b) $brands[mb_strtolower($b['name'])] = (int)$b['id'];
        $cats = [];
        foreach (DB::all('SELECT id, name, slug FROM categories') as $c) {
            $cats[mb_strtolower($c['name'])] = (int)$c['id'];
            $cats[$c['slug']] = (int)$c['id'];
        }

        foreach (array_slice($rows, 1) as $n => $row) {
            $line = $n + 2;
            $d = [];
            foreach ($map as $i => $field) $d[$field] = trim((string)($row[$i] ?? ''));
            if (implode('', $d) === '') continue;
            $report['total']++;
            $errs = [];
            if ($d['sku'] === '') $errs[] = 'SKU manquant';
            elseif (!preg_match('/^[A-Za-z0-9._\-\/]{1,80}$/', $d['sku'])) $errs[] = 'SKU invalide';
            if ($d['name'] === '') $errs[] = 'Nom manquant';
            $price = isset($d['price']) ? self::num($d['price']) : null;
            if (isset($d['price']) && $d['price'] !== '' && ($price === null || $price < 0)) $errs[] = 'Prix invalide';
            $promo = isset($d['promo_price']) ? self::num($d['promo_price']) : null;
            if (isset($d['promo_price']) && $d['promo_price'] !== '' && $promo === null) $errs[] = 'Prix promo invalide';
            $stock = isset($d['stock']) && $d['stock'] !== '' ? self::num($d['stock']) : null;
            if (isset($d['stock']) && $d['stock'] !== '' && $stock === null) $errs[] = 'Stock invalide';

            $brandId = null;
            if (!empty($d['brand'])) {
                $brandId = $brands[mb_strtolower($d['brand'])] ?? null;
                if (!$brandId) {
                    if ($createRefs && !$dryRun) {
                        $brandId = DB::insert('brands', ['name' => $d['brand'], 'slug' => unique_slug('brands', $d['brand'])]);
                        $brands[mb_strtolower($d['brand'])] = $brandId;
                    } elseif (!$createRefs) {
                        $errs[] = 'Marque inconnue : ' . $d['brand'];
                    }
                }
            }
            $catId = null;
            if (!empty($d['category'])) {
                $catId = $cats[mb_strtolower($d['category'])] ?? $cats[slugify($d['category'])] ?? null;
                if (!$catId) {
                    if ($createRefs && !$dryRun) {
                        $catId = DB::insert('categories', ['name' => $d['category'], 'slug' => unique_slug('categories', $d['category'])]);
                        $cats[mb_strtolower($d['category'])] = $catId;
                    } elseif (!$createRefs) {
                        $errs[] = 'Catégorie inconnue : ' . $d['category'];
                    }
                }
            }
            if ($errs) {
                $report['errors'][] = [$line, implode(' ; ', $errs)];
                continue;
            }

            $existing = DB::one('SELECT id, stock FROM products WHERE sku = :s', ['s' => $d['sku']]);
            if ($dryRun) {
                $existing ? $report['updated']++ : $report['created']++;
                continue;
            }
            $data = ['name' => $d['name']];
            foreach (['manufacturer_ref', 'product_type', 'color', 'short_description', 'description', 'weight', 'dimensions', 'warranty', 'delivery_delay'] as $f) {
                if (isset($d[$f])) $data[$f] = $d[$f] !== '' ? $d[$f] : null;
            }
            if ($price !== null) $data['price'] = $price;
            if (isset($d['promo_price'])) $data['promo_price'] = $promo ?: null;
            if ($brandId) $data['brand_id'] = $brandId;
            if ($catId) $data['category_id'] = $catId;
            if (isset($d['published']) && $d['published'] !== '') {
                $data['published'] = in_array(mb_strtolower($d['published']), ['1', 'oui', 'yes', 'true', 'x'], true) ? 1 : 0;
            }
            try {
                if ($existing) {
                    DB::update('products', $data, 'id = :id', ['id' => $existing['id']]);
                    $pid = (int)$existing['id'];
                    if ($stock !== null) Stock::set($pid, (int)$stock, 'import');
                    $report['updated']++;
                } else {
                    $data['sku'] = $d['sku'];
                    $data['slug'] = unique_slug('products', $d['name']);
                    $data['price'] = $data['price'] ?? 0;
                    $data['stock'] = 0;
                    $pid = DB::insert('products', $data);
                    if ($stock) Stock::adjust($pid, (int)$stock, 'import');
                    $report['created']++;
                }
                if (!empty($d['compat'])) {
                    self::linkCompat($pid, $d['compat'], $brandId);
                }
                if (!empty($d['image']) && preg_match('#^https?://#', $d['image'])
                    && !DB::val('SELECT id FROM product_images WHERE product_id = :p AND path = :i', ['p' => $pid, 'i' => $d['image']])) {
                    $hasMain = (int)DB::val('SELECT COUNT(*) FROM product_images WHERE product_id = :p', ['p' => $pid]);
                    DB::insert('product_images', ['product_id' => $pid, 'path' => $d['image'], 'is_main' => $hasMain ? 0 : 1]);
                }
            } catch (Throwable $e) {
                $report['errors'][] = [$line, 'Erreur base de données : ' . $e->getMessage()];
            }
        }
        return $report;
    }

    /** "Canon iR-ADV C3530|Canon iR-ADV C3525" → liaisons de compatibilité (crée les modèles si besoin). */
    public static function linkCompat(int $productId, string $list, ?int $defaultBrandId): void
    {
        $brands = [];
        foreach (DB::all('SELECT id, name FROM brands') as $b) $brands[mb_strtolower($b['name'])] = (int)$b['id'];
        foreach (array_filter(array_map('trim', preg_split('/[|\n]/', $list))) as $model) {
            $brandId = $defaultBrandId;
            $name = $model;
            $first = mb_strtolower(strtok($model, ' '));
            if (isset($brands[$first])) {
                $brandId = $brands[$first];
                $name = trim(mb_substr($model, mb_strlen($first)));
            }
            if (!$brandId || $name === '') continue;
            $mid = DB::val('SELECT id FROM printer_models WHERE brand_id = :b AND name = :n', ['b' => $brandId, 'n' => $name]);
            if (!$mid) {
                $bname = array_search($brandId, $brands, true) ?: '';
                $mid = DB::insert('printer_models', ['brand_id' => $brandId, 'name' => $name, 'slug' => unique_slug('printer_models', $bname . ' ' . $name)]);
            }
            DB::exec('INSERT IGNORE INTO product_compatibility (product_id, printer_model_id) VALUES (:p, :m)', ['p' => $productId, 'm' => $mid]);
        }
    }

    /** Export CSV (séparateur ;, UTF-8 avec BOM pour Excel). */
    public static function exportCsv(): void
    {
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="catalogue-' . date('Ymd-His') . '.csv"');
        $out = fopen('php://output', 'w');
        fwrite($out, "\xEF\xBB\xBF");
        fputcsv($out, self::EXPORT_HEADERS, ';', '"', '\\');
        $stmt = DB::query(
            'SELECT p.*, b.name AS brand_name, c.name AS category_name,
                (SELECT GROUP_CONCAT(CONCAT(mb.name, " ", m.name) SEPARATOR "|") FROM product_compatibility pc
                   JOIN printer_models m ON m.id = pc.printer_model_id JOIN brands mb ON mb.id = m.brand_id WHERE pc.product_id = p.id) AS compat
             FROM products p LEFT JOIN brands b ON b.id = p.brand_id LEFT JOIN categories c ON c.id = p.category_id ORDER BY p.id'
        );
        while ($p = $stmt->fetch()) {
            fputcsv($out, [
                $p['sku'], $p['manufacturer_ref'], $p['name'], $p['brand_name'], $p['category_name'],
                $p['price'], $p['promo_price'], $p['stock'], $p['product_type'], $p['color'],
                $p['short_description'], $p['description'], $p['compat'], $p['weight'], $p['dimensions'],
                $p['warranty'], $p['delivery_delay'], $p['published'] ? 'oui' : 'non',
            ], ';', '"', '\\');
        }
        fclose($out);
        exit;
    }
}
