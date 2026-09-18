<?php

declare(strict_types=1);

namespace App\Domain\Quran\Services;

/**
 * Tells apart reciters whose displayed name is the same.
 *
 * The catalogue holds one row per recording, so the same person appears several times:
 * Abdul Basit is both Mujawwad and Murattal, al-Husary both Murattal and Muallim, and
 * al-Minshawi has a recording with children. Those rows carry the same localized name,
 * so a list showing the name alone reads as a duplicate.
 *
 * Every row of a colliding group is qualified — including a row whose first qualifier is
 * missing, which is why the group is worked out from the original names rather than one
 * qualifier at a time: qualifying only some of them leaves the rest looking unexplained.
 */
final class ReciterLabeller
{
    /**
     * @param  list<array<string, mixed>>  $rows
     * @param  list<string>  $qualifiers  keys to qualify with, best first
     * @param  list<string>  $nameKeys
     * @return list<array<string, mixed>>
     */
    public static function disambiguate(array $rows, array $qualifiers, array $nameKeys = ['nameAr', 'nameEn']): array
    {
        foreach ($nameKeys as $key) {
            $groups = [];
            foreach ($rows as $i => $row) {
                $groups[(string) ($row[$key] ?? '')][] = $i;
            }

            foreach ($groups as $indexes) {
                if (count($indexes) < 2) {
                    continue;
                }

                foreach ($indexes as $i) {
                    foreach ($qualifiers as $qualifier) {
                        $suffix = trim((string) ($rows[$i][$qualifier] ?? ''));
                        if ($suffix !== '') {
                            $rows[$i][$key] = $rows[$i][$key].' · '.$suffix;
                            break;
                        }
                    }
                }
            }
        }

        return $rows;
    }

    /**
     * The qualifier a source name carries in brackets or after a dash — "(with kids)",
     * "- beta". It is the only thing separating two recordings when the catalogue gives
     * them no style, and it is left in the source language because that is where it
     * comes from.
     */
    public static function sourceHint(string $name): ?string
    {
        if (preg_match('/[(\[]\s*([^)\]]{2,40})\s*[)\]]\s*$/u', $name, $m) === 1) {
            return trim($m[1]);
        }

        if (preg_match('/\s[-–]\s*([^-–]{2,40})$/u', $name, $m) === 1) {
            return trim($m[1]);
        }

        return null;
    }
}
