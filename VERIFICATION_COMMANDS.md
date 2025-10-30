# Verification Commands - Quick Reference

This document contains all commands needed to verify the admin panel implementation.

## Pre-Verification Setup

```bash
# Navigate to project directory
cd /Volumes/Data/work/projects/the-last-massenger/the-last-messenger-be
```

---

## 1. Code Formatting

Format all PHP files to PSR-12 standard:

```bash
./vendor/bin/pint
```

Check formatting without making changes:

```bash
./vendor/bin/pint --test
```

---

## 2. Autoload Regeneration

Rebuild Composer autoload files:

```bash
composer dump-autoload --optimize
```

Expected output: "Generated optimized autoload files containing 6616 classes"

---

## 3. Cache Clearing

Clear all Laravel caches:

```bash
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
```

Or run all at once:

```bash
php artisan cache:clear && php artisan config:clear && php artisan route:clear && php artisan view:clear
```

---

## 4. Route Verification

List all admin routes:

```bash
php artisan route:list --path=admin
```

Count admin routes:

```bash
php artisan route:list --path=admin | wc -l
```

Expected: 94+ routes

---

## 5. Syntax Validation

Check for PHP syntax errors in all files:

```bash
find app/Domain app/Http -name "*.php" -exec php -l {} \; 2>&1 | grep -i error
```

Expected: No output (no errors found)

Check specific directories:

```bash
# Admin controllers
find app/Http/Controllers/Admin -name "*.php" -exec php -l {} \; 2>&1 | grep -i error

# Shared domain
find app/Domain/Shared -name "*.php" -exec php -l {} \; 2>&1 | grep -i error

# Genealogy module
find app/Domain/Genealogy -name "*.php" -exec php -l {} \; 2>&1 | grep -i error
```

---

## 6. Database Operations

### Fresh Migration

Drop all tables and re-run migrations:

```bash
php artisan migrate:fresh
```

### Migration with Seeding

Drop all tables, re-run migrations, and seed data:

```bash
php artisan migrate:fresh --seed
```

### Check Migration Status

View which migrations have run:

```bash
php artisan migrate:status
```

### Rollback Last Migration

```bash
php artisan migrate:rollback
```

---

## 7. Create Test Admin User

Open Tinker:

```bash
php artisan tinker
```

Then run this PHP code:

```php
$user = \App\Models\User::create([
    'name' => 'Super Admin',
    'email' => 'admin@thelastmessenger.com',
    'password' => \Illuminate\Support\Facades\Hash::make('admin123'),
    'language' => 'ar',
    'email_verified_at' => now(),
]);

$user->assignRole('super_admin');

echo "Admin user created: {$user->email}\n";
echo "Password: admin123\n";

exit;
```

Verify user creation:

```bash
php artisan tinker
```

```php
\App\Models\User::where('email', 'admin@thelastmessenger.com')->first();
exit;
```

---

## 8. Test Admin Login (Web)

Start Laravel server:

```bash
php artisan serve
```

Open in browser:

```
http://localhost:8000/su/portal/v1/admin/login
```

Login credentials:
- Email: `admin@thelastmessenger.com`
- Password: `admin123`

---

## 9. Test Admin Login (API - cURL)

```bash
curl -X POST http://localhost:8000/su/portal/v1/admin/login \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "email": "admin@thelastmessenger.com",
    "password": "admin123"
  }'
```

Expected response:
```json
{
  "success": true,
  "message": "Login successful",
  "data": {
    "user": {...},
    "token": "..."
  }
}
```

---

## 10. Verify Database Tables

Check if admin_logs table exists:

```bash
php artisan tinker
```

```php
Schema::hasTable('admin_logs');
// Should return: true

DB::table('admin_logs')->count();
// Should return: 0 or more

exit;
```

Check genealogy tables:

```php
Schema::hasTable('people');
Schema::hasTable('relationships');
exit;
```

---

## 11. Test Admin Log Creation

```bash
php artisan tinker
```

```php
$user = \App\Models\User::first();

$logService = app(\App\Domain\Shared\Services\AdminLoggerService::class);

// Test login log
$logService->logLogin($user);

// Verify log created
\App\Domain\Shared\Models\AdminLog::count();
// Should return: 1

// View the log
\App\Domain\Shared\Models\AdminLog::latest()->first()->toArray();

exit;
```

---

## 12. Test Dashboard Service

```bash
php artisan tinker
```

```php
$dashboardService = app(\App\Domain\Shared\Services\DashboardService::class);

// Get statistics
$stats = $dashboardService->getStatistics();
print_r($stats);

// Get recent activity
$activity = $dashboardService->getRecentActivity(5);
print_r($activity->toArray());

exit;
```

---

## 13. Test Genealogy Repository

```bash
php artisan tinker
```

```php
$personRepo = app(\App\Domain\Genealogy\Repositories\PersonRepositoryInterface::class);

// Count people
$count = $personRepo->count();
echo "Total people: {$count}\n";

// Get paginated people
$people = $personRepo->getAllPaginated(10);
print_r($people->toArray());

exit;
```

---

## 14. Run Tests (If Tests Exist)

Run all tests:

```bash
php artisan test
```

Run with coverage:

```bash
php artisan test --coverage
```

Run specific test file:

```bash
php artisan test tests/Feature/Admin/ArticleControllerTest.php
```

Run specific test method:

