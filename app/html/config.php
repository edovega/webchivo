<?php
// Lab credentials
const DB_NAME = 'suppliers';
const DB_PORT = 5432;

const DB_USER_CATALOG = 'catalog_ro';
const DB_PASS_CATALOG = 'Catalog-Lab-2026';

const DB_USER_TENDERS = 'tenders_ro';
const DB_PASS_TENDERS = 'Tenders-Lab-2026';

const DB_USER_AUTH = 'auth_ro';
const DB_PASS_AUTH = 'Auth-Lab-2026';

const DB_USER_SESSION = 'session_ro';
const DB_PASS_SESSION = 'Session-Lab-2026';

const DB_USER_MAILBOX = 'mailbox_rw';
const DB_PASS_MAILBOX = 'Mailbox-Lab-2026';

const DB_ROLES = [
    DB_USER_CATALOG => DB_PASS_CATALOG,
    DB_USER_TENDERS => DB_PASS_TENDERS,
    DB_USER_AUTH => DB_PASS_AUTH,
    DB_USER_SESSION => DB_PASS_SESSION,
    DB_USER_MAILBOX => DB_PASS_MAILBOX,
];

require_once '/var/www/private/vault.php';
