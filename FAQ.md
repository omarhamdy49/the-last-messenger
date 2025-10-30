# Frequently Asked Questions (FAQ)

**The Last Messenger** - Admin Panel FAQ
**Version**: 1.0.0
**Last Updated**: 2025-10-29

---

## 📚 Table of Contents

1. [General Questions](#general-questions)
2. [Account & Access](#account--access)
3. [Content Management](#content-management)
4. [Technical Questions](#technical-questions)
5. [Troubleshooting](#troubleshooting)
6. [Performance & Optimization](#performance--optimization)

---

## 🌟 General Questions

### Q: What is The Last Messenger?

**A:** The Last Messenger is a comprehensive Islamic digital encyclopedia platform focusing on Prophet Muhammad ﷺ, featuring Seerah (biography), Hadith database, Quran module, media programs, timeline events, and genealogy. The admin panel is the backend management system for all content.

---

### Q: Who can access the admin panel?

**A:** Only authorized users with admin privileges can access the admin panel. There are 6 role levels:
1. **Super Admin** - Full system access
2. **Admin** - Manage content and users
3. **Editor** - Create and edit content
4. **Researcher** - Read-only research access
5. **User** - Basic authenticated access
6. **Guest** - Public access (no admin rights)

---

### Q: What browsers are supported?

**A:** The admin panel supports all modern browsers:
- ✅ Chrome 90+ (Recommended)
- ✅ Firefox 88+
- ✅ Safari 14+
- ✅ Edge 90+
- ✅ Opera 76+

**Mobile Browsers**:
- ✅ Chrome Mobile
- ✅ Safari iOS
- ✅ Firefox Mobile
- ✅ Samsung Internet

---

### Q: Is the admin panel mobile-friendly?

**A:** Yes! The admin panel is fully responsive and works on:
- 📱 Smartphones (portrait & landscape)
- 📱 Tablets
- 💻 Laptops
- 🖥️ Desktop monitors

However, for the best experience, we recommend using a desktop or tablet.

---

### Q: What languages are supported?

**A:** The admin panel supports 5 languages:
1. 🇸🇦 **Arabic (ar)** - Primary/default
2. 🇬🇧 **English (en)**
3. 🇫🇷 **French (fr)**
4. 🇹🇷 **Turkish (tr)**
5. 🇵🇰 **Urdu (ur)**

All content can be managed in all 5 languages simultaneously.

---

## 🔐 Account & Access

### Q: How do I get admin access?

**A:** Contact the system administrator or super admin to create an account for you. They will:
1. Create your user account
2. Assign appropriate role(s)
3. Send you login credentials
4. You should change the password on first login

---

### Q: I forgot my password. What should I do?

**A:** Currently, password reset must be done by an administrator. Contact your system admin with:
- Your email address
- Reason for reset
- Identity verification

**Planned for v1.1**: Self-service password reset via email.

---

### Q: Can I change my email address?

**A:** Yes, but only through an administrator. Your email is your unique identifier, so changes require admin approval.

---

### Q: How do I change my password?

**A:**
1. Click your name in the header
2. Select "الملف الشخصي" (Profile)
3. Click "تغيير كلمة المرور" (Change Password)
4. Enter current password
5. Enter new password (min. 8 characters)
6. Confirm new password
7. Click "تحديث" (Update)

---

### Q: What are the password requirements?

**A:** Passwords must meet these criteria:
- ✅ Minimum 8 characters
- ✅ At least 1 uppercase letter (A-Z)
- ✅ At least 1 lowercase letter (a-z)
- ✅ At least 1 number (0-9)
- ✅ At least 1 special character (!@#$%^&*)

**Example**: `MyPass123!`

---

### Q: Can I have multiple roles?

**A:** Yes! Users can be assigned multiple roles. For example, someone can be both an "editor" and "researcher". Their permissions will be the combination of all assigned roles.

---

### Q: How long do I stay logged in?

**A:**
- **Without "Remember Me"**: 2 hours
- **With "Remember Me"**: 30 days

Your session will auto-expire for security. You'll be redirected to the login page.

---

## 📝 Content Management

### Q: How do I create an article in multiple languages?

**A:**
1. Go to Articles → Create New
2. Use the language tabs (العربية, English, Français, Türkçe, اردو)
3. Fill in the title, content, and description for each language
4. At minimum, provide Arabic and English versions
5. Save or publish

**Tip**: You can save a draft with incomplete translations and finish later.

---

### Q: Can I bulk delete items?

**A:** Yes! Bulk delete is available for:
- ✅ Articles
- ✅ Hadith
- ✅ Media Programs
- ✅ Sahaba
- ✅ Seerah Events
- ✅ Users (admin only)

**How to use**:
1. Check the boxes next to items
2. Click "حذف المحدد" (Delete Selected)
3. Confirm the action
4. Items will be soft-deleted (recoverable for 30 days)

---

### Q: How do I upload images?

**A:** Two methods:

**Method 1 - Drag & Drop**:
1. Drag file from your computer
2. Drop onto the upload area
3. Wait for upload to complete
4. Image will appear in preview

**Method 2 - Click to Upload**:
1. Click "اسحب الملفات هنا أو انقر للتحديد"
2. Select file from your computer
3. Click "Open"
4. Wait for upload

**Limits**:
- Max file size: 100MB
- Supported formats: JPG, PNG, WebP
- Recommended size: 1200x630px for featured images

---

### Q: What video formats are supported?

**A:** The admin panel accepts video URLs from:
- ✅ YouTube (https://youtube.com/watch?v=...)
- ✅ Vimeo (https://vimeo.com/...)
- ✅ Direct MP4 links (https://example.com/video.mp4)

**Note**: Videos are not uploaded to the server. You provide a URL to the hosted video.

---

### Q: How do I feature content?

**A:** Most content types have a "Featured" checkbox:
- Articles: Check "مميز" in the form
- Media Programs: Check "Featured"
- Sahaba: Check "مميز"

Featured content appears prominently on the public website.

---

### Q: Can I schedule content to publish later?

**A:** Not in v1.0, but it's planned for v1.1. Currently, you can:
- Save as draft
- Manually publish when ready

---

### Q: How do I organize content into series?

**A:**
1. Create a Series (if it doesn't exist)
2. When creating/editing an article
3. Select the series from "السلسلة" dropdown
4. Save the article

All articles in a series will be linked together on the public website.

---

### Q: What's the difference between Draft, Published, and Archived?

**A:**
- **Draft (مسودة)**: Not visible to public, work in progress
- **Published (منشور)**: Live and visible on the website
- **Archived (أرشيف)**: Hidden from public but preserved for reference

---

### Q: How do I restore a deleted item?

**A:** Items are soft-deleted and recoverable for 30 days:
1. Contact your database administrator
2. Provide the item ID and type
3. They can restore from the database

**Planned for v1.1**: Built-in trash/restore functionality.

---

## 🔧 Technical Questions

### Q: Can I customize the design?

**A:** Yes! Developers can customize:
- ✅ Colors (edit Tailwind config)
- ✅ Fonts (change in layout file)
- ✅ Logo (replace image file)
- ✅ Layouts (edit Blade templates)
- ✅ Components (create new patterns)

See the Developer Guide for details.

---

### Q: How do I add a new language?

**A:** Adding a new language requires:
1. Add language code to `.env` (e.g., `es` for Spanish)
2. Create translation file: `resources/lang/es/admin.php`
3. Update database JSON columns to include new language
4. Update frontend language selector
5. Add language name to translations

**Recommended**: Contact a developer for this task.

---

### Q: Can I backup my data?

**A:** Yes! Backups are automated:
- **Database**: Daily at 2 AM
- **Files**: Daily at 3 AM
- **Retention**: 7 days for database, 30 days for full backups

Manual backup:
```bash
php artisan backup:run
```

See Deployment Guide for backup scripts.

---

### Q: How do I clear the cache?

**A:** From the command line:
```bash
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
```

**Planned for v1.1**: Cache clear button in admin panel.

---

### Q: What's the rate limit for API calls?

**A:**
- **Admin API**: 60 requests per minute per IP
- **Public API**: 120 requests per minute per IP

If exceeded, you'll get a 429 (Too Many Requests) error. Wait 1 minute and try again.

---

### Q: Can I integrate with external services?

**A:** Yes! The admin panel provides:
- ✅ RESTful API
- ✅ JSON responses
- ✅ Authentication via Sanctum
- ✅ Webhook support (planned)

See API Documentation for integration details.

---

## 🐛 Troubleshooting

### Q: Dark mode doesn't work. How do I fix it?

**A:** Try these solutions:

**Solution 1**: Clear localStorage
```javascript
// Open browser console (F12), paste this:
localStorage.clear()
// Refresh page
```

**Solution 2**: Toggle manually
1. Click moon/sun icon in header
2. If it doesn't toggle, try a different browser

**Solution 3**: Check browser compatibility
- Update to latest browser version
- Disable browser extensions that might interfere

---

### Q: Images aren't uploading. What's wrong?

**A:** Common causes and fixes:

**File too large**:
- ✅ Max size is 100MB
- ✅ Compress your image before uploading
- ✅ Use online tools like TinyPNG

**Wrong file type**:
- ✅ Supported: JPG, PNG, WebP
- ✅ Not supported: BMP, TIFF, GIF
- ✅ Convert to supported format

**Network issue**:
- ✅ Check internet connection
- ✅ Try again in a few minutes
- ✅ Contact IT if persistent

**Storage full**:
- ✅ Contact system administrator
- ✅ They may need to increase storage

---

### Q: Dashboard not loading. What should I do?

**A:** Troubleshooting steps:

1. **Refresh the page** (Ctrl+R or Cmd+R)
2. **Clear browser cache** (Ctrl+Shift+Delete)
3. **Check internet connection**
4. **Try incognito mode** (Ctrl+Shift+N)
5. **Try different browser**
6. **Contact support** if still not working

---

### Q: Search returns no results even though content exists

**A:** This usually means:

**Indexing issue**:
- Search indexes may need rebuilding
- Contact system administrator

**Search syntax**:
- Try simpler search terms
- Remove special characters
- Use Arabic or English text (not mixed)

**Filters too restrictive**:
- Reset all filters
- Try search without filters
- Check if status filter is correct

---

### Q: I see "Unauthorized Access" error

**A:** This means:

**Insufficient permissions**:
- ✅ Your role doesn't have permission for this action
- ✅ Contact admin to assign proper role
- ✅ Check with super admin if needed

**Session expired**:
- ✅ Log out and log in again
- ✅ Your session may have timed out

**Account suspended**:
- ✅ Contact system administrator
- ✅ Your account may be temporarily disabled

---

### Q: Forms won't submit. What's the issue?

**A:** Common fixes:

**Validation errors**:
- ✅ Check all required fields (marked with *)
- ✅ Look for red error messages
- ✅ Ensure passwords match

**JavaScript error**:
- ✅ Open browser console (F12)
- ✅ Look for red error messages
- ✅ Screenshot and send to support

**Network timeout**:
- ✅ Check internet connection
- ✅ Try again
- ✅ Save work as draft first

**File too large**:
- ✅ If uploading files, check size limits
- ✅ Compress files before upload

---

### Q: Arabic text appears backwards or broken

**A:** This is an RTL (Right-to-Left) issue:

**Solution 1**: Check browser
- ✅ Update to latest version
- ✅ Chrome and Firefox have best RTL support

**Solution 2**: Check font
- ✅ Ensure Cairo font is loading
- ✅ Check browser console for font errors

**Solution 3**: Clear cache
- ✅ Clear browser cache
- ✅ Refresh page

---

### Q: Slow performance. How can I speed it up?

**A:** Performance tips:

**Browser**:
- ✅ Close unnecessary tabs
- ✅ Clear cache regularly
- ✅ Disable unused extensions
- ✅ Update to latest version

**Network**:
- ✅ Check internet speed
- ✅ Use wired connection if possible
- ✅ Avoid peak hours

**System**:
- ✅ Close other applications
- ✅ Restart computer
- ✅ Check for malware

**Server**:
- ✅ Contact admin if widespread issue
- ✅ May need server optimization

---

## ⚡ Performance & Optimization

### Q: How many items can I display per page?

**A:** You can choose from:
- 10 items per page
- 25 items per page
- 50 items per page
- 100 items per page

**Recommendation**: Use 25-50 for best balance of usability and performance.

---

### Q: How long does it take for content to appear on the website?

**A:** Content appears immediately after publishing. However:
- ✅ Cache may need to refresh (15 minutes max)
- ✅ Search indexes update every hour
- ✅ For instant visibility, clear cache

---

### Q: Can I work offline?

**A:** No, the admin panel requires internet connection. However:
- ✅ Write content in offline editor
- ✅ Copy/paste when online
- ✅ PWA offline mode (planned for v1.1)

---

### Q: How much storage do I have?

**A:** Storage depends on your hosting plan:
- **Standard**: 20GB
- **Professional**: 50GB
- **Enterprise**: 100GB+

Check with your administrator for current usage.

---

### Q: What happens when storage is full?

**A:** When storage reaches 90%:
- ⚠️ Warning message appears
- ⚠️ File uploads may be blocked
- ⚠️ Contact admin to increase storage

**Solutions**:
- Delete unnecessary files
- Compress images before upload
- Archive old content
- Upgrade storage plan

---

## 💬 Getting More Help

### Q: Where can I find more documentation?

**A:** Complete documentation available:
- 📖 **Admin User Guide** - How to use the admin panel
- 👨‍💻 **Developer Guide** - Technical documentation
- 🚀 **Deployment Guide** - Server setup
- 📡 **API Documentation** - API reference
- 🎨 **Component Reference** - UI components
- 🔒 **Security Guide** - Security best practices

---

### Q: How do I report a bug?

**A:** To report a bug:
1. **Document the issue**:
   - What were you trying to do?
   - What happened instead?
   - Steps to reproduce

2. **Include details**:
   - Browser name and version
   - Operating system
   - Screenshots if possible
   - Error messages

3. **Submit**:
   - Email: bugs@thelastmessenger.com
   - GitHub: Create an issue
   - Support portal: Submit ticket

---

### Q: Can I request new features?

**A:** Absolutely! We welcome feature requests:
1. **Check existing requests** (avoid duplicates)
2. **Describe the feature** in detail
3. **Explain why it's useful**
4. **Provide examples** if possible
5. **Submit** via email or GitHub

Priority given to most-requested features.

---

### Q: How do I contact support?

**A:** Multiple support channels:

📧 **Email**:
- General: support@thelastmessenger.com
- Technical: tech@thelastmessenger.com
- Bugs: bugs@thelastmessenger.com

💬 **Live Chat**:
- Available in admin panel (planned)
- Monday-Friday, 9 AM - 5 PM (GMT)

🌐 **Website**:
- https://thelastmessenger.com/support

📖 **Documentation**:
- https://docs.thelastmessenger.com

🐛 **GitHub**:
- https://github.com/your-org/the-last-messenger-be/issues

---

### Q: Is there training available?

**A:** Yes! Training options:

**Documentation**:
- ✅ Comprehensive user guide
- ✅ Video tutorials (coming soon)
- ✅ FAQ (this document)

**Live Training**:
- ✅ On-site training available
- ✅ Remote training sessions
- ✅ Custom training for teams

**Self-Paced**:
- ✅ Video courses (planned)
- ✅ Interactive tutorials (planned)

Contact support@thelastmessenger.com for training inquiries.

---

## 🎉 Tips & Best Practices

### Q: What are some productivity tips?

**A:** Power user tips:

**Keyboard Shortcuts**:
- `Ctrl+K`: Open search
- `Ctrl+S`: Save form
- `Ctrl+/`: Toggle sidebar
- `Esc`: Close modal

**Workflow**:
- ✅ Use bulk actions for multiple items
- ✅ Save drafts frequently
- ✅ Use series to organize content
- ✅ Tag content for easy searching
- ✅ Use filters to narrow results

**Organization**:
- ✅ Create content plan
- ✅ Use consistent naming
- ✅ Assign proper categories
- ✅ Review and update old content

---

### Q: How do I ensure content quality?

**A:** Quality checklist:

✅ **Accuracy**:
- Verify all facts
- Cite authentic sources
- Cross-reference information

✅ **Completeness**:
- Fill all language versions
- Add proper descriptions
- Include relevant images

✅ **Formatting**:
- Use proper headings
- Break into paragraphs
- Add lists where appropriate
- Check for typos

✅ **SEO**:
- Write descriptive titles
- Add meta descriptions
- Use relevant keywords
- Optimize images

✅ **Review**:
- Preview before publishing
- Have someone else review
- Test links
- Check mobile display

---

## 📞 Still Have Questions?

If your question isn't answered here:
1. Check the complete Admin User Guide
2. Search the documentation site
3. Contact support
4. Ask in the community forum

**We're here to help!** 🤝

---

**Document Version**: 1.0.0
**Last Updated**: 2025-10-29
**Maintained By**: The Last Messenger Support Team
