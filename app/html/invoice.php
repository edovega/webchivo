<?php
declare(strict_types=1);
require_once __DIR__ . '/lib/common.php';

// Hardened level only: serves stored invoices from outside the web root.
$id = isset($_GET['id']) ? (string)$_GET['id'] : '';
$path = '/var/lib/cib203/invoices/' . $id;

if (!is_hardened() || preg_match('/^[0-9a-f]{32}$/', $id) !== 1 || !is_file($path)) {
    http_response_code(404);
    header('Content-Type: text/plain; charset=UTF-8');
    echo t('not_found');
    exit;
}

$info = @getimagesize($path);
$mime = is_array($info) ? (string)$info['mime'] : '';
if (!in_array($mime, ['image/jpeg', 'image/png'], true)) {
    http_response_code(404);
    header('Content-Type: text/plain; charset=UTF-8');
    echo t('not_found');
    exit;
}

header('Content-Type: ' . $mime);
header('X-Content-Type-Options: nosniff');
header('Content-Length: ' . (string)filesize($path));
readfile($path);
