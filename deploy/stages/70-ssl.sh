#!/usr/bin/env bash
# Stage 70 — Let's Encrypt TLS via certbot (webroot), then swap Nginx to HTTPS.
# Auto-renewal is handled by the certbot systemd timer; we add a reload hook.

is_installed certbot || apt_quiet install certbot

ACME_WEBROOT="/var/www/_letsencrypt"
install -d -m 0755 "${ACME_WEBROOT}"
chown -R www-data:www-data "${ACME_WEBROOT}"

STAGING_FLAG=""
[[ "${LETSENCRYPT_STAGING:-false}" == "true" ]] && STAGING_FLAG="--staging"

CERT_DIR="/etc/letsencrypt/live/${APP_DOMAIN}"
if [[ -d "${CERT_DIR}" ]] && [[ "${FORCE:-0}" != "1" ]]; then
  ok "Certificate already present for ${APP_DOMAIN}; skipping issuance."
else
  log "Requesting certificate for ${APP_DOMAIN} + ${APP_WWW}..."
  log "DNS for both must already point to ${SERVER_IP} or issuance will fail."
  certbot certonly --webroot -w "${ACME_WEBROOT}" \
    -d "${APP_DOMAIN}" -d "${APP_WWW}" \
    --email "${CERTBOT_EMAIL}" --agree-tos --no-eff-email \
    --non-interactive --keep-until-expiring ${STAGING_FLAG} \
    || die "Certbot issuance failed. Check DNS A records for ${APP_DOMAIN}/${APP_WWW} -> ${SERVER_IP}."
fi

# TLS protocols/ciphers are set directly in the server blocks + stock nginx.conf
# (no external options-ssl-nginx.conf / dhparam dependency).

# Swap the HTTP-only block for the full HTTPS block.
export APP_DOMAIN APP_WWW APP_DIR PHP_VERSION PHP_UPLOAD_MAX ACME_WEBROOT
log "Installing HTTPS server block..."
render_template "${TEMPLATES_DIR}/nginx-https.conf" "/etc/nginx/sites-available/${APP_DOMAIN}"
nginx -t
systemctl reload nginx

# Reload nginx automatically after each renewal.
install -d /etc/letsencrypt/renewal-hooks/deploy
cat > /etc/letsencrypt/renewal-hooks/deploy/reload-nginx.sh <<'EOF'
#!/usr/bin/env bash
systemctl reload nginx
EOF
chmod +x /etc/letsencrypt/renewal-hooks/deploy/reload-nginx.sh

systemctl enable --now certbot.timer >/dev/null 2>&1 || true
log "Dry-running renewal..."
certbot renew --dry-run >/dev/null 2>&1 && ok "Renewal dry-run passed." || warn "Renewal dry-run failed — review certbot logs."
ok "HTTPS live for https://${APP_DOMAIN}."
