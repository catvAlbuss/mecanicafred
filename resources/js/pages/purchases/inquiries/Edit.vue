<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { Pencil } from '@lucide/vue';
import InquiryForm from '@/components/inquiries/InquiryForm.vue';
import { index, show, update } from '@/routes/purchases/inquiries';
import type { InquiryDetail, InquirySupplier } from '@/types';
defineProps<{ inquiry: InquiryDetail; suppliers: InquirySupplier[] }>();
defineOptions({
    layout: { breadcrumbs: [{ title: 'Consultas', href: index() }] },
});
</script>
<template>
    <Head :title="`Editar ${inquiry.number}`" />
    <div
        class="mx-auto flex w-full max-w-5xl flex-1 flex-col gap-6 p-4 sm:p-6 lg:p-8"
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
                    {{ inquiry.number }}
                </p>
                <h1 class="text-2xl font-black sm:text-3xl">Editar consulta</h1>
            </div>
        </header>
        <InquiryForm
            :action="update.url(inquiry.id)"
            :cancel-href="show.url(inquiry.id)"
            submit-label="Guardar cambios"
            :suppliers="suppliers"
            :inquiry="inquiry"
        />
    </div>
</template>
