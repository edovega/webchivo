<?php
declare(strict_types=1);
/**
 * Page header. Set $page_title before including this file.
 *
 * @var string $page_title
 */
require_once __DIR__ . '/common.php';

resume_session();

if (is_hardened()) {
    header(sprintf(
        "Content-Security-Policy: script-src 'nonce-%s' 'strict-dynamic'; object-src 'none'; base-uri 'none'",
        csp_nonce()
    ));
}
header('Content-Type: text/html; charset=UTF-8');

$nav = [
    '/index.php' => 'nav_home',
    '/catalog.php' => 'nav_catalog',
    '/tender.php' => 'nav_tenders',
    '/search.php' => 'nav_search',
    '/tracking.php' => 'nav_tracking',
    '/report.php' => 'nav_report',
    '/messages.php' => 'nav_messages',
    '/upload_invoice.php' => 'nav_upload',
    '/documents.php' => 'nav_documents',
];
$level_key = is_hardened() ? 'level_hardened' : 'level_vulnerable';
$title = isset($page_title) ? $page_title . ' | ' . t('site_name') : t('site_name');
?>
<!doctype html>
<html lang="es-CR" data-theme="light">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= e($title) ?></title>
  <link rel="stylesheet" href="/vendor/pico/pico.green.min.css">
  <link rel="stylesheet" href="/styles.css">
</head>
<body>
  <div class="lab-banner" role="note"><?= e(t('banner')) ?></div>
  <header class="container">
    <nav>
      <ul>
        <li><a href="/index.php" class="brand"><strong><?= e(t('site_name')) ?></strong></a></li>
      </ul>
      <ul>
        <li><small><?= e(t('level_label')) ?> <mark class="level-<?= e(app_level()) ?>"><?= e(t($level_key)) ?></mark></small></li>
      </ul>
    </nav>
    <nav class="main-menu">
      <ul>
<?php foreach ($nav as $href => $key): ?>
        <li><a href="<?= e($href) ?>"><?= e(t($key)) ?></a></li>
<?php endforeach; ?>
<?php if (is_admin_verified()): ?>
        <li><a href="/admin/"><?= e(t('nav_admin')) ?></a></li>
<?php endif; ?>
<?php if (current_user() !== null): ?>
        <li><a href="/inbox.php"><?= e(t('nav_inbox')) ?></a></li>
        <li><a href="/logout.php"><?= e(t('nav_logout')) ?></a></li>
<?php else: ?>
        <li><a href="/login.php"><?= e(t('nav_login')) ?></a></li>
<?php endif; ?>
      </ul>
    </nav>
  </header>
  <main class="container">
