<?php

declare(strict_types=1);

namespace OnaOnbir\OOSettings\Traits;

use Illuminate\Database\Eloquent\Relations\MorphMany;
use OnaOnbir\OOSettings\Contracts\SettingsContract;
use OnaOnbir\OOSettings\Models\Setting;

/**
 * Enhanced HasSettings trait with caching, validation, and bulk operations.
 * 
 * This production-ready trait provides:
 * - Individual setting management
 * - Bulk operations
 * - Caching integration
 * - Event support
 * - Type safety
 */
trait HasSettings
{
    /**
     * Get the settings service instance.
     */
    protected function getSettingsService(): SettingsContract
    {
        return app(SettingsContract::class);
    }

    /**
     * Get the polymorphic relationship to settings.
     */
    public function OOSettings(): MorphMany
    {
        return $this->morphMany(Setting::class, 'settingable');
    }

    /**
     * Get a setting value with caching and validation.
     *
     * @param string $key Setting key (supports dot notation)
     * @param mixed $default Default value if setting doesn't exist
     * @return mixed Setting value or default
     */
    public function getOOSetting(string $key, mixed $default = null): mixed
    {
        return $this->getSettingsService()->getForModel($this, $key, $default);
    }

    /**
     * Set a setting value with validation and events.
     *
     * @param string $key Setting key (supports dot notation)
     * @param mixed $value Value to store
     * @param string|null $name Human-readable name
     * @param string|null $description Setting description
     * @return bool True if successful
     */
    public function setOOSetting(string $key, mixed $value, ?string $name = null, ?string $description = null): bool
    {
        return $this->getSettingsService()->setForModel($this, $key, $value, $name, $description);
    }

    /**
     * Remove a setting.
     *
     * @param string $key Setting key (supports dot notation)
     * @return bool True if setting existed and was removed
     */
    public function forgetOOSetting(string $key): bool
    {
        return $this->getSettingsService()->forgetForModel($this, $key);
    }

    /**
     * Check if a setting exists.
     *
     * @param string $key Setting key
     * @return bool True if setting exists
     */
    public function hasOOSetting(string $key): bool
    {
        return $this->getSettingsService()->hasForModel($this, $key);
    }

    /**
     * Get all settings for this model.
     *
     * @return array<string, mixed> All settings
     */
    public function getAllOOSettings(): array
    {
        return $this->getSettingsService()->allForModel($this);
    }

    /**
     * Set multiple settings at once.
     *
     * @param array<string, mixed> $settings Key-value pairs
     * @param string|null $name Human-readable name for the operation
     * @param string|null $description Description for the operation
     * @return bool True if all settings were saved successfully
     */
    public function setManyOOSettings(array $settings, ?string $name = null, ?string $description = null): bool
    {
        return $this->getSettingsService()->setManyForModel($this, $settings, $name, $description);
    }

    /**
     * Clear all settings for this model.
     *
     * @return bool True if successful
     */
    public function clearAllOOSettings(): bool
    {
        return $this->getSettingsService()->clearForModel($this);
    }

    /**
     * Get a setting value with type casting.
     *
     * @template T
     * @param string $key Setting key
     * @param T $default Default value (determines return type)
     * @return T Setting value cast to the same type as default
     */
    public function getOOSettingAs(string $key, mixed $default = null): mixed
    {
        $value = $this->getOOSetting($key, $default);
        
        if ($value === null || $default === null) {
            return $value;
        }
        
        return $this->castSettingValue($value, $default);
    }

    /**
     * Get multiple settings at once.
     *
     * @param array<string> $keys Setting keys
     * @param mixed $default Default value for missing settings
     * @return array<string, mixed> Key-value pairs
     */
    public function getManyOOSettings(array $keys, mixed $default = null): array
    {
        $result = [];
        
        foreach ($keys as $key) {
            $result[$key] = $this->getOOSetting($key, $default);
        }
        
        return $result;
    }

    /**
     * Remove multiple settings at once.
     *
     * @param array<string> $keys Setting keys
     * @return array<string, bool> Results for each key
     */
    public function forgetManyOOSettings(array $keys): array
    {
        $result = [];
        
        foreach ($keys as $key) {
            $result[$key] = $this->forgetOOSetting($key);
        }
        
        return $result;
    }

    /**
     * Get settings matching a pattern.
     *
     * @param string $pattern Key pattern (supports wildcards)
     * @return array<string, mixed> Matching settings
     */
    public function getOOSettingsByPattern(string $pattern): array
    {
        $allSettings = $this->getAllOOSettings();
        $result = [];
        
        foreach ($allSettings as $key => $value) {
            if ($this->matchesPattern($key, $pattern)) {
                $result[$key] = $value;
            }
        }
        
        return $result;
    }

    /**
     * Update multiple settings with callback.
     *
     * @param array<string> $keys Setting keys to update
     * @param callable $callback Callback function to transform values
     * @return bool True if all updates were successful
     */
    public function updateOOSettings(array $keys, callable $callback): bool
    {
        $updates = [];
        
        foreach ($keys as $key) {
            $oldValue = $this->getOOSetting($key);
            $newValue = $callback($oldValue, $key);
            $updates[$key] = $newValue;
        }
        
        return $this->setManyOOSettings($updates);
    }

