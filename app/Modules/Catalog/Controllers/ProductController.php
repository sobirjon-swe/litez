<?php

namespace App\Modules\Catalog\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Catalog\Models\Product;
use App\Modules\Catalog\Requests\StoreProductRequest;
use App\Modules\Catalog\Requests\UpdateProductRequest;
use App\Modules\Catalog\Resources\ProductResource;
use App\Modules\Catalog\Services\ProductService;

class ProductController extends Controller
{
    public function __construct(
        private ProductService $productService
    ) {}

    public function index()
    {
        $products = $this->productService->list(request()->all());

        return ProductResource::collection($products);
    }

    public function store(StoreProductRequest $request)
    {
        $product = $this->productService->create($request->validated());

        return (new ProductResource($product))
            ->response()
            ->setStatusCode(201);
    }

    public function show(string $slug)
    {
        $product = $this->productService->findBySlug($slug);

        return new ProductResource($product);
    }

    public function update(UpdateProductRequest $request, Product $product)
    {
        $product = $this->productService->update($product, $request->validated());

        return new ProductResource($product);
    }
}
