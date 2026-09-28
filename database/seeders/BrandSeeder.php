<?php

namespace Database\Seeders;

use App\Models\Brand;
use Illuminate\Database\Seeder;

class BrandSeeder extends Seeder
{
    public function run(): void
    {
        $brands = [
            'AMD',
            'Intel',
            'NVIDIA',
            'ASUS',
            'MSI',
            'Gigabyte',
            'Kingston',
            'Corsair',
            'Crucial',
            'Western Digital',
            'Seagate',
            'Cooler Master',
        ];

        foreach ($brands as $name) {
            Brand::updateOrCreate(
                ['nombre' => $name],
                ['activo' => true]
            );
        }
    }
}
