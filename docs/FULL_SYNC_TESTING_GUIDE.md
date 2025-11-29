# Full Database Sync - Testing Guide

## 🎯 Goal: Sync Entire Sunnah Hadith Database

This guide will walk you through syncing all ~45,000 hadiths from 16 collections.

---

## 📋 Prerequisites Checklist

Before starting, ensure:

- [x] Phase 1 optimizations implemented
- [x] Database migrations run (`checkpoint` column exists)
- [x] `.env` has valid `SUNNAH_API_KEY`
- [ ] Queue workers ready to start
- [ ] At least 8GB free disk space
- [ ] Stable internet connection
- [ ] 4-8 hours available for full sync

---

## 🚀 Option 1: Sequential Sync (Recommended for First Test)

**Pros:**
- Easier to monitor
- Respects rate limits automatically
- No queue setup needed

**Cons:**
- Slower (estimated 6-8 hours)

**Commands:**

```bash
# Step 1: Test with smallest collection first (15 min wait for rate limit)
sleep 900 && php artisan hadith:sync --collection=virtues
# Expected time: 2-5 minutes (93 hadiths)

# Step 2: If successful, try medium collection (15 min wait)
sleep 900 && php artisan hadith:sync --collection=riyadussalihin
# Expected time: 10-15 minutes (1,896 hadiths)

# Step 3: Full sync (15 min wait)
sleep 900 && php artisan hadith:sync
# Expected time: 6-8 hours (all 16 collections)
```

---

## ⚡ Option 2: Parallel Sync (Fastest - Recommended for Production)

**Pros:**
- 90% faster (4-6 hours instead of 6-8)
- Utilizes full CPU/network capacity
- Scales with more workers

**Cons:**
- Requires queue setup
- More complex monitoring

**Setup:**

### Terminal 1: Start Queue Workers

```bash
# Start 10 concurrent workers for maximum speed
php artisan queue:work --queue=sync --workers=10 --timeout=3600 --verbose
```

**Important:** Keep this terminal running during entire sync!

### Terminal 2: Dispatch Sync Jobs

```bash
# Wait 15 minutes for rate limit reset
sleep 900

# Dispatch all collections to queue (parallel sync)
php artisan hadith:sync --parallel

# Expected output:
# Dispatching 16 collection jobs in parallel...
#   ✓ Dispatched: bukhari
#   ✓ Dispatched: muslim
#   ✓ Dispatched: nasai
#   ... (16 total)
# ✅ All sync jobs dispatched to queue (Parent Sync Log ID: 1)
# Monitor progress with: php artisan queue:work --queue=sync
```

### Terminal 3: Monitor Progress (Optional)

```bash
# Watch logs in real-time
tail -f storage/logs/laravel.log | grep "Sync"

# Or check database sync logs
watch -n 5 'mysql -u root -p your_database -e "SELECT id, collection_name, status, synced_records, total_records FROM hadith_sync_logs ORDER BY id DESC LIMIT 10;"'
```

---

## 📊 Monitoring Progress

### Check Sync Status

```bash
# Get latest sync log ID from the dispatch output, then:
php artisan hadith:sync --resume=1

# Output shows:
# - Current status (in_progress, completed, failed)
# - Progress (synced_records/total_records)
# - Checkpoint data (if available)
```

### Check Database Records

```bash
# Total hadiths synced
mysql -u root -p your_database -e "SELECT COUNT(*) as total_hadiths FROM hadith;"

# Hadiths per collection
mysql -u root -p your_database -e "
SELECT
    hb.slug as collection,
    COUNT(h.id) as hadith_count
FROM hadith_books hb
LEFT JOIN hadith h ON h.book_id = hb.id
GROUP BY hb.slug
ORDER BY hadith_count DESC;
"
```

### Check Queue Status

```bash
# Pending jobs
php artisan queue:work --queue=sync --once

# Failed jobs
php artisan queue:failed

# Retry failed jobs
php artisan queue:retry all
```

---

## 🎯 Full Sync Testing Plan

### Phase 1: Validate Small Collection (5-10 minutes)

**Goal:** Ensure basic sync works

```bash
# 1. Wait for rate limit (15 minutes)
sleep 900

# 2. Sync smallest collection
php artisan hadith:sync --collection=virtues

# 3. Verify results
mysql -u root -p your_database -e "SELECT COUNT(*) FROM hadith WHERE book_id IN (SELECT id FROM hadith_books WHERE slug LIKE 'virtues%');"

# Expected: ~93 hadiths
```

**Success Criteria:**
- ✅ No errors in logs
- ✅ ~93 hadiths in database
- ✅ Sync log shows "success" status

---

### Phase 2: Validate Medium Collection (15-20 minutes)

**Goal:** Test with realistic dataset

```bash
# 1. Wait for rate limit (15 minutes)
sleep 900

# 2. Sync medium collection
php artisan hadith:sync --collection=riyadussalihin

# 3. Verify results
mysql -u root -p your_database -e "SELECT COUNT(*) FROM hadith WHERE book_id IN (SELECT id FROM hadith_books WHERE slug LIKE 'riyadussalihin%');"

# Expected: ~1,896 hadiths
```

