<script setup lang="ts">
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { ArrowLeft, PackagePlus, Pencil, Trash2, Truck, X } from '@lucide/vue';
import { ref } from 'vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { destroy, index, store, update } from '@/routes/suppliers/catalog';
import { show } from '@/routes/suppliers';
import type { Option } from '@/types';
type CatalogItem = {
    id: number;
    sku: string;
    name: string;
    category: string;
    supplier_sku: string | null;
    last_unit_cost: string | null;
    lead_time_days: number | null;
    availability_status: string;
    available_quantity: string | null;
    last_checked_at: string | null;
    is_preferred: boolean;
};
const props = defineProps<{
    supplier: { id: number; name: string };
    catalog: CatalogItem[];
    availableProducts: { id: number; sku: string; name: string }[];
    availabilityOptions: Option[];
    canManage: boolean;
}>();
defineOptions({
    layout: { breadcrumbs: [{ title: 'Proveedores', href: '/proveedores' }] },
});
const editing = ref<CatalogItem | null>(null);
const form = useForm({
    product_id: '',
    supplier_sku: '',
    last_unit_cost: '',
    lead_time_days: '',
    availability_status: 'unknown',
    available_quantity: '',
    is_preferred: false,
});
function startEdit(item: CatalogItem): void {
    editing.value = item;
    form.product_id = String(item.id);
    form.supplier_sku = item.supplier_sku ?? '';
    form.last_unit_cost = item.last_unit_cost ?? '';
    form.lead_time_days =
        item.lead_time_days === null ? '' : String(item.lead_time_days);
    form.availability_status = item.availability_status;
    form.available_quantity = item.available_quantity ?? '';
    form.is_preferred = item.is_preferred;
}
function reset(): void {
    editing.value = null;
    form.reset();
    form.clearErrors();
}
function submit(): void {
    const options = { preserveScroll: true, onSuccess: reset };
    if (editing.value)
        form.patch(
            update.url({
                supplier: props.supplier.id,
                product: editing.value.id,
            }),
            options,
        );
    else form.post(store.url(props.supplier.id), options);
}
function remove(item: CatalogItem): void {
    if (confirm(`¿Retirar ${item.name} del catálogo?`))
        router.delete(
            destroy.url({ supplier: props.supplier.id, product: item.id }),
            { preserveScroll: true },
        );
}
const statusLabel = (value: string) =>
    props.availabilityOptions.find((option) => option.value === value)?.label ??
    value;
