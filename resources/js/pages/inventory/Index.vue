<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import {
    AlertTriangle,
    Boxes,
    CheckCircle2,
    ChevronLeft,
    ChevronRight,
    Eye,
    PackagePlus,
    Search,
    Tags,
    XCircle,
} from '@lucide/vue';
import { ref } from 'vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { index as categoriesIndex } from '@/routes/inventory/categories';
import { create, index, show } from '@/routes/inventory/products';
import type {
    CategoryOption,
    Option,
    Paginator,
    ProductSummary,
} from '@/types';

const props = defineProps<{
    products: Paginator<ProductSummary>;
    filters: {
        search: string;
        category: number;
        type: string;
        stock: string;
        status: string;
    };
    categories: CategoryOption[];
    types: Option[];
    stats: { total: number; available: number; low: number; out: number };
    canManage: boolean;
    canViewCategories: boolean;
}>();
defineOptions({
    layout: { breadcrumbs: [{ title: 'Inventario', href: index() }] },
});
const searchTerm = ref(props.filters.search);
const categoryFilter = ref(props.filters.category || '');
const typeFilter = ref(props.filters.type);
const stockFilter = ref(props.filters.stock);
const statusFilter = ref(props.filters.status);
function applyFilters(): void {
    router.get(
        index.url(),
        {
            search: searchTerm.value || undefined,
            category: categoryFilter.value || undefined,
            type: typeFilter.value || undefined,
            stock: stockFilter.value || undefined,
            status: statusFilter.value || undefined,
        },
        { preserveState: true, replace: true },
    );
}
function clearFilters(): void {
    searchTerm.value = '';
    categoryFilter.value = '';
    typeFilter.value = '';
    stockFilter.value = '';
    statusFilter.value = '';
    router.get(index.url(), {}, { preserveState: true, replace: true });
}
const labels = { available: 'Disponible', low: 'Stock bajo', out: 'Agotado' };
const statusClass = {
    available: 'bg-racing-green/15 text-racing-green',
    low: 'bg-racing-yellow/20 text-amber-700 dark:text-racing-yellow',
    out: 'bg-racing-red/15 text-racing-red',
};
</script>

