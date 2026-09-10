<?php

namespace App\Http\Controllers;

use App\Models\Supplier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class SupplierMediaController extends Controller
{
    public function __invoke(Supplier $supplier, Media $media): RedirectResponse
    {
        Gate::authorize('update', $supplier);

        $belongsToSupplier = $media->model_type === $supplier->getMorphClass()
            && (int) $media->model_id === $supplier->id;
        $isSupplierMedia = in_array($media->collection_name, ['logo', 'attachments'], true);

        abort_unless($belongsToSupplier && $isSupplierMedia, 404);

        $isLogo = $media->collection_name === 'logo';
        $media->delete();

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => $isLogo ? 'Logo eliminado correctamente.' : 'Archivo eliminado correctamente.',
        ]);

        return back();
    }
}
