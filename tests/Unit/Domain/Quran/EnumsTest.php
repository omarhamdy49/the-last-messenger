<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Quran;

use App\Domain\Quran\Enums\CharType;
use App\Domain\Quran\Enums\RecitationStyle;
use App\Domain\Quran\Enums\ResourceKind;
use App\Domain\Quran\Enums\Script;
use App\Domain\Quran\Enums\SectionType;
use App\Domain\Quran\Enums\SyncResourceGroup;
use PHPUnit\Framework\TestCase;

final class EnumsTest extends TestCase
{
    public function test_section_type_bounds_and_columns(): void
    {
        $this->assertSame(30, SectionType::JUZ->max());
        $this->assertSame(558, SectionType::RUKU->max());
        $this->assertSame(240, SectionType::RUB_EL_HIZB->max());
        $this->assertSame('rub_el_hizb_number', SectionType::RUB_EL_HIZB->verseColumn());
        $this->assertSame('rub_el_hizbs', SectionType::RUB_EL_HIZB->qfPath());
    }

    public function test_script_accepts_column_and_qf_path_forms(): void
    {
        $this->assertSame(Script::UTHMANI, Script::fromExternal('uthmani'));
        $this->assertSame(Script::UTHMANI, Script::fromExternal('text_uthmani'));
        $this->assertSame(Script::INDOPAK_NASTALEEQ, Script::fromExternal('indopak_nastaleeq'));
        $this->assertSame(Script::CODE_V2, Script::fromExternal('v2'));
        $this->assertNull(Script::fromExternal('klingon'));
        $this->assertSame('uthmani_tajweed', Script::UTHMANI_TAJWEED->qfPath());
        $this->assertSame('code_v1', Script::CODE_V1->qfPath());
        $this->assertTrue(Script::CODE_V1->isGlyph());
        $this->assertFalse(Script::QPC_HAFS->isGlyph());
    }

    public function test_sync_group_and_resource_kind_round_trip(): void
    {
        $syncable = array_filter(ResourceKind::cases(), static fn (ResourceKind $k): bool => $k->syncGroup() !== null);

        $this->assertCount(6, $syncable);
        foreach ($syncable as $kind) {
            $this->assertSame($kind, $kind->syncGroup()?->resourceKind());
        }

        $this->assertNull(SyncResourceGroup::MUSHAFS->resourceKind());
        $this->assertNull(ResourceKind::CHAPTER_INFO->syncGroup());
    }

    public function test_resource_kind_catalogue_paths_and_route_segments(): void
    {
        $this->assertSame('resources/chapter_reciters', ResourceKind::CHAPTER_RECITER->qfCataloguePath());
        $this->assertSame('reciters', ResourceKind::CHAPTER_RECITER->qfCatalogueKey());
        $this->assertNull(ResourceKind::WORD_TRANSLATION->qfCataloguePath());
        $this->assertTrue(ResourceKind::RECITATION->isReciter());
        $this->assertFalse(ResourceKind::TAFSIR->isReciter());

        foreach (ResourceKind::cases() as $kind) {
            $this->assertSame($kind, ResourceKind::fromRouteSegment($kind->routeSegment()));
        }
    }

    public function test_recitation_style_normalises_external_names(): void
    {
        $this->assertSame(RecitationStyle::MURATTAL, RecitationStyle::fromExternal('Murattal'));
        $this->assertSame(RecitationStyle::MUJAWWAD, RecitationStyle::fromExternal('مجود'));
        $this->assertSame(RecitationStyle::MUALLIM, RecitationStyle::fromExternal('المعلم'));
        $this->assertNull(RecitationStyle::fromExternal('Hafs'));
        $this->assertNull(RecitationStyle::fromExternal(null));
    }

    public function test_char_type_maps_known_values(): void
    {
        $this->assertSame(CharType::END, CharType::tryFrom('end'));
        $this->assertSame(CharType::RUB_EL_HIZB, CharType::tryFrom(str_replace('-', '_', 'rub-el-hizb')));
    }
}
