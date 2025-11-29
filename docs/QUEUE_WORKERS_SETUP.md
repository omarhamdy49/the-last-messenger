# Queue Workers Setup - Corrected Guide

## ❌ Common Mistake

```bash
# This DOESN'T work - --workers option doesn't exist
php artisan queue:work --queue=sync --workers=10 --timeout=3600
```

## ✅ Correct Approaches

---

## Option 1: Single Worker (Simplest - For Testing)

**Use this for initial testing:**

```bash
# Single worker with 1-hour timeout
php artisan queue:work --queue=sync --timeout=3600 --verbose

# Or with more tries
php artisan queue:work --queue=sync --timeout=3600 --tries=3 --verbose
```

**Pros:**
- Simple, no setup required
- Good for testing

**Cons:**
- Only 1 job at a time (slower)
- Expected sync time: 6-8 hours instead of 4-6

---

## Option 2: Multiple Terminal Windows (Quick Parallel)

**For parallel processing without Supervisor:**

Open 10 terminal windows/tabs and run in each:

```bash
# Terminal 1
php artisan queue:work --queue=sync --timeout=3600 --tries=3

# Terminal 2
php artisan queue:work --queue=sync --timeout=3600 --tries=3

# Terminal 3
php artisan queue:work --queue=sync --timeout=3600 --tries=3

# ... repeat for terminals 4-10
```

**Or use a script:**

```bash
# Create run-workers.sh
cat > run-workers.sh << 'EOF'
#!/bin/bash
for i in {1..10}
do
    php artisan queue:work --queue=sync --timeout=3600 --tries=3 > storage/logs/worker-$i.log 2>&1 &
    echo "Started worker $i (PID: $!)"
done
echo "All workers started. Check logs: storage/logs/worker-*.log"
echo "To stop all: pkill -f 'queue:work'"
EOF

chmod +x run-workers.sh
./run-workers.sh
```

**To stop all workers:**
```bash
pkill -f 'queue:work'
```

**Pros:**
- True parallel processing
- Full speed (4-6 hours)

**Cons:**
- Need to manage multiple processes
- No auto-restart on failure

---

## Option 3: Supervisor (Production Recommended)

**Install Supervisor:**

```bash
# macOS
brew install supervisor
brew services start supervisor

# Ubuntu/Debian
sudo apt-get install supervisor
sudo systemctl enable supervisor
sudo systemctl start supervisor
```

**Create config file:**

```bash
# macOS: /usr/local/etc/supervisor/conf.d/laravel-worker.conf
# Linux: /etc/supervisor/conf.d/laravel-worker.conf

sudo nano /usr/local/etc/supervisor/conf.d/laravel-worker.conf
```

**Paste this configuration:**

```ini
[program:laravel-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /Volumes/Data/work/projects/the-last-massenger/the-last-messenger-be/artisan queue:work --queue=sync --sleep=3 --tries=3 --timeout=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=YOUR_USERNAME
numprocs=10
redirect_stderr=true
stdout_logfile=/Volumes/Data/work/projects/the-last-massenger/the-last-messenger-be/storage/logs/worker.log
stopwaitsecs=3600
```

**Replace:**
- `YOUR_USERNAME` with your macOS username (run `whoami` to find it)
- Path to artisan if different

**Start workers:**

```bash
# Reload supervisor config
sudo supervisorctl reread
sudo supervisorctl update

# Start workers
sudo supervisorctl start laravel-worker:*

# Check status
sudo supervisorctl status

# View logs
tail -f storage/logs/worker.log
```

**Manage workers:**

```bash
# Stop all workers
sudo supervisorctl stop laravel-worker:*

# Restart all workers
sudo supervisorctl restart laravel-worker:*

# Stop supervisor
sudo supervisorctl shutdown
```

**Pros:**
- Auto-restart on failure
- Persistent across reboots
- Production-ready
- Process monitoring

**Cons:**
- Requires setup
- Need sudo access

---

## Option 4: Laravel Horizon (Best for Production)

**Install Horizon:**

```bash
composer require laravel/horizon
php artisan horizon:install
php artisan migrate
```

**Configure `config/horizon.php`:**

```php
'environments' => [
    'production' => [
        'supervisor-1' => [
            'connection' => 'redis',
            'queue' => ['sync'],
            'balance' => 'auto',
            'minProcesses' => 5,
            'maxProcesses' => 10,
            'balanceMaxShift' => 1,
            'tries' => 3,
            'timeout' => 3600,
        ],
    ],

    'local' => [
        'supervisor-1' => [
            'connection' => 'redis',
            'queue' => ['sync'],
            'balance' => 'simple',
            'processes' => 10,
            'tries' => 3,
            'timeout' => 3600,
        ],
    ],
],
```

**Start Horizon:**

```bash
php artisan horizon
```

