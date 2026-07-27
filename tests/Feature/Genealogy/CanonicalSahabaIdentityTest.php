<?php

declare(strict_types=1);

namespace Tests\Feature\Genealogy;

use App\Domain\Genealogy\Models\Person;
use App\Domain\Hadith\Models\Companion;
use App\Domain\Hadith\Models\HadithNarrator;
use App\Domain\Sahaba\Models\Sahabi;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class CanonicalSahabaIdentityTest extends TestCase
{
    use RefreshDatabase;

    public function test_genealogy_uses_sahaba_without_a_people_table(): void
    {
        $father = Person::create([
            'slug' => 'canonical-father',
            'gender' => 'male',
            'person_type' => 'family',
            'is_sahabi' => false,
        ]);

        $child = Person::create([
            'slug' => 'canonical-child',
            'gender' => 'male',
            'person_type' => 'sahabi',
            'is_sahabi' => true,
            'father_id' => $father->id,
        ]);

        $this->assertFalse(Schema::hasTable('people'));
        $this->assertFalse(Schema::hasTable('person_translations'));
        $this->assertSame('sahaba', $child->getTable());
        $this->assertTrue($child->father->is($father));
        $this->assertTrue($father->childrenAsFather->contains($child));
    }

    public function test_hadith_roles_reuse_one_sahabi_identity(): void
    {
        $sahabi = Sahabi::create([
            'slug' => 'umar-ibn-al-khattab-test',
            'gender' => 'male',
            'person_type' => 'sahabi',
            'is_sahabi' => true,
        ]);

        $sahabi->syncTranslations([
            'name' => [
                'ar' => 'عمر بن الخطاب',
                'en' => 'Umar ibn al-Khattab',
            ],
            'biography' => [
                'ar' => 'سيرة عمر',
                'en' => 'Biography of Umar',
            ],
        ]);

        $companion = Companion::findOrCreateByName([
            'name_ar' => 'عُمَر بْن الْخَطَّاب',
            'name_en' => 'Umar ibn al-Khattab',
            'bio_en' => 'This source copy must not become canonical.',
        ]);

        $narrator = HadithNarrator::resolveOrCreate([
            'name_ar' => 'عمر بن الخطاب',
            'name_en' => 'Umar ibn al-Khattab',
            'biography' => 'This source copy must not become canonical.',
            'reliability_grade' => 'Companion',
        ]);

        $this->assertSame($sahabi->id, $companion->sahaba_id);
        $this->assertSame($sahabi->id, $narrator->sahaba_id);
        $this->assertNull($companion->getRawOriginal('name_ar'));
        $this->assertNull($narrator->getRawOriginal('name_ar'));
        $this->assertSame('عمر بن الخطاب', $companion->name_ar);
        $this->assertSame('Umar ibn al-Khattab', $narrator->name_en);
        $this->assertSame('Companion', $narrator->reliability_grade);

        $sameNarrator = HadithNarrator::resolveOrCreate([
            'name_en' => 'Umar ibn al-Khattab',
            'reliability_grade' => 'Companion',
        ]);

        $this->assertSame($narrator->id, $sameNarrator->id);
        $this->assertSame(1, HadithNarrator::where('sahaba_id', $sahabi->id)->count());
    }
}
