<?php
/**
 * Déconnexion (POST + CSRF uniquement).
 */
require dirname(__DIR__) . '/includes/bootstrap.php';
require __DIR__ . '/_inc/helpers.php';

if (!is_post()) {
    redirect(admin_url('index.php'));
}
require_csrf();
if (AdminAuth::user()) {
    AdminAuth::log('logout', 'admin', AdminAuth::id());
}
AdminAuth::logout();
flash('success', 'Vous êtes déconnecté.');
redirect(admin_url('login.php'));
