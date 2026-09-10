<?php

namespace App\Http\Controllers;

use App\Actions\Purchases\SavePurchaseOrder;
use App\Enums\PurchaseOrderStatus;
use App\Http\Requests\IndexPurchaseOrderRequest;
use App\Http\Requests\StorePurchaseOrderRequest;
use App\Http\Requests\UpdatePurchaseOrderRequest;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\PurchaseReceipt;
use App\Models\Supplier;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class PurchaseOrderController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(IndexPurchaseOrderRequest $request): Response
    {
        $search = $request->string('search')->trim()->toString();
        $supplierId = $request->integer('supplier');
        $status = $request->string('status')->toString();
        $from = $request->date('from');
        $to = $request->date('to');
        $orders = PurchaseOrder::query()->with('supplier:id,business_name,trade_name')->withCount('items')
            ->when($search !== '', function (Builder $query) use ($search): void {
                $value = '%'.addcslashes($search, '%_\\').'%';
                $query->where(fn (Builder $nested) => $nested->where('number', 'like', $value)
                    ->orWhereHas('supplier', fn (Builder $supplier) => $supplier->where('business_name', 'like', $value)->orWhere('trade_name', 'like', $value))
                    ->orWhereHas('items', fn (Builder $items) => $items->where('product_name', 'like', $value)->orWhere('product_sku', 'like', $value)));
            })->when($supplierId > 0, fn (Builder $query) => $query->where('supplier_id', $supplierId))
            ->when($status !== '', fn (Builder $query) => $query->where('status', $status), fn (Builder $query) => $query->whereNotIn('status', [PurchaseOrderStatus::Received, PurchaseOrderStatus::Cancelled]))
            ->when($from, fn (Builder $query) => $query->whereDate('created_at', '>=', $from))
            ->when($to, fn (Builder $query) => $query->whereDate('created_at', '<=', $to))
            ->latest('id')->paginate(12)->withQueryString()->through(fn (PurchaseOrder $order): array => $this->summaryData($order));

        return Inertia::render('purchases/orders/Index', [
            'orders' => $orders,
            'filters' => ['search' => $search, 'supplier' => $supplierId, 'status' => $status, 'from' => $from?->toDateString(), 'to' => $to?->toDateString()],
            'suppliers' => $this->supplierOptions(false),
            'statuses' => $this->statusOptions(),
            'canCreate' => $request->user()->can('create', PurchaseOrder::class),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Request $request): Response
    {
        Gate::authorize('create', PurchaseOrder::class);

        return Inertia::render('purchases/orders/Create', [...$this->formOptions(), 'preselectedSupplierId' => $request->integer('supplier') ?: null, 'preselectedProductId' => $request->integer('product') ?: null]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StorePurchaseOrderRequest $request, SavePurchaseOrder $savePurchaseOrder): RedirectResponse
    {
        $order = $savePurchaseOrder->handle($request->validated(), $request->user());
        Inertia::flash('toast', ['type' => 'success', 'message' => 'Pedido creado como borrador.']);

        return to_route('purchases.orders.show', $order);
    }

    /**
     * Display the specified resource.
     */
    public function show(PurchaseOrder $purchaseOrder): Response
    {
        Gate::authorize('view', $purchaseOrder);
        $purchaseOrder->load(['supplier', 'creator:id,name', 'approver:id,name', 'inquiry:id,number', 'items.product:id', 'media', 'receipts.receiver:id,name', 'receipts.items.purchaseOrderItem', 'receipts.media']);
        $receipts = [];
        foreach ($purchaseOrder->receipts->sortByDesc('received_at') as $receipt) {
            $receipts[] = $this->receiptData($receipt);
        }

        return Inertia::render('purchases/orders/Show', [
            'order' => [...$this->formData($purchaseOrder), 'supplier_name' => $purchaseOrder->supplier->trade_name ?: $purchaseOrder->supplier->business_name, 'status_label' => $purchaseOrder->status->label(), 'creator_name' => $purchaseOrder->creator->name, 'approver_name' => $purchaseOrder->approver?->name, 'inquiry' => $purchaseOrder->inquiry ? ['id' => $purchaseOrder->inquiry->id, 'number' => $purchaseOrder->inquiry->number] : null, 'items' => $purchaseOrder->items->map(fn (PurchaseOrderItem $item): array => $this->itemData($item))->values(), 'attachments' => $this->attachmentData($purchaseOrder), 'receipts' => $receipts],
            'canEdit' => $purchaseOrder->status === PurchaseOrderStatus::Draft && Gate::allows('update', $purchaseOrder),
            'canDelete' => $purchaseOrder->status === PurchaseOrderStatus::Draft && Gate::allows('delete', $purchaseOrder),
            'canSend' => $purchaseOrder->status === PurchaseOrderStatus::Draft && Gate::allows('send', $purchaseOrder),
            'canConfirm' => $purchaseOrder->status === PurchaseOrderStatus::Sent && Gate::allows('confirm', $purchaseOrder),
            'canCancel' => in_array($purchaseOrder->status, [PurchaseOrderStatus::Draft, PurchaseOrderStatus::Sent, PurchaseOrderStatus::Confirmed], true) && Gate::allows('cancel', $purchaseOrder),
            'canReceive' => in_array($purchaseOrder->status, [PurchaseOrderStatus::Confirmed, PurchaseOrderStatus::PartiallyReceived], true) && Gate::allows('receive', $purchaseOrder),
            'receiptKey' => (string) Str::uuid(),
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(PurchaseOrder $purchaseOrder): Response
    {
        Gate::authorize('update', $purchaseOrder);
        abort_unless($purchaseOrder->status === PurchaseOrderStatus::Draft, 403);
        $purchaseOrder->load(['items.product', 'media']);

        return Inertia::render('purchases/orders/Edit', [...$this->formOptions(), 'order' => [...$this->formData($purchaseOrder), 'items' => $purchaseOrder->items->map(fn (PurchaseOrderItem $item): array => $this->itemData($item))->values(), 'attachments' => $this->attachmentData($purchaseOrder)]]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdatePurchaseOrderRequest $request, PurchaseOrder $purchaseOrder, SavePurchaseOrder $savePurchaseOrder): RedirectResponse
    {
        $savePurchaseOrder->handle($request->validated(), $request->user(), $purchaseOrder);
        Inertia::flash('toast', ['type' => 'success', 'message' => 'Borrador actualizado y totales recalculados.']);

        return to_route('purchases.orders.show', $purchaseOrder);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(PurchaseOrder $purchaseOrder): RedirectResponse
    {
        Gate::authorize('delete', $purchaseOrder);
        abort_unless($purchaseOrder->status === PurchaseOrderStatus::Draft, 403);
        $purchaseOrder->delete();
        Inertia::flash('toast', ['type' => 'success', 'message' => 'Pedido borrador eliminado.']);

        return to_route('purchases.orders.index');
    }

    /** @return array<string, mixed> */
    private function formOptions(): array
    {
        return ['suppliers' => $this->supplierOptions(true)];
    }

    /** @return array<int, array<string, mixed>> */
    private function supplierOptions(bool $withProducts): array
    {
        $query = Supplier::query()->select(['id', 'business_name', 'trade_name'])->where('is_active', true)->orderBy('business_name');
        if ($withProducts) {
            $query->with(['products' => fn ($products) => $products->select(['products.id', 'sku', 'name', 'unit'])->where('products.is_active', true)->orderBy('name')]);
        }

        $options = [];
        foreach ($query->get() as $supplier) {
            $products = [];
            if ($withProducts) {
                foreach ($supplier->products as $product) {
                    $products[] = ['id' => $product->id, 'sku' => $product->sku, 'name' => $product->name, 'unit' => $product->unit->label(), 'last_unit_cost' => data_get($product, 'pivot.last_unit_cost')];
                }
            }
            $options[] = ['id' => $supplier->id, 'name' => $supplier->trade_name ?: $supplier->business_name, ...($withProducts ? ['products' => $products] : [])];
        }

        return $options;
    }

    /** @return array<string, mixed> */
    private function formData(PurchaseOrder $order): array
    {
        return ['id' => $order->id, 'number' => $order->number, 'supplier_id' => $order->supplier_id, 'status' => $order->status->value, 'currency' => $order->currency, 'expected_at' => $order->expected_at?->toDateString(), 'ordered_at' => $order->ordered_at?->toIso8601String(), 'subtotal' => $order->subtotal, 'tax_rate' => $order->tax_rate, 'tax' => $order->tax, 'total' => $order->total, 'notes' => $order->notes, 'cancellation_reason' => $order->cancellation_reason, 'created_at' => $order->created_at->toIso8601String()];
    }

    /** @return array<string, mixed> */
    private function itemData(PurchaseOrderItem $item): array
    {
        return ['id' => $item->id, 'product_id' => $item->product_id, 'product_name' => $item->product_name, 'product_sku' => $item->product_sku, 'unit_label' => $item->unit->label(), 'quantity_ordered' => $item->quantity_ordered, 'quantity_received' => $item->quantity_received, 'unit_cost' => $item->unit_cost, 'subtotal' => $item->subtotal];
    }

    /** @return array<string, mixed> */
    private function summaryData(PurchaseOrder $order): array
    {
        return ['id' => $order->id, 'number' => $order->number, 'supplier_name' => $order->supplier->trade_name ?: $order->supplier->business_name, 'status' => $order->status->value, 'status_label' => $order->status->label(), 'items_count' => $order->items_count, 'total' => $order->total, 'expected_at' => $order->expected_at?->toDateString(), 'created_at' => $order->created_at->toIso8601String()];
    }

    /** @return array<int, array{value: string, label: string}> */
    private function statusOptions(): array
    {
        return array_map(fn (PurchaseOrderStatus $status): array => ['value' => $status->value, 'label' => $status->label()], PurchaseOrderStatus::cases());
    }

    /** @return array<int, array<string, mixed>> */
    private function attachmentData(PurchaseOrder $order): array
    {
        return $order->getMedia('attachments')->map(fn (Media $media): array => ['id' => $media->id, 'name' => $media->name, 'file_name' => $media->file_name, 'url' => $media->getUrl(), 'size' => $media->size])->all();
    }

    /** @return array<string, mixed> */
    private function receiptData(PurchaseReceipt $receipt): array
    {
        $items = [];
        foreach ($receipt->items as $item) {
            $items[] = ['id' => $item->id, 'product_name' => $item->purchaseOrderItem->product_name, 'quantity_received' => $item->quantity_received, 'unit_cost' => $item->unit_cost, 'ordered_unit_cost' => $item->purchaseOrderItem->unit_cost, 'unit_label' => $item->purchaseOrderItem->unit->label()];
        }

        return ['id' => $receipt->id, 'number' => $receipt->number, 'received_at' => $receipt->received_at->toIso8601String(), 'receiver_name' => $receipt->receiver->name, 'supplier_document_number' => $receipt->supplier_document_number, 'notes' => $receipt->notes, 'items' => $items, 'attachments' => $receipt->getMedia('attachments')->map(fn (Media $media): array => ['id' => $media->id, 'file_name' => $media->file_name, 'url' => $media->getUrl(), 'size' => $media->size])->all()];
    }
}
