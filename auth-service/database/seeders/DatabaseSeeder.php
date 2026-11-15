<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        foreach ([
            ['name' => 'Administrador', 'email' => 'admin@tiendapc.local', 'role' => 'admin', 'activo' => true],
            ['name' => 'Vendedor', 'email' => 'vendedor@tiendapc.local', 'role' => 'vendedor', 'activo' => true],
            ['name' => 'Inactivo', 'email' => 'inactivo@tiendapc.local', 'role' => 'vendedor', 'activo' => false],
        ] as $attributes) {
            User::firstOrCreate(['email' => $attributes['email']], $attributes + ['password' => 'DemoTiendaPC2026!']);
        }
    }
}
