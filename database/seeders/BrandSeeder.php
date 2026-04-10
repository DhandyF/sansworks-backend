<?php

namespace Database\Seeders;

use App\Models\Brand;
use Illuminate\Database\Seeder;

class BrandSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $brands = [
            [
                'name' => 'Fashion Forward',
                'code' => 'BRD001',
                'phone' => '+6289876543210',
                'address' => 'Jl. Sudirman No. 100, Jakarta',
                'notes' => 'Premium casual fashion brand',
            ],
            [
                'name' => 'Executive Wear',
                'code' => 'BRD002',
                'phone' => '+6289876543211',
                'address' => 'Jl. Thamrin No. 200, Jakarta',
                'notes' => 'Formal and business attire',
            ],
            [
                'name' => 'Urban Style',
                'code' => 'BRD003',
                'phone' => '+6289876543212',
                'address' => 'Jl. Asia Afrika No. 300, Bandung',
                'notes' => 'Streetwear and trendy fashion',
            ],
        ];

        foreach ($brands as $brand) {
            Brand::firstOrCreate(
                ['code' => $brand['code']],
                [
                    'name' => $brand['name'],
                    'phone' => $brand['phone'],
                    'address' => $brand['address'],
                    'code' => $brand['code'],
                    'is_active' => true,
                    'notes' => $brand['notes'],
                ]
            );
        }
    }
}
