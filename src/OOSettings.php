<?php

declare(strict_types=1);

namespace OnaOnbir\OOSettings;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use OnaOnbir\OOSettings\Contracts\CacheManagerContract;
use OnaOnbir\OOSettings\Contracts\SettingsContract;
use OnaOnbir\OOSettings\Contracts\ValidationServiceContract;
use OnaOnbir\OOSettings\Events\SettingChanged;
use OnaOnbir\OOSettings\Events\SettingChanging;
use OnaOnbir\OOSettings\Events\SettingDeleted;
use OnaOnbir\OOSettings\Events\SettingDeleting;
use OnaOnbir\OOSettings\Repositories\SettingRepository;

/**
 * Advanced OOSettings service with caching, validation, and event support.
 *
 * This production-ready implementation provides:
 * - High-performance caching
 * - Comprehensive validation
 * - Event-driven architecture
 * - Bulk operations
 * - Error handling and logging
 * - Type safety
 */
class OOSettings implements SettingsContract
{
    /**
     * Setting repository.
     */
    protected SettingRepository $repository;

    /**
     * Cache manager.
     */
    protected CacheManagerContract $cache;

    /**
     * Validation service.
     */
    protected ValidationServiceContract $validator;

    /**
     * Whether events are enabled.
     */
    protected bool $eventsEnabled;

    /**
     * Whether logging is enabled.
     */
    protected bool $loggingEnabled;

    /**
     * Create a new OOSettings instance.
     */
    public function __construct(
        SettingRepository $repository,
        CacheManagerContract $cache,
        ValidationServiceContract $validator
    ) {
        $this->repository = $repository;
        $this->cache = $cache;
        $this->validator = $validator;
        $this->eventsEnabled = config('oo-settings.events.enabled', true);
        $this->loggingEnabled = config('oo-settings.logging.enabled', true);
    }

