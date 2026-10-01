<?php
/**
 * Déconnexion (POST + CSRF uniquement).
 */
if (is_post()) {
    require_csrf();
    Auth::logout();
}
redirect('');
