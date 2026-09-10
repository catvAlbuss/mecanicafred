<?php

namespace App\Http\Requests;

use App\Concerns\ProductCategoryValidationRules;
use App\Models\ProductCategory;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;

class UpdateProductCategoryRequest extends FormRequest
{
    use ProductCategoryValidationRules;

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $category = $this->route('category');

        return $category instanceof ProductCategory && ($this->user()?->can('update', $category) ?? false);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $category = $this->route('category');

        return $this->productCategoryRules($category instanceof ProductCategory ? $category : null);
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['slug' => Str::slug($this->string('name')->toString())]);
    }
}
