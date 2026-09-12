<?php

namespace Yanzyuyu\FilamentWilayah\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Yanzyuyu\FilamentWilayah\Models\City;
use Yanzyuyu\FilamentWilayah\Models\District;
use Yanzyuyu\FilamentWilayah\Models\Province;
use Yanzyuyu\FilamentWilayah\Models\Village;

class WilayahDataService
{
    protected const BASE_API_URL = 'https://emsifa.github.io/api-wilayah-indonesia/api';

    public function getProvinces(): Collection
    {
        $cacheEnabled = (bool) config('filament-wilayah.cache.enabled', true);
        $ttl = (int) config('filament-wilayah.cache.ttl_seconds', 86400);

        if (!$cacheEnabled) {
            return $this->resolveProvinces();
        }

        return Cache::remember('wilayah_provinces', $ttl, fn () => $this->resolveProvinces());
    }

    public function getCities(string $provinceCode): Collection
    {
        if (blank($provinceCode)) {
            return collect();
        }

        $cacheEnabled = (bool) config('filament-wilayah.cache.enabled', true);
        $ttl = (int) config('filament-wilayah.cache.ttl_seconds', 86400);
        $cacheKey = "wilayah_cities_{$provinceCode}";

        if (!$cacheEnabled) {
            return $this->resolveCities($provinceCode);
        }

        return Cache::remember($cacheKey, $ttl, fn () => $this->resolveCities($provinceCode));
    }

    public function getDistricts(string $cityCode): Collection
    {
        if (blank($cityCode)) {
            return collect();
        }

        $cacheEnabled = (bool) config('filament-wilayah.cache.enabled', true);
        $ttl = (int) config('filament-wilayah.cache.ttl_seconds', 86400);
        $cacheKey = "wilayah_districts_{$cityCode}";

        if (!$cacheEnabled) {
            return $this->resolveDistricts($cityCode);
        }

        return Cache::remember($cacheKey, $ttl, fn () => $this->resolveDistricts($cityCode));
    }

    public function getVillages(string $districtCode): Collection
    {
        if (blank($districtCode)) {
            return collect();
        }

        $cacheEnabled = (bool) config('filament-wilayah.cache.enabled', true);
        $ttl = (int) config('filament-wilayah.cache.ttl_seconds', 86400);
        $cacheKey = "wilayah_villages_{$districtCode}";

        if (!$cacheEnabled) {
            return $this->resolveVillages($districtCode);
        }

        return Cache::remember($cacheKey, $ttl, fn () => $this->resolveVillages($districtCode));
    }

    protected function resolveProvinces(): Collection
    {
        $dbData = Province::query()->orderBy('name')->pluck('name', 'code');
        if ($dbData->isNotEmpty()) {
            return $dbData;
        }

        return $this->fetchFromRemoteApi('/provinces.json', 'id', 'name');
    }

    protected function resolveCities(string $provinceCode): Collection
    {
        $dbData = City::query()
            ->where('province_code', $provinceCode)
            ->orderBy('name')
            ->pluck('name', 'code');

        if ($dbData->isNotEmpty()) {
            return $dbData;
        }

        return $this->fetchFromRemoteApi("/regencies/{$provinceCode}.json", 'id', 'name');
    }

    protected function resolveDistricts(string $cityCode): Collection
    {
        $dbData = District::query()
            ->where('city_code', $cityCode)
            ->orderBy('name')
            ->pluck('name', 'code');

        if ($dbData->isNotEmpty()) {
            return $dbData;
        }

        return $this->fetchFromRemoteApi("/districts/{$cityCode}.json", 'id', 'name');
    }

    protected function resolveVillages(string $districtCode): Collection
    {
        $dbData = Village::query()
            ->where('district_code', $districtCode)
            ->orderBy('name')
            ->pluck('name', 'code');

        if ($dbData->isNotEmpty()) {
            return $dbData;
        }

        return $this->fetchFromRemoteApi("/villages/{$districtCode}.json", 'id', 'name');
    }

    protected function fetchFromRemoteApi(string $path, string $keyField, string $valueField): Collection
    {
        try {
            $response = Http::timeout(5)->get(self::BASE_API_URL . $path);
            if (!$response->successful()) {
                return collect();
            }

            $items = $response->json();
            if (!is_array($items)) {
                return collect();
            }

            return collect($items)
                ->pluck($valueField, $keyField)
                ->sort();
        } catch (\Throwable) {
            return collect();
        }
    }
}
