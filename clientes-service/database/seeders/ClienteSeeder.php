<?php

namespace Database\Seeders;

use App\Models\Cliente;
use Illuminate\Database\Seeder;

class ClienteSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $clientes = [
            [
                'nombre' => 'Juan',
                'apellido' => 'PÃ©rez',
                'email' => 'juan.perez@example.com',
                'telefono' => '70711111',
                'direccion' => 'Av. AmÃ©rica 123, Cochabamba',
                'activo' => true,
            ],
            [
                'nombre' => 'MarÃ­a',
                'apellido' => 'LÃ³pez',
                'email' => 'maria.lopez@example.com',
                'telefono' => '70722222',
                'direccion' => 'Calle EspaÃ±a 456, Cochabamba',
                'activo' => true,
            ],
            [
                'nombre' => 'Carlos',
                'apellido' => 'Rojas',
                'email' => 'carlos.rojas@example.com',
                'telefono' => '70733333',
                'direccion' => 'Av. Blanco Galindo 789, Cochabamba',
                'activo' => true,
            ],
            [
                'nombre' => 'Ana',
                'apellido' => 'Paredes',
                'email' => 'ana.paredes@example.com',
                'telefono' => '70744444',
                'direccion' => 'Av. Beijing 321, Cochabamba',
                'activo' => true,
            ],
            [
                'nombre' => 'Diego',
                'apellido' => 'Mendoza',
                'email' => 'diego.mendoza@example.com',
                'telefono' => '70755555',
                'direccion' => 'Calle Sucre 654, Cochabamba',
                'activo' => true,
            ],
            [
                'nombre' => 'LucÃ­a',
                'apellido' => 'Vargas',
                'email' => 'lucia.vargas@example.com',
                'telefono' => '70766666',
                'direccion' => 'Av. Melchor PÃ©rez 987, Cochabamba',
                'activo' => false,
            ],
        ];

        foreach ($clientes as $cliente) {
            Cliente::query()->updateOrCreate(
                ['email' => $cliente['email']],
                $cliente,
            );
        }
    }
}
