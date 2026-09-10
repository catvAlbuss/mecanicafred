<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import {
    ChevronLeft,
    ChevronRight,
    History,
    PackageCheck,
    Search,
    Truck,
    WalletCards,
} from '@lucide/vue';
import { ref } from 'vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { index } from '@/routes/purchases/history';
import { show } from '@/routes/purchases/orders';
import type { HistoryPaginator, Option } from '@/types';

const props = defineProps<{
    orders: HistoryPaginator;
    stats: { orders: number; amount: string; suppliers: number; units: string };
    supplierSummary: {
        supplier_id: number;
        supplier_name: string;
        orders_count: number;
        total_amount: string;
    }[];
    filters: {
        search: string;
        supplier: number;
        product: number;
        status: string;
        from: string | null;
        to: string | null;
    };
    suppliers: { id: number; name: string }[];
    products: { id: number; name: string }[];
    statuses: Option[];
}>();
defineOptions({
    layout: { breadcrumbs: [{ title: 'Historial de compras', href: index() }] },
});
const search = ref(props.filters.search),
    supplier = ref(props.filters.supplier || ''),
    product = ref(props.filters.product || ''),
    status = ref(props.filters.status),
    from = ref(props.filters.from ?? ''),
    to = ref(props.filters.to ?? '');
