<?php

declare(strict_types=1);

namespace Tests\Feature\Quran;

use App\Domain\Quran\Models\TafsirResource;
use App\Domain\Quran\Models\TranslationResource;
use App\Domain\Shared\Models\AdminLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Admin screens for Quran edition/source metadata, plus the surah detail page.
 *
 * These controllers had no coverage while they held their own Eloquent calls;
 * the assertions here pin the behaviour that survived moving those reads and
 * writes behind services — the one-default-per-language rule, checkbox flags
 * persisting as false, self-slug uniqueness on edit, and an audit-log row for
 * every mutation.
 */
class QuranResourceAdminTest extends TestCase
{
    use RefreshDatabase;

    private function seedSurah(): void
    {
        DB::table('quran_surahs')->insert([
            'id' => 1, 'name_ar' => 'الفاتحة', 'name_en' => 'Al-Fatihah', 'transliteration' => 'Al-Fatihah',
            'revelation_place' => 'makkah', 'revelation_order' => 5, 'bismillah_pre' => 0,
            'verses_count' => 3, 'pages' => json_encode([1, 1]),
        ]);

        foreach ([3, 1, 2] as $i => $n) {
            DB::table('quran_verses')->insert([
                'id' => $i + 1, 'surah_id' => 1, 'verse_number' => $n, 'verse_key' => "1:$n",
                'juz_number' => 1, 'hizb_number' => 1, 'rub_el_hizb_number' => 1, 'ruku_number' => 1,
                'manzil_number' => 1, 'sajdah_number' => null, 'page_number' => 1,
            ]);
            DB::table('quran_verse_translations')->insert([
                'verse_id' => $i + 1, 'resource_id' => null, 'lang' => 'ar', 'text' => "آية-$n",
            ]);
            DB::table('quran_verse_tafsirs')->insert([
                'verse_id' => $i + 1, 'resource_id' => null, 'verse_key' => "1:$n",
                'lang' => 'ar', 'text' => "تفسير-$n", 'active' => 1,
            ]);
        }
    }

    public function test_surah_show_renders_verses_and_tafseer(): void
    {
        $this->seedSurah();

        $this->actingAs($this->createAdminUser())
            ->get(route('admin.surahs.show', 1))
            ->assertOk()
            ->assertSee('آية-1', false)
            ->assertSee('آية-3', false)
            ->assertSee('تفسير-2', false);
    }