<template>
    <Head title="Inventario" />
    <div class="flex flex-1 flex-col gap-6 p-4 sm:p-6 lg:p-8">
        <section
            class="bg-racing-black relative overflow-hidden rounded-2xl px-5 py-7 text-white sm:px-8"
        >
            <div
                class="bg-racing-yellow/20 absolute -top-20 -right-16 size-56 rounded-full blur-3xl"
            />
            <div
                class="relative flex flex-col gap-5 sm:flex-row sm:items-center sm:justify-between"
            >
                <div class="flex items-start gap-4">
                    <span
                        class="bg-racing-yellow text-racing-black flex size-12 shrink-0 items-center justify-center rounded-xl"
                        ><Boxes
                    /></span>
                    <div>
                        <p
                            class="text-racing-yellow text-xs font-bold tracking-[.2em] uppercase"
                        >
                            Fredy Racing
                        </p>
                        <h1 class="text-2xl font-black sm:text-3xl">
                            Inventario
                        </h1>
                        <p class="mt-1 text-sm text-zinc-300">
                            Control de productos, repuestos, herramientas y
                            materiales.
                        </p>
                    </div>
                </div>
                <div class="flex flex-wrap gap-2">
                    <Button
                        v-if="canViewCategories"
                        as-child
                        variant="outline"
                        class="border-zinc-600 bg-transparent text-white"
                        ><Link :href="categoriesIndex()"
                            ><Tags />Categorías</Link
                        ></Button
                    ><Button
                        v-if="canManage"
                        as-child
                        class="bg-racing-yellow text-racing-black hover:bg-racing-yellow/90"
                        ><Link :href="create()"
                            ><PackagePlus />Registrar producto</Link
                        ></Button
                    >
                </div>
            </div>
        </section>
        <section class="grid grid-cols-2 gap-3 lg:grid-cols-4">
            <article
                v-for="item in [
                    {
                        n: stats.total,
                        l: 'Productos',
                        i: Boxes,
                        c: 'text-foreground',
                    },
                    {
                        n: stats.available,
                        l: 'Disponibles',
                        i: CheckCircle2,
                        c: 'text-racing-green',
                    },
                    {
                        n: stats.low,
                        l: 'Stock bajo',
                        i: AlertTriangle,
                        c: 'text-racing-yellow',
                    },
                    {
                        n: stats.out,
                        l: 'Agotados',
                        i: XCircle,
                        c: 'text-racing-red',
                    },
                ]"
                :key="item.l"
                class="bg-card rounded-2xl border p-4"
            >
                <component :is="item.i" :class="['size-5', item.c]" />
                <p class="mt-2 text-2xl font-black">{{ item.n }}</p>
                <p class="text-muted-foreground text-sm">{{ item.l }}</p>
            </article>
        </section>
        <section class="bg-card rounded-2xl border p-4">
            <form
                class="grid gap-3 md:grid-cols-2 xl:grid-cols-6"
                @submit.prevent="applyFilters"
            >
                <div class="relative md:col-span-2">
                    <Search
                        class="text-muted-foreground absolute top-2.5 left-3 size-4"
                    /><Input
                        v-model="searchTerm"
                        class="pl-9"
                        placeholder="Buscar nombre, SKU, marca..."
                    />
                </div>
                <select
                    v-model="categoryFilter"
                    class="border-input h-9 rounded-md border bg-transparent px-3 text-sm"
                >
                    <option value="">Todas las categorías</option>
                    <option
                        v-for="category in categories"
                        :key="category.id"
                        :value="category.id"
                    >
                        {{ category.name }}
                    </option></select
                ><select
                    v-model="typeFilter"
                    class="border-input h-9 rounded-md border bg-transparent px-3 text-sm"
                >
                    <option value="">Todos los tipos</option>
                    <option
                        v-for="type in types"
                        :key="type.value"
                        :value="type.value"
                    >
                        {{ type.label }}
                    </option></select
                ><select
                    v-model="stockFilter"
                    class="border-input h-9 rounded-md border bg-transparent px-3 text-sm"
                >
                    <option value="">Todo stock</option>
                    <option value="available">Disponible</option>
                    <option value="low">Stock bajo</option>
                    <option value="out">Agotado</option>
                </select>
                <div class="flex gap-2">
                    <Button type="submit" class="flex-1">Filtrar</Button
                    ><Button
                        type="button"
                        variant="outline"
                        @click="clearFilters"
                        >Limpiar</Button
                    >
                </div>
            </form>
        </section>
        <section
            v-if="products.data.length"
            class="bg-card overflow-hidden rounded-2xl border"
        >
            <div class="hidden overflow-x-auto lg:block">
                <table class="w-full text-sm">
                    <thead
                        class="bg-racing-black text-left text-xs text-white uppercase"
                    >
                        <tr>
                            <th class="px-5 py-3">Producto</th>
                            <th class="px-5 py-3">Categoría</th>
                            <th class="px-5 py-3">Stock</th>
                            <th class="px-5 py-3">Estado</th>
                            <th class="px-5 py-3 text-right">Acción</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="product in products.data"
                            :key="product.id"
                            class="border-t"
                        >
                            <td class="px-5 py-4">
                                <p class="font-bold">{{ product.name }}</p>
                                <p class="text-muted-foreground text-xs">
                                    {{ product.sku }} ·
                                    {{ product.brand || 'Sin marca' }}
                                </p>
                            </td>
                            <td class="px-5 py-4">
                                {{ product.category.name }}
                            </td>
                            <td class="px-5 py-4 font-black">
                                {{ product.current_stock }}
                                <span
                                    class="text-muted-foreground font-normal"
                                    >{{ product.unit_label }}</span
                                >
                            </td>
                            <td class="px-5 py-4">
                                <span
                                    :class="[
                                        'rounded-full px-2.5 py-1 text-xs font-bold',
                                        statusClass[product.stock_status],
                                    ]"
                                    >{{ labels[product.stock_status] }}</span
                                >
                            </td>
                            <td class="px-5 py-4 text-right">
                                <Button as-child size="sm" variant="ghost"
                                    ><Link :href="show(product.id)"
                                        ><Eye />Ver</Link
                                    ></Button
                                >
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div class="grid gap-3 p-3 sm:grid-cols-2 lg:hidden">
                <Link
                    v-for="product in products.data"
                    :key="product.id"
                    :href="show(product.id)"
                    class="hover:border-racing-yellow rounded-xl border p-4 transition"
                    ><div class="flex justify-between gap-3">
                        <div>
                            <p class="font-black">{{ product.name }}</p>
                            <p class="text-muted-foreground text-xs">
                                {{ product.sku }} · {{ product.category.name }}
                            </p>
                        </div>
                        <span
                            :class="[
                                'h-fit rounded-full px-2 py-1 text-xs font-bold',
                                statusClass[product.stock_status],
                            ]"
                            >{{ labels[product.stock_status] }}</span
                        >
                    </div>
                    <p class="mt-5 text-2xl font-black">
                        {{ product.current_stock }}
                        <span
                            class="text-muted-foreground text-sm font-normal"
                            >{{ product.unit_label }}</span
                        >
                    </p></Link
                >
            </div>
        </section>
        <section
            v-else
            class="bg-card rounded-2xl border border-dashed p-10 text-center"
        >
            <Boxes class="text-muted-foreground mx-auto size-9" />
            <h2 class="mt-3 font-black">No se encontraron productos</h2>
            <p class="text-muted-foreground text-sm">
                Prueba otros filtros o registra el primer producto.
            </p>
        </section>
        <nav
            v-if="products.last_page > 1"
            class="flex items-center justify-between"
        >
            <p class="text-muted-foreground text-sm">
                Mostrando {{ products.from }}–{{ products.to }} de
                {{ products.total }}
            </p>
            <div class="flex gap-2">
                <Button
                    as-child
                    size="sm"
                    variant="outline"
                    :disabled="!products.prev_page_url"
                    ><Link :href="products.prev_page_url || '#'" preserve-scroll
                        ><ChevronLeft /></Link></Button
                ><Button
                    as-child
                    size="sm"
                    variant="outline"
                    :disabled="!products.next_page_url"
                    ><Link :href="products.next_page_url || '#'" preserve-scroll
                        ><ChevronRight /></Link
                ></Button>
            </div>
        </nav>
    </div>
</template>
