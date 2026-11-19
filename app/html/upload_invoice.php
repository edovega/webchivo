<?php
declare(strict_types=1);
require_once __DIR__ . '/lib/common.php';

const MAX_INVOICE_BYTES = 200 * 1024;
const PUBLIC_UPLOAD_DIR = '/var/www/html/uploads/';
const PRIVATE_UPLOAD_DIR = '/var/lib/cib203/invoices/';

$notice = null;
$notice_ok = false;
$link = null;
$link_text = null;

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $file = $_FILES['invoice'] ?? null;
    if (!is_array($file) || ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        $notice = t('upload_missing');
    } elseif ($file['error'] === UPLOAD_ERR_INI_SIZE || $file['error'] === UPLOAD_ERR_FORM_SIZE
        || (int)$file['size'] > MAX_INVOICE_BYTES) {
        $notice = t('upload_too_large');
    } elseif ($file['error'] !== UPLOAD_ERR_OK || !is_uploaded_file((string)$file['tmp_name'])) {
        $notice = t('upload_failed');
    } else {
        $original = (string)$file['name'];
        $extension = strtolower(pathinfo($original, PATHINFO_EXTENSION));
        if (!in_array($extension, ['jpg', 'jpeg', 'png'], true)) {
            $notice = t('upload_invalid_type');
        } elseif (@getimagesize((string)$file['tmp_name']) === false) {
            $notice = t('upload_not_image');
        } else {
            if (is_hardened()) {
                $stored = bin2hex(random_bytes(16));
                $target = PRIVATE_UPLOAD_DIR . $stored;
                $link = '/invoice.php?id=' . $stored;
                $link_text = $stored;
            } else {
                // Vulnerable on purpose: original name inside the web root.
                $stored = basename($original);
                $target = PUBLIC_UPLOAD_DIR . $stored;
                $link = 'uploads/' . $stored;
                $link_text = 'uploads/' . $stored;
            }

            if (@move_uploaded_file((string)$file['tmp_name'], $target)) {
                try {
                    $stmt = db_connect('mailbox_rw')->prepare('INSERT INTO invoices (file_name) VALUES (:file_name)');
                    $stmt->execute([':file_name' => $stored]);
                } catch (Throwable $ex) {
                    error_log('upload: insert failed');
                }
                $notice = t('upload_ok');
                $notice_ok = true;
            } else {
                $notice = t('upload_failed');
                $link = null;
            }
        }
    }
}

$page_title = t('upload_title');
require __DIR__ . '/lib/header.php';
?>
<h1><?= e(t('upload_title')) ?></h1>
<p><?= e(t('upload_intro')) ?></p>
<?php if ($notice !== null): ?>
  <div class="<?= $notice_ok ? 'ok-box' : 'error-box' ?>">
    <p><?= e($notice) ?>
<?php if ($notice_ok && $link !== null): ?>
      <a href="<?= e($link) ?>"><?= e((string)$link_text) ?></a>
<?php endif; ?>
    </p>
  </div>
<?php endif; ?>
<form method="post" action="/upload_invoice.php" enctype="multipart/form-data">
  <label for="invoice"><?= e(t('upload_label')) ?></label>
  <input type="file" id="invoice" name="invoice" accept=".jpg,.jpeg,.png,image/jpeg,image/png" required>
  <button type="submit"><?= e(t('upload_button')) ?></button>
</form>
<?php require __DIR__ . '/lib/footer.php'; ?>
