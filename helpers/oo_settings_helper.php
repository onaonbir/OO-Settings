<?php

use Illuminate\Database\Eloquent\Model;

if (! function_exists('oo_setting')) {
    function oo_setting(string $key, mixed $default = null): mixed
    {
        return \OnaOnbir\OOSettings\OOSettings::get($key, $default);
    }
}

if (! function_exists('oo_setting_m')) {
    function oo_setting_m(Model $model, string $key, mixed $default = null): mixed
    {
        if (! method_exists($model, 'getOOSetting')) {
            throw new \LogicException(get_class($model).' does not use HasSettings trait.');
        }

        return $model->getOOSetting($key, $default);
    }
}
