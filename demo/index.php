<?php

$requestUri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

if (str_starts_with($requestUri, '/api/')) {
    header('Content-Type: application/json');
    $dataPath = dirname(__DIR__) . '/database/data';

    if ($requestUri === '/api/provinces') {
        $file = "{$dataPath}/provinces.json";
        echo file_exists($file) ? file_get_contents($file) : json_encode([]);
        exit;
    }

    if ($requestUri === '/api/cities') {
        $provinceCode = $_GET['province_code'] ?? '';
        $file = "{$dataPath}/cities.json";
        if (!file_exists($file) || empty($provinceCode)) {
            echo json_encode([]);
            exit;
        }
        $cities = json_decode(file_get_contents($file), true) ?: [];
        $filtered = array_values(array_filter($cities, fn ($c) => $c['province_code'] === $provinceCode));
        echo json_encode($filtered);
        exit;
    }

    if ($requestUri === '/api/districts') {
        $cityCode = $_GET['city_code'] ?? '';
        $file = "{$dataPath}/districts.json";
        if (!file_exists($file) || empty($cityCode)) {
            echo json_encode([]);
            exit;
        }
        $districts = json_decode(file_get_contents($file), true) ?: [];
        $filtered = array_values(array_filter($districts, fn ($d) => $d['city_code'] === $cityCode));
        echo json_encode($filtered);
        exit;
    }

    if ($requestUri === '/api/villages') {
        $districtCode = $_GET['district_code'] ?? '';
        $file = "{$dataPath}/villages.json";
        if (!file_exists($file) || empty($districtCode)) {
            echo json_encode([]);
            exit;
        }
        $villages = json_decode(file_get_contents($file), true) ?: [];
        $filtered = array_values(array_filter($villages, fn ($v) => $v['district_code'] === $districtCode));
        echo json_encode($filtered);
        exit;
    }

    http_response_code(404);
    echo json_encode(['error' => 'Not found']);
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Filament Wilayah Indonesia - Demo</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
        code, pre { font-family: 'JetBrains Mono', monospace; }
    </style>
</head>
<body class="bg-zinc-950 text-zinc-100 min-h-screen antialiased selection:bg-amber-500 selection:text-black">
    <div class="max-w-6xl mx-auto px-6 py-12" x-data="wilayahApp()">
        <header class="border-b border-zinc-800 pb-8 mb-10 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div>
                <div class="flex items-center gap-3">
                    <span class="bg-amber-500 text-black font-semibold text-xs px-2.5 py-1 rounded">Filament v3 Plugin</span>
                    <span class="text-zinc-500 text-sm font-mono">yanzyuyu/filament-wilayah-indonesia</span>
                </div>
                <h1 class="text-2xl md:text-3xl font-bold tracking-tight text-white mt-2">Wilayah Indonesia Cascading Dropdown</h1>
                <p class="text-zinc-400 text-sm mt-1">Simulasi interaktif dropdown bertingkat reaktif untuk form resource Filament.</p>
            </div>
            <div class="flex items-center gap-3">
                <a href="https://github.com/yanzyuyu/filament-wilayah-indonesia" target="_blank" class="inline-flex items-center gap-2 px-4 py-2 text-xs font-semibold bg-zinc-900 border border-zinc-800 hover:border-zinc-700 rounded-lg text-zinc-300 hover:text-white transition-colors">
                    <svg class="w-4 h-4 fill-current" viewBox="0 0 24 24"><path d="M12 0C5.37 0 0 5.37 0 12c0 5.31 3.435 9.795 8.205 11.385.6.105.825-.255.825-.57 0-.285-.015-1.23-.015-2.235-3.015.555-3.795-.735-4.035-1.41-.135-.345-.72-1.41-1.23-1.695-.42-.225-1.02-.78-.015-.795.945-.015 1.62.87 1.845 1.23 1.08 1.815 2.805 1.305 3.495.99.105-.78.42-1.305.765-1.605-2.67-.3-5.46-1.335-5.46-5.925 0-1.305.465-2.385 1.23-3.225-.12-.3-.54-1.53.12-3.18 0 0 1.005-.315 3.3 1.23.96-.27 1.98-.405 3-.405s2.04.135 3 .405c2.295-1.56 3.3-1.23 3.3-1.23.66 1.65.24 2.88.12 3.18.765.84 1.23 1.905 1.23 3.225 0 4.605-2.805 5.625-5.475 5.925.435.375.81 1.095.81 2.22 0 1.605-.015 2.895-.015 3.3 0 .315.225.69.825.57A12.02 12.02 0 0024 12c0-6.63-5.37-12-12-12z"/></svg>
                    <span>Lihat di GitHub</span>
                </a>
            </div>
        </header>

        <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">
            <div class="lg:col-span-7 bg-zinc-900/60 border border-zinc-800 rounded-xl p-6 md:p-8 backdrop-blur shadow-2xl">
                <div class="flex items-center justify-between pb-5 border-b border-zinc-800/80 mb-6">
                    <div>
                        <h2 class="text-base font-semibold text-white">Form Input Wilayah</h2>
                        <p class="text-xs text-zinc-400">Pilih bertahap dari Provinsi ke tingkat berikutnya.</p>
                    </div>
                    <div class="flex items-center gap-2">
                        <label class="inline-flex items-center gap-2 text-xs text-zinc-400 cursor-pointer select-none">
                            <input type="checkbox" x-model="withoutVillage" class="rounded border-zinc-700 bg-zinc-800 text-amber-500 focus:ring-0">
                            <span>Tanpa Desa/Kelurahan</span>
                        </label>
                        <button @click="resetForm()" class="px-3 py-1 text-xs border border-zinc-700 hover:border-zinc-600 rounded text-zinc-400 hover:text-zinc-200 transition-colors">
                            Reset
                        </button>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                    <div>
                        <label class="block text-xs font-medium text-zinc-300 mb-1.5">
                            Provinsi <span class="text-rose-400">*</span>
                        </label>
                        <div class="relative">
                            <select x-model="selectedProvince" @change="onProvinceChange()" class="w-full bg-zinc-950 border border-zinc-700 focus:border-amber-500 focus:ring-1 focus:ring-amber-500 rounded-lg px-3 py-2.5 text-sm text-zinc-200 appearance-none transition-colors">
                                <option value="">-- Pilih Provinsi --</option>
                                <template x-for="p in provinces" :key="p.code">
                                    <option :value="p.code" x-text="p.name"></option>
                                </template>
                            </select>
                            <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-3 text-zinc-500">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                            </div>
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-zinc-300 mb-1.5">
                            Kabupaten / Kota <span class="text-rose-400">*</span>
                        </label>
                        <div class="relative">
                            <select x-model="selectedCity" :disabled="!selectedProvince || loadingCities" @change="onCityChange()" class="w-full bg-zinc-950 border border-zinc-700 focus:border-amber-500 focus:ring-1 focus:ring-amber-500 disabled:bg-zinc-900/50 disabled:text-zinc-600 disabled:border-zinc-800 rounded-lg px-3 py-2.5 text-sm text-zinc-200 appearance-none transition-colors">
                                <option value="" x-text="loadingCities ? 'Memuat...' : (selectedProvince ? '-- Pilih Kab/Kota --' : 'Pilih provinsi terlebih dahulu')"></option>
                                <template x-for="c in cities" :key="c.code">
                                    <option :value="c.code" x-text="c.name"></option>
                                </template>
                            </select>
                            <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-3 text-zinc-500">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                            </div>
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-zinc-300 mb-1.5">
                            Kecamatan <span class="text-rose-400">*</span>
                        </label>
                        <div class="relative">
                            <select x-model="selectedDistrict" :disabled="!selectedCity || loadingDistricts" @change="onDistrictChange()" class="w-full bg-zinc-950 border border-zinc-700 focus:border-amber-500 focus:ring-1 focus:ring-amber-500 disabled:bg-zinc-900/50 disabled:text-zinc-600 disabled:border-zinc-800 rounded-lg px-3 py-2.5 text-sm text-zinc-200 appearance-none transition-colors">
                                <option value="" x-text="loadingDistricts ? 'Memuat...' : (selectedCity ? '-- Pilih Kecamatan --' : 'Pilih kab/kota terlebih dahulu')"></option>
                                <template x-for="d in districts" :key="d.code">
                                    <option :value="d.code" x-text="d.name"></option>
                                </template>
                            </select>
                            <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-3 text-zinc-500">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                            </div>
                        </div>
                    </div>

                    <div x-show="!withoutVillage">
                        <label class="block text-xs font-medium text-zinc-300 mb-1.5">
                            Kelurahan / Desa <span class="text-rose-400">*</span>
                        </label>
                        <div class="relative">
                            <select x-model="selectedVillage" :disabled="!selectedDistrict || loadingVillages" class="w-full bg-zinc-950 border border-zinc-700 focus:border-amber-500 focus:ring-1 focus:ring-amber-500 disabled:bg-zinc-900/50 disabled:text-zinc-600 disabled:border-zinc-800 rounded-lg px-3 py-2.5 text-sm text-zinc-200 appearance-none transition-colors">
                                <option value="" x-text="loadingVillages ? 'Memuat...' : (selectedDistrict ? '-- Pilih Kelurahan/Desa --' : 'Pilih kecamatan terlebih dahulu')"></option>
                                <template x-for="v in villages" :key="v.code">
                                    <option :value="v.code" x-text="v.name"></option>
                                </template>
                            </select>
                            <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-3 text-zinc-500">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="mt-8 pt-6 border-t border-zinc-800">
                    <span class="text-xs font-semibold uppercase tracking-wider text-zinc-500 block mb-2">Live Livewire State ($data)</span>
                    <pre class="bg-zinc-950 border border-zinc-800/80 rounded-lg p-4 text-xs text-amber-400/90 overflow-x-auto" x-text="JSON.stringify({
                        province_code: selectedProvince || null,
                        city_code: selectedCity || null,
                        district_code: selectedDistrict || null,
                        village_code: withoutVillage ? undefined : (selectedVillage || null)
                    }, null, 2)"></pre>
                </div>
            </div>

            <div class="lg:col-span-5 space-y-6">
                <div class="bg-zinc-900/60 border border-zinc-800 rounded-xl p-6 backdrop-blur">
                    <h3 class="text-sm font-semibold text-white mb-2">Sintaks Filament</h3>
                    <p class="text-xs text-zinc-400 mb-4">Cukup panggil satu baris ini di resource Filament Anda:</p>
                    <pre class="bg-zinc-950 border border-zinc-800 rounded-lg p-4 text-xs text-zinc-300 overflow-x-auto leading-relaxed"><code><span class="text-purple-400">use</span> Yanzyuyu\FilamentWilayah\Forms\Components\WilayahIndonesia;

