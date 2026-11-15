<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    public static $wrap = null;

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'nombre' => $this->nombre,
            'descripcion' => $this->descripcion,
            'categoria_id' => $this->categoria_id,
            'categoria' => $this->whenLoaded('category', fn () => [
                'id' => $this->category->id,
                'nombre' => $this->category->nombre,
            ]),
            'marca_id' => $this->marca_id,
            'marca' => $this->whenLoaded('brand', fn () => $this->brand === null ? null : [
                'id' => $this->brand->id,
                'nombre' => $this->brand->nombre,
            ]),
            'precio' => $this->precio,
            'stock' => $this->stock,
            'sku' => $this->sku,
            'activo' => $this->activo,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
