<?php

declare(strict_types=1);

namespace OnaOnbir\OOSettings\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Carbon;
use OnaOnbir\OOSettings\Models\Traits\JsonCast;

/**
 * Enhanced Setting model with advanced features and optimizations.
 * 
 * @property int $id
 * @property string|null $name
 * @property string|null $description
 * @property string $key
 * @property mixed $value
 * @property string|null $settingable_type
 * @property int|string|null $settingable_id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Model|null $settingable
 */
class Setting extends Model
{
    /**
     * The table associated with the model.
     */
    protected $table;

    /**
     * Create a new Setting instance.
     */
    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
        $this->table = config('oo-settings.table_names.oo_settings', 'oo_settings');
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'name',
        'description', 
        'key',
        'value',
        'settingable_type',
        'settingable_id',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'value' => JsonCast::class,
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<string>
     */
    protected $hidden = [
        'settingable_type',
        'settingable_id',
    ];

    /**
     * Get the polymorphic relationship to the settingable model.
     */
    public function settingable(): MorphTo
    {
        return $this->morphTo();
    }

    /*
    |--------------------------------------------------------------------------
    | Query Scopes
    |--------------------------------------------------------------------------
    */

    /**
     * Scope query to global settings only.
     */
    public function scopeGlobal(Builder $query): Builder
    {
        return $query->whereNull('settingable_type')
                    ->whereNull('settingable_id');
    }

    /**
     * Scope query to model-specific settings only.
     */
    public function scopeForModels(Builder $query): Builder
    {
        return $query->whereNotNull('settingable_type')
                    ->whereNotNull('settingable_id');
    }

    /**
     * Scope query to settings for a specific model class.
     */
    public function scopeForModelClass(Builder $query, string $modelClass): Builder
    {
        return $query->where('settingable_type', $modelClass);
    }

    /**
     * Scope query to settings for a specific model instance.
     */
    public function scopeForModelInstance(Builder $query, Model $model): Builder
    {
        return $query->where('settingable_type', get_class($model))
                    ->where('settingable_id', $model->getKey());
    }

    /**
     * Scope query to settings matching key pattern.
     */
    public function scopeKeyPattern(Builder $query, string $pattern): Builder
    {
        $pattern = str_replace(['*', '?'], ['%', '_'], $pattern);
        return $query->where('key', 'LIKE', $pattern);
    }

    /**
     * Scope query to settings with specific keys.
     */
    public function scopeWithKeys(Builder $query, array $keys): Builder
    {
        return $query->whereIn('key', $keys);
    }

    /**
     * Scope query to recent settings.
     */
    public function scopeRecent(Builder $query, int $days = 7): Builder
    {
        return $query->where('updated_at', '>=', now()->subDays($days));
    }

    /**
     * Scope query to settings created in date range.
     */
    public function scopeCreatedBetween(Builder $query, Carbon $start, Carbon $end): Builder
    {
        return $query->whereBetween('created_at', [$start, $end]);
    }

    /**
     * Scope query to settings updated in date range.
     */
    public function scopeUpdatedBetween(Builder $query, Carbon $start, Carbon $end): Builder
    {
        return $query->whereBetween('updated_at', [$start, $end]);
    }

    /*
    |--------------------------------------------------------------------------
    | Accessor & Mutator Methods
    |--------------------------------------------------------------------------
    */

    /**
     * Get the setting's display name.
     */
    public function getDisplayNameAttribute(): string
    {
        return $this->name ?: $this->key;
    }

    /**
     * Check if this is a global setting.
     */
    public function getIsGlobalAttribute(): bool
    {
        return $this->settingable_type === null && $this->settingable_id === null;
    }

    /**
     * Check if this is a model-specific setting.
     */
    public function getIsModelSpecificAttribute(): bool
    {
        return !$this->is_global;
    }

    /**
     * Get the setting's value type.
     */
    public function getValueTypeAttribute(): string
    {
        return gettype($this->value);
    }

    /**
     * Get the setting's size in bytes.
     */
    public function getSizeAttribute(): int
    {
        return strlen(serialize($this->value));
    }

