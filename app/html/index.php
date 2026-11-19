<?php
declare(strict_types=1);
require_once __DIR__ . '/lib/common.php';

$page_title = t('nav_home');
$cards = [
    '/catalog.php' => ['nav_catalog', 'home_catalog'],
    '/tender.php' => ['nav_tenders', 'home_tenders'],
    '/tracking.php' => ['nav_tracking', 'home_tracking'],
    '/messages.php' => ['nav_messages', 'home_messages'],
    '/upload_invoice.php' => ['nav_upload', 'home_upload'],
    '/documents.php' => ['nav_documents', 'home_documents'],
];
require __DIR__ . '/lib/header.php';
?>
<hgroup>
  <h1><?= e(t('home_title')) ?></h1>
  <p><?= e(t('site_tagline')) ?></p>
</hgroup>
<p><?= e(t('home_intro')) ?></p>
<div class="grid">
<?php foreach (array_slice($cards, 0, 3, true) as $href => [$title_key, $text_key]): ?>
  <article>
    <h3><a href="<?= e($href) ?>"><?= e(t($title_key)) ?></a></h3>
    <p><?= e(t($text_key)) ?></p>
  </article>
<?php endforeach; ?>
</div>
<div class="grid">
<?php foreach (array_slice($cards, 3, 3, true) as $href => [$title_key, $text_key]): ?>
  <article>
    <h3><a href="<?= e($href) ?>"><?= e(t($title_key)) ?></a></h3>
    <p><?= e(t($text_key)) ?></p>
  </article>
<?php endforeach; ?>
</div>
<p><small><?= e(t('home_question')) ?></small></p>
<?php require __DIR__ . '/lib/footer.php'; ?>
