<?php

declare(strict_types=1);

use Illuminate\Database\Eloquent\Model;
use OnaOnbir\OOSettings\Contracts\SettingsContract;

if (! function_exists('oo_setting')) {
    /**
     * Get a global setting value with caching and validation.
     *
     * @param  string  $key  Setting key (supports dot notation)
     * @param  mixed  $default  Default value if setting doesn't exist
     * @return mixed Setting value or default
     */
    function oo_setting(string $key, mixed $default = null): mixed
    {
        return app(SettingsContract::class)->get($key, $default);
    }
}

if (! function_exists('oo_setting_set')) {
    /**
     * Set a global setting value with validation and events.
     *
     * @param  string  $key  Setting key (supports dot notation)
     * @param  mixed  $value  Value to store
     * @param  string|null  $name  Human-readable name
     * @param  string|null  $description  Setting description
     * @return bool True if successful
     */
    function oo_setting_set(string $key, mixed $value, ?string $name = null, ?string $description = null): bool
    {
        return app(SettingsContract::class)->set($key, $value, $name, $description);
    }
}

if (! function_exists('oo_setting_forget')) {
    /**
     * Remove a global setting.
     *
     * @param  string  $key  Setting key (supports dot notation)
     * @return bool True if setting existed and was removed
     */
    function oo_setting_forget(string $key): bool
    {
        return app(SettingsContract::class)->forget($key);
    }
}

if (! function_exists('oo_setting_has')) {
    /**
     * Check if a global setting exists.
     *
     * @param  string  $key  Setting key
     * @return bool True if setting exists
     */
    function oo_setting_has(string $key): bool
    {
        return app(SettingsContract::class)->has($key);
    }
}

if (! function_exists('oo_setting_all')) {
    /**
     * Get all global settings.
     *
     * @return array<string, mixed> All global settings
     */
    function oo_setting_all(): array
    {
        return app(SettingsContract::class)->all();
    }
}

if (! function_exists('oo_setting_m')) {
    /**
     * Get a model-specific setting value with validation.
     *
     * @param  Model  $model  Model instance
     * @param  string  $key  Setting key (supports dot notation)
     * @param  mixed  $default  Default value if setting doesn't exist
     * @return mixed Setting value or default
     */
    function oo_setting_m(Model $model, string $key, mixed $default = null): mixed
    {
        if (! method_exists($model, 'getOOSetting')) {
            throw new \LogicException(get_class($model).' does not use HasSettings trait.');
        }

        return $model->getOOSetting($key, $default);
    }
}

if (! function_exists('oo_setting_m_set')) {
    /**
     * Set a model-specific setting value.
     *
     * @param  Model  $model  Model instance
     * @param  string  $key  Setting key (supports dot notation)
     * @param  mixed  $value  Value to store
     * @param  string|null  $name  Human-readable name
     * @param  string|null  $description  Setting description
     * @return bool True if successful
     */
    function oo_setting_m_set(Model $model, string $key, mixed $value, ?string $name = null, ?string $description = null): bool
    {
        if (! method_exists($model, 'setOOSetting')) {
            throw new \LogicException(get_class($model).' does not use HasSettings trait.');
        }

        return $model->setOOSetting($key, $value, $name, $description);
    }
}

if (! function_exists('oo_setting_m_forget')) {
    /**
     * Remove a model-specific setting.
     *
     * @param  Model  $model  Model instance
     * @param  string  $key  Setting key (supports dot notation)
     * @return bool True if setting existed and was removed
     */
    function oo_setting_m_forget(Model $model, string $key): bool
    {
        if (! method_exists($model, 'forgetOOSetting')) {
            throw new \LogicException(get_class($model).' does not use HasSettings trait.');
        }

        return $model->forgetOOSetting($key);
    }
}

if (! function_exists('oo_setting_m_has')) {
    /**
     * Check if a model-specific setting exists.
     *
     * @param  Model  $model  Model instance
     * @param  string  $key  Setting key
     * @return bool True if setting exists
     */
    function oo_setting_m_has(Model $model, string $key): bool
    {
        if (! method_exists($model, 'hasOOSetting')) {
            throw new \LogicException(get_class($model).' does not use HasSettings trait.');
        }

        return $model->hasOOSetting($key);
    }
}

if (! function_exists('oo_setting_many')) {
    /**
     * Set multiple global settings at once.
     *
     * @param  array<string, mixed>  $settings  Key-value pairs
     * @param  string|null  $name  Human-readable name for the operation
     * @param  string|null  $description  Description for the operation
     * @return bool True if all settings were saved successfully
     */
    function oo_setting_many(array $settings, ?string $name = null, ?string $description = null): bool
    {
        return app(SettingsContract::class)->setMany($settings, $name, $description);
    }
}

if (! function_exists('oo_setting_clear')) {
    /**
     * Clear all global settings.
     *
     * @return bool True if successful
     */
    function oo_setting_clear(): bool
    {
        return app(SettingsContract::class)->clear();
    }
}

