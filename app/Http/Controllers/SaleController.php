<?php

namespace App\Http\Controllers;

use App\Actions\Cashier\RegisterSale;
use App\Enums\PaymentMethod;
use App\Enums\SaleStatus;
use App\Http\Requests\IndexSaleRequest;
use App\Http\Requests\StoreSaleRequest;
use App\Models\CashRegister;
use App\Models\Sale;
use App\Models\SaleItem;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class SaleController extends Controller
{
    public function index(IndexSaleRequest $request): Response
    {
        $search = $request->string('search')->trim()->toString();
        $status = $request->string('status')->toString();
        $method = $request->string('payment_method')->toString();
        $from = $request->date('from');
        $to = $request->date('to');

        $sales = Sale::query()
            ->with('seller:id,name')
            ->withCount('items')
            ->when($search !== '', function (Builder $query) use ($search): void {
                $value = '%'.addcslashes($search, '%_\\').'%';
                $query->where(fn (Builder $nested) => $nested->where('number', 'like', $value)
                    ->orWhere('customer_name', 'like', $value)
                    ->orWhere('customer_document', 'like', $value)
                    ->orWhereHas('items', fn (Builder $items) => $items->where('product_name', 'like', $value)->orWhere('product_sku', 'like', $value)));
            })
            ->when($status !== '', fn (Builder $query) => $query->where('status', $status))
            ->when($method !== '', fn (Builder $query) => $query->where('payment_method', $method))
            ->when($from, fn (Builder $query) => $query->whereDate('sold_at', '>=', $from))
            ->when($to, fn (Builder $query) => $query->whereDate('sold_at', '<=', $to))
            ->latest('id')
            ->paginate(15)
            ->withQueryString()
            ->through(fn (Sale $sale): array => $this->summaryData($sale));

        $totalsQuery = Sale::query()->where('status', SaleStatus::Completed)
            ->when($from, fn (Builder $query) => $query->whereDate('sold_at', '>=', $from))
            ->when($to, fn (Builder $query) => $query->whereDate('sold_at', '<=', $to));

        return Inertia::render('sales/Index', [
            'sales' => $sales,
            'filters' => ['search' => $search, 'status' => $status, 'payment_method' => $method, 'from' => $from?->toDateString(), 'to' => $to?->toDateString()],
            'stats' => [
                'count' => (clone $totalsQuery)->count(),
                'total' => (string) (clone $totalsQuery)->sum('total'),
            ],
            'statuses' => array_map(fn (SaleStatus $status): array => ['value' => $status->value, 'label' => $status->label()], SaleStatus::cases()),
            'paymentMethods' => array_map(fn (PaymentMethod $method): array => ['value' => $method->value, 'label' => $method->label()], PaymentMethod::cases()),
            'canSell' => $request->user()->can('create', Sale::class),
        ]);
    }

    public function create(): Response
    {
        Gate::authorize('create', Sale::class);

        $register = CashRegister::query()->open()->with('opener:id,name')->first();

        return Inertia::render('sales/Create', [
            'register' => $register === null ? null : [
                'id' => $register->id,
                'number' => $register->number,
                'opened_by' => $register->opener->name,
                'opened_at' => $register->opened_at->toIso8601String(),
            ],
            'paymentMethods' => array_map(fn (PaymentMethod $method): array => ['value' => $method->value, 'label' => $method->label()], PaymentMethod::cases()),
            'saleKey' => (string) Str::uuid(),
        ]);
    }

    public function store(StoreSaleRequest $request, RegisterSale $registerSale): RedirectResponse
    {
        $register = CashRegister::currentOpen();

        if ($register === null) {
            throw ValidationException::withMessages(['items' => 'Abre una caja para registrar la venta.']);
        }

        $sale = $registerSale->handle($register, $request->user(), $request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => "Venta {$sale->number} registrada."]);

        return to_route('sales.show', $sale);
    }

    public function show(Sale $sale): Response
    {
        Gate::authorize('view', $sale);

        $sale->load(['items', 'seller:id,name', 'canceller:id,name', 'cashRegister:id,number']);

        return Inertia::render('sales/Show', [
            'sale' => [
                'id' => $sale->id,
                'number' => $sale->number,
                'status' => $sale->status->value,
                'status_label' => $sale->status->label(),
                'register_number' => $sale->cashRegister->number,
                'seller_name' => $sale->seller->name,
                'canceller_name' => $sale->canceller?->name,
                'payment_method' => $sale->payment_method->value,
                'payment_method_label' => $sale->payment_method->label(),
                'customer_name' => $sale->customer_name,
                'customer_document' => $sale->customer_document,
                'subtotal' => $sale->subtotal,
                'discount' => $sale->discount,
                'total' => $sale->total,
                'notes' => $sale->notes,
                'cancellation_reason' => $sale->cancellation_reason,
                'sold_at' => $sale->sold_at->toIso8601String(),
                'cancelled_at' => $sale->cancelled_at?->toIso8601String(),
                'items' => $sale->items->map(fn (SaleItem $item): array => [
                    'id' => $item->id,
                    'product_name' => $item->product_name,
                    'product_sku' => $item->product_sku,
                    'quantity' => $item->quantity,
                    'unit_price' => $item->unit_price,
                    'subtotal' => $item->subtotal,
                ])->values(),
            ],
            'canCancel' => Gate::allows('cancel', $sale),
        ]);
    }

    /** @return array<string, mixed> */
    private function summaryData(Sale $sale): array
    {
        return [
            'id' => $sale->id,
            'number' => $sale->number,
            'status' => $sale->status->value,
            'status_label' => $sale->status->label(),
            'payment_method_label' => $sale->payment_method->label(),
            'customer_name' => $sale->customer_name,
            'items_count' => $sale->items_count,
            'total' => $sale->total,
            'seller_name' => $sale->seller->name,
            'sold_at' => $sale->sold_at->toIso8601String(),
        ];
    }
}
