<?php
/**
 * Modèles d'imprimantes d'une marque (recherche par imprimante).
 */
require dirname(__DIR__) . '/includes/bootstrap.php';
$brand = int_param('brand');
json_response(['ok' => true, 'models' => $brand ? Catalog::printerModels($brand) : []]);
