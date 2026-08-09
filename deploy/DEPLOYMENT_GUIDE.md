# The Last Messenger — Full Deployment Technical Guide

End-to-end runbook for taking **The Last Messenger** from a bare OVH VPS to a
hardened, performance-tuned production host with green GitHub CI/CD. This is the
narrative companion to [`README.md`](README.md) (which is the terse stage
reference) and documents the **actual** path we followed, including the
real-world gotchas and how each was resolved.

> **Live result:** `https://rasoolallah.com` — Ubuntu 26.04 · PHP 8.5 · MySQL 8 ·
> Redis · Nginx (HTTP/2) · Supervisor · behind Cloudflare (Full strict) · CI/CD
> deploys on push to `main` with zero-downtime releases + auto-rollback.

---

## Table of contents

1. [Architecture overview](#1-architecture-overview)
2. [Prerequisites](#2-prerequisites)
3. [Phase 0 — OVH VPS & first SSH access](#3-phase-0--ovh-vps--first-ssh-access)
4. [Phase 1 — DNS & Cloudflare](#4-phase-1--dns--cloudflare)
5. [Phase 2 — Upload scripts & configure](#5-phase-2--upload-scripts--configure)
6. [Phase 3 — Provision the server](#6-phase-3--provision-the-server)
7. [Phase 4 — TLS (Let's Encrypt through Cloudflare)](#7-phase-4--tls-lets-encrypt-through-cloudflare)
8. [Phase 5 — GitHub CI/CD secrets & workflow](#8-phase-5--github-cicd-secrets--workflow)
9. [Phase 6 — First deploy & verification](#9-phase-6--first-deploy--verification)
10. [Phase 7 — SSH hardening (last)](#10-phase-7--ssh-hardening-last)
11. [Phase 8 — Post-deploy app config](#11-phase-8--post-deploy-app-config)
12. [Phase 9 — Cloudflare Full (strict)](#12-phase-9--cloudflare-full-strict)
13. [Phase 10 — Performance & edge-cache configuration](#13-phase-10--performance--edge-cache-configuration)
14. [Operations runbook](#14-operations-runbook)
15. [Troubleshooting — issues we actually hit](#15-troubleshooting--issues-we-actually-hit)
16. [Architecture decisions](#16-architecture-decisions)

---

## 1. Architecture overview

```
                       ┌────────────────────────┐
   Visitor  ─HTTPS─▶   │   Cloudflare (proxy)   │   SSL: Full (strict)
                       │   edge cert + CDN/WAF  │
                       └───────────┬────────────┘
                            HTTPS (origin LE cert)
                                   ▼
                       ┌────────────────────────┐  OVH VPS  51.195.100.16
                       │ Nginx (HTTP/2, gzip,    │  Ubuntu 26.04 · 2 vCPU · 4GB
                       │ real-IP, rate limits)   │
                       └───────────┬────────────┘
                                   │ fastcgi (unix socket)
                       ┌───────────▼────────────┐
                       │ PHP 8.5 FPM (OPcache+JIT)│
                       │  Laravel 12 app          │
                       └───┬─────────┬─────────┬──┘
                           │         │         │
              ┌────────────▼─┐  ┌────▼────┐  ┌─▼──────────────┐
              │  MySQL 8     │  │  Redis  │  │  Supervisor     │
              │  app data +  │  │ cache + │  │  queue:work×2   │
              │  queue jobs  │  │ session │  │  schedule:work  │
              └──────────────┘  └─────────┘  └────────────────┘
```

- **App code** lives under `/var/www/rasoolallah` using a release model:
  `releases/<timestamp>/`, an atomic `current` symlink, and a `shared/`
  directory (`.env`, `storage/`, `bin/`) symlinked into each release.
- **CI/CD**: push to `main` → GitHub Actions builds assets, ships a zip over
  SSH, and runs the server-side `deploy.sh` (composer install → migrate →
  cache → flip symlink → reload FPM → restart workers → health probe →
  auto-rollback on failure).

---

## 2. Prerequisites

| Need | Value used here |
|------|-----------------|
| OVH VPS | `vps-510cdeaa.vps.ovh.net` → `51.195.100.16`, Ubuntu 26.04, 2 vCPU / 4 GB / 40 GB |
| Domain | `rasoolallah.com` (DNS managed by Cloudflare) |
| SSH keypair | `~/Desktop/rasool-allah/rasoolallah-vps-ssh/ovh_vps[.pub]` (ed25519) |
| GitHub repo | `omarhamdy49/the-last-messenger` (the Laravel app, this repo) |
| Local tools | `ssh`, `rsync`, `git`, `composer`, `php` |
| OVH dashboard | `https://manager.ca.ovhcloud.com/...` — for KVM console (break-glass root) |

> The single SSH keypair is used for **both** your admin access and CI/CD (the
> `deployer` user authorizes `ovh_vps.pub`; GitHub stores `ovh_vps` as a secret).

---

## 3. Phase 0 — OVH VPS & first SSH access

1. **Create / identify the VPS** in the OVH manager. Ubuntu 26.04, note the
   public IPv4 (`51.195.100.16`).
2. OVH provisions a default sudo user **`ubuntu`** with your SSH key installed
   (key-based, passwordless sudo). Confirm access:

   ```bash
   ssh -i ~/Desktop/rasool-allah/rasoolallah-vps-ssh/ovh_vps ubuntu@51.195.100.16
   # inside:
   . /etc/os-release && echo "$PRETTY_NAME"   # Ubuntu 26.04 LTS
   nproc; free -h; df -h /                     # 2 / 3.7Gi / 38G
   sudo -n true && echo "passwordless sudo OK"
   ```

3. **Break-glass**: the OVH **KVM console** (dashboard → VPS → Console) gives
   root regardless of SSH config. This is your recovery path after hardening —
   keep the dashboard link handy.

> We provision as `ubuntu` (full sudo) and **harden SSH last** (Phase 7), so the
> long-running install can't lock itself out mid-way.

---

## 4. Phase 1 — DNS & Cloudflare

The domain is proxied through Cloudflare (orange-cloud). Before TLS issuance:

1. In Cloudflare DNS, point both records at the origin:

   ```
   rasoolallah.com.      A   51.195.100.16   (Proxied)
   www.rasoolallah.com.  A   51.195.100.16   (Proxied)
   ```

2. Verify resolution (will show Cloudflare IPs while proxied — that's expected):

   ```bash
   dig +short rasoolallah.com          # Cloudflare IPs (104.21.x / 172.67.x)
   dig +short vps-510cdeaa.vps.ovh.net # 51.195.100.16 (the real origin)
   ```

3. **Cloudflare passes the ACME HTTP-01 challenge through to the origin on port
   80**, so Let's Encrypt on the origin works even while proxied (verified in
   Phase 4). SSL mode is set to **Full (strict)** at the end (Phase 9), once the
   origin has a valid cert.

---

## 5. Phase 2 — Upload scripts & configure

From your machine, in the repo's `deploy/` directory:

1. **Build `config.env`** from the example and set the deploy user's public key
   (everything else has sane defaults; blank secrets auto-generate):

   ```bash
   cd deploy
   cp config.env.example config.env
   # set DEPLOY_USER_PUBKEY to the CONTENTS of ovh_vps.pub:
   #   DEPLOY_USER_PUBKEY="ssh-ed25519 AAAA... ovh-vps-...-omar.hismail"
   # confirm: APP_DOMAIN=rasoolallah.com, APP_WWW=www..., CERTBOT_EMAIL set,
   #          PHP_VERSION=8.5   ← Ubuntu 26.04 ships 8.5 natively (see §14)
   ```

   `config.env` is git-ignored — it never gets committed.

2. **Upload the `deploy/` folder** to the server (as `ubuntu`):

   ```bash
   KEY=~/Desktop/rasool-allah/rasoolallah-vps-ssh/ovh_vps
   rsync -az -e "ssh -i $KEY" --exclude '.git' ./ ubuntu@51.195.100.16:/home/ubuntu/deploy/
   ssh -i "$KEY" ubuntu@51.195.100.16 'chmod +x ~/deploy/provision.sh ~/deploy/lib/*.sh ~/deploy/stages/*.sh ~/deploy/deploy/deploy.sh'
   ```

---

## 6. Phase 3 — Provision the server

The orchestrator (`provision.sh`) runs idempotent, numbered stages. **Run
everything EXCEPT SSH-hardening (20) and TLS (70) first** — hardening is applied
last (Phase 7) and TLS needs the HTTP server live first (Phase 4).

Because the install takes 10–15 min and `unattended-upgrades` can grab the apt
lock, run it **detached** and poll the log:

```bash
KEY=~/Desktop/rasool-allah/rasoolallah-vps-ssh/ovh_vps
ssh -i "$KEY" ubuntu@51.195.100.16 \
  "sudo bash -c 'cd /home/ubuntu/deploy && setsid nohup ./provision.sh \
     00-preflight 05-system 10-swap 15-user 25-firewall 30-fail2ban \
     35-unattended-upgrades 40-php 45-composer 50-node 52-media-tools \
     55-mysql 60-redis 65-nginx 75-app 80-supervisor 85-logrotate 90-healthcheck \
     > /tmp/prov.log 2>&1 < /dev/null & echo PID \$!'"

# poll:
ssh -i "$KEY" ubuntu@51.195.100.16 'tail -30 /tmp/prov.log; ls /var/lib/tlm-provision/'
```

What the stages do (full list in [`README.md`](README.md)):

| Stage | Result |
|-------|--------|
| 05 system | apt update/upgrade, timezone, locale, core pkgs |
| 10 swap | 2 GB swap + VM/network sysctl + fd limits |
| 15 user | creates `deployer`, authorizes the key, scoped `NOPASSWD` sudo |
| 25/30/35 | UFW (deny-all + 22/80/443), fail2ban, unattended security upgrades |
| 40 php | **PHP 8.5** FPM + extensions + tuned pool + OPcache/JIT |
| 45/50/52 | Composer, Node 20, **ffmpeg + yt-dlp** |
| 55/60 | MySQL 8 (secured, app DB + least-priv user, tuned), Redis (localhost, pwd, LRU) |
| 65 | Nginx global tuning + Cloudflare real-IP + HTTP server block |
| 75/80/85 | release layout + prod `.env`, Supervisor workers + scheduler, logrotate |
| 90 healthcheck | verifies every service + connectivity |

At the end, `90-healthcheck` should report **all green** (app checks are skipped
until the first release is deployed). Generated secrets (DB/Redis/root/APP_KEY)
are written to **`/root/.tlm-credentials`** (chmod 600).

> Re-run any stage with `sudo FORCE=1 ./provision.sh <stage>`.

---

## 7. Phase 4 — TLS (Let's Encrypt through Cloudflare)

With DNS pointing at the origin and Nginx serving HTTP, confirm the ACME path
reaches the origin **through Cloudflare**, then issue the cert:

```bash
# 1) prove Cloudflare forwards the ACME path to origin:80
ssh -i "$KEY" ubuntu@51.195.100.16 \
  'echo ok | sudo tee /var/www/_letsencrypt/.well-known/acme-challenge/probe >/dev/null'
curl -s http://rasoolallah.com/.well-known/acme-challenge/probe     # -> ok

# 2) issue + install the cert and swap Nginx to HTTPS
ssh -i "$KEY" ubuntu@51.195.100.16 \
  'sudo bash -c "cd /home/ubuntu/deploy && ./provision.sh 70-ssl"'
```

`70-ssl` runs `certbot certonly --webroot`, installs the HTTPS server block
(HTTP→HTTPS redirect, `www`→apex, HSTS + security headers, modern ciphers),
reloads Nginx, and enables the `certbot.timer` for auto-renewal (with an
nginx-reload deploy hook).

Verify:

```bash
curl -sI --resolve rasoolallah.com:443:51.195.100.16 https://rasoolallah.com/ | head -1   # HTTP/2 200/404
curl -svo /dev/null --resolve rasoolallah.com:443:51.195.100.16 https://rasoolallah.com/ 2>&1 | grep -i issuer
#   issuer: ... Let's Encrypt
```

Cloudflare's `https://rasoolallah.com` returns **521** until the origin has a
443 cert — after this step it returns 200/404 (origin reachable over HTTPS).

---

## 8. Phase 5 — GitHub CI/CD secrets & workflow

The workflow ([`.github/workflows/deploy.yml`](../.github/workflows/deploy.yml))
triggers on push to `main`: build Vite assets → composer preflight → zip → scp →
run the server's `deploy.sh`.

1. **Commit & push** the `deploy/` infra + workflow to `main` (required for the
   workflow to exist on the triggering branch).

2. **Add repo secrets** (Settings → Secrets and variables → Actions). We added
   them via the GitHub web UI; `gh secret set` also works:

   | Secret | Value |
   |--------|-------|
   | `SSH_PRIVATE_KEY` | contents of the **private** key `ovh_vps` |
   | `SSH_USERNAME` | `deployer` |
   | `SSH_SERVER_IP` | `51.195.100.16` |
   | `DEPLOY_PATH` | *(optional)* defaults to `/var/www/rasoolallah` |

   ```bash
   # CLI alternative (keeps the key out of a browser/clipboard):
   gh secret set SSH_PRIVATE_KEY < ~/Desktop/rasool-allah/rasoolallah-vps-ssh/ovh_vps
   gh secret set SSH_USERNAME --body deployer
   gh secret set SSH_SERVER_IP --body 51.195.100.16
   ```

> The workflow uses `StrictHostKeyChecking=no` for scp/ssh and **does not run
> `ssh-keyscan`** — keyscan's multi-key probe produces "Connection closed
> [preauth]" lines that fail2ban (in aggressive mode) treats as an attack and
> bans the runner (see §14).

---

## 9. Phase 6 — First deploy & verification

Push to `main` (or re-run the workflow). The server-side `deploy.sh`:

1. unpacks into `releases/<ts>/`
2. symlinks shared `.env` + `storage/`
3. `composer install --no-dev --optimize-autoloader`
4. `php artisan migrate --force`
5. caches config/route/view/event + `storage:link`
6. **atomically** flips `current` → new release
7. `systemctl reload php8.5-fpm` (resets OPcache) + `supervisorctl restart all`
8. health probe (HTTP 200/301/302) — **auto-rollback** to the previous release on failure
9. prunes old releases (keeps 5)

Verify end-to-end:

```bash
# release landed?
ssh -i "$KEY" deployer@51.195.100.16 'readlink /var/www/rasoolallah/current; sudo supervisorctl status'
# app serving through Cloudflare?
curl -sI https://rasoolallah.com/ | head -1        # HTTP/2 200
```

---

## 10. Phase 7 — SSH hardening (last)

Only after `deployer` login + CI both work, lock SSH down:

```bash
# verify deployer works FIRST (same key CI uses):
ssh -i "$KEY" deployer@51.195.100.16 'whoami; sudo -n systemctl reload nginx && echo sudo-scope-ok'

# then apply hardening
ssh -i "$KEY" ubuntu@51.195.100.16 'sudo bash -c "cd /home/ubuntu/deploy && ./provision.sh 20-ssh-hardening"'
```

`20-ssh-hardening` sets `PermitRootLogin no`, `PasswordAuthentication no`,
`AllowUsers deployer`, `MaxAuthTries 3`, etc.

Verify (root + `ubuntu` are now refused; `deployer` still works):

```bash
ssh -i "$KEY" deployer@51.195.100.16 'echo deployer-ok'     # works
ssh -i "$KEY" root@51.195.100.16     'true'                 # Permission denied
ssh -i "$KEY" ubuntu@51.195.100.16   'true'                 # Permission denied
```

> ⚠️ **After this, root is OVH-KVM-console-only.** `deployer` keeps scoped sudo
> (systemctl reload php-fpm/nginx, supervisorctl). Any future system-package
> install on the live box goes through the OVH console (see §13).

---

## 11. Phase 8 — Post-deploy app config

After the first successful deploy, the DB has tables but no data.

1. **Seed essential data** (roles/permissions, admin users, site settings,
   menus, FAQs, etc.) — run as `deployer`, no root needed:

   ```bash
   ssh -i "$KEY" deployer@51.195.100.16 'cd /var/www/rasoolallah/current && php artisan db:seed --force'
   ```

2. **Set strong admin passwords.** The seeder is env-driven and create-only;
   set `SUPER_ADMIN_PASSWORD` / `ADMIN_PASSWORD` in `shared/.env` *before*
   seeding, or rotate an existing account afterwards:

   ```bash
   ssh -i "$KEY" deployer@51.195.100.16 'cd /var/www/rasoolallah/current && php artisan tinker --execute "
     \$u=App\Models\User::where(\"email\",\"supm@dwit.com\")->first();
     \$u->forceFill([\"password\"=>Hash::make(\"<STRONG-PW>\")])->save();"'
   ```

   Admin portal: `https://rasoolallah.com/su/portal/v1/admin`.

3. **Media tools** (ffmpeg + yt-dlp) — installed by stage `52-media-tools` on
   fresh provisions (apt ffmpeg → `/usr/bin`, yt-dlp → `/usr/local/bin`). On
   this already-hardened box (root console-only) they were instead installed as
   `deployer`-owned binaries under `shared/bin/` with `.env` overrides
   `YT_DLP_PATH` / `FFMPEG_PATH` / `FFPROBE_PATH`. Verify:

   ```bash
   ssh -i "$KEY" deployer@51.195.100.16 'cd /var/www/rasoolallah/current && php artisan tinker --execute "
     echo config(\"youtube.yt_dlp_path\").PHP_EOL;
     echo config(\"media-library.ffmpeg_path\").PHP_EOL;"'
   ```

4. **SMTP** — production `.env` ships the `log` mailer. To send real mail, set
   `MAIL_MAILER=smtp` + host/port/username/password/encryption/from-address in
   `shared/.env`, then `php artisan config:cache` + reload FPM.

---

## 12. Phase 9 — Cloudflare Full (strict)

The origin now has a valid Let's Encrypt cert, so tighten Cloudflare's origin
validation:

- **Cloudflare dashboard → `rasoolallah.com` → SSL/TLS → Overview → Encryption
  mode → Full (strict).**

> The Cloudflare dashboard **does not render under browser automation** (bot
> protection), so this is a manual step (or via the CF API with a
> `Zone → SSL and Certificates → Edit` token). Verify:

```bash
curl -sI https://rasoolallah.com/ | head -1     # HTTP/2 200  (525/526 ⇒ strict can't validate origin)
```

---

## 13. Phase 10 — Performance & edge-cache configuration

Applied after the baseline deploy to take mobile PageSpeed from "very bad" to
**97** (all Core Web Vitals green). Full applied state + the exact API calls live
in [`../TODO_cloudflare-perf.md`](../TODO_cloudflare-perf.md).

### Build / front-end (in the repo — CI builds it)
- **Vite minifier = terser** (`vite.config.js` `build.minify: 'terser'`) — needs
  the `terser` devDependency (do not remove it; CI's `npm run build` requires it).
- **Homepage inlines critical CSS** + defers the full sheet
  (`resources/views/layouts/public.blade.php`, only for `routeIs('public.home')`
  when not running the Vite dev server). Critical set =
  `resources/css/critical-home.css`. **Regenerate after CSS changes:**
  `npm i -D penthouse puppeteer && node gen-critical.cjs public/build/assets/app-*.css`
  (penthouse/puppeteer are NOT kept as deps so CI doesn't download Chromium).
- **Self-hosted fonts** in `public/fonts/*.woff2` (`@font-face` + `unicode-range`
  in `app.css`, `font-display:swap`), preloaded by locale in the layout — no
  third-party Google Fonts.
- **Curated Lucide icon subset** `resources/js/icons.js` (union of view literals +
  admin icon-picker choices + seeded icons). Regenerate when new `data-lucide`
  icons are introduced, or they won't render. The full set is ~579KB; the subset
  is ~100KB raw.
- **Images:** WebP, resized to display size, `width`/`height` + `loading="lazy"`
  on below-the-fold, hero preloaded with `fetchpriority="high"` + `srcset`.

### Origin cache headers (in the repo)
`App\Http\Middleware\CacheGuestPages` (PREPENDED to the web group so its response
phase runs outermost, after `StartSession`):
- Anonymous `GET` of `public.home` → `Cache-Control: public, s-maxage=1800,
  stale-while-revalidate=600` **(30-min edge TTL — change here, not in Cloudflare)**
  and **strips all `Set-Cookie`** so the edge never caches a session.
- Authenticated requests (incl. admin) → `Cache-Control: private, no-store`.
- To make another page cacheable, add its route name to `CACHEABLE_ROUTES`.

### Cloudflare (set via API; recreate with the token if re-provisioning)
Two cache rules in the `http_request_cache_settings` phase
(`PUT /zones/{id}/rulesets/phases/http_request_cache_settings/entrypoint`):
1. **Bypass (first):** admin (`/su/portal/`), API (`/api/`), auth/account routes,
   **and any request carrying `the-last-messenger-session`** → `cache: false`.
   This guarantees the admin portal and logged-in pages are NEVER edge-cached.
2. **Homepage (second):** `path eq "/" and not cookie contains session` →
   `cache: true`, `edge_ttl` + `browser_ttl` = **`respect_origin`** (so the
   30-min TTL above drives it — Cloudflare imposes no TTL of its own).

Dashboard toggles (need a token with *Zone Settings: Edit*, or do them in the UI):
**Brotli**, **Early Hints**, **Tiered Cache**. (Polish is Pro-plan only.)

**Verify (must all hold):**
```bash
curl -s -D - -o /dev/null https://rasoolallah.com/                | grep -i cf-cache-status  # HIT + s-maxage=1800
curl -s -D - -o /dev/null https://rasoolallah.com/su/portal/v1/admin| grep -i cf-cache-status # DYNAMIC (never HIT)
curl -s -D - -o /dev/null https://rasoolallah.com/api/v1/articles  | grep -i cf-cache-status  # DYNAMIC + HTTP 401 without a bearer token
curl -s -D - -o /dev/null https://rasoolallah.com/ -H "Cookie: the-last-messenger-session=x" | grep -i cf-cache-status  # DYNAMIC
```

### ⚠️ Cache-purge gotcha (operational)
Vite build assets are content-hashed → updating them auto-busts cache. But
**static files keep their names** (`public/images/*`, `public/fonts/*`) and carry
a 30-day `immutable` cache, so after changing one you MUST **purge Cloudflare**
(`POST /zones/{id}/purge_cache` with `{"files":[...]}` or purge-everything).
After a **purge-everything**, re-warm the sub-resources (fonts/CSS/JS/images)
before testing — a cold edge tanks the first PageSpeed run.

> **FCP floor:** even with the HTML edge-cached (HIT), FCP sits at ~1.8s on PSI's
> emulated slow-4G — that's the connection-setup floor (DNS+TCP+TLS round-trips),
> not a server/code limit. Real-network users are much faster.

---

## 14. Operations runbook

**Deploy:** push to `main` (or re-run the workflow). Manual:

```bash
sudo -u deployer /var/www/rasoolallah/bin/deploy.sh /path/to/release.zip <sha>
```

**Rollback:** the previous release stays under `releases/`. To roll back:

```bash
ssh -i "$KEY" deployer@51.195.100.16 '
  ls -1dt /var/www/rasoolallah/releases/*/ | sed -n 2p   # previous
  ln -nfs <previous> /var/www/rasoolallah/current
  sudo systemctl reload php8.5-fpm && sudo supervisorctl restart all'
```

**Queue workers / scheduler (Supervisor):**

```bash
ssh -i "$KEY" deployer@51.195.100.16 'sudo supervisorctl status'
ssh -i "$KEY" deployer@51.195.100.16 'sudo supervisorctl restart all'
```

Queue = **database** (MySQL `jobs` table); cache + sessions = Redis. Workers +
scheduler auto-start on boot (`supervisor` unit `enabled` + `autostart=true`).

**Logs:**

```bash
# app:        /var/www/rasoolallah/shared/storage/logs/laravel-*.log
# workers:    /var/www/rasoolallah/shared/storage/logs/worker.log / scheduler.log
# nginx:      /var/log/nginx/rasoolallah.com.{access,error}.log
# mysql slow: /var/log/mysql/slow.log
```

**TLS renewal:** automatic via `certbot.timer`; test with
`sudo certbot renew --dry-run` (needs root → OVH console).

**Root access (post-hardening):** OVH manager → VPS → **Console (KVM)** → log in
as root. `deployer` over SSH only has the scoped sudo verbs above.

**Secrets:** `/root/.tlm-credentials` (DB/Redis/root/APP_KEY) — root-only.

---

## 15. Troubleshooting — issues we actually hit

| Symptom | Cause | Fix (applied) |
|---------|-------|---------------|
| `apt-get update` 404 on `ondrej/php … resolute` | ondrej PPA has no Ubuntu 26.04 build | Use **native PHP 8.5** (`PHP_VERSION=8.5`); stage 40 installs distro packages, no PPA |
| `Unable to locate package php8.5-opcache` | OPcache is built into the core SAPI on 8.5 (no separate pkg; `exif` ships in `-common`) | Removed those from the install list; OPcache verified present in FPM |
| `composer install` fails: `nette/schema`/`phpspreadsheet` require ≤ PHP 8.4 | `composer.lock` pinned 8.4-capped deps | Pinned `config.platform.php=8.5.4`, `composer update`d the chain (nette/schema 1.3.5, phpspreadsheet 1.30.0, Laravel 12.62); dropped `--ignore-platform-reqs` |
| `artisan package:discover` exits 1: `Cannot assign null to … SunnahApiClient::$apiKey` | `config/sunnah.php` returned `null` into a typed `string` | Default `env('SUNNAH_API_KEY', '')` |
| `config:cache`: *value at "translatable.locales" is non-serializable* | `config/translatable.php` used a **Closure** for `locales` | Static array; `TranslationServiceProvider` still sets active locales from DB at boot |
| Runner SSH `Connection timed out` / scp closed | `ufw limit` on 22 drops an IP after 6 conns/30s — a single deploy (keyscan+scp+ssh) trips it | `ufw allow OpenSSH` (fail2ban + key-only auth still protect) |
| Runner banned by fail2ban | `sshd` jail in **aggressive** mode bans on `ssh-keyscan`'s "Connection closed [preauth]" | Default jail mode + **removed the ssh-keyscan step** (scp/ssh use `StrictHostKeyChecking=no`) |
| `sudo systemctl reload` → *terminal required* | stage 15 baked the **wrong PHP version** into the NOPASSWD rule (ran when `PHP_VERSION` was still 8.4) | Re-run stage 15 after setting the correct version |
| `current -> current` self-symlink after a failed first deploy | rollback used `readlink -f` on a non-existent `current` (resolves to itself) | Capture direct `readlink`; only roll back to a valid, different release |
| deptrac CI always red | workflow used `--fail-on-uncovered` (~9k intentionally-unlayered tokens) + 1 real violation | Run `composer deptrac` on PHP 8.5; fixed `SitemapController` to use the repository (drop direct `App\Models\Article`) |
| Cloudflare `521` then OK | origin had no 443 cert; CF (Full mode) couldn't reach origin over HTTPS | Issue LE cert on origin (Phase 4) |

---

## 16. Architecture decisions

- **PHP 8.5 (not 8.4):** Ubuntu 26.04 "resolute" ships 8.5 natively and ondrej
  has no build for it. 8.5 satisfies Laravel 12's `^8.2` and gets distro
  security updates (no third-party PPA). `config.platform.php` pins the lock to
  the production runtime.
- **Queue on MySQL, cache/session on Redis:** the Redis instance is memory-capped
  with `allkeys-lru` eviction (correct for a cache) — it could silently drop
  queued jobs. The durable queue therefore lives in MySQL; Supervisor processes
  it either way. To move the queue to Redis, use a separate instance/DB with
  `maxmemory-policy noeviction`.
- **OPcache `validate_timestamps=0`:** max performance; every deploy reloads
  PHP-FPM, so OPcache stays coherent.
- **Zero-downtime releases + auto-rollback:** atomic `current` symlink flip; a
  failed deploy never leaves the site half-updated.
- **Harden SSH last:** provisioning runs as `ubuntu` (full sudo); locking SSH to
  `deployer`-only is the final step so the long install can't self-lock. Root is
  then OVH-KVM-console-only by design.
- **Cloudflare Full (strict):** the origin serves a real LE cert, so end-to-end
  TLS is validated edge→origin (not just edge→visitor).
```
