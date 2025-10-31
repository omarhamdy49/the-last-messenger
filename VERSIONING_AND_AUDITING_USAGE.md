# Version Control & Auditing System - Usage Guide

## Quick Start

### Enabling Versioning on a Model

Add the `HasVersions` trait to any model to automatically track all changes:

```php
<?php

namespace App\Domain\Blog\Models;

use App\Domain\Shared\Traits\HasVersions;
use Illuminate\Database\Eloquent\Model;

class Article extends Model
{
    use HasVersions;

    // Your model code...
}
```

**That's it!** Every create, update, and delete will now be automatically versioned.

### Enabling Audit Logging on a Model

Add the `HasAuditLog` trait to any model to automatically log all changes:

```php
<?php

namespace App\Domain\Blog\Models;

use App\Domain\Shared\Traits\HasAuditLog;
use Illuminate\Database\Eloquent\Model;

class Article extends Model
{
    use HasAuditLog;

    // Your model code...
}
```

**That's it!** Every create, update, and delete will now be automatically audited.

### Using Both Traits Together

You can use both traits on the same model for complete tracking:

```php
<?php

namespace App\Domain\Blog\Models;

use App\Domain\Shared\Traits\HasVersions;
use App\Domain\Shared\Traits\HasAuditLog;
use Illuminate\Database\Eloquent\Model;

class Article extends Model
{
    use HasVersions, HasAuditLog;

    // Your model code...
}
```

## Manual Version Control

### Creating a Version Manually

```php
$article = Article::find(1);

// Create a version with custom summary
$version = $article->createVersion(
    changeType: 'published',
    summary: 'Published article for the first time',
    metadata: ['published_by' => 'admin', 'channel' => 'web']
);
```

### Viewing Version History

```php
// Get all versions for an article
$versions = $article->versions()->get();

// Get latest version
$latestVersion = $article->latestVersion();

// Get specific version by number
$version3 = $article->getVersion(3);

// Get version count
$count = $article->getVersionCount();

// Get current version number
$currentVersion = $article->getCurrentVersionNumber();
```

### Comparing Versions

```php
// Compare version 1 and version 3
$diff = $article->compareVersions(1, 3);

// Result:
// [
//     'title' => [
//         'old' => 'Old Title',
//         'new' => 'New Title',
//         'type' => 'modified'
//     ],
//     'content' => [
//         'old' => 'Old content...',
//         'new' => 'New content...',
//         'type' => 'modified'
//     ]
// ]
```

### Reverting to Previous Version

```php
// Revert article to version 2
$success = $article->revertToVersion(2);

if ($success) {
    // Article has been reverted to version 2
    // A new version is created marking the revert
}
```

## Manual Audit Logging

### Creating Audit Logs Manually

```php
$article = Article::find(1);

// Log a custom action
$log = $article->logAudit(
    action: 'exported',
    description: 'Article exported to PDF',
    oldValues: null,
    newValues: ['format' => 'pdf', 'size' => '2.5MB'],
    metadata: ['user_agent' => 'Chrome', 'export_time' => 1.5]
);
```

### Viewing Audit Trail

```php
// Get all audit logs for an article
$logs = $article->auditLogs()->get();

// Get recent audits (last 10)
$recentLogs = $article->getRecentAudits(10);

// Get audits by action
$publishLogs = $article->getAuditsByAction('published');

// Get audits in date range
$logs = $article->getAuditsInRange(
    startDate: now()->subDays(30),
    endDate: now()
);

// Get audit summary
$summary = $article->getAuditSummary();
// Returns: total_audits, created_at, last_updated_at, last_updated_by, total_updates
```

## Using Services

### Version Service

```php
use App\Domain\Shared\Services\VersionService;

public function __construct(protected VersionService $versionService) {}

public function manageVersions()
{
    $article = Article::find(1);

    // Create version
    $version = $this->versionService->createVersion(
        $article,
        $article->getAttributes(),
        auth()->id(),
        'Manual version creation'
    );

    // Get versions
    $versions = $this->versionService->getVersions($article, perPage: 15);

    // Get version timeline
    $timeline = $this->versionService->getVersionTimeline($article);

    // Compare versions
    $diff = $this->versionService->compareVersions($v1Id, $v2Id);

    // Revert to version
    $success = $this->versionService->revertToVersion($article, $versionId, auth()->id());

    // Get statistics
    $stats = $this->versionService->getVersionStats();

    // Prune old versions (keep last 10)
    $deleted = $this->versionService->pruneOldVersions($article, keepCount: 10);
}
```

