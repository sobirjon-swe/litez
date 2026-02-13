<?php

namespace App\Modules\Catalog\Resources;

use App\Modules\Inventory\Resources\StockResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'price' => $this->price,
            'sku' => $this->sku,
            'category_id' => $this->category_id,
            'is_published' => $this->is_published,
            'category' => new CategoryResource($this->whenLoaded('category')),
            'attributes' => ProductAttributeResource::collection($this->whenLoaded('attributes')),
            'stock' => new StockResource($this->whenLoaded('stock')),
        ];
    }
}