    /**
     * {@inheritdoc}
     */
    public function get(string $key, mixed $default = null): mixed
    {
        try {
            $this->validator->validateKey($key);

            [$mainKey, $nestedKey] = $this->splitKey($key);

            // Try cache first
            $cacheKey = $this->cache->globalKey($mainKey);
            $cachedValue = $this->cache->get($cacheKey);

            if ($cachedValue !== null) {
                return $this->extractNestedValue($cachedValue, $nestedKey, $default);
            }

            // Fallback to database
            $setting = $this->repository->findGlobal($mainKey);

            if (! $setting) {
                return $default;
            }

            // Cache the value
            $this->cache->put($cacheKey, $setting->value);

            return $this->extractNestedValue($setting->value, $nestedKey, $default);

        } catch (\Throwable $e) {
            $this->logError('get', $key, $e);

            return $default;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function set(string $key, mixed $value, ?string $name = null, ?string $description = null): bool
    {
        try {
            $this->validator->validateKey($key);
            $this->validator->validateValue($value);

            [$mainKey, $nestedKey] = $this->splitKey($key);

            // Get old value for events
            $oldSetting = $this->repository->findGlobal($mainKey);
            $oldValue = $oldSetting ? $this->extractNestedValue($oldSetting->value, $nestedKey) : null;

            // Fire changing event
            if ($this->eventsEnabled) {
                $event = new SettingChanging($key, $value, $oldValue, null, [
                    'name' => $name,
                    'description' => $description,
                ]);

                Event::dispatch($event);

                if ($event->isCancelled()) {
                    $this->logInfo('set_cancelled', $key, [
                        'reason' => $event->getCancellationReason(),
                    ]);

                    return false;
                }
            }

            // Prepare new value
            $newValue = $this->prepareNestedValue($oldSetting?->value, $nestedKey, $value);

            // Save to database
            $setting = $this->repository->createOrUpdateGlobal($mainKey, $newValue, $name, $description);

            // Update cache
            $cacheKey = $this->cache->globalKey($mainKey);
            $this->cache->put($cacheKey, $newValue);

            // Fire changed event
            if ($this->eventsEnabled) {
                Event::dispatch(new SettingChanged($key, $value, $oldValue, null, [
                    'setting_id' => $setting->id,
                    'name' => $name,
                    'description' => $description,
                ]));
            }

            $this->logInfo('set_success', $key, [
                'old_value' => $oldValue,
                'new_value' => $value,
            ]);

            return true;

        } catch (\Throwable $e) {
            $this->logError('set', $key, $e, [
                'value' => $value,
                'name' => $name,
                'description' => $description,
            ]);

            return false;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function forget(string $key): bool
    {
        try {
            $this->validator->validateKey($key);

            [$mainKey, $nestedKey] = $this->splitKey($key);

            $setting = $this->repository->findGlobal($mainKey);

            if (! $setting) {
                return false;
            }

            $oldValue = $this->extractNestedValue($setting->value, $nestedKey);

            // Fire deleting event
            if ($this->eventsEnabled) {
                $event = new SettingDeleting($key, $oldValue);
                Event::dispatch($event);

                if ($event->isCancelled()) {
                    $this->logInfo('forget_cancelled', $key, [
                        'reason' => $event->getCancellationReason(),
                    ]);

                    return false;
                }
            }

            $success = false;

            if ($nestedKey === null) {
                // Delete entire setting
                $success = $this->repository->deleteGlobal($mainKey);

                // Remove from cache
                if ($success) {
                    $cacheKey = $this->cache->globalKey($mainKey);
                    $this->cache->forget($cacheKey);
                }
            } else {
                // Remove nested key
                $data = is_array($setting->value) ? $setting->value : [];
                Arr::forget($data, $nestedKey);

                $newSetting = $this->repository->createOrUpdateGlobal($mainKey, $data);
                $success = $newSetting !== null;

                // Update cache
                if ($success) {
                    $cacheKey = $this->cache->globalKey($mainKey);
                    $this->cache->put($cacheKey, $data);
                }
            }

            // Fire deleted event
            if ($success && $this->eventsEnabled) {
                Event::dispatch(new SettingDeleted($key, $oldValue));
            }

            $this->logInfo('forget_success', $key, [
                'old_value' => $oldValue,
            ]);

            return $success;

        } catch (\Throwable $e) {
            $this->logError('forget', $key, $e);

            return false;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function has(string $key): bool
    {
        try {
            $this->validator->validateKey($key);

            [$mainKey, $nestedKey] = $this->splitKey($key);

            // Check cache first
            $cacheKey = $this->cache->globalKey($mainKey);
            $cachedValue = $this->cache->get($cacheKey);

            if ($cachedValue !== null) {
                return $nestedKey === null || Arr::has($cachedValue, $nestedKey);
            }

            // Check database
            if (! $this->repository->existsGlobal($mainKey)) {
                return false;
            }

            if ($nestedKey === null) {
                return true;
            }

            $setting = $this->repository->findGlobal($mainKey);

            return $setting && Arr::has($setting->value, $nestedKey);

        } catch (\Throwable $e) {
            $this->logError('has', $key, $e);

            return false;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function all(): array
    {
        try {
            $settings = $this->repository->getAllGlobal();

            $result = [];
            foreach ($settings as $setting) {
                $result[$setting->key] = $setting->value;
            }

            return $result;

        } catch (\Throwable $e) {
            $this->logError('all', '', $e);

            return [];
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getForModel(Model $model, string $key, mixed $default = null): mixed
    {
        try {
            $this->validator->validateKey($key);

            [$mainKey, $nestedKey] = $this->splitKey($key);

            // Try cache first
            $cacheKey = $this->cache->modelKey(get_class($model), $model->getKey(), $mainKey);
            $cachedValue = $this->cache->get($cacheKey);

            if ($cachedValue !== null) {
                return $this->extractNestedValue($cachedValue, $nestedKey, $default);
            }

            // Fallback to database
            $setting = $this->repository->findForModel($model, $mainKey);

            if (! $setting) {
                return $default;
            }

            // Cache the value
            $this->cache->put($cacheKey, $setting->value);

            return $this->extractNestedValue($setting->value, $nestedKey, $default);

        } catch (\Throwable $e) {
            $this->logError('get_for_model', $key, $e, ['model' => get_class($model)]);

            return $default;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function setForModel(Model $model, string $key, mixed $value, ?string $name = null, ?string $description = null): bool
    {
        try {
            $this->validator->validateKey($key);
            $this->validator->validateValue($value);

            [$mainKey, $nestedKey] = $this->splitKey($key);

            // Get old value for events
            $oldSetting = $this->repository->findForModel($model, $mainKey);
            $oldValue = $oldSetting ? $this->extractNestedValue($oldSetting->value, $nestedKey) : null;

            // Fire changing event
            if ($this->eventsEnabled) {
                $event = new SettingChanging($key, $value, $oldValue, $model, [
                    'name' => $name,
                    'description' => $description,
                ]);

                Event::dispatch($event);

                if ($event->isCancelled()) {
                    $this->logInfo('set_for_model_cancelled', $key, [
                        'reason' => $event->getCancellationReason(),
                        'model' => get_class($model),
                    ]);

                    return false;
                }
            }

            // Prepare new value
            $newValue = $this->prepareNestedValue($oldSetting?->value, $nestedKey, $value);

            // Save to database
            $setting = $this->repository->createOrUpdateForModel($model, $mainKey, $newValue, $name, $description);

            // Update cache
            $cacheKey = $this->cache->modelKey(get_class($model), $model->getKey(), $mainKey);
            $this->cache->put($cacheKey, $newValue);

            // Fire changed event
            if ($this->eventsEnabled) {
                Event::dispatch(new SettingChanged($key, $value, $oldValue, $model, [
                    'setting_id' => $setting->id,
                    'name' => $name,
                    'description' => $description,
                ]));
            }

            $this->logInfo('set_for_model_success', $key, [
                'old_value' => $oldValue,
                'new_value' => $value,
                'model' => get_class($model),
            ]);

            return true;

        } catch (\Throwable $e) {
            $this->logError('set_for_model', $key, $e, [
                'value' => $value,
                'model' => get_class($model),
                'name' => $name,
                'description' => $description,
            ]);

            return false;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function forgetForModel(Model $model, string $key): bool
    {
        try {
            $this->validator->validateKey($key);

            [$mainKey, $nestedKey] = $this->splitKey($key);

            $setting = $this->repository->findForModel($model, $mainKey);

            if (! $setting) {
                return false;
            }

            $oldValue = $this->extractNestedValue($setting->value, $nestedKey);

            // Fire deleting event
            if ($this->eventsEnabled) {
                $event = new SettingDeleting($key, $oldValue, $model);
                Event::dispatch($event);

                if ($event->isCancelled()) {
                    $this->logInfo('forget_for_model_cancelled', $key, [
                        'reason' => $event->getCancellationReason(),
                        'model' => get_class($model),
                    ]);

                    return false;
                }
            }

            $success = false;

            if ($nestedKey === null) {
                // Delete entire setting
                $success = $this->repository->deleteForModel($model, $mainKey);

                // Remove from cache
                if ($success) {
                    $cacheKey = $this->cache->modelKey(get_class($model), $model->getKey(), $mainKey);
                    $this->cache->forget($cacheKey);
                }
            } else {
                // Remove nested key
                $data = is_array($setting->value) ? $setting->value : [];
                Arr::forget($data, $nestedKey);

                $newSetting = $this->repository->createOrUpdateForModel($model, $mainKey, $data);
                $success = $newSetting !== null;

                // Update cache
                if ($success) {
                    $cacheKey = $this->cache->modelKey(get_class($model), $model->getKey(), $mainKey);
                    $this->cache->put($cacheKey, $data);
                }
            }

            // Fire deleted event
            if ($success && $this->eventsEnabled) {
                Event::dispatch(new SettingDeleted($key, $oldValue, $model));
            }

            $this->logInfo('forget_for_model_success', $key, [
                'old_value' => $oldValue,
                'model' => get_class($model),
            ]);

            return $success;

        } catch (\Throwable $e) {
            $this->logError('forget_for_model', $key, $e, ['model' => get_class($model)]);

            return false;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function hasForModel(Model $model, string $key): bool
    {
        try {
            $this->validator->validateKey($key);

            [$mainKey, $nestedKey] = $this->splitKey($key);

            // Check cache first
            $cacheKey = $this->cache->modelKey(get_class($model), $model->getKey(), $mainKey);
            $cachedValue = $this->cache->get($cacheKey);

            if ($cachedValue !== null) {
                return $nestedKey === null || Arr::has($cachedValue, $nestedKey);
            }

            // Check database
            if (! $this->repository->existsForModel($model, $mainKey)) {
                return false;
            }

            if ($nestedKey === null) {
                return true;
            }

            $setting = $this->repository->findForModel($model, $mainKey);

            return $setting && Arr::has($setting->value, $nestedKey);

        } catch (\Throwable $e) {
            $this->logError('has_for_model', $key, $e, ['model' => get_class($model)]);

            return false;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function allForModel(Model $model): array
    {
        try {
            $settings = $this->repository->getAllForModel($model);

            $result = [];
            foreach ($settings as $setting) {
                $result[$setting->key] = $setting->value;
            }

            return $result;

        } catch (\Throwable $e) {
            $this->logError('all_for_model', '', $e, ['model' => get_class($model)]);

            return [];
        }
    }

    /**
     * {@inheritdoc}
     */
    public function setMany(array $settings, ?string $name = null, ?string $description = null): bool
    {
        try {
            $this->validator->validateMany($settings);

            return $this->repository->bulkCreateOrUpdateGlobal($settings, $name, $description);

        } catch (\Throwable $e) {
            $this->logError('set_many', '', $e, ['settings_count' => count($settings)]);

            return false;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function setManyForModel(Model $model, array $settings, ?string $name = null, ?string $description = null): bool
    {
        try {
            $this->validator->validateMany($settings);

            return $this->repository->bulkCreateOrUpdateForModel($model, $settings, $name, $description);

        } catch (\Throwable $e) {
            $this->logError('set_many_for_model', '', $e, [
                'model' => get_class($model),
                'settings_count' => count($settings),
            ]);

            return false;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function clear(): bool
    {
        try {
            $success = $this->repository->deleteAllGlobal();

            if ($success) {
                $this->cache->invalidateGlobal();
            }

            $this->logInfo('clear_success', '', ['cleared_global' => true]);

            return $success;

        } catch (\Throwable $e) {
            $this->logError('clear', '', $e);

            return false;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function clearForModel(Model $model): bool
    {
        try {
            $success = $this->repository->deleteAllForModel($model);

            if ($success) {
                $this->cache->invalidateModel(get_class($model), $model->getKey());
            }

            $this->logInfo('clear_for_model_success', '', ['model' => get_class($model)]);

            return $success;

        } catch (\Throwable $e) {
            $this->logError('clear_for_model', '', $e, ['model' => get_class($model)]);

            return false;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function clearCache(): bool
    {
        try {
            return $this->cache->flush();
        } catch (\Throwable $e) {
            $this->logError('clear_cache', '', $e);

            return false;
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Internal Utilities
    |--------------------------------------------------------------------------
    */

    /**
     * Split a key into main key and nested key.
     *
     * @return array{0: string, 1: string|null}
     */
    protected function splitKey(string $key): array
    {
        return str_contains($key, '.')
            ? explode('.', $key, 2)
            : [$key, null];
    }

    /**
     * Extract nested value from data.
     */
    protected function extractNestedValue(mixed $data, ?string $nestedKey, mixed $default = null): mixed
    {
        if ($nestedKey === null) {
            return $data;
        }

        return data_get($data, $nestedKey, $default);
    }

    /**
     * Prepare nested value for storage.
     */
    protected function prepareNestedValue(mixed $existingData, ?string $nestedKey, mixed $value): mixed
    {
        if ($nestedKey === null) {
            return $value;
        }

        $data = is_array($existingData) ? $existingData : [];
        data_set($data, $nestedKey, $value);

        return $data;
    }

    /**
     * Log an error.
     */
    protected function logError(string $operation, string $key, \Throwable $exception, array $context = []): void
    {
        if (! $this->loggingEnabled) {
            return;
        }

        Log::error("OOSettings operation '{$operation}' failed", array_merge([
            'key' => $key,
            'error' => $exception->getMessage(),
            'exception_class' => get_class($exception),
            'trace' => $exception->getTraceAsString(),
        ], $context));
    }

    /**
     * Log an info message.
     */
    protected function logInfo(string $operation, string $key, array $context = []): void
    {
        if (! $this->loggingEnabled || ! config('oo-settings.logging.log_info', false)) {
            return;
        }

        Log::info("OOSettings operation '{$operation}' completed", array_merge([
            'key' => $key,
        ], $context));
    }

    /**
     * Get repository instance.
     */
    public function getRepository(): SettingRepository
    {
        return $this->repository;
    }

    /**
     * Get cache manager instance.
     */
    public function getCacheManager(): CacheManagerContract
    {
        return $this->cache;
    }

    /**
     * Get validation service instance.
     */
    public function getValidationService(): ValidationServiceContract
    {
        return $this->validator;
    }

    /**
     * Get settings statistics.
     *
     * @return array<string, mixed>
     */
    public function getStats(): array
    {
        $repositoryStats = $this->repository->getStats();
        $cacheStats = $this->cache->stats();

        return [
            'repository' => $repositoryStats,
            'cache' => $cacheStats,
            'events_enabled' => $this->eventsEnabled,
            'logging_enabled' => $this->loggingEnabled,
        ];
    }

    /**
     * Enable or disable events.
     */
    public function toggleEvents(bool $enabled = true): void
    {
        $this->eventsEnabled = $enabled;
    }

    /**
     * Enable or disable logging.
     */
    public function toggleLogging(bool $enabled = true): void
    {
        $this->loggingEnabled = $enabled;
    }
}
