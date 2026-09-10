<?php

namespace App\Http\Requests;

use App\Concerns\SupplierInquiryValidationRules;
use App\Models\SupplierInquiry;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreSupplierInquiryRequest extends FormRequest
{
    use SupplierInquiryValidationRules;

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('create', SupplierInquiry::class) ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return $this->inquiryRules();
    }
}
