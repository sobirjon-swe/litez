<?php

namespace App\Modules\Catalog\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Catalog\Requests\StoreCategoryRequest;
use App\Modules\Catalog\Resources\CategoryResource;
use App\Modules\Catalog\Services\CategoryService;

class CategoryController extends Controller
{
    public function __construct(
        private CategoryService $categoryService
    ) {}

    public function index()
    {
        $tree = $this->categoryService->getTree();

        return CategoryResource::collection($tree);
    }

    public function store(StoreCategoryRequest $request)
    {
        $category = $this->categoryService->create($request->validated());

        return (new CategoryResource($category))
            ->response()
            ->setStatusCode(201);
    }
}
