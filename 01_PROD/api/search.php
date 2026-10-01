<?php
/**
 * Suggestions de recherche (AJAX).
 */
require dirname(__DIR__) . '/includes/bootstrap.php';
header('Cache-Control: no-store');
json_response(Catalog::suggest(mb_substr((string)query_param('q'), 0, 100)));
