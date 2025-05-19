<?php

namespace OnaOnbir\OOSettings\Traits;

use OnaOnbir\OOSettings\Models\Setting;
use OnaOnbir\OOSettings\OOSettings;

trait HasSettings
{
    public function OOSettings()
    {
        return $this->morphMany(Setting::class, 'settingable');
    }

    public function getOOSetting(string $key, mixed $default = null): mixed
    {
        return OOSettings::getForModel($this, $key, $default);
    }

    public function setOOSetting(string $key, mixed $value): void
    {
        OOSettings::setForModel($this, $key, $value);
    }

    public function forgetOOSetting(string $key): void
    {
        OOSettings::forgetForModel($this, $key);
    }
}
