<?php

namespace App\Http\Controllers;

use App\Enums\PurchaseOrderStatus;
use App\Models\PurchaseOrder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class PurchaseOrderMediaController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(PurchaseOrder $purchaseOrder, Media $media): RedirectResponse
    {
        Gate::authorize('update', $purchaseOrder);
        abort_unless($purchaseOrder->status === PurchaseOrderStatus::Draft, 403);
        abort_unless($media->model_type === PurchaseOrder::class && $media->model_id === $purchaseOrder->id && $media->collection_name === 'attachments', 404);
        $media->delete();
        Inertia::flash('toast', ['type' => 'success', 'message' => 'Adjunto eliminado.']);

        return back();
    }
}
