<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import {
    ChevronLeft,
    ChevronRight,
    Eye,
    Plus,
    Search,
    ShoppingCart,
} from '@lucide/vue';
import { ref } from 'vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { create, index, show } from '@/routes/purchases/orders';
import type { Option, OrderPaginator } from '@/types';
const props = defineProps<{
    orders: OrderPaginator;
    filters: {
        search: string;
        supplier: number;
        status: string;
        from: string | null;
        to: string | null;
    };
    suppliers: { id: number; name: string }[];
    statuses: Option[];
    canCreate: boolean;
}>();
defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Pedidos a proveedores', href: index() }],
    },
});
const search = ref(props.filters.search),
    supplier = ref(props.filters.supplier || ''),
    status = ref(props.filters.status),
    from = ref(props.filters.from ?? ''),
    to = ref(props.filters.to ?? '');
function filter(): void {
    router.get(
        index.url(),
        {
            search: search.value || undefined,
            supplier: supplier.value || undefined,
            status: status.value || undefined,
            from: from.value || undefined,
            to: to.value || undefined,
        },
        { preserveState: true, replace: true },
    );
}
function clearFilters(): void {
    search.value = '';
    supplier.value = '';
    status.value = '';
    from.value = '';
    to.value = '';
    router.get(index.url(), {}, { preserveState: true, replace: true });
}
const colors: Record<string, string> = {
    draft: 'bg-muted text-muted-foreground',
    sent: 'bg-blue-500/15 text-blue-600 dark:text-blue-400',
    confirmed: 'bg-racing-green/15 text-racing-green',
    partially_received:
        'bg-racing-yellow/20 text-amber-700 dark:text-racing-yellow',
    received: 'bg-racing-green text-white',
    cancelled: 'bg-racing-red/15 text-racing-red',
};
const money = (value: string) =>
    new Intl.NumberFormat('es-PE', {
        style: 'currency',
        currency: 'PEN',
    }).format(Number(value));
