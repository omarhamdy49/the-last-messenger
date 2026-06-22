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
# Only install if we actually got CIDRs (avoid an empty/broken config).
if grep -q set_real_ip_from "${CF_CONF}.tmp"; then
  mv "${CF_CONF}.tmp" "${CF_CONF}"
  ok "Cloudflare real-IP config installed ($(grep -c set_real_ip_from "${CF_CONF}") ranges)."
else
  rm -f "${CF_CONF}.tmp"
  warn "Could not fetch Cloudflare IP ranges — skipping real-IP restoration."
fi

# HTTP-only site so the app is reachable and ACME challenges can be served
# before a certificate exists.
log "Writing HTTP server block for ${APP_DOMAIN}..."
render_template "${TEMPLATES_DIR}/nginx-http.conf" "/etc/nginx/sites-available/${APP_DOMAIN}"
ln -sfn "/etc/nginx/sites-available/${APP_DOMAIN}" "/etc/nginx/sites-enabled/${APP_DOMAIN}"
rm -f /etc/nginx/sites-enabled/default

log "Validating Nginx config..."
nginx -t

systemctl enable nginx >/dev/null 2>&1 || true
systemctl reload nginx 2>/dev/null || systemctl restart nginx
ok "Nginx serving HTTP for ${APP_DOMAIN} (run stage 70-ssl for HTTPS)."
