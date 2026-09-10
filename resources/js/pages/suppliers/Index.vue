<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import {
    Building2,
    CheckCircle2,
    ChevronLeft,
    ChevronRight,
    CircleOff,
    PackageSearch,
    Pencil,
    Plus,
    Search,
    Truck,
} from '@lucide/vue';
import { ref } from 'vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { create, edit, index, show } from '@/routes/suppliers';
import { update as updateStatus } from '@/routes/suppliers/status';
import type { SupplierPaginator, SupplierSummary } from '@/types';

const props = defineProps<{
    suppliers: SupplierPaginator;
    filters: {
        search: string;
        status: string;
    };
    stats: {
        total: number;
        active: number;
        inactive: number;
    };
    canManage: boolean;
}>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Proveedores', href: index() }],
    },
});

const searchTerm = ref(props.filters.search);
const statusFilter = ref(props.filters.status);

function applyFilters(): void {
    router.get(
        index.url(),
        {
            search: searchTerm.value || undefined,
            status: statusFilter.value || undefined,
        },
        { preserveState: true, replace: true },
    );
}

function clearFilters(): void {
    searchTerm.value = '';
    statusFilter.value = '';
    router.get(index.url(), {}, { preserveState: true, replace: true });
}

function supplierInitials(supplier: SupplierSummary): string {
    return supplier.business_name
        .split(' ')
        .slice(0, 2)
        .map((word) => word.charAt(0))
        .join('')
        .toUpperCase();
}

function confirmStatusChange(supplier: SupplierSummary): boolean {
    return window.confirm(
        supplier.is_active
            ? `¿Desactivar a ${supplier.business_name}?`
            : `¿Activar a ${supplier.business_name}?`,
    );
}
</script>

