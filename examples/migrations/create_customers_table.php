<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('phone')->nullable();
            $table->string('email')->nullable();

            $table->string('province_code', 10)->nullable()->index();
            $table->string('city_code', 10)->nullable()->index();
            $table->string('district_code', 10)->nullable()->index();
            $table->string('village_code', 15)->nullable()->index();

            $table->string('postal_code', 10)->nullable();
            $table->text('street_address')->nullable();
            $table->timestamps();

            $table->foreign('province_code')
                ->references('code')
                ->on('wilayah_provinces')
                ->nullOnDelete();

            $table->foreign('city_code')
                ->references('code')
                ->on('wilayah_cities')
                ->nullOnDelete();

            $table->foreign('district_code')
                ->references('code')
                ->on('wilayah_districts')
                ->nullOnDelete();

            $table->foreign('village_code')
                ->references('code')
                ->on('wilayah_villages')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customers');
    }
};
