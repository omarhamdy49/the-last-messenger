<?php

declare(strict_types=1);

namespace App\Domain\Hadith\Import\Sunnah;

final class SunnahCollectionCatalog
{
    /** @var array<string, array{reference: string, sort: int, expected: int, chapters: bool}> */
    private const IMPORTABLE = [
        'bukhari' => ['reference' => 'Sahih al-Bukhari {hadithNumber}', 'sort' => 1, 'expected' => 7277, 'chapters' => true],
        'muslim' => ['reference' => 'Sahih Muslim {hadithNumber}', 'sort' => 2, 'expected' => 7459, 'chapters' => true],
        'nasai' => ['reference' => "Sunan an-Nasa'i {hadithNumber}", 'sort' => 3, 'expected' => 5768, 'chapters' => true],
        'abudawud' => ['reference' => 'Sunan Abi Dawud {hadithNumber}', 'sort' => 4, 'expected' => 5276, 'chapters' => true],
        'tirmidhi' => ['reference' => 'Jami` at-Tirmidhi {hadithNumber}', 'sort' => 5, 'expected' => 4053, 'chapters' => true],
        'ibnmajah' => ['reference' => 'Sunan Ibn Majah {hadithNumber}', 'sort' => 6, 'expected' => 4345, 'chapters' => true],
        'ahmad' => ['reference' => 'Musnad Ahmad {hadithNumber}', 'sort' => 8, 'expected' => 1359, 'chapters' => false],
        'shamail' => ['reference' => "Ash-Shama'il Al-Muhammadiyah {hadithNumber}", 'sort' => 11, 'expected' => 402, 'chapters' => false],
        'riyadussalihin' => ['reference' => 'Riyad as-Salihin {hadithNumber}', 'sort' => 12, 'expected' => 1896, 'chapters' => true],
    ];

    /** @var array<string, string> */
    private const UNAVAILABLE = [
        'malik' => 'API reports 1,973 available Hadiths but exposes zero books or book Hadith rows.',
        'darimi' => 'API reports zero available Hadiths and exposes zero books.',
        'adab' => 'API exposes 1,185 of 1,326 available Hadiths.',
        'mishkat' => 'API book inventory exposes 5,015 of 5,319 available Hadiths.',
        'bulugh' => 'API exposes 378 of 1,767 available Hadiths.',
    ];

    /** @return list<SunnahCollectionDefinition> */
    public function importable(): array
    {
        $definitions = [];
        foreach (self::IMPORTABLE as $source => $definition) {
            $definitions[] = new SunnahCollectionDefinition(
                source: $source,
                slug: $source,
                referenceTemplate: $definition['reference'],
                sortOrder: $definition['sort'],
                expectedHadiths: $definition['expected'],
                hasChapters: $definition['chapters'],
            );
        }

        return $definitions;
    }

    public function find(string $source): ?SunnahCollectionDefinition
    {
        foreach ($this->importable() as $definition) {
            if ($definition->source === $source) {
                return $definition;
            }
        }

        return null;
    }

    /** @return array<string, string> */
    public function unavailable(): array
    {
        return self::UNAVAILABLE;
    }
}
