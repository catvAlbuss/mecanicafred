<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import {
    ChevronLeft,
    ChevronRight,
    ClipboardList,
    Eye,
    Plus,
    Search,
} from '@lucide/vue';
import { ref } from 'vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { create, index, show } from '@/routes/purchases/inquiries';
import type { InquiryPaginator, Option } from '@/types';
const props = defineProps<{
    inquiries: InquiryPaginator;
    filters: { search: string; supplier: number; status: string };
    suppliers: { id: number; name: string }[];
    statuses: Option[];
    canManage: boolean;
}>();
defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Consultas a proveedores', href: index() }],
    },
});
const search = ref(props.filters.search),
    supplier = ref(props.filters.supplier || ''),
    status = ref(props.filters.status);
function filter(): void {
    router.get(
        index.url(),
        {
            search: search.value || undefined,
            supplier: supplier.value || undefined,
            status: status.value || undefined,
        },
        { preserveState: true, replace: true },
    );
}
const colors: Record<string, string> = {
    draft: 'bg-muted text-muted-foreground',
    sent: 'bg-blue-500/15 text-blue-600',
    answered: 'bg-racing-green/15 text-racing-green',
    closed: 'bg-zinc-500/15 text-zinc-500',
    cancelled: 'bg-racing-red/15 text-racing-red',
};
</script>
<template>
    <Head title="Consultas a proveedores" />
    <div class="flex flex-1 flex-col gap-6 p-4 sm:p-6 lg:p-8">
        <section class="bg-racing-black rounded-2xl p-6 text-white">
            <div
                class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between"
            >
                <div class="flex gap-4">
                    <span
                        class="bg-racing-yellow text-racing-black flex size-12 items-center justify-center rounded-xl"
                        ><ClipboardList
                    /></span>
                    <div>
                        <p
                            class="text-racing-yellow text-xs font-bold tracking-[.2em] uppercase"
                        >
                            Compras
                        </p>
                        <h1 class="text-2xl font-black sm:text-3xl">
                            Consultas a proveedores
                        </h1>
                        <p class="text-sm text-zinc-300">
                            Disponibilidad y cotizaciones registradas.
                        </p>
                    </div>
                </div>
                <Button
                    v-if="canManage"
                    as-child
                    class="bg-racing-yellow text-racing-black"
                    ><Link :href="create()"
                        ><Plus />Nueva consulta</Link
                    ></Button
                >
            </div>
        </section>
        <form
            class="bg-card grid gap-3 rounded-2xl border p-4 md:grid-cols-[1fr_14rem_12rem_auto]"
            @submit.prevent="filter"
        >
            <div class="relative">
                <Search
                    class="text-muted-foreground absolute top-2.5 left-3 size-4"
                /><Input
                    v-model="search"
                    class="pl-9"
                    placeholder="Número o proveedor"
                />
            </div>
            <select
                v-model="supplier"
                class="border-input h-9 rounded-md border bg-transparent px-3 text-sm"
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
                v-model="status"
                class="border-input h-9 rounded-md border bg-transparent px-3 text-sm"
            >
                <option value="">Todos los estados</option>
                <option
                    v-for="item in statuses"
                    :key="item.value"
                    :value="item.value"
                >
                    {{ item.label }}
                </option></select
            ><Button>Filtrar</Button>
        </form>
        <section
            v-if="inquiries.data.length"
            class="grid gap-3 md:grid-cols-2 xl:grid-cols-3"
        >
            <Link
                v-for="inquiry in inquiries.data"
                :key="inquiry.id"
                :href="show(inquiry.id)"
                class="bg-card hover:border-racing-yellow rounded-2xl border p-5 transition"
                ><div class="flex justify-between">
                    <p class="font-black">{{ inquiry.number }}</p>
                    <span
                        :class="[
                            'rounded-full px-2 py-1 text-xs font-bold',
                            colors[inquiry.status],
                        ]"
                        >{{ inquiry.status_label }}</span
                    >
                </div>
                <p class="mt-4 font-bold">{{ inquiry.supplier_name }}</p>
                <div
                    class="text-muted-foreground mt-4 flex items-center justify-between text-sm"
                >
                    <span>{{ inquiry.items_count }} productos</span
                    ><Eye class="size-4" /></div
            ></Link>
        </section>
        <div
            v-else
            class="text-muted-foreground rounded-2xl border border-dashed p-12 text-center"
        >
            No hay consultas con estos filtros.
        </div>
        <nav v-if="inquiries.last_page > 1" class="flex justify-end gap-2">
            <Button as-child variant="outline" size="icon"
                ><Link :href="inquiries.prev_page_url || '#'"
                    ><ChevronLeft /></Link></Button
            ><Button as-child variant="outline" size="icon"
                ><Link :href="inquiries.next_page_url || '#'"
                    ><ChevronRight /></Link
            ></Button>
        </nav>
    </div>
</template>
