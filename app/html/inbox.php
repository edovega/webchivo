<?php
declare(strict_types=1);
require_once __DIR__ . '/lib/common.php';

$user = current_user();
if ($user === null) {
    redirect('/login.php');
}

$inbox = null;
try {
    $stmt = db_connect('session_ro')->prepare('SELECT title, content FROM inboxes WHERE username = :username');
    $stmt->execute([':username' => $user]);
    $inbox = $stmt->fetch() ?: null;
} catch (Throwable $ex) {
    error_log('inbox: query failed');
}

// Which inbox content is visible to this session.
$show_content = false;
$notice = t('inbox_empty');
if ($inbox !== null) {
    if ($user === 'auditoria') {
        if (($_SESSION['password_verified'] ?? false) === true) {
            $show_content = true;
        } else {
            $notice = t('inbox_confirm_password');
        }
    } elseif ($user === 'finanzas') {
        $show_content = true;
    }
}

$page_title = t('inbox_title');
require __DIR__ . '/lib/header.php';
?>
<h1><?= e(t('inbox_title')) ?></h1>
<p><small><?= e(t('inbox_user')) ?> <?= e($user) ?></small></p>
<?php if ($show_content): ?>
  <article>
    <header><strong><?= e((string)$inbox['title']) ?></strong></header>
    <p class="document-body"><?= e((string)$inbox['content']) ?></p>
  </article>
<?php else: ?>
  <article>
    <p><?= e($notice) ?></p>
  </article>
<?php endif; ?>
<?php require __DIR__ . '/lib/footer.php'; ?>
