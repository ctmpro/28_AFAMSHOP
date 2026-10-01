<?php
/**
 * API panier : add, update, remove, coupon, remove_coupon.
 * Répond en JSON (AJAX) ou redirige (formulaire classique).
 */
require dirname(__DIR__) . '/includes/bootstrap.php';

if (!is_post()) {
    json_response(['ok' => false, 'error' => 'method'], 405);
}
require_csrf();

$action = post('action');
$productId = int_param('product_id');
$res = ['ok' => true];

switch ($action) {
    case 'add':
        $res = Cart::add($productId, max(1, int_param('qty', 1)));
        if ($res['ok']) $res['message'] = __('added_to_cart');
        break;
    case 'update':
        $res = Cart::update($productId, int_param('qty'));
        break;
    case 'remove':
        Cart::remove($productId);
        $res = ['ok' => true, 'count' => Cart::count()];
        break;
    case 'coupon':
        $code = mb_substr(post('code'), 0, 50);
        $t = Cart::totals();
        [$coupon, $error] = Cart::checkCoupon($code, $t['subtotal'], Auth::user()['email'] ?? null);
        if ($coupon) {
            Cart::setCoupon($coupon['code']);
            $res = ['ok' => true, 'message' => __('coupon_applied')];
        } else {
            Cart::setCoupon(null);
            $res = ['ok' => false, 'error' => $error];
        }
        break;
    case 'remove_coupon':
        Cart::setCoupon(null);
        break;
    default:
        $res = ['ok' => false, 'error' => 'action'];
}

$res['count'] = Cart::count();

if (is_ajax()) {
    if (in_array($action, ['update', 'remove', 'coupon', 'remove_coupon'], true)) {
        $t = Cart::totals();
        $res['totals'] = [
            'subtotal' => money($t['subtotal']),
            'discount' => $t['discount'] > 0 ? '-' . money($t['discount']) : '',
            'total' => money($t['total']),
        ];
        foreach ($t['items'] as $it) {
            $res['lines'][$it['product']['id']] = ['qty' => $it['qty'], 'total' => money($it['line_total'])];
        }
    }
    json_response($res, $res['ok'] ? 200 : 422);
}

if (!empty($res['error'])) flash('error', $res['error']);
elseif (!empty($res['warning'])) flash('warning', $res['warning']);
elseif (!empty($res['message'])) flash('success', $res['message']);

if ($action === 'add' && post('go') === 'checkout' && $res['ok']) {
    redirect('commande');
}
redirect('panier');
