<?php

declare(strict_types=1);

namespace OnaOnbir\OOSettings\Repositories;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use OnaOnbir\OOSettings\Models\Setting;
use OnaOnbir\OOSettings\Exceptions\SettingOperationException;

/**
 * Repository for managing setting data operations.
 * 
 * Provides optimized database operations with transaction support,
 * bulk operations, and efficient querying strategies.
 */
class SettingRepository
{
    /**
     * The setting model instance.
     */
    protected Setting $model;

    /**
     * Create a new setting repository.
     */
    public function __construct(Setting $model)
    {
        $this->model = $model;
    }

    /**
     * Find a global setting by key.
     */
    public function findGlobal(string $key): ?Setting
    {
        return $this->model
            ->whereNull('settingable_type')
            ->whereNull('settingable_id')
            ->where('key', $key)
            ->first();
    }

    /**
     * Find a model-specific setting by key.
     */
    public function findForModel(Model $model, string $key): ?Setting
    {
        return $this->model
            ->where('settingable_type', get_class($model))
            ->where('settingable_id', $model->getKey())
            ->where('key', $key)
            ->first();
    }

    /**
     * Get all global settings.
     *
     * @return \Illuminate\Database\Eloquent\Collection<int, Setting>
     */
    public function getAllGlobal()
    {
        return $this->model
            ->whereNull('settingable_type')
            ->whereNull('settingable_id')
            ->get();
    }

    /**
     * Get all settings for a model.
     *
     * @return \Illuminate\Database\Eloquent\Collection<int, Setting>
     */
    public function getAllForModel(Model $model)
    {
        return $this->model
            ->where('settingable_type', get_class($model))
            ->where('settingable_id', $model->getKey())
            ->get();
    }

    /**
     * Create or update a global setting.
     */
    public function createOrUpdateGlobal(
        string $key,
        mixed $value,
        ?string $name = null,
        ?string $description = null
    ): Setting {
        try {
            return DB::transaction(function () use ($key, $value, $name, $description) {
                $setting = $this->model->updateOrCreate(
                    [
                        'key' => $key,
                        'settingable_type' => null,
                        'settingable_id' => null,
                    ],
                    [
                        'value' => $value,
                        'name' => $name,
                        'description' => $description,
                    ]
                );
                
                return $setting;
            });
        } catch (\Throwable $e) {
            throw SettingOperationException::databaseOperation('create_or_update_global', $e->getMessage(), $e);
        }
    }

    /**
     * Create or update a model-specific setting.
     */
    public function createOrUpdateForModel(
        Model $model,
        string $key,
        mixed $value,
        ?string $name = null,
        ?string $description = null
    ): Setting {
        try {
            return DB::transaction(function () use ($model, $key, $value, $name, $description) {
                $setting = $this->model->updateOrCreate(
                    [
                        'key' => $key,
                        'settingable_type' => get_class($model),
                        'settingable_id' => $model->getKey(),
                    ],
                    [
                        'value' => $value,
                        'name' => $name,
                        'description' => $description,
                    ]
                );
                
                return $setting;
            });
        } catch (\Throwable $e) {
            throw SettingOperationException::databaseOperation('create_or_update_model', $e->getMessage(), $e);
        }
    }

    /**
     * Delete a global setting.
     */
    public function deleteGlobal(string $key): bool
    {
        try {
            return DB::transaction(function () use ($key) {
                return $this->model
                    ->whereNull('settingable_type')
                    ->whereNull('settingable_id')
                    ->where('key', $key)
                    ->delete() > 0;
            });
        } catch (\Throwable $e) {
            throw SettingOperationException::databaseOperation('delete_global', $e->getMessage(), $e);
        }
    }

    /**
     * Delete a model-specific setting.
     */
    public function deleteForModel(Model $model, string $key): bool
    {
        try {
            return DB::transaction(function () use ($model, $key) {
                return $this->model
                    ->where('settingable_type', get_class($model))
                    ->where('settingable_id', $model->getKey())
                    ->where('key', $key)
                    ->delete() > 0;
            });
        } catch (\Throwable $e) {
            throw SettingOperationException::databaseOperation('delete_model', $e->getMessage(), $e);
        }
    }

