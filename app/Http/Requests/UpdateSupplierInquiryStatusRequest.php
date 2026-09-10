<?php

namespace App\Http\Requests;

use App\Models\SupplierInquiry;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSupplierInquiryStatusRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $inquiry = $this->route('inquiry');

        return $inquiry instanceof SupplierInquiry && ($this->user()?->can('transition', $inquiry) ?? false);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return ['action' => ['required', Rule::in(['send', 'close', 'cancel'])], 'reason' => ['nullable', 'string', 'max:255']];
    }
}
