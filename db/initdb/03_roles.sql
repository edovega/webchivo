-- One role per page group, none of them superuser.

REVOKE ALL ON DATABASE suppliers FROM PUBLIC;
REVOKE ALL ON SCHEMA public FROM PUBLIC;

CREATE ROLE catalog_ro  LOGIN PASSWORD 'Catalog-Lab-2026'  NOSUPERUSER NOCREATEDB NOCREATEROLE NOREPLICATION;
CREATE ROLE tenders_ro  LOGIN PASSWORD 'Tenders-Lab-2026'  NOSUPERUSER NOCREATEDB NOCREATEROLE NOREPLICATION;
CREATE ROLE auth_ro     LOGIN PASSWORD 'Auth-Lab-2026'     NOSUPERUSER NOCREATEDB NOCREATEROLE NOREPLICATION;
CREATE ROLE session_ro  LOGIN PASSWORD 'Session-Lab-2026'  NOSUPERUSER NOCREATEDB NOCREATEROLE NOREPLICATION;
CREATE ROLE mailbox_rw  LOGIN PASSWORD 'Mailbox-Lab-2026'  NOSUPERUSER NOCREATEDB NOCREATEROLE NOREPLICATION;
CREATE ROLE reviewer_rw LOGIN PASSWORD 'Reviewer-Lab-2026' NOSUPERUSER NOCREATEDB NOCREATEROLE NOREPLICATION;

GRANT CONNECT ON DATABASE suppliers TO catalog_ro, tenders_ro, auth_ro, session_ro, mailbox_rw, reviewer_rw;
GRANT USAGE ON SCHEMA public TO catalog_ro, tenders_ro, auth_ro, session_ro, mailbox_rw, reviewer_rw;

-- catalog.php, search.php
GRANT SELECT ON products, suppliers, users, secret_settings TO catalog_ro;

-- tender.php
GRANT SELECT ON tenders, tender_vault TO tenders_ro;

-- login.php
GRANT SELECT ON users TO auth_ro;

-- inbox.php, admin/dashboard.php, password verification
GRANT SELECT ON users, inboxes TO session_ro;

-- messages.php, report.php, upload_invoice.php, admin/messages.php
GRANT SELECT, INSERT ON messages, reports, invoices TO mailbox_rw;
GRANT USAGE ON SEQUENCE messages_id_seq, reports_id_seq, invoices_id_seq TO mailbox_rw;

-- reviewer.py
GRANT SELECT ON users TO reviewer_rw;
GRANT UPDATE (password_md5) ON users TO reviewer_rw;
GRANT SELECT ON reviewer_notes TO reviewer_rw;
GRANT SELECT, UPDATE ON reports TO reviewer_rw;

-- Keep injected pg_sleep calls from tying up the lab.
ALTER ROLE catalog_ro SET statement_timeout = '20s';
ALTER ROLE tenders_ro SET statement_timeout = '20s';
ALTER ROLE auth_ro SET statement_timeout = '20s';
