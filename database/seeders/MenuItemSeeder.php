<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\MenuItem;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class MenuItemSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $menuItems = [
            // Appetizers
            [
                'category_slug' => 'appetizers',
                'name' => 'Chicken Wings',
                'price' => 12.99,
                'description' => 'Crispy fried chicken wings with buffalo sauce',
                'is_available' => true,
                'order' => 1,
            ],
            [
                'category_slug' => 'appetizers',
                'name' => 'Mozzarella Sticks',
                'price' => 8.99,
                'description' => 'Golden fried mozzarella cheese sticks',
                'is_available' => true,
                'order' => 2,
            ],
            [
                'category_slug' => 'appetizers',
                'name' => 'Nachos',
                'price' => 10.99,
                'description' => 'Tortilla chips with cheese, jalapeños, and salsa',
                'is_available' => true,
                'order' => 3,
            ],

            // Main Courses
            [
                'category_slug' => 'main-courses',
                'name' => 'Grilled Salmon',
                'price' => 24.99,
                'description' => 'Fresh Atlantic salmon grilled to perfection',
                'is_available' => true,
                'order' => 1,
            ],
            [
                'category_slug' => 'main-courses',
                'name' => 'Beef Burger',
                'price' => 16.99,
                'description' => 'Juicy beef patty with lettuce, tomato, and cheese',
                'is_available' => true,
                'order' => 2,
            ],
            [
                'category_slug' => 'main-courses',
                'name' => 'Chicken Alfredo',
                'price' => 18.99,
                'description' => 'Creamy fettuccine pasta with grilled chicken',
                'is_available' => true,
                'order' => 3,
            ],
            [
                'category_slug' => 'main-courses',
                'name' => 'Vegetable Stir Fry',
                'price' => 14.99,
                'description' => 'Mixed vegetables stir-fried with tofu',
                'is_available' => true,
                'order' => 4,
            ],

            // Desserts
            [
                'category_slug' => 'desserts',
                'name' => 'Chocolate Cake',
                'price' => 7.99,
                'description' => 'Rich chocolate cake with vanilla frosting',
                'is_available' => true,
                'order' => 1,
            ],
            [
                'category_slug' => 'desserts',
                'name' => 'Ice Cream Sundae',
                'price' => 6.99,
                'description' => 'Vanilla ice cream with chocolate syrup and nuts',
                'is_available' => true,
                'order' => 2,
            ],

            // Beverages
            [
                'category_slug' => 'beverages',
                'name' => 'Coca Cola',
                'price' => 2.99,
                'description' => 'Classic cola drink',
                'is_available' => true,
                'order' => 1,
            ],
            [
                'category_slug' => 'beverages',
                'name' => 'Fresh Orange Juice',
                'price' => 4.99,
                'description' => 'Freshly squeezed orange juice',
                'is_available' => true,
                'order' => 2,
            ],

            // Salads
            [
                'category_slug' => 'salads',
                'name' => 'Caesar Salad',
                'price' => 11.99,
                'description' => 'Crisp romaine lettuce with Caesar dressing and croutons',
                'is_available' => true,
                'order' => 1,
            ],
            [
                'category_slug' => 'salads',
                'name' => 'Greek Salad',
                'price' => 13.99,
                'description' => 'Mixed greens with feta cheese, olives, and vinaigrette',
                'is_available' => true,
                'order' => 2,
            ],
        ];

        foreach ($menuItems as $itemData) {
            $category = Category::where('slug', $itemData['category_slug'])->first();
            if ($category) {
                MenuItem::updateOrCreate(
                    [
                        'name' => $itemData['name'],
                        'category_id' => $category->id,
                    ],
                    [
                        'category_id' => $category->id,
                        'slug' => Str::slug($itemData['name']),
                        'price' => $itemData['price'],
                        'description' => $itemData['description'],
                        'is_available' => $itemData['is_available'],
                        'order' => $itemData['order'],
                    ]
                );
            }
        }

        $this->command->info('✅ Menu items seeded: ' . count($menuItems) . ' items');
    }
}
