<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import {
    ChevronLeft,
    ChevronRight,
    Eye,
    Plus,
    Search,
    ShoppingBag,
} from '@lucide/vue';
import { ref } from 'vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { create, index, show } from '@/routes/sales';
import type { CashOption, SalePaginator } from '@/types';

const props = defineProps<{
    sales: SalePaginator;
    filters: {
        search: string;
        status: string;
        payment_method: string;
        from: string | null;
        to: string | null;
    };
    stats: { count: number; total: string };
    statuses: CashOption[];
    paymentMethods: CashOption[];
    canSell: boolean;
}>();

defineOptions({
    layout: { breadcrumbs: [{ title: 'Ventas', href: index() }] },
});

const search = ref(props.filters.search);
const status = ref(props.filters.status);
const method = ref(props.filters.payment_method);
const from = ref(props.filters.from ?? '');
const to = ref(props.filters.to ?? '');

const money = (value: string) =>
    new Intl.NumberFormat('es-PE', {
        style: 'currency',
        currency: 'PEN',
    }).format(Number(value));
const time = (value: string) =>
    new Date(value).toLocaleString('es-PE', {
        dateStyle: 'short',
        timeStyle: 'short',
    });

function filter(): void {
    router.get(
        index.url(),
        {
            search: search.value || undefined,
            status: status.value || undefined,
            payment_method: method.value || undefined,
            from: from.value || undefined,
            to: to.value || undefined,
        },
        { preserveState: true, replace: true },
    );
}
function clearFilters(): void {
    search.value = status.value = method.value = from.value = to.value = '';
    router.get(index.url(), {}, { preserveState: true, replace: true });
}
</script>

