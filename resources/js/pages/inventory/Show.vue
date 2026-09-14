<script setup lang="ts">
import { Form, Head, Link, router, usePage } from '@inertiajs/vue3';
import {
    ArrowDown,
    ArrowLeft,
    ArrowUp,
    Boxes,
    Image,
    History,
    LoaderCircle,
    Pencil,
    Star,
    ShoppingCart,
    Trash2,
    Truck,
} from '@lucide/vue';
import ProductBarcodeLabel from '@/components/inventory/ProductBarcodeLabel.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { create as createInquiry } from '@/routes/purchases/inquiries';
import { create as createOrder } from '@/routes/purchases/orders';
import { index as purchaseHistory } from '@/routes/purchases/history';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { store as storeAdjustment } from '@/routes/inventory/products/adjustments';
import {
    destroy as destroyImage,
    primary,
} from '@/routes/inventory/products/media';
import { destroy, edit, index, show } from '@/routes/inventory/products';
import { update as updateStatus } from '@/routes/inventory/products/status';
import type { InventoryMovement, Paginator, ProductDetail } from '@/types';
const props = defineProps<{
    product: ProductDetail;
    movements: Paginator<InventoryMovement>;
    canManage: boolean;
    canAdjust: boolean;
}>();
const canCreateOrders =
    usePage().props.auth.user?.permissions?.includes('pedidos-compra.crear') ??
    false;
const canViewPurchaseHistory =
    usePage().props.auth.user?.permissions?.includes('historial-compras.ver') ??
    false;
defineOptions({
    layout: { breadcrumbs: [{ title: 'Inventario', href: index() }] },
});
function removeImage(id: number): void {
    if (confirm('¿Eliminar esta imagen?'))
        router.delete(
            destroyImage.url({ product: props.product.id, media: id }),
            { preserveScroll: true },
        );
}
function setPrimary(id: number): void {
    router.patch(
        primary.url({ product: props.product.id, media: id }),
        {},
        { preserveScroll: true },
    );
}
function toggleStatus(): void {
    router.patch(
        updateStatus.url(props.product.id),
        { is_active: !props.product.is_active },
        { preserveScroll: true },
    );
}
function removeProduct(): void {
    if (
        confirm('¿Eliminar este producto? Si tiene historial será desactivado.')
    )
        router.delete(destroy.url(props.product.id));
}
const formatDate = (value: string) =>
    new Intl.DateTimeFormat('es-PE', {
        dateStyle: 'medium',
        timeStyle: 'short',
    }).format(new Date(value));
