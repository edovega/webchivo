<?php
declare(strict_types=1);
require_once __DIR__ . '/lib/common.php';

$q = isset($_GET['q']) ? (string)$_GET['q'] : '';
$rows = [];
$error = null;

if ($q !== '') {
    // The query itself is always safe. Only the echo of $q is the exercise.
    try {
        $stmt = db_connect('catalog_ro')->prepare(
            'SELECT id, name, description, price FROM products
             WHERE active = true AND (name ILIKE :q1 OR description ILIKE :q2)
             ORDER BY id LIMIT 50'
        );
        $like = '%' . $q . '%';
        $stmt->execute([':q1' => $like, ':q2' => $like]);
        $rows = $stmt->fetchAll();
    } catch (Throwable $ex) {
        error_log('search: query failed');
        $error = t('generic_error');
    }
}

$page_title = t('search_title');
require __DIR__ . '/lib/header.php';
?>
<h1><?= e(t('search_title')) ?></h1>
<form method="get" action="/search.php">
  <fieldset role="group">
    <input type="search" name="q" aria-label="<?= e(t('search_label')) ?>" placeholder="<?= e(t('search_label')) ?>">
    <button type="submit"><?= e(t('search_button')) ?></button>
  </fieldset>
</form>

<?php if ($q !== ''): ?>
  <p><?= e(t('search_results_for')) ?> <?= lab_output($q) ?></p>
<?php if ($error !== null): ?>
  <div class="error-box"><p><?= e($error) ?></p></div>
<?php elseif ($rows === []): ?>
  <p><?= e(t('search_none')) ?></p>
<?php else: ?>
  <div class="table-wrap">
    <table>
      <thead>
        <tr>
          <th><?= e(t('col_id')) ?></th>
          <th><?= e(t('col_name')) ?></th>
          <th><?= e(t('col_description')) ?></th>
          <th><?= e(t('col_price')) ?></th>
        </tr>
      </thead>
      <tbody>
<?php foreach ($rows as $row): ?>
        <tr>
          <td><?= e((string)$row['id']) ?></td>
          <td><?= e((string)$row['name']) ?></td>
          <td><?= e((string)$row['description']) ?></td>
          <td><?= e((string)$row['price']) ?></td>
        </tr>
<?php endforeach; ?>
      </tbody>
    </table>
  </div>
<?php endif; ?>
<?php endif; ?>
<?php require __DIR__ . '/lib/footer.php'; ?>
