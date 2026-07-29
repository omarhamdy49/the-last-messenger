<?php

declare(strict_types=1);

namespace Tests\Feature\Admin\Hadith;

use App\Domain\Hadith\Models\HadithCollection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Guards the admin Hadith Collection create/update flow.
 *
 * Regression: the forms used to post `name[ar]` / `slug` while the FormRequest
 * validated `name_ar` / `slug_ar`, so every submission 422'd on `name_ar`. These
 * tests lock the form ⇄ request ⇄ controller contract together.
 */
class CollectionCrudTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    /** A valid create payload matching the FormRequest contract. */
    private function validPayload(array $overrides = []): array
    {
        return array_merge([
            'name_ar' => 'صحيح البخاري',
            'name_en' => 'Sahih al-Bukhari',
            'slug_ar' => 'sahih-bukhari',
            'slug_en' => 'sahih-al-bukhari',
            'description_ar' => 'أصح كتب الحديث.',
            'description_en' => 'The most authentic hadith book.',
            'abbreviation' => 'خ',
            'total_hadiths' => 7277,
            'featured' => '1',
            'active' => '1',
            'display_order' => 3,
        ], $overrides);
    }

    public function test_create_form_renders(): void
    {
        $admin = $this->createAdminUser();

        $this->actingAs($admin)
            ->get(route('admin.hadith.collections.create'))
            ->assertOk();
    }

    public function test_admin_can_create_collection(): void
    {
        $admin = $this->createAdminUser();

        $response = $this->actingAs($admin)
            ->post(route('admin.hadith.collections.store'), $this->validPayload());

        $response->assertRedirect(route('admin.hadith.collections.index'));
        $response->assertSessionHasNoErrors();

        $this->assertDatabaseHas('hadith_collections', [
            'slug' => 'sahih-al-bukhari',
            'featured' => true,
            'display_order' => 3,
        ]);
        $collection = HadithCollection::query()->where('slug', 'sahih-al-bukhari')->firstOrFail();
        $this->assertSame('صحيح البخاري', $collection->name['ar']);
        $this->assertSame('Sahih al-Bukhari', $collection->name['en']);
    }

    public function test_create_collection_fails_validation_without_name_ar(): void
    {
        $admin = $this->createAdminUser();

        $response = $this->actingAs($admin)
            ->postJson(route('admin.hadith.collections.store'), $this->validPayload(['name_ar' => '']));

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['name_ar']);

        $this->assertDatabaseMissing('hadith_collections', ['slug' => 'sahih-bukhari']);
    }

    public function test_create_collection_fails_validation_without_slug_ar(): void
    {
        $admin = $this->createAdminUser();

        $response = $this->actingAs($admin)
            ->postJson(route('admin.hadith.collections.store'), $this->validPayload(['slug_ar' => '']));

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['slug_ar']);
    }

    public function test_edit_form_renders(): void
    {
        $admin = $this->createAdminUser();
        $collection = $this->seedCollection();

        $this->actingAs($admin)
            ->get(route('admin.hadith.collections.edit', $collection->id))
            ->assertOk();
    }

    public function test_admin_can_update_collection(): void
    {
        $admin = $this->createAdminUser();
        $collection = $this->seedCollection();

        $response = $this->actingAs($admin)
            ->put(route('admin.hadith.collections.update', $collection->id), [
                'name_ar' => 'اسم محدّث',
                'name_en' => 'Updated name',
                'slug_ar' => $collection->slug_ar, // unchanged → unique rule ignores self
                'slug_en' => $collection->slug_en,
                'description_ar' => 'وصف محدّث',
                'abbreviation' => 'م',
                'total_hadiths' => 100,
                'featured' => '1',
                'active' => '1',
                'display_order' => 9,
            ]);

        $response->assertRedirect(route('admin.hadith.collections.index'));
        $response->assertSessionHasNoErrors();

        $collection->refresh();
        $this->assertSame('اسم محدّث', $collection->name['ar']);
        $this->assertSame('Updated name', $collection->name['en']);
        $this->assertTrue($collection->featured);
        $this->assertSame(9, $collection->display_order);
    }

    public function test_update_collection_fails_validation_without_name_ar(): void
    {
        $admin = $this->createAdminUser();
        $collection = $this->seedCollection();

        $response = $this->actingAs($admin)
            ->putJson(route('admin.hadith.collections.update', $collection->id), [
                'name_ar' => '',
                'slug_ar' => $collection->slug_ar,
            ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['name_ar']);

        $this->assertSame('سنن أبي داود', $collection->fresh()->name['ar']);
    }

    private function seedCollection(): HadithCollection
    {
        return HadithCollection::create([
            'code' => 'sunan-abi-dawud',
            'name' => ['ar' => 'سنن أبي داود', 'en' => 'Sunan Abi Dawud'],
            'slug' => 'sunan-abi-dawud',
            'description' => ['ar' => 'من السنن الأربعة.'],
            'abbreviation' => 'د',
            'featured' => false,
            'active' => true,
            'display_order' => 0,
        ]);
    }
}
