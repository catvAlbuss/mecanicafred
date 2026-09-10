<?php

namespace App\Http\Requests;

use App\Enums\SupplierInquiryStatus;
use App\Models\SupplierInquiry;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IndexSupplierInquiryRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('viewAny', SupplierInquiry::class) ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return ['search' => ['nullable', 'string', 'max:100'], 'supplier' => ['nullable', 'integer', 'exists:suppliers,id'], 'status' => ['nullable', Rule::enum(SupplierInquiryStatus::class)]];
    }
}
