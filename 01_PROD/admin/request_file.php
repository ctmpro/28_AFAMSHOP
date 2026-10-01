<?php
/**
 * Téléchargement protégé d'une pièce jointe de demande (uploads/private/requests, non accessible publiquement).
 */
require dirname(__DIR__) . '/includes/bootstrap.php';
require __DIR__ . '/_inc/helpers.php';
$admin = AdminAuth::require('requests');

$id = int_param('id');
$req = DB::one('SELECT id, attachment FROM requests WHERE id = :id', ['id' => $id]);
$file = $req ? request_attachment_path($req['attachment']) : null;
if (!$file) {
    http_response_code(404);
    exit('Fichier introuvable.');
}

$mime = (new finfo(FILEINFO_MIME_TYPE))->file($file) ?: 'application/octet-stream';
// Les types sûrs peuvent s'afficher dans le navigateur, le reste est forcé en téléchargement
$inline = in_array($mime, ['application/pdf', 'image/jpeg', 'image/png', 'image/webp'], true) && query_param('inline');
AdminAuth::log('request_file_download', 'request', $id, basename($file));

header('Content-Type: ' . $mime);
header('Content-Length: ' . filesize($file));
header('Content-Disposition: ' . ($inline ? 'inline' : 'attachment') . '; filename="demande-' . $id . '-' . preg_replace('/[^A-Za-z0-9._-]/', '_', basename($file)) . '"');
header('X-Content-Type-Options: nosniff');
header("Content-Security-Policy: default-src 'none'; sandbox");
readfile($file);
