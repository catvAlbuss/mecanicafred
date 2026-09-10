<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class ProductMediaController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Product $product, Media $media): RedirectResponse
    {
        Gate::authorize('update', $product);
        abort_unless($media->model_type === Product::class && $media->model_id === $product->id && $media->collection_name === 'images', 404);
        $wasPrimary = (bool) $media->getCustomProperty('is_primary', false);
        $media->delete();
        if ($wasPrimary && ($next = $product->fresh()->getFirstMedia('images'))) {
            $next->setCustomProperty('is_primary', true)->save();
        }
        Inertia::flash('toast', ['type' => 'success', 'message' => 'Imagen eliminada.']);

        return back();
    }
}
