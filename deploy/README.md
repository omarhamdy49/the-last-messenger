# The Last Messenger — VPS Provisioning & Deployment

Idempotent, modular scripts that take a fresh **Ubuntu 26.04** box to a hardened,
performance-tuned production host for the Laravel 12 app, plus a CI/CD pipeline
for zero-downtime deploys.

**Target host:** 2 vCPU · 4 GB RAM · 40 GB · IPv4 `51.195.100.16` · domain `rasoolallah.com`
**Stack installed:** PHP 8.4 (FPM + OPcache/JIT) · MySQL 8 · Redis · Nginx · Supervisor · Certbot · UFW · fail2ban

---

## What you get

| Area | Result |
|------|--------|
| **Security** | Key-only SSH, root login disabled, UFW default-deny, fail2ban, unattended security upgrades, least-privilege DB user, localhost-bound MySQL/Redis, secure headers + HSTS, hidden-file denial |
| **Performance** | 2 GB swap + VM tuning, OPcache + JIT, memory-tuned PHP-FPM pool, InnoDB tuned to RAM, Redis LRU cache, gzip, long-cache static assets |
| **Reliability** | Supervisor-managed queue workers + scheduler, zero-downtime symlinked releases with **auto-rollback**, health checks, log rotation, TLS auto-renewal |
| **Operations** | One idempotent command, per-stage re-runs, generated secrets stored root-only, CI/CD over SSH |

---

## Layout

```
deploy/
├── provision.sh              # orchestrator — run this
├── config.env.example        # copy to config.env and fill in
├── lib/common.sh             # shared helpers (logging, secrets, templating)
├── stages/                   # ordered, idempotent stages (00 … 90)
├── templates/                # config rendered with your values
└── deploy/deploy.sh          # server-side zero-downtime deploy (CI calls this)
.github/workflows/deploy.yml  # CI/CD: build assets → ship → deploy
```

### Stages

| Stage | Does |
|-------|------|
| `00-preflight` | Validate config, OS, resources |
| `05-system` | Update, timezone, locale, hostname, core packages |
| `10-swap` | 2 GB swap + VM/network sysctl + file-descriptor limits |
| `15-user` | Non-root `deployer` user + authorised SSH key + scoped sudo |
| `20-ssh-hardening` | Key-only SSH, no root, no passwords (**after** key is set) |
| `25-firewall` | UFW: 22 (rate-limited), 80, 443; deny rest |
| `30-fail2ban` | Ban brute-force SSH + nginx abuse |
| `35-unattended-upgrades` | Automatic security patches |
| `40-php` | PHP 8.4 + extensions + tuned FPM pool + OPcache/JIT |
| `45-composer` | Composer (checksum-verified) |
| `50-node` | Node.js 20 (asset-build fallback) |
| `52-media-tools` | ffmpeg/ffprobe + yt-dlp (media conversions + YouTube ingestion) |
| `55-mysql` | MySQL 8: secure, app DB + least-privilege user, InnoDB tuning |
| `60-redis` | Redis: localhost, password, 256 MB LRU, no persistence |
| `65-nginx` | Global tuning + rate-limit zones + HTTP server block |
| `70-ssl` | Let's Encrypt cert + HTTPS server block + auto-renew |
| `75-app` | Release/shared layout + production `.env` + deploy script |
| `80-supervisor` | Queue workers + `schedule:work` |
| `85-logrotate` | Rotate worker/scheduler/php-fpm logs |
| `90-healthcheck` | Verify every service + app endpoint |

---

## Prerequisites

1. **DNS first.** Point both records at the server *before* running stage 70:
   ```
   rasoolallah.com.      A   51.195.100.16
   www.rasoolallah.com.  A   51.195.100.16
   ```
2. **An SSH key pair** for CI. The **public** key goes into `config.env`
   (`DEPLOY_USER_PUBKEY`); the **private** key becomes the GitHub secret
   `SSH_PRIVATE_KEY`.

---

## Run it

```bash
# As root on the fresh server:
git clone <repo> /opt/tlm && cd /opt/tlm/deploy     # or scp the deploy/ folder up
cp config.env.example config.env
nano config.env                                     # set DEPLOY_USER_PUBKEY (required) + review tunables

sudo ./provision.sh                                 # full run, in order
```

