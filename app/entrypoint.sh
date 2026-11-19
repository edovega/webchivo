#!/bin/sh
# App container entrypoint. Runs as root before Apache. Never echoes a flag.
set -eu

log() { echo "[lab-entrypoint] $*" >&2; }

SEAL=/opt/cib203/seal
VAULT=/var/www/private/vault.php
RCE_FILE=/root/rce_flag.txt

# 1. PHP configuration for the current level.
level="${APP_LEVEL:-vulnerable}"
case "$level" in
  vulnerable|hardened) ;;
  *)
    log "WARNING: unknown APP_LEVEL, using vulnerable"
    level=vulnerable
    ;;
esac
cp "/opt/cib203/php/lab-${level}.ini" /usr/local/etc/php/conf.d/zz-lab.ini
log "level: ${level}"

# 2 to 4. Plant flags 7 and 8, then remove the seal binary.
if [ -x "$SEAL" ]; then
  flag7="$("$SEAL" 7)"
  flag8="$("$SEAL" 8)"

  umask 077
  cat > "$VAULT" <<EOF
<?php
/*
 * Internal configuration vault
 *
 * Loaded by config.php. Produces no output.
 *
 * vault_token: ${flag7}
 */
EOF
  chown root:www-data "$VAULT"
  chmod 0640 "$VAULT"

  printf '%s\n' "$flag8" > "$RCE_FILE"
  chown root:root "$RCE_FILE"
  chmod 0600 "$RCE_FILE"
  chmod 0700 /root

  unset flag7 flag8
  rm -f "$SEAL"
  log "flags planted, seal removed"
elif [ -f "$VAULT" ] && [ -f "$RCE_FILE" ]; then
  log "flags already planted, seal already removed (restart)"
else
  log "ERROR: seal binary is missing and flag files do not exist"
  exit 1
fi

# 5. Private invoice storage for the hardened level.
mkdir -p /var/lib/cib203/invoices
chown www-data:www-data /var/lib/cib203/invoices
chmod 0750 /var/lib/cib203/invoices

# 6. Apache.
if [ "$#" -eq 0 ]; then
  set -- apache2-foreground
fi
exec docker-php-entrypoint "$@"
