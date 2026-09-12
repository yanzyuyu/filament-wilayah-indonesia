<?php

namespace Yanzyuyu\FilamentWilayah\Forms\Components;

use Filament\Forms\Components\Group;
use Filament\Forms\Components\Select;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Yanzyuyu\FilamentWilayah\Models\City;
use Yanzyuyu\FilamentWilayah\Models\District;
use Yanzyuyu\FilamentWilayah\Models\Province;
use Yanzyuyu\FilamentWilayah\Models\Village;

class WilayahIndonesia extends Group
{
    protected string $provinceField = 'province_code';

    protected string $cityField = 'city_code';

    protected string $districtField = 'district_code';

    protected string $villageField = 'village_code';

    protected string $provinceLabel = 'Provinsi';

    protected string $cityLabel = 'Kabupaten / Kota';

    protected string $districtLabel = 'Kecamatan';

    protected string $villageLabel = 'Kelurahan / Desa';

    protected bool $hasVillage = true;

    protected bool $isRequired = false;

    protected bool $isSearchable = true;

    protected bool $isPreloaded = false;

    public static function make(): static
    {
        $static = app(static::class);
        $static->configure();
        $static->columns(2);

        return $static;
    }

    public function provinceField(string $fieldName): static
    {
        $this->provinceField = $fieldName;
        return $this;
    }

    public function cityField(string $fieldName): static
    {
        $this->cityField = $fieldName;
        return $this;
    }

    public function districtField(string $fieldName): static
    {
        $this->districtField = $fieldName;
        return $this;
    }

    public function villageField(string $fieldName): static
    {
        $this->villageField = $fieldName;
        return $this;
    }

    public function provinceLabel(string $label): static
    {
        $this->provinceLabel = $label;
        return $this;
    }

    public function cityLabel(string $label): static
    {
        $this->cityLabel = $label;
        return $this;
    }

    public function districtLabel(string $label): static
    {
        $this->districtLabel = $label;
        return $this;
    }

    public function villageLabel(string $label): static
    {
        $this->villageLabel = $label;
        return $this;
    }

    public function withoutVillage(bool $condition = true): static
    {
        $this->hasVillage = !$condition;
        return $this;
    }

    public function required(bool $condition = true): static
    {
        $this->isRequired = $condition;
        return $this;
    }

    public function searchable(bool $condition = true): static
    {
        $this->isSearchable = $condition;
        return $this;
    }

    public function preload(bool $condition = true): static
    {
        $this->isPreloaded = $condition;
        return $this;
    }

    public function getChildComponents(): array
    {
        $components = [
            $this->buildProvinceSelect(),
            $this->buildCitySelect(),
            $this->buildDistrictSelect(),
        ];

        if ($this->hasVillage) {
            $components[] = $this->buildVillageSelect();
        }

        return $components;
    }

    protected function buildProvinceSelect(): Select
    {
        $cityField = $this->cityField;
        $districtField = $this->districtField;
        $villageField = $this->villageField;

        return Select::make($this->provinceField)
            ->label($this->provinceLabel)
            ->options(fn () => $this->getProvinces())
            ->searchable($this->isSearchable)
            ->preload($this->isPreloaded)
            ->required($this->isRequired)
            ->live()
            ->afterStateUpdated(function (Set $set) use ($cityField, $districtField, $villageField) {
                $set($cityField, null);
                $set($districtField, null);
                $set($villageField, null);
            });
    }

    protected function buildCitySelect(): Select
    {
        $provinceField = $this->provinceField;
        $districtField = $this->districtField;
        $villageField = $this->villageField;

        return Select::make($this->cityField)
            ->label($this->cityLabel)
            ->options(fn (Get $get) => $this->getCities((string) $get($provinceField)))
            ->searchable($this->isSearchable)
            ->preload($this->isPreloaded)
            ->required($this->isRequired)
            ->disabled(fn (Get $get): bool => blank($get($provinceField)))
            ->live()
            ->afterStateUpdated(function (Set $set) use ($districtField, $villageField) {
                $set($districtField, null);
                $set($villageField, null);
            });
    }

    protected function buildDistrictSelect(): Select
    {
        $cityField = $this->cityField;
        $villageField = $this->villageField;

        return Select::make($this->districtField)
            ->label($this->districtLabel)
            ->options(fn (Get $get) => $this->getDistricts((string) $get($cityField)))
            ->searchable($this->isSearchable)
            ->preload($this->isPreloaded)
            ->required($this->isRequired)
            ->disabled(fn (Get $get): bool => blank($get($cityField)))
            ->live()
            ->afterStateUpdated(function (Set $set) use ($villageField) {
                $set($villageField, null);
            });
    }

    protected function buildVillageSelect(): Select
    {
        $districtField = $this->districtField;

        return Select::make($this->villageField)
            ->label($this->villageLabel)
            ->options(fn (Get $get) => $this->getVillages((string) $get($districtField)))
            ->searchable($this->isSearchable)
            ->preload($this->isPreloaded)
            ->required($this->isRequired)
            ->disabled(fn (Get $get): bool => blank($get($districtField)));
    }

    protected function getProvinces(): Collection
    {
        $cacheEnabled = (bool) config('filament-wilayah.cache.enabled', true);
        $ttl = (int) config('filament-wilayah.cache.ttl_seconds', 86400);

        if (!$cacheEnabled) {
            return Province::query()->orderBy('name')->pluck('name', 'code');
        }

        return Cache::remember('wilayah_provinces', $ttl, function () {
            return Province::query()->orderBy('name')->pluck('name', 'code');
        });
    }

    protected function getCities(string $provinceCode): Collection
    {
        if (blank($provinceCode)) {
            return collect();
        }

        $cacheEnabled = (bool) config('filament-wilayah.cache.enabled', true);
        $ttl = (int) config('filament-wilayah.cache.ttl_seconds', 86400);

        if (!$cacheEnabled) {
            return City::query()
                ->where('province_code', $provinceCode)
                ->orderBy('name')
                ->pluck('name', 'code');
        }

        return Cache::remember("wilayah_cities_{$provinceCode}", $ttl, function () use ($provinceCode) {
            return City::query()
                ->where('province_code', $provinceCode)
                ->orderBy('name')
                ->pluck('name', 'code');
        });
    }

    protected function getDistricts(string $cityCode): Collection
    {
        if (blank($cityCode)) {
            return collect();
        }

        $cacheEnabled = (bool) config('filament-wilayah.cache.enabled', true);
        $ttl = (int) config('filament-wilayah.cache.ttl_seconds', 86400);

        if (!$cacheEnabled) {
            return District::query()
                ->where('city_code', $cityCode)
                ->orderBy('name')
                ->pluck('name', 'code');
        }

        return Cache::remember("wilayah_districts_{$cityCode}", $ttl, function () use ($cityCode) {
            return District::query()
                ->where('city_code', $cityCode)
                ->orderBy('name')
                ->pluck('name', 'code');
        });
    }

    protected function getVillages(string $districtCode): Collection
    {
        if (blank($districtCode)) {
            return collect();
        }

        $cacheEnabled = (bool) config('filament-wilayah.cache.enabled', true);
        $ttl = (int) config('filament-wilayah.cache.ttl_seconds', 86400);

        if (!$cacheEnabled) {
            return Village::query()
                ->where('district_code', $districtCode)
                ->orderBy('name')
                ->pluck('name', 'code');
        }

        return Cache::remember("wilayah_villages_{$districtCode}", $ttl, function () use ($districtCode) {
            return Village::query()
                ->where('district_code', $districtCode)
                ->orderBy('name')
                ->pluck('name', 'code');
        });
    }
}
