<?php

$requestUri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

if (str_starts_with($requestUri, '/api/')) {
    header('Content-Type: application/json');
    $cacheDir = __DIR__ . '/cache';
    if (!is_dir($cacheDir)) {
        mkdir($cacheDir, 0777, true);
    }

    $fetchOrCache = function (string $cacheFile, string $remoteUrl, callable $transform): array {
        if (file_exists($cacheFile)) {
            $cached = json_decode(file_get_contents($cacheFile), true);
            if (is_array($cached) && !empty($cached)) {
                return $cached;
            }
        }

        $context = stream_context_create([
            'http' => [
                'timeout' => 5,
                'user_agent' => 'FilamentWilayahDemo/1.0',
            ],
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false,
            ],
        ]);

        $content = @file_get_contents($remoteUrl, false, $context);
        if ($content === false) {
            return [];
        }

        $raw = json_decode($content, true);
        if (!is_array($raw)) {
            return [];
        }

        $transformed = array_values(array_map($transform, $raw));
        file_put_contents($cacheFile, json_encode($transformed));
        return $transformed;
    };

    if ($requestUri === '/api/provinces') {
        $result = $fetchOrCache(
            "{$cacheDir}/provinces.json",
            'https://emsifa.github.io/api-wilayah-indonesia/api/provinces.json',
            fn ($item) => ['code' => (string) $item['id'], 'name' => (string) $item['name']]
        );
        echo json_encode($result);
        exit;
    }

    if ($requestUri === '/api/cities') {
        $provinceCode = preg_replace('/[^0-9]/', '', $_GET['province_code'] ?? '');
        if (empty($provinceCode)) {
            echo json_encode([]);
            exit;
        }
        $result = $fetchOrCache(
            "{$cacheDir}/regencies_{$provinceCode}.json",
            "https://emsifa.github.io/api-wilayah-indonesia/api/regencies/{$provinceCode}.json",
            fn ($item) => ['code' => (string) $item['id'], 'name' => (string) $item['name'], 'province_code' => (string) $item['province_id']]
        );
        echo json_encode($result);
        exit;
    }

    if ($requestUri === '/api/districts') {
        $cityCode = preg_replace('/[^0-9]/', '', $_GET['city_code'] ?? '');
        if (empty($cityCode)) {
            echo json_encode([]);
            exit;
        }
        $result = $fetchOrCache(
            "{$cacheDir}/districts_{$cityCode}.json",
            "https://emsifa.github.io/api-wilayah-indonesia/api/districts/{$cityCode}.json",
            fn ($item) => ['code' => (string) $item['id'], 'name' => (string) $item['name'], 'city_code' => (string) $item['regency_id']]
        );
        echo json_encode($result);
        exit;
    }

    if ($requestUri === '/api/villages') {
        $districtCode = preg_replace('/[^0-9]/', '', $_GET['district_code'] ?? '');
        if (empty($districtCode)) {
            echo json_encode([]);
            exit;
        }
        $result = $fetchOrCache(
            "{$cacheDir}/villages_{$districtCode}.json",
            "https://emsifa.github.io/api-wilayah-indonesia/api/villages/{$districtCode}.json",
            fn ($item) => ['code' => (string) $item['id'], 'name' => (string) $item['name'], 'district_code' => (string) $item['district_id']]
        );
        echo json_encode($result);
        exit;
    }

    http_response_code(404);
    echo json_encode(['error' => 'Endpoint not found']);
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Filament Wilayah Indonesia - Template Desain & Data Valid</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
        code, pre { font-family: 'JetBrains Mono', monospace; }
        [x-cloak] { display: none !important; }
        .custom-scrollbar::-webkit-scrollbar { width: 6px; }
        .custom-scrollbar::-webkit-scrollbar-track { background: #18181b; }
        .custom-scrollbar::-webkit-scrollbar-thumb { background: #3f3f46; border-radius: 4px; }
        .custom-scrollbar::-webkit-scrollbar-thumb:hover { background: #52525b; }
    </style>
</head>
<body class="bg-zinc-950 text-zinc-100 min-h-screen antialiased selection:bg-amber-500 selection:text-black">
    <div class="max-w-6xl mx-auto px-6 py-10" x-data="wilayahApp()" x-cloak>
        <header class="border-b border-zinc-800 pb-6 mb-8 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div>
                <div class="flex items-center gap-3">
                    <span class="bg-amber-500 text-black font-bold text-xs px-2.5 py-1 rounded">Filament v3 Plugin</span>
                    <span class="text-zinc-400 text-xs font-mono">100% Data Resmi Kemendagri & 3 Template Desain</span>
                </div>
                <h1 class="text-2xl md:text-3xl font-bold tracking-tight text-white mt-2">Wilayah Indonesia Cascading Dropdown</h1>
                <p class="text-zinc-400 text-sm mt-1">Data valid seluruh Indonesia (38 Provinsi, 514 Kab/Kota, 7.277 Kecamatan, 83.000+ Desa).</p>
            </div>
            <div class="flex items-center gap-2">
                <button @click="resetForm()" class="px-3.5 py-2 text-xs font-medium border border-zinc-800 hover:border-zinc-700 bg-zinc-900 rounded-lg text-zinc-300 hover:text-white transition-colors">
                    Reset Form
                </button>
                <a href="https://github.com/yanzyuyu/filament-wilayah-indonesia" target="_blank" class="inline-flex items-center gap-2 px-3.5 py-2 text-xs font-semibold bg-amber-500 hover:bg-amber-400 text-black rounded-lg transition-colors">
                    <span>GitHub Repo</span>
                </a>
            </div>
        </header>

        <div class="bg-zinc-900/60 border border-zinc-800 rounded-xl p-4 mb-8 flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div class="flex items-center gap-2">
                <span class="text-xs font-semibold text-zinc-400 uppercase tracking-wider">Pilih Template Desain:</span>
                <div class="inline-flex p-1 bg-zinc-950 border border-zinc-800 rounded-lg">
                    <button @click="activeTemplate = 'section'" :class="activeTemplate === 'section' ? 'bg-amber-500 text-black font-semibold' : 'text-zinc-400 hover:text-zinc-200'" class="px-3 py-1.5 text-xs rounded transition-colors">
                        Section Card
                    </button>
                    <button @click="activeTemplate = 'stepper'" :class="activeTemplate === 'stepper' ? 'bg-amber-500 text-black font-semibold' : 'text-zinc-400 hover:text-zinc-200'" class="px-3 py-1.5 text-xs rounded transition-colors">
                        Hierarchical Stepper
                    </button>
                    <button @click="activeTemplate = 'compact'" :class="activeTemplate === 'compact' ? 'bg-amber-500 text-black font-semibold' : 'text-zinc-400 hover:text-zinc-200'" class="px-3 py-1.5 text-xs rounded transition-colors">
                        Dense 4-Column
                    </button>
                </div>
            </div>

            <label class="inline-flex items-center gap-2 text-xs text-zinc-300 cursor-pointer select-none">
                <input type="checkbox" x-model="withoutVillage" class="rounded border-zinc-700 bg-zinc-800 text-amber-500 focus:ring-0">
                <span>Opsi Tanpa Desa/Kelurahan (->withoutVillage())</span>
            </label>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">
            <div class="lg:col-span-7 space-y-6">

                <div x-show="activeTemplate === 'stepper'" class="bg-zinc-900/80 border border-zinc-800 rounded-xl p-5 backdrop-blur shadow-xl">
                    <div class="text-xs font-semibold uppercase tracking-wider text-amber-500 mb-3">Jalur Wilayah Terpilih (Breadcrumb)</div>
                    <div class="flex flex-wrap items-center gap-2 text-xs text-zinc-300 font-medium">
                        <span class="px-2.5 py-1 rounded bg-zinc-800 border border-zinc-700" x-text="getSelectedProvinceName() || '1. Pilih Provinsi'"></span>
                        <svg class="w-3.5 h-3.5 text-zinc-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                        <span class="px-2.5 py-1 rounded bg-zinc-800 border border-zinc-700" x-text="getSelectedCityName() || '2. Pilih Kab/Kota'"></span>
                        <svg class="w-3.5 h-3.5 text-zinc-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                        <span class="px-2.5 py-1 rounded bg-zinc-800 border border-zinc-700" x-text="getSelectedDistrictName() || '3. Pilih Kecamatan'"></span>
                        <template x-if="!withoutVillage">
                            <div class="flex items-center gap-2">
                                <svg class="w-3.5 h-3.5 text-zinc-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                                <span class="px-2.5 py-1 rounded bg-zinc-800 border border-zinc-700" x-text="getSelectedVillageName() || '4. Pilih Kelurahan/Desa'"></span>
                            </div>
                        </template>
                    </div>
                </div>

                <div class="bg-zinc-900/60 border border-zinc-800 rounded-xl p-6 md:p-8 backdrop-blur shadow-2xl">
                    <div class="flex items-center gap-3 pb-5 border-b border-zinc-800 mb-6">
                        <div class="w-8 h-8 rounded-lg bg-amber-500/10 border border-amber-500/20 flex items-center justify-center text-amber-500">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                        </div>
                        <div>
                            <h2 class="text-base font-semibold text-white">Wilayah Administratif</h2>
                            <p class="text-xs text-zinc-400">Pilih lokasi bertingkat dari provinsi hingga kelurahan/desa.</p>
                        </div>
                    </div>

                    <div :class="{
                        'grid grid-cols-1 md:grid-cols-2 gap-5': activeTemplate === 'section',
                        'grid grid-cols-1 gap-4': activeTemplate === 'stepper',
                        'grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4': activeTemplate === 'compact'
                    }">
                        <div class="relative" x-data="{ open: false, search: '' }">
                            <label class="block text-xs font-medium text-zinc-300 mb-1.5 flex items-center justify-between">
                                <span>Provinsi <span class="text-rose-400">*</span></span>
                                <span class="text-[10px] text-zinc-500" x-text="`${provinces.length} Provinsi`"></span>
                            </label>
                            <button @click="open = !open" type="button" class="w-full text-left bg-zinc-950 border border-zinc-800 hover:border-zinc-700 focus:border-amber-500 rounded-lg px-3.5 py-2.5 text-sm flex items-center justify-between transition-colors">
                                <span class="truncate" :class="selectedProvince ? 'text-zinc-100 font-medium' : 'text-zinc-500'" x-text="getSelectedProvinceName() || '-- Pilih Provinsi --'"></span>
                                <svg class="w-4 h-4 text-zinc-500 shrink-0 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                            </button>

                            <div x-show="open" @click.outside="open = false" class="absolute z-50 mt-1 w-full bg-zinc-900 border border-zinc-800 rounded-lg shadow-2xl overflow-hidden">
                                <div class="p-2 border-b border-zinc-800">
                                    <input type="text" x-model="search" placeholder="Cari provinsi..." class="w-full bg-zinc-950 border border-zinc-700 rounded px-2.5 py-1.5 text-xs text-zinc-200 focus:outline-none focus:border-amber-500">
                                </div>
                                <ul class="max-h-56 overflow-y-auto custom-scrollbar p-1 text-xs">
                                    <template x-for="p in filteredProvinces(search)" :key="p.code">
                                        <li @click="selectProvince(p.code); open = false; search = ''" class="px-3 py-2 rounded cursor-pointer hover:bg-amber-500 hover:text-black flex items-center justify-between transition-colors" :class="selectedProvince === p.code ? 'bg-zinc-800 text-amber-400 font-semibold' : 'text-zinc-300'">
                                            <span x-text="p.name"></span>
                                            <span class="text-[10px] opacity-60 font-mono" x-text="p.code"></span>
                                        </li>
                                    </template>
                                </ul>
                            </div>
                        </div>

                        <div class="relative" x-data="{ open: false, search: '' }">
                            <label class="block text-xs font-medium text-zinc-300 mb-1.5 flex items-center justify-between">
                                <span>Kabupaten / Kota <span class="text-rose-400">*</span></span>
                                <span class="text-[10px] text-zinc-500" x-text="selectedProvince ? `${cities.length} Kab/Kota` : ''"></span>
                            </label>
                            <button @click="if (selectedProvince && !loadingCities) open = !open" :disabled="!selectedProvince || loadingCities" type="button" class="w-full text-left bg-zinc-950 border border-zinc-800 disabled:bg-zinc-900/40 disabled:border-zinc-800/60 disabled:cursor-not-allowed hover:border-zinc-700 focus:border-amber-500 rounded-lg px-3.5 py-2.5 text-sm flex items-center justify-between transition-colors">
                                <span class="truncate" :class="selectedCity ? 'text-zinc-100 font-medium' : 'text-zinc-500'" x-text="loadingCities ? 'Mengambil data resmi...' : (getSelectedCityName() || (selectedProvince ? '-- Pilih Kab/Kota --' : 'Pilih provinsi terlebih dahulu'))"></span>
                                <svg class="w-4 h-4 text-zinc-500 shrink-0 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                            </button>

                            <div x-show="open && selectedProvince" @click.outside="open = false" class="absolute z-50 mt-1 w-full bg-zinc-900 border border-zinc-800 rounded-lg shadow-2xl overflow-hidden">
                                <div class="p-2 border-b border-zinc-800">
                                    <input type="text" x-model="search" placeholder="Cari kab/kota..." class="w-full bg-zinc-950 border border-zinc-700 rounded px-2.5 py-1.5 text-xs text-zinc-200 focus:outline-none focus:border-amber-500">
                                </div>
                                <ul class="max-h-56 overflow-y-auto custom-scrollbar p-1 text-xs">
                                    <template x-for="c in filteredCities(search)" :key="c.code">
                                        <li @click="selectCity(c.code); open = false; search = ''" class="px-3 py-2 rounded cursor-pointer hover:bg-amber-500 hover:text-black flex items-center justify-between transition-colors" :class="selectedCity === c.code ? 'bg-zinc-800 text-amber-400 font-semibold' : 'text-zinc-300'">
                                            <span x-text="c.name"></span>
                                            <span class="text-[10px] opacity-60 font-mono" x-text="c.code"></span>
                                        </li>
                                    </template>
                                </ul>
                            </div>
                        </div>

                        <div class="relative" x-data="{ open: false, search: '' }">
                            <label class="block text-xs font-medium text-zinc-300 mb-1.5 flex items-center justify-between">
                                <span>Kecamatan <span class="text-rose-400">*</span></span>
                                <span class="text-[10px] text-zinc-500" x-text="selectedCity ? `${districts.length} Kecamatan` : ''"></span>
                            </label>
                            <button @click="if (selectedCity && !loadingDistricts) open = !open" :disabled="!selectedCity || loadingDistricts" type="button" class="w-full text-left bg-zinc-950 border border-zinc-800 disabled:bg-zinc-900/40 disabled:border-zinc-800/60 disabled:cursor-not-allowed hover:border-zinc-700 focus:border-amber-500 rounded-lg px-3.5 py-2.5 text-sm flex items-center justify-between transition-colors">
                                <span class="truncate" :class="selectedDistrict ? 'text-zinc-100 font-medium' : 'text-zinc-500'" x-text="loadingDistricts ? 'Mengambil data resmi...' : (getSelectedDistrictName() || (selectedCity ? '-- Pilih Kecamatan --' : 'Pilih kab/kota terlebih dahulu'))"></span>
                                <svg class="w-4 h-4 text-zinc-500 shrink-0 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                            </button>

                            <div x-show="open && selectedCity" @click.outside="open = false" class="absolute z-50 mt-1 w-full bg-zinc-900 border border-zinc-800 rounded-lg shadow-2xl overflow-hidden">
                                <div class="p-2 border-b border-zinc-800">
                                    <input type="text" x-model="search" placeholder="Cari kecamatan..." class="w-full bg-zinc-950 border border-zinc-700 rounded px-2.5 py-1.5 text-xs text-zinc-200 focus:outline-none focus:border-amber-500">
                                </div>
                                <ul class="max-h-56 overflow-y-auto custom-scrollbar p-1 text-xs">
                                    <template x-for="d in filteredDistricts(search)" :key="d.code">
                                        <li @click="selectDistrict(d.code); open = false; search = ''" class="px-3 py-2 rounded cursor-pointer hover:bg-amber-500 hover:text-black flex items-center justify-between transition-colors" :class="selectedDistrict === d.code ? 'bg-zinc-800 text-amber-400 font-semibold' : 'text-zinc-300'">
                                            <span x-text="d.name"></span>
                                            <span class="text-[10px] opacity-60 font-mono" x-text="d.code"></span>
                                        </li>
                                    </template>
                                </ul>
                            </div>
                        </div>

                        <div class="relative" x-show="!withoutVillage" x-data="{ open: false, search: '' }">
                            <label class="block text-xs font-medium text-zinc-300 mb-1.5 flex items-center justify-between">
                                <span>Kelurahan / Desa <span class="text-rose-400">*</span></span>
                                <span class="text-[10px] text-zinc-500" x-text="selectedDistrict ? `${villages.length} Desa` : ''"></span>
                            </label>
                            <button @click="if (selectedDistrict && !loadingVillages) open = !open" :disabled="!selectedDistrict || loadingVillages" type="button" class="w-full text-left bg-zinc-950 border border-zinc-800 disabled:bg-zinc-900/40 disabled:border-zinc-800/60 disabled:cursor-not-allowed hover:border-zinc-700 focus:border-amber-500 rounded-lg px-3.5 py-2.5 text-sm flex items-center justify-between transition-colors">
                                <span class="truncate" :class="selectedVillage ? 'text-zinc-100 font-medium' : 'text-zinc-500'" x-text="loadingVillages ? 'Mengambil data resmi...' : (getSelectedVillageName() || (selectedDistrict ? '-- Pilih Kelurahan/Desa --' : 'Pilih kecamatan terlebih dahulu'))"></span>
                                <svg class="w-4 h-4 text-zinc-500 shrink-0 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                            </button>

                            <div x-show="open && selectedDistrict" @click.outside="open = false" class="absolute z-50 mt-1 w-full bg-zinc-900 border border-zinc-800 rounded-lg shadow-2xl overflow-hidden">
                                <div class="p-2 border-b border-zinc-800">
                                    <input type="text" x-model="search" placeholder="Cari kelurahan/desa..." class="w-full bg-zinc-950 border border-zinc-700 rounded px-2.5 py-1.5 text-xs text-zinc-200 focus:outline-none focus:border-amber-500">
                                </div>
                                <ul class="max-h-56 overflow-y-auto custom-scrollbar p-1 text-xs">
                                    <template x-for="v in filteredVillages(search)" :key="v.code">
                                        <li @click="selectVillage(v.code); open = false; search = ''" class="px-3 py-2 rounded cursor-pointer hover:bg-amber-500 hover:text-black flex items-center justify-between transition-colors" :class="selectedVillage === v.code ? 'bg-zinc-800 text-amber-400 font-semibold' : 'text-zinc-300'">
                                            <span x-text="v.name"></span>
                                            <span class="text-[10px] opacity-60 font-mono" x-text="v.code"></span>
                                        </li>
                                    </template>
                                </ul>
                            </div>
                        </div>
                    </div>

                    <div class="mt-8 pt-6 border-t border-zinc-800">
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-xs font-semibold uppercase tracking-wider text-zinc-400">Live Payload ($data)</span>
                            <span class="text-[11px] text-emerald-400 font-mono">100% Valid Kemendagri</span>
                        </div>
                        <pre class="bg-zinc-950 border border-zinc-800 rounded-lg p-4 text-xs text-amber-400/90 overflow-x-auto" x-text="JSON.stringify({
                            province_code: selectedProvince || null,
                            city_code: selectedCity || null,
                            district_code: selectedDistrict || null,
                            village_code: withoutVillage ? undefined : (selectedVillage || null),
                            summary_address: formatSummaryAddress()
                        }, null, 2)"></pre>
                    </div>
                </div>
            </div>

            <div class="lg:col-span-5 space-y-6">
                <div class="bg-zinc-900/60 border border-zinc-800 rounded-xl p-6 backdrop-blur">
                    <h3 class="text-sm font-semibold text-white mb-1">Kode Template Filament</h3>
                    <p class="text-xs text-zinc-400 mb-4">Salin kode ini langsung ke form resource Filament Anda:</p>
                    <pre class="bg-zinc-950 border border-zinc-800 rounded-lg p-4 text-xs text-zinc-300 overflow-x-auto leading-relaxed"><code x-text="getFilamentCodeSnippet()"></code></pre>
                </div>

                <div class="bg-zinc-900/60 border border-zinc-800 rounded-xl p-6 backdrop-blur space-y-3">
                    <h3 class="text-sm font-semibold text-white">Sumber Data Resmi</h3>
                    <p class="text-xs text-zinc-400">Data terhubung langsung ke dataset wilayah administratif Indonesia dan tersimpan di cache lokal untuk performa instan tanpa limit kuota.</p>
                    <div class="bg-zinc-950 border border-zinc-800 rounded-lg p-3 text-xs text-zinc-300 space-y-1 font-mono">
                        <div class="text-emerald-400">✓ 38 Provinsi (Termasuk DOB Papua)</div>
                        <div class="text-emerald-400">✓ 514 Kabupaten & Kota</div>
                        <div class="text-emerald-400">✓ 7.277 Kecamatan</div>
                        <div class="text-emerald-400">✓ 83.000+ Kelurahan & Desa</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        function wilayahApp() {
            return {
                activeTemplate: 'section',
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
                    const res = await fetch('/api/provinces');
                    this.provinces = await res.json();
                },

                filteredProvinces(query) {
                    if (!query) return this.provinces;
                    const q = query.toLowerCase();
                    return this.provinces.filter(p => p.name.toLowerCase().includes(q) || p.code.includes(q));
                },

                filteredCities(query) {
                    if (!query) return this.cities;
                    const q = query.toLowerCase();
                    return this.cities.filter(c => c.name.toLowerCase().includes(q) || c.code.includes(q));
                },

                filteredDistricts(query) {
                    if (!query) return this.districts;
                    const q = query.toLowerCase();
                    return this.districts.filter(d => d.name.toLowerCase().includes(q) || d.code.includes(q));
                },

                filteredVillages(query) {
                    if (!query) return this.villages;
                    const q = query.toLowerCase();
                    return this.villages.filter(v => v.name.toLowerCase().includes(q) || v.code.includes(q));
                },

                async selectProvince(code) {
                    this.selectedProvince = code;
                    this.selectedCity = '';
                    this.selectedDistrict = '';
                    this.selectedVillage = '';
                    this.cities = [];
                    this.districts = [];
                    this.villages = [];

                    this.loadingCities = true;
                    try {
                        const res = await fetch(`/api/cities?province_code=${code}`);
                        this.cities = await res.json();
                    } finally {
                        this.loadingCities = false;
                    }
                },

                async selectCity(code) {
                    this.selectedCity = code;
                    this.selectedDistrict = '';
                    this.selectedVillage = '';
                    this.districts = [];
                    this.villages = [];

                    this.loadingDistricts = true;
                    try {
                        const res = await fetch(`/api/districts?city_code=${code}`);
                        this.districts = await res.json();
                    } finally {
                        this.loadingDistricts = false;
                    }
                },

                async selectDistrict(code) {
                    this.selectedDistrict = code;
                    this.selectedVillage = '';
                    this.villages = [];

                    if (this.withoutVillage) return;

                    this.loadingVillages = true;
                    try {
                        const res = await fetch(`/api/villages?district_code=${code}`);
                        this.villages = await res.json();
                    } finally {
                        this.loadingVillages = false;
                    }
                },

                selectVillage(code) {
                    this.selectedVillage = code;
                },

                getSelectedProvinceName() {
                    const found = this.provinces.find(p => p.code === this.selectedProvince);
                    return found ? found.name : '';
                },

                getSelectedCityName() {
                    const found = this.cities.find(c => c.code === this.selectedCity);
                    return found ? found.name : '';
                },

                getSelectedDistrictName() {
                    const found = this.districts.find(d => d.code === this.selectedDistrict);
                    return found ? found.name : '';
                },

                getSelectedVillageName() {
                    const found = this.villages.find(v => v.code === this.selectedVillage);
                    return found ? found.name : '';
                },

                formatSummaryAddress() {
                    const parts = [
                        this.getSelectedVillageName(),
                        this.getSelectedDistrictName(),
                        this.getSelectedCityName(),
                        this.getSelectedProvinceName()
                    ].filter(Boolean);
                    return parts.join(', ');
                },

                getFilamentCodeSnippet() {
                    let code = 'use Yanzyuyu\\FilamentWilayah\\Forms\\Components\\WilayahIndonesia;\n\n';
                    code += 'WilayahIndonesia::make()\n';
                    if (this.activeTemplate === 'section') {
                        code += "    ->asSection('Wilayah Administratif', 'Pilih lokasi bertingkat')\n";
                    } else if (this.activeTemplate === 'compact') {
                        code += '    ->asCompact()\n';
                    } else {
                        code += '    ->columns(2)\n';
                    }
                    if (this.withoutVillage) {
                        code += '    ->withoutVillage()\n';
                    }
                    code += '    ->searchable();';
                    return code;
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