if (! function_exists('oo_setting_cache_clear')) {
    /**
     * Clear all settings cache.
     *
     * @return bool True if successful
     */
    function oo_setting_cache_clear(): bool
    {
        return app(SettingsContract::class)->clearCache();
    }
}

if (! function_exists('oo_setting_stats')) {
    /**
     * Get settings statistics.
     *
     * @return array<string, mixed> Statistics data
     */
    function oo_setting_stats(): array
    {
        return app(SettingsContract::class)->getStats();
    }
}

if (! function_exists('oo_setting_toggle')) {
    /**
     * Toggle a boolean global setting.
     *
     * @param  string  $key  Setting key
     * @param  bool  $default  Default value if setting doesn't exist
     * @return bool New setting value
     */
    function oo_setting_toggle(string $key, bool $default = false): bool
    {
        $service = app(SettingsContract::class);
        $currentValue = $service->get($key, $default);
        $newValue = ! $currentValue;
        $service->set($key, $newValue);

        return $newValue;
    }
}

if (! function_exists('oo_setting_increment')) {
    /**
     * Increment a numeric global setting.
     *
     * @param  string  $key  Setting key
     * @param  int|float  $amount  Amount to increment by
     * @param  int|float  $default  Default value if setting doesn't exist
     * @return int|float New setting value
     */
    function oo_setting_increment(string $key, int|float $amount = 1, int|float $default = 0): int|float
    {
        $service = app(SettingsContract::class);
        $currentValue = $service->get($key, $default);
        $newValue = $currentValue + $amount;
        $service->set($key, $newValue);

        return $newValue;
    }
}

if (! function_exists('oo_setting_decrement')) {
    /**
     * Decrement a numeric global setting.
     *
     * @param  string  $key  Setting key
     * @param  int|float  $amount  Amount to decrement by
     * @param  int|float  $default  Default value if setting doesn't exist
     * @return int|float New setting value
     */
    function oo_setting_decrement(string $key, int|float $amount = 1, int|float $default = 0): int|float
    {
        return oo_setting_increment($key, -$amount, $default);
    }
}

if (! function_exists('oo_setting_as')) {
    /**
     * Get a global setting value with type casting.
     *
     * @template T
     *
     * @param  string  $key  Setting key
     * @param  T  $default  Default value (determines return type)
     * @return T Setting value cast to the same type as default
     */
    function oo_setting_as(string $key, mixed $default = null): mixed
    {
        $value = oo_setting($key, $default);

        if ($value === null || $default === null) {
            return $value;
        }

        return match (gettype($default)) {
            'boolean' => (bool) $value,
            'integer' => (int) $value,
            'double' => (float) $value,
            'string' => (string) $value,
            'array' => is_array($value) ? $value : [$value],
            default => $value,
        };
    }
}

if (! function_exists('oo_setting_append')) {
    /**
     * Append to a global array setting.
     *
     * @param  string  $key  Setting key
     * @param  mixed  $value  Value to append
     * @return bool True if successful
     */
    function oo_setting_append(string $key, mixed $value): bool
    {
        $service = app(SettingsContract::class);
        $currentValue = $service->get($key, []);

        if (! is_array($currentValue)) {
            $currentValue = [$currentValue];
        }

        $currentValue[] = $value;

        return $service->set($key, $currentValue);
    }
}

if (! function_exists('oo_setting_remove')) {
    /**
     * Remove from a global array setting.
     *
     * @param  string  $key  Setting key
     * @param  mixed  $value  Value to remove
     * @return bool True if successful
     */
    function oo_setting_remove(string $key, mixed $value): bool
    {
        $service = app(SettingsContract::class);
        $currentValue = $service->get($key, []);

        if (! is_array($currentValue)) {
            return false;
        }

        $newValue = array_values(array_filter($currentValue, fn ($item) => $item !== $value));

        return $service->set($key, $newValue);
    }
}

if (! function_exists('oo_setting_config')) {
    /**
     * Get or set configuration for OOSettings.
     *
     * @param  string|null  $key  Configuration key
     * @param  mixed  $default  Default value
     * @return mixed Configuration value
     */
    function oo_setting_config(?string $key = null, mixed $default = null): mixed
    {
        if ($key === null) {
            return config('oo-settings');
        }

        return config("oo-settings.{$key}", $default);
    }
}

/*
|--------------------------------------------------------------------------
| Legacy Function Names (Backward Compatibility)
|--------------------------------------------------------------------------
*/

if (! function_exists('setting')) {
    /**
     * Legacy function name for backward compatibility.
     *
     * @deprecated Use oo_setting() instead
     */
    function setting(string $key, mixed $default = null): mixed
    {
        return oo_setting($key, $default);
    }
}

if (! function_exists('setting_m')) {
    /**
     * Legacy function name for backward compatibility.
     *
     * @deprecated Use oo_setting_m() instead
     */
    function setting_m(Model $model, string $key, mixed $default = null): mixed
    {
        return oo_setting_m($model, $key, $default);
    }
}
