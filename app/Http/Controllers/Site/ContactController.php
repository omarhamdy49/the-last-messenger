<?php

declare(strict_types=1);

namespace App\Http\Controllers\Site;

use App\Domain\Content\DTOs\CreateContactMessageData;
use App\Domain\Content\Services\ContactMessageService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Site\StoreContactMessageRequest;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

final class ContactController extends Controller
{
    public function __construct(
        private readonly ContactMessageService $contactMessageService,
    ) {}

    public function show(): View
    {
        return view('public.contact.index');
    }

    public function store(StoreContactMessageRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $this->contactMessageService->create(CreateContactMessageData::fromArray([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'subject' => $validated['subject'],
            'body' => $validated['message'],
        ]));

        return redirect()
            ->route('public.contact')
            ->with('toast', __('site.contact_success'));
    }
}