    /**
     * Toggle a boolean setting.
     *
     * @param string $key Setting key
     * @param bool $default Default value if setting doesn't exist
     * @return bool New setting value
     */
    public function toggleOOSetting(string $key, bool $default = false): bool
    {
        $currentValue = $this->getOOSetting($key, $default);
        $newValue = !$currentValue;
        $this->setOOSetting($key, $newValue);
        return $newValue;
    }

    /**
     * Increment a numeric setting.
     *
     * @param string $key Setting key
     * @param int|float $amount Amount to increment by
     * @param int|float $default Default value if setting doesn't exist
     * @return int|float New setting value
     */
    public function incrementOOSetting(string $key, int|float $amount = 1, int|float $default = 0): int|float
    {
        $currentValue = $this->getOOSetting($key, $default);
        $newValue = $currentValue + $amount;
        $this->setOOSetting($key, $newValue);
        return $newValue;
    }

    /**
     * Decrement a numeric setting.
     *
     * @param string $key Setting key
     * @param int|float $amount Amount to decrement by
     * @param int|float $default Default value if setting doesn't exist
     * @return int|float New setting value
     */
    public function decrementOOSetting(string $key, int|float $amount = 1, int|float $default = 0): int|float
    {
        return $this->incrementOOSetting($key, -$amount, $default);
    }

    /**
     * Append to an array setting.
     *
     * @param string $key Setting key
     * @param mixed $value Value to append
     * @return bool True if successful
     */
    public function appendToOOSetting(string $key, mixed $value): bool
    {
        $currentValue = $this->getOOSetting($key, []);
        
        if (!is_array($currentValue)) {
            $currentValue = [$currentValue];
        }
        
        $currentValue[] = $value;
        return $this->setOOSetting($key, $currentValue);
    }

    /**
     * Remove from an array setting.
     *
     * @param string $key Setting key
     * @param mixed $value Value to remove
     * @return bool True if successful
     */
    public function removeFromOOSetting(string $key, mixed $value): bool
    {
        $currentValue = $this->getOOSetting($key, []);
        
        if (!is_array($currentValue)) {
            return false;
        }
        
        $newValue = array_values(array_filter($currentValue, fn($item) => $item !== $value));
        return $this->setOOSetting($key, $newValue);
    }

    /**
     * Apply default settings for this model if they don't exist.
     *
     * @param array<string, mixed> $defaults Default settings
     * @return bool True if successful
     */
    public function applyDefaultOOSettings(array $defaults): bool
    {
        $toSet = [];
        
        foreach ($defaults as $key => $value) {
            if (!$this->hasOOSetting($key)) {
                $toSet[$key] = $value;
            }
        }
        
        if (empty($toSet)) {
            return true;
        }
        
        return $this->setManyOOSettings($toSet);
    }

    /**
     * Get settings statistics for this model.
     *
     * @return array<string, mixed> Statistics
     */
    public function getOOSettingsStats(): array
    {
        $settings = $this->getAllOOSettings();
        
        return [
            'total_settings' => count($settings),
            'setting_types' => $this->analyzeSettingTypes($settings),
            'memory_usage' => strlen(serialize($settings)),
            'last_updated' => $this->OOSettings()->latest('updated_at')->value('updated_at'),
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Protected Helper Methods
    |--------------------------------------------------------------------------
    */

    /**
     * Cast setting value to match default type.
     */
    protected function castSettingValue(mixed $value, mixed $default): mixed
    {
        return match (gettype($default)) {
            'boolean' => (bool) $value,
            'integer' => (int) $value,
            'double' => (float) $value,
            'string' => (string) $value,
            'array' => is_array($value) ? $value : [$value],
            default => $value,
        };
    }

    /**
     * Check if key matches pattern.
     */
    protected function matchesPattern(string $key, string $pattern): bool
    {
        $regex = '/^' . str_replace(['*', '?'], ['.*', '.'], preg_quote($pattern, '/')) . '$/i';
        return preg_match($regex, $key) === 1;
    }

    /**
     * Analyze setting types for statistics.
     */
    protected function analyzeSettingTypes(array $settings): array
    {
        $types = [];
        
        foreach ($settings as $value) {
            $type = gettype($value);
            $types[$type] = ($types[$type] ?? 0) + 1;
        }
        
        return $types;
    }

    /*
    |--------------------------------------------------------------------------
    | Legacy Methods (Backward Compatibility)
    |--------------------------------------------------------------------------
    */

    /**
     * Legacy method name compatibility.
     * @deprecated Use getOOSetting() instead
     */
    public function getSetting(string $key, mixed $default = null): mixed
    {
        return $this->getOOSetting($key, $default);
    }

    /**
     * Legacy method name compatibility.
     * @deprecated Use setOOSetting() instead
     */
    public function setSetting(string $key, mixed $value): bool
    {
        return $this->setOOSetting($key, $value);
    }

    /**
     * Legacy method name compatibility.
     * @deprecated Use forgetOOSetting() instead
     */
    public function forgetSetting(string $key): bool
    {
        return $this->forgetOOSetting($key);
    }
}
