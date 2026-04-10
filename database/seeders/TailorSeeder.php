<?php

namespace Database\Seeders;

use App\Models\Tailor;
use Illuminate\Database\Seeder;

class TailorSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $tailors = [
            [
                'name' => 'CV. Jaya Makmur',
                'code' => 'TLR001',
                'phone' => '+6281234567890',
                'address' => 'Jl. Garut No. 123, Jawa Barat',
                'notes' => 'Specialized in casual wear',
            ],
            [
                'name' => 'UD. Berkah Abadi',
                'code' => 'TLR002',
                'phone' => '+6281234567891',
                'address' => 'Jl. Bandung No. 456, Jawa Barat',
                'notes' => 'Specialized in formal wear',
            ],
            [
                'name' => 'Tenun Cahaya',
                'code' => 'TLR003',
                'phone' => '+6281234567892',
                'address' => 'Jl. Surabaya No. 789, Jawa Timur',
                'notes' => 'Specialized in traditional fabrics',
            ],
        ];

        foreach ($tailors as $tailor) {
            Tailor::firstOrCreate(
                ['code' => $tailor['code']],
                [
                    'name' => $tailor['name'],
                    'phone' => $tailor['phone'],
                    'address' => $tailor['address'],
                    'code' => $tailor['code'],
                    'is_active' => true,
                    'notes' => $tailor['notes'],
                ]
            );
        }
    }
}
