# Filament Wilayah Indonesia

Cascading dropdown reaktif untuk data wilayah administratif Indonesia (Provinsi, Kabupaten/Kota, Kecamatan, Kelurahan/Desa) pada form Filament v3.

Menyusun form alamat bertingkat di Laravel Filament biasanya memerlukan penulisan berulang untuk dependency logic, state update Livewire, query filtering, dan reset field manual. Package ini mengemas seluruh kebutuhan tersebut ke dalam satu komponen deklaratif siap pakai.

---

## Fitur Utama

- Integrasi instan ke schema Form Filament v3 via `WilayahIndonesia::make()`.
- Reaktivitas otomatis: pemilihan provinsi otomatis memfilter kota, mereset kecamatan dan kelurahan.
- Mendukung kustomisasi nama kolom database per field (`province_id`, `city_id`, dsb).
- Opsi tanpa kelurahan (`withoutVillage`) jika kebutuhan form hanya sampai tingkat kecamatan.
- Built-in dataset resmi 38 Provinsi Indonesia (termasuk 4 DOB Papua).
- Dukungan cache internal untuk meminimalkan beban query database saat form diakses bersamaan.

---

## Instalasi

### 1. Require Package via Composer

```bash
composer require yanzyuyu/filament-wilayah-indonesia
```

### 2. Jalankan Installer Command

Command ini otomatis mempublikasikan migration dan mengisi data master wilayah ke database:

```bash
php artisan wilayah:install
```

Opsi tambahan:
- `php artisan wilayah:install --skip-seed`: Menjalankan migrasi tanpa seeding data.
- `php artisan wilayah:install --force`: Menghapus data lama dan mengimpor ulang.

---

## Cara Penggunaan

### Penggunaan Standar

Panggil `WilayahIndonesia::make()` langsung di dalam schema form Filament Resource atau Page:

```php
use Yanzyuyu\FilamentWilayah\Forms\Components\WilayahIndonesia;

public static function form(Form $form): Form
{
    return $form->schema([
        WilayahIndonesia::make()
            ->columns(2),
    ]);
}
```

Secara default, komponen ini akan mengikat ke atribut model berikut:
- `province_code`
- `city_code`
- `district_code`
- `village_code`

---

### Kustomisasi Nama Kolom & Label

Jika tabel database Anda menggunakan konvensi nama kolom yang berbeda:

```php
WilayahIndonesia::make()
    ->provinceField('provinsi_id')
    ->cityField('kabupaten_id')
    ->districtField('kecamatan_id')
    ->villageField('desa_id')
    ->provinceLabel('Pilih Provinsi')
    ->cityLabel('Pilih Kab/Kota')
    ->columns(2)
    ->required()
```

---

### Menonaktifkan Pilihan Kelurahan / Desa

Jika formulir Anda hanya membutuhkan pendataan hingga level kecamatan:

```php
WilayahIndonesia::make()
    ->withoutVillage()
    ->columns(3)
```

---

## Konfigurasi

Publish file konfigurasi jika Anda ingin mengubah nama tabel atau pengaturan cache:

```bash
php artisan vendor:publish --tag="filament-wilayah-config"
```

File konfigurasi akan tersimpan di `config/filament-wilayah.php`:

```php
return [
    'tables' => [
        'provinces' => 'wilayah_provinces',
        'cities' => 'wilayah_cities',
        'districts' => 'wilayah_districts',
        'villages' => 'wilayah_villages',
    ],

    'cache' => [
        'enabled' => true,
        'ttl_seconds' => 86400,
    ],
];
```

---

## Struktur Repositori

```
filament-wilayah-indonesia/
├── .github/
│   └── workflows/
│       └── run-tests.yml
├── config/
│   └── filament-wilayah.php
├── database/
│   ├── data/
│   │   ├── cities.json
│   │   ├── districts.json
│   │   ├── provinces.json
│   │   └── villages.json
│   └── migrations/
│       └── create_wilayah_indonesia_tables.php.stub
├── src/
│   ├── Commands/
│   │   └── InstallWilayahCommand.php
│   ├── Forms/
│   │   └── Components/
│   │       └── WilayahIndonesia.php
│   ├── Models/
│   │   ├── City.php
│   │   ├── District.php
│   │   ├── Province.php
│   │   └── Village.php
│   └── FilamentWilayahServiceProvider.php
├── composer.json
├── LICENSE
└── README.md
```

---

## Model Relasi Eloquent

Package ini menyertakan Eloquent Model yang dapat digunakan langsung pada model aplikasi Anda:

```php
use Yanzyuyu\FilamentWilayah\Models\Province;
use Yanzyuyu\FilamentWilayah\Models\City;
use Yanzyuyu\FilamentWilayah\Models\District;
use Yanzyuyu\FilamentWilayah\Models\Village;

// Contoh relasi pada model User atau Store
public function province()
{
    return $this->belongsTo(Province::class, 'province_code', 'code');
}

public function city()
{
    return $this->belongsTo(City::class, 'city_code', 'code');
}
```

---

## Lisensi

Lisensi MIT. Silakan gunakan dan sesuaikan sesuai kebutuhan proyek Anda.
