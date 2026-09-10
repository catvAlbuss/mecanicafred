<?php

namespace App\Http\Controllers;

use App\Actions\Inventory\CreateProduct;
use App\Enums\MeasurementUnit;
use App\Enums\ProductType;
use App\Http\Requests\IndexProductRequest;
use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateProductRequest;
use App\Models\InventoryMovement;
use App\Models\Product;
use App\Models\ProductCategory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class ProductController extends Controller
{
    public function index(IndexProductRequest $request): Response
    {
        $search = $request->string('search')->trim()->toString();
        $category = $request->integer('category');
        $type = $request->string('type')->toString();
        $stock = $request->string('stock')->toString();
        $status = $request->string('status')->toString();

        $products = Product::query()
            ->select(['id', 'product_category_id', 'sku', 'barcode', 'name', 'description', 'brand', 'unit', 'minimum_stock', 'current_stock', 'location', 'last_purchase_cost', 'sale_price', 'is_active'])
            ->with(['category:id,name,type', 'media'])
            ->when($search !== '', function (Builder $query) use ($search): void {
                $value = '%'.addcslashes($search, '%_\\').'%';
                $query->where(function (Builder $searchQuery) use ($value): void {
                    $searchQuery->where('name', 'like', $value)->orWhere('sku', 'like', $value)
                        ->orWhere('barcode', 'like', $value)
                        ->orWhere('brand', 'like', $value)->orWhere('description', 'like', $value);
                });
            })
            ->when($category > 0, fn (Builder $query) => $query->where('product_category_id', $category))
            ->when($type !== '', fn (Builder $query) => $query->whereHas('category', fn (Builder $categoryQuery) => $categoryQuery->where('type', $type)))
            ->when($stock === 'out', fn (Builder $query) => $query->where('current_stock', '<=', 0))
            ->when($stock === 'low', fn (Builder $query) => $query->where('current_stock', '>', 0)->whereColumn('current_stock', '<=', 'minimum_stock'))
            ->when($stock === 'available', fn (Builder $query) => $query->whereColumn('current_stock', '>', 'minimum_stock'))
            ->when($status !== '', fn (Builder $query) => $query->where('is_active', $status === 'active'))
            ->orderBy('name')->orderBy('id')->paginate(12)->withQueryString()
            ->through(fn (Product $product): array => $this->summaryData($product));

        return Inertia::render('inventory/Index', [
            'products' => $products,
            'filters' => compact('search', 'category', 'type', 'stock', 'status'),
            'categories' => $this->categoryOptions(),
            'types' => $this->typeOptions(),
            'stats' => [
                'total' => Product::query()->count(),
                'available' => Product::query()->whereColumn('current_stock', '>', 'minimum_stock')->count(),
                'low' => Product::query()->where('current_stock', '>', 0)->whereColumn('current_stock', '<=', 'minimum_stock')->count(),
                'out' => Product::query()->where('current_stock', '<=', 0)->count(),
            ],
            'canManage' => $request->user()->can('create', Product::class),
            'canViewCategories' => $request->user()->can('viewAny', ProductCategory::class),
        ]);
    }

    public function create(): Response
    {
        Gate::authorize('create', Product::class);

        return Inertia::render('inventory/Create', $this->formOptions());
    }

    public function store(StoreProductRequest $request, CreateProduct $createProduct): RedirectResponse
    {
        $images = $request->file('images', []);
        $product = $createProduct->handle($request->safe()->except('images'), $request->user(), is_array($images) ? array_values($images) : []);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Producto registrado correctamente.']);

        return to_route('inventory.products.show', $product);
    }

    public function show(Product $product): Response
    {
        Gate::authorize('view', $product);
        $product->load(['category', 'media', 'suppliers:id,business_name,trade_name,is_active']);
        $movements = $product->inventoryMovements()->with('user:id,name')->latest('occurred_at')->latest('id')
            ->paginate(10, ['*'], 'movements_page')->withQueryString()->through(fn (InventoryMovement $movement): array => [
                'id' => $movement->id, 'type' => $movement->type->value, 'type_label' => $movement->type->label(),
                'quantity' => $movement->quantity, 'stock_before' => $movement->stock_before, 'stock_after' => $movement->stock_after,
                'reason' => $movement->reason, 'occurred_at' => $movement->occurred_at->toIso8601String(),
                'user_name' => $movement->user_id === null ? 'Sistema' : $movement->user->name,
            ]);

        return Inertia::render('inventory/Show', [
            'product' => [...$this->formData($product), 'images' => $this->imageData($product), 'suppliers' => $product->suppliers->map(fn ($supplier) => [
                'id' => $supplier->id, 'name' => $supplier->trade_name ?: $supplier->business_name, 'is_active' => $supplier->is_active,
                'availability_status' => data_get($supplier->getRelation('pivot'), 'availability_status'),
                'available_quantity' => data_get($supplier->getRelation('pivot'), 'available_quantity'),
                'last_unit_cost' => data_get($supplier->getRelation('pivot'), 'last_unit_cost'),
                'lead_time_days' => data_get($supplier->getRelation('pivot'), 'lead_time_days'),
            ])->values()],
            'movements' => $movements,
            'canManage' => Gate::allows('update', $product),
            'canAdjust' => Gate::allows('adjust', $product),
        ]);
    }

    public function edit(Product $product): Response
    {
        Gate::authorize('update', $product);
        $product->load(['category', 'media']);

        return Inertia::render('inventory/Edit', [...$this->formOptions(), 'product' => [...$this->formData($product), 'images' => $this->imageData($product)]]);
    }

    public function update(UpdateProductRequest $request, Product $product): RedirectResponse
    {
        $product->update($request->safe()->except(['images', 'initial_stock', 'current_stock']));
        $images = $request->file('images', []);
        if (is_array($images)) {
            $hasPrimary = $product->getMedia('images')->contains(fn (Media $media) => $media->getCustomProperty('is_primary', false));
            foreach ($images as $image) {
                $product->addMedia($image)->withCustomProperties(['is_primary' => ! $hasPrimary])->toMediaCollection('images');
                $hasPrimary = true;
            }
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Producto actualizado correctamente.']);

        return to_route('inventory.products.show', $product);
    }

    public function destroy(Product $product): RedirectResponse
    {
        Gate::authorize('delete', $product);
        if ($product->inventoryMovements()->exists() || $product->suppliers()->exists() || $product->inquiryItems()->exists() || $product->purchaseOrderItems()->exists() || $product->saleItems()->exists()) {
            $product->update(['is_active' => false]);
            $message = 'El producto tiene historial y fue desactivado.';
        } else {
            $product->delete();
            $message = 'Producto eliminado correctamente.';
        }
        Inertia::flash('toast', ['type' => 'success', 'message' => $message]);

        return to_route('inventory.products.index');
    }

    /** @return array<string, mixed> */
    private function formOptions(): array
    {
        return ['categories' => $this->categoryOptions(true), 'units' => array_map(fn (MeasurementUnit $unit) => ['value' => $unit->value, 'label' => $unit->label()], MeasurementUnit::cases())];
    }

    /** @return array<int, array<string, mixed>> */
    private function categoryOptions(bool $activeOnly = false): array
    {
        return ProductCategory::query()->select(['id', 'name', 'type', 'is_active'])->when($activeOnly, fn (Builder $query) => $query->where('is_active', true))
            ->orderBy('name')->get()->map(fn (ProductCategory $category) => ['id' => $category->id, 'name' => $category->name, 'type' => $category->type->value])->all();
    }

    /** @return array<int, array{value: string, label: string}> */
    private function typeOptions(): array
    {
        return array_map(fn (ProductType $type) => ['value' => $type->value, 'label' => $type->label()], ProductType::cases());
    }

    /** @return array<string, mixed> */
    private function summaryData(Product $product): array
    {
        $status = bccomp((string) $product->current_stock, '0', 3) !== 1 ? 'out' : (bccomp((string) $product->current_stock, (string) $product->minimum_stock, 3) !== 1 ? 'low' : 'available');

        return [...$this->formData($product), 'stock_status' => $status, 'primary_image_url' => $this->primaryImage($product)?->getUrl()];
    }

    /** @return array<string, mixed> */
    private function formData(Product $product): array
    {
        return ['id' => $product->id, 'product_category_id' => $product->product_category_id, 'sku' => $product->sku, 'barcode' => $product->barcode, 'name' => $product->name,
            'description' => $product->description, 'brand' => $product->brand, 'unit' => $product->unit->value, 'unit_label' => $product->unit->label(),
            'minimum_stock' => $product->minimum_stock, 'current_stock' => $product->current_stock, 'location' => $product->location,
            'last_purchase_cost' => $product->last_purchase_cost, 'sale_price' => $product->sale_price, 'is_active' => $product->is_active,
            'category' => ['id' => $product->category->id, 'name' => $product->category->name, 'type' => $product->category->type->value, 'type_label' => $product->category->type->label()]];
    }

    private function primaryImage(Product $product): ?Media
    {
        return $product->getMedia('images')->first(fn (Media $media) => $media->getCustomProperty('is_primary', false)) ?? $product->getFirstMedia('images');
    }

    /** @return array<int, array<string, mixed>> */
    private function imageData(Product $product): array
    {
        return $product->getMedia('images')->sortByDesc(fn (Media $media) => (bool) $media->getCustomProperty('is_primary', false))->values()->map(fn (Media $media) => [
            'id' => $media->id, 'name' => $media->name, 'url' => $media->getUrl(), 'is_primary' => (bool) $media->getCustomProperty('is_primary', false),
        ])->all();
    }
}
