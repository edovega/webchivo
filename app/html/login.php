<?php
declare(strict_types=1);
require_once __DIR__ . '/lib/common.php';

$message = null;
$db_error = null;

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $username = isset($_POST['username']) ? (string)$_POST['username'] : '';
    $password = isset($_POST['password']) ? (string)$_POST['password'] : '';
    $row = null;

    if (is_hardened()) {
        try {
            $stmt = db_connect('auth_ro')->prepare(
                'SELECT id, username, role FROM users WHERE username = :username AND password_md5 = md5(:password)'
            );
            $stmt->execute([':username' => $username, ':password' => $password]);
            $row = $stmt->fetch() ?: null;
        } catch (Throwable $ex) {
            error_log('login: query failed');
            $row = null;
        }
    } else {
        // Vulnerable on purpose: string concatenation and full database errors.
        $conn = db_connect_legacy('auth_ro');
        $sql = "SELECT id, username, role FROM users WHERE username = '" . $username
            . "' AND password_md5 = md5('" . $password . "')";
        $result = $conn ? @pg_query($conn, $sql) : false;
        if ($result === false) {
            $db_error = $conn ? pg_last_error($conn) : t('generic_error');
        } else {
            $row = pg_fetch_assoc($result) ?: null;
        }
    }

    if ($row !== null) {
        $row_username = (string)($row['username'] ?? '');
        $row_role = (string)($row['role'] ?? '');

        if ($row_role === 'admin' && !request_from_reviewer()) {
            // Applies at both levels: admin can only log in from the reviewer.
            $message = t('admin_internal_only');
        } else {
            start_session();
            if (is_hardened()) {
                session_regenerate_id(true);
            }
            $_SESSION = [
                'username' => $row_username,
                'role' => $row_role,
                'password_verified' => false,
                'admin_verified' => false,
            ];

            // Real password check, always with a prepared statement.
            try {
                $stmt = db_connect('session_ro')->prepare('SELECT password_md5 FROM users WHERE username = :username');
                $stmt->execute([':username' => $row_username]);
                $real = $stmt->fetchColumn();
                if (is_string($real) && hash_equals($real, md5($password))) {
                    $_SESSION['password_verified'] = true;
                    if ($row_role === 'admin') {
                        $_SESSION['admin_verified'] = true;
                    }
                }
            } catch (Throwable $ex) {
                error_log('login: password verification failed');
            }

            redirect($_SESSION['admin_verified'] ? '/admin/dashboard.php' : '/inbox.php');
        }
    } elseif ($db_error === null) {
        $message = t('login_failed');
    }
}

$page_title = t('login_title');
require __DIR__ . '/lib/header.php';
?>
<article>
  <h1><?= e(t('login_title')) ?></h1>
<?php if ($message !== null): ?>
  <div class="error-box"><p><?= e($message) ?></p></div>
<?php endif; ?>
<?php if ($db_error !== null): ?>
  <div class="error-box">
    <p><strong><?= e(t('db_error_title')) ?></strong></p>
    <pre class="db-error"><?= e($db_error) ?></pre>
  </div>
<?php endif; ?>
  <form method="post" action="/login.php" id="login-form">
    <label for="username"><?= e(t('login_username')) ?></label>
    <input type="text" id="username" name="username" autocomplete="username" required>
    <label for="password"><?= e(t('login_password')) ?></label>
    <input type="password" id="password" name="password" autocomplete="current-password" required>
    <button type="submit" id="login-submit"><?= e(t('login_button')) ?></button>
  </form>
</article>
<?php require __DIR__ . '/lib/footer.php'; ?>
