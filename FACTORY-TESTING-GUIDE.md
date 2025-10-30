# Factory Testing Guide

## Overview
This project includes comprehensive model factories for all 7 major domain models. All factories support multi-language JSON fields for Arabic (ar), English (en), French (fr), Turkish (tr), and Urdu (ur).

## Created Factories

### 1. ArticleFactory
**Location:** `database/factories/ArticleFactory.php`
**Model:** `App\Domain\Blog\Models\Article`

**Basic Usage:**
```php
use Database\Factories\ArticleFactory;

// Create published article
$article = ArticleFactory::new()->published()->create();

// Create draft article
$article = ArticleFactory::new()->draft()->create();

// Create featured article
$article = ArticleFactory::new()->featured()->create();

// Create in specific category
$article = ArticleFactory::new()->inCategory(5)->create();
```

**Available States:**
- `published()` - Published with published_at date
- `draft()` - Draft status, no published_at
- `archived()` - Archived status
- `featured()` - With featured image and high views
- `popular()` - High views and favorites counts
- `inCategory($id)` - Assign to specific category
- `inSeries($id)` - Assign to specific series

---

### 2. HadithFactory
**Location:** `database/factories/HadithFactory.php`
**Model:** `App\Domain\Hadith\Models\Hadith`

**Basic Usage:**
```php
use Database\Factories\HadithFactory;

// Create sahih hadith
$hadith = HadithFactory::new()->sahih()->create();

// Create from specific book
$hadith = HadithFactory::new()->fromBook(1)->create();

// Create popular hadith
$hadith = HadithFactory::new()->popular()->create();
```

**Available States:**
- `sahih()` - Authentic grade
- `hasan()` - Good grade
- `daif()` - Weak grade
- `fromBook($id)` - From specific book (1-9)
- `popular()` - High view count

---

### 3. MediaProgramFactory
**Location:** `database/factories/MediaProgramFactory.php`
**Model:** `App\Domain\Media\Models\MediaProgram`

**Basic Usage:**
```php
use Database\Factories\MediaProgramFactory;

// Create ongoing program
$program = MediaProgramFactory::new()->ongoing()->create();

// Create with episodes
$program = MediaProgramFactory::new()
    ->completed()
    ->withManyEpisodes()
    ->create();
```

**Available States:**
- `ongoing()` - Ongoing status
- `completed()` - Completed status with many episodes
- `paused()` - Paused status
- `popular()` - High views
- `inCategory($id)` - Specific category
- `withManyEpisodes()` - 50-200 episodes

---

### 4. MediaEpisodeFactory
**Location:** `database/factories/MediaEpisodeFactory.php`
**Model:** `App\Domain\Media\Models\MediaEpisode`

**Basic Usage:**
```php
use Database\Factories\MediaEpisodeFactory;

// Create episode for program
$episode = MediaEpisodeFactory::new()
    ->forProgram(1)
    ->number(1)
    ->create();

// Create with Vimeo video
$episode = MediaEpisodeFactory::new()->vimeo()->create();
```

**Available States:**
- `unpublished()` - No published_at date
- `recent()` - Published in last month
- `forProgram($id)` - Assign to program
- `number($num)` - Specific episode number
- `popular()` - High views
- `long()` - 60-180 minutes
- `short()` - 5-15 minutes
- `vimeo()` - Use Vimeo URL instead of YouTube

---

### 5. SahabiFactory
**Location:** `database/factories/SahabiFactory.php`
**Model:** `App\Domain\Sahaba\Models\Sahabi`

**Basic Usage:**
```php
use Database\Factories\SahabiFactory;

// Create male companion
$sahabi = SahabiFactory::new()->create();

// Create female companion
$sahabi = SahabiFactory::new()->female()->create();

// Create ten promised paradise
$sahabi = SahabiFactory::new()->tenPromisedParadise()->create();
```

**Available States:**
- `female()` - Female companion
- `tenPromisedParadise()` - One of the 10 promised paradise
- `badrParticipant()` - Participated in Battle of Badr
- `featured()` - Featured companion
- `topNarrator()` - 3000-7000 hadith

---

### 6. SeerahEventFactory
**Location:** `database/factories/SeerahEventFactory.php`
**Model:** `App\Domain\Seerah\Models\SeerahEvent`

**Basic Usage:**
```php
use Database\Factories\SeerahEventFactory;

// Create battle event
$event = SeerahEventFactory::new()->battle()->create();

// Create in Mecca
$event = SeerahEventFactory::new()->inMecca()->create();

// Create featured migration event
$event = SeerahEventFactory::new()
    ->migration()
    ->featured()
    ->create();
```

**Available States:**
- `battle()` - Battle event
- `migration()` - Migration event
- `revelation()` - Revelation event
- `miracle()` - Miracle event
- `featured()` - Featured with high views
- `withLocation()` - With lat/long coordinates
- `inMecca()` - Located in Mecca
- `inMedina()` - Located in Medina

