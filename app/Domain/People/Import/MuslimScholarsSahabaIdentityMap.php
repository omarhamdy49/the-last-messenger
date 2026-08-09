<?php

declare(strict_types=1);

namespace App\Domain\People\Import;

/**
 * Reviewed identity decisions keyed by the stable MuslimScholars source ID.
 *
 * Database IDs are deliberately forbidden here: they change after migrate:fresh.
 * Slug aliases let the importer work with both the original compact seed set
 * and the expanded Prophet-family seed set.
 */
final class MuslimScholarsSahabaIdentityMap
{
    /** @var array<string, list<string>> */
    private const SOURCE_TO_SLUGS = [
        '3' => ['umar-ibn-al-khattab'],
        '4' => ['uthman-ibn-affan'],
        '5' => ['ali-ibn-abi-talib'],
        '7' => ['al-zubayr-ibn-al-awwam'],
        '17' => ['abdullah-ibn-abbas'],
        '18' => ['abdullah-ibn-umar'],
        '20' => ['hamza-ibn-abd-al-muttalib'],
        '21' => ['jafar-ibn-abi-talib'],
        '30' => ['al-hasan-ibn-ali'],
        '31' => ['al-husayn-ibn-ali'],
        '51' => ['khadijah-bint-khuwaylid'],
        '52' => ['sawda-bint-zamaa'],
        '53' => ['aisha-bint-abi-bakr'],
        '54' => ['hafsa-bint-umar-um-al-muminin', 'hafsa-bint-umar'],
        '55' => ['zaynab-bint-khuzayma'],
        '56' => ['umm-salama-hind-bint-abi-umayya'],
        '57' => ['zaynab-bint-jahsh'],
        '58' => ['juwayriya-bint-al-harith'],
        '59' => ['umm-habiba-ramla-bint-abi-sufyan'],
        '60' => ['safiyya-bint-huyayy'],
        '61' => ['maymuna-bint-al-harith'],
        '62' => ['mariya-al-qibtiyya'],
        '63' => ['fatima-al-zahra'],
        '85' => ['safiyya-bint-abd-al-muttalib'],
        '92' => ['umama-bint-abi-al-as'],
        '94' => ['umm-kulthum-bint-muhammad'],
        '97' => ['zaynab-bint-muhammad'],
        '98' => ['zaynab-bint-ali'],
        '99' => ['umm-kulthum-bint-ali'],
        '100' => ['al-abbas-ibn-abd-al-muttalib'],
        '102' => ['ruqayya-bint-muhammad'],
        '117' => ['abu-sufyan-ibn-al-harith'],
        '119' => ['abu-al-as-ibn-al-rabi'],
        '128' => ['qutham-ibn-al-abbas'],
        '133' => ['ubaydullah-ibn-al-abbas'],
        '135' => ['al-fadl-ibn-al-abbas'],
        '164' => ['talib-ibn-abi-talib'],
        '165' => ['aqil-ibn-abi-talib'],
        '174' => ['rabia-ibn-al-harith'],
        '177' => ['nawfal-ibn-al-harith'],
        '198' => ['umm-hani-bint-abi-talib'],
        '231' => ['arwa-bint-abd-al-muttalib'],
        '261' => ['atika-bint-abd-al-muttalib'],
        '428' => ['utba-ibn-abi-lahab'],
        '430' => ['muattib-ibn-abi-lahab'],
        '516' => ['al-qasim-ibn-muhammad'],
        '517' => ['abdullah-ibn-muhammad'],
        '518' => ['ibrahim-ibn-muhammad'],
        '519' => ['ali-ibn-abi-al-as'],
        '571' => ['abdullah-ibn-uthman'],
        '7639' => ['al-harith-ibn-abd-al-muttalib'],
    ];

    /** Known prefix/homonym collisions that must always remain distinct. */
    private const FORCE_DISTINCT = ['4741', '6491', '9372'];

    /** @return list<string> */
    public function slugsFor(string $sourceId): array
    {
        return self::SOURCE_TO_SLUGS[$sourceId] ?? [];
    }

    public function mustRemainDistinct(string $sourceId): bool
    {
        return in_array($sourceId, self::FORCE_DISTINCT, true);
    }

    /** @return array<string, list<string>> */
    public function reviewedMappings(): array
    {
        return self::SOURCE_TO_SLUGS;
    }
}
