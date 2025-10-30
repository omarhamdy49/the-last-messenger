# Admin Panel User Guide

**The Last Messenger** - Islamic Digital Encyclopedia
**Version**: 1.0.0
**Last Updated**: 2025-10-29

---

## 📚 Table of Contents

1. [Getting Started](#getting-started)
2. [Dashboard Overview](#dashboard-overview)
3. [Managing Content](#managing-content)
4. [Activity Logs](#activity-logs)
5. [Settings](#settings)
6. [Troubleshooting](#troubleshooting)

---

## 🚀 Getting Started

### Accessing the Admin Panel

1. **Navigate to the admin login page**:
   ```
   http://your-domain.com/su/portal/v1/admin/login
   ```

2. **Enter your credentials**:
   - Email address
   - Password
   - (Optional) Check "Remember Me" to stay logged in

3. **Click "تسجيل الدخول" (Login)**

### Default Admin Accounts

For testing purposes, use these pre-seeded accounts:

**Super Admin** (Full Access):
- Email: `superadmin@thelastmessenger.com`
- Password: `SuperAdmin123!`

**Admin** (Content Management):
- Email: `admin@thelastmessenger.com`
- Password: `Admin123!`

**Editor** (Edit Content):
- Email: `editor@thelastmessenger.com`
- Password: `Editor123!`

⚠️ **IMPORTANT**: Change these passwords immediately in production!

### First Login

After logging in, you'll be redirected to the **Dashboard** which displays:
- 📊 Key statistics (articles, hadith, media programs, users)
- 📈 Content growth charts
- 🕒 Recent activity timeline
- ⚡ Quick action buttons

---

## 🎯 Dashboard Overview

### Statistics Cards

The dashboard displays four main statistics cards:

#### 1. **المقالات (Articles)**
- Total article count
- Monthly growth percentage
- Blue gradient card
- Click to view articles management

#### 2. **الأحاديث (Hadith)**
- Total hadith count
- Monthly growth percentage
- Green gradient card
- Click to view hadith management

#### 3. **البرامج الإعلامية (Media Programs)**
- Total programs count
- Monthly growth percentage
- Purple gradient card
- Click to view media management

#### 4. **المستخدمون (Users)**
- Total user count
- Monthly growth percentage
- Cyan gradient card
- Click to view user management

### Interactive Features

**Dark Mode Toggle**:
- Click the moon/sun icon in the header
- Preference saved automatically to localStorage
- Charts update dynamically

**Charts**:
- **نمو المحتوى (Content Growth)**: Last 6 months trend
- **توزيع المحتوى (Content Distribution)**: Content by type

**Quick Actions**:
- ➕ Create new article
- ➕ Add new hadith
- ➕ Create media program
- ➕ Add companion (Sahabi)

**System Status**:
- ✅ Server status
- ✅ Database connection
- 💾 Storage usage

---

## 📝 Managing Content

### Articles Management (إدارة المقالات)

Navigate to: **Articles** → **Manage Articles**

#### Viewing Articles

**List View Features**:
- 📊 Statistics cards (Total, Published, Draft, Archived)
- 🔍 Search bar (searches titles and content)
- 🎚️ Filters: Status, Category, Author
- 📄 Pagination (10, 25, 50, 100 items per page)
- 🗑️ Bulk actions (delete, publish, archive)

**Table Columns**:
- ID
- Title (shows Arabic by default)
- Category
- Status (badge colored)
- Author
- Views
- Created Date
- Actions (Edit, Delete)

#### Creating a New Article

1. Click **"مقال جديد" (New Article)** button
2. Fill in the multi-language form:

**Basic Information**:
- **العنوان (Title)** - Required in all 5 languages (Arabic, English, French, Turkish, Urdu)
- **المحتوى (Content)** - Rich text editor for each language
- **مقتطف (Excerpt)** - Short summary
- **الوصف التعريفي (Meta Description)** - SEO description

**Categorization**:
- **التصنيف (Category)** - Select from dropdown
- **السلسلة (Series)** - Optional article series
- **الوسوم (Tags)** - Add multiple tags

**Status**:
- 📝 **مسودة (Draft)** - Not visible to users
- ✅ **منشور (Published)** - Live on the website
- 🗃️ **أرشيف (Archived)** - Hidden from public

**Media**:
- **الصورة المميزة (Featured Image)** - Main article image
- **معرض الصور (Gallery)** - Additional images

**SEO**:
- **الرابط (Slug)** - Auto-generated from title
- **كلمات مفتاحية (Keywords)** - For search optimization

3. Click **"حفظ" (Save)** or **"حفظ ومتابعة" (Save & Continue)**

#### Editing an Article

1. Click **"تعديل" (Edit)** button on any article
2. Update the desired fields
3. Click **"تحديث" (Update)**

**Tips**:
- ✅ Preview changes before publishing
- ✅ Check all language versions
- ✅ Verify images load correctly
- ✅ Test links in content

#### Deleting an Article

**Single Delete**:
1. Click **"حذف" (Delete)** button
2. Confirm deletion in the popup
3. Article moved to trash (soft delete)

**Bulk Delete**:
1. Select multiple articles using checkboxes
2. Click **"حذف المحدد" (Delete Selected)**
3. Confirm bulk deletion

⚠️ **Warning**: Deleted articles can be recovered within 30 days.

#### Bulk Actions

**Available Actions**:
- 🗑️ **Bulk Delete** - Delete multiple articles
- ✅ **Bulk Publish** - Publish multiple drafts
- 🗃️ **Bulk Archive** - Archive multiple articles

**How to Use**:
1. Check boxes next to articles
2. Select action from bulk actions dropdown
3. Click "Apply"
4. Confirm action

#### Search and Filter

**Search**:
- Type in the search box (500ms debounce)
- Searches: Title, Content, Excerpt
- Works with Arabic and English text

**Filters**:
- **Status**: All, Published, Draft, Archived
- **Category**: Select from dropdown
- **Author**: Filter by author name
- **Date Range**: From date → To date

**Tips**:
- 🔍 Combine search with filters for precise results
- 🔄 Click "إعادة تعيين" (Reset) to clear all filters
- 💾 Filters persist during your session

---

### Hadith Management (إدارة الأحاديث)

Navigate to: **Hadith** → **Manage Hadith**

#### Viewing Hadith

**Statistics**:
- Total hadith count
- By authenticity grade
- By book

**List View**:
- Hadith text (Arabic + English)
- Book name
- Chapter
- Hadith number
- Authenticity grade (badge colored)
- Narrator chain
- Actions

#### Creating a New Hadith

1. Click **"حديث جديد" (New Hadith)**
2. Fill in the form:

**Text**:
- **النص العربي (Arabic Text)** - Full hadith in Arabic
- **النص الإنجليزي (English Translation)** - English version
- **الشرح (Explanation)** - Commentary (5 languages)

**Classification**:
- **الكتاب (Book)** - Sahih Bukhari, Muslim, etc.
- **الباب (Chapter)** - Chapter within the book
- **رقم الحديث (Hadith Number)** - Reference number

**Authenticity**:
- صحيح (Sahih) - Authentic
- حسن (Hasan) - Good
- ضعيف (Daif) - Weak
- موضوع (Mawdu) - Fabricated

**Narrator Chain**:
- Add narrators in order
- Link to narrator profiles
- Specify reliability grade

3. Click **"حفظ" (Save)**

#### Tips for Hadith Management

- ✅ Always verify hadith text with authentic sources
- ✅ Include complete narrator chain
- ✅ Add cross-references to similar hadith
- ✅ Tag by topic for easier searching
- ⚠️ Mark weak hadith clearly

---

### Media Programs Management (إدارة البرامج الإعلامية)

Navigate to: **Media** → **Media Programs**

#### Viewing Programs

**Card View**:
- Program thumbnail
- Title (multi-language)
- Category
- Episode count
- Views
- Status

#### Creating a New Program

1. Click **"برنامج جديد" (New Program)**
2. Fill in the form:

**Basic Info**:
- **اسم البرنامج (Program Name)** - 5 languages
- **الوصف (Description)** - 5 languages
- **التصنيف (Category)** - Documentary, Series, Lecture, etc.

**Media**:
- **صورة البرنامج (Thumbnail)** - Square image (recommended 500x500)
- **صورة الغلاف (Banner)** - Wide image (recommended 1920x400)
- **الإعلان التشويقي (Trailer)** - YouTube/Vimeo URL

**Episodes**:
- Add episodes after creating program
- Each episode has: Title, Description, Video URL, Duration

**Metadata**:
- **المقدم (Presenter)** - Host/presenter name
- **سنة الإنتاج (Production Year)**
- **الوسوم (Tags)**

3. Click **"حفظ" (Save)**

#### Managing Episodes

**Adding Episodes**:
1. Open program detail page
2. Click **"إضافة حلقة" (Add Episode)**
3. Fill episode form:
   - Episode number
   - Title
   - Description
   - Video URL (YouTube, Vimeo, or direct link)
   - Duration
   - Thumbnail
4. Click **"حفظ" (Save)**

**Reordering Episodes**:
- Drag and drop episodes to reorder
- Changes save automatically

---

### Quran Management (إدارة القرآن الكريم)

Navigate to: **Quran** → **Manage Quran**

#### Viewing Verses

**List View**:
- Surah name
- Verse number
- Arabic text
- Translation (5 languages)
- Tafseer (interpretation)
- Actions

#### Editing Translations

1. Click **"تعديل" (Edit)** on a verse
2. Update translations:
   - English
   - French
   - Turkish
   - Urdu
3. Add/edit Tafseer
4. Click **"تحديث" (Update)**

**Note**: Arabic Quran text is read-only and cannot be edited.

#### Managing Tafseer

**Adding Tafseer**:
1. Select verse
2. Click **"إضافة تفسير" (Add Tafseer)**
3. Choose Mufassir (interpreter):
   - Ibn Kathir
   - Qurtubi
   - Tabari
   - Al-Jalalayn
4. Add interpretation text
5. Save

---

### Companions Management (إدارة الصحابة)

Navigate to: **Sahaba** → **Manage Companions**

#### Viewing Companions

**Statistics**:
- Total companions
- By gender
- By status (Sahabi, Tabii, Tabi Tabiin)

**List View**:
- Name (Arabic + English)
- Kunya (nickname)
- Tribe
- Status
- Hadith count
- Actions

#### Creating a New Companion Profile

1. Click **"صحابي جديد" (New Sahabi)**
2. Fill comprehensive form:

**Names** (5 languages each):
- **الاسم (Name)**
- **الاسم الكامل (Full Name)**
- **الكنية (Kunya)** - Abu/Umm title
- **اللقب (Laqab)** - Honorific title

**Lineage**:
- **القبيلة (Tribe)**
- **العشيرة (Clan)**
- **اسم الأب (Father's Name)**
- **اسم الأم (Mother's Name)**

**Biography** (5 languages):
- **السيرة الذاتية (Biography)** - Life story
- **الفضائل (Virtues)** - Notable qualities
- **الأحاديث المشهورة (Famous Narrations)** - Key hadith narrated

**Dates**:
- **تاريخ الميلاد (Birth Date)** - Hijri + Gregorian
- **تاريخ الوفاة (Death Date)** - Hijri + Gregorian
- **تاريخ الإسلام (Conversion Date)**
- **ترتيب الإسلام (Conversion Order)** - Rank in accepting Islam

**Places**:
- **مكان الميلاد (Birthplace)**
- **مكان الوفاة (Death Place)**
- **مكان الدفن (Burial Place)**

**Statistics**:
- **عدد الأحاديث (Hadith Count)** - Number of hadith narrated
- **ترتيب الأهمية (Importance Rank)** - 1-10 scale
- **مميز (Featured)** - Checkbox for featured companions

**Gender & Status**:
- **الجنس (Gender)**: Male / Female
- **الصفة (Status)**: Sahabi / Tabii / Tabi Tabiin

3. Click **"حفظ" (Save)**

#### Tips for Sahaba Profiles

- ✅ Include authentic historical sources
- ✅ Cross-reference with genealogy tree
- ✅ Link related hadith
- ✅ Add historical context
- ✅ Verify dates with multiple sources

---

### Seerah Events Management (إدارة أحداث السيرة)

Navigate to: **Seerah** → **Timeline Events**

#### Viewing Events

**Timeline View**:
- Chronological display
- Hijri + Gregorian dates
- Event cards with details
- Zoom in/out functionality

**List View**:
- Event title
- Date (Hijri/Gregorian)
- Location
- Category
- Participants
- Actions

#### Creating a New Event

1. Click **"حدث جديد" (New Event)**
2. Fill the form:

**Event Details** (5 languages):
- **العنوان (Title)**
- **الوصف (Description)**
- **التفاصيل (Details)** - Full event story

**Dates**:
- **التاريخ الهجري (Hijri Date)** - Islamic calendar
- **التاريخ الميلادي (Gregorian Date)** - Western calendar
- **المدة (Duration)** - If event lasted multiple days

**Location**:
- **الموقع (Location Name)**
- **خطوط الطول والعرض (Coordinates)** - For map display

**Classification**:
- **الفئة (Category)**:
  - Birth/Death
  - Battle
  - Treaty
  - Migration
  - Miracle
  - Revelation
  - Delegation
- **الأهمية (Importance)**: 1-10 scale

**Participants**:
- Add companion profiles who participated
- Specify roles (e.g., commander, messenger)

**Sources**:
- Add historical references
- Link to books/pages

3. Click **"حفظ" (Save)**

#### Timeline Features

**Navigation**:
- Scroll through timeline
- Jump to specific year
- Filter by category
- Search by name

**Interactive Map**:
- Events shown on map
- Click marker for details
- Filter by location

---

### Genealogy Management (شجرة العائلة)

Navigate to: **Genealogy** → **Family Tree**

#### Viewing the Tree

**Interactive Tree View**:
- Centered on Prophet Muhammad ﷺ
- Zoom in/out
- Pan around
- Collapse/expand branches
- Search for person

**List View**:
- Person name
- Father/Mother
- Spouses
- Children count
- Generation
- Actions

#### Adding a Person

1. Click **"شخص جديد" (New Person)**
2. Fill the form:

**Basic Info** (5 languages):
- **الاسم الكامل (Full Name)**
- **الكنية (Kunya)**
- **اللقب (Laqab)**

**Biography** (5 languages):
- **السيرة (Biography)**
- **ملاحظات (Notes)**

**Dates**:
- **تاريخ الميلاد (Birth Date)**
- **تاريخ الوفاة (Death Date)**

**Relationships**:
- **الأب (Father)** - Select from existing people
- **الأم (Mother)** - Select from existing people
- **الأزواج (Spouses)** - Can add multiple
- **الأبناء (Children)** - Can add multiple

**Generation**:
- Calculated automatically from relationships
- Can be manually overridden

3. Click **"حفظ" (Save)**

#### Managing Relationships

**Adding Relationships**:
1. Open person detail page
2. Click **"إضافة علاقة" (Add Relationship)**
3. Select relationship type:
   - Father/Mother
   - Spouse
   - Child
   - Sibling
4. Select person from dropdown
5. Save

**Editing Relationships**:
- Click on relationship
- Change person or type
- Save changes

**Deleting Relationships**:
- Click delete icon on relationship
- Confirm deletion
- Tree updates automatically

#### Tree Visualization Tips

- 🌳 Use zoom to focus on specific branches
- 🔍 Search to jump to any person
- 📱 Works on mobile with touch gestures
- 💾 Tree layout auto-saves
- 🎨 Color-coded by generation

---

### Users Management (إدارة المستخدمين)

Navigate to: **Users** → **Manage Users**

> **Note**: Only Super Admin and Admin roles can access this section.

#### Viewing Users

**Statistics**:
- Total users
- Active users
- Verified emails
- By role

**List View**:
- User ID
- Name
- Email
- Roles (badges)
- Email verified status
- Created date
- Actions

#### Creating a New User

1. Click **"مستخدم جديد" (New User)**
2. Fill the form:

**Account Details**:
- **الاسم (Name)** - Full name
- **البريد الإلكتروني (Email)** - Must be unique
- **كلمة المرور (Password)** - Min. 8 characters
- **تأكيد كلمة المرور (Confirm Password)**

**Preferences**:
- **اللغة (Language)** - Default language (ar, en, fr, tr, ur)
- **البريد مفعل (Email Verified)** - Check to verify email immediately

**Role Assignment**:
- Select one or more roles:
  - 👑 **Super Admin** - Full system access
  - 🛡️ **Admin** - Manage content and users
  - ✏️ **Editor** - Create and edit content
  - 🔍 **Researcher** - Read-only research access
  - 👤 **User** - Basic user access
  - 👥 **Guest** - Public access only

3. Click **"حفظ" (Save)**

**Password Requirements**:
- ✅ Minimum 8 characters
- ✅ At least one uppercase letter
- ✅ At least one lowercase letter
- ✅ At least one number
- ✅ At least one special character (!@#$%^&*)

#### Editing User Profile

1. Click **"تعديل" (Edit)** on user
2. Update information:
   - Name
   - Email
   - Language preference
   - Roles
3. (Optional) Change password
4. Click **"تحديث" (Update)**

#### User Statistics

**View user activity**:
- Articles created
- Favorites count
- Comments posted
- Login history
- Last active date

#### Bulk Actions

**Available Actions**:
- 🗑️ **Bulk Delete** - Delete multiple users
- ✅ **Verify Emails** - Mark emails as verified
- 🔒 **Suspend Accounts** - Temporarily disable access
- 👑 **Assign Roles** - Bulk role assignment

---

### Roles & Permissions Management (إدارة الصلاحيات)

Navigate to: **Users** → **Roles & Permissions**

> **Note**: Only Super Admin role can access this section.

#### Viewing Roles

**Card View**:
- Role name
- Display name (5 languages)
- Permission count
- User count
- Actions

**Available Roles**:
1. 👑 **Super Admin** - Full system access (cannot be deleted)
2. 🛡️ **Admin** - Manage content and users
3. ✏️ **Editor** - Create and edit content
4. 🔍 **Researcher** - Read-only research access
5. 👤 **User** - Basic user access
6. 👥 **Guest** - Public access only

#### Creating a New Role

1. Click **"صلاحية جديدة" (New Role)**
2. Fill the form:

**Basic Info**:
- **اسم الصلاحية (Role Name)** - English, lowercase, no spaces (e.g., content_manager)
- **الاسم المعروض (Display Name)** - 5 languages
- **الوصف (Description)** - 5 languages
- **الحارس (Guard Name)** - Default: web

**Permissions**:
Select permissions by module:

**Articles Module**:
- ✅ articles.view
- ✅ articles.create
- ✅ articles.update
- ✅ articles.delete
- ✅ articles.publish
- ✅ articles.archive

**Hadith Module**:
- ✅ hadith.view
- ✅ hadith.create
- ✅ hadith.update
- ✅ hadith.delete

**Media Module**:
- ✅ media.view
- ✅ media.create
- ✅ media.update
- ✅ media.delete
- ✅ media.upload

**Quran Module**:
- ✅ quran.view
- ✅ quran.update (translations only)

**Sahaba Module**:
- ✅ sahaba.view
- ✅ sahaba.create
- ✅ sahaba.update
- ✅ sahaba.delete

**Seerah Module**:
- ✅ seerah.view
- ✅ seerah.create
- ✅ seerah.update
- ✅ seerah.delete

**Genealogy Module**:
- ✅ genealogy.view
- ✅ genealogy.create
- ✅ genealogy.update
- ✅ genealogy.delete

**Users Module**:
- ✅ users.view
- ✅ users.create
- ✅ users.update
- ✅ users.delete
- ✅ users.assign-roles

**Roles Module**:
- ✅ roles.view
- ✅ roles.create
- ✅ roles.update
- ✅ roles.delete
- ✅ roles.assign-permissions

**Convenience Buttons**:
- **Select All in Module** - Check all permissions for a module
- **Deselect All in Module** - Uncheck all permissions for a module

3. Click **"حفظ" (Save)**

#### Editing Roles

1. Click **"تعديل" (Edit)** on role card
2. Update display name or description
3. Check/uncheck permissions
4. Click **"تحديث" (Update)**

⚠️ **Warning**: Cannot edit permissions for Super Admin role.

#### Deleting Roles

1. Click **"حذف" (Delete)** on role card
2. Confirm deletion
3. Users with this role will have it removed

⚠️ **Restrictions**:
- Cannot delete Super Admin role
- Cannot delete roles with assigned users (remove users first)

#### Viewing Permissions

Navigate to: **Users** → **Permissions**

**Grouped Display**:
- Permissions grouped by module
- Shows which roles have each permission
- Color-coded badges

---

## 📋 Activity Logs

Navigate to: **Activity Logs** → **View Logs**

### What Gets Logged

**Tracked Actions**:
- ✅ Create operations (all content types)
- ✅ Update operations (with old/new values)
- ✅ Delete operations
- ✅ Bulk operations
- ✅ Login/Logout events
- ✅ Role assignments
- ✅ Permission changes

**Captured Data**:
- User who performed action
- Action type
- Affected model (Article, Hadith, etc.)
- Model ID
- Old values (before update)
- New values (after update)
- IP address
- User agent (browser info)
- Timestamp

### Viewing Logs

**List View**:
- Log ID
- User name
- Action (badge colored)
- Model type
- Model ID
- IP address
- Date/time
- Actions (View Details)

### Filtering Logs

**Available Filters**:
- **Date Range**: From → To
- **User**: Select from dropdown
- **Action Type**:
  - Create
  - Update
  - Delete
  - Bulk Delete
  - Login
  - Logout
- **Model Type**:
  - Article
  - Hadith
  - Media Program
  - Seerah Event
  - Sahabi
  - User
  - Role

**Search**:
- Search by model ID
- Search by IP address

### Log Details

**View single log entry**:
1. Click **"عرض التفاصيل" (View Details)**
2. See detailed information:
   - Full user information
   - Complete action details
   - Old vs New values (diff view)
   - Browser and OS details
   - Related logs timeline

**Diff View**:
- Side-by-side comparison
- Highlighted changes
- JSON formatted for readability

### Exporting Logs

1. Apply desired filters
2. Click **"تصدير" (Export)** button
3. Choose format: CSV or Excel
4. Download file

**Uses for Logs**:
- 🔍 Audit trail for compliance
- 🐛 Troubleshooting errors
- 📊 User activity analysis
- 🔒 Security monitoring
- 📈 Content creation statistics

---

## ⚙️ Settings

### Changing Language

**Header Language Selector**:
1. Click language dropdown in header
2. Select from:
   - 🇸🇦 العربية (Arabic)
   - 🇬🇧 English
   - 🇫🇷 Français
   - 🇹🇷 Türkçe
   - 🇵🇰 اردو (Urdu)
3. Page reloads with selected language
4. Preference saved to your account

### Dark Mode Toggle

**Enable/Disable Dark Mode**:
1. Click moon/sun icon in header
2. Interface switches instantly
3. Preference saved to localStorage
4. Persists across sessions
5. Charts update automatically

**Light Mode**:
- White backgrounds
- Dark text
- Subtle shadows
- Clear borders

**Dark Mode**:
- Dark gray backgrounds
- White text
- Glass effects
- Subtle borders

### Profile Management

**Updating Your Profile**:
1. Click your name in header
2. Select **"الملف الشخصي" (Profile)**
3. Update information:
   - Name
   - Email
   - Language preference
4. Click **"حفظ" (Save)**

**Changing Password**:
1. Go to Profile page
2. Click **"تغيير كلمة المرور" (Change Password)**
3. Enter:
   - Current password
   - New password
   - Confirm new password
4. Click **"تحديث" (Update)**

### Notification Preferences

**Configure notifications** (if enabled):
1. Go to Profile → Notifications
2. Toggle on/off:
   - Email notifications
   - Browser notifications
   - Notification types (comments, mentions, etc.)
3. Save preferences

---

## 🔧 Troubleshooting

### Common Issues and Solutions

#### Issue: Login Page Not Loading

**Solution**:
1. Clear browser cache (Ctrl+Shift+Delete)
2. Disable browser extensions
3. Try incognito mode
4. Check internet connection
5. Try a different browser

#### Issue: "Invalid Credentials" Error

**Solutions**:
- ✅ Verify email is correct (case-sensitive)
- ✅ Check Caps Lock is off for password
- ✅ Use "Forgot Password" link
- ✅ Contact system administrator
- ✅ Verify account is not suspended

#### Issue: "Unauthorized Access" Error

**Causes & Solutions**:
- **Insufficient permissions**: Contact admin to assign proper role
- **Session expired**: Log out and log in again
- **Account suspended**: Contact system administrator

#### Issue: Dark Mode Not Working

**Solutions**:
1. Clear localStorage:
   ```javascript
   // In browser console:
   localStorage.clear()
   ```
2. Refresh page (Ctrl+R)
3. Toggle dark mode again
4. Try different browser

#### Issue: Images Not Uploading

**Check**:
- ✅ File size (max 10MB)
- ✅ File type (jpg, png, webp only)
- ✅ Internet connection stable
- ✅ Browser permissions for file access
- ✅ Storage quota not exceeded

**Solutions**:
- Compress image before uploading
- Convert to supported format
- Try smaller file
- Clear browser cache

#### Issue: Forms Not Submitting

**Solutions**:
1. Check all required fields are filled
2. Verify no validation errors
3. Check browser console for errors (F12)
4. Disable ad blockers
5. Try a different browser
6. Check internet connection

#### Issue: Dashboard Charts Not Displaying

**Solutions**:
1. Refresh page (Ctrl+R)
2. Clear browser cache
3. Disable ad blockers
4. Check browser JavaScript is enabled
5. Update browser to latest version

#### Issue: Search Not Working

**Check**:
- Wait for debounce (500ms delay)
- Try different search terms
- Clear search and try again
- Check filters not over-restricting results

#### Issue: Bulk Actions Not Working

**Solutions**:
1. Ensure items are selected (checkboxes)
2. Verify you have permission for the action
3. Check browser console for errors
4. Try with fewer items
5. Refresh page and try again

#### Issue: Text Appearing in Wrong Language

**Solutions**:
1. Check language selector in header
2. Clear browser cache
3. Verify your profile language setting
4. Log out and log in again

#### Issue: Page Loading Slowly

**Solutions**:
- ✅ Check internet connection speed
- ✅ Clear browser cache
- ✅ Close unnecessary tabs
- ✅ Disable browser extensions
- ✅ Contact admin about server performance

#### Issue: Mobile View Issues

**Solutions**:
1. Rotate device to landscape mode
2. Zoom out if content is cut off
3. Clear mobile browser cache
4. Try desktop mode if needed
5. Update mobile browser

---

## 📞 Getting Help

### Support Resources

**Documentation**:
- 📖 Admin Guide (this document)
- 👨‍💻 Developer Guide (for technical issues)
- 🚀 Deployment Guide (for hosting)
- 📚 API Documentation (for integrations)

**Contact Support**:
- 📧 Email: support@thelastmessenger.com
- 🌐 Website: https://thelastmessenger.com
- 💬 Support Chat: Available in admin panel

**Community**:
- 💬 Discussion Forum
- 📺 Video Tutorials (coming soon)
- 📝 FAQ Section

### Reporting Bugs

**Submit a bug report**:
1. Document the issue:
   - What were you trying to do?
   - What happened instead?
   - What did you expect to happen?
2. Include:
   - Browser name and version
   - Operating system
   - Screenshots if applicable
   - Steps to reproduce
3. Send to: bugs@thelastmessenger.com

### Feature Requests

**Request new features**:
1. Describe the feature in detail
2. Explain why it would be useful
3. Provide examples if possible
4. Submit via: features@thelastmessenger.com

---

## 🎓 Best Practices

### Content Creation

**Writing Guidelines**:
- ✅ Write in clear, simple language
- ✅ Verify facts with authentic sources
- ✅ Include proper citations
- ✅ Use consistent terminology
- ✅ Proofread before publishing

**Multi-Language Content**:
- ✅ Ensure all 5 languages are complete
- ✅ Use professional translators
- ✅ Maintain consistent tone across languages
- ✅ Consider cultural context
- ✅ Verify Arabic text for accuracy

**SEO Optimization**:
- ✅ Use descriptive titles
- ✅ Write compelling meta descriptions
- ✅ Include relevant keywords naturally
- ✅ Optimize images with alt text
- ✅ Use proper heading structure (H1, H2, H3)

### Media Management

**Image Guidelines**:
- 📐 Recommended sizes:
  - Featured Image: 1200x630px
  - Thumbnail: 500x500px
  - Banner: 1920x400px
- 📏 Aspect ratios:
  - Square: 1:1
  - Landscape: 16:9
  - Banner: 4.8:1
- 💾 Format: WebP (best), JPEG, or PNG
- 📦 Optimize before upload (max 500KB)

**Video Guidelines**:
- 🎥 Host on YouTube or Vimeo for best performance
- 📝 Always include captions/subtitles
- ⏱️ Keep episodes under 30 minutes when possible
- 🎬 Use consistent branding (intros/outros)

### Security Best Practices

**Account Security**:
- 🔒 Use strong, unique passwords
- 🔄 Change password every 90 days
- 👤 Never share account credentials
- 🚪 Always log out when finished
- 📱 Enable two-factor authentication (if available)

**Content Security**:
- ✅ Review content before publishing
- ✅ Verify sources are authentic
- ✅ Check for copyright issues
- ✅ Remove sensitive information
- ✅ Back up important content

---

## 📊 Keyboard Shortcuts

**Global Shortcuts**:
- `Ctrl + K` - Open search
- `Ctrl + /` - Toggle sidebar
- `Ctrl + S` - Save current form
- `Esc` - Close modal/cancel
- `Ctrl + Z` - Undo (in editors)

**Navigation**:
- `Alt + D` - Go to Dashboard
- `Alt + A` - Go to Articles
- `Alt + H` - Go to Hadith
- `Alt + M` - Go to Media

**Actions**:
- `Ctrl + N` - Create new (in list views)
- `Ctrl + E` - Edit selected item
- `Delete` - Delete selected item
- `Ctrl + A` - Select all

---

## 📱 Mobile App

**Progressive Web App (PWA)**:
- 📲 Install admin panel as app on mobile
- 🔔 Receive push notifications
- 📴 Limited offline functionality
- 🚀 Faster load times

**Installation**:
1. Open admin panel in mobile browser
2. Click "Add to Home Screen" in browser menu
3. Icon appears on home screen
4. Launch like native app

---

## 🎉 Tips & Tricks

### Productivity Tips

**Quick Navigation**:
- Bookmark frequently used pages
- Use browser tabs for multi-tasking
- Learn keyboard shortcuts
- Pin sidebar menu open

**Batch Processing**:
- Use bulk actions for multiple items
- Export to Excel for offline editing
- Import bulk data when possible
- Schedule content in advance (if feature available)

**Search Tips**:
- Use quotes for exact phrase: `"Prophet Muhammad"`
- Combine filters for precise results
- Save frequent searches
- Use advanced search for complex queries

**Form Tips**:
- Use browser autofill for repeated data
- Copy/paste between language tabs
- Save drafts frequently
- Preview before publishing

---

## 📈 Analytics & Reports

**Available Reports** (if feature enabled):
- 📊 Content performance
- 👥 User activity
- 📈 Growth trends
- 🔍 Search queries
- 💾 Storage usage

**Exporting Data**:
- CSV format for Excel
- Excel format for advanced analysis
- PDF format for reports

---

## 🔄 Updates & Changelog

**Stay Updated**:
- Check dashboard for update notifications
- Review changelog regularly
- Test new features in staging environment
- Report any issues after updates

**Current Version**: 1.0.0
**Release Date**: 2025-10-29

---

## ✅ Quick Reference

### Most Common Tasks

**Daily Tasks**:
1. Check dashboard for statistics
2. Review recent activity
3. Respond to comments (if enabled)
4. Moderate new content
5. Check for errors in logs

**Weekly Tasks**:
1. Review and publish scheduled content
2. Update popular articles
3. Check for broken links
4. Review user-reported issues
5. Backup important data

**Monthly Tasks**:
1. Review analytics and trends
2. Update outdated content
3. Audit user permissions
4. Clean up old drafts
5. Plan content for next month

---

## 🌟 Conclusion

This admin panel is designed to be intuitive and powerful. Take time to explore all features, and don't hesitate to reach out for support when needed.

**Remember**:
- 💾 Save your work frequently
- ✅ Preview before publishing
- 🔍 Double-check facts and sources
- 🌐 Complete all language versions
- 📊 Monitor your statistics
- 🔒 Keep your account secure

**Happy Managing! 🎉**

---

**Document Version**: 1.0.0
**Last Updated**: 2025-10-29
**Maintained By**: The Last Messenger Development Team