<template>

    <Head title="Ventas" />
    <div class="flex flex-1 flex-col gap-6 p-4 sm:p-6 lg:p-8">
        <section class="bg-racing-black relative overflow-hidden rounded-2xl p-6 text-white">
            <div class="bg-racing-yellow/15 absolute -top-16 -right-12 size-48 rounded-full blur-3xl" />
            <div class="relative flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div class="flex gap-4">
                    <span
                        class="bg-racing-yellow text-racing-black flex size-12 shrink-0 items-center justify-center rounded-xl">
                        <ShoppingBag />
                    </span>
                    <div>
                        <p class="text-racing-yellow text-xs font-bold tracking-[.2em] uppercase">
                            Tienda
                        </p>
                        <h1 class="text-2xl font-black sm:text-3xl">Ventas</h1>
                        <p class="text-sm text-zinc-300">
                            {{ stats.count }} ventas ·
                            {{ money(stats.total) }} en el rango
                        </p>
                    </div>
                </div>
                <Button v-if="canSell" as-child class="bg-racing-yellow text-racing-black">
                    <Link :href="create()">
                        <Plus />Nueva venta
                    </Link>
                </Button>
            </div>
        </section>

        <form
            class="bg-card grid gap-3 rounded-2xl border p-4 md:grid-cols-2 xl:grid-cols-[minmax(12rem,1fr)_10rem_10rem_9rem_9rem_auto]"
            @submit.prevent="filter">
            <div class="relative">
                <Search class="text-muted-foreground absolute top-2.5 left-3 size-4" />
                <Input v-model="search" class="pl-9" placeholder="N°, cliente o producto" aria-label="Buscar ventas" />
            </div>
            <select v-model="status" class="h-9 rounded-md border px-3 text-sm" aria-label="Estado">
                <option value="">Todos los estados</option>
                <option v-for="item in statuses" :key="item.value" :value="item.value">
                    {{ item.label }}
                </option>
            </select>
            <select v-model="method" class="h-9 rounded-md border px-3 text-sm" aria-label="Método de pago">
                <option value="">Todos los métodos</option>
                <option v-for="item in paymentMethods" :key="item.value" :value="item.value">
                    {{ item.label }}
                </option>
            </select>
            <Input v-model="from" type="date" aria-label="Desde" />
            <Input v-model="to" type="date" aria-label="Hasta" />
            <div class="flex gap-2">
                <Button type="submit">Filtrar</Button>
                <Button type="button" variant="outline" @click="clearFilters">Limpiar</Button>
            </div>
        </form>

        <section v-if="sales.data.length" class="bg-card overflow-hidden rounded-2xl border">
            <div class="hidden overflow-x-auto lg:block">
                <table class="w-full text-sm">
                    <thead class="bg-racing-black text-left text-xs text-white uppercase">
                        <tr>
                            <th class="px-5 py-3">Venta</th>
                            <th class="px-5 py-3">Cliente</th>
                            <th class="px-5 py-3">Items</th>
                            <th class="px-5 py-3">Pago</th>
                            <th class="px-5 py-3">Total</th>
                            <th class="px-5 py-3">Fecha</th>
                            <th class="px-5 py-3">Estado</th>
                            <th class="px-5 py-3 text-right">Acción</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="sale in sales.data" :key="sale.id" class="border-t">
                            <td class="px-5 py-4 font-black">
                                {{ sale.number }}
                            </td>
                            <td class="px-5 py-4">
                                {{ sale.customer_name ?? 'Mostrador' }}
                            </td>
                            <td class="px-5 py-4">{{ sale.items_count }}</td>
                            <td class="px-5 py-4">
                                {{ sale.payment_method_label }}
                            </td>
                            <td class="px-5 py-4 font-black">
                                {{ money(sale.total) }}
                            </td>
                            <td class="px-5 py-4">{{ time(sale.sold_at) }}</td>
                            <td class="px-5 py-4">
                                <span :class="[
                                    'rounded-full px-2 py-1 text-xs font-bold',
                                    sale.status === 'cancelled'
                                        ? 'bg-racing-red/15 text-racing-red'
                                        : 'bg-racing-green/15 text-racing-green',
                                ]">{{ sale.status_label }}</span>
                            </td>
                            <td class="px-5 py-4 text-right">
                                <Button as-child size="icon" variant="ghost">
                                    <Link :href="show(sale.id)" :aria-label="`Ver ${sale.number}`">
                                        <Eye />
                                    </Link>
                                </Button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div class="grid gap-3 p-3 lg:hidden">
                <Link v-for="sale in sales.data" :key="sale.id" :href="show(sale.id)" class="rounded-xl border p-4">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <p class="font-black">{{ sale.number }}</p>
                            <p class="text-muted-foreground text-sm">
                                {{ sale.customer_name ?? 'Mostrador' }} ·
                                {{ sale.payment_method_label }}
                            </p>
                        </div>
                        <span :class="[
                            'rounded-full px-2 py-1 text-xs font-bold',
                            sale.status === 'cancelled'
                                ? 'bg-racing-red/15 text-racing-red'
                                : 'bg-racing-green/15 text-racing-green',
                        ]">{{ sale.status_label }}</span>
                    </div>
                    <div class="mt-3 flex items-end justify-between">
                        <p class="text-muted-foreground text-xs">
                            {{ sale.items_count }} items ·
                            {{ time(sale.sold_at) }}
                        </p>
                        <p class="font-black">{{ money(sale.total) }}</p>
                    </div>
                </Link>
            </div>
        </section>
        <div v-else class="text-muted-foreground rounded-2xl border border-dashed p-12 text-center">
            <ShoppingBag class="mx-auto mb-3 size-8 opacity-50" />
            <p class="font-bold">No hay ventas con estos filtros.</p>
        </div>

        <nav v-if="sales.last_page > 1" class="flex justify-end gap-2">
            <Button as-child variant="outline" size="icon">
                <Link :href="sales.prev_page_url || '#'" aria-label="Página anterior">
                    <ChevronLeft />
                </Link>
            </Button><Button as-child variant="outline" size="icon">
                <Link :href="sales.next_page_url || '#'" aria-label="Página siguiente">
                    <ChevronRight />
                </Link>
            </Button>
        </nav>
    </div>
</template>
