# Load testing rasoolallah.com

```bash
brew install k6
mkdir -p tests/load/results
```

## Before you run this

Three things will silently invalidate your results. Read these first.

**1. Cloudflare is in front of the site.** A test against `https://rasoolallah.com`
measures the Cloudflare edge, not the server. The homepage HTML is edge-cached
(~30 min), so a "500 VU" load test can show a p95 of 40 ms and prove nothing about
origin capacity. Cloudflare may also challenge or block k6 as a bot mid-run.

To measure the actual application, bypass the edge:

```bash
k6 run tests/load/k6.js --env SCENARIO=load --env TARGET=origin
```

That maps `rasoolallah.com` → `51.195.100.16` in k6's resolver. TLS SNI and the
`Host` header stay correct, so the certificate and the nginx vhost still match —
only DNS is overridden. Run **both** modes: `cdn` tells you what users experience,
`origin` tells you what the server can actually take.

**2. nginx rate-limits per IP.** From `conf.d/tlm-global.conf` and
`snippets/tlm-app.conf`:

| Zone | Limit | Applies to |
|---|---|---|
| `perip` | 20 concurrent connections | `/` and `/api/` |
| `api` | 30 r/s, burst 60 | `/api/` |
| `login` | 5 r/min, burst 5 | auth endpoints |

A load test from one machine is one IP. Past ~20 concurrent connections you are
measuring `limit_conn`, not the app — you'll see a wall of `503`s that look like
capacity failure but aren't. The script counts these separately as
`throttled_429_503` so they don't pollute `http_req_failed`, but the latency
numbers are still meaningless once you're being shaped.

Options, in order of preference:

- Run distributed (k6 Cloud, or several VMs / cloud functions in different regions).
  This is the only way to honestly simulate "huge concurrent traffic".
- Temporarily allowlist the test source IP in nginx during a maintenance window:
  ```nginx
  geo $limit_exempt { default 0; YOUR.TEST.IP.HERE 1; }
  map $limit_exempt $limit_key { 0 $binary_remote_addr; 1 ""; }
  # then use $limit_key instead of $binary_remote_addr in the limit_*_zone directives
  ```
- Accept the ceiling and read `throttled_429_503` as "we hit the shaper here".

Laravel adds its own `throttle:search` and `throttle:ai_inquiry` on top. Search
endpoints are excluded from the default pool for that reason — add them with
`--env SEARCH=1` only when you specifically want to test the throttle.

**3. Never run `stress` or `spike` against production outside a maintenance
window.** The box has 3.7 GB of RAM and `vm.overcommit_memory=1`, which means the
kernel will not refuse an allocation — it will OOM-kill a process instead. The
most likely victim is `mysqld`. Announce the window, take a snapshot first, and
have someone watching.

## Scenarios

| Scenario | Executor | Shape | Use it to answer |
|---|---|---|---|
| `smoke` | 2 VUs, 60 iterations | ~3 min | "Does every endpoint still work?" Run before every other scenario. |
| `load` | ramping-vus → 60% of PEAK | ~16 min | "What is normal-traffic latency?" This is the number you quote. |
| `stress` | ramping-vus → 200% of PEAK | ~13 min | "Where is the knee, and what breaks first?" |
| `spike` | ramping-arrival-rate, 20 → 4×PEAK in 15 s | ~6 min | "TV mention / viral share. Does it recover?" |
| `soak` | constant-vus at 30% of PEAK | 2 h default | "Do we leak?" The only scenario that catches slow RSS growth. |

```bash
k6 run tests/load/k6.js --env SCENARIO=smoke
k6 run tests/load/k6.js --env SCENARIO=load   --env TARGET=origin --env PEAK=300
k6 run tests/load/k6.js --env SCENARIO=stress --env TARGET=origin --env PEAK=300
k6 run tests/load/k6.js --env SCENARIO=spike  --env TARGET=cdn
k6 run tests/load/k6.js --env SCENARIO=soak   --env SOAK_DURATION=4h
```

### Knobs

| Env | Default | Meaning |
|---|---|---|
| `SCENARIO` | `smoke` | `smoke\|load\|stress\|spike\|soak\|all` |
| `TARGET` | `cdn` | `cdn` (through Cloudflare) or `origin` (direct to nginx) |
| `PEAK` | `300` | Peak VU count. Every scenario scales off this one number. |
| `HOST` | `rasoolallah.com` | Override for staging |
| `ORIGIN_IP` | `51.195.100.16` | Used only when `TARGET=origin` |
| `SEARCH` | unset | `1` includes the throttled search endpoints |
| `SOAK_DURATION` | `2h` | Soak length |

## Watch the server while it runs

k6 alone tells you the site got slow. It does not tell you *why*. Run the
companion monitor in a second terminal — it samples RAM, php-fpm queue depth,
MySQL connections and slow queries every 5 s and writes a CSV you can line up
against the k6 timeline:

```bash
tests/load/monitor.sh 900 > tests/load/results/monitor-$(date +%s).csv
```

The three columns that matter on this box:

- `mem_avail_mb` — if this trends toward zero, you are heading for an OOM kill.
- `fpm_listen_queue` — anything above 0 sustained means `pm.max_children` (16)
  is the bottleneck, not the code.
- `mysql_threads_running` — if this climbs while `fpm_active` is flat, the
  bottleneck is a query, not PHP.

## Reading the results

`http_req_failed` excludes 429/503 by design, so it isolates real failures.
Check these in order:

1. `server_errors_5xx` > 0 → a bug, not capacity. Fix before tuning anything.
2. `throttled_429_503` high → you're measuring the rate limiter. See caveat 2.
3. `cloudflare_cache_hit` on a `cdn` run → tells you how much of the load never
   reached the origin at all. A high hit rate means the origin numbers from that
   run are worthless; rerun with `TARGET=origin`.
4. `lat_quran` p95 vs `lat_home` p95 → the Quran reader is the heaviest read path.
   If it diverges sharply from `home` under load, that's where to optimise.

Raw results land in `tests/load/results/<scenario>-<target>.json`.
