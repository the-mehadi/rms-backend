<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            [
                'name' => 'Appetizers',
                'slug' => Str::slug('Appetizers'),
                'description' => 'Delicious starters to begin your meal',
                'is_active' => true,
                'order' => 1,
            ],
            [
                'name' => 'Main Courses',
                'slug' => Str::slug('Main Courses'),
                'description' => 'Hearty main dishes for your dining pleasure',
                'is_active' => true,
                'order' => 2,
            ],
            [
                'name' => 'Desserts',
                'slug' => Str::slug('Desserts'),
                'description' => 'Sweet treats to end your meal perfectly',
                'is_active' => true,
                'order' => 3,
            ],
            [
                'name' => 'Beverages',
                'slug' => Str::slug('Beverages'),
                'description' => 'Refreshing drinks and beverages',
                'is_active' => true,
                'order' => 4,
            ],
            [
                'name' => 'Salads',
                'slug' => Str::slug('Salads'),
                'description' => 'Fresh and healthy salad options',
                'is_active' => true,
                'order' => 5,
            ],
        ];

        foreach ($categories as $categoryData) {
            Category::updateOrCreate(
                ['slug' => $categoryData['slug']],
                $categoryData
            );
        }

        $this->command->info('✅ Categories seeded: ' . count($categories) . ' categories');
    }
}
