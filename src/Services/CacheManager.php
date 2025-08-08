<?php

declare(strict_types=1);

namespace OnaOnbir\OOSettings\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use OnaOnbir\OOSettings\Contracts\CacheManagerContract;

/**
 * Advanced cache manager for OOSettings with tagging, TTL, and invalidation strategies.
 * 
 * Provides high-performance caching with intelligent invalidation and warming capabilities.
 */
class CacheManager implements CacheManagerContract
{
    /**
     * Cache store instance.
     */
    protected $cache;

    /**
     * Cache key prefix.
     */
    protected string $prefix;

    /**
     * Default TTL in seconds.
     */
    protected int $defaultTtl;

    /**
     * Whether cache is enabled.
     */
    protected bool $enabled;

    /**
     * Cache statistics.
     *
     * @var array<string, int>
     */
    protected array $stats = [
        'hits' => 0,
        'misses' => 0,
        'writes' => 0,
        'deletes' => 0,
        'flushes' => 0,
    ];

    /**
     * Create a new cache manager.
     */
    public function __construct()
    {
        $this->cache = Cache::store(config('oo-settings.cache.store'));
        $this->prefix = config('oo-settings.cache.prefix', 'oo_settings');
        $this->defaultTtl = config('oo-settings.cache.default_ttl', 3600);
        $this->enabled = config('oo-settings.cache.enabled', true);
    }

    /**
     * {@inheritdoc}
     */
    public function get(string $key, mixed $default = null): mixed
    {
        if (!$this->enabled) {
            return $default;
        }

        try {
            $cacheKey = $this->buildKey($key);
            $value = $this->cache->get($cacheKey, $default);
            
            if ($value !== $default) {
                $this->stats['hits']++;
            } else {
                $this->stats['misses']++;
            }
            
            return $value;
        } catch (\Throwable $e) {
            Log::warning('Cache get operation failed', [
                'key' => $key,
                'error' => $e->getMessage(),
            ]);
            
            return $default;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function put(string $key, mixed $value, ?int $ttl = null): bool
    {
        if (!$this->enabled) {
            return true;
        }

        try {
            $cacheKey = $this->buildKey($key);
            $cacheTtl = $ttl ?? $this->defaultTtl;
            
            $tags = $this->extractTags($key);
            
            if (!empty($tags) && method_exists($this->cache, 'tags')) {
                $result = $this->cache->tags($tags)->put($cacheKey, $value, $cacheTtl);
            } else {
                $result = $this->cache->put($cacheKey, $value, $cacheTtl);
            }
            
            if ($result) {
                $this->stats['writes']++;
            }
            
            return $result;
        } catch (\Throwable $e) {
            Log::warning('Cache put operation failed', [
                'key' => $key,
                'error' => $e->getMessage(),
            ]);
            
            return false;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function has(string $key): bool
    {
        if (!$this->enabled) {
            return false;
        }

        try {
            return $this->cache->has($this->buildKey($key));
        } catch (\Throwable $e) {
            Log::warning('Cache has operation failed', [
                'key' => $key,
                'error' => $e->getMessage(),
            ]);
            
            return false;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function forget(string $key): bool
    {
        if (!$this->enabled) {
            return true;
        }

        try {
            $result = $this->cache->forget($this->buildKey($key));
            
            if ($result) {
                $this->stats['deletes']++;
            }
            
            return $result;
        } catch (\Throwable $e) {
            Log::warning('Cache forget operation failed', [
                'key' => $key,
                'error' => $e->getMessage(),
            ]);
            
            return false;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function flush(array $tags = []): bool
    {
        if (!$this->enabled) {
            return true;
        }

        try {
            if (empty($tags)) {
                // Flush all OOSettings cache
                $result = $this->cache->flush();
            } elseif (method_exists($this->cache, 'tags')) {
                // Flush specific tags
                $result = $this->cache->tags($tags)->flush();
            } else {
                // Fallback: clear entire cache if tagging not supported
                $result = $this->cache->flush();
            }
            
            if ($result) {
                $this->stats['flushes']++;
            }
            
            return $result;
        } catch (\Throwable $e) {
            Log::warning('Cache flush operation failed', [
                'tags' => $tags,
                'error' => $e->getMessage(),
            ]);
            
            return false;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function globalKey(string $key): string
    {
        return "global:{$key}";
    }

    /**
     * {@inheritdoc}
     */
    public function modelKey(string $modelClass, int|string $modelId, string $key): string
    {
        return "model:{$modelClass}:{$modelId}:{$key}";
    }

    /**
     * {@inheritdoc}
     */
    public function globalTags(): array
    {
        return ['oo_settings', 'global'];
    }

    /**
     * {@inheritdoc}
     */
    public function modelTags(string $modelClass, int|string $modelId): array
    {
        return [
            'oo_settings',
            'model',
            "model:{$modelClass}",
            "model:{$modelClass}:{$modelId}",
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function invalidateGlobal(): bool
    {
        return $this->flush($this->globalTags());
    }

    /**
     * {@inheritdoc}
     */
    public function invalidateModel(string $modelClass, int|string $modelId): bool
    {
        return $this->flush($this->modelTags($modelClass, $modelId));
    }

    /**
     * {@inheritdoc}
     */
    public function warmUp(array $data): bool
    {
        if (!$this->enabled) {
            return true;
        }

        $success = true;
        
        foreach ($data as $key => $value) {
            if (!$this->put($key, $value)) {
                $success = false;
            }
        }
        
        return $success;
    }

    /**
     * {@inheritdoc}
     */
    public function stats(): array
    {
        return array_merge($this->stats, [
            'enabled' => $this->enabled,
            'store' => config('oo-settings.cache.store'),
            'prefix' => $this->prefix,
            'default_ttl' => $this->defaultTtl,
            'hit_ratio' => $this->calculateHitRatio(),
        ]);
    }

    /**
     * Build cache key with prefix.
     */
    protected function buildKey(string $key): string
    {
        return "{$this->prefix}:{$key}";
    }

    /**
     * Extract cache tags from key.
     *
     * @return array<string>
     */
    protected function extractTags(string $key): array
    {
        if (str_starts_with($key, 'global:')) {
            return $this->globalTags();
        }
        
        if (str_starts_with($key, 'model:')) {
            $parts = explode(':', $key);
            if (count($parts) >= 4) {
                return $this->modelTags($parts[1], $parts[2]);
            }
        }
        
        return ['oo_settings'];
    }

    /**
     * Calculate cache hit ratio.
     */
    protected function calculateHitRatio(): float
    {
        $total = $this->stats['hits'] + $this->stats['misses'];
        
        if ($total === 0) {
            return 0.0;
        }
        
        return round(($this->stats['hits'] / $total) * 100, 2);
    }
}
