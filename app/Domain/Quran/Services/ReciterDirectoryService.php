<?php

declare(strict_types=1);

namespace App\Domain\Quran\Services;

use App\Domain\Quran\Models\ReciterProfile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

final class ReciterDirectoryService
{
    public function listing(string $locale): array
    {
        return Cache::remember(QuranCacheKeys::publicReciters($locale).':profiles', 3600, function () use ($locale): array {
            $profiles = ReciterProfile::visible()->with(['translations', 'recordings' => fn ($q) => $q->active()->recitationsOnly()->with('translations')])->orderBy('display_order')->orderBy('id')->get();
            $coverage = DB::table('quran_recitations as a')->join('quran_recording_sources as s', 's.id', '=', 'a.source_id')->join('quran_reciters as r', 'r.id', '=', 's.recording_id')
                ->where('s.is_active', true)->where('r.is_active', true)->where('r.kind', 'recitation')
                ->selectRaw('r.profile_id, count(distinct a.surah_id) as surahs')->groupBy('r.profile_id')->pluck('surahs', 'profile_id');
            $all = $profiles->map(fn ($profile) => [
                'id' => $profile->id, 'slug' => $profile->slug, 'nameAr' => $profile->displayName('ar'), 'nameEn' => $profile->displayName('en'),
                'country' => $profile->country, 'countryFlag' => country_flag($profile->country), 'countryName' => country_name($profile->country, $locale),
                'style' => $profile->recordings->pluck('style')->filter()->unique()->first(),
                'styles' => $profile->recordings->pluck('style')->filter()->unique()->values()->all(),
                'isFeatured' => $profile->is_featured, 'followersCount' => $profile->followers_count,
                'profileImage' => $profile->profile_image_url, 'description' => $profile->description($locale),
                'recitationsCount' => (int) ($coverage[$profile->id] ?? 0), 'recordingsCount' => $profile->recordings->count(),
                'aliases' => $profile->recordings->flatMap(fn ($r) => [$r->slug, $r->getDisplayName('ar'), $r->getDisplayName('en')])->implode(' '),
            ])->all();

            return ['all' => $all, 'featured' => array_slice(array_values(array_filter($all, fn ($r) => $r['isFeatured'])), 0, 3), 'total' => count($all)];
        });
    }
}
