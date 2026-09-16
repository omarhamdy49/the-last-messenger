<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Domain\Seerah\Models\SeerahEvent;
use App\Domain\Seerah\Models\SeerahMilestone;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SeerahRichTextDescriptionTest extends TestCase
{
    use RefreshDatabase;

    public function test_event_and_era_descriptions_preserve_formatting_and_remove_unsafe_html_on_save(): void
    {
        $this->actingAs($this->createAdminUser());

        foreach (['seerah-events' => SeerahEvent::class, 'seerah-milestones' => SeerahMilestone::class] as $resource => $model) {
            $this->post(route("admin.{$resource}.store"), [
                'slug' => 'formatted-description',
                'title' => ['ar' => 'عنوان للتجربة'],
                'description' => ['ar' => '<p>وصف <strong>منسق</strong></p><script>alert("unsafe")</script>'],
            ])->assertRedirect(route("admin.{$resource}.index"));

            $record = $model::query()->where('slug', 'formatted-description')->firstOrFail();
            $this->assertSame('<p>وصف <strong>منسق</strong></p>', $record->getTranslation('description', 'ar', false));
            $this->get(route("admin.{$resource}.edit", $record->id))->assertOk()
                ->assertSee('name="description[ar]"', false)
                ->assertSee('&lt;strong&gt;منسق&lt;/strong&gt;', false);

            $this->put(route("admin.{$resource}.update", $record->id), [
                'description' => ['ar' => '<ul><li>تفصيل جديد</li></ul><img src="invalid" onerror="alert(1)">'],
            ])->assertRedirect(route("admin.{$resource}.index"));

            $description = $record->fresh()->getTranslation('description', 'ar', false);
            $this->assertStringContainsString('<ul><li>تفصيل جديد</li></ul>', $description);
            $this->assertStringNotContainsString('onerror', $description);
        }
    }
}
