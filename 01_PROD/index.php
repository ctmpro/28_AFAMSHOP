<?php
/**
 * Contrôleur frontal du site public : associe l'URL demandée à une page de pages/.
 */
// Serveur de développement PHP : servir directement les fichiers statiques et scripts existants
if (PHP_SAPI === 'cli-server') {
    $file = __DIR__ . parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    if ($file !== __DIR__ . '/' && is_file($file)) {
        if (preg_match('#/(config|includes|sql|lang|utilitaires|uploads/private)/|/\.env#', $file)) {
            http_response_code(403);
            exit('Forbidden');
        }
        return false;
    }
}

require __DIR__ . '/includes/bootstrap.php';

$route = trim((string)($_GET['_route'] ?? ''), '/');
if ($route === '' && PHP_SAPI === 'cli-server') {
    $route = trim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/');
}
unset($_GET['_route']);

$routes = [
    '' => 'home',
    'recherche' => 'catalog',
    'promotions' => 'catalog',
    'nouveautes' => 'catalog',
    'categorie/{slug}' => 'catalog',
    'marque/{slug}' => 'catalog',
    'marques' => 'brands',
    'produit/{slug}' => 'product',
    'recherche-imprimante' => 'printer-finder',
    'comparer' => 'compare',
    'panier' => 'cart',
    'commande' => 'checkout',
    'commande/confirmation' => 'order-confirmation',
    'commande/suivi' => 'order-confirmation',
    'commande/paiement-retour' => 'payment-return',
    'commande/payer' => 'payment-start',
    'commande/facture' => 'invoice',
    'compte' => 'account/dashboard',
    'compte/connexion' => 'account/login',
    'compte/inscription' => 'account/register',
    'compte/deconnexion' => 'account/logout',
    'compte/mot-de-passe-oublie' => 'account/forgot',
    'compte/reinitialisation' => 'account/reset',
    'compte/commandes' => 'account/orders',
    'compte/commande/{slug}' => 'account/order',
    'compte/adresses' => 'account/addresses',
    'compte/favoris' => 'account/favorites',
    'compte/demandes' => 'account/requests',
    'compte/profil' => 'account/profile',
    'compte/mot-de-passe' => 'account/password',
    'devis' => 'quote',
    'contact' => 'contact',
    'services' => 'services',
    'service/{slug}' => 'service',
    'page/{slug}' => 'page',
];

$page = null;
$params = [];
foreach ($routes as $pattern => $target) {
    $regex = '#^' . str_replace('\{slug\}', '([a-z0-9][a-z0-9\-]*)', preg_quote($pattern, '#')) . '$#';
    if (preg_match($regex, $route, $m)) {
        $page = $target;
        $params['slug'] = $m[1] ?? null;
        $params['route'] = $route;
        break;
    }
}
// Pages institutionnelles accessibles directement : /a-propos, /cgv...
if ($page === null && preg_match('#^[a-z0-9\-]+$#', $route)
    && DB::val('SELECT id FROM pages WHERE slug = :s AND published = 1', ['s' => $route])) {
    $page = 'page';
    $params['slug'] = $route;
}

try {
    if ($page === null || !is_file(PAGES_PATH . '/' . $page . '.php')) {
        http_response_code(404);
        require PAGES_PATH . '/404.php';
    } else {
        $slug = $params['slug'];
        require PAGES_PATH . '/' . $page . '.php';
    }
} catch (Throwable $e) {
    error_log('AFAMSHOP: ' . $e->getMessage() . ' @ ' . $e->getFile() . ':' . $e->getLine());
    http_response_code(500);
    if (APP_DEBUG) {
        echo '<pre>' . e((string)$e) . '</pre>';
    } else {
        require PAGES_PATH . '/500.php';
    }
}
