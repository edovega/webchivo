<?php
declare(strict_types=1);
require_once __DIR__ . '/../lib/common.php';

if (!is_admin_verified()) {
    redirect('/login.php');
}

$inbox = null;
try {
    $stmt = db_connect('session_ro')->prepare('SELECT title, content FROM inboxes WHERE username = :username');
    $stmt->execute([':username' => 'admin']);
    $inbox = $stmt->fetch() ?: null;
} catch (Throwable $ex) {
    error_log('dashboard: query failed');
}

// Synthetic accounts payable summary.
$summary = [
    'ap_pending' => '37',
    'ap_due_week' => '9',
    'ap_overdue' => '3',
    'ap_total' => '48 215 600,00',
];

$page_title = t('dashboard_title');
require __DIR__ . '/../lib/header.php';
?>
<h1><?= e(t('dashboard_title')) ?></h1>
<h2><?= e(t('dashboard_summary')) ?></h2>
<div class="grid">
<?php foreach ($summary as $key => $value): ?>
  <article>
    <header><?= e(t($key)) ?></header>
    <strong><?= e($value) ?></strong>
  </article>
<?php endforeach; ?>
</div>

<h2><?= e(t('dashboard_inbox')) ?></h2>
<?php if ($inbox !== null): ?>
  <article>
    <header><strong><?= e((string)$inbox['title']) ?></strong></header>
    <p class="document-body"><?= e((string)$inbox['content']) ?></p>
  </article>
<?php else: ?>
  <p><?= e(t('inbox_empty')) ?></p>
<?php endif; ?>
<p><a href="/admin/messages.php" role="button"><?= e(t('admin_messages_link')) ?></a></p>
<?php require __DIR__ . '/../lib/footer.php'; ?>
