<?php

declare(strict_types=1);

namespace App\Domain\Shared\Enums;

use App\Domain\Shared\Models\Location;

/**
 * The kind of place a {@see Location} is.
 *
 * Stored as `varchar(20)`, not a MySQL `ENUM`, so adding a kind is a code change
 * rather than a migration — consistent with how the rest of the schema stores
 * enum-backed columns.
 *
 * Labels are Arabic because Arabic is the default *and* fallback locale. The
 * English label exists for exports and the admin language tabs.
 */
enum LocationType: string
{
    case CITY = 'city';
    case REGION = 'region';
    case COUNTRY = 'country';
    case MOUNTAIN = 'mountain';
    case VALLEY = 'valley';
    case MOSQUE = 'mosque';
    case WELL = 'well';
    case BATTLEFIELD = 'battlefield';
    case LANDMARK = 'landmark';

    /**
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public function label(): string
    {
        return match ($this) {
            self::CITY => 'مدينة',
            self::REGION => 'إقليم',
            self::COUNTRY => 'بلد',
            self::MOUNTAIN => 'جبل',
            self::VALLEY => 'وادٍ',
            self::MOSQUE => 'مسجد',
            self::WELL => 'بئر',
            self::BATTLEFIELD => 'موقع غزوة',
            self::LANDMARK => 'معلم',
        };
    }

    public function labelEn(): string
    {
        return match ($this) {
            self::CITY => 'City',
            self::REGION => 'Region',
            self::COUNTRY => 'Country',
            self::MOUNTAIN => 'Mountain',
            self::VALLEY => 'Valley',
            self::MOSQUE => 'Mosque',
            self::WELL => 'Well',
            self::BATTLEFIELD => 'Battlefield',
            self::LANDMARK => 'Landmark',
        };
    }
}
