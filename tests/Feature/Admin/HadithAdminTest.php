<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Domain\Hadith\Enums\HadithGrade;
use App\Domain\Hadith\Models\Hadith;
use App\Domain\Hadith\Models\HadithBook;
use App\Domain\Hadith\Models\HadithChapter;
use App\Domain\Hadith\Models\HadithCollection;
use App\Domain\Hadith\Models\HadithText;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class HadithAdminTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    public function test_admin_can_open_all_hadith_management_lists(): void
    {
        $this->actingAs($this->createAdminUser());

        $this->get(route('admin.hadith.index'))->assertOk();
        $this->get(route('admin.hadith-collections.index'))->assertOk();
        $this->get(route('admin.hadith-books.index'))->assertOk();
        $this->get(route('admin.hadith-chapters.index'))->assertOk();
    }

    public function test_collection_names_link_to_the_collection_page_from_management_lists(): void
    {
        $hadith = Hadith::factory()->create();
        $collectionUrl = route('admin.hadith-collections.show', $hadith->collection);
        $this->actingAs($this->createAdminUser());

        $this->get(route('admin.hadith.index'))->assertSee($collectionUrl, false);
        $this->get(route('admin.hadith-books.index'))->assertSee($collectionUrl, false);
        $this->get(route('admin.hadith-chapters.index'))->assertSee($collectionUrl, false);
    }

    public function test_admin_can_manage_collection_content_without_changing_imported_identity(): void
    {
        $collection = HadithCollection::factory()->create();
        $originalIdentity = $collection->only([
            'code',
            'has_books',
            'has_chapters',
            'has_volumes',
            'reference_template',
            'display_order',
        ]);
        $this->actingAs($this->createAdminUser());

        $this->get(route('admin.hadith-collections.show', $collection))->assertOk();

        $this->put(route('admin.hadith-collections.update', $collection), [
            'updated_at' => $collection->updated_at->toISOString(),
            'is_published' => '0',
            'featured' => '1',
            'name' => ['ar' => 'مجموعة محررة', 'en' => 'Edited Collection', 'fr' => null, 'tr' => null, 'ur' => null],
            'author' => ['ar' => 'مؤلف محرر', 'en' => 'Edited Author', 'fr' => null, 'tr' => null, 'ur' => null],
            'short_intro' => ['ar' => 'مقدمة مختصرة', 'en' => null, 'fr' => null, 'tr' => null, 'ur' => null],
            'about' => ['ar' => 'نبذة محررة', 'en' => null, 'fr' => null, 'tr' => null, 'ur' => null],
        ])->assertRedirect(route('admin.hadith-collections.show', $collection));

        $updated = $collection->fresh();
        $this->assertSame($originalIdentity, $updated->only(array_keys($originalIdentity)));
        $this->assertSame('Edited Collection', $updated->getTranslation('name', 'en'));
        $this->assertFalse($updated->is_published);
        $this->assertTrue($updated->featured);
    }

    public function test_admin_can_search_hadith_identity(): void
    {
        $hadith = Hadith::factory()->create(['hadith_number' => 'admin-needle']);
        $this->actingAs($this->createAdminUser());

        $this->get(route('admin.hadith.index', ['search' => 'admin-needle']))
            ->assertOk()
            ->assertSee("ID {$hadith->id}");
    }

    public function test_book_and_chapter_updates_preserve_imported_identity(): void
    {
        $chapter = HadithChapter::factory()->create();
        $book = $chapter->book;
        $this->actingAs($this->createAdminUser());

        $this->put(route('admin.hadith-books.update', $book), [
            'updated_at' => $book->updated_at->toISOString(),
            'is_published' => '0',
            'title' => ['ar' => 'كتاب محرر', 'en' => 'Edited Book'],
            'intro' => ['ar' => 'مقدمة'],
        ])->assertRedirect(route('admin.hadith-books.edit', $book));

        $this->put(route('admin.hadith-chapters.update', $chapter), [
            'updated_at' => $chapter->fresh()->updated_at->toISOString(),
            'is_published' => '0',
            'title' => ['ar' => 'باب محرر', 'en' => 'Edited Chapter'],
            'intro' => ['ar' => 'تمهيد'],
            'ending' => ['ar' => 'خاتمة'],
        ])->assertRedirect(route('admin.hadith-chapters.edit', $chapter));

        $this->assertSame($book->book_number, $book->fresh()->book_number);
        $this->assertSame($book->sort_order, $book->fresh()->sort_order);
        $this->assertSame('Edited Book', $book->fresh()->getTranslation('title', 'en'));
        $this->assertFalse($book->fresh()->is_published);
        $this->assertSame($chapter->chapter_number, $chapter->fresh()->chapter_number);
        $this->assertSame($chapter->sort_order, $chapter->fresh()->sort_order);
        $this->assertSame('Edited Chapter', $chapter->fresh()->getTranslation('title', 'en'));
        $this->assertFalse($chapter->fresh()->is_published);
    }

    public function test_hadith_update_normalizes_search_text_and_recomputes_grade(): void
    {
        $hadith = Hadith::factory()->create(['display_grade' => HadithGrade::UNKNOWN]);
        HadithText::factory()->for($hadith)->arabic()->create();
        $this->actingAs($this->createAdminUser());

        $this->put(route('admin.hadith.update', $hadith), [
            'updated_at' => $hadith->updated_at->toISOString(),
            'is_published' => '1',
            'texts' => [
                'ar' => ['body' => 'إِنَّـمَا الأَعْمَالُ بِالنِّيَّاتِ'],
                'en' => ['body' => 'Actions are judged by intentions'],
            ],
            'gradings' => [[
                'grade' => 'sahih',
                'scope' => 'hadith',
                'authority_rank' => 10,
                'raw_grade' => 'Sahih',
                'locale' => 'en',
            ]],
        ])->assertRedirect(route('admin.hadith.edit', $hadith));

        $this->assertDatabaseHas('hadith_texts', [
            'hadith_id' => $hadith->id,
            'locale' => 'ar',
            'search_body' => 'انما الاعمال بالنيات',
        ]);
        $this->assertSame(HadithGrade::SAHIH, $hadith->fresh()->display_grade);
        $this->assertDatabaseHas('hadith_gradings', [
            'hadith_id' => $hadith->id,
            'grade' => 'sahih',
            'scope' => 'hadith',
        ]);
    }

    public function test_stale_edit_is_rejected_without_overwriting_the_record(): void
    {
        $book = HadithBook::factory()->create();
        $this->actingAs($this->createAdminUser());

        $this->from(route('admin.hadith-books.edit', $book))
            ->put(route('admin.hadith-books.update', $book), [
                'updated_at' => now()->subDay()->toISOString(),
                'is_published' => '0',
                'title' => ['ar' => 'لن يحفظ'],
                'intro' => ['ar' => null],
            ])
            ->assertRedirect(route('admin.hadith-books.edit', $book))
            ->assertSessionHasErrors('updated_at');

        $this->assertTrue($book->fresh()->is_published);
    }
}