function filter(): void {
    router.get(
        index.url(),
        {
            search: search.value || undefined,
            supplier: supplier.value || undefined,
            product: product.value || undefined,
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
    product.value = '';
    status.value = '';
    from.value = '';
    to.value = '';
    router.get(index.url(), {}, { preserveState: true, replace: true });
}
const money = (value: string) =>
    new Intl.NumberFormat('es-PE', {
        style: 'currency',
        currency: 'PEN',
    }).format(Number(value));
</script>

<template>
    <Head title="Historial de compras" />
    <div class="flex flex-1 flex-col gap-6 p-4 sm:p-6 lg:p-8">
        <section
            class="bg-racing-black relative overflow-hidden rounded-2xl p-6 text-white"
        >
            <div
                class="bg-racing-green/20 absolute -top-16 -right-12 size-48 rounded-full blur-3xl"
            />
            <div class="relative flex items-start gap-4">
                <span
                    class="bg-racing-yellow text-racing-black flex size-12 shrink-0 items-center justify-center rounded-xl"
                    ><History
                /></span>
                <div>
                    <p
                        class="text-racing-yellow text-xs font-bold tracking-[.2em] uppercase"
                    >
                        Compras
                    </p>
                    <h1 class="text-2xl font-black sm:text-3xl">
                        Historial de compras
                    </h1>
                    <p class="text-sm text-zinc-300">
                        Pedidos recibidos y cancelados con trazabilidad
                        completa.
                    </p>
                </div>
            </div>
        </section>
        <section class="grid grid-cols-2 gap-3 lg:grid-cols-4">
            <article
                v-for="item in [
                    {
                        label: 'Pedidos cerrados',
                        value: stats.orders,
                        icon: History,
                    },
                    {
                        label: 'Monto acumulado',
                        value: money(stats.amount),
                        icon: WalletCards,
                    },
                    {
                        label: 'Unidades recibidas',
                        value: Number(stats.units).toFixed(3),
                        icon: PackageCheck,
                    },
                    {
                        label: 'Proveedores',
                        value: stats.suppliers,
                        icon: Truck,
                    },
                ]"
                :key="item.label"
                class="bg-card rounded-2xl border p-4"
            >
                <component :is="item.icon" class="text-racing-yellow size-5" />
                <p class="mt-2 text-xl font-black sm:text-2xl">
                    {{ item.value }}
                </p>
                <p class="text-muted-foreground text-xs sm:text-sm">
                    {{ item.label }}
                </p>
            </article>
        </section>
        <form
            class="bg-card grid gap-3 rounded-2xl border p-4 md:grid-cols-2 xl:grid-cols-[minmax(13rem,1fr)_12rem_14rem_10rem_10rem_10rem_auto]"
            @submit.prevent="filter"
        >
            <div class="relative">
                <Search
                    class="text-muted-foreground absolute top-2.5 left-3 size-4"
                /><Input
                    v-model="search"
                    class="pl-9"
                    placeholder="Pedido, proveedor o producto"
                    aria-label="Buscar historial"
                />
            </div>
            <select
                v-model="supplier"
                class="h-9 rounded-md border px-3 text-sm"
                aria-label="Proveedor"
            >
                <option value="">Todos los proveedores</option>
                <option
                    v-for="item in suppliers"
                    :key="item.id"
                    :value="item.id"
                >
                    {{ item.name }}
                </option></select
            ><select
                v-model="product"
                class="h-9 rounded-md border px-3 text-sm"
                aria-label="Producto"
            >
                <option value="">Todos los productos</option>
                <option
                    v-for="item in products"
                    :key="item.id"
                    :value="item.id"
                >
                    {{ item.name }}
                </option></select
            ><select
                v-model="status"
                class="h-9 rounded-md border px-3 text-sm"
                aria-label="Estado"
            >
                <option value="">Todos los estados</option>
                <option
                    v-for="item in statuses"
                    :key="item.value"
                    :value="item.value"
                >
                    {{ item.label }}
                </option></select
            ><Input v-model="from" type="date" aria-label="Desde" /><Input
                v-model="to"
                type="date"
                aria-label="Hasta"
            />
            <div class="flex gap-2">
                <Button type="submit">Filtrar</Button
                ><Button type="button" variant="outline" @click="clearFilters"
                    >Limpiar</Button
                >
            </div>
        </form>
        <div class="grid gap-6 xl:grid-cols-[minmax(0,1fr)_20rem]">
            <section>
                <div
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
                                    <th class="px-5 py-3">Recepciones</th>
                                    <th class="px-5 py-3">Cierre</th>
                                    <th class="px-5 py-3">Estado</th>
                                    <th class="px-5 py-3 text-right">Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr
                                    v-for="order in orders.data"
                                    :key="order.id"
                                    class="border-t"
                                >
                                    <td class="px-5 py-4">
                                        <Link
                                            :href="show(order.id)"
                                            class="hover:text-racing-yellow font-black"
                                            >{{ order.number }}</Link
                                        >
                                    </td>
                                    <td class="px-5 py-4">
                                        {{ order.supplier_name }}
                                    </td>
                                    <td class="px-5 py-4">
                                        {{ order.receipts_count }}
                                    </td>
                                    <td class="px-5 py-4">
                                        {{
                                            new Date(
                                                order.closed_at,
                                            ).toLocaleDateString('es-PE')
                                        }}
                                    </td>
                                    <td class="px-5 py-4">
                                        <span
                                            :class="[
                                                'rounded-full px-2 py-1 text-xs font-bold',
                                                order.status === 'received'
                                                    ? 'bg-racing-green/15 text-racing-green'
                                                    : 'bg-racing-red/15 text-racing-red',
                                            ]"
                                            >{{ order.status_label }}</span
                                        >
                                    </td>
                                    <td class="px-5 py-4 text-right font-black">
                                        {{ money(order.total) }}
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
                            ><div class="flex justify-between gap-3">
                                <div>
                                    <p class="font-black">{{ order.number }}</p>
                                    <p class="text-muted-foreground text-sm">
                                        {{ order.supplier_name }}
                                    </p>
                                </div>
                                <span
                                    :class="[
                                        'h-fit rounded-full px-2 py-1 text-xs font-bold',
                                        order.status === 'received'
                                            ? 'bg-racing-green/15 text-racing-green'
                                            : 'bg-racing-red/15 text-racing-red',
                                    ]"
                                    >{{ order.status_label }}</span
                                >
                            </div>
                            <div class="mt-4 flex justify-between text-sm">
                                <span class="text-muted-foreground"
                                    >{{
                                        order.receipts_count
                                    }}
                                    recepciones</span
                                ><strong>{{ money(order.total) }}</strong>
                            </div></Link
                        >
                    </div>
                </div>
                <div
                    v-else
                    class="text-muted-foreground rounded-2xl border border-dashed p-12 text-center"
                >
                    <History class="mx-auto mb-3 size-8 opacity-50" />
                    <p class="font-bold">No hay compras en este periodo.</p>
                    <p class="text-sm">
                        Prueba ajustando o limpiando los filtros.
                    </p>
                </div>
                <nav
                    v-if="orders.last_page > 1"
                    class="mt-4 flex justify-end gap-2"
                >
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
            </section>
            <aside class="bg-card h-fit rounded-2xl border p-5">
                <h2 class="font-black">Compras por proveedor</h2>
                <p class="text-muted-foreground text-sm">
                    Frecuencia y monto según los filtros.
                </p>
                <div v-if="supplierSummary.length" class="mt-4 grid gap-3">
                    <button
                        v-for="item in supplierSummary"
                        :key="item.supplier_id"
                        type="button"
                        class="hover:border-racing-yellow rounded-xl border p-3 text-left transition"
                        @click="
                            supplier = item.supplier_id;
                            filter();
                        "
                    >
                        <span class="block truncate font-bold">{{
                            item.supplier_name
                        }}</span
                        ><span
                            class="text-muted-foreground mt-1 flex justify-between text-xs"
                            ><span>{{ item.orders_count }} pedidos</span
                            ><strong class="text-foreground">{{
                                money(item.total_amount)
                            }}</strong></span
                        >
                    </button>
                </div>
                <p v-else class="text-muted-foreground mt-4 text-sm">
                    Sin proveedores para mostrar.
                </p>
            </aside>
        </div>
    </div>
</template>
