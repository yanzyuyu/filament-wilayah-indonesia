<?php

namespace Yanzyuyu\FilamentWilayah\Forms\Components;

use Filament\Forms\Components\Component;
use Filament\Forms\Components\Fieldset;
use Filament\Forms\Components\Group;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Yanzyuyu\FilamentWilayah\Services\WilayahDataService;

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

    protected string $templateType = 'grid';

    protected ?string $sectionHeading = null;

    protected ?string $sectionDescription = null;

    protected bool $sectionCollapsible = false;

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

    public function asSection(
        string $heading = 'Wilayah Administratif',
        ?string $description = 'Pilih lokasi bertingkat dari provinsi hingga kelurahan/desa',
        bool $collapsible = false
    ): static {
        $this->templateType = 'section';
        $this->sectionHeading = $heading;
        $this->sectionDescription = $description;
        $this->sectionCollapsible = $collapsible;
        return $this;
    }

    public function asFieldset(string $label = 'Wilayah Administratif'): static
    {
        $this->templateType = 'fieldset';
        $this->sectionHeading = $label;
        return $this;
    }

    public function asCompact(): static
    {
        $this->templateType = 'compact';
        $this->columns(4);
        return $this;
    }

    public function asInline(): static
    {
        $this->templateType = 'inline';
        $this->columns(1);
        return $this;
    }

    public function getChildComponents(): array
    {
        $selects = [
            $this->buildProvinceSelect(),
            $this->buildCitySelect(),
            $this->buildDistrictSelect(),
        ];

        if ($this->hasVillage) {
            $selects[] = $this->buildVillageSelect();
        }

        if ($this->templateType === 'section') {
            $section = Section::make($this->sectionHeading ?? 'Wilayah Administratif')
                ->description($this->sectionDescription)
                ->collapsible($this->sectionCollapsible)
                ->schema($selects)
                ->columns($this->getColumns() ?? 2);

            return [$section];
        }

        if ($this->templateType === 'fieldset') {
            $fieldset = Fieldset::make($this->sectionHeading ?? 'Wilayah Administratif')
                ->schema($selects)
                ->columns($this->getColumns() ?? 2);

            return [$fieldset];
        }

        return $selects;
    }

    protected function buildProvinceSelect(): Select
    {
        $cityField = $this->cityField;
        $districtField = $this->districtField;
        $villageField = $this->villageField;

        return Select::make($this->provinceField)
            ->label($this->provinceLabel)
            ->prefixIcon('heroicon-m-map-pin')
            ->placeholder('-- Pilih Provinsi --')
            ->options(fn () => app(WilayahDataService::class)->getProvinces())
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
            ->prefixIcon('heroicon-m-building-office-2')
            ->placeholder(fn (Get $get): string => blank($get($provinceField))
                ? 'Pilih provinsi terlebih dahulu'
                : '-- Pilih Kab/Kota --'
            )
            ->options(fn (Get $get) => app(WilayahDataService::class)->getCities((string) $get($provinceField)))
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
            ->prefixIcon('heroicon-m-home-modern')
            ->placeholder(fn (Get $get): string => blank($get($cityField))
                ? 'Pilih kab/kota terlebih dahulu'
                : '-- Pilih Kecamatan --'
            )
            ->options(fn (Get $get) => app(WilayahDataService::class)->getDistricts((string) $get($cityField)))
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
            ->prefixIcon('heroicon-m-home')
            ->placeholder(fn (Get $get): string => blank($get($districtField))
                ? 'Pilih kecamatan terlebih dahulu'
                : '-- Pilih Kelurahan/Desa --'
            )
            ->options(fn (Get $get) => app(WilayahDataService::class)->getVillages((string) $get($districtField)))
            ->searchable($this->isSearchable)
            ->preload($this->isPreloaded)
            ->required($this->isRequired)
            ->disabled(fn (Get $get): bool => blank($get($districtField)));
    }
}
