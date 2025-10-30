#!/bin/bash

echo "=================================="
echo "Performance Optimization Test"
echo "=================================="
echo ""

echo "1. Testing LanguageCacheService..."
php artisan tinker --execute="
\$service = app(\App\Domain\Shared\Services\LanguageCacheService::class);
echo '✓ Default Language: ' . \$service->getDefaultLanguageCode() . PHP_EOL;
echo '✓ Active Languages: ' . implode(', ', \$service->getActiveLanguageCodes()) . PHP_EOL;
echo '✓ RTL Detection: ar=' . (\$service->isRtl('ar') ? 'RTL' : 'LTR') . ', en=' . (\$service->isRtl('en') ? 'RTL' : 'LTR') . PHP_EOL;
"

echo ""
echo "2. Checking Database Indexes..."
mysql -u root -p$DB_PASSWORD the_last_massenger -e "
SELECT TABLE_NAME, INDEX_NAME, COLUMN_NAME 
FROM INFORMATION_SCHEMA.STATISTICS 
WHERE TABLE_SCHEMA = 'the_last_massenger' 
AND INDEX_NAME LIKE 'idx_%' 
ORDER BY TABLE_NAME, INDEX_NAME;
" 2>/dev/null || echo "Note: Run with proper MySQL credentials to see indexes"

echo ""
echo "3. Cache Warmed: $(php artisan tinker --execute="\$s = app(\App\Domain\Shared\Services\LanguageCacheService::class); \$s->warmCache(); echo 'YES';")"

echo ""
echo "=================================="
echo "Optimization Test Complete!"
echo "=================================="
