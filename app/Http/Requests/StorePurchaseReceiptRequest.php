<?php

namespace App\Http\Requests;

use App\Models\PurchaseOrder;
use App\Models\PurchaseReceipt;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePurchaseReceiptRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $purchaseOrder = $this->route('purchaseOrder');

        if (! $purchaseOrder instanceof PurchaseOrder || ! ($this->user()?->can('pedidos-compra.recibir') ?? false)) {
            return false;
        }

        return $this->user()->can('receive', $purchaseOrder)
            || PurchaseReceipt::query()->where('purchase_order_id', $purchaseOrder->id)->where('idempotency_key', $this->string('idempotency_key')->toString())->exists();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $purchaseOrder = $this->route('purchaseOrder');
        $purchaseOrderId = $purchaseOrder instanceof PurchaseOrder ? $purchaseOrder->id : 0;

        return [
            'idempotency_key' => ['required', 'uuid'],
            'received_at' => ['required', 'date', 'before_or_equal:now'],
            'supplier_document_number' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'items' => ['required', 'array', 'min:1', 'max:50'],
            'items.*.purchase_order_item_id' => ['required', 'integer', 'distinct', Rule::exists('purchase_order_items', 'id')->where('purchase_order_id', $purchaseOrderId)],
            'items.*.quantity_received' => ['required', 'decimal:0,3', 'gte:0', 'max:99999999999.999'],
            'items.*.unit_cost' => ['nullable', 'decimal:0,4', 'gt:0', 'max:9999999999.9999'],
            'attachments' => ['nullable', 'array', 'max:5'],
            'attachments.*' => ['file', 'mimes:pdf,jpg,jpeg,png,webp', 'extensions:pdf,jpg,jpeg,png,webp', 'max:5120'],
        ];
    }
}
