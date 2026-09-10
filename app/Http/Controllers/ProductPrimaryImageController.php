<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class ProductPrimaryImageController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Product $product, Media $media): RedirectResponse
    {
        Gate::authorize('update', $product);
        abort_unless($media->model_type === Product::class && $media->model_id === $product->id && $media->collection_name === 'images', 404);
        foreach ($product->getMedia('images') as $image) {
            $image->setCustomProperty('is_primary', $image->is($media))->save();
        }
        Inertia::flash('toast', ['type' => 'success', 'message' => 'Imagen principal actualizada.']);

        return back();
    }
}
