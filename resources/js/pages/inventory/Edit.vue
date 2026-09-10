<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { Pencil } from '@lucide/vue';
import ProductForm from '@/components/inventory/ProductForm.vue';
import { edit, index, show, update } from '@/routes/inventory/products';
import type { CategoryOption, Option, ProductDetail } from '@/types';
const props = defineProps<{
    product: ProductDetail;
    categories: CategoryOption[];
    units: Option[];
}>();
defineOptions({
    layout: { breadcrumbs: [{ title: 'Inventario', href: index() }] },
});
</script>
<template>

    <Head :title="`Editar ${product.name}`" />
    <div class="mx-auto flex w-full max-w-5xl flex-1 flex-col gap-6 p-4 sm:p-6 lg:p-8">
        <header class="flex items-start gap-4">
            <span class="bg-racing-yellow text-racing-black flex size-12 items-center justify-center rounded-xl">
                <Pencil />
            </span>
            <div>
                <p class="text-racing-yellow text-xs font-bold tracking-[.2em] uppercase">
                    {{ product.sku }}
                </p>
                <h1 class="text-2xl font-black sm:text-3xl">
                    Editar {{ product.name }}
                </h1>
            </div>
        </header>
        <ProductForm :action="update.url(product.id)" :cancel-href="show.url(product.id)" submit-label="Guardar cambios"
            :categories="categories" :units="units" :product="product" />
    </div>
</template>
