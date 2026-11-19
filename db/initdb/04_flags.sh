#!/bin/bash
# Plants flags 1 to 6 from the seal binary, then deletes the binary.
# Runs once, as postgres, when the data volume is empty. Never echoes a flag.
set -euo pipefail

SEAL=/opt/cib203/seal

if [ ! -x "$SEAL" ]; then
  echo "04_flags: ERROR: seal binary not found" >&2
  exit 1
fi

f1="$("$SEAL" 1)"
f2="$("$SEAL" 2)"
f3="$("$SEAL" 3)"
f4="$("$SEAL" 4)"
f5="$("$SEAL" 5)"
f6="$("$SEAL" 6)"

psql -v ON_ERROR_STOP=1 -q \
  --username "$POSTGRES_USER" --dbname "$POSTGRES_DB" \
  -v f1="$f1" -v f2="$f2" -v f3="$f3" -v f4="$f4" -v f5="$f5" -v f6="$f6" <<'SQL'
INSERT INTO secret_settings (name, value) VALUES ('catalog_flag', :'f1');

INSERT INTO tender_vault (code, note) VALUES ('LIC-2026-000', 'Nota interna de la comisión: ' || :'f2');

INSERT INTO inboxes (username, title, content) VALUES
    ('finanzas', 'Conciliación bancaria', 'Código de conciliación ' || :'f3'),
    ('auditoria', 'Informe de auditoría', 'Sello del informe ' || :'f4'),
    ('admin', 'Bandera de administración', :'f5'),
    ('compras', 'Sin documentos', ''),
    ('bodega', 'Sin documentos', ''),
    ('soporte', 'Sin documentos', '');

INSERT INTO reviewer_notes (name, value) VALUES ('internal_note', :'f6');
SQL

unset f1 f2 f3 f4 f5 f6
rm -f "$SEAL"
echo "04_flags: flags planted, seal removed" >&2
