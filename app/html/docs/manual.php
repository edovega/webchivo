<?php
declare(strict_types=1);
require_once __DIR__ . '/../lib/i18n.php';
if (!function_exists('e')) {
    function e(?string $value): string
    {
        return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}
?>
<h2><?= e(t('doc_manual')) ?></h2>
<?php foreach (['manual_p1', 'manual_p2', 'manual_p3', 'manual_p4'] as $key): ?>
<?php if (t($key) !== $key): ?>
<p><?= e(t($key)) ?></p>
<?php endif; ?>
<?php endforeach; ?>
