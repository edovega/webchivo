<?php
declare(strict_types=1);
require_once __DIR__ . '/lib/common.php';

$code = isset($_GET['code']) ? (string)$_GET['code'] : '';
$checked = $code !== '';
$found = false;

if ($checked) {
    if (is_hardened()) {
        try {
            $stmt = db_connect('tenders_ro')->prepare('SELECT 1 FROM tenders WHERE code = :code');
            $stmt->execute([':code' => $code]);
            $found = $stmt->fetchColumn() !== false;
        } catch (Throwable $ex) {
            error_log('tender: query failed');
            $found = false;
        }
    } else {
        // Vulnerable on purpose: string concatenation. Errors are never shown,
        // the page only says whether rows exist.
        $conn = db_connect_legacy('tenders_ro');
        $result = $conn ? @pg_query($conn, "SELECT 1 FROM tenders WHERE code = '" . $code . "'") : false;
        $found = $result !== false && pg_num_rows($result) > 0;
    }
}

$tenders = [];
try {
    $tenders = db_connect('tenders_ro')
        ->query('SELECT code, institution, subject, amount, status, closes_on FROM tenders ORDER BY code')
        ->fetchAll();
} catch (Throwable $ex) {
    error_log('tender: list failed');
}

$page_title = t('tender_title');
require __DIR__ . '/lib/header.php';
?>
<h1><?= e(t('tender_title')) ?></h1>
<p><?= e(t('tender_intro')) ?></p>
<form method="get" action="/tender.php">
  <fieldset role="group">
    <input type="text" name="code" placeholder="LIC-2026-001" aria-label="<?= e(t('tender_code_label')) ?>" value="">
    <button type="submit"><?= e(t('tender_check_button')) ?></button>
  </fieldset>
</form>

<?php if ($checked): ?>
  <div class="<?= $found ? 'ok-box' : 'error-box' ?>">
    <p><?= e(t($found ? 'tender_found' : 'tender_not_found')) ?></p>
  </div>
<?php endif; ?>

<h2><?= e(t('tender_list_title')) ?></h2>
<div class="table-wrap">
  <table>
    <thead>
      <tr>
        <th><?= e(t('col_code')) ?></th>
        <th><?= e(t('col_institution')) ?></th>
        <th><?= e(t('col_subject')) ?></th>
        <th><?= e(t('col_amount')) ?></th>
        <th><?= e(t('col_status')) ?></th>
        <th><?= e(t('col_closes_on')) ?></th>
      </tr>
    </thead>
    <tbody>
<?php foreach ($tenders as $tender): ?>
      <tr>
        <td><?= e((string)$tender['code']) ?></td>
        <td><?= e((string)$tender['institution']) ?></td>
        <td><?= e((string)$tender['subject']) ?></td>
        <td><?= e((string)$tender['amount']) ?></td>
        <td><?= e((string)$tender['status']) ?></td>
        <td><?= e((string)$tender['closes_on']) ?></td>
      </tr>
<?php endforeach; ?>
    </tbody>
  </table>
</div>
<?php require __DIR__ . '/lib/footer.php'; ?>
