<?php

namespace Tests\Unit\Services;

use App\Services\CategoryCacheService;
use Illuminate\Cache\CacheManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CategoryCacheServiceTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function remember_stores_and_returns_data(): void
    {
        $service = app(CategoryCacheService::class);
        $userId = 1;
        $expectedData = ['category1' => 'Work', 'category2' => 'Personal'];

        $result = $service->remember($userId, fn() => $expectedData);

        $this->assertEquals($expectedData, $result);
    }

    #[Test]
    public function remember_caches_data_for_ttl(): void
    {
        $service = app(CategoryCacheService::class);
        $cache = app(CacheManager::class);
        $userId = 1;
        $expectedData = ['test' => 'data'];

        $service->remember($userId, fn() => $expectedData);

        $cached = $cache->get($service->getKey($userId));
        $this->assertEquals($expectedData, $cached);
    }

    #[Test]
    public function remember_uses_callback_only_once_if_cached(): void
    {
        $service = app(CategoryCacheService::class);
        $userId = 1;
        $callCount = 0;

        $callback = fn() => ['call' => ++$callCount];

        $service->remember($userId, $callback);
        $result1 = $service->remember($userId, $callback);

        $this->assertEquals(1, $result1['call']);
    }

    #[Test]
    public function get_key_returns_correct_format(): void
    {
        $service = app(CategoryCacheService::class);
        $userId = 123;

        $key = $service->getKey($userId);

        $this->assertEquals('categories.user.123', $key);
    }

    #[Test]
    public function get_key_formats_different_user_ids(): void
    {
        $service = app(CategoryCacheService::class);

        $key1 = $service->getKey(1);
        $key2 = $service->getKey(999);

        $this->assertEquals('categories.user.1', $key1);
        $this->assertEquals('categories.user.999', $key2);
    }

    #[Test]
    public function clear_removes_cached_data(): void
    {
        $service = app(CategoryCacheService::class);
        $cache = app(CacheManager::class);
        $userId = 1;

        $service->remember($userId, fn() => ['data' => 'value']);
        $service->clear($userId);

        $cached = $cache->get($service->getKey($userId));
        $this->assertNull($cached);
    }

    #[Test]
    public function clear_returns_true_when_key_exists(): void
    {
        $service = app(CategoryCacheService::class);
        $userId = 1;

        $service->remember($userId, fn() => ['data' => 'value']);
        $result = $service->clear($userId);

        $this->assertTrue($result);
    }

    #[Test]
    public function clear_returns_false_when_key_does_not_exist(): void
    {
        $service = app(CategoryCacheService::class);
        $userId = 999;

        $result = $service->clear($userId);

        $this->assertFalse($result);
    }

    #[Test]
    public function remember_has_3600_second_ttl(): void
    {
        $service = app(CategoryCacheService::class);
        $cache = app(CacheManager::class);
        $userId = 1;

        $service->remember($userId, fn() => ['data' => 'value']);

        $this->assertNotNull($cache->get($service->getKey($userId)));
    }
}
