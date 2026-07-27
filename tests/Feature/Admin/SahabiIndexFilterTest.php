<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Domain\Sahaba\Models\Sahabi;
use App\Domain\Sahaba\Models\SahabiCategory;
use Illuminate\Foundation\Testing\DatabaseTruncation;
use Tests\TestCase;

/**
 * The admin data table's search + advanced filters (gender, category, tribe,
 * featured, verified) all run through SahabiRepository::getFilteredPaginated().
 * These guard that each filter actually narrows the result set and that the
 * listing stays locale-aware-name-ordered underneath them.
 */
class SahabiIndexFilterTest extends TestCase
{
    use DatabaseTruncation;

    protected array $tablesToTruncate = [
        'model_has_permissions',
        'model_has_roles',
        'role_has_permissions',
        'permissions',
        'roles',
        'users',
        'sahabi_category',
        'sahabi_translations',
        'sahaba',
    ];

    protected function tearDown(): void
    {
        $this->truncateTablesForAllConnections();

        parent::tearDown();
    }

    private function makeSahabi(string $slug, array $translations, array $attributes = []): Sahabi
    {
        $sahabi = Sahabi::create(array_merge([
            'slug' => $slug,
            'gender' => 'male',
            'is_sahabi' => true,
            'person_type' => 'sahabi',
        ], $attributes));

        $sahabi->syncTranslations(['name' => $translations]);

        return $sahabi;
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $response = $this->get(route('admin.sahaba.index'));

        $response->assertRedirect(route('login'));
    }

    public function test_index_renders_companions_ordered_by_name_asc(): void
    {
        $this->actingAs($this->createAdminUser());

        $this->makeSahabi('zayd', ['ar' => 'زيد', 'en' => 'Zayd']);
        $this->makeSahabi('abu-bakr', ['ar' => 'أبو بكر', 'en' => 'Abu Bakr']);

        $response = $this->get(route('admin.sahaba.index'));

        $response->assertOk();
        $response->assertViewHas('sahaba');

        $names = $response->viewData('sahaba')->pluck('slug')->all();
        $this->assertSame(['abu-bakr', 'zayd'], $names);
    }

    public function test_search_filters_by_kunya(): void
    {
        $this->actingAs($this->createAdminUser());

        $match = $this->makeSahabi('has-kunya', ['ar' => 'صحابي الأول', 'en' => 'First Companion']);
        $match->syncTranslations(['kunya' => ['ar' => 'أبو الفريد', 'en' => 'Abu Al Fareed']]);

        $this->makeSahabi('no-match', ['ar' => 'صحابي آخر', 'en' => 'Other Companion']);

        $response = $this->get(route('admin.sahaba.index', ['search' => 'الفريد']));

        $response->assertOk();
        $slugs = $response->viewData('sahaba')->pluck('slug')->all();
        $this->assertSame(['has-kunya'], $slugs);
    }

    public function test_search_does_not_match_an_ancestors_name_in_full_name(): void
    {
        $this->actingAs($this->createAdminUser());

        $ownName = $this->makeSahabi('named-zayd', ['ar' => 'زيد', 'en' => 'Zayd']);
        $ownName->syncTranslations(['full_name' => ['en' => 'Zayd bin Harith']]);

        // "Zayd" only appears as an ancestor buried in the nasab chain, not
        // in this companion's own name/kunya/laqab.
        $ancestorOnly = $this->makeSahabi('descendant-of-zayd', ['ar' => 'بلال', 'en' => 'Bilal']);
        $ancestorOnly->syncTranslations(['full_name' => ['en' => 'Bilal bin Amr bin Zayd bin Harith']]);

        $response = $this->get(route('admin.sahaba.index', ['search' => 'Zayd']));

        $response->assertOk();
        $slugs = $response->viewData('sahaba')->pluck('slug')->all();
        $this->assertSame(['named-zayd'], $slugs);
    }

    public function test_gender_filter_narrows_results(): void
    {
        $this->actingAs($this->createAdminUser());

        $this->makeSahabi('male-one', ['ar' => 'رجل', 'en' => 'Man'], ['gender' => 'male']);
        $this->makeSahabi('female-one', ['ar' => 'امرأة', 'en' => 'Woman'], ['gender' => 'female']);

        $response = $this->get(route('admin.sahaba.index', ['gender' => 'female']));

        $response->assertOk();
        $slugs = $response->viewData('sahaba')->pluck('slug')->all();
        $this->assertSame(['female-one'], $slugs);
    }

    public function test_category_filter_narrows_results(): void
    {
        $this->actingAs($this->createAdminUser());

        $category = SahabiCategory::firstOrCreate(['key' => 'badr'], ['rank' => 9]);
        $badr = $this->makeSahabi('badr-participant', ['ar' => 'بدري', 'en' => 'Badri']);
        $badr->categories()->sync([$category->id]);

        $this->makeSahabi('no-category', ['ar' => 'بلا فئة', 'en' => 'No category']);

        $response = $this->get(route('admin.sahaba.index', ['categories' => ['badr']]));

        $response->assertOk();
        $slugs = $response->viewData('sahaba')->pluck('slug')->all();
        $this->assertSame(['badr-participant'], $slugs);
    }

    public function test_invalid_gender_filter_is_rejected(): void
    {
        $this->actingAs($this->createAdminUser());

        $response = $this->get(route('admin.sahaba.index', ['gender' => 'invalid']));

        $response->assertSessionHasErrors('gender');
    }
}
