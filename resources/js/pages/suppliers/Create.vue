<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { Building2 } from '@lucide/vue';
import SupplierForm from '@/components/suppliers/SupplierForm.vue';
import { create, index, store } from '@/routes/suppliers';

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Proveedores', href: index() },
            { title: 'Nuevo proveedor', href: create() },
        ],
    },
});
</script>

<template>
    <Head title="Nuevo proveedor" />

    <div
        class="mx-auto flex w-full max-w-5xl flex-1 flex-col gap-6 p-4 sm:p-6 lg:p-8"
    >
        <header class="flex items-start gap-4">
            <span
                class="bg-racing-yellow text-racing-black flex size-12 shrink-0 items-center justify-center rounded-xl"
            >
                <Building2 class="size-6" />
            </span>
            <div>
                <p
                    class="text-racing-yellow text-xs font-bold tracking-[0.2em] uppercase"
                >
                    Proveedores
                </p>
                <h1 class="text-2xl font-black tracking-tight sm:text-3xl">
                    Nuevo proveedor
                </h1>
                <p class="text-muted-foreground mt-1 text-sm">
                    Registra la información necesaria para futuras compras.
                </p>
            </div>
        </header>

        <SupplierForm
            :action="store.url()"
            :cancel-href="index.url()"
            submit-label="Registrar proveedor"
        />
    </div>
</template>
