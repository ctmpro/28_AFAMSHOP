<?php
/**
 * Gestion des stocks et historique des mouvements.
 */
class Stock
{
    /** Applique une variation de stock et l'historise. Retourne le nouveau stock. */
    public static function adjust(int $productId, int $delta, string $reason, ?int $orderId = null, ?string $note = null): int
    {
        DB::exec('UPDATE products SET stock = stock + :d WHERE id = :id', ['d' => $delta, 'id' => $productId]);
        $after = (int)DB::val('SELECT stock FROM products WHERE id = :id', ['id' => $productId]);
        DB::insert('stock_movements', [
            'product_id' => $productId,
            'qty_change' => $delta,
            'stock_after' => $after,
            'reason' => $reason,
            'order_id' => $orderId,
            'admin_id' => $_SESSION['admin_id'] ?? null,
            'note' => $note,
        ]);
        if ($delta < 0) {
            self::checkLow($productId, $after);
        }
        return $after;
    }

    /** Définit une valeur absolue (inventaire). */
    public static function set(int $productId, int $value, string $reason = 'manual', ?string $note = null): int
    {
        $current = (int)DB::val('SELECT stock FROM products WHERE id = :id', ['id' => $productId]);
        if ($current === $value) {
            return $current;
        }
        return self::adjust($productId, $value - $current, $reason, null, $note);
    }

    private static function checkLow(int $productId, int $after): void
    {
        $p = DB::one('SELECT id, name, sku, stock, low_stock_threshold, on_order FROM products WHERE id = :id', ['id' => $productId]);
        if (!$p) return;
        $threshold = Catalog::lowThreshold($p);
        // Notifie uniquement au franchissement du seuil
        if ($after <= $threshold) {
            Mailer::notifyAdmin('low_stock', ['product' => $p['name'], 'sku' => $p['sku'], 'stock' => $after]);
        }
    }

    public static function lowStockProducts(int $limit = 50): array
    {
        return DB::all(
            'SELECT id, name, sku, stock, low_stock_threshold FROM products
             WHERE stock > 0 AND stock <= COALESCE(low_stock_threshold, :t) ORDER BY stock ASC LIMIT ' . (int)$limit,
            ['t' => (int)setting('low_stock_threshold', 5)]
        );
    }

    public static function outOfStockProducts(int $limit = 50): array
    {
        return DB::all('SELECT id, name, sku, stock, on_order FROM products WHERE stock <= 0 ORDER BY name LIMIT ' . (int)$limit);
    }
}