    public function test_translation_resource_crud_logs_and_enforces_one_default(): void
    {
        $admin = $this->createAdminUser();

        $existing = TranslationResource::create([
            'slug' => 'existing-en', 'name' => ['ar' => 'قائم', 'en' => 'Existing'],
            'language' => 'en', 'direction' => 'ltr', 'is_default' => true, 'is_active' => true,
            'display_order' => 0,
        ]);

        $this->actingAs($admin)->get(route('admin.translation-resources.index'))->assertOk();
        $this->actingAs($admin)->get(route('admin.translation-resources.create'))->assertOk();

        $this->actingAs($admin)
            ->post(route('admin.translation-resources.store'), [
                'name_ar' => 'جديد', 'name_en' => 'New', 'slug' => 'new-en',
                'language' => 'en', 'direction' => 'ltr', 'is_default' => '1', 'is_active' => '1',
            ])
            ->assertRedirect(route('admin.translation-resources.index'));

        $created = TranslationResource::where('slug', 'new-en')->firstOrFail();
        $this->assertTrue($created->is_default);
        $this->assertFalse($existing->fresh()->is_default, 'the previous default must be demoted');
        $this->assertSame(['ar' => 'جديد', 'en' => 'New'], $created->name);
        $this->assertDatabaseHas('admin_logs', [
            'action' => 'created', 'model_type' => TranslationResource::class, 'model_id' => $created->id,
        ]);

        // Editing keeps its own slug (the unique rule must ignore this row).
        $this->actingAs($admin)->get(route('admin.translation-resources.edit', $created->id))->assertOk();
        $this->actingAs($admin)
            ->put(route('admin.translation-resources.update', $created->id), [
                'name_ar' => 'محدث', 'slug' => 'new-en',
                'language' => 'en', 'direction' => 'ltr', 'display_order' => '5',
            ])
            ->assertRedirect(route('admin.translation-resources.index'))
            ->assertSessionHasNoErrors();

        $created->refresh();
        $this->assertSame(5, $created->display_order);
        $this->assertFalse($created->is_default, 'an unchecked checkbox must persist as false');
        $this->assertSame(['ar' => 'محدث', 'en' => 'محدث'], $created->name);
        $this->assertDatabaseHas('admin_logs', [
            'action' => 'updated', 'model_type' => TranslationResource::class, 'model_id' => $created->id,
        ]);

        // A slug already taken by another row is still rejected.
        $this->actingAs($admin)
            ->put(route('admin.translation-resources.update', $created->id), [
                'name_ar' => 'x', 'slug' => 'existing-en', 'language' => 'en', 'direction' => 'ltr',
            ])
            ->assertSessionHasErrors('slug');

        $this->actingAs($admin)
            ->delete(route('admin.translation-resources.destroy', $created->id))
            ->assertRedirect(route('admin.translation-resources.index'));

        $this->assertDatabaseMissing('quran_translation_resources', ['id' => $created->id]);
        $this->assertDatabaseHas('admin_logs', [
            'action' => 'deleted', 'model_type' => TranslationResource::class, 'model_id' => $created->id,
        ]);
    }

    public function test_tafsir_resource_crud_logs(): void
    {
        $admin = $this->createAdminUser();

        $this->actingAs($admin)->get(route('admin.tafsir-resources.index'))->assertOk();
        $this->actingAs($admin)->get(route('admin.tafsir-resources.create'))->assertOk();

        $this->actingAs($admin)
            ->post(route('admin.tafsir-resources.store'), [
                'name_ar' => 'الميسر', 'slug' => 'muyassar', 'language' => 'ar', 'is_active' => '1',
            ])
            ->assertRedirect(route('admin.tafsir-resources.index'));

        $created = TafsirResource::where('slug', 'muyassar')->firstOrFail();
        $this->assertTrue($created->is_active);
        $this->assertDatabaseHas('admin_logs', [
            'action' => 'created', 'model_type' => TafsirResource::class, 'model_id' => $created->id,
        ]);

        $this->actingAs($admin)->get(route('admin.tafsir-resources.edit', $created->id))->assertOk();
        $this->actingAs($admin)
            ->put(route('admin.tafsir-resources.update', $created->id), [
                'name_ar' => 'الميسر٢', 'slug' => 'muyassar', 'language' => 'ar',
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('admin.tafsir-resources.index'));

        $this->assertFalse($created->fresh()->is_active);
        $this->assertDatabaseHas('admin_logs', [
            'action' => 'updated', 'model_type' => TafsirResource::class, 'model_id' => $created->id,
        ]);

        $this->actingAs($admin)
            ->delete(route('admin.tafsir-resources.destroy', $created->id))
            ->assertRedirect(route('admin.tafsir-resources.index'));

        $this->assertDatabaseMissing('quran_tafsir_resources', ['id' => $created->id]);
        $this->assertDatabaseHas('admin_logs', [
            'action' => 'deleted', 'model_type' => TafsirResource::class, 'model_id' => $created->id,
        ]);
    }

    public function test_invalid_payload_writes_nothing(): void
    {
        $this->actingAs($this->createAdminUser())
            ->post(route('admin.translation-resources.store'), ['slug' => 'x'])
            ->assertSessionHasErrors(['name_ar', 'language', 'direction']);

        $this->assertSame(0, TranslationResource::query()->count());
        $this->assertSame(0, AdminLog::query()->count());
    }
}
