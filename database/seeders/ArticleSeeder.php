<?php

namespace Database\Seeders;

use App\Models\Article;
use Illuminate\Database\Seeder;

class ArticleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $articles = [
            [
                'name' => 'Kemeja Casual',
                'code' => 'ART001',
                'sewing_price' => 25000,
                'description' => 'Casual shirt with standard quality',
            ],
            [
                'name' => 'Kemeja Formal',
                'code' => 'ART002',
                'sewing_price' => 35000,
                'description' => 'Formal shirt with premium quality',
            ],
            [
                'name' => 'Kaos Polos',
                'code' => 'ART003',
                'sewing_price' => 15000,
                'description' => 'Plain t-shirt basic',
            ],
            [
                'name' => 'Celana Panjang',
                'code' => 'ART004',
                'sewing_price' => 30000,
                'description' => 'Long pants/trousers',
            ],
            [
                'name' => 'Celana Pendek',
                'code' => 'ART005',
                'sewing_price' => 20000,
                'description' => 'Short pants',
            ],
            [
                'name' => 'Jaket Hoodie',
                'code' => 'ART006',
                'sewing_price' => 45000,
                'description' => 'Hoodie jacket',
            ],
            [
                'name' => 'Dress Wanita',
                'code' => 'ART007',
                'sewing_price' => 40000,
                'description' => 'Women dress',
            ],
            [
                'name' => 'Rok Panjang',
                'code' => 'ART008',
                'sewing_price' => 25000,
                'description' => 'Long skirt',
            ],
        ];

        foreach ($articles as $article) {
            Article::firstOrCreate(
                ['code' => $article['code']],
                [
                    'name' => $article['name'],
                    'sewing_price' => $article['sewing_price'],
                    'code' => $article['code'],
                    'description' => $article['description'],
                    'is_active' => true,
                ]
            );
        }
    }
}
