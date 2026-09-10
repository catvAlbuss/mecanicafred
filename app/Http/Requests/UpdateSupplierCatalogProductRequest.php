<?php

namespace App\Http\Requests;

use App\Concerns\SupplierCatalogValidationRules;
use App\Models\Product;
use App\Models\Supplier;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateSupplierCatalogProductRequest extends FormRequest
{
    use SupplierCatalogValidationRules;

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $supplier = $this->route('supplier');

        return $supplier instanceof Supplier && ($this->user()?->can('update', $supplier) ?? false);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $supplier = $this->route('supplier');
        $product = $this->route('product');

        return $this->catalogRules($supplier instanceof Supplier ? $supplier : new Supplier, $product instanceof Product ? $product : null);
    }
}