Useful variants:
```bash
sudo ./provision.sh --list                # list stages
sudo ./provision.sh 40-php 65-nginx       # run specific stages
sudo FORCE=1 ./provision.sh 55-mysql      # re-run a completed stage
sudo ./provision.sh 90-healthcheck        # re-check health anytime
```

> ⚠️ After `20-ssh-hardening`, **open a new SSH session as `deployer` before
> closing your current one** — root/password login is now disabled.

Generated secrets (DB, Redis, root MySQL, APP_KEY) are written to
`/root/.tlm-credentials` (chmod 600) and reused on re-runs.

---

## CI/CD setup

Add these **GitHub Actions secrets** to the `the-last-messenger-be` repo:

| Secret | Value |
|--------|-------|
| `SSH_PRIVATE_KEY` | Private half of the key in `DEPLOY_USER_PUBKEY` |
| `SSH_USERNAME` | `deployer` |
| `SSH_SERVER_IP` | `51.195.100.16` |
| `DEPLOY_PATH` | *(optional)* defaults to `/var/www/rasoolallah` |

Push to `main` → the workflow builds Vite assets, validates Composer, zips the
app, ships it over SSH, and runs `deploy.sh`, which:

1. Unpacks into `releases/<timestamp>/`
2. Symlinks shared `.env` + `storage/`
3. `composer install --no-dev` → `migrate --force` → cache config/route/view/event
4. **Atomically** flips the `current` symlink
5. Reloads PHP-FPM (resets OPcache) + restarts Supervisor workers
6. Health-probes the app — **auto-rolls-back** to the previous release on failure
7. Prunes old releases (keeps 5)

---

## Design decisions (the "why")

- **Queue on MySQL, not Redis.** Redis here is a memory-capped cache with
  `allkeys-lru` eviction — perfect for cache/sessions, but it could silently
  drop queued jobs. The durable queue therefore stays in MySQL; Supervisor
  workers process it all the same. To move the queue to Redis later, run a
  *second* Redis instance with `maxmemory-policy noeviction` and point
  `QUEUE_CONNECTION=redis` at it.
- **Cache + sessions on Redis** (`phpredis`). Redis supports tags, which
  activates the tag-based invalidation in `LanguageCacheService` (see
  `.env.example`). `phpredis` (C ext) is faster than the dev default `predis`.
- **OPcache `validate_timestamps=0`.** Max performance; code changes require a
  PHP-FPM reload — which every deploy does, so OPcache is always coherent.
- **`innodb_flush_log_at_trx_commit=2`.** Big write-throughput win for a
  read-heavy content site, risking ≤1 s of commits on a hard crash. Set to `1`
  in `templates/mysqld-tuning.cnf` for full ACID durability.
- **Zero-downtime releases + auto-rollback** over in-place `unzip` so a bad
  deploy never leaves the site half-updated.

---

## Memory budget (≈4 GB)

| Component | Budget |
|-----------|--------|
| MySQL (InnoDB buffer pool 1 GB + overhead) | ~1.3 GB |
| PHP-FPM (16 workers × ~70 MB) | ~1.1 GB |
| Redis (cap) | 256 MB |
| Nginx + Supervisor + OS | ~0.5 GB |
| Headroom + swap | remainder + 2 GB swap |

Tune `PHP_FPM_MAX_CHILDREN`, `MYSQL_INNODB_BUFFER_POOL`, and `REDIS_MAXMEMORY`
in `config.env` if you resize the box.

---

## Troubleshooting

| Symptom | Check |
|---------|-------|
| Certbot fails | DNS A records for both apex + www must resolve to the IP; port 80 open |
| 502 Bad Gateway | No release deployed yet, or `php8.4-fpm` down (`systemctl status php8.4-fpm`) |
| Workers not running | `sudo supervisorctl status`; they start only after the first deploy |
| App errors | `tail -f /var/www/rasoolallah/shared/storage/logs/laravel-*.log` |
| Locked out of SSH | Use the provider's console; check `/etc/ssh/sshd_config.d/99-tlm-hardening.conf` |
| Recover secrets | `sudo cat /root/.tlm-credentials` |