const availabilityLabels: Record<string, string> = {
    unknown: 'Sin consultar',
    available: 'Disponible',
    limited: 'Limitado',
    unavailable: 'No disponible',
};
</script>
<template>
    <Head :title="product.name" />
    <div class="flex flex-1 flex-col gap-6 p-4 sm:p-6 lg:p-8">
        <header
            class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between"
        >
            <div class="flex items-start gap-4">
                <Button as-child variant="outline" size="icon">
                    <Link :href="index()">
                        <ArrowLeft />
                    </Link>
                </Button>
                <div>
                    <p
                        class="text-racing-yellow text-xs font-bold tracking-[.2em] uppercase"
                    >
                        {{ product.sku }}
                    </p>
                    <h1 class="text-2xl font-black sm:text-3xl">
                        {{ product.name }}
                    </h1>
                    <p class="text-muted-foreground text-sm">
                        {{ product.category.name }} ·
                        {{ product.brand || 'Sin marca' }}
                    </p>
                </div>
            </div>
            <div v-if="canManage" class="flex flex-wrap gap-2">
                <Button as-child variant="outline">
                    <Link :href="edit(product.id)">
                        <Pencil />Editar
                    </Link> </Button
                ><Button variant="outline" @click="toggleStatus">{{
                    product.is_active ? 'Desactivar' : 'Activar'
                }}</Button
                ><Button
                    variant="destructive"
                    size="icon"
                    @click="removeProduct"
                >
                    <Trash2 />
                </Button>
            </div>
        </header>
        <div class="grid gap-6 xl:grid-cols-[minmax(0,1fr)_22rem]">
            <main class="grid gap-6">
                <section class="bg-card rounded-2xl border p-5">
                    <div class="grid gap-5 sm:grid-cols-3">
                        <div>
                            <p class="text-muted-foreground text-xs uppercase">
                                Stock actual
                            </p>
                            <p class="mt-1 text-3xl font-black">
                                {{ product.current_stock }}
                                <span class="text-sm font-normal">{{
                                    product.unit_label
                                }}</span>
                            </p>
                        </div>
                        <div>
                            <p class="text-muted-foreground text-xs uppercase">
                                Stock mínimo
                            </p>
                            <p class="mt-1 text-xl font-black">
                                {{ product.minimum_stock }}
                            </p>
                        </div>
                        <div>
                            <p class="text-muted-foreground text-xs uppercase">
                                Ubicación
                            </p>
                            <p class="mt-1 font-bold">
                                {{ product.location || 'No asignada' }}
                            </p>
                        </div>
                    </div>
                    <p
                        v-if="product.description"
                        class="text-muted-foreground mt-5 border-t pt-5 text-sm"
                    >
                        {{ product.description }}
                    </p>
                </section>
                <section class="bg-card rounded-2xl border p-5">
                    <h2 class="flex items-center gap-2 font-black">
                        <Image class="text-racing-yellow size-5" />Galería
                    </h2>
                    <div
                        v-if="product.images.length"
                        class="mt-4 grid grid-cols-2 gap-3 sm:grid-cols-3"
                    >
                        <article
                            v-for="image in product.images"
                            :key="image.id"
                            class="group relative overflow-hidden rounded-xl border"
                        >
                            <img
                                :src="image.url"
                                :alt="image.name"
                                class="aspect-square w-full object-cover"
                            /><span
                                v-if="image.is_primary"
                                class="bg-racing-yellow text-racing-black absolute top-2 left-2 rounded-full px-2 py-1 text-xs font-bold"
                                >Principal</span
                            >
                            <div
                                v-if="canManage"
                                class="absolute inset-x-2 bottom-2 flex gap-2"
                            >
                                <Button
                                    v-if="!image.is_primary"
                                    size="icon"
                                    variant="secondary"
                                    @click="setPrimary(image.id)"
                                >
                                    <Star /> </Button
                                ><Button
                                    size="icon"
                                    variant="destructive"
                                    @click="removeImage(image.id)"
                                >
                                    <Trash2 />
                                </Button>
                            </div>
                        </article>
                    </div>
                    <p
                        v-else
                        class="text-muted-foreground mt-4 rounded-xl border border-dashed p-8 text-center text-sm"
                    >
                        Aún no hay imágenes.
                    </p>
                </section>
                <section class="bg-card overflow-hidden rounded-2xl border">
                    <div class="flex items-start justify-between gap-3 p-5">
                        <div>
                            <h2 class="font-black">Historial de movimientos</h2>
                            <p class="text-muted-foreground text-sm">
                                Registro inmutable de cada cambio del saldo.
                            </p>
                        </div>
                        <Button
                            v-if="canViewPurchaseHistory"
                            as-child
                            size="sm"
                            variant="outline"
                        >
                            <Link
                                :href="
                                    purchaseHistory({
                                        query: { product: product.id },
                                    })
                                "
                            >
                                <History />Compras
                            </Link>
                        </Button>
                    </div>
                    <div v-if="movements.data.length" class="divide-y">
                        <article
                            v-for="movement in movements.data"
                            :key="movement.id"
                            class="flex flex-col gap-3 p-5 sm:flex-row sm:items-center sm:justify-between"
                        >
                            <div class="flex gap-3">
                                <span
                                    :class="[
                                        'flex size-9 items-center justify-center rounded-full',
                                        Number(movement.quantity) >= 0
                                            ? 'bg-racing-green/15 text-racing-green'
                                            : 'bg-racing-red/15 text-racing-red',
                                    ]"
                                >
                                    <ArrowUp
                                        v-if="Number(movement.quantity) >= 0"
                                        class="size-4"
                                    />
                                    <ArrowDown v-else class="size-4" />
                                </span>
                                <div>
                                    <p class="font-bold">
                                        {{ movement.type_label }}
                                    </p>
                                    <p class="text-muted-foreground text-xs">
                                        {{ movement.reason }} ·
                                        {{ movement.user_name }}
                                    </p>
                                </div>
                            </div>
                            <div class="sm:text-right">
                                <p class="font-black">
                                    {{ Number(movement.quantity) > 0 ? '+' : ''
                                    }}{{ movement.quantity }}
                                </p>
                                <p class="text-muted-foreground text-xs">
                                    {{ movement.stock_before }} →
                                    {{ movement.stock_after }} ·
                                    {{ formatDate(movement.occurred_at) }}
                                </p>
                            </div>
                        </article>
                    </div>
                    <p
                        v-else
                        class="text-muted-foreground p-8 text-center text-sm"
                    >
                        No hay movimientos registrados.
                    </p>
                </section>
            </main>
            <aside class="grid h-fit gap-6 xl:sticky xl:top-6">
                <section class="bg-card rounded-2xl border p-5">
                    <h2 class="flex items-center gap-2 font-black">
                        <Boxes class="text-racing-yellow size-5" />Código para
                        escanear
                    </h2>
                    <p class="text-muted-foreground mt-1 text-sm">
                        SKU {{ product.sku }}
                    </p>
                    <div class="mt-4">
                        <ProductBarcodeLabel
                            :value="product.barcode"
                            :name="product.name"
                            :sku="product.sku"
                        />
                    </div>
                </section>
                <Form
                    v-if="canAdjust"
                    :action="storeAdjustment.url(product.id)"
                    method="post"
                    class="bg-racing-black rounded-2xl p-5 text-white"
                    reset-on-success
                    #default="{ errors, processing }"
                >
                    <h2 class="font-black">Ajustar stock</h2>
                    <p class="mt-1 text-sm text-zinc-400">
                        Todo ajuste requiere un motivo.
                    </p>
                    <div class="mt-5 grid gap-4">
                        <div class="grid gap-2">
                            <Label for="direction" class="text-white"
                                >Movimiento</Label
                            ><select
                                id="direction"
                                name="direction"
                                class="h-9 rounded-md border border-zinc-700 bg-zinc-900 px-3 text-sm text-white"
                            >
                                <option value="in">Entrada</option>
                                <option value="out">Salida</option>
                            </select>
                            <InputError :message="errors.direction" />
                        </div>
                        <div class="grid gap-2">
                            <Label for="quantity" class="text-white"
                                >Cantidad</Label
                            ><Input
                                id="quantity"
                                name="quantity"
                                type="number"
                                min="0.001"
                                step="0.001"
                                required
                                class="border-zinc-700 bg-zinc-900 text-white dark:text-white"
                            />
                            <InputError :message="errors.quantity" />
                        </div>
                        <div class="grid gap-2">
                            <Label for="reason" class="text-white">Motivo</Label
                            ><textarea
                                id="reason"
                                name="reason"
                                required
                                maxlength="255"
                                rows="3"
                                class="rounded-md border border-zinc-700 bg-zinc-900 px-3 py-2 text-sm text-white"
                            />
                            <InputError :message="errors.reason" />
                        </div>
                        <Button
                            type="submit"
                            :disabled="processing"
                            class="bg-racing-yellow text-racing-black"
                        >
                            <LoaderCircle
                                v-if="processing"
                                class="animate-spin"
                            />Registrar ajuste
                        </Button>
                    </div>
                </Form>
                <section class="bg-card rounded-2xl border p-5">
                    <h2 class="flex items-center gap-2 font-black">
                        <Truck class="text-racing-yellow size-5" />Proveedores
                    </h2>
                    <div
                        v-if="product.suppliers.length"
                        class="mt-4 grid gap-2"
                    >
                        <article
                            v-for="supplier in product.suppliers"
                            :key="supplier.id"
                            class="rounded-lg border p-3 text-sm font-bold"
                        >
                            <span class="block">{{ supplier.name }}</span>
                            <span
                                class="text-muted-foreground mt-1 block text-xs font-normal"
                            >
                                {{
                                    availabilityLabels[
                                        supplier.availability_status
                                    ]
                                }}
                                ·
                                {{
                                    supplier.available_quantity ??
                                    'sin cantidad'
                                }}
                                ·
                                {{
                                    supplier.last_unit_cost
                                        ? `S/ ${supplier.last_unit_cost}`
                                        : 'sin precio'
                                }}
                            </span>
                            <div class="mt-3 flex flex-wrap gap-2">
                                <Button as-child size="sm" variant="outline">
                                    <Link
                                        :href="
                                            createInquiry({
                                                query: {
                                                    supplier: supplier.id,
                                                    product: product.id,
                                                },
                                            })
                                        "
                                    >
                                        <Truck />Consultar
                                    </Link>
                                </Button>
                                <Button
                                    v-if="canCreateOrders"
                                    as-child
                                    size="sm"
                                    class="bg-racing-yellow text-racing-black"
                                >
                                    <Link
                                        :href="
                                            createOrder({
                                                query: {
                                                    supplier: supplier.id,
                                                    product: product.id,
                                                },
                                            })
                                        "
                                    >
                                        <ShoppingCart />Crear pedido
                                    </Link>
                                </Button>
                            </div>
                        </article>
                    </div>
                    <p v-else class="text-muted-foreground mt-3 text-sm">
                        Este producto aún no tiene proveedores asociados.
                    </p>
                </section>
            </aside>
        </div>
    </div>
</template>
