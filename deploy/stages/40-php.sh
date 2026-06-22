#!/usr/bin/env bash
# Stage 40 — PHP 8.5 (FPM + CLI) with all extensions this Laravel app needs,
# a memory-tuned FPM pool, and OPcache + JIT.

PV="${PHP_VERSION}"

# Ubuntu 26.04 "resolute" ships PHP 8.5 natively (distro-supported, gets
# security updates, no third-party PPA needed). We install the native packages
# directly; if PHP_VERSION isn't available the apt install below fails loudly.
wait_apt_lock
apt-get update -y

log "Installing PHP ${PV} and extensions..."
# Extensions mapped to deps: mysql(DB), redis(cache/session), gd+exif(medialibrary/
# intervention), zip(excel/composer), intl(translatable), bcmath, mbstring, xml,
# curl. NOTE: on PHP 8.5/Ubuntu 26.04 OPcache is built into the core SAPI (there
# is no php8.5-opcache package) and exif ships in php-common — so neither is a
# separate package here.
apt_quiet install \
  "php${PV}-fpm" "php${PV}-cli" "php${PV}-common" \
  "php${PV}-mysql" "php${PV}-redis" \
  "php${PV}-mbstring" "php${PV}-xml" "php${PV}-curl" "php${PV}-zip" \
  "php${PV}-gd" "php${PV}-bcmath" "php${PV}-intl" "php${PV}-readline"

FPM_POOL="/etc/php/${PV}/fpm/pool.d/www.conf"
FPM_INI="/etc/php/${PV}/fpm/conf.d/99-tlm.ini"
CLI_INI="/etc/php/${PV}/cli/conf.d/99-tlm.ini"
OPCACHE_INI="/etc/php/${PV}/fpm/conf.d/10-tlm-opcache.ini"

export PV PHP_FPM_MAX_CHILDREN PHP_FPM_START_SERVERS PHP_FPM_MIN_SPARE \
       PHP_FPM_MAX_SPARE PHP_MEMORY_LIMIT PHP_UPLOAD_MAX PHP_OPCACHE_JIT_BUFFER TIMEZONE

log "Writing FPM pool + OPcache/JIT config..."
render_template "${TEMPLATES_DIR}/php-fpm-pool.conf" "${FPM_POOL}"
render_template "${TEMPLATES_DIR}/php-tuning.ini"    "${FPM_INI}"
render_template "${TEMPLATES_DIR}/php-tuning.ini"    "${CLI_INI}"
render_template "${TEMPLATES_DIR}/opcache.ini"       "${OPCACHE_INI}"

# Verify OPcache is actually present (built-in on 8.5); warn if our tuning is moot.
if "php${PV}" -m 2>/dev/null | grep -qi 'Zend OPcache\|opcache'; then
  ok "OPcache present (built-in)."
else
  warn "OPcache not detected in 'php -m' — JIT/opcache tuning will have no effect."
fi

log "Validating PHP-FPM config..."
"php-fpm${PV}" -t

systemctl enable "php${PV}-fpm" >/dev/null 2>&1 || true
systemctl restart "php${PV}-fpm"
ok "PHP ${PV} installed and tuned. Modules: $(php${PV} -m | tr '\n' ' ' | sed 's/  */ /g' | cut -c1-200)..."
