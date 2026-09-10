<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3';
import {
    Building2,
    CheckCircle2,
    CircleOff,
    ClipboardList,
    FileText,
    History,
    Mail,
    MapPin,
    PackageSearch,
    Pencil,
    Phone,
    Trash2,
} from '@lucide/vue';
import { Button } from '@/components/ui/button';
import { create as createInquiry } from '@/routes/purchases/inquiries';
import { index as purchaseHistory } from '@/routes/purchases/history';
import { index as purchaseOrders } from '@/routes/purchases/orders';
import { index as catalogIndex } from '@/routes/suppliers/catalog';
import { destroy, edit, index } from '@/routes/suppliers';
import { update as updateStatus } from '@/routes/suppliers/status';
import type { SupplierDetail } from '@/types';

const props = defineProps<{
    supplier: SupplierDetail;
    canManage: boolean;
}>();
const permissions = usePage().props.auth.user?.permissions ?? [];
const canViewPurchaseHistory = permissions.includes('historial-compras.ver');
const canViewOrders = permissions.includes('pedidos-compra.ver');

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Proveedores', href: index() }],
    },
});

const availabilityLabels: Record<string, string> = {
    unknown: 'Sin consultar',
    available: 'Disponible',
    limited: 'Limitado',
    unavailable: 'No disponible',
};

function formatFileSize(bytes: number): string {
    return bytes < 1024 * 1024
        ? `${Math.ceil(bytes / 1024)} KB`
        : `${(bytes / 1024 / 1024).toFixed(1)} MB`;
}

function confirmStatusChange(): boolean {
    return window.confirm(
        props.supplier.is_active
            ? `¿Desactivar a ${props.supplier.business_name}?`
            : `¿Activar a ${props.supplier.business_name}?`,
    );
}

function confirmDeletion(): boolean {
    return window.confirm(
        props.supplier.products?.length
            ? 'Este proveedor tiene productos asociados y será desactivado en lugar de eliminarse. ¿Continuar?'
            : '¿Eliminar este proveedor? Esta acción no se puede deshacer.',
    );
}
</script>

