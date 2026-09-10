<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { ClipboardPlus } from '@lucide/vue';
import InquiryForm from '@/components/inquiries/InquiryForm.vue';
import { create, index, store } from '@/routes/purchases/inquiries';
import type { InquirySupplier } from '@/types';
defineProps<{
    suppliers: InquirySupplier[];
    preselectedSupplierId: number | null;
    preselectedProductId: number | null;
}>();
defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Consultas', href: index() },
            { title: 'Nueva', href: create() },
        ],
    },
});
</script>
<template>
    <Head title="Nueva consulta" />
    <div
        class="mx-auto flex w-full max-w-5xl flex-1 flex-col gap-6 p-4 sm:p-6 lg:p-8"
    >
        <header class="flex items-start gap-4">
            <span
                class="bg-racing-yellow text-racing-black flex size-12 items-center justify-center rounded-xl"
                ><ClipboardPlus
            /></span>
            <div>
                <p
                    class="text-racing-yellow text-xs font-bold tracking-[.2em] uppercase"
                >
                    Compras
                </p>
                <h1 class="text-2xl font-black sm:text-3xl">Nueva consulta</h1>
                <p class="text-muted-foreground text-sm">
                    Solicita disponibilidad y cotización sin alterar el
                    inventario.
                </p>
            </div>
        </header>
        <InquiryForm
            :action="store.url()"
            :cancel-href="index.url()"
            submit-label="Crear borrador"
            :suppliers="suppliers"
            :preselected-supplier-id="preselectedSupplierId"
            :preselected-product-id="preselectedProductId"
        />
    </div>
</template>
