<?php

declare(strict_types=1);

namespace OnaOnbir\OOSettings\Contracts;

use Illuminate\Database\Eloquent\Model;

/**
 * Settings Contract defining the core settings management interface.
 * 
 * This contract provides a standardized way to manage both global settings
 * and model-specific polymorphic settings with full type safety and caching support.
 */
interface SettingsContract
{
    /**
     * Get a global setting value.
     *
     * @param string $key The setting key (supports dot notation)
     * @param mixed $default Default value if setting doesn't exist
     * @return mixed The setting value or default
     * 
     * @throws \OnaOnbir\OOSettings\Exceptions\InvalidKeyException
     */
    public function get(string $key, mixed $default = null): mixed;

    /**
     * Set a global setting value.
     *
     * @param string $key The setting key (supports dot notation)
     * @param mixed $value The value to store
     * @param string|null $name Human-readable name
     * @param string|null $description Setting description
     * @return bool True if successful
     * 
     * @throws \OnaOnbir\OOSettings\Exceptions\InvalidKeyException
     * @throws \OnaOnbir\OOSettings\Exceptions\InvalidValueException
     */
    public function set(string $key, mixed $value, ?string $name = null, ?string $description = null): bool;

    /**
     * Remove a global setting.
     *
     * @param string $key The setting key (supports dot notation)
     * @return bool True if setting existed and was removed
     * 
     * @throws \OnaOnbir\OOSettings\Exceptions\InvalidKeyException
     */
    public function forget(string $key): bool;

    /**
     * Check if a global setting exists.
     *
     * @param string $key The setting key
     * @return bool True if setting exists
     */
    public function has(string $key): bool;

    /**
     * Get all global settings.
     *
     * @return array<string, mixed> All global settings
     */
    public function all(): array;

    /**
     * Get a model-specific setting value.
     *
     * @param Model $model The model instance
     * @param string $key The setting key (supports dot notation)
     * @param mixed $default Default value if setting doesn't exist
     * @return mixed The setting value or default
     * 
     * @throws \OnaOnbir\OOSettings\Exceptions\InvalidKeyException
     */
    public function getForModel(Model $model, string $key, mixed $default = null): mixed;

    /**
     * Set a model-specific setting value.
     *
     * @param Model $model The model instance
     * @param string $key The setting key (supports dot notation)
     * @param mixed $value The value to store
     * @param string|null $name Human-readable name
     * @param string|null $description Setting description
     * @return bool True if successful
     * 
     * @throws \OnaOnbir\OOSettings\Exceptions\InvalidKeyException
     * @throws \OnaOnbir\OOSettings\Exceptions\InvalidValueException
     */
    public function setForModel(Model $model, string $key, mixed $value, ?string $name = null, ?string $description = null): bool;

    /**
     * Remove a model-specific setting.
     *
     * @param Model $model The model instance
     * @param string $key The setting key (supports dot notation)
     * @return bool True if setting existed and was removed
     * 
     * @throws \OnaOnbir\OOSettings\Exceptions\InvalidKeyException
     */
    public function forgetForModel(Model $model, string $key): bool;

    /**
     * Check if a model-specific setting exists.
     *
     * @param Model $model The model instance
     * @param string $key The setting key
     * @return bool True if setting exists
     */
    public function hasForModel(Model $model, string $key): bool;

    /**
     * Get all settings for a model.
     *
     * @param Model $model The model instance
     * @return array<string, mixed> All model settings
     */
    public function allForModel(Model $model): array;

    /**
     * Set multiple settings at once.
     *
     * @param array<string, mixed> $settings Key-value pairs
     * @param string|null $name Human-readable name for the operation
     * @param string|null $description Description for the operation
     * @return bool True if all settings were saved successfully
     * 
     * @throws \OnaOnbir\OOSettings\Exceptions\InvalidKeyException
     * @throws \OnaOnbir\OOSettings\Exceptions\InvalidValueException
     */
    public function setMany(array $settings, ?string $name = null, ?string $description = null): bool;

    /**
     * Set multiple model-specific settings at once.
     *
     * @param Model $model The model instance
     * @param array<string, mixed> $settings Key-value pairs
     * @param string|null $name Human-readable name for the operation
     * @param string|null $description Description for the operation
     * @return bool True if all settings were saved successfully
     * 
     * @throws \OnaOnbir\OOSettings\Exceptions\InvalidKeyException
     * @throws \OnaOnbir\OOSettings\Exceptions\InvalidValueException
     */
    public function setManyForModel(Model $model, array $settings, ?string $name = null, ?string $description = null): bool;

    /**
     * Clear all global settings.
     *
     * @return bool True if successful
     */
    public function clear(): bool;

    /**
     * Clear all settings for a model.
     *
     * @param Model $model The model instance
     * @return bool True if successful
     */
    public function clearForModel(Model $model): bool;

    /**
     * Clear all caches.
     *
     * @return bool True if successful
     */
    public function clearCache(): bool;
}