<template>
    <Head :title="supplier.business_name" />

    <div class="flex flex-1 flex-col gap-6 p-4 sm:p-6 lg:p-8">
        <section
            class="bg-racing-black relative overflow-hidden rounded-2xl px-5 py-7 text-white sm:px-8"
        >
            <div
                class="bg-racing-yellow/20 absolute -top-20 -right-16 size-56 rounded-full blur-3xl"
            />
            <div
                class="relative flex flex-col gap-5 lg:flex-row lg:items-center lg:justify-between"
            >
                <div class="flex min-w-0 items-start gap-4">
                    <img
                        v-if="supplier.logo_url"
                        :src="supplier.logo_url"
                        :alt="`Logo de ${supplier.business_name}`"
                        class="size-16 shrink-0 rounded-2xl border border-white/20 bg-white object-cover"
                    />
                    <span
                        v-else
                        class="bg-racing-yellow text-racing-black flex size-16 shrink-0 items-center justify-center rounded-2xl"
                    >
                        <Building2 class="size-8" />
                    </span>
                    <div class="min-w-0">
                        <span
                            class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-bold"
                            :class="
                                supplier.is_active
                                    ? 'bg-racing-green/20 text-green-300'
                                    : 'bg-racing-red/20 text-red-300'
                            "
                        >
                            <CheckCircle2
                                v-if="supplier.is_active"
                                class="size-3.5"
                            />
                            <CircleOff v-else class="size-3.5" />
                            {{ supplier.is_active ? 'Activo' : 'Inactivo' }}
                        </span>
                        <h1
                            class="mt-2 truncate text-2xl font-black tracking-tight sm:text-3xl"
                        >
                            {{ supplier.business_name }}
                        </h1>
                        <p class="mt-1 text-sm text-zinc-300">
                            RUC {{ supplier.tax_id
                            }}<span v-if="supplier.trade_name">
                                · {{ supplier.trade_name }}</span
                            >
                        </p>
                    </div>
                </div>

                <div v-if="canManage" class="flex flex-wrap gap-2">
                    <Button
                        as-child
                        class="bg-racing-yellow text-racing-black hover:bg-racing-yellow/90"
                    >
                        <Link :href="catalogIndex(supplier)">
                            <PackageSearch class="size-4" /> Catálogo
                        </Link>
                    </Button>
                    <Button
                        as-child
                        variant="outline"
                        class="border-white/20 bg-white/5 text-white hover:bg-white/10 hover:text-white"
                    >
                        <Link
                            :href="
                                createInquiry({
                                    query: { supplier: supplier.id },
                                })
                            "
                        >
                            <ClipboardList class="size-4" /> Nueva consulta
                        </Link>
                    </Button>
                    <Button
                        as-child
                        variant="outline"
                        class="border-white/20 bg-white/5 text-white hover:bg-white/10 hover:text-white"
                    >
                        <Link :href="edit(supplier)"
                            ><Pencil class="size-4" /> Editar</Link
                        >
                    </Button>
                    <Button
                        as-child
                        variant="outline"
                        class="border-white/20 bg-white/5 text-white hover:bg-white/10 hover:text-white"
                    >
                        <Link
                            :href="updateStatus(supplier)"
                            method="patch"
                            as="button"
                            :data="{ is_active: !supplier.is_active }"
                            :on-before="confirmStatusChange"
                        >
                            {{ supplier.is_active ? 'Desactivar' : 'Activar' }}
                        </Link>
                    </Button>
                    <Button as-child variant="destructive">
                        <Link
                            :href="destroy(supplier)"
                            method="delete"
                            as="button"
                            :on-before="confirmDeletion"
                        >
                            <Trash2 class="size-4" /> Eliminar
                        </Link>
                    </Button>
                </div>
            </div>
        </section>

        <section class="grid gap-6 xl:grid-cols-[0.75fr_1.25fr]">
            <div class="grid content-start gap-6">
                <article
                    class="bg-card rounded-2xl border p-5 shadow-sm sm:p-6"
                >
                    <h2 class="font-black">Datos de contacto</h2>
                    <dl class="mt-5 grid gap-4 text-sm">
                        <div class="flex gap-3">
                            <Building2
                                class="text-racing-yellow mt-0.5 size-4 shrink-0"
                            />
                            <div>
                                <dt class="text-muted-foreground">Contacto</dt>
                                <dd class="font-medium">
                                    {{
                                        supplier.contact_name || 'Sin registrar'
                                    }}
                                </dd>
                            </div>
                        </div>
                        <div class="flex gap-3">
                            <Phone
                                class="text-racing-yellow mt-0.5 size-4 shrink-0"
                            />
                            <div>
                                <dt class="text-muted-foreground">Teléfonos</dt>
                                <dd class="font-medium">
                                    {{
                                        [
                                            supplier.phone,
                                            supplier.secondary_phone,
                                        ]
                                            .filter(Boolean)
                                            .join(' · ') || 'Sin registrar'
                                    }}
                                </dd>
                            </div>
                        </div>
                        <div class="flex gap-3">
                            <Mail
                                class="text-racing-yellow mt-0.5 size-4 shrink-0"
                            />
                            <div class="min-w-0">
                                <dt class="text-muted-foreground">Correo</dt>
                                <dd class="truncate font-medium">
                                    {{ supplier.email || 'Sin registrar' }}
                                </dd>
                            </div>
                        </div>
                        <div class="flex gap-3">
                            <MapPin
                                class="text-racing-yellow mt-0.5 size-4 shrink-0"
                            />
                            <div>
                                <dt class="text-muted-foreground">Ubicación</dt>
                                <dd class="font-medium">
                                    {{
                                        [
                                            supplier.address,
                                            supplier.district,
                                            supplier.province,
                                        ]
                                            .filter(Boolean)
                                            .join(', ') || 'Sin registrar'
                                    }}
                                </dd>
                            </div>
                        </div>
                    </dl>
                    <div
                        v-if="supplier.notes"
                        class="bg-muted mt-5 rounded-xl p-4 text-sm leading-6"
                    >
                        {{ supplier.notes }}
                    </div>
                </article>

                <article
                    class="bg-card rounded-2xl border p-5 shadow-sm sm:p-6"
                >
                    <h2 class="font-black">Documentos</h2>
                    <div
                        v-if="supplier.attachments.length"
                        class="mt-4 grid gap-2"
                    >
                        <a
                            v-for="attachment in supplier.attachments"
                            :key="attachment.id"
                            :href="attachment.url"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="hover:border-racing-yellow/60 flex items-center gap-3 rounded-xl border p-3 transition"
                        >
                            <FileText
                                class="text-racing-yellow size-5 shrink-0"
                            />
                            <span class="min-w-0"
                                ><span
                                    class="block truncate text-sm font-bold"
                                    >{{ attachment.file_name }}</span
                                ><span
                                    class="text-muted-foreground block text-xs"
                                    >{{ formatFileSize(attachment.size) }}</span
                                ></span
                            >
                        </a>
                    </div>
                    <p v-else class="text-muted-foreground mt-3 text-sm">
                        No hay documentos adjuntos.
                    </p>
                </article>
            </div>

            <div class="grid content-start gap-6">
                <article
                    class="bg-card rounded-2xl border p-5 shadow-sm sm:p-6"
                >
                    <div class="flex items-center justify-between gap-3">
                        <div>
                            <h2 class="font-black">Catálogo ofrecido</h2>
                            <p class="text-muted-foreground text-sm">
                                Productos asociados al proveedor.
                            </p>
                        </div>
                        <PackageSearch class="text-racing-yellow size-5" />
                    </div>

                    <div
                        v-if="supplier.products?.length"
                        class="mt-5 grid gap-3"
                    >
                        <article
                            v-for="product in supplier.products"
                            :key="product.id"
                            class="grid gap-2 rounded-xl border p-4 sm:grid-cols-[1fr_auto] sm:items-center"
                        >
                            <div class="min-w-0">
                                <h3 class="truncate font-bold">
                                    {{ product.name }}
                                </h3>
                                <p class="text-muted-foreground text-xs">
                                    {{ product.sku }} · {{ product.category }}
                                </p>
                            </div>
                            <span
                                class="bg-muted w-fit rounded-full px-2.5 py-1 text-xs font-bold"
                            >
                                {{
                                    availabilityLabels[
                                        product.availability_status ?? 'unknown'
                                    ] ?? 'Sin consultar'
                                }}
                            </span>
                        </article>
                    </div>
                    <div
                        v-else
                        class="mt-5 rounded-xl border border-dashed p-6 text-center"
                    >
                        <PackageSearch
                            class="text-muted-foreground mx-auto size-8"
                        />
                        <p class="mt-2 text-sm font-bold">
                            Sin productos asociados
                        </p>
                        <p class="text-muted-foreground text-xs">
                            La asociación se habilitará en la Fase 4.
                        </p>
                    </div>
                </article>

                <section class="grid gap-4 sm:grid-cols-2">
                    <article class="bg-card rounded-2xl border p-5">
                        <ClipboardList class="text-racing-yellow size-5" />
                        <h2 class="mt-3 font-black">Consultas y pedidos</h2>
                        <p class="text-muted-foreground mt-1 text-sm">
                            Revisa las órdenes activas de este proveedor.
                        </p>
                        <Button
                            v-if="canViewOrders"
                            as-child
                            size="sm"
                            variant="outline"
                            class="mt-4"
                            ><Link
                                :href="
                                    purchaseOrders({
                                        query: { supplier: supplier.id },
                                    })
                                "
                                >Ver pedidos</Link
                            ></Button
                        >
                    </article>
                    <article class="bg-card rounded-2xl border p-5">
                        <History class="text-racing-green size-5" />
                        <h2 class="mt-3 font-black">Historial de compras</h2>
                        <p class="text-muted-foreground mt-1 text-sm">
                            Pedidos recibidos y cancelados con este proveedor.
                        </p>
                        <Button
                            v-if="canViewPurchaseHistory"
                            as-child
                            size="sm"
                            variant="outline"
                            class="mt-4"
                            ><Link
                                :href="
                                    purchaseHistory({
                                        query: { supplier: supplier.id },
                                    })
                                "
                                >Ver historial</Link
                            ></Button
                        >
                    </article>
                </section>
            </div>
        </section>
    </div>
</template>
