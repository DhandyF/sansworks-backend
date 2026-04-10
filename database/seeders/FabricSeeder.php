<?php

namespace Database\Seeders;

use App\Models\Fabric;
use Illuminate\Database\Seeder;

class FabricSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $fabrics = [
            [
                'name' => 'Katun Primisima',
                'color' => 'Putih',
                'unit' => 'meter',
                'total_quantity' => 500,
                'price_per_unit' => 45000,
            ],
            [
                'name' => 'Katun Primisima',
                'color' => 'Hitam',
                'unit' => 'meter',
                'total_quantity' => 400,
                'price_per_unit' => 45000,
            ],
            [
                'name' => 'Lacoste',
                'color' => 'Biru Navy',
                'unit' => 'meter',
                'total_quantity' => 300,
                'price_per_unit' => 55000,
            ],
            [
                'name' => 'Lacoste',
                'color' => 'Merah',
                'unit' => 'meter',
                'total_quantity' => 250,
                'price_per_unit' => 55000,
            ],
            [
                'name' => 'Bahan Kaos PE',
                'color' => 'Putih',
                'unit' => 'roll',
                'total_quantity' => 50,
                'price_per_unit' => 150000,
            ],
            [
                'name' => 'Bahan Kaos PE',
                'color' => 'Hitam',
                'unit' => 'roll',
                'total_quantity' => 40,
                'price_per_unit' => 150000,
            ],
            [
                'name' => 'Denim',
                'color' => 'Biru Dongker',
                'unit' => 'meter',
                'total_quantity' => 600,
                'price_per_unit' => 75000,
            ],
            [
                'name' => 'Sutra',
                'color' => 'Emas',
                'unit' => 'meter',
                'total_quantity' => 100,
                'price_per_unit' => 120000,
            ],
        ];

        foreach ($fabrics as $fabric) {
            Fabric::firstOrCreate(
                [
                    'name' => $fabric['name'],
                    'color' => $fabric['color'],
                ],
                [
                    'name' => $fabric['name'],
                    'color' => $fabric['color'],
                    'unit' => $fabric['unit'],
                    'total_quantity' => $fabric['total_quantity'],
                    'price_per_unit' => $fabric['price_per_unit'],
                ]
            );
        }
    }
}
