<?php

declare(strict_types=1);

namespace Tests\Unit\Quran;

use App\Domain\Quran\Services\AnswerVerseLinker;
use Tests\TestCase;

final class AnswerVerseLinkerTest extends TestCase
{
    private AnswerVerseLinker $linker;

    protected function setUp(): void
    {
        parent::setUp();
        $this->linker = new AnswerVerseLinker;
    }

    public function test_it_extracts_inline_markers_in_order_without_duplicates(): void
    {
        $answer = 'Patience is praised [[2:155]], and again later [[2:155]] with mercy [[1:1]].';

        $verses = $this->linker->fromAnswer($answer);

        $this->assertSame(['2:155', '1:1'], array_column($verses, 'key'));
        $this->assertSame('/quran/2#v155', parse_url($verses[0]['url'], PHP_URL_PATH).'#'.parse_url($verses[0]['url'], PHP_URL_FRAGMENT));
        $this->assertSame(2, $verses[0]['surah']);
        $this->assertSame(155, $verses[0]['ayah']);
    }

    public function test_it_handles_ranges_and_merges_citation_references(): void
    {
        $answer = 'See [[2:255-257]] for the throne verse.';
        $citations = [
            ['tool' => 'fetch_tafsir', 'args' => ['ayahs' => '3:18']],
            ['tool' => 'search_quran', 'args' => ['query' => 'mercy']], // no verse ref
        ];

        $verses = $this->linker->fromAnswer($answer, $citations);

        // Range collapses to its start ayah; the citation verse is appended.
        $this->assertSame(['2:255', '3:18'], array_column($verses, 'key'));
    }

    public function test_it_drops_out_of_range_references(): void
    {
        $answer = 'Not a verse [[200:5]] nor [[2:0]] but valid [[2:1]].';

        $verses = $this->linker->fromAnswer($answer);

        $this->assertSame(['2:1'], array_column($verses, 'key'));
    }
}