### Audit Service

```php
use App\Domain\Shared\Services\AuditService;

public function __construct(protected AuditService $auditService) {}

public function manageAudits()
{
    $article = Article::find(1);

    // Log action
    $log = $this->auditService->logAction(
        auth()->id(),
        'custom_action',
        $article,
        'Custom action description',
        oldValues: ['field' => 'old'],
        newValues: ['field' => 'new']
    );

    // Get audit logs with pagination
    $logs = $this->auditService->paginate(15);

    // Get audit trail for content
    $trail = $this->auditService->getAuditLog($article, perPage: 15);

    // Get user activity
    $userActivity = $this->auditService->getAuditTrail($userId, perPage: 15);

    // Get activity report
    $report = $this->auditService->getActivityReport(
        startDate: now()->subMonth(),
        endDate: now()
    );

    // Generate audit report
    $report = $this->auditService->generateAuditReport(
        format: 'array', // or 'json', 'csv'
        filters: [
            'start_date' => now()->subMonth(),
            'end_date' => now(),
            'user_id' => 1
        ]
    );

    // Get statistics
    $stats = $this->auditService->getAuditStats();

    // Search logs
    $logs = $this->auditService->searchLogs([
        'user_id' => 1,
        'action' => 'updated',
        'start_date' => now()->subWeek(),
        'end_date' => now()
    ], perPage: 15);

    // Export logs
    $csv = $this->auditService->exportLogs(
        filters: ['action' => 'updated'],
        format: 'csv'
    );

    // Prune old logs (keep last 90 days)
    $deleted = $this->auditService->pruneOldLogs(daysToKeep: 90);
}
```

## API Endpoints

### Version Control Endpoints

```bash
# Get version statistics
GET /su/portal/v1/admin/versions/stats

# Get versions for specific content
GET /su/portal/v1/admin/versions/{type}/{id}
# Example: GET /su/portal/v1/admin/versions/articles/123

# Get specific version
GET /su/portal/v1/admin/versions/{versionId}

# Compare two versions
GET /su/portal/v1/admin/versions/{v1}/{v2}/compare
# Example: GET /su/portal/v1/admin/versions/1/3/compare

# Revert to version
POST /su/portal/v1/admin/versions/revert
Body: {
    "version_id": 5,
    "reason": "Rolling back problematic changes"
}

# Get version timeline
GET /su/portal/v1/admin/versions/{type}/{id}/timeline

# Prune old versions
POST /su/portal/v1/admin/versions/{type}/{id}/prune
Body: {
    "keep_count": 10
}
```

### Audit Log Endpoints

```bash
# List audit logs
GET /su/portal/v1/admin/audit
Query params: ?user_id=1&action=updated&start_date=2025-01-01

# Get audit statistics
GET /su/portal/v1/admin/audit/stats

# Get specific audit entry
GET /su/portal/v1/admin/audit/{id}

# Get user activity
GET /su/portal/v1/admin/audit/user/{userId}

# Get user activity report
GET /su/portal/v1/admin/audit/user/{userId}/report
Query params: ?start_date=2025-01-01&end_date=2025-01-31

# Get logs by IP address
GET /su/portal/v1/admin/audit/ip/{ipAddress}

# Get logs by action type
GET /su/portal/v1/admin/audit/action/{action}

# Get activity report
GET /su/portal/v1/admin/audit/activity-report
Query params: ?start_date=2025-01-01&end_date=2025-01-31

# Get activity heatmap
GET /su/portal/v1/admin/audit/heatmap
Query params: ?start_date=2025-01-01&end_date=2025-01-31

# Generate custom report
POST /su/portal/v1/admin/audit/generate-report
Body: {
    "start_date": "2025-01-01",
    "end_date": "2025-01-31",
    "format": "json",
    "user_id": 1,
    "action": "updated"
}

# Export audit logs
GET /su/portal/v1/admin/audit/export
Query params: ?format=csv&start_date=2025-01-01&end_date=2025-01-31

# Prune old logs
POST /su/portal/v1/admin/audit/prune
Body: {
    "days_to_keep": 90
}
```

## Advanced Usage

### Custom Versionable Attributes

Override the `getVersionableAttributes()` method to customize what gets versioned:

```php
class Article extends Model
{
    use HasVersions;

    protected function getVersionableAttributes(): array
    {
        // Only version specific fields
        return [
            'title' => $this->title,
            'content' => $this->content,
            'status' => $this->status,
        ];
    }
}
```

