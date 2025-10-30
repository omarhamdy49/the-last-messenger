# Admin Logger Service - Quick Reference Guide

## Integration Complete: 9 out of 11 Controllers

✅ **Integrated Controllers:**
1. ArticleController
2. HadithController  
3. MediaProgramController
4. QuranController
5. SahabiController
6. SeerahEventController
7. GenealogyController
8. UserController
9. RoleController

⚠️ **Not Required (Read-Only):**
10. DashboardController
11. DashboardWebController

## Logging Statistics

| Method | Usage Count | Purpose |
|--------|-------------|---------|
| `logCreate()` | 9 | Log model creation |
| `logUpdate()` | 9 | Log model updates with diff |
| `logDelete()` | 9 | Log model deletion |
| `logBulkAction()` | 10 | Log bulk operations |
| `logCustomAction()` | 2 | Log custom actions (role/permission assignment) |

**Total Logging Points:** 39 across all controllers

## Usage Examples

### Basic CRUD Logging

```php
// CREATE
$model = $this->service->create($data);
$this->logger->logCreate($model, $request->user());

// UPDATE
$oldValues = $model->toArray();
$model->update($data);
$this->logger->logUpdate($model, $oldValues, $model->fresh()->toArray(), $request->user());

// DELETE
$this->logger->logDelete($model, request()->user());
$model->delete();
```

### Bulk Action Logging

```php
$ids = $request->input('ids');
// Perform bulk operation
foreach ($ids as $id) {
    $this->service->delete($id);
}
$this->logger->logBulkAction('bulk_delete', ModelClass::class, $ids, $request->user());
```

### Custom Action Logging

```php
$this->logger->logCustomAction(
    'assign_roles',           // Action name
    $user,                    // Model
    $request->user(),         // Admin user performing action
    ['old_roles' => $old],    // Old values
    ['new_roles' => $new]     // New values
);
```

## What Gets Logged?

Each log entry captures:
- ✅ User ID (who performed the action)
- ✅ Action type (created, updated, deleted, etc.)
- ✅ Model type and ID
- ✅ Old values (before change)
- ✅ New values (after change)
- ✅ IP address
- ✅ User agent
- ✅ Timestamp

## Security Features

### Automatic Sensitive Data Filtering

The following fields are automatically redacted:
- `password`
- `password_confirmation`
- `remember_token`
- `api_token`
- `access_token`
- `secret`
- `token`
- `secret_key`
- `private_key`
- `api_secret`

**Filtered values show as:** `***FILTERED***`

## Viewing Logs

### Database Query
```sql
SELECT * FROM admin_logs 
WHERE user_id = 1 
ORDER BY created_at DESC 
LIMIT 50;
```

### Laravel Eloquent
```php
use App\Domain\Shared\Models\AdminLog;

// Recent logs
$logs = AdminLog::with('user')
    ->latest()
    ->take(50)
    ->get();

// Logs for specific model
$logs = AdminLog::where('model_type', Article::class)
    ->where('model_id', 123)
    ->get();

// Logs by action type
$logs = AdminLog::where('action', 'deleted')
    ->latest()
    ->paginate(15);
```

## Error Handling

All logging is fail-safe:
- ✅ Wrapped in try-catch blocks
- ✅ Failures logged to Laravel log
- ✅ Application continues even if logging fails
- ✅ No impact on user experience

## Best Practices

### DO ✅
- Always capture old values BEFORE updating
- Use `$model->fresh()` to get latest data after update
- Pass `$request->user()` for authenticated user
- Log bulk actions once (not per item)
- Use descriptive action names for custom actions

### DON'T ❌
- Log in middleware or service layer (controllers only)
- Manually filter sensitive data (automatic)
- Skip logging for "minor" changes
- Log GET/read operations (unnecessary overhead)

## Troubleshooting

### Logs Not Appearing?
1. Check `admin_logs` table exists: `php artisan migrate`
2. Verify user is authenticated: `auth()->check()`
3. Check Laravel log for errors: `storage/logs/laravel.log`

### Old Values Missing?
Ensure you capture values BEFORE the update:
```php
// ❌ WRONG
$model->update($data);
$oldValues = $model->toArray(); // Too late!

// ✅ CORRECT
$oldValues = $model->toArray();
$model->update($data);
```

## Testing

### Manual Testing Checklist
```bash
# 1. Create a record
POST /su/portal/v1/admin/articles

# 2. Check admin_logs table
SELECT * FROM admin_logs WHERE action = 'created' ORDER BY id DESC LIMIT 1;

# 3. Update the record
PUT /su/portal/v1/admin/articles/1

# 4. Check old_values vs new_values in log
SELECT old_values, new_values FROM admin_logs WHERE action = 'updated' ORDER BY id DESC LIMIT 1;

# 5. Delete the record
DELETE /su/portal/v1/admin/articles/1

# 6. Verify deletion logged
SELECT * FROM admin_logs WHERE action = 'deleted' ORDER BY id DESC LIMIT 1;
```

## Future Enhancements

- [ ] Admin UI to view logs
- [ ] Export logs to CSV/Excel
- [ ] Real-time notifications for critical actions
- [ ] Log retention policy (auto-archive old logs)
- [ ] Advanced filtering and search
- [ ] Rollback functionality using old values

---

**Last Updated:** October 29, 2025  
**Status:** Production Ready ✅
