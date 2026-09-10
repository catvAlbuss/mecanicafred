<?php

namespace App\Http\Controllers;

use App\Enums\PurchaseOrderStatus;
use App\Http\Requests\IndexPurchaseHistoryRequest;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\Supplier;
use Illuminate\Database\Eloquent\Builder;
use Inertia\Inertia;
use Inertia\Response;

class PurchaseHistoryController extends Controller
{
    public function __invoke(IndexPurchaseHistoryRequest $request): Response
    {
        $search = $request->string('search')->trim()->toString();
        $supplierId = $request->integer('supplier');
        $productId = $request->integer('product');
        $status = $request->string('status')->toString();
        $from = $request->date('from');
        $to = $request->date('to');
        $baseQuery = PurchaseOrder::query()->whereIn('status', [PurchaseOrderStatus::Received, PurchaseOrderStatus::Cancelled])
            ->when($search !== '', function (Builder $query) use ($search): void {
                $value = '%'.addcslashes($search, '%_\\').'%';
                $query->where(fn (Builder $nested) => $nested->where('number', 'like', $value)->orWhereHas('supplier', fn (Builder $supplier) => $supplier->where('business_name', 'like', $value)->orWhere('trade_name', 'like', $value))->orWhereHas('items', fn (Builder $items) => $items->where('product_name', 'like', $value)->orWhere('product_sku', 'like', $value)));
            })->when($supplierId > 0, fn (Builder $query) => $query->where('supplier_id', $supplierId))
            ->when($productId > 0, fn (Builder $query) => $query->whereHas('items', fn (Builder $items) => $items->where('product_id', $productId)))
            ->when($status !== '', fn (Builder $query) => $query->where('status', $status))
            ->when($from, fn (Builder $query) => $query->whereDate('updated_at', '>=', $from))
            ->when($to, fn (Builder $query) => $query->whereDate('updated_at', '<=', $to));

        $orders = (clone $baseQuery)->with('supplier:id,business_name,trade_name')->withCount(['items', 'receipts'])->latest('updated_at')->paginate(12)->withQueryString()->through(fn (PurchaseOrder $order): array => ['id' => $order->id, 'number' => $order->number, 'supplier_name' => $order->supplier->trade_name ?: $order->supplier->business_name, 'status' => $order->status->value, 'status_label' => $order->status->label(), 'items_count' => $order->items_count, 'receipts_count' => $order->receipts_count, 'total' => $order->total, 'closed_at' => $order->updated_at->toIso8601String()]);
        $completedQuery = (clone $baseQuery)->where('status', PurchaseOrderStatus::Received);
        $stats = ['orders' => (clone $baseQuery)->count(), 'amount' => (string) (clone $completedQuery)->sum('total'), 'suppliers' => (clone $baseQuery)->distinct()->count('supplier_id'), 'units' => (string) PurchaseOrderItem::query()->whereIn('purchase_order_id', (clone $completedQuery)->select('id'))->sum('quantity_received')];
        $summaryRows = (clone $completedQuery)->join('suppliers', 'suppliers.id', '=', 'purchase_orders.supplier_id')->selectRaw('purchase_orders.supplier_id, suppliers.business_name, suppliers.trade_name, COUNT(*) as orders_count, SUM(purchase_orders.total) as total_amount')->groupBy('purchase_orders.supplier_id', 'suppliers.business_name', 'suppliers.trade_name')->orderByDesc('orders_count')->limit(8)->get();
        $supplierSummary = [];
        foreach ($summaryRows as $row) {
            $supplierSummary[] = ['supplier_id' => (int) data_get($row, 'supplier_id'), 'supplier_name' => (string) (data_get($row, 'trade_name') ?: data_get($row, 'business_name')), 'orders_count' => (int) data_get($row, 'orders_count'), 'total_amount' => (string) data_get($row, 'total_amount')];
        }

        return Inertia::render('purchases/history/Index', [
            'orders' => $orders,
            'stats' => $stats,
            'supplierSummary' => $supplierSummary,
            'filters' => ['search' => $search, 'supplier' => $supplierId, 'product' => $productId, 'status' => $status, 'from' => $from?->toDateString(), 'to' => $to?->toDateString()],
            'suppliers' => Supplier::query()->select(['id', 'business_name', 'trade_name'])->orderBy('business_name')->get()->map(fn (Supplier $supplier): array => ['id' => $supplier->id, 'name' => $supplier->trade_name ?: $supplier->business_name]),
            'products' => Product::query()->select(['id', 'sku', 'name'])->orderBy('name')->get()->map(fn (Product $product): array => ['id' => $product->id, 'name' => "{$product->sku} · {$product->name}"]),
            'statuses' => [['value' => PurchaseOrderStatus::Received->value, 'label' => PurchaseOrderStatus::Received->label()], ['value' => PurchaseOrderStatus::Cancelled->value, 'label' => PurchaseOrderStatus::Cancelled->label()]],
        ]);
    }
}
