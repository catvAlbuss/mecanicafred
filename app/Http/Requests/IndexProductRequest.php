<?php

namespace App\Http\Requests;

use App\Enums\ProductType;
use App\Models\Product;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IndexProductRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('viewAny', Product::class) ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:100'],
            'category' => ['nullable', 'integer', 'exists:product_categories,id'],
            'type' => ['nullable', Rule::enum(ProductType::class)],
            'stock' => ['nullable', Rule::in(['available', 'low', 'out'])],
            'status' => ['nullable', Rule::in(['active', 'inactive'])],
        ];
    }
}
