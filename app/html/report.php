<?php
declare(strict_types=1);
require_once __DIR__ . '/lib/common.php';

/**
 * Same rules the reviewer re-validates before visiting a path.
 */
function valid_report_path(string $path): bool
{
    return $path !== ''
        && $path[0] === '/'
        && strncmp($path, '//', 2) !== 0
        && strpos($path, '\\') === false
        && strlen($path) <= 500;
}

$notice = null;
$notice_ok = false;

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $path = isset($_POST['path']) ? (string)$_POST['path'] : '';
    if (!valid_report_path($path)) {
        $notice = t('report_invalid');
    } else {
        try {
            $stmt = db_connect('mailbox_rw')->prepare('INSERT INTO reports (path) VALUES (:path)');
            $stmt->execute([':path' => $path]);
            $notice = t('report_received');
            $notice_ok = true;
        } catch (Throwable $ex) {
            error_log('report: insert failed');
            $notice = t('generic_error');
        }
    }
}

$page_title = t('report_title');
require __DIR__ . '/lib/header.php';
?>
<h1><?= e(t('report_title')) ?></h1>
<p><?= e(t('report_hint')) ?></p>
<?php if ($notice !== null): ?>
  <div class="<?= $notice_ok ? 'ok-box' : 'error-box' ?>"><p><?= e($notice) ?></p></div>
<?php endif; ?>
<form method="post" action="/report.php">
  <label for="path"><?= e(t('report_label')) ?></label>
  <input type="text" id="path" name="path" maxlength="500" placeholder="/tracking.php#PED-1001" required>
  <button type="submit"><?= e(t('report_button')) ?></button>
</form>
<?php require __DIR__ . '/lib/footer.php'; ?>
