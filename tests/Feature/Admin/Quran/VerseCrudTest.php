<?php

declare(strict_types=1);

namespace Tests\Feature\Admin\Quran;

use App\Domain\Quran\Models\Surah;
use App\Domain\Quran\Models\Verse;
use App\Http\Requests\Admin\StoreVerseRequest;
use App\Http\Requests\Admin\UpdateVerseRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Admin verse CRUD.
 *
 * These writes were completely dead: both StoreVerseRequest and UpdateVerseRequest
 * called SajdaType::values() inside rules(), and that enum does not exist — so every
 * submission fatalled with a class-not-found error before validation even ran. There
 * were no tests on this controller at all, which is why it shipped.
 *
 * The store/update cases below fail with the pre-fix code and pass after it.
 */
class VerseCrudTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private Surah $surah;

    protected function setUp(): void
    {
        parent::setUp();

        Role::findOrCreate('super_admin', 'web');

        $this->admin = User::factory()->create();
        $this->admin->assignRole('super_admin');

        // No Surah/Verse factories exist; this matches the fixture shape used by
        // tests/Feature/Repositories/VerseRepositoryTest.php.
        $this->surah = Surah::create([
            'revelation_place' => 'makkah',
            'revelation_order' => 5,
            'bismillah_pre' => true,
            'name_ar' => 'سورة الاختبار',
            'name_en' => 'Test Surah',
            'transliteration' => 'Test-Surah',
            'verses_count' => 7,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function validPayload(array $overrides = []): array
    {
        // Only real `quran_verses` columns. hizb_number, rub_el_hizb_number and
        // ruku_number are NOT NULL in the schema, so all three are required.
        return array_merge([
            'surah_id' => $this->surah->id,
            'verse_number' => 1,
            'juz_number' => 1,
            'hizb_number' => 1,
            'rub_el_hizb_number' => 1,
            'ruku_number' => 1,
            'page_number' => 1,
        ], $overrides);
    }

    private function makeVerse(): Verse
    {
        return Verse::create([
            'surah_id' => $this->surah->id,
            'verse_number' => 1,
            'verse_key' => "{$this->surah->id}:1",
            'juz_number' => 1,
            'hizb_number' => 1,
            'rub_el_hizb_number' => 1,
            'ruku_number' => 1,
            'manzil_number' => 1,
            'page_number' => 1,
        ]);
    }

    public function test_admin_can_create_a_verse(): void
    {
        $response = $this->actingAs($this->admin, 'admin')
            ->post(route('admin.verses.store'), $this->validPayload());

        $response->assertRedirect();
        $response->assertSessionHasNoErrors();
        // The controller catches Throwable into a flash message, so a hard failure
        // still looks like a clean redirect. Assert on the flash too.
        $this->assertNull(session('error'), 'store() flashed: '.session('error'));

        $this->assertDatabaseHas('quran_verses', [
            'surah_id' => $this->surah->id,
            'verse_number' => 1,
        ]);
    }

    public function test_admin_can_update_a_verse(): void
    {
        $verse = $this->makeVerse();

        $response = $this->actingAs($this->admin, 'admin')
            ->put(route('admin.verses.update', $verse->id), $this->validPayload([
                'verse_number' => 2,
            ]));

        $response->assertRedirect();
        $response->assertSessionHasNoErrors();

        $this->assertDatabaseHas('quran_verses', [
            'id' => $verse->id,
            'verse_number' => 2,
        ]);
    }

    /**
     * sajdah_number is what the create/edit forms actually submit (min 1, max 15).
     * It previously had no validation rule at all, so `$request->validated()` dropped
     * it and the value was silently discarded on every save.
     */
    public function test_sajdah_number_is_persisted(): void
    {
        $this->actingAs($this->admin, 'admin')
            ->post(route('admin.verses.store'), $this->validPayload(['sajdah_number' => 7]))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('quran_verses', [
            'surah_id' => $this->surah->id,
            'verse_number' => 1,
            'sajdah_number' => 7,
        ]);
    }

    public function test_sajdah_number_is_optional_and_stored_as_null(): void
    {
        $this->actingAs($this->admin, 'admin')
            ->post(route('admin.verses.store'), $this->validPayload(['sajdah_number' => null]))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('quran_verses', [
            'surah_id' => $this->surah->id,
            'verse_number' => 1,
            'sajdah_number' => null,
        ]);
    }

    /**
     * There are 15 sajdah verses in the Quran; anything outside 1..15 is a data error.
     */
    public function test_sajdah_number_is_rejected_outside_one_to_fifteen(): void
    {
        $this->actingAs($this->admin, 'admin')
            ->post(route('admin.verses.store'), $this->validPayload(['sajdah_number' => 16]))
            ->assertSessionHasErrors('sajdah_number');

        $this->assertDatabaseCount('quran_verses', 0);
    }

    /**
     * show() used to build a $stats array the view never read, whose words_count and
     * letters_count are not columns on quran_verses and have no accessor — both always
     * resolved to null and `?? 0` hid it. Dropped rather than derived: nothing renders
     * them. Assert the page still works and that no $stats is passed, so a reader does
     * not "restore" the phantom attributes.
     */
    public function test_show_renders_without_a_stats_payload(): void
    {
        $verse = $this->makeVerse();

        $this->actingAs($this->admin, 'admin')
            ->get(route('admin.verses.show', $verse->id))
            ->assertOk()
            ->assertViewHas('verse')
            ->assertViewMissing('stats');
    }

    /**
     * Guards against the drift that broke this feature: the request required fields the
     * form never submitted (text_uthmani, translations.*) while the form submitted a
     * NOT NULL column the request never validated (rub_el_hizb_number). Assert the form
     * actually carries an input for every field the request marks required.
     */
    public function test_create_form_submits_every_required_field(): void
    {
        $html = $this->actingAs($this->admin, 'admin')
            ->get(route('admin.verses.create'))
            ->assertOk()
            ->getContent();

        $required = array_keys(array_filter(
            (new StoreVerseRequest)->rules(),
            fn ($rules) => in_array('required', (array) $rules, true),
        ));

        foreach ($required as $field) {
            $this->assertStringContainsString(
                'name="'.$field.'"',
                $html,
                "The create form has no input for required field [{$field}]."
            );
        }
    }

    public function test_edit_form_submits_every_required_field(): void
    {
        $verse = $this->makeVerse();

        $html = $this->actingAs($this->admin, 'admin')
            ->get(route('admin.verses.edit', $verse->id))
            ->assertOk()
            ->getContent();

        $required = array_keys(array_filter(
            (new UpdateVerseRequest)->rules(),
            fn ($rules) => in_array('required', (array) $rules, true),
        ));

        foreach ($required as $field) {
            $this->assertStringContainsString(
                'name="'.$field.'"',
                $html,
                "The edit form has no input for required field [{$field}]."
            );
        }
    }
}