<span class="text-blue-400">WilayahIndonesia</span>::<span class="text-amber-400">make</span>()
    -><span class="text-amber-400">columns</span>(<span class="text-emerald-400">2</span>)<span x-text="withoutVillage ? '\n    ->withoutVillage()' : ''"></span>;</code></pre>
                </div>

                <div class="bg-zinc-900/60 border border-zinc-800 rounded-xl p-6 backdrop-blur space-y-3">
                    <h3 class="text-sm font-semibold text-white">Cara Install di Proyek Anda</h3>
                    <div class="space-y-2">
                        <div class="bg-zinc-950 border border-zinc-800 rounded-lg px-3 py-2 text-xs font-mono text-zinc-300 flex items-center justify-between">
                            <span>composer require yanzyuyu/filament-wilayah-indonesia</span>
                        </div>
                        <div class="bg-zinc-950 border border-zinc-800 rounded-lg px-3 py-2 text-xs font-mono text-zinc-300 flex items-center justify-between">
                            <span>php artisan wilayah:install</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        function wilayahApp() {
            return {
                provinces: [],
                cities: [],
                districts: [],
                villages: [],
                selectedProvince: '',
                selectedCity: '',
                selectedDistrict: '',
                selectedVillage: '',
                withoutVillage: false,
                loadingCities: false,
                loadingDistricts: false,
                loadingVillages: false,

                async init() {
                    const response = await fetch('/api/provinces');
                    this.provinces = await response.json();
                },

                async onProvinceChange() {
                    this.selectedCity = '';
                    this.selectedDistrict = '';
                    this.selectedVillage = '';
                    this.cities = [];
                    this.districts = [];
                    this.villages = [];

                    if (!this.selectedProvince) return;

                    this.loadingCities = true;
                    try {
                        const res = await fetch(`/api/cities?province_code=${this.selectedProvince}`);
                        this.cities = await res.json();
                    } finally {
                        this.loadingCities = false;
                    }
                },

                async onCityChange() {
                    this.selectedDistrict = '';
                    this.selectedVillage = '';
                    this.districts = [];
                    this.villages = [];

                    if (!this.selectedCity) return;

                    this.loadingDistricts = true;
                    try {
                        const res = await fetch(`/api/districts?city_code=${this.selectedCity}`);
                        this.districts = await res.json();
                    } finally {
                        this.loadingDistricts = false;
                    }
                },

                async onDistrictChange() {
                    this.selectedVillage = '';
                    this.villages = [];

                    if (!this.selectedDistrict || this.withoutVillage) return;

                    this.loadingVillages = true;
                    try {
                        const res = await fetch(`/api/villages?district_code=${this.selectedDistrict}`);
                        this.villages = await res.json();
                    } finally {
                        this.loadingVillages = false;
                    }
                },

                resetForm() {
                    this.selectedProvince = '';
                    this.selectedCity = '';
                    this.selectedDistrict = '';
                    this.selectedVillage = '';
                    this.cities = [];
                    this.districts = [];
                    this.villages = [];
                }
            };
        }
    </script>
</body>
</html>