### Custom Auditable Attributes

Override the `getAuditableAttributes()` method to customize what gets audited:

```php
class Article extends Model
{
    use HasAuditLog;

    protected function getAuditableAttributes(): array
    {
        // Exclude sensitive fields from audit
        return collect($this->getAttributes())
            ->except(['password', 'secret_key', 'token'])
            ->toArray();
    }
}
```

### Conditional Versioning/Auditing

Implement `shouldCreateVersion()` or `shouldAudit()` methods to control when versioning/auditing happens:

```php
class Article extends Model
{
    use HasVersions, HasAuditLog;

    public function shouldCreateVersion(): bool
    {
        // Only version published articles
        return $this->status === 'published';
    }

    public function shouldAudit(): bool
    {
        // Don't audit draft changes
        return $this->status !== 'draft';
    }
}
```

## Database Queries

### Querying Versions

```php
use App\Domain\Shared\Models\ContentVersion;

// Get all versions for article type
$versions = ContentVersion::where('versionable_type', Article::class)->get();

// Get versions by change type
$publishedVersions = ContentVersion::where('change_type', 'published')->get();

// Get versions in date range
$versions = ContentVersion::whereBetween('created_at', [$start, $end])->get();

// Get versions by user
$myVersions = ContentVersion::where('changed_by', auth()->id())->get();
```

### Querying Audit Logs

```php
use App\Domain\Shared\Models\AuditLog;

// Get all logs for article type
$logs = AuditLog::where('subject_type', Article::class)->get();

// Get logs by action
$updateLogs = AuditLog::where('action', 'updated')->get();

// Get logs by user
$userLogs = AuditLog::where('user_id', auth()->id())->get();

// Get logs by IP
$ipLogs = AuditLog::where('ip_address', '192.168.1.1')->get();

// Get recent logs
$recentLogs = AuditLog::latest('created_at')->limit(50)->get();
```

## Performance Tips

1. **Use Eager Loading**: When fetching versions or audit logs with user information:
   ```php
   $versions = $article->versions()->with('changedBy')->get();
   $logs = $article->auditLogs()->with('user')->get();
   ```

2. **Prune Regularly**: Set up a scheduled task to prune old data:
   ```php
   // In app/Console/Kernel.php
   $schedule->call(function () {
       $versionService = app(VersionService::class);
       $auditService = app(AuditService::class);

       // Keep last 10 versions per model
       // Keep last 90 days of audit logs
       $auditService->pruneOldLogs(90);
   })->monthly();
   ```

3. **Index Optimization**: Both tables are already indexed for performance, but if you frequently query by custom fields, add indexes:
   ```php
   Schema::table('content_versions', function (Blueprint $table) {
       $table->index('metadata->published_by');
   });
   ```

4. **Cache Statistics**: Statistics are already cached, but you can adjust TTL in repositories:
   ```php
   // In ContentVersionRepository
   protected int $cacheTTL = 600; // 10 minutes
   ```

## Troubleshooting

### Versions Not Being Created

1. Check if trait is added to model
2. Check if `shouldCreateVersion()` method returns true
3. Check database connection and migrations
4. Check logs for errors

### Audit Logs Not Being Created

1. Check if trait is added to model
2. Check if `shouldAudit()` method returns true
3. Check if request context is available (IP, user agent)
4. Check logs for errors

### Performance Issues

1. Implement version pruning
2. Implement audit log pruning
3. Add database indexes for frequently queried fields
4. Use eager loading for relationships
5. Increase cache TTL for statistics

## Best Practices

1. **Always version critical content**: Articles, Hadith, Quran verses, etc.
2. **Always audit user actions**: Especially admin actions
3. **Prune regularly**: Keep system performant
4. **Use meaningful version summaries**: Help team understand what changed
5. **Review audit logs regularly**: For security and compliance
6. **Export logs periodically**: For backup and compliance
7. **Test version revert in staging**: Before using in production
8. **Monitor disk space**: Versions and logs can grow large

## Security Considerations

1. **Restrict access**: Only admins should access version/audit endpoints
2. **Mask sensitive data**: Override `getAuditableAttributes()` to exclude secrets
3. **Rate limit**: Audit endpoints to prevent abuse
4. **Regular backups**: Export audit logs for compliance
5. **Monitor suspicious activity**: Use audit logs to detect anomalies
