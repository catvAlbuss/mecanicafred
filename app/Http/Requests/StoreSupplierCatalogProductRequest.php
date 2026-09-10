<?php

namespace App\Http\Requests;

use App\Concerns\SupplierCatalogValidationRules;
use App\Models\Supplier;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreSupplierCatalogProductRequest extends FormRequest
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

        return $this->catalogRules($supplier instanceof Supplier ? $supplier : new Supplier);
    }

    protected function prepareForValidation(): void
    {
        if (! $this->has('is_preferred')) {
            $this->merge(['is_preferred' => false]);
        }
    }
}
