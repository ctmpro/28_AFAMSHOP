<?php
/**
 * Comparateur de produits (4 maximum, en session).
 */
require dirname(__DIR__) . '/includes/bootstrap.php';
if (!is_post()) json_response(['ok' => false], 405);
require_csrf();
$list = array_values(array_map('intval', $_SESSION['compare'] ?? []));
$pid = int_param('product_id');
$action = post('action', 'toggle');
if ($action === 'clear') {
    $list = [];
} elseif (in_array($pid, $list, true)) {
    $list = array_values(array_diff($list, [$pid]));
} else {
    if (count($list) >= 4) {
        json_response(['ok' => false, 'error' => __('compare_max'), 'count' => count($list)], 422);
    }
    if (Catalog::productById($pid)) $list[] = $pid;
}
$_SESSION['compare'] = $list;
if (!is_ajax()) redirect('comparer');
json_response(['ok' => true, 'count' => count($list), 'active' => in_array($pid, $list, true), 'message' => in_array($pid, $list, true) ? __('compare_added') : '', 'url' => url('comparer')]);