</script>
<template>
    <Head :title="`Catálogo · ${supplier.name}`" />
    <div class="flex flex-1 flex-col gap-6 p-4 sm:p-6 lg:p-8">
        <header class="flex items-start gap-4">
            <Button as-child variant="outline" size="icon"
                ><Link :href="show(supplier.id)"><ArrowLeft /></Link></Button
            ><span
                class="bg-racing-yellow text-racing-black flex size-11 items-center justify-center rounded-xl"
                ><Truck
            /></span>
            <div>
                <p
                    class="text-racing-yellow text-xs font-bold tracking-[.2em] uppercase"
                >
                    Proveedor
                </p>
                <h1 class="text-2xl font-black sm:text-3xl">
                    Catálogo de {{ supplier.name }}
                </h1>
                <p class="text-muted-foreground text-sm">
                    Disponibilidad, costos y tiempos de entrega.
                </p>
            </div>
        </header>
        <div class="grid gap-6 xl:grid-cols-[22rem_minmax(0,1fr)]">
            <form
                v-if="canManage"
                class="bg-card h-fit rounded-2xl border p-5 xl:sticky xl:top-6"
                @submit.prevent="submit"
            >
                <div class="flex justify-between">
                    <h2 class="font-black">
                        {{ editing ? 'Editar oferta' : 'Asociar producto' }}
                    </h2>
                    <Button
                        v-if="editing"
                        type="button"
                        size="icon"
                        variant="ghost"
                        @click="reset"
                        ><X
                    /></Button>
                </div>
                <div class="mt-5 grid gap-4">
                    <div class="grid gap-2">
                        <Label>Producto</Label
                        ><select
                            v-model="form.product_id"
                            required
                            :disabled="!!editing"
                            class="border-input h-9 rounded-md border bg-transparent px-3 text-sm"
                        >
                            <option value="">Selecciona</option>
                            <option v-if="editing" :value="String(editing.id)">
                                {{ editing.sku }} · {{ editing.name }}
                            </option>
                            <option
                                v-for="product in availableProducts"
                                :key="product.id"
                                :value="String(product.id)"
                            >
                                {{ product.sku }} · {{ product.name }}
                            </option></select
                        ><InputError :message="form.errors.product_id" />
                    </div>
                    <div class="grid gap-2">
                        <Label>SKU del proveedor</Label
                        ><Input v-model="form.supplier_sku" />
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div class="grid gap-2">
                            <Label>Costo S/</Label
                            ><Input
                                v-model="form.last_unit_cost"
                                type="number"
                                min="0"
                                step="0.0001"
                            />
                        </div>
                        <div class="grid gap-2">
                            <Label>Plazo (días)</Label
                            ><Input
                                v-model="form.lead_time_days"
                                type="number"
                                min="0"
                            />
                        </div>
                    </div>
                    <div class="grid gap-2">
                        <Label>Disponibilidad</Label
                        ><select
                            v-model="form.availability_status"
                            class="border-input h-9 rounded-md border bg-transparent px-3 text-sm"
                        >
                            <option
                                v-for="option in availabilityOptions"
                                :key="option.value"
                                :value="option.value"
                            >
                                {{ option.label }}
                            </option>
                        </select>
                    </div>
                    <div class="grid gap-2">
                        <Label>Cantidad disponible</Label
                        ><Input
                            v-model="form.available_quantity"
                            type="number"
                            min="0"
                            step="0.001"
                        />
                    </div>
                    <label class="flex items-center gap-2 text-sm font-bold"
                        ><input
                            v-model="form.is_preferred"
                            type="checkbox"
                            class="accent-racing-green size-4"
                        />Proveedor preferido</label
                    ><Button
                        type="submit"
                        :disabled="form.processing"
                        class="bg-racing-yellow text-racing-black"
                        ><Pencil v-if="editing" /><PackagePlus v-else />{{
                            editing ? 'Guardar' : 'Asociar'
                        }}</Button
                    >
                </div>
            </form>
            <section class="grid gap-3 md:grid-cols-2">
                <article
                    v-for="item in catalog"
                    :key="item.id"
                    class="bg-card rounded-2xl border p-5"
                >
                    <div class="flex justify-between gap-3">
                        <div>
                            <p class="font-black">{{ item.name }}</p>
                            <p class="text-muted-foreground text-xs">
                                {{ item.sku }} · {{ item.category }}
                            </p>
                        </div>
                        <span
                            class="bg-racing-green/15 text-racing-green h-fit rounded-full px-2 py-1 text-xs font-bold"
                            >{{ statusLabel(item.availability_status) }}</span
                        >
                    </div>
                    <dl class="mt-5 grid grid-cols-2 gap-3 text-sm">
                        <div>
                            <dt class="text-muted-foreground">Costo</dt>
                            <dd class="font-bold">
                                {{
                                    item.last_unit_cost
                                        ? `S/ ${item.last_unit_cost}`
                                        : 'Sin precio'
                                }}
                            </dd>
                        </div>
                        <div>
                            <dt class="text-muted-foreground">Disponible</dt>
                            <dd class="font-bold">
                                {{ item.available_quantity ?? 'Sin consultar' }}
                            </dd>
                        </div>
                        <div>
                            <dt class="text-muted-foreground">Entrega</dt>
                            <dd class="font-bold">
                                {{
                                    item.lead_time_days === null
                                        ? 'Sin plazo'
                                        : `${item.lead_time_days} días`
                                }}
                            </dd>
                        </div>
                        <div>
                            <dt class="text-muted-foreground">
                                Código proveedor
                            </dt>
                            <dd class="font-bold">
                                {{ item.supplier_sku || '—' }}
                            </dd>
                        </div>
                    </dl>
                    <div v-if="canManage" class="mt-4 flex gap-2 border-t pt-4">
                        <Button
                            size="sm"
                            variant="outline"
                            @click="startEdit(item)"
                            ><Pencil />Editar</Button
                        ><Button
                            size="icon"
                            variant="ghost"
                            class="text-racing-red ml-auto"
                            @click="remove(item)"
                            ><Trash2
                        /></Button>
                    </div>
                </article>
                <div
                    v-if="!catalog.length"
                    class="text-muted-foreground rounded-2xl border border-dashed p-10 text-center md:col-span-2"
                >
                    Este proveedor todavía no tiene productos asociados.
                </div>
            </section>
        </div>
    </div>
</template>
