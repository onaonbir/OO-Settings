<?php

declare(strict_types=1);

namespace OnaOnbir\OOSettings\Contracts;

/**
 * Cache Manager Contract for settings caching operations.
 *
 * Provides a standardized interface for caching settings data
 * with support for tagging, TTL, and invalidation strategies.
 */
interface CacheManagerContract
{
    /**
     * Get a value from cache.
     *
     * @param  string  $key  Cache key
     * @param  mixed  $default  Default value if not found
     * @return mixed Cached value or default
     */
    public function get(string $key, mixed $default = null): mixed;

    /**
     * Put a value in cache.
     *
     * @param  string  $key  Cache key
     * @param  mixed  $value  Value to cache
     * @param  int|null  $ttl  Time to live in seconds (null for default)
     * @return bool True if successful
     */
    public function put(string $key, mixed $value, ?int $ttl = null): bool;

    /**
     * Check if a key exists in cache.
     *
     * @param  string  $key  Cache key
     * @return bool True if exists
     */
    public function has(string $key): bool;

    /**
     * Remove a key from cache.
     *
     * @param  string  $key  Cache key
     * @return bool True if successful
     */
    public function forget(string $key): bool;

    /**
     * Clear cache by tags.
     *
     * @param  array<string>  $tags  Cache tags
     * @return bool True if successful
     */
    public function flush(array $tags = []): bool;

    /**
     * Generate cache key for global settings.
     *
     * @param  string  $key  Setting key
     * @return string Cache key
     */
    public function globalKey(string $key): string;

    /**
     * Generate cache key for model settings.
     *
     * @param  string  $modelClass  Model class name
     * @param  int|string  $modelId  Model ID
     * @param  string  $key  Setting key
     * @return string Cache key
     */
    public function modelKey(string $modelClass, int|string $modelId, string $key): string;

    /**
     * Get cache tags for global settings.
     *
     * @return array<string> Cache tags
     */
    public function globalTags(): array;

    /**
     * Get cache tags for model settings.
     *
     * @param  string  $modelClass  Model class name
     * @param  int|string  $modelId  Model ID
     * @return array<string> Cache tags
     */
    public function modelTags(string $modelClass, int|string $modelId): array;

    /**
     * Invalidate all global settings cache.
     *
     * @return bool True if successful
     */
    public function invalidateGlobal(): bool;

    /**
     * Invalidate model settings cache.
     *
     * @param  string  $modelClass  Model class name
     * @param  int|string  $modelId  Model ID
     * @return bool True if successful
     */
    public function invalidateModel(string $modelClass, int|string $modelId): bool;

    /**
     * Warm up cache with fresh data.
     *
     * @param  array<string, mixed>  $data  Data to warm up
     * @return bool True if successful
     */
    public function warmUp(array $data): bool;

    /**
     * Get cache statistics.
     *
     * @return array<string, mixed> Cache statistics
     */
    public function stats(): array;
}
