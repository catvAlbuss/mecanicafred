<?php

namespace App\Http\Requests;

use App\Models\PurchaseOrder;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePurchaseOrderStatusRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $purchaseOrder = $this->route('purchaseOrder');
        if (! $purchaseOrder instanceof PurchaseOrder) {
            return false;
        }

        return match ($this->string('action')->toString()) {
            'send' => $this->user()?->can('send', $purchaseOrder) ?? false,
            'confirm' => $this->user()?->can('confirm', $purchaseOrder) ?? false,
            'cancel' => $this->user()?->can('cancel', $purchaseOrder) ?? false,
            default => false,
        };
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'action' => ['required', Rule::in(['send', 'confirm', 'cancel'])],
            'reason' => [Rule::requiredIf($this->input('action') === 'cancel'), 'nullable', 'string', 'max:255'],
        ];
    }
}
