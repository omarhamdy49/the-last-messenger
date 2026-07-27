<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Domain\Sahaba\Models\Sahabi;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Every column/translatable field on `sahaba` + `sahabi_translations` that the
 * admin create/edit forms expose must actually round-trip through
 * store()/update() — this guards against fields that have a validation rule
 * or a form input but never get wired through to the model on the other end.
 */
class SahabiCrudTest extends TestCase
{
    use RefreshDatabase;

    private function fullPayload(): array
    {
        return [
            'slug' => 'test-full-sahabi',
            'name' => ['ar' => 'اسم الاختبار', 'en' => 'Test Name'],
            'full_name' => ['ar' => 'الاسم الكامل للاختبار', 'en' => 'Full Test Name'],
            'kunya' => ['ar' => 'أبو الاختبار', 'en' => 'Abu Test'],
            'laqab' => ['ar' => 'الصادق', 'en' => 'The Truthful'],
            'title' => ['ar' => 'الخليفة الاختباري', 'en' => 'Test Caliph'],
            'occupation' => ['ar' => 'تاجر', 'en' => 'Merchant'],
            'biography' => ['ar' => 'سيرة الاختبار', 'en' => 'Test biography'],
            'virtues' => ['ar' => 'فضائل الاختبار', 'en' => 'Test virtues'],
            'famous_narrations' => ['ar' => 'أحاديث الاختبار', 'en' => 'Test narrations'],
            'martyrdom' => ['ar' => 'استشهاد الاختبار', 'en' => 'Test martyrdom'],
            'notes' => ['ar' => 'ملاحظات الاختبار', 'en' => 'Test notes'],
            'gender' => 'male',
            'categories' => ['badr', 'muhajir'],
            'tribe' => 'قريش',
            'clan' => 'بنو هاشم',
            'birth_date_hijri' => '10 ق.هـ',
            'death_date_hijri' => '20 هـ',
            'birth_date_gregorian' => '1990-01-15',
            'death_date_gregorian' => '2040-06-20',
            'age_at_death' => 50,
            'birth_place' => 'مكة المكرمة',
            'death_place' => 'المدينة المنورة',
            'burial_place' => 'البقيع',
            'conversion_date_hijri' => '1 هـ',
            'conversion_order' => 5,
            'roles' => 'خليفة، قائد عسكري',
            'reported_hadith_count' => 123,
            'importance_rank' => 7,
            'is_featured' => '1',
            'is_verified' => '1',
            'generation' => 1,
        ];
    }

    public function test_store_persists_every_field_the_form_exposes(): void
    {
        $admin = $this->createAdminUser();
        $this->actingAs($admin);

        $response = $this->post(route('admin.sahaba.store'), $this->fullPayload());

        $response->assertRedirect(route('admin.sahaba.index'));

        $sahabi = Sahabi::where('slug', 'test-full-sahabi')->with('categories')->firstOrFail();

        $this->assertSame('male', $sahabi->gender);
        $this->assertSame('قريش', $sahabi->tribe);
        $this->assertSame('بنو هاشم', $sahabi->clan);
        $this->assertSame('10 ق.هـ', $sahabi->birth_date_hijri);
        $this->assertSame('20 هـ', $sahabi->death_date_hijri);
        $this->assertSame('1990-01-15', $sahabi->birth_date_gregorian->toDateString());
        $this->assertSame('2040-06-20', $sahabi->death_date_gregorian->toDateString());
        $this->assertSame(50, $sahabi->age_at_death);
        $this->assertSame('مكة المكرمة', $sahabi->birth_place);
        $this->assertSame('المدينة المنورة', $sahabi->death_place);
        $this->assertSame('البقيع', $sahabi->burial_place);
        $this->assertSame('1 هـ', $sahabi->conversion_date_hijri);
        $this->assertSame(5, $sahabi->conversion_order);
        $this->assertSame(['خليفة', 'قائد عسكري'], $sahabi->roles);
        $this->assertSame(123, $sahabi->reported_hadith_count);
        $this->assertSame(7, $sahabi->importance_rank);
        $this->assertTrue($sahabi->is_featured);
        $this->assertTrue($sahabi->is_verified);
        $this->assertSame(1, $sahabi->generation);
        $this->assertSame('sahabi', $sahabi->person_type->value);
        $this->assertTrue($sahabi->is_sahabi);

        $this->assertSame(['badr', 'muhajir'], $sahabi->categories->pluck('key')->sort()->values()->all());

        $this->assertSame('اسم الاختبار', $sahabi->getTranslation('name', 'ar'));
        $this->assertSame('Test Name', $sahabi->getTranslation('name', 'en'));
        $this->assertSame('الاسم الكامل للاختبار', $sahabi->getTranslation('full_name', 'ar'));
        $this->assertSame('أبو الاختبار', $sahabi->getTranslation('kunya', 'ar'));
        $this->assertSame('الصادق', $sahabi->getTranslation('laqab', 'ar'));
        $this->assertSame('الخليفة الاختباري', $sahabi->getTranslation('title', 'ar'));
        $this->assertSame('تاجر', $sahabi->getTranslation('occupation', 'ar'));
        $this->assertSame('سيرة الاختبار', $sahabi->getTranslation('biography', 'ar'));
        $this->assertSame('فضائل الاختبار', $sahabi->getTranslation('virtues', 'ar'));
        $this->assertSame('أحاديث الاختبار', $sahabi->getTranslation('famous_narrations', 'ar'));
        $this->assertSame('استشهاد الاختبار', $sahabi->getTranslation('martyrdom', 'ar'));
        $this->assertSame('ملاحظات الاختبار', $sahabi->getTranslation('notes', 'ar'));
    }

