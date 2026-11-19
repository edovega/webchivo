<?php
declare(strict_types=1);
require_once __DIR__ . '/../lib/common.php';

if (!is_admin_verified()) {
    redirect('/login.php');
}

$messages = [];
try {
    $messages = db_connect('mailbox_rw')
        ->query('SELECT id, name, subject, body, created_at FROM messages ORDER BY id DESC')
        ->fetchAll();
} catch (Throwable $ex) {
    error_log('admin messages: query failed');
}

$page_title = t('admin_messages_title');
require __DIR__ . '/../lib/header.php';
?>
<h1><?= e(t('admin_messages_title')) ?></h1>
<?php if ($messages === []): ?>
  <p><?= e(t('messages_none')) ?></p>
<?php else: ?>
  <div class="table-wrap">
    <table>
      <thead>
        <tr>
          <th><?= e(t('col_date')) ?></th>
          <th><?= e(t('messages_name')) ?></th>
          <th><?= e(t('messages_subject')) ?></th>
          <th><?= e(t('messages_body')) ?></th>
        </tr>
      </thead>
      <tbody>
<?php foreach ($messages as $message): ?>
        <tr class="message-row" data-message-id="<?= e((string)$message['id']) ?>">
          <td><?= e(substr((string)$message['created_at'], 0, 16)) ?></td>
          <td><?= lab_output((string)$message['name']) ?></td>
          <td><?= lab_output((string)$message['subject']) ?></td>
          <td><?= lab_output((string)$message['body']) ?></td>
        </tr>
<?php endforeach; ?>
      </tbody>
    </table>
  </div>
<?php endif; ?>
<?php require __DIR__ . '/../lib/footer.php'; ?>
