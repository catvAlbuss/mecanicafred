<?php

namespace App\Http\Controllers;

use App\Enums\SupplierInquiryStatus;
use App\Http\Requests\IndexSupplierInquiryRequest;
use App\Http\Requests\StoreSupplierInquiryRequest;
use App\Http\Requests\UpdateSupplierInquiryRequest;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\SupplierInquiry;
use App\Models\SupplierInquiryItem;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class SupplierInquiryController extends Controller
{
    public function index(IndexSupplierInquiryRequest $request): Response
    {
        $search = $request->string('search')->trim()->toString();
        $supplierId = $request->integer('supplier');
        $status = $request->string('status')->toString();
        $inquiries = SupplierInquiry::query()->with('supplier:id,business_name,trade_name')->withCount('items')
            ->when($search !== '', function (Builder $query) use ($search): void {
                $value = '%'.addcslashes($search, '%_\\').'%';
                $query->where(function (Builder $nested) use ($value): void {
                    $nested->where('number', 'like', $value)->orWhereHas('supplier', fn (Builder $supplier) => $supplier->where('business_name', 'like', $value)->orWhere('trade_name', 'like', $value));
                });
            })->when($supplierId > 0, fn (Builder $query) => $query->where('supplier_id', $supplierId))
            ->when($status !== '', fn (Builder $query) => $query->where('status', $status))
            ->latest('id')->paginate(12)->withQueryString()->through(fn (SupplierInquiry $inquiry): array => $this->summaryData($inquiry));

        return Inertia::render('purchases/inquiries/Index', [
            'inquiries' => $inquiries, 'filters' => ['search' => $search, 'supplier' => $supplierId, 'status' => $status],
            'suppliers' => Supplier::query()->select(['id', 'business_name', 'trade_name'])->where('is_active', true)->orderBy('business_name')->get()->map(fn (Supplier $supplier) => ['id' => $supplier->id, 'name' => $supplier->trade_name ?: $supplier->business_name]),
            'statuses' => $this->statusOptions(), 'canManage' => $request->user()->can('create', SupplierInquiry::class),
        ]);
    }

    public function create(Request $request): Response
    {
        Gate::authorize('create', SupplierInquiry::class);

        return Inertia::render('purchases/inquiries/Create', [...$this->formOptions(), 'preselectedSupplierId' => $request->integer('supplier') ?: null, 'preselectedProductId' => $request->integer('product') ?: null]);
    }

    public function store(StoreSupplierInquiryRequest $request): RedirectResponse
    {
        $inquiry = DB::transaction(function () use ($request): SupplierInquiry {
            $data = $request->validated();
            $items = Arr::pull($data, 'items');
            Arr::forget($data, 'attachments');
            $inquiry = SupplierInquiry::query()->create([...$data, 'requested_by' => $request->user()->id, 'status' => SupplierInquiryStatus::Draft]);
            $inquiry->items()->createMany($items);
            $this->storeAttachments($request, $inquiry);

            return $inquiry;
        });
        Inertia::flash('toast', ['type' => 'success', 'message' => 'Consulta creada como borrador.']);

        return to_route('purchases.inquiries.show', $inquiry);
    }

    public function show(SupplierInquiry $inquiry): Response
    {
        Gate::authorize('view', $inquiry);
        $inquiry->load(['supplier', 'requester:id,name', 'items.product.category', 'media', 'purchaseOrder']);

        return Inertia::render('purchases/inquiries/Show', [
            'inquiry' => [...$this->formData($inquiry), 'status_label' => $inquiry->status->label(), 'supplier_name' => $inquiry->supplier->trade_name ?: $inquiry->supplier->business_name,
                'requester_name' => $inquiry->requester->name, 'items' => $inquiry->items->map(fn (SupplierInquiryItem $item) => $this->itemData($item))->values(),
                'attachments' => $this->attachmentData($inquiry), 'purchase_order' => $inquiry->purchaseOrder ? ['id' => $inquiry->purchaseOrder->id, 'number' => $inquiry->purchaseOrder->number] : null],
            'canEdit' => Gate::allows('update', $inquiry), 'canTransition' => Gate::allows('transition', $inquiry), 'canConvert' => Gate::allows('convert', $inquiry),
        ]);
    }

    public function edit(SupplierInquiry $inquiry): Response
    {
        Gate::authorize('update', $inquiry);
        $inquiry->load(['items.product', 'media']);

        return Inertia::render('purchases/inquiries/Edit', [...$this->formOptions(), 'inquiry' => [...$this->formData($inquiry), 'items' => $inquiry->items->map(fn (SupplierInquiryItem $item) => $this->itemData($item))->values(), 'attachments' => $this->attachmentData($inquiry)]]);
    }

    public function update(UpdateSupplierInquiryRequest $request, SupplierInquiry $inquiry): RedirectResponse
    {
        DB::transaction(function () use ($request, $inquiry): void {
            $data = $request->validated();
            $items = Arr::pull($data, 'items');
            Arr::forget($data, 'attachments');
            $inquiry->update($data);
            $inquiry->items()->delete();
            $inquiry->items()->createMany($items);
            $this->storeAttachments($request, $inquiry);
        });
        Inertia::flash('toast', ['type' => 'success', 'message' => 'Borrador actualizado.']);

        return to_route('purchases.inquiries.show', $inquiry);
    }

    public function destroy(SupplierInquiry $inquiry): RedirectResponse
    {
        Gate::authorize('delete', $inquiry);
        $inquiry->delete();
        Inertia::flash('toast', ['type' => 'success', 'message' => 'Consulta eliminada.']);

        return to_route('purchases.inquiries.index');
    }

    /** @return array<string, mixed> */
    private function formOptions(): array
    {
        $suppliers = Supplier::query()->select(['id', 'business_name', 'trade_name'])->where('is_active', true)->with(['products' => fn ($query) => $query->select(['products.id', 'sku', 'name', 'unit'])->where('products.is_active', true)->orderBy('name')])->orderBy('business_name')->get();

        return ['suppliers' => $suppliers->map(fn (Supplier $supplier) => ['id' => $supplier->id, 'name' => $supplier->trade_name ?: $supplier->business_name, 'products' => $supplier->products->map(fn (Product $product) => ['id' => $product->id, 'sku' => $product->sku, 'name' => $product->name, 'unit' => $product->unit->label()])->values()])->values()];
    }

    /** @return array<string, mixed> */
    private function formData(SupplierInquiry $inquiry): array
    {
        return ['id' => $inquiry->id, 'number' => $inquiry->number, 'supplier_id' => $inquiry->supplier_id, 'status' => $inquiry->status->value, 'requested_at' => $inquiry->requested_at?->toIso8601String(), 'responded_at' => $inquiry->responded_at?->toIso8601String(), 'valid_until' => $inquiry->valid_until?->toDateString(), 'notes' => $inquiry->notes];
    }

    /** @return array<string, mixed> */
    private function itemData(SupplierInquiryItem $item): array
    {
        return ['id' => $item->id, 'product_id' => $item->product_id, 'product_name' => $item->product->name, 'product_sku' => $item->product->sku, 'unit_label' => $item->product->unit->label(), 'quantity_requested' => $item->quantity_requested, 'is_available' => $item->is_available, 'quantity_available' => $item->quantity_available, 'quoted_unit_cost' => $item->quoted_unit_cost, 'supplier_notes' => $item->supplier_notes];
    }

    /** @return array<string, mixed> */
    private function summaryData(SupplierInquiry $inquiry): array
    {
        return ['id' => $inquiry->id, 'number' => $inquiry->number, 'supplier_name' => $inquiry->supplier->trade_name ?: $inquiry->supplier->business_name, 'status' => $inquiry->status->value, 'status_label' => $inquiry->status->label(), 'items_count' => $inquiry->items_count, 'requested_at' => $inquiry->requested_at?->toIso8601String(), 'created_at' => $inquiry->created_at->toIso8601String()];
    }

    /** @return array<int, array{value: string, label: string}> */
    private function statusOptions(): array
    {
        return array_map(fn (SupplierInquiryStatus $status) => ['value' => $status->value, 'label' => $status->label()], SupplierInquiryStatus::cases());
    }

    /** @return array<int, array<string, mixed>> */
    private function attachmentData(SupplierInquiry $inquiry): array
    {
        return $inquiry->getMedia('attachments')->map(fn (Media $media) => ['id' => $media->id, 'name' => $media->name, 'file_name' => $media->file_name, 'url' => $media->getUrl(), 'size' => $media->size])->all();
    }

    private function storeAttachments(Request $request, SupplierInquiry $inquiry): void
    {
        $attachments = $request->file('attachments', []);
        if (! is_array($attachments)) {
            return;
        }
        foreach ($attachments as $attachment) {
            $inquiry->addMedia($attachment)->toMediaCollection('attachments');
        }
    }
}
