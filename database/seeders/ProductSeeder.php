<?php

namespace Database\Seeders;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        $products = [
            ['Ryzen 5 5600G', 'Procesadores', 'AMD', 'Procesador de 6 núcleos con gráficos Radeon integrados.', '950.00', 10, 'CPU-001', true],
            ['Ryzen 7 5700X', 'Procesadores', 'AMD', 'Procesador de 8 núcleos para alto rendimiento y multitarea.', '1450.00', 4, 'CPU-002', true],
            ['Intel Core i5-12400F', 'Procesadores', 'Intel', 'Procesador de 6 núcleos eficiente para gaming y productividad.', '1200.00', 7, 'CPU-003', true],
            ['GeForce RTX 4060 8GB', 'Tarjetas Gráficas', 'NVIDIA', 'Tarjeta gráfica de 8 GB con soporte para ray tracing.', '3200.00', 5, 'GPU-001', true],
            ['GeForce RTX 3060 12GB', 'Tarjetas Gráficas', 'NVIDIA', 'Tarjeta gráfica de 12 GB para juegos en alta resolución.', '2800.00', 0, 'GPU-002', true],
            ['Radeon RX 7600 8GB', 'Tarjetas Gráficas', 'AMD', 'Tarjeta gráfica de 8 GB optimizada para gaming en 1080p.', '2900.00', 3, 'GPU-003', true],
            ['Kingston Fury 16GB DDR4', 'Memorias RAM', 'Kingston', 'Módulo DDR4 de 16 GB para equipos de escritorio.', '350.00', 20, 'RAM-001', true],
            ['Corsair Vengeance 32GB DDR5', 'Memorias RAM', 'Corsair', 'Kit DDR5 de 32 GB para plataformas de nueva generación.', '850.00', 6, 'RAM-002', true],
            ['Crucial 16GB DDR4', 'Memorias RAM', 'Crucial', 'Memoria DDR4 de 16 GB confiable para uso diario.', '320.00', 2, 'RAM-003', true],
            ['Kingston NV2 1TB NVMe', 'Almacenamiento', 'Kingston', 'Unidad NVMe de 1 TB para arranque y carga rápida.', '480.00', 15, 'SSD-001', true],
            ['WD Blue SN580 1TB', 'Almacenamiento', 'Western Digital', 'SSD NVMe de 1 TB para trabajo y entretenimiento.', '520.00', 8, 'SSD-002', true],
            ['Seagate Barracuda 2TB', 'Almacenamiento', 'Seagate', 'Disco duro de 2 TB para almacenamiento de gran capacidad.', '450.00', 0, 'HDD-001', false],
            ['ASUS Prime B550M-A', 'Placas Madre', 'ASUS', 'Placa madre micro-ATX con chipset B550 para procesadores AMD.', '900.00', 5, 'MB-001', true],
            ['MSI B760M Gaming Plus', 'Placas Madre', 'MSI', 'Placa madre micro-ATX B760 orientada a equipos gaming.', '1350.00', 3, 'MB-002', true],
            ['Corsair CV650 650W', 'Fuentes de Poder', 'Corsair', 'Fuente de poder de 650 W para configuraciones equilibradas.', '520.00', 12, 'PSU-001', true],
            ['Cooler Master Hyper 212', 'Refrigeración', 'Cooler Master', 'Disipador por aire para mantener estable la temperatura del CPU.', '300.00', 4, 'COOL-001', true],
            ['Cooler Master MasterBox Q300L', 'Gabinetes', 'Cooler Master', 'Gabinete micro-ATX compacto con ventilación optimizada.', '480.00', 6, 'CASE-001', true],
        ];

        foreach ($products as [$name, $categoryName, $brandName, $description, $price, $stock, $sku, $active]) {
            $category = Category::firstOrCreate(
                ['nombre' => $categoryName],
                ['activo' => true]
            );
            $brand = Brand::firstOrCreate(
                ['nombre' => $brandName],
                ['activo' => true]
            );

            Product::updateOrCreate(
                ['sku' => $sku],
                [
                    'nombre' => $name,
                    'descripcion' => $description,
                    'categoria_id' => $category->id,
                    'marca_id' => $brand->id,
                    'precio' => $price,
                    'stock' => $stock,
                    'activo' => $active,
                ]
            );
        }
    }
}
