<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            'Procesadores' => 'Procesadores para computadoras de escritorio y estaciones de trabajo.',
            'Tarjetas Gráficas' => 'Tarjetas dedicadas para procesamiento gráfico y videojuegos.',
            'Memorias RAM' => 'Módulos de memoria para mejorar el rendimiento del sistema.',
            'Almacenamiento' => 'Unidades SSD, NVMe y discos duros para guardar información.',
            'Placas Madre' => 'Placas principales para integrar los componentes del equipo.',
            'Fuentes de Poder' => 'Fuentes de alimentación para suministrar energía estable.',
            'Refrigeración' => 'Soluciones para controlar la temperatura de los componentes.',
            'Gabinetes' => 'Chasis para proteger y organizar los componentes del computador.',
        ];

        foreach ($categories as $name => $description) {
            Category::updateOrCreate(
                ['nombre' => $name],
                [
                    'descripcion' => $description,
                    'activo' => true,
                ]
            );
        }
    }
}
