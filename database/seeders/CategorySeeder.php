<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            // Income
            [
                'nama' => 'Gaji',
                'tipe' => 'income',
                'ikon' => 'briefcase',
                'warna' => '#10b981', // emerald
            ],
            [
                'nama' => 'Uang Saku',
                'tipe' => 'income',
                'ikon' => 'wallet',
                'warna' => '#06b6d4', // cyan
            ],
            [
                'nama' => 'Bonus/Lainnya',
                'tipe' => 'income',
                'ikon' => 'sparkles',
                'warna' => '#8b5cf6', // purple
            ],

            // Expense
            [
                'nama' => 'Makan & Minum',
                'tipe' => 'expense',
                'ikon' => 'utensils',
                'warna' => '#f43f5e', // rose
            ],
            [
                'nama' => 'Kos/Sewa',
                'tipe' => 'expense',
                'ikon' => 'home',
                'warna' => '#ec4899', // pink
            ],
            [
                'nama' => 'Transportasi',
                'tipe' => 'expense',
                'ikon' => 'bike',
                'warna' => '#f97316', // orange
            ],
            [
                'nama' => 'Tagihan & Internet',
                'tipe' => 'expense',
                'ikon' => 'zap',
                'warna' => '#eab308', // amber/yellow
            ],
            [
                'nama' => 'Tabungan',
                'tipe' => 'expense',
                'ikon' => 'piggy-bank',
                'warna' => '#14b8a6', // teal
            ],
            [
                'nama' => 'Hiburan & Belanja',
                'tipe' => 'expense',
                'ikon' => 'shopping-bag',
                'warna' => '#d946ef', // fuchsia
            ],
        ];

        foreach ($categories as $cat) {
            Category::firstOrCreate(
                [
                    'user_id' => null,
                    'nama' => $cat['nama'],
                    'tipe' => $cat['tipe'],
                ],
                [
                    'ikon' => $cat['ikon'],
                    'warna' => $cat['warna'],
                ]
            );
        }
    }
}
