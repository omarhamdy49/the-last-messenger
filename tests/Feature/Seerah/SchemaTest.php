<?php

declare(strict_types=1);

namespace Tests\Feature\Seerah;

use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * The Seerah schema, as built from scratch by the rewritten migrations.
 *
 * Column lists are asserted exactly, not with hasColumn(): the point of the 005
 * refactor is that nothing outside the target shape survives.
 */
class SchemaTest extends TestCase
{
    use RefreshDatabase;

    private const COLUMNS = [
        'seerah_milestones' => ['id', 'slug', 'order', 'metadata', 'hijri_from', 'hijri_to', 'gregorian_from', 'gregorian_to', 'is_active', 'created_at', 'updated_at'],
        'seerah_milestone_translations' => ['id', 'sm_id', 'locale', 'title', 'subtitle', 'description', 'is_ready', 'created_at', 'updated_at'],
        'seerah_event_types' => ['id', 'slug', 'metadata', 'locale', 'is_active', 'created_at', 'updated_at'],
        'seerah_events' => ['id', 'slug', 'event_type', 'sm_id', 'location_id', 'order', 'thumb_url', 'cover_url', 'hijri_year', 'hijri_month', 'hijri_day', 'gregorian_date', 'status', 'era', 'statistics', 'created_at', 'updated_at'],
        'seerah_event_translations' => ['id', 'event_id', 'locale', 'title', 'description', 'content', 'dispute_note', 'is_ready', 'metadata', 'sources', 'created_at', 'updated_at'],
        'seerah_events_gallery' => ['id', 'event_id', 'image', 'media_id', 'caption', 'order', 'created_at', 'updated_at'],
        'seerah_events_media' => ['id', 'event_id', 'media_id', 'created_at', 'updated_at'],
    ];

    public function test_each_seerah_table_has_exactly_the_target_columns(): void
    {
        foreach (self::COLUMNS as $table => $expected) {
            $actual = Schema::getColumnListing($table);
            sort($actual);
            sort($expected);

            $this->assertSame($expected, $actual, "Column drift on {$table}");
        }
    }

    public function test_removed_tables_do_not_exist(): void
    {
        foreach ([
            'seerah_participants',
            'seerah_participant_translations',
            'seerah_event_timeline_items',
            'seerah_event_timeline_item_translations',
            'seerah_event_type_translations',
        ] as $table) {
            $this->assertFalse(Schema::hasTable($table), "{$table} should be gone");
        }
    }

    public function test_translation_locale_is_unique_per_event(): void
    {
        $eventId = $this->insertEvent();
        DB::table('seerah_event_translations')->insert(['event_id' => $eventId, 'locale' => 'ar', 'title' => 'أ']);

        $this->expectException(QueryException::class);
        DB::table('seerah_event_translations')->insert(['event_id' => $eventId, 'locale' => 'ar', 'title' => 'ب']);
    }

    public function test_a_media_item_attaches_to_an_event_once(): void
    {
        $eventId = $this->insertEvent();
        $mediaId = $this->insertMedia();
        DB::table('seerah_events_media')->insert(['event_id' => $eventId, 'media_id' => $mediaId]);

        $this->expectException(QueryException::class);
        DB::table('seerah_events_media')->insert(['event_id' => $eventId, 'media_id' => $mediaId]);
    }

    public function test_deleting_an_event_cascades_to_its_children(): void
    {
        $eventId = $this->insertEvent();
        $mediaId = $this->insertMedia();
        DB::table('seerah_event_translations')->insert(['event_id' => $eventId, 'locale' => 'ar', 'title' => 'أ']);
        DB::table('seerah_events_gallery')->insert(['event_id' => $eventId, 'image' => 'x.jpg', 'media_id' => $mediaId]);
        DB::table('seerah_events_media')->insert(['event_id' => $eventId, 'media_id' => $mediaId]);

        DB::table('seerah_events')->where('id', $eventId)->delete();

        $this->assertSame(0, DB::table('seerah_event_translations')->count());
        $this->assertSame(0, DB::table('seerah_events_gallery')->count());
        $this->assertSame(0, DB::table('seerah_events_media')->count());
    }

    public function test_an_event_type_in_use_cannot_be_deleted(): void
    {
        $eventId = $this->insertEvent();
        $typeId = DB::table('seerah_events')->where('id', $eventId)->value('event_type');

        $this->expectException(QueryException::class);
        DB::table('seerah_event_types')->where('id', $typeId)->delete();
    }

    public function test_deleting_a_milestone_unlinks_its_events(): void
    {
        $milestoneId = DB::table('seerah_milestones')->insertGetId(['slug' => 'm1']);
        $eventId = $this->insertEvent(['sm_id' => $milestoneId]);

        DB::table('seerah_milestones')->where('id', $milestoneId)->delete();

        $this->assertNull(DB::table('seerah_events')->where('id', $eventId)->value('sm_id'));
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function insertEvent(array $attributes = []): int
    {
        $typeId = DB::table('seerah_event_types')->insertGetId([
            'slug' => 'type-'.uniqid(),
            'locale' => json_encode(['ar' => 'نوع']),
        ]);

        return DB::table('seerah_events')->insertGetId($attributes + [
            'slug' => 'event-'.uniqid(),
            'event_type' => $typeId,
        ]);
    }

    private function insertMedia(): int
    {
        return DB::table('media')->insertGetId([
            'filename' => 'a.jpg',
            'original_filename' => 'a.jpg',
            'path' => 'media/a.jpg',
            'mime_type' => 'image/jpeg',
            'size' => 1,
            'mediable_type' => 'x',
            'mediable_id' => 0,
        ]);
    }
}