</script>
<template>
    <Head title="Pedidos a proveedores" />
    <div class="flex flex-1 flex-col gap-6 p-4 sm:p-6 lg:p-8">
        <section
            class="bg-racing-black relative overflow-hidden rounded-2xl p-6 text-white"
        >
            <div
                class="bg-racing-yellow/15 absolute -top-16 -right-12 size-48 rounded-full blur-3xl"
            />
            <div
                class="relative flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between"
            >
                <div class="flex gap-4">
                    <span
                        class="bg-racing-yellow text-racing-black flex size-12 shrink-0 items-center justify-center rounded-xl"
                        ><ShoppingCart
                    /></span>
                    <div>
                        <p
                            class="text-racing-yellow text-xs font-bold tracking-[.2em] uppercase"
                        >
                            Compras
                        </p>
                        <h1 class="text-2xl font-black sm:text-3xl">
                            Pedidos a proveedores
                        </h1>
                        <p class="text-sm text-zinc-300">
                            Órdenes activas pendientes de recepción.
                        </p>
                    </div>
                </div>
                <Button
                    v-if="canCreate"
                    as-child
                    class="bg-racing-yellow text-racing-black"
                    ><Link :href="create()"><Plus />Nuevo pedido</Link></Button
                >
            </div>
        </section>
        <form
            class="bg-card grid gap-3 rounded-2xl border p-4 md:grid-cols-2 xl:grid-cols-[minmax(14rem,1fr)_13rem_11rem_10rem_10rem_auto]"
            @submit.prevent="filter"
        >
            <div class="relative">
                <Search
                    class="text-muted-foreground absolute top-2.5 left-3 size-4"
                /><Input
                    v-model="search"
                    class="pl-9"
                    placeholder="Pedido, proveedor o producto"
                    aria-label="Buscar pedidos"
                />
            </div>
            <select
                v-model="supplier"
                class="h-9 rounded-md border px-3 text-sm"
                aria-label="Filtrar por proveedor"
            >
                <option value="">Todos los proveedores</option>
                <option
                    v-for="item in suppliers"
                    :key="item.id"
                    :value="item.id"
                >
                    {{ item.name }}
                </option>
            </select>
            <select
                v-model="status"
                class="h-9 rounded-md border px-3 text-sm"
                aria-label="Filtrar por estado"
            >
                <option value="">Pedidos activos</option>
                <option
                    v-for="item in statuses"
                    :key="item.value"
                    :value="item.value"
                >
                    {{ item.label }}
                </option>
            </select>
            <Input
                v-model="from"
                type="date"
                aria-label="Fecha inicial"
            /><Input v-model="to" type="date" aria-label="Fecha final" />
            <div class="flex gap-2">
                <Button type="submit">Filtrar</Button
                ><Button type="button" variant="outline" @click="clearFilters"
                    >Limpiar</Button
                >
            </div>
        </form>
        <section
            v-if="orders.data.length"
            class="bg-card overflow-hidden rounded-2xl border"
        >
            <div class="hidden overflow-x-auto lg:block">
                <table class="w-full text-sm">
                    <thead
                        class="bg-racing-black text-left text-xs text-white uppercase"
                    >
                        <tr>
                            <th class="px-5 py-3">Pedido</th>
                            <th class="px-5 py-3">Proveedor</th>
                            <th class="px-5 py-3">Productos</th>
                            <th class="px-5 py-3">Entrega</th>
                            <th class="px-5 py-3">Total</th>
                            <th class="px-5 py-3">Estado</th>
                            <th class="px-5 py-3 text-right">Acción</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="order in orders.data"
                            :key="order.id"
                            class="border-t"
                        >
                            <td class="px-5 py-4 font-black">
                                {{ order.number }}
                            </td>
                            <td class="px-5 py-4">{{ order.supplier_name }}</td>
                            <td class="px-5 py-4">{{ order.items_count }}</td>
                            <td class="px-5 py-4">
                                {{ order.expected_at || 'Sin fecha' }}
                            </td>
                            <td class="px-5 py-4 font-black">
                                {{ money(order.total) }}
                            </td>
                            <td class="px-5 py-4">
                                <span
                                    :class="[
                                        'rounded-full px-2 py-1 text-xs font-bold',
                                        colors[order.status],
                                    ]"
                                    >{{ order.status_label }}</span
                                >
                            </td>
                            <td class="px-5 py-4 text-right">
                                <Button as-child size="icon" variant="ghost"
                                    ><Link
                                        :href="show(order.id)"
                                        :aria-label="`Ver ${order.number}`"
                                        ><Eye /></Link
                                ></Button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div class="grid gap-3 p-3 lg:hidden">
                <Link
                    v-for="order in orders.data"
                    :key="order.id"
                    :href="show(order.id)"
                    class="rounded-xl border p-4"
                    ><div class="flex items-start justify-between gap-3">
                        <div>
                            <p class="font-black">{{ order.number }}</p>
                            <p class="text-muted-foreground text-sm">
                                {{ order.supplier_name }}
                            </p>
                        </div>
                        <span
                            :class="[
                                'rounded-full px-2 py-1 text-xs font-bold',
                                colors[order.status],
                            ]"
                            >{{ order.status_label }}</span
                        >
                    </div>
                    <div class="mt-4 flex items-end justify-between">
                        <p class="text-muted-foreground text-xs">
                            {{ order.items_count }} productos ·
                            {{ order.expected_at || 'Sin fecha' }}
                        </p>
                        <p class="font-black">{{ money(order.total) }}</p>
                    </div></Link
                >
            </div>
        </section>
        <div
            v-else
            class="text-muted-foreground rounded-2xl border border-dashed p-12 text-center"
        >
            <ShoppingCart class="mx-auto mb-3 size-8 opacity-50" />
            <p class="font-bold">No hay pedidos con estos filtros.</p>
            <p class="text-sm">
                Prueba limpiando los filtros o crea un pedido nuevo.
            </p>
        </div>
        <nav v-if="orders.last_page > 1" class="flex justify-end gap-2">
            <Button as-child variant="outline" size="icon"
                ><Link
                    :href="orders.prev_page_url || '#'"
                    aria-label="Página anterior"
                    ><ChevronLeft /></Link></Button
            ><Button as-child variant="outline" size="icon"
                ><Link
                    :href="orders.next_page_url || '#'"
                    aria-label="Página siguiente"
                    ><ChevronRight /></Link
            ></Button>
        </nav>
    </div>
</template>
