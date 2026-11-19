<?php
declare(strict_types=1);
require_once __DIR__ . '/lib/common.php';

$allowed = [
    'manual' => 'docs/manual.php',
    'policy' => 'docs/policy.php',
    'hours' => 'docs/hours.php',
];
$labels = [
    'manual' => 'doc_manual',
    'policy' => 'doc_policy',
    'hours' => 'doc_hours',
];

$default = is_hardened() ? 'manual' : 'docs/manual.php';
$doc = isset($_GET['doc']) ? (string)$_GET['doc'] : $default;

$page_title = t('documents_title');
require __DIR__ . '/lib/header.php';
?>
<h1><?= e(t('documents_title')) ?></h1>
<nav>
  <ul>
<?php foreach ($labels as $key => $label): ?>
    <li><a href="/documents.php?doc=<?= e(is_hardened() ? $key : $allowed[$key]) ?>"><?= e(t($label)) ?></a></li>
<?php endforeach; ?>
  </ul>
</nav>
<article class="document-body">
<?php
if (is_hardened()) {
    if (isset($allowed[$doc])) {
        include __DIR__ . '/' . $allowed[$doc];
    } else {
        echo '<p>' . e(t('generic_error')) . '</p>';
    }
} else {
    // Vulnerable on purpose: the parameter goes straight to include.
    include($doc);
}
?>
</article>
<?php require __DIR__ . '/lib/footer.php'; ?>