    /**
     * Get human-readable size.
     */
    public function getHumanSizeAttribute(): string
    {
        $bytes = $this->size;
        $units = ['B', 'KB', 'MB', 'GB'];
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, 2) . ' ' . $units[$i];
    }

    /*
    |--------------------------------------------------------------------------
    | Helper Methods
    |--------------------------------------------------------------------------
    */

    /**
     * Check if the setting key matches a pattern.
     */
    public function matchesPattern(string $pattern): bool
    {
        $regex = '/^' . str_replace(['*', '?'], ['.*', '.'], preg_quote($pattern, '/')) . '$/i';
        return preg_match($regex, $this->key) === 1;
    }

    /**
     * Check if the setting value is of a specific type.
     */
    public function isValueType(string $type): bool
    {
        return $this->value_type === $type;
    }

    /**
     * Check if the setting value is null.
     */
    public function isNull(): bool
    {
        return $this->value === null;
    }

    /**
     * Check if the setting value is empty.
     */
    public function isEmpty(): bool
    {
        return empty($this->value);
    }

    /**
     * Check if the setting value is an array.
     */
    public function isArray(): bool
    {
        return is_array($this->value);
    }

    /**
     * Check if the setting value is a boolean.
     */
    public function isBoolean(): bool
    {
        return is_bool($this->value);
    }

    /**
     * Check if the setting value is numeric.
     */
    public function isNumeric(): bool
    {
        return is_numeric($this->value);
    }

    /**
     * Check if the setting value is a string.
     */
    public function isString(): bool
    {
        return is_string($this->value);
    }

    /**
     * Get the setting value as a specific type.
     */
    public function getValueAs(string $type): mixed
    {
        return match ($type) {
            'bool', 'boolean' => (bool) $this->value,
            'int', 'integer' => (int) $this->value,
            'float', 'double' => (float) $this->value,
            'string' => (string) $this->value,
            'array' => is_array($this->value) ? $this->value : [$this->value],
            'object' => (object) $this->value,
            default => $this->value,
        };
    }

    /**
     * Create a duplicate of this setting.
     */
    public function duplicate(array $overrides = []): static
    {
        $attributes = array_merge(
            $this->only(['name', 'description', 'key', 'value']),
            $overrides
        );
        
        return static::create($attributes);
    }

    /**
     * Export setting to array format.
     */
    public function export(bool $includeMetadata = true): array
    {
        $data = [
            'key' => $this->key,
            'value' => $this->value,
        ];
        
        if ($includeMetadata) {
            $data = array_merge($data, [
                'name' => $this->name,
                'description' => $this->description,
                'type' => $this->is_global ? 'global' : 'model',
                'model_class' => $this->settingable_type,
                'model_id' => $this->settingable_id,
                'created_at' => $this->created_at?->toISOString(),
                'updated_at' => $this->updated_at?->toISOString(),
                'value_type' => $this->value_type,
                'size' => $this->size,
            ]);
        }
        
        return $data;
    }

    /*
    |--------------------------------------------------------------------------
    | Static Methods
    |--------------------------------------------------------------------------
    */

    /**
     * Find a global setting by key.
     */
    public static function findGlobalByKey(string $key): ?static
    {
        return static::global()->where('key', $key)->first();
    }

    /**
     * Find a model setting by key.
     */
    public static function findModelByKey(Model $model, string $key): ?static
    {
        return static::forModelInstance($model)->where('key', $key)->first();
    }

    /**
     * Get statistics about settings.
     */
    public static function getStatistics(): array
    {
        $totalCount = static::count();
        $globalCount = static::global()->count();
        $modelCount = static::forModels()->count();
        
        return [
            'total_settings' => $totalCount,
            'global_settings' => $globalCount,
            'model_settings' => $modelCount,
            'unique_keys' => static::distinct('key')->count(),
            'unique_models' => static::forModels()
                ->distinct('settingable_type', 'settingable_id')
                ->count(),
            'total_size' => static::sum('size'),
            'average_size' => $totalCount > 0 ? static::avg('size') : 0,
            'oldest_setting' => static::oldest('created_at')->value('created_at'),
            'newest_setting' => static::latest('created_at')->value('created_at'),
        ];
    }

    /**
     * Clean up orphaned model settings.
     */
    public static function cleanupOrphaned(): int
    {
        $deletedCount = 0;
        $modelSettings = static::forModels()->get()->groupBy('settingable_type');
        
        foreach ($modelSettings as $modelClass => $settings) {
            if (!class_exists($modelClass)) {
                // Delete settings for non-existent model classes
                $deletedCount += static::where('settingable_type', $modelClass)->delete();
                continue;
            }
            
            $existingIds = $modelClass::pluck('id')->toArray();
            $orphanedSettings = $settings->whereNotIn('settingable_id', $existingIds);
            
            foreach ($orphanedSettings as $setting) {
                $setting->delete();
                $deletedCount++;
            }
        }
        
        return $deletedCount;
    }

    /**
     * Import settings from array.
     */
    public static function import(array $data, bool $merge = false): int
    {
        $importedCount = 0;
        
        foreach ($data as $item) {
            if (!isset($item['key'])) {
                continue;
            }
            
            $attributes = [
                'key' => $item['key'],
                'settingable_type' => $item['model_class'] ?? null,
                'settingable_id' => $item['model_id'] ?? null,
            ];
            
            if ($merge && static::where($attributes)->exists()) {
                continue; // Skip existing settings when merging
            }
            
            static::updateOrCreate($attributes, [
                'value' => $item['value'],
                'name' => $item['name'] ?? null,
                'description' => $item['description'] ?? null,
            ]);
            
            $importedCount++;
        }
        
        return $importedCount;
    }

    /*
    |--------------------------------------------------------------------------
    | Model Events
    |--------------------------------------------------------------------------
    */

    /**
     * Boot the model.
     */
    protected static function boot(): void
    {
        parent::boot();
        
        // Clear related caches when settings are modified
        static::saved(function (Setting $setting) {
            $setting->clearRelatedCaches();
        });
        
        static::deleted(function (Setting $setting) {
            $setting->clearRelatedCaches();
        });
    }

    /**
     * Clear caches related to this setting.
     */
    protected function clearRelatedCaches(): void
    {
        if (!config('oo-settings.cache.enabled', true)) {
            return;
        }
        
        try {
            $cacheManager = app('oo-settings')->getCacheManager();
            
            if ($this->is_global) {
                $cacheManager->forget($cacheManager->globalKey($this->key));
            } else {
                $cacheManager->forget(
                    $cacheManager->modelKey(
                        $this->settingable_type,
                        $this->settingable_id,
                        $this->key
                    )
                );
            }
        } catch (\Throwable $e) {
            // Silently fail if cache manager is not available
            // This prevents errors during testing or if service is not registered
        }
    }
}
