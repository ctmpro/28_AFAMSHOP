<?php
/**
 * Logique métier du catalogue : produits, prix effectifs, stock, recherche, filtres.
 */
class Catalog
{
    /** Expression SQL du prix effectif (prix promo dans sa période de validité). */
    public const PRICE_SQL = "(CASE WHEN p.promo_price IS NOT NULL AND p.promo_price > 0
        AND (p.promo_start IS NULL OR p.promo_start <= NOW())
        AND (p.promo_end IS NULL OR p.promo_end >= NOW()) THEN p.promo_price ELSE p.price END)";

    private static ?array $promotions = null;

    // -----------------------------------------------------------------
    // Prix et promotions
    // -----------------------------------------------------------------
    public static function activePromotions(): array
    {
        if (self::$promotions === null) {
            self::$promotions = DB::all(
                'SELECT * FROM promotions WHERE active = 1
                 AND (start_at IS NULL OR start_at <= NOW()) AND (end_at IS NULL OR end_at >= NOW())'
            );
        }
        return self::$promotions;
    }

    /**
     * Ajoute à chaque produit : final_price, old_price (si réduction), discount_percent,
     * stock_status, main_image.
     */
    public static function decorate(array $products): array
    {
        if (!$products) {
            return [];
        }
        $ids = array_column($products, 'id');
        $params = [];
        $images = [];
        foreach (DB::all('SELECT product_id, path FROM product_images WHERE product_id IN ' . DB::in($ids, $params) . ' ORDER BY is_main DESC, sort, id', $params) as $img) {
            $images[$img['product_id']] ??= $img['path'];
        }
        $now = time();
        foreach ($products as &$p) {
            $price = (float)$p['price'];
            $final = $price;
            if ($p['promo_price'] !== null && (float)$p['promo_price'] > 0
                && (!$p['promo_start'] || strtotime($p['promo_start']) <= $now)
                && (!$p['promo_end'] || strtotime($p['promo_end']) >= $now)) {
                $final = min($final, (float)$p['promo_price']);
            }
            foreach (self::activePromotions() as $promo) {
                $match = match ($promo['scope']) {
                    'all' => true,
                    'product' => (int)$promo['target_id'] === (int)$p['id'],
                    'category' => in_array((int)$promo['target_id'], self::categoryAncestors((int)$p['category_id']), true),
                    'brand' => (int)$promo['target_id'] === (int)$p['brand_id'],
                    default => false,
                };
                if ($match) {
                    $v = $promo['discount_type'] === 'percent'
                        ? $price * (1 - min(100, (float)$promo['value']) / 100)
                        : $price - (float)$promo['value'];
                    $final = min($final, max(0, $v));
                }
            }
            $final = round_money($final);
            $p['final_price'] = $final;
            $p['old_price'] = $final < $price ? $price : null;
            $p['discount_percent'] = ($final < $price && $price > 0) ? (int)round((1 - $final / $price) * 100) : 0;
            $p['stock_status'] = self::stockStatus($p);
            $p['main_image'] = $images[$p['id']] ?? null;
        }
        return $products;
    }

    public static function lowThreshold(array $p): int
    {
        return $p['low_stock_threshold'] !== null && $p['low_stock_threshold'] !== ''
            ? (int)$p['low_stock_threshold']
            : (int)setting('low_stock_threshold', 5);
    }

    /** in_stock, low_stock, out_of_stock, on_order */
    public static function stockStatus(array $p): string
    {
        $stock = (int)$p['stock'];
        if ($stock <= 0) {
            return !empty($p['on_order']) ? 'on_order' : 'out_of_stock';
        }
        return $stock <= self::lowThreshold($p) ? 'low_stock' : 'in_stock';
    }

    public static function stockLabel(string $status): string
    {
        return __('stock_' . $status);
    }

    public static function canBuy(array $p, int $qty = 1): bool
    {
        if (!$p['published']) {
            return false;
        }
        if ((int)$p['stock'] >= $qty) {
            return true;
        }
        return !empty($p['on_order']) || setting_bool('allow_backorders');
    }

    // -----------------------------------------------------------------
    // Produits
    // -----------------------------------------------------------------
    private const BASE_SELECT = 'SELECT p.*, b.name AS brand_name, b.slug AS brand_slug, c.name AS category_name, c.slug AS category_slug
        FROM products p
        LEFT JOIN brands b ON b.id = p.brand_id
        LEFT JOIN categories c ON c.id = p.category_id';

    public static function product(string $slug): ?array
    {
        $p = DB::one(self::BASE_SELECT . ' WHERE p.slug = :s AND p.published = 1', ['s' => $slug]);
        return $p ? self::decorate([$p])[0] : null;
    }

    public static function productById(int $id, bool $onlyPublished = true): ?array
    {
        $p = DB::one(self::BASE_SELECT . ' WHERE p.id = :id' . ($onlyPublished ? ' AND p.published = 1' : ''), ['id' => $id]);
        return $p ? self::decorate([$p])[0] : null;
    }

    public static function productsByIds(array $ids, bool $onlyPublished = true): array
    {
        if (!$ids) {
            return [];
        }
        $params = [];
        $rows = DB::all(self::BASE_SELECT . ' WHERE p.id IN ' . DB::in($ids, $params) . ($onlyPublished ? ' AND p.published = 1' : ''), $params);
        $rows = self::decorate($rows);
        $byId = [];
        foreach ($rows as $r) {
            $byId[$r['id']] = $r;
        }
        return $byId;
    }

    public static function images(int $productId): array
    {
        return DB::all('SELECT * FROM product_images WHERE product_id = :id ORDER BY is_main DESC, sort, id', ['id' => $productId]);
    }

    public static function compatibleModels(int $productId): array
    {
        return DB::all(
            'SELECT m.*, b.name AS brand_name FROM product_compatibility pc
             JOIN printer_models m ON m.id = pc.printer_model_id
             JOIN brands b ON b.id = m.brand_id
             WHERE pc.product_id = :id ORDER BY b.name, m.name',
            ['id' => $productId]
        );
    }

    /** Lignes "Clé: valeur" → tableau associatif. */
    public static function parseSpecs(?string $specs): array
    {
        $out = [];
        foreach (preg_split('/\r?\n/', (string)$specs) as $line) {
            $line = trim($line);
            if ($line === '') continue;
            if (str_contains($line, ':')) {
                [$k, $v] = array_map('trim', explode(':', $line, 2));
                $out[$k] = $v;
            } else {
                $out[$line] = '';
            }
        }
        return $out;
    }

    public static function reviews(int $productId): array
    {
        return DB::all("SELECT * FROM reviews WHERE product_id = :id AND status = 'approved' ORDER BY created_at DESC", ['id' => $productId]);
    }

    public static function rating(int $productId): array
    {
        $r = DB::one("SELECT COUNT(*) n, AVG(rating) avg FROM reviews WHERE product_id = :id AND status = 'approved'", ['id' => $productId]);
        return ['count' => (int)$r['n'], 'avg' => $r['avg'] ? round((float)$r['avg'], 1) : 0];
    }

    public static function related(array $product, int $limit = 8): array
    {
        $rows = DB::all(
            self::BASE_SELECT . ' WHERE p.published = 1 AND p.id <> :id AND (p.category_id = :c OR p.brand_id = :b)
             ORDER BY (p.category_id = :c2) DESC, p.sales_count DESC LIMIT ' . (int)$limit,
            ['id' => $product['id'], 'c' => $product['category_id'], 'b' => $product['brand_id'], 'c2' => $product['category_id']]
        );
        return self::decorate($rows);
    }

    public static function featured(int $limit = 8): array
    {
        return self::decorate(DB::all(self::BASE_SELECT . ' WHERE p.published = 1 ORDER BY p.is_featured DESC, p.sales_count DESC, p.id DESC LIMIT ' . (int)$limit));
    }

    public static function onSale(int $limit = 8): array
    {
        $res = self::search(['promo' => 1, 'sort' => 'relevance'], 1, $limit);
        return $res['items'];
    }

    public static function newest(int $limit = 8): array
    {
        return self::decorate(DB::all(self::BASE_SELECT . ' WHERE p.published = 1 ORDER BY p.created_at DESC, p.id DESC LIMIT ' . (int)$limit));
    }

    // -----------------------------------------------------------------
    // Catégories et marques
    // -----------------------------------------------------------------
    public static function categories(bool $onlyActive = true): array
    {
        static $cache = [];
        $k = (int)$onlyActive;
        if (!isset($cache[$k])) {
            $cache[$k] = DB::all('SELECT * FROM categories' . ($onlyActive ? ' WHERE active = 1' : '') . ' ORDER BY sort, name');
        }
        return $cache[$k];
    }

    /** Arbre : catégories racines avec 'children'. */
    public static function categoryTree(bool $onlyActive = true): array
    {
        $all = self::categories($onlyActive);
        $children = [];
        foreach ($all as $c) {
            $children[(int)$c['parent_id']][] = $c;
        }
        $build = function (int $parent) use (&$build, $children) {
            $out = [];
            foreach ($children[$parent] ?? [] as $c) {
                $c['children'] = $build((int)$c['id']);
                $out[] = $c;
            }
            return $out;
        };
        return $build(0);
    }

    public static function category(string $slug): ?array
    {
        return DB::one('SELECT * FROM categories WHERE slug = :s AND active = 1', ['s' => $slug]);
    }

    public static function categoryDescendants(int $id): array
    {
        $all = self::categories(false);
        $ids = [$id];
        $changed = true;
        while ($changed) {
            $changed = false;
            foreach ($all as $c) {
                if (in_array((int)$c['parent_id'], $ids, true) && !in_array((int)$c['id'], $ids, true)) {
                    $ids[] = (int)$c['id'];
                    $changed = true;
                }
            }
        }
        return $ids;
    }

    public static function categoryAncestors(int $id): array
    {
        $byId = [];
        foreach (self::categories(false) as $c) {
            $byId[(int)$c['id']] = $c;
        }
        $out = [];
        while ($id && isset($byId[$id]) && !in_array($id, $out, true)) {
            $out[] = $id;
            $id = (int)$byId[$id]['parent_id'];
        }
        return $out;
    }

    public static function breadcrumb(int $categoryId): array
    {
        $byId = [];
        foreach (self::categories(false) as $c) {
            $byId[(int)$c['id']] = $c;
        }
        return array_reverse(array_map(fn($id) => $byId[$id], self::categoryAncestors($categoryId)));
    }

    public static function brands(bool $onlyActive = true): array
    {
        return DB::all('SELECT * FROM brands' . ($onlyActive ? ' WHERE active = 1' : '') . ' ORDER BY featured DESC, sort, name');
    }

    public static function brand(string $slug): ?array
    {
        return DB::one('SELECT * FROM brands WHERE slug = :s AND active = 1', ['s' => $slug]);
    }

    /** Marques possédant des modèles d'imprimantes (recherche par imprimante). */
    public static function printerBrands(): array
    {
        return DB::all('SELECT DISTINCT b.id, b.name, b.slug FROM brands b JOIN printer_models m ON m.brand_id = b.id AND m.active = 1 WHERE b.active = 1 ORDER BY b.name');
    }

    public static function printerModels(int $brandId): array
    {
        return DB::all('SELECT id, name, slug, series FROM printer_models WHERE brand_id = :b AND active = 1 ORDER BY series, name', ['b' => $brandId]);
    }

    public static function printerModel(string $slug): ?array
    {
        return DB::one('SELECT m.*, b.name AS brand_name, b.slug AS brand_slug FROM printer_models m JOIN brands b ON b.id = m.brand_id WHERE m.slug = :s AND m.active = 1', ['s' => $slug]);
    }

    // -----------------------------------------------------------------
    // Recherche / listing avec filtres
    // -----------------------------------------------------------------
    /**
     * $f : q, category_id, brand (array d'ids), price_min, price_max, availability (in_stock|on_order),
     *      type, color, model (id modèle imprimante), promo (bool), sort.
     */
    public static function search(array $f, int $page = 1, int $perPage = 24): array
    {
        [$where, $params] = self::buildWhere($f);
        $from = ' FROM products p LEFT JOIN brands b ON b.id = p.brand_id LEFT JOIN categories c ON c.id = p.category_id';
        $whereSql = ' WHERE ' . implode(' AND ', $where);

        $total = (int)DB::val('SELECT COUNT(*)' . $from . $whereSql, $params);
        $pg = paginate($total, $perPage, $page);

        $order = match ($f['sort'] ?? 'relevance') {
            'price_asc' => self::PRICE_SQL . ' ASC',
            'price_desc' => self::PRICE_SQL . ' DESC',
            'newest' => 'p.created_at DESC, p.id DESC',
            'bestsellers' => 'p.sales_count DESC',
            'name' => 'p.name ASC',
            default => (!empty($f['q']) ? self::relevanceSql($f['q'], $params) . ' DESC, ' : '') . 'p.is_featured DESC, (p.stock > 0) DESC, p.sales_count DESC, p.id DESC',
        };

        $rows = DB::all(
            'SELECT p.*, b.name AS brand_name, b.slug AS brand_slug, c.name AS category_name, c.slug AS category_slug'
            . $from . $whereSql . ' ORDER BY ' . $order . ' LIMIT ' . (int)$pg['per_page'] . ' OFFSET ' . (int)$pg['offset'],
            $params
        );

        return ['items' => self::decorate($rows), 'pagination' => $pg, 'facets' => self::facets($f)];
    }

    private static function relevanceSql(string $q, array &$params): string
    {
        $params['rel_exact'] = $q;
        $params['rel_start'] = $q . '%';
        return '((p.sku = :rel_exact OR p.manufacturer_ref = :rel_exact) * 10 + (p.name LIKE :rel_start) * 3)';
    }

    private static function buildWhere(array $f, array $skip = []): array
    {
        $where = ['p.published = 1'];
        $params = [];

        if (!empty($f['q'])) {
            $words = array_slice(array_filter(preg_split('/\s+/', mb_substr($f['q'], 0, 100))), 0, 6);
            foreach ($words as $i => $w) {
                $params["q$i"] = '%' . $w . '%';
                $where[] = "(p.name LIKE :q$i OR p.sku LIKE :q$i OR p.manufacturer_ref LIKE :q$i OR b.name LIKE :q$i
                    OR p.product_type LIKE :q$i OR p.short_description LIKE :q$i
                    OR EXISTS (SELECT 1 FROM product_compatibility pc JOIN printer_models m ON m.id = pc.printer_model_id
                               WHERE pc.product_id = p.id AND m.name LIKE :q$i))";
            }
        }
        if (!empty($f['category_id'])) {
            $where[] = 'p.category_id IN ' . DB::in(self::categoryDescendants((int)$f['category_id']), $params, 'cat');
        }
        if (!in_array('brand', $skip, true) && !empty($f['brand'])) {
            $where[] = 'p.brand_id IN ' . DB::in(array_map('intval', (array)$f['brand']), $params, 'br');
        }
        if (!empty($f['brand_id'])) {
            $where[] = 'p.brand_id = :brand_id';
            $params['brand_id'] = (int)$f['brand_id'];
        }
        if (isset($f['price_min']) && $f['price_min'] !== '') {
            $where[] = self::PRICE_SQL . ' >= :pmin';
            $params['pmin'] = (float)$f['price_min'];
        }
        if (isset($f['price_max']) && $f['price_max'] !== '') {
            $where[] = self::PRICE_SQL . ' <= :pmax';
            $params['pmax'] = (float)$f['price_max'];
        }
        if (!in_array('availability', $skip, true) && !empty($f['availability'])) {
            if ($f['availability'] === 'in_stock') {
                $where[] = 'p.stock > 0';
            } elseif ($f['availability'] === 'on_order') {
                $where[] = 'p.stock <= 0 AND p.on_order = 1';
            }
        }
        if (!in_array('type', $skip, true) && !empty($f['type'])) {
            $where[] = 'p.product_type IN ' . DB::in((array)$f['type'], $params, 'ty');
        }
        if (!in_array('color', $skip, true) && !empty($f['color'])) {
            $where[] = 'p.color IN ' . DB::in((array)$f['color'], $params, 'co');
        }
        if (!empty($f['model'])) {
            $where[] = 'EXISTS (SELECT 1 FROM product_compatibility pc2 WHERE pc2.product_id = p.id AND pc2.printer_model_id = :model)';
            $params['model'] = (int)$f['model'];
        }
        if (!empty($f['promo'])) {
            $conds = ['(p.promo_price IS NOT NULL AND p.promo_price > 0 AND p.promo_price < p.price
                AND (p.promo_start IS NULL OR p.promo_start <= NOW()) AND (p.promo_end IS NULL OR p.promo_end >= NOW()))'];
            foreach (self::activePromotions() as $i => $promo) {
                switch ($promo['scope']) {
                    case 'all':
                        $conds[] = '1=1';
                        break;
                    case 'product':
                        $conds[] = "p.id = :pr$i";
                        $params["pr$i"] = (int)$promo['target_id'];
                        break;
                    case 'brand':
                        $conds[] = "p.brand_id = :pr$i";
                        $params["pr$i"] = (int)$promo['target_id'];
                        break;
                    case 'category':
                        $conds[] = 'p.category_id IN ' . DB::in(self::categoryDescendants((int)$promo['target_id']), $params, "prc{$i}_");
                        break;
                }
            }
            $where[] = '(' . implode(' OR ', $conds) . ')';
        }
        return [$where, $params];
    }

    /** Valeurs disponibles pour les filtres (marques, types, couleurs, disponibilité, prix). */
    public static function facets(array $f): array
    {
        $from = ' FROM products p LEFT JOIN brands b ON b.id = p.brand_id LEFT JOIN categories c ON c.id = p.category_id';

        [$w, $pa] = self::buildWhere($f, ['brand']);
        $brands = DB::all('SELECT b.id, b.name, COUNT(*) n' . $from . ' WHERE ' . implode(' AND ', $w) . ' AND b.id IS NOT NULL GROUP BY b.id, b.name ORDER BY b.name', $pa);

        [$w, $pa] = self::buildWhere($f, ['type']);
        $types = DB::all("SELECT p.product_type AS v, COUNT(*) n" . $from . ' WHERE ' . implode(' AND ', $w) . " AND p.product_type IS NOT NULL AND p.product_type <> '' GROUP BY p.product_type ORDER BY p.product_type", $pa);

        [$w, $pa] = self::buildWhere($f, ['color']);
        $colors = DB::all("SELECT p.color AS v, COUNT(*) n" . $from . ' WHERE ' . implode(' AND ', $w) . " AND p.color IS NOT NULL AND p.color <> '' GROUP BY p.color ORDER BY p.color", $pa);

        $fp = $f;
        unset($fp['price_min'], $fp['price_max']);
        [$w, $pa] = self::buildWhere($fp);
        $range = DB::one('SELECT MIN(' . self::PRICE_SQL . ') mn, MAX(' . self::PRICE_SQL . ') mx' . $from . ' WHERE ' . implode(' AND ', $w), $pa);

        return [
            'brands' => $brands,
            'types' => $types,
            'colors' => $colors,
            'price_min' => (float)($range['mn'] ?? 0),
            'price_max' => (float)($range['mx'] ?? 0),
        ];
    }

    /** Suggestions pour la recherche AJAX. */
    public static function suggest(string $q, int $limit = 8): array
    {
        $q = trim($q);
        if (mb_strlen($q) < 2) {
            return ['products' => [], 'models' => [], 'categories' => []];
        }
        $res = self::search(['q' => $q, 'sort' => 'relevance'], 1, $limit);
        $products = array_map(fn($p) => [
            'name' => $p['name'],
            'sku' => $p['sku'],
            'ref' => $p['manufacturer_ref'],
            'brand' => $p['brand_name'],
            'price' => money($p['final_price']),
            'image' => media_url($p['main_image']),
            'url' => url('produit/' . $p['slug']),
        ], $res['items']);
        $like = '%' . $q . '%';
        $models = array_map(fn($m) => [
            'name' => $m['brand_name'] . ' ' . $m['name'],
            'url' => url('recherche-imprimante', ['modele' => $m['slug']]),
        ], DB::all('SELECT m.name, m.slug, b.name brand_name FROM printer_models m JOIN brands b ON b.id = m.brand_id WHERE m.active = 1 AND (m.name LIKE :q OR CONCAT(b.name, " ", m.name) LIKE :q) ORDER BY m.name LIMIT 5', ['q' => $like]));
        $cats = array_map(fn($c) => [
            'name' => $c['name'],
            'url' => url('categorie/' . $c['slug']),
        ], DB::all('SELECT name, slug FROM categories WHERE active = 1 AND name LIKE :q ORDER BY name LIMIT 4', ['q' => $like]));
        return ['products' => $products, 'models' => $models, 'categories' => $cats, 'total' => $res['pagination']['total']];
    }
}
