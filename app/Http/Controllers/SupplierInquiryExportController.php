<?php

namespace App\Http\Controllers;

use App\Models\SupplierInquiry;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SupplierInquiryExportController extends Controller
{
    public function __invoke(SupplierInquiry $inquiry): StreamedResponse
    {
        Gate::authorize('view', $inquiry);

        $items = $inquiry->items()
            ->with('product:id,sku,name')
            ->oldest('id')
            ->get();

        return response()->streamDownload(function () use ($items): void {
            $stream = fopen('php://output', 'wb');

            if ($stream === false) {
                return;
            }

            fwrite($stream, "\xEF\xBB\xBF");
            fputcsv($stream, ['Código', 'Producto', 'Cantidad'], ';', '"', '');

            foreach ($items as $item) {
                fputcsv($stream, [
                    $this->excelSafe($item->product->sku),
                    $this->excelSafe($item->product->name),
                    $this->formatQuantity($item->quantity_requested),
                ], ';', '"', '');
            }

            fclose($stream);
        }, 'cotizacion-'.Str::lower($inquiry->number).'.csv', [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    private function excelSafe(string $value): string
    {
        return preg_match('/^[=+\-@\t\r]/', ltrim($value)) === 1 ? "'{$value}" : $value;
    }

    private function formatQuantity(float|string $quantity): string
    {
        return rtrim(rtrim(number_format((float) $quantity, 3, ',', ''), '0'), ',');
    }
}
