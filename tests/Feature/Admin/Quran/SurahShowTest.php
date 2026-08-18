<?php

declare(strict_types=1);

namespace Tests\Feature\Admin\Quran;

use App\Domain\Quran\Models\Surah;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Admin surah detail page.
 *
 * show() built a $stats array whose words_count and letters_count are not columns
 * on quran_surahs, and Surah declares no accessor for either — both always resolved
 * to null, `?? 0` swallowed it, and the sidebar card rendered 0 for both from the
 * day it shipped. Same defect class as the verse stats and the four fixed under T0.1.
 *
 * Both were dropped (controller keys + view rows) rather than derived from
 * quran_words: nothing needs the numbers, and no columns were added on spec.
 */
class SurahShowTest extends TestCase
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

        // No Surah factory exists; this matches the fixture shape used by
        // tests/Feature/Admin/Quran/VerseCrudTest.php.
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

    public function test_show_renders_only_the_verses_count_stat(): void
    {
        $response = $this->actingAs($this->admin, 'admin')
            ->get(route('admin.surahs.show', $this->surah->id))
            ->assertOk();

        $response->assertViewHas('stats', fn (array $stats) => $stats === ['verses_count' => 7]);
    }

    /**
     * The view defaulted both phantom stats with `?? 0` of its own, so removing them
     * from the controller alone would still have rendered two 0 rows. Assert on the
     * markup, not just the view data.
     */
    public function test_show_no_longer_renders_the_phantom_word_and_letter_rows(): void
    {
        $response = $this->actingAs($this->admin, 'admin')
            ->get(route('admin.surahs.show', $this->surah->id))
            ->assertOk();

        // The stats card still exists and its one real row still works.
        $response->assertSee('عدد الآيات', false);
        $response->assertSee('7', false);

        $response->assertDontSee('عدد الكلمات', false);
        $response->assertDontSee('عدد الحروف', false);
    }
}
