<?php
declare(strict_types=1);

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/i18n.php';

/**
 * Current lab level: "vulnerable" or "hardened". Unknown values fall back to
 * "vulnerable" and log a warning.
 */
function app_level(): string
{
    static $level = null;
    if ($level === null) {
        $raw = getenv('APP_LEVEL');
        if ($raw === false || $raw === '') {
            $raw = 'vulnerable';
        }
        if ($raw !== 'vulnerable' && $raw !== 'hardened') {
            error_log('WARNING: unknown APP_LEVEL, using vulnerable');
            $raw = 'vulnerable';
        }
        $level = $raw;
    }
    return $level;
}

function is_hardened(): bool
{
    return app_level() === 'hardened';
}

function db_host(): string
{
    $host = getenv('DB_HOST');
    return ($host === false || $host === '') ? 'db' : $host;
}

/**
 * PDO connection for the given role. Used for every prepared statement.
 */
function db_connect(string $role): PDO
{
    if (!isset(DB_ROLES[$role])) {
        throw new InvalidArgumentException('unknown database role');
    }
    $dsn = sprintf('pgsql:host=%s;port=%d;dbname=%s', db_host(), DB_PORT, DB_NAME);
    return new PDO($dsn, $role, DB_ROLES[$role], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
}

/**
 * Native pgsql connection for the given role. Used only by the queries that
 * are vulnerable on purpose at the "vulnerable" level.
 *
 * @return \PgSql\Connection|false
 */
function db_connect_legacy(string $role)
{
    if (!isset(DB_ROLES[$role])) {
        return false;
    }
    $conn = sprintf(
        "host=%s port=%d dbname=%s user=%s password=%s connect_timeout=5",
        db_host(),
        DB_PORT,
        DB_NAME,
        $role,
        DB_ROLES[$role]
    );
    return @pg_connect($conn, PGSQL_CONNECT_FORCE_NEW);
}

function start_session(): void
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }
}

/**
 * Resumes a session only if the browser already sent a session cookie, so
 * anonymous visitors never receive one.
 */
function resume_session(): bool
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return true;
    }
    if (!isset($_COOKIE[session_name()])) {
        return false;
    }
    session_start();
    return true;
}

function current_user(): ?string
{
    if (!resume_session()) {
        return null;
    }
    $user = $_SESSION['username'] ?? null;
    return is_string($user) && $user !== '' ? $user : null;
}

function is_admin_verified(): bool
{
    if (!resume_session()) {
        return false;
    }
    return ($_SESSION['admin_verified'] ?? false) === true;
}

/**
 * HTML-encodes a value for output.
 */
function e(?string $value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/**
 * Output used by the XSS exercises: raw at "vulnerable", encoded at "hardened".
 */
function lab_output(?string $value): string
{
    return is_hardened() ? e($value) : (string)$value;
}

/**
 * Per-response CSP nonce, used only at the "hardened" level.
 */
function csp_nonce(): string
{
    static $nonce = null;
    if ($nonce === null) {
        $nonce = base64_encode(random_bytes(16));
    }
    return $nonce;
}

/**
 * Attribute to add to every first-party <script> tag.
 */
function script_nonce_attr(): string
{
    return is_hardened() ? ' nonce="' . e(csp_nonce()) . '"' : '';
}

function redirect(string $path): void
{
    header('Location: ' . $path, true, 302);
    exit;
}

/**
 * True when the request comes from the reviewer container, the only place an
 * admin login is accepted from.
 */
function request_from_reviewer(): bool
{
    $reviewer = gethostbyname('reviewer');
    if ($reviewer === 'reviewer') {
        return false;
    }
    return hash_equals($reviewer, (string)($_SERVER['REMOTE_ADDR'] ?? ''));
}
