<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Product;
use App\Models\Tag;
use App\Models\PromoCode;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run()
    {
        // Create admin user
        User::create([
            'username' => 'admin',
            'email' => 'admin@magazine.com',
            'password' => Hash::make('password'),
            'role' => 'admin',
            'city' => 'Casablanca',
            'address' => '123 Admin Street',
            'fidelity_points' => 1000
        ]);
        
        // Create regular user
        User::create([
            'username' => 'john_doe',
            'email' => 'john@example.com',
            'password' => Hash::make('password'),
            'role' => 'user',
            'city' => 'Rabat',
            'address' => '456 User Avenue',
            'fidelity_points' => 50
        ]);
        
        // Create tags
        $tags = ['Fashion', 'Electronics', 'Books', 'Sports', 'Home', 'Beauty'];
        foreach ($tags as $tagName) {
            Tag::create(['name' => $tagName]);
        }
        
        // Create sample products
        $products = [
            [
                'title' => 'Smartphone X',
                'description' => 'Latest smartphone with amazing features',
                'price' => 599.99,
                'discount_percentage' => 10,
                'stock' => 50,
                'theme' => 'Electronics'
            ],
            [
                'title' => 'Designer T-Shirt',
                'description' => 'Comfortable cotton t-shirt',
                'price' => 29.99,
                'discount_percentage' => 0,
                'stock' => 100,
                'theme' => 'Fashion'
            ],
            [
                'title' => 'Programming Book',
                'description' => 'Learn Laravel from scratch',
                'price' => 49.99,
                'discount_percentage' => 20,
                'stock' => 30,
                'theme' => 'Books'
            ]
        ];
        
        foreach ($products as $productData) {
            Product::create($productData);
        }
        
        // Create promo code
        PromoCode::create([
            'code' => 'WELCOME20',
            'discount_percentage' => 20,
            'max_usage' => 100,
            'used_count' => 0,
            'expires_at' => now()->addMonths(1)
        ]);
    }
}