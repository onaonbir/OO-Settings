<?php

namespace OnaOnbir\OOSettings;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use OnaOnbir\OOSettings\Models\Setting;

class OOSettings
{
    /*
    |--------------------------------------------------------------------------
    | Global Settings
    |--------------------------------------------------------------------------
    */

    public static function get(string $key, mixed $default = null): mixed
    {
        [$mainKey, $nestedKey] = self::splitKey($key);

        $setting = Setting::whereNull('settingable_type')
            ->where('key', $mainKey)
            ->first();

        if (! $setting) {
            return $default;
        }

        return is_null($nestedKey)
            ? $setting->value
            : data_get($setting->value, $nestedKey, $default);
    }

    public static function set(string $key, mixed $value,string $name = null,string $description = null): void
    {
        [$mainKey, $nestedKey] = self::splitKey($key);

        $setting = Setting::firstOrNew([
            'key' => $mainKey,
            'settingable_type' => null,
            'settingable_id' => null,
        ]);

        if (is_null($nestedKey)) {
            $setting->value = $value;
        } else {
            $data = is_array($setting->value) ? $setting->value : [];
            data_set($data, $nestedKey, $value);
            $setting->value = $data;
        }

        $setting->name = $name;
        $setting->description = $description;

        $setting->save();
    }

    public static function forget(string $key): void
    {
        [$mainKey, $nestedKey] = self::splitKey($key);

        $setting = Setting::where('key', $mainKey)
            ->whereNull('settingable_type')
            ->first();

        if (! $setting) {
            return;
        }

        if (is_null($nestedKey)) {
            $setting->delete();
        } else {
            $data = $setting->value;
            Arr::forget($data, $nestedKey);
            $setting->value = $data;
            $setting->save();
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Model-Specific Settings
    |--------------------------------------------------------------------------
    */

    public static function getForModel(Model $model, string $key, mixed $default = null): mixed
    {
        [$mainKey, $nestedKey] = self::splitKey($key);

        $setting = $model->OOSettings()->where('key', $mainKey)->first();

        if (! $setting) {
            return $default;
        }

        return is_null($nestedKey)
            ? $setting->value
            : data_get($setting->value, $nestedKey, $default);
    }

    public static function setForModel(Model $model, string $key, mixed $value,string $name = null,string $description = null): void
    {
        [$mainKey, $nestedKey] = self::splitKey($key);

        $setting = $model->OOSettings()->firstOrNew(['key' => $mainKey]);

        if (is_null($nestedKey)) {
            $setting->value = $value;
        } else {
            $data = is_array($setting->value) ? $setting->value : [];
            data_set($data, $nestedKey, $value);
            $setting->value = $data;
        }

        $setting->name = $name;
        $setting->description = $description;

        $setting->save();
    }

    public static function forgetForModel(Model $model, string $key): void
    {
        [$mainKey, $nestedKey] = self::splitKey($key);

        $setting = $model->OOSettings()->where('key', $mainKey)->first();

        if (! $setting) {
            return;
        }

        if (is_null($nestedKey)) {
            $setting->delete();
        } else {
            $data = $setting->value;
            Arr::forget($data, $nestedKey);
            $setting->value = $data;
            $setting->save();
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Internal Utilities
    |--------------------------------------------------------------------------
    */

    protected static function splitKey(string $key): array
    {
        return str_contains($key, '.')
            ? explode('.', $key, 2)
            : [$key, null];
    }
}
