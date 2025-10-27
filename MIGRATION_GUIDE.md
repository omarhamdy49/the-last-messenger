# Database Migration Guide

## Current Issue
Error: `SQLSTATE[42S01]: Base table or view already exists: 1050 Table 'articles' already exists`

This means migrations were partially run. You need to reset the database.

---

## Solution 1: Use SQLite (Recommended for Development)

SQLite is already configured and ready to use:

```bash
# Make sure you're using SQLite in .env
# DB_CONNECTION=sqlite
# DB_DATABASE=/path/to/database.sqlite

# Fresh migration
php artisan migrate:fresh

# With seeding (roles & permissions)
php artisan migrate:fresh --seed
```

---

## Solution 2: Reset MySQL Database

### Option A: Manual Database Reset
```sql
-- Connect to MySQL
mysql -u root -p

-- Drop and recreate database
DROP DATABASE IF EXISTS the_last_messenger;
CREATE DATABASE the_last_messenger CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Exit MySQL
exit;
```

Then run:
```bash
php artisan migrate:fresh --seed
```

### Option B: Use Artisan Commands
```bash
# Rollback all migrations
php artisan migrate:reset

# Run all migrations again
php artisan migrate

# Or do both in one command (fresh)
php artisan migrate:fresh --seed
```

---

## Solution 3: Skip Existing Tables

If you want to keep existing data and only run new migrations:

```bash
# This will only run pending migrations
php artisan migrate
```

But you'll need to manually handle the conflict by either:
1. Dropping the conflicting table
2. Modifying the migration to check if table exists

---

## Verifying Migration Status

Check which migrations have run:
```bash
php artisan migrate:status
```

---

## After Successful Migration

1. **Seed roles and permissions:**
```bash
php artisan db:seed --class=RolePermissionSeeder
```

2. **Create a test user:**
```bash
php artisan tinker

# In tinker:
$user = App\Models\User::create([
    'name' => 'Admin User',
    'email' => 'admin@example.com',
    'password' => Hash::make('password'),
    'language' => 'ar'
]);

$user->assignRole('super_admin');
```

3. **Test the API:**
```bash
# Start server
php artisan serve

# Test endpoints:
# http://localhost:8000/api/health
# http://localhost:8000/api/articles
# http://localhost:8000/api/hadith/books
```

---

## Quick Commands Reference

```bash
# Fresh migration (drops all tables)
php artisan migrate:fresh

# Fresh with seeding
php artisan migrate:fresh --seed

# Rollback last batch
php artisan migrate:rollback

# Rollback all migrations
php artisan migrate:reset

# Check migration status
php artisan migrate:status

# Run pending migrations
php artisan migrate
```

---

## Database Configuration

### For SQLite (.env):
```env
DB_CONNECTION=sqlite
DB_DATABASE=/absolute/path/to/database/database.sqlite
```

### For MySQL (.env):
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=the_last_messenger
DB_USERNAME=root
DB_PASSWORD=your_password
```

---

## Troubleshooting

### Issue: "Table already exists"
**Solution:** Run `php artisan migrate:fresh`

### Issue: "No such table: migrations"
**Solution:** Run `php artisan migrate:install` then `php artisan migrate`

### Issue: "SQLSTATE[HY000] [2002] Operation not permitted"
**Solution:** 
- Check MySQL is running: `mysql.server status`
- Check credentials in .env file
- Try using SQLite instead

### Issue: "Class 'Database\Seeders\RolePermissionSeeder' not found"
**Solution:** Run `composer dump-autoload` first

---

## Recommended Workflow

For clean development setup:

```bash
# 1. Fresh migration
php artisan migrate:fresh

# 2. Seed roles & permissions
php artisan db:seed --class=RolePermissionSeeder

# 3. Create test data (optional)
php artisan tinker
# Create users, articles, etc.

# 4. Start development server
php artisan serve
```

---

## Current Database Schema

After successful migration, you'll have:

**20+ Tables:**
- users, password_reset_tokens, sessions
- personal_access_tokens (Sanctum)
- roles, permissions, model_has_roles, etc. (Spatie Permission)
- media (Spatie Media Library)
- categories, tags, taggables, series
- articles
- favorites, user_interactions
- hadith_books, hadith_chapters, hadith, hadith_narrators, hadith_narrator_chain
- media_categories, media_programs, media_episodes

**Total Migrations:** 20+  
**Estimated Schema Size:** ~50 columns across all tables  
**Indexes:** Optimized with proper foreign keys and search indexes

