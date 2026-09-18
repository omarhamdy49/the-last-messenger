{{-- Dating dispute note, carried verbatim. @props note --}}
@props(['note'])

@if (filled($note))
    <aside id="dispute" class="sx-section sx-dispute" role="note">
        <span class="sx-dispute-icon"><i data-lucide="alert-triangle" aria-hidden="true"></i></span>
        <div class="min-w-0">
            <p class="font-extrabold mb-1">{{ __('seerah.public.dispute_heading') }}</p>
            <p class="break-words">{{ $note }}</p>
        </div>
    </aside>
@endif
