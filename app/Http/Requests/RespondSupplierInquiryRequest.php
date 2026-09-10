<?php

namespace App\Http\Requests;

use App\Models\SupplierInquiry;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class RespondSupplierInquiryRequest extends FormRequest
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
        $inquiry = $this->route('inquiry');
        $inquiryId = $inquiry instanceof SupplierInquiry ? $inquiry->id : 0;

        return [
            'valid_until' => ['nullable', 'date', 'after_or_equal:today'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.id' => ['required', 'integer', 'distinct', Rule::exists('supplier_inquiry_items', 'id')->where('supplier_inquiry_id', $inquiryId)],
            'items.*.is_available' => ['required', 'boolean'],
            'items.*.quantity_available' => ['nullable', 'decimal:0,3', 'gt:0', 'max:99999999999.999'],
            'items.*.quoted_unit_cost' => ['nullable', 'decimal:0,4', 'gt:0', 'max:9999999999.9999'],
            'items.*.supplier_notes' => ['nullable', 'string', 'max:255'],
        ];
    }

    /** @return array<int, callable(Validator): void> */
    public function after(): array
    {
        return [function (Validator $validator): void {
            $inquiry = $this->route('inquiry');
            if ($inquiry instanceof SupplierInquiry && count($this->array('items')) !== $inquiry->items()->count()) {
                $validator->errors()->add('items', 'Registra la respuesta de todos los productos consultados.');
            }

            foreach ($this->array('items') as $index => $item) {
                if (! is_array($item) || ! filter_var($item['is_available'] ?? false, FILTER_VALIDATE_BOOL)) {
                    continue;
                }
                if (empty($item['quantity_available'])) {
                    $validator->errors()->add("items.{$index}.quantity_available", 'Indica la cantidad disponible.');
                }
                if (! isset($item['quoted_unit_cost']) || $item['quoted_unit_cost'] === '') {
                    $validator->errors()->add("items.{$index}.quoted_unit_cost", 'Indica el costo cotizado.');
                }
            }
        }];
    }
}
