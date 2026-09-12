<?php

namespace Yanzyuyu\FilamentWilayah\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class InstallWilayahCommand extends Command
{
    protected $signature = 'wilayah:install
                            {--force : Force truncate and reseed wilayah tables}
                            {--skip-seed : Only publish and run migrations without seeding}';

    protected $description = 'Install migrations and seed Indonesian administrative regions dataset';

    public function handle(): int
    {
        $this->components->info('Installing Filament Wilayah Indonesia...');

        $this->publishAssets();

        $this->runMigrations();

        if ($this->option('skip-seed')) {
            $this->components->warn('Seeding skipped via --skip-seed option.');
            return self::SUCCESS;
        }

        $this->seedDataset();

        $this->components->info('Filament Wilayah Indonesia successfully installed!');
        return self::SUCCESS;
    }

    protected function publishAssets(): void
    {
        $this->callSilent('vendor:publish', [
            '--tag' => 'filament-wilayah-config',
            '--force' => true,
        ]);

        $this->callSilent('vendor:publish', [
            '--tag' => 'filament-wilayah-migrations',
        ]);

        $this->components->twoColumnDetail('Configuration & Migrations', '<fg=green;options=bold>PUBLISHED</>');
    }

    protected function runMigrations(): void
    {
        $this->call('migrate');
        $this->components->twoColumnDetail('Database Tables', '<fg=green;options=bold>MIGRATED</>');
    }

    protected function seedDataset(): void
    {
        $tableProvinces = config('filament-wilayah.tables.provinces', 'wilayah_provinces');
        $tableCities = config('filament-wilayah.tables.cities', 'wilayah_cities');
        $tableDistricts = config('filament-wilayah.tables.districts', 'wilayah_districts');
        $tableVillages = config('filament-wilayah.tables.villages', 'wilayah_villages');

        $dataPath = dirname(__DIR__, 2) . '/database/data';

        if ($this->option('force')) {
            DB::table($tableVillages)->delete();
            DB::table($tableDistricts)->delete();
            DB::table($tableCities)->delete();
            DB::table($tableProvinces)->delete();
        }

        $this->seedFile("{$dataPath}/provinces.json", $tableProvinces, 'Provinces');
        $this->seedFile("{$dataPath}/cities.json", $tableCities, 'Cities / Regencies');
        $this->seedFile("{$dataPath}/districts.json", $tableDistricts, 'Districts (Kecamatan)');
        $this->seedFile("{$dataPath}/villages.json", $tableVillages, 'Villages (Kelurahan/Desa)');
    }

    protected function seedFile(string $filePath, string $tableName, string $label): void
    {
        if (!File::exists($filePath)) {
            $this->components->warn("Data file not found: {$filePath}");
            return;
        }

        $rawJson = File::get($filePath);
        $records = json_decode($rawJson, true);

        if (empty($records) || !is_array($records)) {
            $this->components->warn("Empty or invalid JSON: {$filePath}");
            return;
        }

        $now = now();
        $batch = [];
        $total = count($records);

        foreach ($records as $item) {
            $item['created_at'] = $now;
            $item['updated_at'] = $now;
            $batch[] = $item;

            if (count($batch) >= 500) {
                DB::table($tableName)->upsert($batch, ['code']);
                $batch = [];
            }
        }

        if (!empty($batch)) {
            DB::table($tableName)->upsert($batch, ['code']);
        }

        $this->components->twoColumnDetail($label, "<fg=green;options=bold>{$total} records</>");
    }
}
