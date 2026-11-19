<?php
declare(strict_types=1);
require_once __DIR__ . '/lib/common.php';

$category = isset($_GET['category']) ? (string)$_GET['category'] : '';
$rows = [];
$error = null;

// Category list, always with a prepared statement.
$categories = [];
try {
    $categories = db_connect('catalog_ro')
        ->query('SELECT DISTINCT category FROM products WHERE active = true ORDER BY category')
        ->fetchAll(PDO::FETCH_COLUMN);
} catch (Throwable $ex) {
    error_log('catalog: category list failed');
}

if ($category !== '') {
    if (is_hardened()) {
        try {
            $stmt = db_connect('catalog_ro')->prepare(
                'SELECT id, name, description, price FROM products WHERE category = :category AND active = true ORDER BY id'
            );
            $stmt->execute([':category' => $category]);
            $rows = $stmt->fetchAll(PDO::FETCH_NUM);
        } catch (Throwable $ex) {
            error_log('catalog: query failed');
            $error = t('generic_error');
        }
    } else {
        // Vulnerable on purpose: string concatenation and full database errors.
        $conn = db_connect_legacy('catalog_ro');
        $sql = "SELECT id, name, description, price FROM products WHERE category = '" . $category . "' AND active = true ORDER BY id";
        $result = $conn ? @pg_query($conn, $sql) : false;
        if ($result === false) {
            $error = $conn ? pg_last_error($conn) : t('generic_error');
        } else {
            while (($row = pg_fetch_row($result)) !== false) {
                $rows[] = $row;
            }
        }
    }
}

$page_title = t('catalog_title');
require __DIR__ . '/lib/header.php';
?>
<h1><?= e(t('catalog_title')) ?></h1>
<p><?= e(t('catalog_intro')) ?></p>
<nav>
  <ul>
<?php foreach ($categories as $name): ?>
    <li><a href="/catalog.php?category=<?= e(rawurlencode((string)$name)) ?>"><?= e((string)$name) ?></a></li>
<?php endforeach; ?>
  </ul>
</nav>

<?php if ($error !== null): ?>
  <div class="error-box">
<?php if (is_hardened()): ?>
    <p><?= e($error) ?></p>
<?php else: ?>
    <p><strong><?= e(t('db_error_title')) ?></strong></p>
    <pre class="db-error"><?= e($error) ?></pre>
<?php endif; ?>
  </div>
<?php elseif ($category !== ''): ?>
  <h2><?= e($category) ?></h2>
<?php if ($rows === []): ?>
  <p><?= e(t('catalog_empty')) ?></p>
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
          <td><?= e($row[0] === null ? '' : (string)$row[0]) ?></td>
          <td><?= e($row[1] === null ? '' : (string)$row[1]) ?></td>
          <td><?= e($row[2] === null ? '' : (string)$row[2]) ?></td>
          <td><?= e($row[3] === null ? '' : (string)$row[3]) ?></td>
        </tr>
<?php endforeach; ?>
      </tbody>
    </table>
  </div>
<?php endif; ?>
<?php endif; ?>
<?php require __DIR__ . '/lib/footer.php'; ?>
