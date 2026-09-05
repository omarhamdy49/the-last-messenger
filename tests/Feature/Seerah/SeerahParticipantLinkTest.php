<?php

declare(strict_types=1);

namespace Tests\Feature\Seerah;

use App\Domain\People\Models\Muslim;
use App\Domain\Seerah\Models\SeerahEvent;
use App\Domain\Seerah\Models\SeerahParticipant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * `seerah_participants.participant_id` is the only link between an event and a real
 * person record. The admin form rendered name/role/description alone, so the field was
 * never posted: all 1,193 participants on production are unlinked. Worse, the edit form
 * rebuilt its rows without the id, so saving an event that DID carry a link posted a
 * null over it — the same shape of bug that blanks lessons and sources.
 *
 * Driven over HTTP on purpose: the risk is not only the service (which always handled
 * the column) but the request layer, since `validated()` drops any key the rules do not
 * name. This asserts the whole path holds.
 */
class SeerahParticipantLinkTest extends TestCase
{
    use RefreshDatabase;

    /** @return array<string, mixed> */
    private function payload(SeerahEvent $event, array $participants): array
    {
        return [
            'title' => ['ar' => $event->getTranslation('title', 'ar') ?: 'حدث'],
            'seerah_milestone_id' => $event->seerah_milestone_id,
            'seerah_event_type_id' => $event->seerah_event_type_id,
            'status' => 'draft',
            'importance' => 'medium',
            'display_order' => 10,
            'participants' => $participants,
        ];
    }

    /** @return array<int, array<string, mixed>> */
    private function row(?int $participantId, string $name = 'أبو بكر الصديق'): array
    {
        $row = [
            'name' => ['ar' => $name],
            'description' => ['ar' => 'دوره في هذا الحدث'],
            'role' => 'companion',
        ];

        if ($participantId !== null) {
            $row['participant_id'] = $participantId;
        }

        return [$row];
    }

    private function update(SeerahEvent $event, array $participants): void
    {
        $this->actingAs($this->createAdminUser(role: 'super_admin'), 'admin')
            ->put(route('admin.seerah-events.update', $event->id), $this->payload($event, $participants))
            ->assertSessionHasNoErrors();
    }

    public function test_a_participant_can_be_linked_to_a_muslim_record(): void
    {
        $event = SeerahEvent::factory()->create();
        $person = Muslim::factory()->create();

        $this->update($event, $this->row($person->id));

        $participant = SeerahParticipant::where('event_id', $event->id)->firstOrFail();

        $this->assertSame($person->id, $participant->participant_id);
        $this->assertSame('person', $participant->participant_type);
    }

    /**
     * The regression that mattered: an editor fixing a typo in the description must not
     * unlink the person. This passes only because the form now carries the id back.
     */
    public function test_resaving_a_row_that_carries_the_id_keeps_the_link(): void
    {
        $event = SeerahEvent::factory()->create();
        $person = Muslim::factory()->create();

        $this->update($event, $this->row($person->id));
        $this->update($event, $this->row($person->id, 'أبو بكر عبد الله بن أبي قحافة'));

        $participant = SeerahParticipant::where('event_id', $event->id)->firstOrFail();

        $this->assertSame($person->id, $participant->participant_id, 'the link was dropped on re-save');
        $this->assertSame('أبو بكر عبد الله بن أبي قحافة', $participant->getTranslation('name', 'ar'));
    }

    /**
     * Free-text participants stay legitimate — tribes and unnamed groups have no record
     * to point at. A row with no key simply stores a null link.
     */
    public function test_a_row_posted_without_the_key_stores_a_null_link(): void
    {
        $event = SeerahEvent::factory()->create();

        $this->update($event, $this->row(null, 'جرهم'));

        $this->assertNull(SeerahParticipant::where('event_id', $event->id)->firstOrFail()->participant_id);
    }

    /**
     * The half the HTTP tests above cannot prove: they post the id explicitly, so they pass
     * against the old form too. The bug lived in the view — `$oldParticipants` rebuilt each
     * row from name/description/role only, so the id was gone before the browser ever saw
     * it. This asserts the edit page actually ships it back.
     */
    public function test_the_edit_form_carries_an_existing_link_back_into_the_row(): void
    {
        $event = SeerahEvent::factory()->create();
        $person = Muslim::factory()->create();

        $this->update($event, $this->row($person->id));

        $html = $this->actingAs($this->createAdminUser(role: 'super_admin'), 'admin')
            ->get(route('admin.seerah-events.edit', $event->id))
            ->assertOk()
            ->getContent();

        // The repeater seeds its rows from a JSON blob in x-data; the id must be in it.
        $this->assertMatchesRegularExpression(
            '/participant_id[^,}]{0,20}'.$person->id.'/u',
            $html,
            'the edit form did not carry participant_id into the participant row'
        );

        // …and there must be an input that posts it back.
        $this->assertStringContainsString('participants[${index}][participant_id]', $html);
    }

    public function test_an_unknown_person_id_is_rejected_rather_than_stored(): void
    {
        $event = SeerahEvent::factory()->create();

        $this->actingAs($this->createAdminUser(role: 'super_admin'), 'admin')
            ->put(route('admin.seerah-events.update', $event->id), $this->payload($event, $this->row(999999)))
            ->assertSessionHasErrors('participants.0.participant_id');
    }
}