<template>
    <Head title="Proveedores" />

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
                <div class="flex max-w-2xl items-start gap-4">
                    <span
                        class="bg-racing-yellow text-racing-black flex size-12 shrink-0 items-center justify-center rounded-xl"
                    >
                        <Truck class="size-6" />
                    </span>
                    <div>
                        <p
                            class="text-racing-yellow text-xs font-bold tracking-[0.2em] uppercase"
                        >
                            Fredy Racing
                        </p>
                        <h1
                            class="mt-1 text-2xl font-black tracking-tight sm:text-3xl"
                        >
                            Proveedores
                        </h1>
                        <p class="mt-2 text-sm leading-6 text-zinc-300">
                            Gestiona contactos, documentos y el catálogo
                            disponible para abastecer el taller.
                        </p>
                    </div>
                </div>

                <Button
                    v-if="canManage"
                    as-child
                    class="bg-racing-yellow text-racing-black hover:bg-racing-yellow/90"
                >
                    <Link :href="create()">
                        <Plus class="size-4" />
                        Nuevo proveedor
                    </Link>
                </Button>
            </div>
        </section>

        <section class="grid gap-3 sm:grid-cols-3">
            <article class="bg-card rounded-2xl border p-4 shadow-sm">
                <Building2 class="text-racing-yellow size-5" />
                <p class="mt-3 text-2xl font-black">{{ stats.total }}</p>
                <p class="text-muted-foreground text-sm">
                    Proveedores registrados
                </p>
            </article>
            <article class="bg-card rounded-2xl border p-4 shadow-sm">
                <CheckCircle2 class="text-racing-green size-5" />
                <p class="mt-3 text-2xl font-black">{{ stats.active }}</p>
                <p class="text-muted-foreground text-sm">Activos</p>
            </article>
            <article class="bg-card rounded-2xl border p-4 shadow-sm">
                <CircleOff class="text-racing-red size-5" />
                <p class="mt-3 text-2xl font-black">{{ stats.inactive }}</p>
                <p class="text-muted-foreground text-sm">Inactivos</p>
            </article>
        </section>

        <form
            class="bg-card grid gap-3 rounded-2xl border p-4 shadow-sm md:grid-cols-[1fr_12rem_auto]"
            @submit.prevent="applyFilters"
        >
            <div class="relative">
                <Search
                    class="text-muted-foreground absolute top-1/2 left-3 size-4 -translate-y-1/2"
                />
                <Input
                    v-model="searchTerm"
                    class="pl-9"
                    placeholder="Buscar por nombre, RUC, contacto o producto…"
                    aria-label="Buscar proveedores"
                />
            </div>
            <select
                v-model="statusFilter"
                class="border-input bg-background focus-visible:border-ring focus-visible:ring-ring/50 h-9 rounded-md border px-3 text-sm shadow-xs outline-none focus-visible:ring-3"
                aria-label="Filtrar por estado"
            >
                <option value="">Todos los estados</option>
                <option value="active">Activos</option>
                <option value="inactive">Inactivos</option>
            </select>
            <div class="flex gap-2">
                <Button type="submit" class="flex-1 md:flex-none"
                    >Buscar</Button
                >
                <Button
                    v-if="filters.search || filters.status"
                    type="button"
                    variant="ghost"
                    @click="clearFilters"
                >
                    Limpiar
                </Button>
            </div>
        </form>

        <section
            v-if="suppliers.data.length"
            class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3 2xl:grid-cols-4"
        >
            <article
                v-for="supplier in suppliers.data"
                :key="supplier.id"
                class="group bg-card hover:border-racing-yellow/60 flex min-w-0 flex-col overflow-hidden rounded-2xl border shadow-sm transition hover:shadow-md"
            >
                <div class="bg-racing-yellow h-1.5" />
                <div class="flex flex-1 flex-col gap-4 p-5">
                    <div class="flex items-start justify-between gap-3">
                        <div class="flex min-w-0 items-center gap-3">
                            <img
                                v-if="supplier.logo_url"
                                :src="supplier.logo_url"
                                :alt="`Logo de ${supplier.business_name}`"
                                class="size-12 shrink-0 rounded-xl border object-cover"
                            />
                            <span
                                v-else
                                class="bg-racing-black text-racing-yellow flex size-12 shrink-0 items-center justify-center rounded-xl text-sm font-black"
                                aria-hidden="true"
                            >
                                {{ supplierInitials(supplier) }}
                            </span>
                            <div class="min-w-0">
                                <h2 class="truncate font-black">
                                    {{ supplier.business_name }}
                                </h2>
                                <p
                                    class="text-muted-foreground truncate text-xs"
                                >
                                    RUC {{ supplier.tax_id }}
                                </p>
                            </div>
                        </div>
                        <span
                            class="shrink-0 rounded-full px-2 py-1 text-[11px] font-bold"
                            :class="
                                supplier.is_active
                                    ? 'bg-racing-green/10 text-racing-green'
                                    : 'bg-racing-red/10 text-racing-red'
                            "
                        >
                            {{ supplier.is_active ? 'Activo' : 'Inactivo' }}
                        </span>
                    </div>

                    <dl class="grid gap-2 text-sm">
                        <div class="flex justify-between gap-3">
                            <dt class="text-muted-foreground">Contacto</dt>
                            <dd class="truncate text-right font-medium">
                                {{ supplier.contact_name || 'Sin registrar' }}
                            </dd>
                        </div>
                        <div class="flex justify-between gap-3">
                            <dt class="text-muted-foreground">Teléfono</dt>
                            <dd class="truncate text-right font-medium">
                                {{ supplier.phone || 'Sin registrar' }}
                            </dd>
                        </div>
                        <div class="flex justify-between gap-3">
                            <dt class="text-muted-foreground">Productos</dt>
                            <dd class="font-bold">
                                {{ supplier.products_count }}
                            </dd>
                        </div>
                    </dl>

                    <div class="mt-auto flex flex-wrap gap-2 border-t pt-4">
                        <Button as-child size="sm" class="flex-1">
                            <Link :href="show(supplier)">Ver detalles</Link>
                        </Button>
                        <Button
                            v-if="canManage"
                            as-child
                            size="icon-sm"
                            variant="outline"
                        >
                            <Link
                                :href="edit(supplier)"
                                :aria-label="`Editar ${supplier.business_name}`"
                            >
                                <Pencil class="size-4" />
                            </Link>
                        </Button>
                        <Button
                            v-if="canManage"
                            as-child
                            size="sm"
                            variant="ghost"
                        >
                            <Link
                                :href="updateStatus(supplier)"
                                method="patch"
                                as="button"
                                :data="{ is_active: !supplier.is_active }"
                                preserve-scroll
                                :on-before="() => confirmStatusChange(supplier)"
                            >
                                {{
                                    supplier.is_active
                                        ? 'Desactivar'
                                        : 'Activar'
                                }}
                            </Link>
                        </Button>
                    </div>
                </div>
            </article>
        </section>

        <section
            v-else
            class="bg-card rounded-2xl border border-dashed p-8 text-center sm:p-12"
        >
            <PackageSearch class="text-muted-foreground mx-auto size-10" />
            <h2 class="mt-4 text-lg font-black">No encontramos proveedores</h2>
            <p class="text-muted-foreground mx-auto mt-1 max-w-md text-sm">
                Ajusta los filtros o registra el primer proveedor de Fredy
                Racing.
            </p>
            <Button
                v-if="canManage"
                as-child
                class="bg-racing-yellow text-racing-black hover:bg-racing-yellow/90 mt-5"
            >
                <Link :href="create()"
                    ><Plus class="size-4" /> Nuevo proveedor</Link
                >
            </Button>
        </section>

        <nav
            v-if="suppliers.last_page > 1"
            class="bg-card flex flex-col gap-3 rounded-xl border p-3 text-sm sm:flex-row sm:items-center sm:justify-between"
            aria-label="Paginación de proveedores"
        >
            <p class="text-muted-foreground">
                Mostrando {{ suppliers.from }}–{{ suppliers.to }} de
                {{ suppliers.total }}
            </p>
            <div class="flex items-center justify-between gap-2 sm:justify-end">
                <Button
                    as-child
                    size="sm"
                    variant="outline"
                    :disabled="!suppliers.prev_page_url"
                >
                    <Link
                        v-if="suppliers.prev_page_url"
                        :href="suppliers.prev_page_url"
                        preserve-scroll
                    >
                        <ChevronLeft class="size-4" /> Anterior
                    </Link>
                    <span v-else><ChevronLeft class="size-4" /> Anterior</span>
                </Button>
                <span class="px-2 font-bold"
                    >{{ suppliers.current_page }} /
                    {{ suppliers.last_page }}</span
                >
                <Button
                    as-child
                    size="sm"
                    variant="outline"
                    :disabled="!suppliers.next_page_url"
                >
                    <Link
                        v-if="suppliers.next_page_url"
                        :href="suppliers.next_page_url"
                        preserve-scroll
                    >
                        Siguiente <ChevronRight class="size-4" />
                    </Link>
                    <span v-else
                        >Siguiente <ChevronRight class="size-4"
                    /></span>
                </Button>
            </div>
        </nav>
    </div>
</template>
