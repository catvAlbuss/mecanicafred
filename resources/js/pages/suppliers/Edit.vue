<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { FileText, ImageIcon, Pencil, Trash2 } from '@lucide/vue';
import SupplierForm from '@/components/suppliers/SupplierForm.vue';
import { Button } from '@/components/ui/button';
import { edit, index, show, update } from '@/routes/suppliers';
import { destroy as destroyMedia } from '@/routes/suppliers/media';
import type { SupplierDetail } from '@/types';

const props = defineProps<{
    supplier: SupplierDetail;
}>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Proveedores', href: index() }],
    },
});

function formatFileSize(bytes: number): string {
    return bytes < 1024 * 1024
        ? `${Math.ceil(bytes / 1024)} KB`
        : `${(bytes / 1024 / 1024).toFixed(1)} MB`;
}

function confirmMediaRemoval(name: string): boolean {
    return window.confirm(
        `¿Eliminar ${name}? Esta acción no se puede deshacer.`,
    );
}
</script>

<template>
    <Head :title="`Editar ${supplier.business_name}`" />

    <div
        class="mx-auto flex w-full max-w-5xl flex-1 flex-col gap-6 p-4 sm:p-6 lg:p-8"
    >
        <header class="flex items-start gap-4">
            <span
                class="bg-racing-yellow text-racing-black flex size-12 shrink-0 items-center justify-center rounded-xl"
            >
                <Pencil class="size-5" />
            </span>
            <div class="min-w-0">
                <p
                    class="text-racing-yellow text-xs font-bold tracking-[0.2em] uppercase"
                >
                    Proveedores
                </p>
                <h1
                    class="truncate text-2xl font-black tracking-tight sm:text-3xl"
                >
                    Editar {{ supplier.business_name }}
                </h1>
                <p class="text-muted-foreground mt-1 text-sm">
                    Actualiza la ficha, el logo y los documentos.
                </p>
            </div>
        </header>

        <section
            v-if="supplier.logo_url || supplier.attachments.length"
            class="bg-card grid gap-4 rounded-2xl border p-5 shadow-sm sm:p-6"
        >
            <div
                v-if="supplier.logo_url"
                class="flex flex-col gap-4 rounded-xl border p-4 sm:flex-row sm:items-center sm:justify-between"
            >
                <div class="flex items-center gap-3">
                    <img
                        :src="supplier.logo_url"
                        :alt="`Logo de ${supplier.business_name}`"
                        class="size-14 rounded-xl border object-cover"
                    />
                    <div>
                        <p class="font-bold">Logo actual</p>
                        <p class="text-muted-foreground text-xs">
                            Sube otro logo para reemplazarlo.
                        </p>
                    </div>
                </div>
                <Button
                    v-if="supplier.logo_media_id"
                    as-child
                    size="sm"
                    variant="destructive"
                >
                    <Link
                        :href="
                            destroyMedia({
                                supplier,
                                media: supplier.logo_media_id,
                            })
                        "
                        method="delete"
                        as="button"
                        preserve-scroll
                        :on-before="() => confirmMediaRemoval('el logo')"
                    >
                        <Trash2 class="size-4" /> Eliminar logo
                    </Link>
                </Button>
            </div>

            <div v-if="supplier.attachments.length" class="grid gap-3">
                <h2 class="font-black">Documentos actuales</h2>
                <article
                    v-for="attachment in supplier.attachments"
                    :key="attachment.id"
                    class="flex flex-col gap-3 rounded-xl border p-4 sm:flex-row sm:items-center sm:justify-between"
                >
                    <div class="flex min-w-0 items-center gap-3">
                        <FileText class="text-racing-yellow size-5 shrink-0" />
                        <div class="min-w-0">
                            <a
                                :href="attachment.url"
                                target="_blank"
                                rel="noopener noreferrer"
                                class="block truncate font-bold hover:underline"
                            >
                                {{ attachment.file_name }}
                            </a>
                            <p class="text-muted-foreground text-xs">
                                {{ formatFileSize(attachment.size) }}
                            </p>
                        </div>
                    </div>
                    <Button as-child size="icon-sm" variant="destructive">
                        <Link
                            :href="
                                destroyMedia({ supplier, media: attachment })
                            "
                            method="delete"
                            as="button"
                            preserve-scroll
                            :aria-label="`Eliminar ${attachment.file_name}`"
                            :on-before="
                                () => confirmMediaRemoval(attachment.file_name)
                            "
                        >
                            <Trash2 class="size-4" />
                        </Link>
                    </Button>
                </article>
            </div>
        </section>

        <section
            v-else
            class="bg-card text-muted-foreground flex items-center gap-3 rounded-xl border border-dashed p-4 text-sm"
        >
            <ImageIcon class="size-5 shrink-0" />
            Este proveedor todavía no tiene logo ni documentos.
        </section>

        <SupplierForm
            :action="update.url(supplier)"
            :cancel-href="show.url(supplier)"
            submit-label="Guardar cambios"
            :supplier="props.supplier"
        />
    </div>
</template>