    /**
     * Delete all global settings.
     */
    public function deleteAllGlobal(): bool
    {
        try {
            return DB::transaction(function () {
                return $this->model
                    ->whereNull('settingable_type')
                    ->whereNull('settingable_id')
                    ->delete() > 0;
            });
        } catch (\Throwable $e) {
            throw SettingOperationException::databaseOperation('delete_all_global', $e->getMessage(), $e);
        }
    }

    /**
     * Delete all settings for a model.
     */
    public function deleteAllForModel(Model $model): bool
    {
        try {
            return DB::transaction(function () use ($model) {
                return $this->model
                    ->where('settingable_type', get_class($model))
                    ->where('settingable_id', $model->getKey())
                    ->delete() > 0;
            });
        } catch (\Throwable $e) {
            throw SettingOperationException::databaseOperation('delete_all_model', $e->getMessage(), $e);
        }
    }

    /**
     * Bulk create or update global settings.
     *
     * @param array<string, mixed> $settings Key-value pairs
     * @return bool
     */
    public function bulkCreateOrUpdateGlobal(array $settings, ?string $name = null, ?string $description = null): bool
    {
        try {
            return DB::transaction(function () use ($settings, $name, $description) {
                foreach ($settings as $key => $value) {
                    $this->createOrUpdateGlobal($key, $value, $name, $description);
                }
                return true;
            });
        } catch (\Throwable $e) {
            throw SettingOperationException::databaseOperation('bulk_create_update_global', $e->getMessage(), $e);
        }
    }

    /**
     * Bulk create or update model-specific settings.
     *
     * @param array<string, mixed> $settings Key-value pairs
     * @return bool
     */
    public function bulkCreateOrUpdateForModel(
        Model $model,
        array $settings,
        ?string $name = null,
        ?string $description = null
    ): bool {
        try {
            return DB::transaction(function () use ($model, $settings, $name, $description) {
                foreach ($settings as $key => $value) {
                    $this->createOrUpdateForModel($model, $key, $value, $name, $description);
                }
                return true;
            });
        } catch (\Throwable $e) {
            throw SettingOperationException::databaseOperation('bulk_create_update_model', $e->getMessage(), $e);
        }
    }

    /**
     * Check if a global setting exists.
     */
    public function existsGlobal(string $key): bool
    {
        return $this->model
            ->whereNull('settingable_type')
            ->whereNull('settingable_id')
            ->where('key', $key)
            ->exists();
    }

    /**
     * Check if a model-specific setting exists.
     */
    public function existsForModel(Model $model, string $key): bool
    {
        return $this->model
            ->where('settingable_type', get_class($model))
            ->where('settingable_id', $model->getKey())
            ->where('key', $key)
            ->exists();
    }

    /**
     * Get settings count for performance monitoring.
     *
     * @return array<string, int>
     */
    public function getStats(): array
    {
        try {
            $globalCount = $this->model
                ->whereNull('settingable_type')
                ->whereNull('settingable_id')
                ->count();

            $modelCount = $this->model
                ->whereNotNull('settingable_type')
                ->whereNotNull('settingable_id')
                ->count();

            $totalCount = $this->model->count();

            return [
                'global_settings' => $globalCount,
                'model_settings' => $modelCount,
                'total_settings' => $totalCount,
                'unique_models' => $this->model
                    ->whereNotNull('settingable_type')
                    ->distinct('settingable_type', 'settingable_id')
                    ->count(),
            ];
        } catch (\Throwable $e) {
            throw SettingOperationException::databaseOperation('get_stats', $e->getMessage(), $e);
        }
    }

    /**
     * Search settings by key pattern.
     *
     * @return \Illuminate\Database\Eloquent\Collection<int, Setting>
     */
    public function searchByKeyPattern(string $pattern, ?Model $model = null)
    {
        $query = $this->model->where('key', 'LIKE', str_replace('*', '%', $pattern));

        if ($model) {
            $query->where('settingable_type', get_class($model))
                  ->where('settingable_id', $model->getKey());
        } else {
            $query->whereNull('settingable_type')
                  ->whereNull('settingable_id');
        }

        return $query->get();
    }
}