**Access Dashboard:**

Visit: `http://localhost:8000/horizon`

**Pros:**
- Beautiful dashboard
- Real-time monitoring
- Auto-scaling workers
- Metrics and stats
- Failed job management

**Cons:**
- Requires Redis
- More complex setup

---

## 🎯 Recommended Approach for Your Test

### For Quick Testing (Right Now):

**Use the background script approach:**

```bash
# Create and run the worker script
cat > start-workers.sh << 'EOF'
#!/bin/bash
echo "Starting 10 queue workers..."
for i in {1..10}
do
    nohup php artisan queue:work --queue=sync --timeout=3600 --tries=3 > storage/logs/worker-$i.log 2>&1 &
    echo "Worker $i started (PID: $!)"
    sleep 0.5
done
echo ""
echo "✅ All 10 workers running!"
echo "📊 Monitor: tail -f storage/logs/worker-*.log"
echo "🛑 Stop all: pkill -f 'queue:work'"
EOF

chmod +x start-workers.sh
./start-workers.sh
```

**Then dispatch jobs:**

```bash
# Wait 15 minutes for rate limit
sleep 900

# Dispatch parallel sync
php artisan hadith:sync --parallel
```

**Monitor progress:**

```bash
# Watch all worker logs
tail -f storage/logs/worker-*.log

# Or watch combined output
tail -f storage/logs/worker-*.log | grep -i "sync"

# Check database progress
watch -n 10 'mysql -u root -p your_database -e "SELECT COUNT(*) FROM hadith;"'
```

**Stop workers when done:**

```bash
pkill -f 'queue:work'
```

---

## 🔍 Verify Workers Are Running

```bash
# Check running workers
ps aux | grep "queue:work"

# Count workers
ps aux | grep "queue:work" | grep -v grep | wc -l
# Should show: 10

# See worker PIDs
pgrep -f "queue:work"
```

---

## 📊 Complete Test Commands

```bash
# 1. Start workers (10 parallel)
./start-workers.sh

# 2. Wait for rate limit (15 min)
sleep 900

# 3. Dispatch sync jobs
php artisan hadith:sync --parallel

# 4. Monitor in another terminal
tail -f storage/logs/worker-*.log | grep "Sync"

# 5. Check progress every 10 seconds
watch -n 10 'mysql -u root -p your_db -e "SELECT COUNT(*) FROM hadith;"'

# 6. When complete, stop workers
pkill -f 'queue:work'
```

---

## 🐛 Troubleshooting

### Workers not processing jobs

```bash
# Check if workers are running
ps aux | grep "queue:work"

# Restart workers
pkill -f 'queue:work'
./start-workers.sh
```

### Jobs stuck in queue

```bash
# Clear failed jobs
php artisan queue:flush

# Restart queue
php artisan queue:restart

# Check failed jobs
php artisan queue:failed
```

### Worker timeout

```bash
# Increase timeout in worker command
php artisan queue:work --queue=sync --timeout=7200 --tries=3
```

---

## Summary: Choose Your Approach

| Approach | Setup Time | Parallel | Auto-Restart | Production-Ready |
|----------|------------|----------|--------------|------------------|
| **Single Worker** | 0 min | ❌ No | ❌ No | ❌ No |
| **Background Script** | 2 min | ✅ Yes | ❌ No | ⚠️ Testing only |
| **Supervisor** | 10 min | ✅ Yes | ✅ Yes | ✅ Yes |
| **Horizon** | 15 min | ✅ Yes | ✅ Yes | ✅ Yes |

**For testing right now:** Use the background script (Option 2)

**For production:** Set up Supervisor (Option 3) or Horizon (Option 4)

---

## Quick Start Script

Save this and run:

```bash
#!/bin/bash
# quick-sync.sh

echo "🚀 Starting Hadith Sync (Parallel Mode)"
echo ""

# Start workers
echo "Starting 10 workers..."
for i in {1..10}; do
    nohup php artisan queue:work --queue=sync --timeout=3600 --tries=3 > storage/logs/worker-$i.log 2>&1 &
done
sleep 2

# Check workers started
WORKER_COUNT=$(pgrep -f "queue:work" | wc -l)
echo "✅ $WORKER_COUNT workers running"
echo ""

# Wait for rate limit
echo "⏰ Waiting 15 minutes for API rate limit reset..."
sleep 900

# Dispatch sync
echo "📤 Dispatching sync jobs..."
php artisan hadith:sync --parallel

echo ""
echo "🎯 Sync started!"
echo "📊 Monitor: tail -f storage/logs/worker-*.log"
echo "🛑 Stop: pkill -f 'queue:work'"
```

Usage:
```bash
chmod +x quick-sync.sh
./quick-sync.sh
```

---

That's the corrected approach! Use the background script for testing, then set up Supervisor for production.
