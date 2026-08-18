#!/usr/bin/env bash
# Self-check for tls_site_is_live() — the guard that decides whether stage 65 may
# overwrite an existing nginx server block. Run it directly; no framework, no root:
#
#     ./deploy/lib/selftest-nginx-guard.sh
#
# It exists because the failure mode is silent and expensive: get this predicate
# wrong in the "true" direction and a re-provision takes a live HTTPS site down.

source "$(dirname "${BASH_SOURCE[0]}")/common.sh"

TMP="$(mktemp -d)"
trap 'rm -rf "${TMP}"' EXIT
fails=0

check() { # check <expectation: live|dead> <label> <site-file>
  local want="$1" label="$2" site="$3" got
  if tls_site_is_live "${site}"; then got=live; else got=dead; fi
  if [[ "${got}" == "${want}" ]]; then
    printf '  ok    %-46s -> %s\n' "${label}" "${got}"
  else
    printf '  FAIL  %-46s -> %s (wanted %s)\n' "${label}" "${got}" "${want}"
    fails=$((fails + 1))
  fi
}

touch "${TMP}/real.pem"

check dead "missing site file" "${TMP}/absent.conf"

printf 'server {\n  listen 80;\n}\n' > "${TMP}/http-only.conf"
check dead "HTTP-only bootstrap block" "${TMP}/http-only.conf"

printf 'server {\n  listen 443 ssl;\n  ssl_certificate %s;\n}\n' "${TMP}/real.pem" > "${TMP}/tls.conf"
check live "443 + certificate present on disk" "${TMP}/tls.conf"

printf 'server {\n  listen [::]:443 ssl;\n  ssl_certificate %s;\n}\n' "${TMP}/real.pem" > "${TMP}/tls6.conf"
check live "IPv6 listener [::]:443" "${TMP}/tls6.conf"

# The one that must NOT be preserved: nginx cannot start against a missing cert, so
# keeping this block would strand the box. Rewriting it with plaintext is the recovery.
printf 'server {\n  listen 443 ssl;\n  ssl_certificate %s/gone.pem;\n}\n' "${TMP}" > "${TMP}/tls-nocert.conf"
check dead "443 but certificate file deleted" "${TMP}/tls-nocert.conf"

printf 'server {\n  listen 80;\n  # listen 443 ssl;\n  # ssl_certificate %s;\n}\n' "${TMP}/real.pem" > "${TMP}/commented.conf"
check dead "commented-out TLS directives" "${TMP}/commented.conf"

if [[ ${fails} -eq 0 ]]; then echo "  all passed"; else echo "  ${fails} FAILED"; exit 1; fi
