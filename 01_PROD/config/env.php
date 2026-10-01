<?php
/**
 * Chargement des variables d'environnement depuis le fichier .env
 * (situé à la racine de 01_PROD, jamais versionné ni accessible depuis le web).
 */
function loadEnvFile(): bool {
    $envFile = __DIR__ . '/../.env';
    if (!file_exists($envFile)) {
        error_log("AFAMSHOP: Fichier .env non trouvé dans " . dirname($envFile));
        return false;
    }
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        // Ignorer les commentaires
        if (strpos(trim($line), '#') === 0) {
            continue;
        }
        if (strpos($line, '=') !== false) {
            list($key, $value) = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value);
            // Supprimer les guillemets autour de la valeur
            if (preg_match('/^([\'"])(.*)\1$/', $value, $matches)) {
                $value = $matches[2];
            }
            $_ENV[$key] = $value;
            putenv("$key=$value");
        }
    }
    return true;
}

/**
 * Lecture d'une variable d'environnement avec valeur par défaut.
 */
function env(string $key, $default = null) {
    if (array_key_exists($key, $_ENV)) {
        $v = $_ENV[$key];
    } else {
        $v = getenv($key);
        if ($v === false) {
            return $default;
        }
    }
    $lower = strtolower((string)$v);
    if ($lower === 'true') return true;
    if ($lower === 'false') return false;
    if ($lower === 'null') return null;
    return $v;
}
