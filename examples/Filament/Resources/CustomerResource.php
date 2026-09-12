<?php

namespace App\Filament\Resources;

use App\Models\Customer;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Yanzyuyu\FilamentWilayah\Forms\Components\WilayahIndonesia;

class CustomerResource extends Resource
{
    protected static ?string $model = Customer::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-group';

    protected static ?string $navigationLabel = 'Pelanggan';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Section::make('Informasi Pelanggan')
                ->schema([
                    TextInput::make('name')
                        ->label('Nama Lengkap')
                        ->required()
                        ->maxLength(255),

                    TextInput::make('phone')
                        ->label('Nomor WhatsApp / HP')
                        ->tel()
                        ->maxLength(20),

                    TextInput::make('email')
                        ->label('Alamat Email')
                        ->email()
                        ->maxLength(255),
                ])
                ->columns(3),

            WilayahIndonesia::make()
                ->asSection('Alamat & Wilayah Administratif', 'Pilih lokasi bertingkat dari provinsi hingga desa/kelurahan')
                ->columns(2)
                ->searchable()
                ->required(),

            Section::make('Detail Alamat Tambahan')
                ->schema([
                    TextInput::make('postal_code')
                        ->label('Kode Pos')
                        ->maxLength(10),

                    Textarea::make('street_address')
                        ->label('Alamat Lengkap (Jalan, RT/RW, Patokan)')
                        ->rows(2)
                        ->columnSpanFull(),
                ])
                ->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Nama')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('phone')
                    ->label('Telepon')
                    ->searchable(),

                TextColumn::make('province.name')
                    ->label('Provinsi')
                    ->sortable()
                    ->badge(),

                TextColumn::make('city.name')
                    ->label('Kab/Kota')
                    ->sortable(),

                TextColumn::make('district.name')
                    ->label('Kecamatan')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('village.name')
                    ->label('Desa/Kelurahan')
                    ->toggleable(isToggledHiddenByDefault: true),
            ]);
    }
}
