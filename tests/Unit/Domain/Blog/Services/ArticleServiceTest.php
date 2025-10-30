<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Blog\Services;

use App\Domain\Blog\Repositories\ArticleRepositoryInterface;
use App\Domain\Blog\Services\ArticleService;
use App\Exceptions\Domain\ResourceNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class ArticleServiceTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test getting article by slug throws exception when not found
     */
    public function test_get_article_by_slug_throws_exception_when_not_found(): void
    {
        $mockRepository = Mockery::mock(ArticleRepositoryInterface::class);
        $mockRepository->shouldReceive('findBySlug')
            ->once()
            ->with('non-existent-slug')
            ->andReturn(null);

        $service = new ArticleService($mockRepository);

        $this->expectException(ResourceNotFoundException::class);
        $service->getArticleBySlug('non-existent-slug');
    }

    /**
     * Test incrementing views
     */
    public function test_incrementing_views(): void
    {
        $this->markTestSkipped('Requires full implementation');
    }
}
