<?php
declare(strict_types=1);
require_once __DIR__ . '/lib/common.php';

$notice = null;
$notice_ok = false;

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $name = isset($_POST['name']) ? trim((string)$_POST['name']) : '';
    $subject = isset($_POST['subject']) ? trim((string)$_POST['subject']) : '';
    $body = isset($_POST['body']) ? trim((string)$_POST['body']) : '';

    if ($name === '' || $subject === '' || $body === '') {
        $notice = t('messages_invalid');
    } else {
        try {
            $stmt = db_connect('mailbox_rw')->prepare(
                'INSERT INTO messages (name, subject, body) VALUES (:name, :subject, :body)'
            );
            $stmt->execute([':name' => $name, ':subject' => $subject, ':body' => $body]);
            $notice = t('message_received');
            $notice_ok = true;
        } catch (Throwable $ex) {
            error_log('messages: insert failed');
            $notice = t('generic_error');
        }
    }
}

$latest = [];
try {
    $latest = db_connect('mailbox_rw')
        ->query('SELECT name, subject FROM messages ORDER BY id DESC LIMIT 5')
        ->fetchAll();
} catch (Throwable $ex) {
    error_log('messages: list failed');
}

$page_title = t('messages_title');
require __DIR__ . '/lib/header.php';
?>
<h1><?= e(t('messages_title')) ?></h1>
<p><?= e(t('messages_intro')) ?></p>
<?php if ($notice !== null): ?>
  <div class="<?= $notice_ok ? 'ok-box' : 'error-box' ?>"><p><?= e($notice) ?></p></div>
<?php endif; ?>
<form method="post" action="/messages.php">
  <label for="name"><?= e(t('messages_name')) ?></label>
  <input type="text" id="name" name="name" required>
  <label for="subject"><?= e(t('messages_subject')) ?></label>
  <input type="text" id="subject" name="subject" required>
  <label for="body"><?= e(t('messages_body')) ?></label>
  <textarea id="body" name="body" rows="5" required></textarea>
  <button type="submit"><?= e(t('messages_send')) ?></button>
</form>

<h2><?= e(t('messages_latest')) ?></h2>
<?php if ($latest === []): ?>
  <p><?= e(t('messages_none')) ?></p>
<?php else: ?>
  <div class="table-wrap">
    <table>
      <thead>
        <tr>
          <th><?= e(t('messages_name')) ?></th>
          <th><?= e(t('messages_subject')) ?></th>
        </tr>
      </thead>
      <tbody>
<?php foreach ($latest as $message): ?>
        <tr>
          <td><?= lab_output((string)$message['name']) ?></td>
          <td><?= lab_output((string)$message['subject']) ?></td>
        </tr>
<?php endforeach; ?>
      </tbody>
    </table>
  </div>
<?php endif; ?>
<?php require __DIR__ . '/lib/footer.php'; ?>
