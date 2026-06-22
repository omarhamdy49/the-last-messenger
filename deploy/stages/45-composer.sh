#!/usr/bin/env bash
# Stage 45 — Composer (global, verified installer).

if command -v composer >/dev/null 2>&1; then
  ok "Composer present: $(composer --version 2>/dev/null | head -n1)"
  composer self-update --no-interaction 2>/dev/null || true
  return 0 2>/dev/null || exit 0
fi

log "Installing Composer..."
EXPECTED="$(curl -fsSL https://composer.github.io/installer.sig)"
php -r "copy('https://getcomposer.org/installer', '/tmp/composer-setup.php');"
ACTUAL="$(php -r "echo hash_file('sha384', '/tmp/composer-setup.php');")"
[[ "${EXPECTED}" == "${ACTUAL}" ]] || die "Composer installer checksum mismatch — aborting."

php /tmp/composer-setup.php --quiet --install-dir=/usr/local/bin --filename=composer
rm -f /tmp/composer-setup.php
ok "Composer installed: $(composer --version | head -n1)"
