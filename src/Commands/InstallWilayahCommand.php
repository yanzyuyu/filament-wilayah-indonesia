<?php

namespace Yanzyuyu\FilamentWilayah\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;

class InstallWilayahCommand extends Command
{
    protected $signature = 'wilayah:install
                            {--force : Force truncate and reseed wilayah tables}
                            {--sync-all : Fetch and sync 100% complete dataset from official open data}
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

        if ($this->option('sync-all')) {
            $this->syncFromOfficialSource();
        } else {
            $this->seedDataset();
        }

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

    protected function syncFromOfficialSource(): void
    {
        $tableProvinces = config('filament-wilayah.tables.provinces', 'wilayah_provinces');
        $tableCities = config('filament-wilayah.tables.cities', 'wilayah_cities');

        $this->components->task('Fetching official provinces data', function () use ($tableProvinces) {
            $response = Http::timeout(10)->get('https://emsifa.github.io/api-wilayah-indonesia/api/provinces.json');
            if ($response->successful()) {
                $now = now();
                $rows = array_map(fn ($p) => [
                    'code' => (string) $p['id'],
                    'name' => (string) $p['name'],
                    'created_at' => $now,
                    'updated_at' => $now,
                ], $response->json());
                DB::table($tableProvinces)->upsert($rows, ['code']);
            }
        });

        $this->components->task('Fetching official regencies / cities data', function () use ($tableProvinces, $tableCities) {
            $provinces = DB::table($tableProvinces)->pluck('code');
            $now = now();
            foreach ($provinces as $provCode) {
                $res = Http::timeout(10)->get("https://emsifa.github.io/api-wilayah-indonesia/api/regencies/{$provCode}.json");
                if ($res->successful()) {
                    $rows = array_map(fn ($c) => [
                        'code' => (string) $c['id'],
                        'province_code' => (string) $c['province_id'],
                        'name' => (string) $c['name'],
                        'created_at' => $now,
                        'updated_at' => $now,
                    ], $res->json());
                    DB::table($tableCities)->upsert($rows, ['code']);
                }
            }
        });
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
