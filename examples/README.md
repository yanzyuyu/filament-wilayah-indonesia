# Contoh Implementasi Nyata (Real-World Examples)

Folder ini berisi contoh integrasi end-to-end penggunaan `filament-wilayah-indonesia` pada aplikasi Laravel & Filament v3 sungguhan.

---

## Daftar File Contoh

1. `migrations/create_customers_table.php`
   Skema tabel database dengan kolom `province_code`, `city_code`, `district_code`, dan `village_code` beserta foreign key constraint yang tepat.

2. `Models/Customer.php`
   Model Eloquent yang telah mendefinisikan relasi:
   - `$customer->province` (Model `Province`)
   - `$customer->city` (Model `City`)
   - `$customer->district` (Model `District`)
   - `$customer->village` (Model `Village`)
   - Accessor `$customer->full_address` yang otomatis menggabungkan alamat lengkap.

3. `Filament/Resources/CustomerResource.php`
   Resource Filament v3 lengkap dengan form input bertingkat menggunakan `WilayahIndonesia::make()->asSection()` dan tampilan tabel daftar pelanggan.

---

## Cara Menggunakan Contoh Ini di Proyek Anda

### 1. Salin Skema Migrasi
Salin kolom wilayah dari `migrations/create_customers_table.php` ke file migrasi tabel Anda (misal `users`, `stores`, `orders`):

```php
$table->string('province_code', 10)->nullable()->index();
$table->string('city_code', 10)->nullable()->index();
$table->string('district_code', 10)->nullable()->index();
$table->string('village_code', 15)->nullable()->index();
```

### 2. Tambahkan Relasi di Model Eloquent
Tambahkan method relasi berikut ke model Anda:

```php
use Yanzyuyu\FilamentWilayah\Models\Province;
use Yanzyuyu\FilamentWilayah\Models\City;
use Yanzyuyu\FilamentWilayah\Models\District;
use Yanzyuyu\FilamentWilayah\Models\Village;

public function province()
{
    return $this->belongsTo(Province::class, 'province_code', 'code');
}

public function city()
{
    return $this->belongsTo(City::class, 'city_code', 'code');
}

public function district()
{
    return $this->belongsTo(District::class, 'district_code', 'code');
}

public function village()
{
    return $this->belongsTo(Village::class, 'village_code', 'code');
}
```

### 3. Tambahkan ke Form Filament Resource
Tambahkan komponen pada schema form Anda:

```php
use Yanzyuyu\FilamentWilayah\Forms\Components\WilayahIndonesia;

WilayahIndonesia::make()
    ->asSection('Alamat Pengiriman', 'Pilih lokasi bertingkat')
    ->columns(2)
    ->searchable()
    ->required()
```
