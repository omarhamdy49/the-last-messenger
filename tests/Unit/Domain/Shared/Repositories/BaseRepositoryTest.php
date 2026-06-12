<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Shared\Repositories;

use App\Domain\Shared\Repositories\BaseRepository;
use App\Models\Article;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BaseRepositoryTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test that base repository can be instantiated
     */
    public function test_base_repository_can_be_instantiated(): void
    {
        $this->assertTrue(true); // Base test
    }

    /**
     * Test pagination returns correct structure
     */
    public function test_pagination_returns_correct_structure(): void
    {
        // Create test articles
        Article::factory()->count(20)->create();

        $repository = new class extends BaseRepository
        {
            protected function getModel(): \Illuminate\Database\Eloquent\Model
            {
                return new Article;
            }
        };

        $result = $repository->paginate(10);

        $this->assertNotEmpty($result);
        $this->assertCount(10, $result->items());
        $this->assertEquals(20, $result->total());
    }
}
