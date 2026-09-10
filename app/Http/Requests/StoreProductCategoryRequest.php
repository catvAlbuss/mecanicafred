<?php

namespace App\Http\Requests;

use App\Concerns\ProductCategoryValidationRules;
use App\Models\ProductCategory;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;

class StoreProductCategoryRequest extends FormRequest
{
    use ProductCategoryValidationRules;

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('create', ProductCategory::class) ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return $this->productCategoryRules();
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['slug' => Str::slug($this->string('name')->toString())]);

        if (! $this->has('is_active')) {
            $this->merge(['is_active' => true]);
        }
    }
}
