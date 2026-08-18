#!/usr/bin/env bash
# Stage 65 — Nginx: global perf/security config + an HTTP-first server block.
# TLS (443 + redirect) is layered on by stage 70-ssl once certs exist.

is_installed nginx || apt_quiet install nginx

# Dedicated ACME webroot (used by certbot --webroot, served by both http/https).
ACME_WEBROOT="/var/www/_letsencrypt"
install -d -m 0755 "${ACME_WEBROOT}"
chown -R www-data:www-data "${ACME_WEBROOT}"

export APP_DOMAIN APP_WWW APP_DIR PHP_VERSION PHP_UPLOAD_MAX ACME_WEBROOT

# Harden the stock nginx.conf in place (idempotent): hide version banner. The
# stock file ships `server_tokens build;` which we can't override from conf.d
# (duplicate-directive error), so flip it here.
if grep -qE '^\s*server_tokens\s+(build|on)\s*;' /etc/nginx/nginx.conf; then
  backup_file /etc/nginx/nginx.conf
  sed -i -E 's/^\s*server_tokens\s+(build|on)\s*;.*/\tserver_tokens off;/' /etc/nginx/nginx.conf
  ok "Set server_tokens off in nginx.conf"
fi

# events{} tunables — same in-place treatment, and for the same reason: they cannot be set
# from conf.d (that is http{} context), so they have to be patched into the stock file.
#
# Ubuntu ships worker_connections 768. With worker_processes auto (2 here) that is 1536
# slots, but every proxied request consumes TWO — one for the client, one for the FastCGI
# upstream — so the real client ceiling is ~768. Behind Cloudflare the origin sees
# long-lived keepalive connections from many edge PoPs, so that ceiling is closer than it
# looks. multi_accept lets a worker take all pending connections per event-loop pass
# instead of one.
#
# NOTE: worker_connections is read at startup. `nginx -s reload` does NOT apply it —
# systemctl restart nginx is required, which is why this is a provision-time change.
if grep -qE '^\s*worker_connections\s+768\s*;' /etc/nginx/nginx.conf; then
  backup_file /etc/nginx/nginx.conf
  sed -i -E 's/^\s*worker_connections\s+768\s*;.*/\tworker_connections 4096;/' /etc/nginx/nginx.conf
  ok "Raised worker_connections 768 -> 4096 in nginx.conf"
fi

if grep -qE '^\s*#\s*multi_accept\s+on\s*;' /etc/nginx/nginx.conf; then
  backup_file /etc/nginx/nginx.conf
  sed -i -E 's/^\s*#\s*multi_accept\s+on\s*;.*/\tmulti_accept on;/' /etc/nginx/nginx.conf
  ok "Enabled multi_accept in nginx.conf"
fi

# Global directives (gzip tuning, TLS sessions, rate-limit zones) — http{} context.
log "Writing global Nginx config..."
render_template "${TEMPLATES_DIR}/nginx-global.conf" "/etc/nginx/conf.d/tlm-global.conf"

# Shared app/PHP location block, included by both the HTTP and HTTPS server blocks.
install -d /etc/nginx/snippets
render_template "${TEMPLATES_DIR}/nginx-app.conf" "/etc/nginx/snippets/tlm-app.conf"

# Cloudflare real-IP restoration: the site is proxied through Cloudflare, so the
# TCP peer is a Cloudflare edge. Without this, rate-limit zones, logs, and
# Laravel's request IP all see Cloudflare IPs instead of real visitors.
# Ranges are fetched live (they change); falls back to skipping if unreachable.
CF_CONF="/etc/nginx/conf.d/tlm-cloudflare-realip.conf"
log "Configuring Cloudflare real-IP restoration..."
{
  echo "# Cloudflare real visitor IP — generated $(date -u +%FT%TZ)"
  for url in https://www.cloudflare.com/ips-v4 https://www.cloudflare.com/ips-v6; do
    curl -fsSL "$url" 2>/dev/null | while read -r cidr; do
      [[ -n "$cidr" ]] && echo "set_real_ip_from ${cidr};"
    done
  done
  echo "real_ip_header CF-Connecting-IP;"
  echo "real_ip_recursive on;"
} > "${CF_CONF}.tmp"
# FAIL CLOSED. Without real-IP restoration every request appears to come from the
# Cloudflare edge IP that proxied it, so nginx's `limit_conn perip 20` and
# `limit_req zone=api` key on the EDGE rather than the visitor: every visitor behind a
# given Cloudflare datacenter then shares one 20-connection budget. The result is mass
# 503s that look exactly like an origin outage, and they arrive precisely when traffic is
# highest. Warning and continuing turned a transient curl failure into a latent outage, so
# a fetch failure is now fatal unless an existing good config is already in place.
#
# Sanity-check the count too: Cloudflare publishes ~15 v4 + ~7 v6 ranges. A handful of
# lines means we fetched an error page that happened to contain the string.
CF_RANGES="$(grep -c set_real_ip_from "${CF_CONF}.tmp" 2>/dev/null || echo 0)"

