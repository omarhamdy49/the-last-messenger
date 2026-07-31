<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Domain\Genealogy\Models\Person;
use Illuminate\Foundation\Testing\DatabaseTruncation;
use Tests\TestCase;

/**
 * The admin Genealogy data table's search + advanced filters (gender,
 * person_type, tribe, verified) run through PersonRepository::getFilteredPaginated().
 * Before this, GenealogyController::index() built the query with
 * getAllPeople($perPage) and silently ignored every filter the GET form
 * submitted — these guard that regression doesn't reappear.
 */
class GenealogyIndexFilterTest extends TestCase
{
    use DatabaseTruncation;

    protected array $tablesToTruncate = [
        'model_has_permissions',
        'model_has_roles',
        'role_has_permissions',
        'permissions',
        'roles',
        'users',
        'sahabi_translations',
        'sahaba',
    ];

    protected function tearDown(): void
    {
        $this->truncateTablesForAllConnections();

        parent::tearDown();
    }

    private function makePerson(string $slug, array $translations, array $attributes = []): Person
    {
        $person = Person::create(array_merge([
            'slug' => $slug,
            'gender' => 'male',
            'person_type' => 'family',
        ], $attributes));

        $person->syncTranslations(['name' => $translations]);

        return $person;
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $response = $this->get(route('admin.genealogy.index'));

        $response->assertRedirect(route('login'));
    }

    public function test_index_renders_people_ordered_by_name_asc(): void
    {
        $this->actingAs($this->createAdminUser());

        $this->makePerson('zayd', ['ar' => 'زيد', 'en' => 'Zayd']);
        $this->makePerson('abu-bakr', ['ar' => 'أبو بكر', 'en' => 'Abu Bakr']);
        $this->makePerson('english-only', ['en' => 'Aardvark']);

        $response = $this->get(route('admin.genealogy.index'));

        $response->assertOk();
        $slugs = $response->viewData('people')->pluck('slug')->all();
        $this->assertSame(['abu-bakr', 'zayd', 'english-only'], $slugs);
    }

    public function test_search_filters_by_name(): void
    {
        $this->actingAs($this->createAdminUser());

        $this->makePerson('bilal', ['ar' => 'بلال', 'en' => 'Bilal']);
        $this->makePerson('other', ['ar' => 'آخر', 'en' => 'Other']);

        $response = $this->get(route('admin.genealogy.index', ['search' => 'Bilal']));

        $response->assertOk();
        $slugs = $response->viewData('people')->pluck('slug')->all();
        $this->assertSame(['bilal'], $slugs);
    }

    public function test_gender_filter_narrows_results(): void
    {
        $this->actingAs($this->createAdminUser());

        $this->makePerson('male-one', ['ar' => 'رجل', 'en' => 'Man'], ['gender' => 'male']);
        $this->makePerson('female-one', ['ar' => 'امرأة', 'en' => 'Woman'], ['gender' => 'female']);

        $response = $this->get(route('admin.genealogy.index', ['gender' => 'female']));

        $response->assertOk();
        $slugs = $response->viewData('people')->pluck('slug')->all();
        $this->assertSame(['female-one'], $slugs);
    }

    public function test_person_type_filter_narrows_results(): void
    {
        $this->actingAs($this->createAdminUser());

        $this->makePerson('prophet-one', ['ar' => 'نبي', 'en' => 'Prophet'], ['person_type' => 'prophet']);
        $this->makePerson('family-one', ['ar' => 'قريب', 'en' => 'Relative'], ['person_type' => 'family']);

        $response = $this->get(route('admin.genealogy.index', ['person_type' => 'prophet']));

        $response->assertOk();
        $slugs = $response->viewData('people')->pluck('slug')->all();
        $this->assertSame(['prophet-one'], $slugs);
    }

    public function test_invalid_person_type_filter_is_rejected(): void
    {
        $this->actingAs($this->createAdminUser());

        $response = $this->get(route('admin.genealogy.index', ['person_type' => 'not-a-real-type']));

        $response->assertSessionHasErrors('person_type');
    }
}
