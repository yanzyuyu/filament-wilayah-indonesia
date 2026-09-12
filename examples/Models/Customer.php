<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Yanzyuyu\FilamentWilayah\Models\City;
use Yanzyuyu\FilamentWilayah\Models\District;
use Yanzyuyu\FilamentWilayah\Models\Province;
use Yanzyuyu\FilamentWilayah\Models\Village;

class Customer extends Model
{
    protected $fillable = [
        'name',
        'phone',
        'email',
        'province_code',
        'city_code',
        'district_code',
        'village_code',
        'postal_code',
        'street_address',
    ];

    public function province(): BelongsTo
    {
        return $this->belongsTo(Province::class, 'province_code', 'code');
    }

    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class, 'city_code', 'code');
    }

    public function district(): BelongsTo
    {
        return $this->belongsTo(District::class, 'district_code', 'code');
    }

    public function village(): BelongsTo
    {
        return $this->belongsTo(Village::class, 'village_code', 'code');
    }

    protected function fullAddress(): Attribute
    {
        return Attribute::make(
            get: function (): string {
                return collect([
                    $this->street_address,
                    $this->village?->name,
                    $this->district?->name,
                    $this->city?->name,
                    $this->province?->name,
                    $this->postal_code,
                ])->filter()->join(', ');
            }
        );
    }
}
