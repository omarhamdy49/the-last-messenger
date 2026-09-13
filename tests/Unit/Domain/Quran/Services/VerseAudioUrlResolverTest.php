<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Quran\Services;

use App\Domain\Quran\Models\Reciter;
use App\Domain\Quran\Models\RecordingSource;
use App\Domain\Quran\Services\VerseAudioUrlResolver;
use InvalidArgumentException;
use Tests\TestCase;

class VerseAudioUrlResolverTest extends TestCase
{
    private VerseAudioUrlResolver $resolver;

    protected function setUp(): void
    {
        parent::setUp();
        $this->resolver = new VerseAudioUrlResolver;
    }

    public function test_resolves_zero_padded_verse_url(): void
    {
        $url = $this->resolver->resolveForFolder('Alafasy_128kbps', 1, 1);

        $this->assertSame('https://everyayah.com/data/Alafasy_128kbps/001001.mp3', $url);
    }

    public function test_resolves_three_digit_surah_and_verse_numbers(): void
    {
        $url = $this->resolver->resolveForFolder('Husary_128kbps', 114, 6);

        $this->assertSame('https://everyayah.com/data/Husary_128kbps/114006.mp3', $url);
    }

    public function test_resolves_from_reciter_model(): void
    {
        $reciter = (new Reciter)->setRelation('sources', collect([new RecordingSource(['kind' => 'ayah', 'provider' => 'everyayah', 'is_active' => true, 'base_url' => 'https://everyayah.com/data', 'metadata' => ['folder' => 'Abdul_Basit_Murattal_192kbps']])]));

        $url = $this->resolver->resolve($reciter, 2, 255);

        $this->assertSame('https://everyayah.com/data/Abdul_Basit_Murattal_192kbps/002255.mp3', $url);
    }

    public function test_returns_null_when_reciter_has_no_everyayah_folder(): void
    {
        $reciter = (new Reciter)->setRelation('sources', collect());

        $this->assertNull($this->resolver->resolve($reciter, 1, 1));
    }

    public function test_rejects_surah_number_out_of_range(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->resolver->resolveForFolder('Alafasy_128kbps', 115, 1);
    }

    public function test_rejects_non_positive_verse_number(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->resolver->resolveForFolder('Alafasy_128kbps', 1, 0);
    }
}
