# Quick Verification Commands

**Purpose:** Fast commands to verify admin panel functionality
**Use When:** After fixes, before deployment, during code review

---

## 1. Pre-Flight Checks

### Clear All Caches
```bash
php artisan cache:clear && \
php artisan config:clear && \
php artisan route:clear && \
php artisan view:clear && \
echo "✅ All caches cleared"
```

### Verify Environment
```bash
php artisan about | head -20
```

### Check Database Connection
```bash
php artisan db:show
```

### List All Routes
```bash
php artisan route:list --path=admin | grep -E "GET|POST|PUT|DELETE" | wc -l
echo "Total admin routes registered"
```

---

## 2. Code Quality Checks

### Run Code Style Fixer
```bash
./vendor/bin/pint --test
echo "✅ Code style check complete"
```

### Auto-Fix Code Style Issues
```bash
./vendor/bin/pint
echo "✅ Code style fixed (if any issues found)"
```

### Check for Syntax Errors
```bash
find app -name "*.php" -exec php -l {} \; | grep -v "No syntax errors"
```

### Verify Autoloader
```bash
composer dump-autoload -o
echo "✅ Autoloader optimized"
```

---

## 3. Database Verification

### Check Migration Status
```bash
php artisan migrate:status
```

### Count Tables
```bash
php artisan tinker --execute="echo DB::select('SELECT COUNT(*) as count FROM information_schema.tables WHERE table_schema = DATABASE()')[0]->count;"
echo "tables in database"
```

### Verify Admin User Exists
```bash
php artisan tinker --execute="echo \App\Models\User::whereHas('roles', function(\$q) { \$q->whereIn('name', ['admin', 'super_admin']); })->count();"
echo "admin users found"
```

### Test Admin Logs Table
```bash
php artisan tinker --execute="echo \App\Domain\Shared\Models\AdminLog::count();"
echo "activity log entries"
```

---

## 4. Route Verification

### List All Admin Routes
```bash
php artisan route:list --path=admin --columns=method,uri,name
```

### Count Admin Routes
```bash
php artisan route:list --path=admin | grep -c "admin\."
echo "named admin routes"
```

### Verify Critical Routes Exist
```bash
php artisan route:list --name=admin.dashboard.index
php artisan route:list --name=admin.articles.index
php artisan route:list --name=admin.hadith.index
php artisan route:list --name=login
echo "✅ Critical routes verified"
```

### Check Middleware Registration
```bash
php artisan route:list --path=admin | grep -o "auth" | head -1
echo "middleware is registered"
```

---

## 5. Dependency Verification

### Check Spatie Packages
```bash
composer show | grep spatie
```

### Verify Laravel Version
```bash
php artisan --version
```

### Check PHP Version
```bash
php -v | head -1
```

### Verify Required Extensions
```bash
php -m | grep -E "pdo|mysql|json|mbstring|tokenizer|xml|ctype|fileinfo|gd"
```

---

## 6. File Structure Verification

### Count Admin Controllers
```bash
find app/Http/Controllers/Admin -name "*.php" | wc -l
echo "admin controllers"
```

### Count Blade Templates
```bash
find resources/views/admin -name "*.blade.php" | wc -l
echo "blade templates"
```

### Count Domain Models
```bash
find app/Domain -name "*.php" -path "*/Models/*" | wc -l
echo "domain models"
```

### Count Repositories
```bash
find app/Domain -name "*Repository.php" | wc -l
echo "repositories"
```

### Count Services
```bash
find app/Domain -name "*Service.php" | wc -l
echo "services"
```

---

## 7. Test Critical Functionality

### Test Dashboard Service
```bash
php artisan tinker --execute="
\$service = app(\App\Domain\Shared\Services\DashboardService::class);
\$stats = \$service->getStatistics();
echo 'Articles: ' . (\$stats['articles']['total'] ?? 0) . PHP_EOL;
echo 'Hadith: ' . (\$stats['hadith']['total'] ?? 0) . PHP_EOL;
echo 'Users: ' . (\$stats['users']['total'] ?? 0) . PHP_EOL;
"
```

### Test Admin Logger Service
```bash
php artisan tinker --execute="
\$logger = app(\App\Domain\Shared\Services\AdminLoggerService::class);
\$user = \App\Models\User::first();
\$article = \App\Domain\Blog\Models\Article::first();
if (\$user && \$article) {
    \$logger->logCustomAction('test', \$article, \$user, null, ['test' => 'data']);
    echo '✅ Admin logging works';
} else {
    echo '⚠️ No user or article found for testing';
}
"
```

