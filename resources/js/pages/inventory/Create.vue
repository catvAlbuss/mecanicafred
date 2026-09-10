<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { PackagePlus } from '@lucide/vue';
import ProductForm from '@/components/inventory/ProductForm.vue';
import { create, index, store } from '@/routes/inventory/products';
import type { CategoryOption, Option } from '@/types';
defineProps<{ categories: CategoryOption[]; units: Option[] }>();
defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Inventario', href: index() },
            { title: 'Nuevo producto', href: create() },
        ],
    },
});
</script>
<template>

    <Head title="Nuevo producto" />
    <div class="mx-auto flex w-full max-w-5xl flex-1 flex-col gap-6 p-4 sm:p-6 lg:p-8">
        <header class="flex items-start gap-4">
            <span class="bg-racing-yellow text-racing-black flex size-12 items-center justify-center rounded-xl">
                <PackagePlus />
            </span>
            <div>
                <p class="text-racing-yellow text-xs font-bold tracking-[.2em] uppercase">
                    Inventario
                </p>
                <h1 class="text-2xl font-black sm:text-3xl">Nuevo producto</h1>
                <p class="text-muted-foreground text-sm">
                    Registra el producto y su saldo inicial.
                </p>
            </div>
        </header>
        <ProductForm :action="store.url()" :cancel-href="index.url()" submit-label="Registrar producto"
            :categories="categories" :units="units" />
    </div>
</template>