    public function test_update_persists_every_field_the_form_exposes(): void
    {
        $admin = $this->createAdminUser();
        $this->actingAs($admin);

        $this->post(route('admin.sahaba.store'), $this->fullPayload());
        $sahabi = Sahabi::where('slug', 'test-full-sahabi')->firstOrFail();

        $updatePayload = array_merge($this->fullPayload(), [
            'slug' => 'test-full-sahabi',
            'categories' => ['ansar'],
            'clan' => 'بنو تيم',
            'roles' => 'راوي',
            'reported_hadith_count' => 456,
            'is_verified' => '0',
            'generation' => 2,
            'notes' => ['ar' => 'ملاحظات محدثة', 'en' => 'Updated notes'],
        ]);

        $response = $this->put(route('admin.sahaba.update', $sahabi->id), $updatePayload);

        $response->assertRedirect(route('admin.sahaba.index'));

        $sahabi->refresh();
        $sahabi->load('categories');

        $this->assertSame('بنو تيم', $sahabi->clan);
        $this->assertSame(['راوي'], $sahabi->roles);
        $this->assertSame(456, $sahabi->reported_hadith_count);
        $this->assertFalse($sahabi->is_verified);
        $this->assertSame(2, $sahabi->generation);
        $this->assertSame(['ansar'], $sahabi->categories->pluck('key')->all());
        $this->assertSame('ملاحظات محدثة', $sahabi->getTranslation('notes', 'ar'));
    }

    public function test_show_and_edit_pages_render_every_populated_field_without_error(): void
    {
        $admin = $this->createAdminUser();
        $this->actingAs($admin);

        $this->post(route('admin.sahaba.store'), $this->fullPayload());
        $sahabi = Sahabi::where('slug', 'test-full-sahabi')->firstOrFail();

        $this->get(route('admin.sahaba.show', $sahabi->id))
            ->assertOk()
            ->assertSee('بنو هاشم') // clan
            ->assertSee('البقيع') // burial_place
            ->assertSee('فضائل الاختبار') // virtues
            ->assertSee('استشهاد الاختبار') // martyrdom
            ->assertSee('ملاحظات الاختبار') // notes
            ->assertSee('خليفة') // roles
            ->assertSee('123'); // reported_hadith_count

        $this->get(route('admin.sahaba.edit', $sahabi->id))
            ->assertOk()
            ->assertSee('value="بنو هاشم"', false) // clan input
            ->assertSee('البقيع') // burial_place input value
            ->assertSee('فضائل الاختبار') // virtues wysiwyg value
            ->assertSee('خليفة، قائد عسكري', false); // roles input value
    }
}
