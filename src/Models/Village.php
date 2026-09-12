<?php

namespace Yanzyuyu\FilamentWilayah\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Village extends Model
{
    protected $primaryKey = 'code';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'code',
        'district_code',
        'name',
    ];

    public function getTable(): string
    {
        return config('filament-wilayah.tables.villages', 'wilayah_villages');
    }

    public function district(): BelongsTo
    {
        return $this->belongsTo(District::class, 'district_code', 'code');
    }
}
