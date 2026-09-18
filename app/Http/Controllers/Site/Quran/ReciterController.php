<?php

declare(strict_types=1);

namespace App\Http\Controllers\Site\Quran;

use App\Domain\Quran\Services\PublicReciterService;
use App\Domain\Shared\Enums\FavorableType;
use App\Domain\Shared\Services\FavoriteService;
use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;

final class ReciterController extends Controller
{
    public function __construct(private readonly PublicReciterService $reciters) {}

    public function index(): View
    {
        $data = $this->reciters->listing(app()->getLocale());

        return view('public.quran.reciters', [
            'featuredReciters' => $data['featured'],
            'reciters' => $data['all'],
            'totalReciters' => $data['total'],
        ]);
    }

    public function show(string $slug, FavoriteService $favorites): View
    {
        $data = $this->reciters->show($slug, app()->getLocale()) ?? abort(404);
        $user = auth()->user();
        $data['reciter']['isSaved'] = $user !== null && $favorites->isFavorited($user, FavorableType::RECITER, (int) $data['reciter']['id']);

        return view('public.quran.reciter', $data);
    }
}
