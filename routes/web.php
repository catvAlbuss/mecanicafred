<?php

use App\Http\Controllers\InventoryAdjustmentController;
use App\Http\Controllers\ProductBarcodeLookupController;
use App\Http\Controllers\ProductCategoryController;
use App\Http\Controllers\ProductCategoryStatusController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProductMediaController;
use App\Http\Controllers\ProductPrimaryImageController;
use App\Http\Controllers\ProductStatusController;
use App\Http\Controllers\PurchaseHistoryController;
use App\Http\Controllers\PurchaseOrderController;
use App\Http\Controllers\PurchaseOrderMediaController;
use App\Http\Controllers\PurchaseOrderStatusController;
use App\Http\Controllers\PurchaseReceiptController;
use App\Http\Controllers\SupplierCatalogController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\SupplierInquiryController;
use App\Http\Controllers\SupplierInquiryConversionController;
use App\Http\Controllers\SupplierInquiryMediaController;
use App\Http\Controllers\SupplierInquiryResponseController;
use App\Http\Controllers\SupplierInquiryStatusController;
use App\Http\Controllers\SupplierMediaController;
use App\Http\Controllers\SupplierStatusController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/login')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::inertia('dashboard', 'Dashboard')->name('dashboard');
    Route::get('inventario', [ProductController::class, 'index'])->name('inventory.products.index');
    Route::get('inventario/codigo-barras', ProductBarcodeLookupController::class)->name('inventory.products.barcode.lookup');
    Route::resource('inventario/productos', ProductController::class)
        ->except('index')->parameters(['productos' => 'product'])->names('inventory.products');
    Route::patch('inventario/productos/{product}/estado', ProductStatusController::class)->name('inventory.products.status.update');
    Route::post('inventario/productos/{product}/ajustes', InventoryAdjustmentController::class)->name('inventory.products.adjustments.store');
    Route::delete('inventario/productos/{product}/imagenes/{media}', ProductMediaController::class)->name('inventory.products.media.destroy');
    Route::patch('inventario/productos/{product}/imagenes/{media}/principal', ProductPrimaryImageController::class)->name('inventory.products.media.primary');
    Route::resource('inventario/categorias', ProductCategoryController::class)
        ->only(['index', 'store', 'update', 'destroy'])->parameters(['categorias' => 'category'])->names('inventory.categories');
    Route::patch('inventario/categorias/{category}/estado', ProductCategoryStatusController::class)->name('inventory.categories.status.update');
    Route::patch('proveedores/{supplier}/estado', SupplierStatusController::class)
        ->name('suppliers.status.update');
    Route::get('proveedores/{supplier}/catalogo', [SupplierCatalogController::class, 'index'])->name('suppliers.catalog.index');
    Route::post('proveedores/{supplier}/catalogo', [SupplierCatalogController::class, 'store'])->name('suppliers.catalog.store');
    Route::patch('proveedores/{supplier}/catalogo/{product}', [SupplierCatalogController::class, 'update'])->name('suppliers.catalog.update');
    Route::delete('proveedores/{supplier}/catalogo/{product}', [SupplierCatalogController::class, 'destroy'])->name('suppliers.catalog.destroy');
    Route::delete('proveedores/{supplier}/archivos/{media}', SupplierMediaController::class)
        ->name('suppliers.media.destroy');
    Route::resource('proveedores', SupplierController::class)
        ->parameters(['proveedores' => 'supplier'])
        ->names('suppliers');
    Route::patch('compras/consultas/{inquiry}/estado', SupplierInquiryStatusController::class)->name('purchases.inquiries.status.update');
    Route::put('compras/consultas/{inquiry}/respuesta', SupplierInquiryResponseController::class)->name('purchases.inquiries.response.update');
    Route::post('compras/consultas/{inquiry}/convertir', SupplierInquiryConversionController::class)->name('purchases.inquiries.convert.store');
    Route::delete('compras/consultas/{inquiry}/archivos/{media}', SupplierInquiryMediaController::class)->name('purchases.inquiries.media.destroy');
    Route::resource('compras/consultas', SupplierInquiryController::class)
        ->parameters(['consultas' => 'inquiry'])->names('purchases.inquiries');
    Route::patch('compras/pedidos/{purchaseOrder}/estado', PurchaseOrderStatusController::class)->name('purchases.orders.status.update');
    Route::post('compras/pedidos/{purchaseOrder}/recepciones', PurchaseReceiptController::class)->name('purchases.orders.receipts.store');
    Route::delete('compras/pedidos/{purchaseOrder}/archivos/{media}', PurchaseOrderMediaController::class)->name('purchases.orders.media.destroy');
    Route::resource('compras/pedidos', PurchaseOrderController::class)
        ->parameters(['pedidos' => 'purchaseOrder'])->names('purchases.orders');
    Route::get('compras/historial', PurchaseHistoryController::class)->name('purchases.history.index');
});

require __DIR__.'/settings.php';
