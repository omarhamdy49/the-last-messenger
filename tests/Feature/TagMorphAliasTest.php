<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Domain\Blog\Models\Tag;
use App\Models\Article;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Regression guard for the taggables morph-alias bug: rows written with the
 * FQCN instead of the 'article' morph alias are invisible to every tag
 * relation (fixed by the 2026_06_11_230000 normalization migration).
 */
class TagMorphAliasTest extends TestCase
{
    use RefreshDatabase;

    public function test_attaching_a_tag_writes_the_morph_alias_not_the_fqcn(): void
    {
        $article = Article::factory()->create();
        $tag = Tag::create(['slug' => 'morph-alias-tag']);

        $article->tags()->attach($tag->id);

        $this->assertDatabaseHas('taggables', [
            'taggable_id' => $article->id,
            'taggable_type' => 'article',
        ]);
        $this->assertDatabaseMissing('taggables', [
            'taggable_type' => Article::class,
        ]);
    }

    public function test_tag_relations_resolve_in_both_directions(): void
    {
        $article = Article::factory()->create();
        $tag = Tag::create(['slug' => 'both-directions-tag']);
        $article->tags()->attach($tag->id);

        $this->assertSame(1, Article::whereHas('tags')->count());
        $this->assertTrue($tag->articles()->pluck('articles.id')->contains($article->id));
    }

    public function test_legacy_fqcn_rows_are_invisible_until_normalized(): void
    {
        $article = Article::factory()->create();
        $tag = Tag::create(['slug' => 'legacy-fqcn-tag']);

        // Simulate a pre-morph-map legacy row exactly as found in production.
        DB::table('taggables')->insert([
            'taggable_id' => $article->id,
            'taggable_type' => Article::class,
            'tag_id' => $tag->id,
        ]);

        $this->assertSame(0, Article::whereHas('tags')->count());

        // The normalization the migration performs makes the row visible.
        DB::table('taggables')
            ->where('taggable_type', Article::class)
            ->update(['taggable_type' => 'article']);

        $this->assertSame(1, Article::whereHas('tags')->count());
    }
}
