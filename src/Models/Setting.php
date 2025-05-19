<?php

namespace OnaOnbir\OOSettings\Models;

use Illuminate\Database\Eloquent\Model;
use OnaOnbir\OOSettings\Models\Traits\JsonCast;

class Setting extends Model
{

    protected $table;

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
        $this->table = config('oo-settings.table_names.oo_settings', 'oo_settings');
    }

    protected $fillable = [
        'name', 'description', 'key', 'value',
    ];

    protected $casts = [
        'value' => JsonCast::class,
    ];

    public function settingable()
    {
        return $this->morphTo();
    }
}
