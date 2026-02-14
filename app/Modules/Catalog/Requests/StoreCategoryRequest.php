<?php

namespace App\Modules\Catalog\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', 'unique:categories,slug'],
            'description' => ['nullable', 'string'],
            'parent_id' => ['nullable', 'exists:categories,id'],
            'is_active' => ['boolean'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            if ($this->parent_id) {
                $depth = $this->getCategoryDepth($this->parent_id);
                if ($depth >= 2) {
                    $validator->errors()->add('parent_id', 'Kategoriya daraxtining maksimal chuqurligi 3 daraja.');
                }
            }
        });
    }

    private function getCategoryDepth(int $parentId): int
    {
        $depth = 0;
        $category = \App\Modules\Catalog\Models\Category::find($parentId);

        while ($category && $category->parent_id) {
            $depth++;
            $category = $category->parent;
        }

        return $depth;
    }
}
