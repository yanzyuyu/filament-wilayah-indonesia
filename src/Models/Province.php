<?php

namespace Yanzyuyu\FilamentWilayah\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class Province extends Model
{
    protected $primaryKey = 'code';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'code',
        'name',
    ];

    public function getTable(): string
    {
        return config('filament-wilayah.tables.provinces', 'wilayah_provinces');
    }

    public function cities(): HasMany
    {
        return $this->hasMany(City::class, 'province_code', 'code');
    }

    public function districts(): HasManyThrough
    {
        return $this->hasManyThrough(District::class, City::class, 'province_code', 'city_code', 'code', 'code');
    }
}
