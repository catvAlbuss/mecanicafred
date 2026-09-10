<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { Pencil } from '@lucide/vue';
import PurchaseOrderForm from '@/components/purchases/PurchaseOrderForm.vue';
import { index, show, update } from '@/routes/purchases/orders';
import type { OrderDetail, OrderSupplier } from '@/types';
defineProps<{ order: OrderDetail; suppliers: OrderSupplier[] }>();
defineOptions({
    layout: { breadcrumbs: [{ title: 'Pedidos', href: index() }] },
});
</script>
<template>
    <Head :title="`Editar ${order.number}`" />
    <div
        class="mx-auto flex w-full max-w-6xl flex-1 flex-col gap-6 p-4 sm:p-6 lg:p-8"
    >
        <header class="flex items-start gap-4">
            <span
                class="bg-racing-yellow text-racing-black flex size-12 items-center justify-center rounded-xl"
                ><Pencil
            /></span>
            <div>
                <p
                    class="text-racing-yellow text-xs font-bold tracking-[.2em] uppercase"
                >
                    {{ order.number }}
                </p>
                <h1 class="text-2xl font-black sm:text-3xl">Editar pedido</h1>
                <p class="text-muted-foreground text-sm">
                    Disponible mientras permanezca como borrador.
                </p>
            </div>
        </header>
        <PurchaseOrderForm
            :action="update.url(order.id)"
            :cancel-href="show.url(order.id)"
            submit-label="Guardar cambios"
            :suppliers="suppliers"
            :order="order"
        />
    </div>
</template>
