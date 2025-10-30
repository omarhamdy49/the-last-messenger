# Admin Panel Login Credentials

## 🔐 Test Credentials

Use these credentials to log in to the admin panel:

### 👑 Super Admin (Full Access)
```
Email:    superadmin@thelastmessenger.com
Password: SuperAdmin123!
```

### 👤 Admin (Content Management)
```
Email:    admin@thelastmessenger.com
Password: Admin123!
```

### ✏️ Editor (Edit Content)
```
Email:    editor@thelastmessenger.com
Password: Editor123!
```

---

## 🌐 Access URLs

### Login Page
```
http://localhost:8000/su/portal/v1/admin/login
```

### Dashboard (after login)
```
http://localhost:8000/su/portal/v1/admin/dashboard
```

### Sahaba Management
```
http://localhost:8000/su/portal/v1/admin/sahaba
```

---

## 🚀 Quick Start

1. **Start the development server:**
   ```bash
   php artisan serve
   ```

2. **Open your browser:**
   ```
   http://localhost:8000/su/portal/v1/admin/login
   ```

3. **Login with any of the credentials above**

4. **Explore the admin panel!**

---

## ⚠️ Security Notes

- **NEVER use these credentials in production!**
- Change all passwords immediately after deployment
- Use strong, unique passwords for production environments
- Enable two-factor authentication in production
- Regularly rotate admin passwords

---

## 🐛 Troubleshooting

### Can't log in?
1. Make sure the database is migrated: `php artisan migrate`
2. Seed admin users: `php artisan db:seed --class=AdminUserSeeder`
3. Clear cache: `php artisan cache:clear`
4. Check logs: `storage/logs/laravel.log`

### "419 Page Expired" error?
- Clear browser cookies and try again
- Check that APP_KEY is set in `.env`

### Redirected back to login?
- Make sure you're using the correct email
- Passwords are case-sensitive
- Check that the user has admin/super_admin/editor role

---

**Last Updated:** 2025-10-28
