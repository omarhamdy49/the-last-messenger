<?php

declare(strict_types=1);

namespace Database\Seeders\Seerah;

use App\Domain\Seerah\Models\SeerahEvent;
use App\Domain\Seerah\Models\SeerahEventTimelineItem;
use App\Domain\Seerah\Models\SeerahParticipant;
use App\Domain\Seerah\Models\SeerahParticipantTranslation;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Base class for Seerah event content seeders.
 *
 * Idempotent by design: events upsert on `slug`, translations on
 * (parent_id, locale), timeline items on (seerah_event_id, display_order),
 * and participants on their Arabic name within the event.
 */
abstract class SeerahEventSeederBase extends Seeder
{
    /**
     * The event definitions this seeder owns.
     *
     * @return array<int, array<string, mixed>>
     */
    abstract protected function events(): array;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        foreach ($this->events() as $eventDefinition) {
            DB::transaction(function () use ($eventDefinition) {
                $this->upsertEvent($eventDefinition);
            });
        }
    }

    /**
     * Upsert a single event with translations, timeline items and participants.
     *
     * @param  array<string, mixed>  $eventDefinition
     */
    private function upsertEvent(array $eventDefinition): void
    {
        $event = SeerahEvent::query()->updateOrCreate(
            ['slug' => $eventDefinition['slug']],
            [
                'event_type' => $eventDefinition['event_type'],
                'importance' => $eventDefinition['importance'],
                'era' => $eventDefinition['era'],
                'phase' => $eventDefinition['phase'],
                'hijri_date' => $eventDefinition['hijri_date'] ?? null,
                'hijri_year' => $eventDefinition['hijri_year'] ?? null,
                'hijri_month' => $eventDefinition['hijri_month'] ?? null,
                'hijri_day' => $eventDefinition['hijri_day'] ?? null,
                'gregorian_date' => $eventDefinition['gregorian_date'] ?? null,
                'gregorian_year' => $eventDefinition['gregorian_year'] ?? null,
                'location' => $eventDefinition['location'] ?? null,
                'latitude' => $eventDefinition['latitude'] ?? null,
                'longitude' => $eventDefinition['longitude'] ?? null,
                'statistics' => $eventDefinition['statistics'] ?? null,
                'display_order' => $eventDefinition['display_order'],
                'is_featured' => $eventDefinition['is_featured'] ?? false,
                'is_pivotal' => $eventDefinition['is_pivotal'] ?? false,
            ]
        );

        $this->upsertEventTranslations($event, $eventDefinition['translations']);
        $this->upsertTimelineItems($event, $eventDefinition['timeline'] ?? []);
        $this->upsertParticipants($event, $eventDefinition['participants'] ?? []);
    }

    /**
     * @param  array<string, array<string, string|null>>  $translations
     */
    private function upsertEventTranslations(SeerahEvent $event, array $translations): void
    {
        foreach ($translations as $locale => $fields) {
            $event->translations()->updateOrCreate(
                ['locale' => $locale],
                [
                    'title' => $fields['title'],
                    'description' => $fields['description'],
                    'content' => $fields['content'] ?? null,
                    'is_ready' => true,
                ]
            );
        }
    }

    /**
     * @param  array<int, array<string, array<string, string>>>  $timelineItems
     */
    private function upsertTimelineItems(SeerahEvent $event, array $timelineItems): void
    {
        foreach ($timelineItems as $index => $itemTranslations) {
            $item = SeerahEventTimelineItem::query()->updateOrCreate(
                [
                    'seerah_event_id' => $event->id,
                    'display_order' => ($index + 1) * 10,
                ],
                []
            );

            foreach ($itemTranslations as $locale => $fields) {
                $item->translations()->updateOrCreate(
                    ['locale' => $locale],
                    [
                        'title' => $fields['title'],
                        'description' => $fields['description'] ?? null,
                        'is_ready' => true,
                    ]
                );
            }
        }
    }

    /**
     * Participants have no natural key column, so the Arabic name within
     * the event acts as the identity for idempotent re-runs.
     *
     * @param  array<int, array<string, mixed>>  $participants
     */
    private function upsertParticipants(SeerahEvent $event, array $participants): void
    {
        foreach ($participants as $participantDefinition) {
            $arabicName = $participantDefinition['translations']['ar']['name'];

            $existingParticipantId = SeerahParticipantTranslation::query()
                ->select(['seerah_participant_translations.seerah_participant_id'])
                ->join(
                    'seerah_participants',
                    'seerah_participants.id',
                    '=',
                    'seerah_participant_translations.seerah_participant_id'
                )
                ->where('seerah_participants.event_id', $event->id)
                ->where('seerah_participant_translations.locale', 'ar')
                ->where('seerah_participant_translations.name', $arabicName)
                ->value('seerah_participant_translations.seerah_participant_id');

            $participant = $existingParticipantId !== null
                ? SeerahParticipant::query()->findOrFail($existingParticipantId)
                : new SeerahParticipant;

            $participant->fill([
                'event_id' => $event->id,
                'participant_type' => $participantDefinition['type'] ?? 'person',
                'role' => $participantDefinition['role'],
            ]);
            $participant->save();

            foreach ($participantDefinition['translations'] as $locale => $fields) {
                SeerahParticipantTranslation::query()->updateOrCreate(
                    [
                        'seerah_participant_id' => $participant->id,
                        'locale' => $locale,
                    ],
                    [
                        'name' => $fields['name'],
                        'description' => $fields['description'] ?? null,
                        'is_ready' => true,
                    ]
                );
            }
        }
    }
}
