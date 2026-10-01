<?php
/**
 * Ajout / retrait d'un favori (client connecté).
 */
require dirname(__DIR__) . '/includes/bootstrap.php';
if (!is_post()) json_response(['ok' => false], 405);
require_csrf();
if (!Auth::check()) {
    json_response(['ok' => false, 'error' => __('favorites_login'), 'login' => url('compte/connexion', ['retour' => $_SERVER['HTTP_REFERER'] ?? ''])], 401);
}
$pid = int_param('product_id');
if (!Catalog::productById($pid)) json_response(['ok' => false, 'error' => __('product_not_found')], 404);
$params = ['c' => Auth::id(), 'p' => $pid];
if (DB::val('SELECT 1 FROM favorites WHERE customer_id = :c AND product_id = :p', $params)) {
    DB::exec('DELETE FROM favorites WHERE customer_id = :c AND product_id = :p', $params);
    json_response(['ok' => true, 'active' => false, 'message' => __('favorite_removed'), 'label' => __('add_to_favorites')]);
}
DB::exec('INSERT INTO favorites (customer_id, product_id) VALUES (:c, :p)', $params);
json_response(['ok' => true, 'active' => true, 'message' => __('favorite_added'), 'label' => __('remove_from_favorites')]);