### Test Repository Binding
```bash
php artisan tinker --execute="
try {
    \$repo = app(\App\Domain\Blog\Repositories\ArticleRepositoryInterface::class);
    echo '✅ Repository binding works: ' . get_class(\$repo);
} catch (\Exception \$e) {
    echo '❌ Repository binding failed: ' . \$e->getMessage();
}
"
```

---

## 8. View Compilation Verification

### Compile All Blade Views
```bash
php artisan view:cache
echo "✅ All views compiled"
```

### Check for Blade Errors
```bash
php artisan view:clear && php artisan view:cache 2>&1 | grep -i error
```

### List Compiled Views
```bash
ls -lh storage/framework/views/ | wc -l
echo "compiled view files"
```

---

## 9. Security Checks

### Check APP_KEY is Set
```bash
php artisan tinker --execute="echo config('app.key') ? '✅ APP_KEY is set' : '❌ APP_KEY is missing';"
```

### Verify Debug Mode
```bash
php artisan tinker --execute="echo config('app.debug') ? '⚠️ Debug mode ON (disable in production)' : '✅ Debug mode OFF';"
```

### Check CSRF Protection
```bash
grep -r "@csrf" resources/views/admin | wc -l
echo "CSRF tokens found in views"
```

### Verify Storage Link
```bash
ls -la public/storage > /dev/null 2>&1 && echo "✅ Storage link exists" || echo "❌ Storage link missing (run: php artisan storage:link)"
```

---

## 10. Performance Checks

### Check Cached Config
```bash
test -f bootstrap/cache/config.php && echo "✅ Config cached" || echo "⚠️ Config not cached"
```

### Check Cached Routes
```bash
test -f bootstrap/cache/routes-v7.php && echo "✅ Routes cached" || echo "⚠️ Routes not cached"
```

### Database Query Count Test
```bash
php artisan tinker --execute="
DB::enableQueryLog();
\$service = app(\App\Domain\Shared\Services\DashboardService::class);
\$stats = \$service->getStatistics();
echo count(DB::getQueryLog()) . ' queries executed';
"
```

---

## 11. One-Line Status Check

### Complete Health Check
```bash
php artisan about && \
php artisan route:list --path=admin | head -5 && \
php artisan db:show && \
composer show | grep -E "laravel/framework|spatie" && \
echo "✅ All systems operational"
```

---

## 12. Testing Commands

### Run All Tests (if they exist)
```bash
php artisan test --parallel
```

### Run Specific Test Suite
```bash
php artisan test --testsuite=Feature
```

### Run Tests with Coverage
```bash
php artisan test --coverage --min=80
```

---

## 13. Server Operations

### Start Development Server
```bash
php artisan serve
```

### Start with Custom Port
```bash
php artisan serve --port=8080
```

### Run Queue Worker
```bash
php artisan queue:work --tries=3
```

### Monitor Logs in Real-Time
```bash
php artisan pail
```

---

## 14. Quick Database Reset

### Fresh Migration with Seeding
```bash
php artisan migrate:fresh --seed
```

### Interactive Database Reset
```bash
./reset-database.sh
```

---

## 15. Deployment Preparation

### Optimize for Production
```bash
composer install --no-dev --optimize-autoloader && \
php artisan config:cache && \
php artisan route:cache && \
php artisan view:cache && \
echo "✅ Optimized for production"
```

### Clear All Optimizations (Development)
```bash
php artisan config:clear && \
php artisan route:clear && \
php artisan view:clear && \
php artisan cache:clear && \
echo "✅ Cleared all optimizations"
```

---

## 16. Troubleshooting Commands

### Check File Permissions
```bash
ls -la storage bootstrap/cache public/storage
```

### Fix File Permissions
```bash
chmod -R 755 storage bootstrap/cache && \
chown -R www-data:www-data storage bootstrap/cache && \
echo "✅ Permissions fixed"
```

### Clear Everything
```bash
php artisan optimize:clear && \
composer dump-autoload && \
echo "✅ Complete cache clear"
```

### Check for Errors in Log
```bash
tail -50 storage/logs/laravel.log
```

---

## 17. Git Status Check

### Check Uncommitted Changes
```bash
git status --short
```

### Show Modified Files
```bash
git diff --name-only
```

### Show Untracked Files
```bash
git ls-files --others --exclude-standard
```

---