---

### 7. PersonFactory (Genealogy)
**Location:** `database/factories/PersonFactory.php`
**Model:** `App\Domain\Genealogy\Models\Person`

**Basic Usage:**
```php
use Database\Factories\PersonFactory;

// Create male person
$person = PersonFactory::new()->male()->create();

// Create prophet
$person = PersonFactory::new()->prophet()->create();

// Create with relationships
$father = PersonFactory::new()->create();
$person = PersonFactory::new()
    ->withFather($father->id)
    ->create();
```

**Available States:**
- `prophet()` - Prophet person type
- `male()` - Male gender
- `female()` - Female gender with female names
- `companion()` - Companion type
- `tribeLeader()` - Tribe leader type
- `withFather($id)` - Set father_id
- `withMother($id)` - Set mother_id
- `verified()` - Verified person

---

## Mass Creation Examples

### Create Test Dataset
```php
// Articles
Article::factory()->count(50)->published()->create();
Article::factory()->count(20)->draft()->create();

// Hadith
Hadith::factory()->count(100)->sahih()->create();
Hadith::factory()->count(50)->hasan()->create();

// Media Programs with Episodes
MediaProgram::factory()
    ->count(10)
    ->has(MediaEpisode::factory()->count(20), 'episodes')
    ->create();

// Companions
Sahabi::factory()->count(30)->create();
Sahabi::factory()->count(10)->female()->create();
Sahabi::factory()->tenPromisedParadise()->create();

// Timeline Events
SeerahEvent::factory()->count(50)->create();
SeerahEvent::factory()->count(10)->battle()->create();

// Genealogy
Person::factory()->count(100)->create();
```

## Important Notes

### Multi-Language Fields
All factories generate realistic multi-language data for all 5 supported languages:
- Arabic (ar)
- English (en)
- French (fr)
- Turkish (tr)
- Urdu (ur)

### Models with Spatie HasTranslations
The following models use Spatie's HasTranslations trait:
- MediaProgram
- MediaEpisode
- Sahabi
- SeerahEvent
- Person

When using `make()` on these models, you may encounter type issues due to how the trait handles attribute setting. **Always use `create()` for these models in tests and seeders.**

### Realistic Data
Factories generate realistic data including:
- Famous companion names (Sahabi)
- Historical Islamic events (SeerahEvent)
- Authentic hadith references
- YouTube video URLs (MediaEpisode)
- Arabic text and Islamic terminology

### Relationships
Some factories have default foreign key values:
- `ArticleFactory`: user_id = 1, category_id = random(1-10)
- `HadithFactory`: book_id = random(1-9), chapter_id = random(1-50)
- `MediaEpisodeFactory`: program_id = 1 (override with `forProgram($id)`)
- `SahabiFactory`: Can link to Person via father_id/mother_id

Always ensure referenced records exist before creating dependent records.

## Testing with Factories

### Feature Test Example
```php
use Database\Factories\ArticleFactory;

class ArticleTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_view_published_article()
    {
        $article = ArticleFactory::new()->published()->create();

        $response = $this->get("/api/articles/{$article->slug}");

        $response->assertStatus(200);
        $response->assertJson([
            'data' => [
                'slug' => $article->slug,
                'status' => 'published',
            ]
        ]);
    }
}
```

### Seeder Example
```php
use Database\Factories\ArticleFactory;
use Database\Factories\HadithFactory;

class DatabaseSeeder extends Seeder
{
    public function run()
    {
        // Create admin user
        User::factory()->create([
            'email' => 'admin@example.com',
        ]);

        // Create content
        Article::factory()->count(100)->published()->create();
        Hadith::factory()->count(500)->create();
        MediaProgram::factory()->count(20)->create();
    }
}
```

## Troubleshooting

### "Class not found" Error
Run: `composer dump-autoload`

### "Foreign key constraint fails"
Ensure parent records exist:
```php
// Wrong: category_id 10 might not exist
Article::factory()->create();

// Right: Create or use existing category
$category = Category::factory()->create();
Article::factory()->create(['category_id' => $category->id]);
```

### "Cannot access offset of type string on string"
This occurs with `make()` on HasTranslations models. Use `create()` instead:
```php
// May cause issues
$program = MediaProgram::factory()->make();

// Always works
$program = MediaProgram::factory()->create();
```

## Summary

All 7 factories are production-ready and include:
- ✅ Multi-language support (5 languages)
- ✅ Realistic Islamic/Arabic content
- ✅ Multiple states for different scenarios
- ✅ Proper enum handling
- ✅ Relationship support
- ✅ Comprehensive PHPDoc documentation
- ✅ Strict type declarations
