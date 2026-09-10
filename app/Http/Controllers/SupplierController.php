<?php

namespace App\Http\Controllers;

use App\Http\Requests\IndexSupplierRequest;
use App\Http\Requests\StoreSupplierRequest;
use App\Http\Requests\UpdateSupplierRequest;
use App\Models\Supplier;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class SupplierController extends Controller
{
    public function index(IndexSupplierRequest $request): Response
    {
        $search = $request->string('search')->trim()->toString();
        $status = $request->string('status')->toString();

        $suppliers = Supplier::query()
            ->select([
                'id',
                'tax_id',
                'business_name',
                'trade_name',
                'contact_name',
                'phone',
                'email',
                'is_active',
            ])
            ->with('media')
            ->withCount('products')
            ->when($search !== '', function (Builder $query) use ($search): void {
                $query->where(function (Builder $searchQuery) use ($search): void {
                    $searchQuery
                        ->where('business_name', 'like', "%{$search}%")
                        ->orWhere('trade_name', 'like', "%{$search}%")
                        ->orWhere('tax_id', 'like', "%{$search}%")
                        ->orWhere('contact_name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhereHas('products', function (Builder $productQuery) use ($search): void {
                            $productQuery
                                ->where('name', 'like', "%{$search}%")
                                ->orWhere('sku', 'like', "%{$search}%");
                        });
                });
            })
            ->when($status !== '', fn (Builder $query) => $query->where('is_active', $status === 'active'))
            ->orderBy('business_name')
            ->orderBy('id')
            ->paginate(12)
            ->withQueryString()
            ->through(fn (Supplier $supplier): array => $this->summaryData($supplier));

        return Inertia::render('suppliers/Index', [
            'suppliers' => $suppliers,
            'filters' => [
                'search' => $search,
                'status' => $status,
            ],
            'stats' => [
                'total' => Supplier::query()->count(),
                'active' => Supplier::query()->where('is_active', true)->count(),
                'inactive' => Supplier::query()->where('is_active', false)->count(),
            ],
            'canManage' => $request->user()->can('create', Supplier::class),
        ]);
    }

    public function create(): Response
    {
        Gate::authorize('create', Supplier::class);

        return Inertia::render('suppliers/Create');
    }

    public function store(StoreSupplierRequest $request): RedirectResponse
    {
        $supplier = Supplier::query()->create(
            $request->safe()->except(['logo', 'attachments']),
        );

        $this->storeMedia($request, $supplier);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'Proveedor registrado correctamente.',
        ]);

        return to_route('suppliers.show', $supplier);
    }

    public function show(Supplier $supplier): Response
    {
        Gate::authorize('view', $supplier);

        $supplier->load(['media', 'products.category']);

        return Inertia::render('suppliers/Show', [
            'supplier' => [
                ...$this->formData($supplier),
                'logo_url' => $supplier->getFirstMediaUrl('logo') ?: null,
                'logo_media_id' => $supplier->getFirstMedia('logo')?->id,
                'attachments' => $this->attachmentData($supplier),
                'products' => $supplier->products
                    ->sortBy('name')
                    ->values()
                    ->map(fn ($product): array => [
                        'id' => $product->id,
                        'sku' => $product->sku,
                        'name' => $product->name,
                        'category' => $product->category->name,
                        'availability_status' => data_get($product->getRelation('pivot'), 'availability_status'),
                    ]),
            ],
            'canManage' => Gate::allows('update', $supplier),
        ]);
    }

    public function edit(Supplier $supplier): Response
    {
        Gate::authorize('update', $supplier);

        $supplier->load('media');

        return Inertia::render('suppliers/Edit', [
            'supplier' => [
                ...$this->formData($supplier),
                'logo_url' => $supplier->getFirstMediaUrl('logo') ?: null,
                'logo_media_id' => $supplier->getFirstMedia('logo')?->id,
                'attachments' => $this->attachmentData($supplier),
            ],
        ]);
    }

    public function update(UpdateSupplierRequest $request, Supplier $supplier): RedirectResponse
    {
        $supplier->update($request->safe()->except(['logo', 'attachments']));
        $this->storeMedia($request, $supplier);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'Proveedor actualizado correctamente.',
        ]);

        return to_route('suppliers.show', $supplier);
    }

    public function destroy(Supplier $supplier): RedirectResponse
    {
        Gate::authorize('delete', $supplier);

        if ($supplier->products()->exists() || $supplier->inquiries()->exists() || $supplier->purchaseOrders()->exists()) {
            $supplier->update(['is_active' => false]);

            Inertia::flash('toast', [
                'type' => 'success',
                'message' => 'El proveedor tiene productos asociados y fue desactivado.',
            ]);
        } else {
            $supplier->delete();

            Inertia::flash('toast', [
                'type' => 'success',
                'message' => 'Proveedor eliminado correctamente.',
            ]);
        }

        return to_route('suppliers.index');
    }

    /**
     * @return array<string, mixed>
     */
    private function summaryData(Supplier $supplier): array
    {
        return [
            'id' => $supplier->id,
            'tax_id' => $supplier->tax_id,
            'business_name' => $supplier->business_name,
            'trade_name' => $supplier->trade_name,
            'contact_name' => $supplier->contact_name,
            'phone' => $supplier->phone,
            'email' => $supplier->email,
            'is_active' => $supplier->is_active,
            'products_count' => $supplier->products_count,
            'logo_url' => $supplier->getFirstMediaUrl('logo') ?: null,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function formData(Supplier $supplier): array
    {
        return [
            'id' => $supplier->id,
            'tax_id' => $supplier->tax_id,
            'business_name' => $supplier->business_name,
            'trade_name' => $supplier->trade_name,
            'contact_name' => $supplier->contact_name,
            'phone' => $supplier->phone,
            'secondary_phone' => $supplier->secondary_phone,
            'email' => $supplier->email,
            'address' => $supplier->address,
            'district' => $supplier->district,
            'province' => $supplier->province,
            'notes' => $supplier->notes,
            'is_active' => $supplier->is_active,
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function attachmentData(Supplier $supplier): array
    {
        return $supplier->getMedia('attachments')
            ->map(fn (Media $media): array => [
                'id' => $media->id,
                'name' => $media->name,
                'file_name' => $media->file_name,
                'mime_type' => $media->mime_type,
                'size' => $media->size,
                'url' => $media->getUrl(),
            ])
            ->values()
            ->all();
    }

    private function storeMedia(Request $request, Supplier $supplier): void
    {
        $logo = $request->file('logo');

        if ($logo instanceof UploadedFile) {
            $supplier->addMedia($logo)->toMediaCollection('logo');
        }

        $attachments = $request->file('attachments', []);

        if (! is_array($attachments)) {
            return;
        }

        foreach ($attachments as $attachment) {
            $supplier->addMedia($attachment)->toMediaCollection('attachments');
        }
    }
}