if [[ "${CF_RANGES}" -ge 10 ]]; then
  mv "${CF_CONF}.tmp" "${CF_CONF}"
  ok "Cloudflare real-IP config installed (${CF_RANGES} ranges)."
elif [[ -s "${CF_CONF}" ]] && grep -q set_real_ip_from "${CF_CONF}"; then
  # Keep the last known-good file rather than degrading to edge-IP rate limiting.
  rm -f "${CF_CONF}.tmp"
  warn "Could not refresh Cloudflare IP ranges (got ${CF_RANGES}); keeping the existing $(grep -c set_real_ip_from "${CF_CONF}") ranges."
elif [[ "${CF_REALIP_OPTIONAL:-0}" == "1" ]]; then
  rm -f "${CF_CONF}.tmp"
  warn "Could not fetch Cloudflare IP ranges (got ${CF_RANGES}); CF_REALIP_OPTIONAL=1 set, continuing WITHOUT real-IP restoration. Rate limits will key on the Cloudflare edge IP."
else
  rm -f "${CF_CONF}.tmp"
  die "Could not fetch Cloudflare IP ranges (got ${CF_RANGES}) and no previous config exists. Refusing to provision nginx without real-IP restoration — rate limiting would key on the Cloudflare edge IP and 503 real visitors. Re-run once network access is restored, or set CF_REALIP_OPTIONAL=1 to override deliberately."
fi

# HTTP-only site so the app is reachable and ACME challenges can be served
# before a certificate exists.
#
# GUARD — do not clobber a live HTTPS box. This stage is HTTP-first by design: stage
# 70-ssl layers 443 back on afterwards. Re-running it on a server that is ALREADY
# serving HTTPS therefore replaces a working TLS server block with a plaintext one,
# and every visitor gets a Cloudflare 521 until stage 70 runs again. That is not
# hypothetical — it took rasoolallah.com down for ~10 minutes on 18 Aug 2026.
#
# Note this guard deliberately does NOT key off FORCE. FORCE=1 is the normal way to
# re-run a completed stage, so honouring it here would disarm the guard in exactly
# the situation it exists for. Overwriting is an explicit, separate opt-in.
SITE_FILE="/etc/nginx/sites-available/${APP_DOMAIN}"

if [[ "${NGINX_REWRITE_SITE:-0}" != "1" ]] && tls_site_is_live "${SITE_FILE}"; then
  warn "${APP_DOMAIN} already serves HTTPS — keeping its existing server block."
  warn "Global config, app snippet and Cloudflare real-IP above were still refreshed."
  warn "To replace it with the HTTP-only bootstrap block: NGINX_REWRITE_SITE=1 ./provision.sh 65-nginx"
else
  log "Writing HTTP server block for ${APP_DOMAIN}..."
  render_template "${TEMPLATES_DIR}/nginx-http.conf" "${SITE_FILE}"
fi

ln -sfn "${SITE_FILE}" "/etc/nginx/sites-enabled/${APP_DOMAIN}"
rm -f /etc/nginx/sites-enabled/default

log "Validating Nginx config..."
nginx -t

systemctl enable nginx >/dev/null 2>&1 || true
systemctl reload nginx 2>/dev/null || systemctl restart nginx
ok "Nginx serving HTTP for ${APP_DOMAIN} (run stage 70-ssl for HTTPS)."
