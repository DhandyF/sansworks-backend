<?php

namespace Database\Seeders;

use App\Models\Size;
use Illuminate\Database\Seeder;

class SizeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $sizes = [
            ['name' => 'Extra Small', 'abbreviation' => 'XS', 'sort_order' => 1],
            ['name' => 'Small', 'abbreviation' => 'S', 'sort_order' => 2],
            ['name' => 'Medium', 'abbreviation' => 'M', 'sort_order' => 3],
            ['name' => 'Large', 'abbreviation' => 'L', 'sort_order' => 4],
            ['name' => 'Extra Large', 'abbreviation' => 'XL', 'sort_order' => 5],
            ['name' => 'Double Extra Large', 'abbreviation' => 'XXL', 'sort_order' => 6],
            ['name' => 'Triple Extra Large', 'abbreviation' => '3XL', 'sort_order' => 7],
            ['name' => 'Quadruple Extra Large', 'abbreviation' => '4XL', 'sort_order' => 8],
        ];

        foreach ($sizes as $size) {
            Size::firstOrCreate(
                ['abbreviation' => $size['abbreviation']],
                [
                    'name' => $size['name'],
                    'abbreviation' => $size['abbreviation'],
                    'sort_order' => $size['sort_order'],
                    'is_active' => true,
                ]
            );
        }
    }
}
