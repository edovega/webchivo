<?php
declare(strict_types=1);
require_once __DIR__ . '/lib/common.php';

// The sink is the exercise: innerHTML at "vulnerable", textContent at "hardened".
$sink = is_hardened() ? 'textContent' : 'innerHTML';

$page_title = t('tracking_title');
require __DIR__ . '/lib/header.php';
?>
<h1><?= e(t('tracking_title')) ?></h1>
<form id="tracking-form">
  <fieldset role="group">
    <input type="text" id="order" placeholder="PED-1001" aria-label="<?= e(t('tracking_label')) ?>">
    <button type="submit"><?= e(t('tracking_button')) ?></button>
  </fieldset>
</form>
<article>
  <div id="status" data-prefix="<?= e(t('tracking_prefix')) ?>" data-empty="<?= e(t('tracking_empty')) ?>"></div>
</article>
<script<?= script_nonce_attr() ?>>
(function () {
  var status = document.getElementById('status');
  var prefix = status.getAttribute('data-prefix') + ' ';

  function render() {
    var raw = location.hash.slice(1);
    if (raw === '') {
      status.textContent = status.getAttribute('data-empty');
      return;
    }
    var value;
    try {
      value = decodeURIComponent(raw);
    } catch (err) {
      value = raw;
    }
    status.<?= $sink ?> = prefix + value;
  }

  document.getElementById('tracking-form').addEventListener('submit', function (event) {
    event.preventDefault();
    location.hash = encodeURIComponent(document.getElementById('order').value);
  });
  window.addEventListener('hashchange', render);
  render();
})();
</script>
<?php require __DIR__ . '/lib/footer.php'; ?>
