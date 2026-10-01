<?php
/**
 * Panier (conservé en session pendant la navigation) et calcul des totaux.
 */
class Cart
{
    public const MAX_QTY = 999;

    public static function raw(): array
    {
        return $_SESSION['cart'] ?? [];
    }

    public static function count(): int
    {
        return array_sum(self::raw());
    }

    public static function add(int $productId, int $qty = 1): array
    {
        $p = Catalog::productById($productId);
        if (!$p) {
            return ['ok' => false, 'error' => __('product_not_found')];
        }
        $newQty = min(self::MAX_QTY, (self::raw()[$productId] ?? 0) + max(1, $qty));
        if (!Catalog::canBuy($p, $newQty)) {
            $newQty = max(0, (int)$p['stock']);
            if ($newQty === 0) {
                return ['ok' => false, 'error' => __('out_of_stock_msg')];
            }
            $_SESSION['cart'][$productId] = $newQty;
            return ['ok' => true, 'warning' => __('qty_limited', ['n' => $newQty]), 'count' => self::count()];
        }
        $_SESSION['cart'][$productId] = $newQty;
        return ['ok' => true, 'count' => self::count(), 'product' => $p['name']];
    }

    public static function update(int $productId, int $qty): array
    {
        if ($qty <= 0) {
            self::remove($productId);
            return ['ok' => true, 'count' => self::count()];
        }
        $p = Catalog::productById($productId);
        if (!$p) {
            self::remove($productId);
            return ['ok' => false, 'error' => __('product_not_found')];
        }
        $qty = min(self::MAX_QTY, $qty);
        $warning = null;
        if (!Catalog::canBuy($p, $qty)) {
            $qty = max(1, (int)$p['stock']);
            $warning = __('qty_limited', ['n' => $qty]);
        }
        $_SESSION['cart'][$productId] = $qty;
        return ['ok' => true, 'count' => self::count(), 'warning' => $warning];
    }

    public static function remove(int $productId): void
    {
        unset($_SESSION['cart'][$productId]);
    }

    public static function clear(): void
    {
        unset($_SESSION['cart'], $_SESSION['coupon']);
    }

    public static function setCoupon(?string $code): void
    {
        if ($code) {
            $_SESSION['coupon'] = strtoupper(trim($code));
        } else {
            unset($_SESSION['coupon']);
        }
    }

    public static function couponCode(): ?string
    {
        return $_SESSION['coupon'] ?? null;
    }

    /** Lignes du panier avec produits à jour. Les produits indisponibles sont retirés. */
    public static function items(): array
    {
        $raw = self::raw();
        if (!$raw) {
            return [];
        }
        $products = Catalog::productsByIds(array_keys($raw));
        $items = [];
        foreach ($raw as $id => $qty) {
            if (!isset($products[$id])) {
                self::remove((int)$id);
                continue;
            }
            $p = $products[$id];
            $items[] = [
                'product' => $p,
                'qty' => (int)$qty,
                'unit_price' => $p['final_price'],
                'line_total' => round_money($p['final_price'] * $qty),
                'available' => Catalog::canBuy($p, (int)$qty),
            ];
        }
        return $items;
    }

    /**
     * Vérifie un coupon. Retourne [coupon|null, erreur|null].
     */
    public static function checkCoupon(string $code, float $subtotal, ?string $email = null): array
    {
        $c = DB::one('SELECT * FROM coupons WHERE code = :c', ['c' => strtoupper(trim($code))]);
        $now = time();
        if (!$c || !$c['active']) {
            return [null, __('coupon_invalid')];
        }
        if (($c['start_at'] && strtotime($c['start_at']) > $now) || ($c['end_at'] && strtotime($c['end_at']) < $now)) {
            return [null, __('coupon_expired')];
        }
        if ($c['max_uses'] !== null && (int)$c['uses'] >= (int)$c['max_uses']) {
            return [null, __('coupon_exhausted')];
        }
        if ($c['min_amount'] !== null && $subtotal < (float)$c['min_amount']) {
            return [null, __('coupon_min', ['amount' => money($c['min_amount'])])];
        }
        if ($email && $c['per_customer_limit'] !== null) {
            $used = (int)DB::val('SELECT COUNT(*) FROM coupon_usages WHERE coupon_id = :c AND email = :e', ['c' => $c['id'], 'e' => strtolower($email)]);
            if ($used >= (int)$c['per_customer_limit']) {
                return [null, __('coupon_already_used')];
            }
        }
        return [$c, null];
    }

    public static function couponDiscount(array $coupon, float $subtotal): float
    {
        $d = $coupon['discount_type'] === 'percent'
            ? $subtotal * min(100, (float)$coupon['value']) / 100
            : (float)$coupon['value'];
        return round_money(min($d, $subtotal));
    }

    public static function zones(): array
    {
        return DB::all('SELECT * FROM delivery_zones WHERE active = 1 ORDER BY sort, name');
    }

    public static function deliveryFee(?array $zone, string $method, float $amount): float
    {
        if ($method === 'pickup' || !$zone) {
            return 0.0;
        }
        $threshold = $zone['free_threshold'] !== null ? (float)$zone['free_threshold'] : (float)setting('free_shipping_threshold', 0);
        if ($threshold > 0 && $amount >= $threshold) {
            return 0.0;
        }
        return (float)$zone['fee'];
    }

    /**
     * Calcule tous les totaux du panier.
     * $opts : zone_id, delivery_method, email
     */
    public static function totals(array $opts = []): array
    {
        $items = self::items();
        $subtotal = round_money(array_sum(array_column($items, 'line_total')));

        $discount = 0.0;
        $coupon = null;
        $couponError = null;
        if (self::couponCode()) {
            [$coupon, $couponError] = self::checkCoupon(self::couponCode(), $subtotal, $opts['email'] ?? null);
            if ($coupon) {
                $discount = self::couponDiscount($coupon, $subtotal);
            }
        }

        $method = ($opts['delivery_method'] ?? 'delivery') === 'pickup' && setting_bool('pickup_enabled', true) ? 'pickup' : 'delivery';
        $zone = null;
        if (!empty($opts['zone_id'])) {
            $zone = DB::one('SELECT * FROM delivery_zones WHERE id = :id AND active = 1', ['id' => (int)$opts['zone_id']]);
        }
        $afterDiscount = $subtotal - $discount;
        $delivery = self::deliveryFee($zone, $method, $afterDiscount);

        $rate = (float)setting('tax_rate', 0);
        $included = setting_bool('prices_include_tax', true);
        if ($rate > 0) {
            $tax = $included ? $afterDiscount - $afterDiscount / (1 + $rate / 100) : $afterDiscount * $rate / 100;
        } else {
            $tax = 0;
        }
        $tax = round_money($tax);
        $total = round_money($afterDiscount + $delivery + ($included ? 0 : $tax));

        return [
            'items' => $items,
            'subtotal' => $subtotal,
            'discount' => $discount,
            'coupon' => $coupon,
            'coupon_error' => $couponError,
            'delivery_method' => $method,
            'zone' => $zone,
            'delivery_fee' => $delivery,
            'tax_rate' => $rate,
            'tax_included' => $included,
            'tax' => $tax,
            'total' => $total,
            'count' => array_sum(array_column($items, 'qty')),
        ];
    }
}
