<?php

namespace App\Http\Controllers;

use App\Models\SupplierInquiry;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class SupplierInquiryMediaController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(SupplierInquiry $inquiry, Media $media): RedirectResponse
    {
        Gate::authorize('update', $inquiry);
        abort_unless($media->model_type === SupplierInquiry::class && $media->model_id === $inquiry->id && $media->collection_name === 'attachments', 404);
        $media->delete();
        Inertia::flash('toast', ['type' => 'success', 'message' => 'Adjunto eliminado.']);

        return back();
    }
}
