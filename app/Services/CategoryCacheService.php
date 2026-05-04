<?php

namespace App\Services;

use Illuminate\Cache\CacheManager;

readonly class CategoryCacheService
{
    public function __construct(private CacheManager $cache) {}

    public function remember(int $userId, \Closure $callback): array
    {
        return $this->cache->remember($this->getKey($userId), 3600, $callback);
    }

    public function getKey(int $userId): string
    {
        return 'categories.user.'.$userId;
    }

    public function clear(int $id): bool
    {
        return $this->cache->forget($this->getKey($id));
    }
}