**Success Criteria:**
- ✅ No errors in logs
- ✅ ~1,896 hadiths in database
- ✅ Bulk upsert operations working
- ✅ Transactions committed successfully

---

### Phase 3: Full Parallel Sync (4-6 hours)

**Goal:** Sync entire database

```bash
# Terminal 1: Start 10 queue workers
php artisan queue:work --queue=sync --workers=10 --timeout=3600 --verbose

# Terminal 2: Wait for rate limit, then dispatch
sleep 900 && php artisan hadith:sync --parallel

# Terminal 3: Monitor (optional)
tail -f storage/logs/laravel.log
```

**Monitor Every Hour:**
```bash
# Check total hadiths synced
mysql -u root -p your_database -e "SELECT COUNT(*) FROM hadith;"

# Check sync logs
mysql -u root -p your_database -e "SELECT collection_name, status, synced_records, failed_records FROM hadith_sync_logs WHERE sync_type = 'collection' ORDER BY id DESC LIMIT 16;"
```

**Success Criteria:**
- ✅ All 16 collections show "success" status
- ✅ ~45,000 hadiths in database
- ✅ No failed jobs in queue
- ✅ Completed in 4-6 hours

---

## 🔍 Expected Results

### Database Counts (Approximate)

| Collection | Expected Hadiths |
|------------|------------------|
| bukhari | ~7,277 |
| muslim | ~7,459 |
| nasai | ~5,768 |
| abudawud | ~5,276 |
| tirmidhi | ~4,053 |
| ibnmajah | ~4,345 |
| malik | ~1,973 |
| ahmad | ~1,359 |
| riyadussalihin | ~1,896 |
| adab | ~1,326 |
| shamail | ~402 |
| mishkat | ~5,319 |
| bulugh | ~1,767 |
| forty | ~122 |
| hisn | ~268 |
| virtues | ~93 |
| **TOTAL** | **~48,703** |

**Note:** Actual counts may vary slightly based on API data.

---

## 🐛 Troubleshooting

### Issue 1: 429 Too Many Requests

**Symptoms:**
```
Error fetching books page 1 for collection bukhari
429 Too Many Requests
```

**Solution:**
```bash
# Stop sync
# Wait 30 minutes
sleep 1800

# Resume sync
php artisan hadith:sync --resume=<SYNC_LOG_ID>
```

---

### Issue 2: Jobs Stuck in Queue

**Symptoms:**
- Queue worker shows jobs but nothing processes
- Jobs stay in "pending" status

**Solution:**
```bash
# Restart queue workers
# Terminal 1: Ctrl+C to stop
# Then restart:
php artisan queue:restart
php artisan queue:work --queue=sync --workers=10 --timeout=3600
```

---

### Issue 3: Database Connection Errors

**Symptoms:**
```
SQLSTATE[HY000] [2002] Connection refused
```

**Solution:**
```bash
# Check database is running
mysql -u root -p -e "SELECT 1;"

# Increase connection limits in config/database.php
'options' => [
    PDO::ATTR_TIMEOUT => 60,
    PDO::MYSQL_ATTR_INIT_COMMAND => 'SET SESSION wait_timeout=600',
],

# Restart sync
```

---

### Issue 4: Memory Errors

**Symptoms:**
```
PHP Fatal error: Allowed memory size exhausted
```

**Solution:**
```bash
# Increase PHP memory limit
# Edit php.ini or run:
php -d memory_limit=2G artisan hadith:sync --parallel

# Or reduce batch size in config/sunnah.php:
'sync' => [
    'batch_size' => 250, // Reduce from 500
],
```

---

### Issue 5: Checkpoint Not Resuming

**Symptoms:**
- Resume command shows checkpoint but starts from beginning

**Solution:**
- This is expected in Phase 1
- Checkpoint display works, but resume logic needs Phase 2 implementation
- For now, checkpoints are saved but not used for resuming
- Manual resume: Sync only remaining collections

---

## 📈 Performance Monitoring

### Real-Time Metrics

```bash
# Hadiths per second
watch -n 10 'mysql -u root -p your_database -e "SELECT COUNT(*) FROM hadith;" | tail -1'

# Sync log progress
watch -n 30 'php artisan hadith:sync --resume=1'

# Queue throughput
watch -n 5 'php artisan queue:work --queue=sync --once --verbose'
```

### Post-Sync Validation

```bash
# Verify data integrity
php artisan tinker --execute="
\$books = \App\Domain\Hadith\Models\HadithBook::with('hadiths')->get();
foreach (\$books as \$book) {
    echo \"\$book->slug: \" . \$book->hadiths()->count() . \" hadiths\n\";
}
"

# Check for missing translations
mysql -u root -p your_database -e "
SELECT COUNT(*) as missing_arabic
FROM hadith
WHERE arabic_text IS NULL OR arabic_text = '';
"

# Expected: 0 missing Arabic text
```

---

## 🎯 Success Criteria Checklist

### Before Full Sync
- [ ] Small collection test passed (virtues)
- [ ] Medium collection test passed (riyadussalihin)
- [ ] Queue workers running successfully
- [ ] Database has enough disk space
- [ ] No rate limit errors in last 30 minutes