```bash
php artisan test --filter test_admin_can_create_article
```

---

## 15. Check Logs for Errors

View Laravel log file:

```bash
tail -f storage/logs/laravel.log
```

View last 50 lines:

```bash
tail -n 50 storage/logs/laravel.log
```

Search for errors:

```bash
grep -i error storage/logs/laravel.log
```

---

## 16. Performance Testing

### Check Route Response Time

```bash
# Install ApacheBench (if not installed)
# macOS: brew install httpd

# Test dashboard endpoint (requires authentication)
ab -n 100 -c 10 http://localhost:8000/su/portal/v1/admin/dashboard
```

### Check Database Query Count

Enable query logging in Tinker:

```bash
php artisan tinker
```

```php
DB::enableQueryLog();

$dashboardService = app(\App\Domain\Shared\Services\DashboardService::class);
$stats = $dashboardService->getStatistics();

$queries = DB::getQueryLog();
echo "Total queries: " . count($queries) . "\n";
print_r($queries);

exit;
```

---

## 17. Check Repository Bindings

Verify all repositories are bound:

```bash
php artisan tinker
```

```php
// Test AdminLog repository binding
$adminLogRepo = app(\App\Domain\Shared\Repositories\AdminLogRepositoryInterface::class);
echo get_class($adminLogRepo) . "\n";
// Expected: App\Domain\Shared\Repositories\AdminLogRepository

// Test Person repository binding
$personRepo = app(\App\Domain\Genealogy\Repositories\PersonRepositoryInterface::class);
echo get_class($personRepo) . "\n";
// Expected: App\Domain\Genealogy\Repositories\PersonRepository

exit;
```

---

## 18. Inspect Middleware

List all middleware:

```bash
php artisan route:list --columns=uri,name,middleware | grep admin
```

Verify AdminAuthMiddleware is registered:

```bash
grep -r "AdminAuthMiddleware" app/Http/Kernel.php bootstrap/app.php
```

---

## 19. Check Language Files

Verify Arabic translations exist:

```bash
cat resources/lang/ar/admin.php | head -20
```

Verify English translations exist:

```bash
cat resources/lang/en/admin.php | head -20
```

---

## 20. Production Optimization (Before Deployment)

Cache configuration:

```bash
php artisan config:cache
```

Cache routes:

```bash
php artisan route:cache
```

Cache views:

```bash
php artisan view:cache
```

Optimize autoloader:

```bash
composer dump-autoload --optimize --classmap-authoritative
```

Run all optimization commands:

```bash
php artisan optimize
```

Clear optimizations (for development):

```bash
php artisan optimize:clear
```

---

## Quick Troubleshooting

### "Class not found" Error

```bash
composer dump-autoload
php artisan config:clear
```

### "Route not found" Error

```bash
php artisan route:clear
php artisan route:list --path=admin
```

### "View not found" Error

```bash
php artisan view:clear
ls -la resources/views/admin/
```

### Database Connection Error

Check `.env` file:

```bash
cat .env | grep DB_
```

Test database connection:

```bash
php artisan tinker
```

```php
DB::connection()->getPdo();
// Should not throw an exception
exit;
```

### Permission Denied Errors

Fix storage permissions:

```bash
chmod -R 775 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache
```

---

## Useful Development Commands

### Watch Logs in Real-Time

```bash
php artisan pail
```

Or use tail:

```bash
tail -f storage/logs/laravel.log
```

### Run Development Server

```bash
php artisan serve
```

Custom host and port:

```bash
php artisan serve --host=0.0.0.0 --port=8080
```

### Run Queue Worker

```bash
php artisan queue:work
```

### List All Artisan Commands

```bash
php artisan list
```

### Get Help for Specific Command

```bash
php artisan help migrate
```

---

## Security Checklist Before Deployment

- [ ] Change default admin password
- [ ] Set `APP_ENV=production` in `.env`
- [ ] Set `APP_DEBUG=false` in `.env`
- [ ] Generate new `APP_KEY` if not already done
- [ ] Review `.env` for exposed secrets
- [ ] Enable HTTPS/SSL
- [ ] Configure rate limiting
- [ ] Review CORS settings
- [ ] Enable query logging monitoring
- [ ] Set up log rotation
- [ ] Configure backup strategy
- [ ] Test all authentication flows
- [ ] Verify admin permissions are working
- [ ] Test CSRF protection
- [ ] Review file upload restrictions

---

## Emergency Rollback

If something breaks after deployment:

```bash
# 1. Rollback last migration
php artisan migrate:rollback --step=1

# 2. Clear all caches
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear

# 3. Restore previous code version (via Git)
git log --oneline -5
git reset --hard <previous-commit-hash>

# 4. Re-run setup
composer install --no-dev --optimize-autoloader
php artisan migrate
php artisan config:cache
php artisan route:cache
```

---

## Support Resources

- Laravel Documentation: https://laravel.com/docs
- Laravel Sanctum: https://laravel.com/docs/sanctum
- Spatie Permission: https://spatie.be/docs/laravel-permission
- Project CLAUDE.md: `/Volumes/Data/work/projects/the-last-massenger/the-last-messenger-be/CLAUDE.md`
- Integration Summary: `/Volumes/Data/work/projects/the-last-massenger/the-last-messenger-be/INTEGRATION_SUMMARY.md`

---

**Last Updated:** October 29, 2025
