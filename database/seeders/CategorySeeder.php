<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    /**
     * Seed default system categories.
     */
    public function run(): void
    {
        $categories = [
            // Expense categories
            ['name' => 'Makanan & Minuman', 'icon' => '🍔', 'color' => '#ef4444', 'type' => 'expense'],
            ['name' => 'Transportasi', 'icon' => '🚗', 'color' => '#f97316', 'type' => 'expense'],
            ['name' => 'Belanja', 'icon' => '🛒', 'color' => '#eab308', 'type' => 'expense'],
            ['name' => 'Tagihan & Utilitas', 'icon' => '💡', 'color' => '#84cc16', 'type' => 'expense'],
            ['name' => 'Hiburan', 'icon' => '🎬', 'color' => '#06b6d4', 'type' => 'expense'],
            ['name' => 'Kesehatan', 'icon' => '🏥', 'color' => '#8b5cf6', 'type' => 'expense'],
            ['name' => 'Pendidikan', 'icon' => '📚', 'color' => '#ec4899', 'type' => 'expense'],
            ['name' => 'Pakaian', 'icon' => '👕', 'color' => '#14b8a6', 'type' => 'expense'],
            ['name' => 'Rumah Tangga', 'icon' => '🏠', 'color' => '#f59e0b', 'type' => 'expense'],
            ['name' => 'Lainnya', 'icon' => '📦', 'color' => '#6b7280', 'type' => 'expense'],

            // Income categories
            ['name' => 'Gaji', 'icon' => '💰', 'color' => '#10b981', 'type' => 'income'],
            ['name' => 'Freelance', 'icon' => '💻', 'color' => '#3b82f6', 'type' => 'income'],
            ['name' => 'Investasi', 'icon' => '📈', 'color' => '#8b5cf6', 'type' => 'income'],
            ['name' => 'Hadiah', 'icon' => '🎁', 'color' => '#ec4899', 'type' => 'income'],
            ['name' => 'Penjualan', 'icon' => '🏷️', 'color' => '#f97316', 'type' => 'income'],
            ['name' => 'Pendapatan Lain', 'icon' => '✨', 'color' => '#6366f1', 'type' => 'income'],
        ];

        foreach ($categories as $category) {
            Category::firstOrCreate(
                ['name' => $category['name'], 'user_id' => null],
                $category
            );
        }
    }
}
