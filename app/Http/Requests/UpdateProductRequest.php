<?php

namespace App\Http\Requests;

use App\Concerns\ProductValidationRules;
use App\Models\Product;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class UpdateProductRequest extends FormRequest
{
    use ProductValidationRules;

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $product = $this->route('product');

        return $product instanceof Product && ($this->user()?->can('update', $product) ?? false);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $product = $this->route('product');

        return $this->productRules($product instanceof Product ? $product : null);
    }

    /** @return array<int, callable(Validator): void> */
    public function after(): array
    {
        return [function (Validator $validator): void {
            $product = $this->route('product');
            $images = $this->file('images', []);
            if ($product instanceof Product && is_array($images) && $product->getMedia('images')->count() + count($images) > 5) {
                $validator->errors()->add('images', 'El producto puede tener como máximo 5 imágenes.');
            }
        }];
    }
}
