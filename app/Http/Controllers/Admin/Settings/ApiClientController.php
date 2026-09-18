<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Settings\ApiClientRequest;
use App\Services\ApiClientService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Settings → API clients: credentials for applications calling /api/v1.
 * The plain secret is shown once (as the direct response to create or rotate) and
 * never stored, flashed or shown again.
 */
final class ApiClientController extends Controller
{
    /** Scopes an admin can grant (mirrors ApiClient::SCOPES; controllers may not import models). */
    private const SCOPES = ['*', 'quran', 'hadith', 'seerah', 'media', 'articles', 'user'];

    public function __construct(private readonly ApiClientService $clients) {}

    public function index(Request $request): View
    {
        return view('admin.settings.api-clients.index', [
            'clients' => $this->clients->paginate($request->integer('per_page', 20)),
            'scopes' => self::SCOPES,
        ]);
    }

    public function store(ApiClientRequest $request): View
    {
        ['client' => $client, 'secret' => $secret] = $this->clients->create($request->validated(), $request->user()?->id);

        return view('admin.settings.api-clients.created', ['client' => $client, 'secret' => $secret]);
    }

    public function edit(int $apiClient): View
    {
        return view('admin.settings.api-clients.edit', ['client' => $this->clients->findOrFail($apiClient), 'scopes' => self::SCOPES]);
    }

    public function update(ApiClientRequest $request, int $apiClient): RedirectResponse
    {
        $this->clients->update($apiClient, $request->validated());

        return redirect()->route('admin.settings.api-clients.index')->with('success', __('admin.api_clients.updated'));
    }

    public function rotate(int $apiClient): View
    {
        ['client' => $client, 'secret' => $secret] = $this->clients->rotateSecret($apiClient);

        return view('admin.settings.api-clients.created', ['client' => $client, 'secret' => $secret, 'rotated' => true]);
    }

    public function toggle(int $apiClient): RedirectResponse
    {
        $this->clients->toggle($apiClient);

        return back()->with('success', __('admin.api_clients.updated'));
    }

    public function destroy(int $apiClient): RedirectResponse
    {
        $this->clients->delete($apiClient);

        return redirect()->route('admin.settings.api-clients.index')->with('success', __('admin.api_clients.deleted'));
    }
}