### During Full Sync
- [ ] Queue workers processing jobs
- [ ] Hadith count increasing steadily
- [ ] No failed jobs accumulating
- [ ] Logs show successful book/chapter/hadith syncs

### After Full Sync
- [ ] All 16 collections synced
- [ ] ~45,000+ hadiths in database
- [ ] All sync logs show "success" status
- [ ] No failed jobs in queue
- [ ] Data integrity checks pass

---

## 🚀 Production Deployment

Once testing succeeds, set up for production:

### 1. Configure Supervisor (Queue Workers)

```bash
# /etc/supervisor/conf.d/laravel-worker.conf
[program:laravel-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /path/to/artisan queue:work --queue=sync --sleep=3 --tries=3 --timeout=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=10
redirect_stderr=true
stdout_logfile=/path/to/worker.log
stopwaitsecs=3600
```

```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start laravel-worker:*
```

### 2. Schedule Daily Incremental Sync

```php
// app/Console/Kernel.php
protected function schedule(Schedule $schedule)
{
    // Daily incremental sync at 2 AM
    $schedule->command('hadith:sync --parallel')
        ->dailyAt('02:00')
        ->withoutOverlapping()
        ->runInBackground();
}
```

### 3. Set Up Monitoring

```bash
# Install Laravel Horizon
composer require laravel/horizon
php artisan horizon:install
php artisan migrate

# Access dashboard at: /horizon
```

---

## 📝 Quick Reference Commands

```bash
# Test connection
php artisan hadith:sync --test-connection

# List collections
php artisan hadith:sync --list-collections

# Sync one collection
php artisan hadith:sync --collection=virtues

# Full sequential sync
php artisan hadith:sync

# Full parallel sync
php artisan hadith:sync --parallel

# Resume from failure
php artisan hadith:sync --resume=<ID>

# Start queue workers
php artisan queue:work --queue=sync --workers=10 --timeout=3600

# Monitor queue
php artisan queue:work --queue=sync --once --verbose

# Check database
mysql -u root -p your_database -e "SELECT COUNT(*) FROM hadith;"
```

---

## 🎬 Complete Testing Script

Here's a complete bash script to run the full test:

```bash
#!/bin/bash

echo "=== Sunnah API Full Sync Test ==="
echo ""

# Step 1: Test connection
echo "Step 1: Testing API connection..."
php artisan hadith:sync --test-connection
if [ $? -ne 0 ]; then
    echo "❌ API connection failed. Check your API key."
    exit 1
fi
echo "✅ API connection successful"
echo ""

# Step 2: Wait for rate limit
echo "Step 2: Waiting 15 minutes for rate limit reset..."
sleep 900
echo ""

# Step 3: Test small collection
echo "Step 3: Syncing small collection (virtues - 93 hadiths)..."
php artisan hadith:sync --collection=virtues
if [ $? -ne 0 ]; then
    echo "❌ Small collection sync failed"
    exit 1
fi
echo "✅ Small collection synced"
echo ""

# Step 4: Verify small collection
echo "Step 4: Verifying small collection..."
COUNT=$(mysql -u root -p your_database -se "SELECT COUNT(*) FROM hadith WHERE book_id IN (SELECT id FROM hadith_books WHERE slug LIKE 'virtues%');")
echo "Found $COUNT hadiths (expected ~93)"
if [ "$COUNT" -lt 80 ]; then
    echo "❌ Count too low"
    exit 1
fi
echo "✅ Verification passed"
echo ""

# Step 5: Wait for rate limit
echo "Step 5: Waiting 15 minutes for rate limit reset..."
sleep 900
echo ""

# Step 6: Full parallel sync
echo "Step 6: Starting full parallel sync..."
echo "Please start queue workers in another terminal:"
echo "  php artisan queue:work --queue=sync --workers=10 --timeout=3600"
echo ""
read -p "Press Enter when queue workers are running..."

php artisan hadith:sync --parallel
SYNC_ID=$(mysql -u root -p your_database -se "SELECT MAX(id) FROM hadith_sync_logs WHERE sync_type='full';")
echo "✅ Sync jobs dispatched (Sync Log ID: $SYNC_ID)"
echo ""

echo "=== Monitor progress with: ==="
echo "  php artisan hadith:sync --resume=$SYNC_ID"
echo "  tail -f storage/logs/laravel.log"
echo "  mysql -u root -p your_database -e 'SELECT COUNT(*) FROM hadith;'"
echo ""
echo "Expected completion: 4-6 hours"
```

Save as `test-full-sync.sh`, make executable, and run:

```bash
chmod +x test-full-sync.sh
./test-full-sync.sh
```

---

## 📞 Need Help?

- Check logs: `storage/logs/laravel.log`
- Review sync status: `php artisan hadith:sync --resume=<ID>`
- Database issues: Verify `hadith_sync_logs` table
- Queue issues: `php artisan queue:failed`

---

**Good luck with your full sync! 🚀**

Expected total time: **4-6 hours (parallel)** or **6-8 hours (sequential)**