## 18. Quick Smoke Test Script

Create this file: `smoke-test.sh`

```bash
#!/bin/bash

echo "🔍 Running smoke tests..."
echo ""

# Test 1: PHP Version
echo "1. PHP Version:"
php -v | head -1

# Test 2: Laravel Version
echo ""
echo "2. Laravel Version:"
php artisan --version

# Test 3: Database Connection
echo ""
echo "3. Database Connection:"
php artisan db:show | grep -E "Connection|Database"

# Test 4: Routes Count
echo ""
echo "4. Admin Routes:"
ROUTES=$(php artisan route:list --path=admin | wc -l)
echo "   Total: $ROUTES routes"

# Test 5: Code Style
echo ""
echo "5. Code Style:"
./vendor/bin/pint --test 2>&1 | grep -E "PASS|FAIL"

# Test 6: Admin User
echo ""
echo "6. Admin Users:"
php artisan tinker --execute="echo \App\Models\User::whereHas('roles', function(\$q) { \$q->whereIn('name', ['admin', 'super_admin']); })->count();"
echo "   admin users found"

# Test 7: Storage Link
echo ""
echo "7. Storage Link:"
ls -la public/storage > /dev/null 2>&1 && echo "   ✅ Exists" || echo "   ❌ Missing"

# Test 8: Blade Views
echo ""
echo "8. Blade Views:"
VIEW_COUNT=$(find resources/views/admin -name "*.blade.php" | wc -l)
echo "   $VIEW_COUNT templates found"

echo ""
echo "✅ Smoke test complete"
```

Make executable:
```bash
chmod +x smoke-test.sh
./smoke-test.sh
```

---

## 19. Environment Verification

### Verify .env File Exists
```bash
test -f .env && echo "✅ .env file exists" || echo "❌ .env file missing"
```

### Check Required ENV Variables
```bash
php artisan tinker --execute="
\$required = ['APP_KEY', 'DB_CONNECTION', 'DB_DATABASE'];
foreach (\$required as \$var) {
    echo \$var . ': ' . (env(\$var) ? '✅' : '❌') . PHP_EOL;
}
"
```

---

## 20. Final Checklist Command

```bash
echo "🔍 Admin Panel Verification Checklist"
echo "======================================"
echo ""
echo "1. PHP Version: $(php -v | head -1 | awk '{print $2}')"
echo "2. Laravel Version: $(php artisan --version | awk '{print $3}')"
echo "3. Database: $(php artisan db:show | grep Connection | awk '{print $2}')"
echo "4. Admin Routes: $(php artisan route:list --path=admin | wc -l) registered"
echo "5. Blade Views: $(find resources/views/admin -name '*.blade.php' | wc -l) templates"
echo "6. Controllers: $(find app/Http/Controllers/Admin -name '*.php' | wc -l) files"
echo "7. Domain Models: $(find app/Domain -name '*.php' -path '*/Models/*' | wc -l) models"
echo "8. Storage Link: $(ls -la public/storage > /dev/null 2>&1 && echo '✅' || echo '❌')"
echo "9. Config Cached: $(test -f bootstrap/cache/config.php && echo '✅' || echo '❌')"
echo "10. APP_KEY Set: $(php artisan tinker --execute="echo config('app.key') ? '✅' : '❌';")"
echo ""
echo "======================================"
echo "Run: php artisan serve"
echo "Visit: http://localhost:8000/su/portal/v1/admin/login"
```

---

## Usage Examples

### Before Starting Work
```bash
php artisan optimize:clear
composer dump-autoload
echo "✅ Ready to code"
```

### After Making Changes
```bash
./vendor/bin/pint
php artisan test
git status
```

### Before Committing
```bash
./vendor/bin/pint
php artisan route:list --path=admin > /dev/null
php artisan view:cache > /dev/null
echo "✅ Ready to commit"
```

### Before Deployment
```bash
composer install --no-dev --optimize-autoloader
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan migrate --force
echo "✅ Ready to deploy"
```

---

**Quick Reference Card**

```
✅ Pass
❌ Fail
⚠️ Warning

Essential Commands:
- php artisan about
- php artisan route:list --path=admin
- php artisan db:show
- php artisan serve
- ./vendor/bin/pint

Troubleshooting:
- php artisan optimize:clear
- composer dump-autoload
- php artisan storage:link
- tail -f storage/logs/laravel.log
```

---

**Document Version:** 1.0
**Last Updated:** October 29, 2025
