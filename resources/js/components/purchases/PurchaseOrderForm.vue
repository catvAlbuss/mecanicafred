<script setup lang="ts">
import { Link, useForm } from '@inertiajs/vue3';
import { FileUp, LoaderCircle, Plus, Save, Trash2 } from '@lucide/vue';
import { computed } from 'vue';
import { toast } from 'vue-sonner';
import BarcodeScanner from '@/components/BarcodeScanner.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useProductLookup } from '@/composables/useProductLookup';
import type { OrderDetail, OrderSupplier } from '@/types';

const props = defineProps<{
    action: string;
    cancelHref: string;
    submitLabel: string;
    suppliers: OrderSupplier[];
    order?: OrderDetail;
    preselectedSupplierId?: number | null;
    preselectedProductId?: number | null;
}>();
const initialSupplier =
    props.order?.supplier_id ?? props.preselectedSupplierId ?? '';
const initialItems =
    props.order?.items.map((item) => ({
        product_id: String(item.product_id),
        quantity_ordered: item.quantity_ordered,
        unit_cost: item.unit_cost,
    })) ??
    (props.preselectedProductId
        ? [
              {
                  product_id: String(props.preselectedProductId),
                  quantity_ordered: '1.000',
                  unit_cost: '',
              },
          ]
        : [{ product_id: '', quantity_ordered: '1.000', unit_cost: '' }]);
const form = useForm({
    supplier_id: String(initialSupplier),
    expected_at: props.order?.expected_at ?? '',
    tax_rate: props.order?.tax_rate ?? '18.00',
    notes: props.order?.notes ?? '',
    items: initialItems,
    attachments: [] as File[],
});
const { findByCode } = useProductLookup();
const products = computed(
    () =>
        props.suppliers.find(
            (supplier) => String(supplier.id) === form.supplier_id,
        )?.products ?? [],
);
async function onScan(code: string): Promise<void> {
    if (!form.supplier_id) {
        toast.error('Elige primero un proveedor.');
        return;
    }
    try {
        const scanned = await findByCode(code);
        const inCatalog = products.value.find((item) => item.id === scanned.id);
        if (!inCatalog) {
            toast.error(
                `${scanned.name} no está en el catálogo de este proveedor.`,
            );
            return;
        }
        const existing = form.items.find(
            (item) => Number(item.product_id) === scanned.id,
        );
        if (existing) {
            existing.quantity_ordered = (
                (Number(existing.quantity_ordered) || 0) + 1
            ).toFixed(3);
            toast.success(
                `${scanned.name}: cantidad ${existing.quantity_ordered}.`,
            );
            return;
        }
        const blank = form.items.findIndex((item) => item.product_id === '');
        const line = {
            product_id: String(scanned.id),
            quantity_ordered: '1.000',
            unit_cost:
                inCatalog.last_unit_cost ?? scanned.last_purchase_cost ?? '',
        };
        if (blank === -1) {
            form.items.push(line);
        } else {
            form.items[blank] = line;
        }
        toast.success(`${scanned.name} agregado al pedido.`);
    } catch (error) {
        toast.error(
            error instanceof Error ? error.message : 'Código no válido.',
        );
    }
}
const subtotal = computed(() =>
    form.items.reduce(
        (sum, item) =>
            sum +
            (Number(item.quantity_ordered) || 0) *
                (Number(item.unit_cost) || 0),
        0,
    ),
);
const tax = computed(
    () => (subtotal.value * (Number(form.tax_rate) || 0)) / 100,
);
const money = (value: number) =>
    new Intl.NumberFormat('es-PE', {
        style: 'currency',
        currency: 'PEN',
    }).format(value);
function changeSupplier(): void {
    form.items = [{ product_id: '', quantity_ordered: '1.000', unit_cost: '' }];
}
function changeProduct(index: number): void {
    const product = products.value.find(
        (item) => String(item.id) === form.items[index].product_id,
    );
    if (product?.last_unit_cost)
        form.items[index].unit_cost = product.last_unit_cost;
}
function isSelected(productId: number, currentIndex: number): boolean {
    return form.items.some(
        (item, index) =>
            index !== currentIndex && Number(item.product_id) === productId,
    );
}
function addItem(): void {
    form.items.push({
        product_id: '',
        quantity_ordered: '1.000',
        unit_cost: '',
    });
}
function submit(): void {
    if (props.order) form.patch(props.action, { forceFormData: true });
    else form.post(props.action, { forceFormData: true });
}
</script>

<template>
    <form class="grid gap-6" @submit.prevent="submit">
        <section class="bg-card rounded-2xl border p-5 sm:p-6">
            <div>
                <h2 class="font-black">Datos del pedido</h2>
                <p class="text-muted-foreground text-sm">
                    Los totales definitivos se recalculan de forma segura al
                    guardar.
                </p>
            </div>
            <div class="mt-5 grid gap-5 md:grid-cols-3">
                <div class="grid gap-2 md:col-span-2">
                    <Label for="order-supplier">Proveedor</Label
                    ><select
                        id="order-supplier"
                        v-model="form.supplier_id"
                        required
                        class="h-10 rounded-md border px-3 text-sm"
                        @change="changeSupplier"
                    >
                        <option value="">Selecciona un proveedor</option>
                        <option
                            v-for="supplier in suppliers"
                            :key="supplier.id"
                            :value="String(supplier.id)"
                        >
                            {{ supplier.name }}
                        </option></select
                    ><InputError :message="form.errors.supplier_id" />
                </div>
                <div class="grid gap-2">
                    <Label for="expected-at">Entrega esperada</Label
                    ><Input
                        id="expected-at"
                        v-model="form.expected_at"
                        type="date"
                    /><InputError :message="form.errors.expected_at" />
                </div>
                <div class="grid gap-2">
                    <Label for="tax-rate">Impuesto (%)</Label
                    ><Input
                        id="tax-rate"
                        v-model="form.tax_rate"
                        type="number"
                        min="0"
                        max="100"
                        step="0.01"
                        required
                    /><InputError :message="form.errors.tax_rate" />
                </div>
                <div class="grid gap-2 md:col-span-2">
                    <Label for="order-notes">Notas para el proveedor</Label
                    ><textarea
                        id="order-notes"
                        v-model="form.notes"
                        rows="3"
                        maxlength="2000"
                        class="rounded-md border px-3 py-2 text-sm"
                        placeholder="Condiciones, dirección o indicaciones de entrega"
                    /><InputError :message="form.errors.notes" />
                </div>
            </div>
        </section>

        <section class="bg-card rounded-2xl border p-5 sm:p-6">
            <div
                class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between"
            >
                <div>
                    <h2 class="font-black">Productos</h2>
                    <p class="text-muted-foreground text-sm">
                        Solo se muestran productos asociados al catálogo del
                        proveedor.
                    </p>
                </div>
                <div class="flex flex-wrap gap-2">
                    <BarcodeScanner
                        trigger-label="Escanear"
                        trigger-size="sm"
                        description="Cada escaneo agrega el producto o suma una unidad."
                        @detected="onScan"
                    />
                    <Button
                        type="button"
                        size="sm"
                        variant="outline"
                        :disabled="!form.supplier_id || form.items.length >= 50"
                        @click="addItem"
                        ><Plus />Agregar producto</Button
                    >
                </div>
            </div>
            <InputError class="mt-3" :message="form.errors.items" />
            <div class="mt-5 grid gap-3">
                <article
                    v-for="(item, index) in form.items"
                    :key="index"
                    class="grid gap-3 rounded-xl border p-4 lg:grid-cols-[minmax(12rem,1fr)_9rem_10rem_auto]"
                >
                    <div class="grid gap-2">
                        <Label :for="`product-${index}`"
                            >Producto {{ index + 1 }}</Label
                        ><select
                            :id="`product-${index}`"
                            v-model="item.product_id"
                            required
                            class="h-10 min-w-0 rounded-md border px-3 text-sm"
                            @change="changeProduct(index)"
                        >
                            <option value="">Selecciona un producto</option>
                            <option
                                v-for="product in products"
                                :key="product.id"
                                :value="String(product.id)"
                                :disabled="isSelected(product.id, index)"
                            >
                                {{ product.sku }} · {{ product.name }}
                            </option></select
                        ><InputError
                            :message="form.errors[`items.${index}.product_id`]"
                        />
                    </div>
                    <div class="grid gap-2">
                        <Label :for="`quantity-${index}`">Cantidad</Label
                        ><Input
                            :id="`quantity-${index}`"
                            v-model="item.quantity_ordered"
                            type="number"
                            min="0.001"
                            step="0.001"
                            required
                        /><InputError
                            :message="
                                form.errors[`items.${index}.quantity_ordered`]
                            "
                        />
                    </div>
                    <div class="grid gap-2">
                        <Label :for="`cost-${index}`">Costo unitario</Label
                        ><Input
                            :id="`cost-${index}`"
                            v-model="item.unit_cost"
                            type="number"
                            min="0"
                            step="0.0001"
                            required
                        /><InputError
                            :message="form.errors[`items.${index}.unit_cost`]"
                        />
                    </div>
                    <Button
                        type="button"
                        size="icon"
                        variant="ghost"
                        class="text-racing-red self-end"
                        :aria-label="`Quitar producto ${index + 1}`"
                        :disabled="form.items.length === 1"
                        @click="form.items.splice(index, 1)"
                        ><Trash2
                    /></Button>
                </article>
            </div>
        </section>

        <div class="grid gap-6 lg:grid-cols-[1fr_20rem]">
            <section class="bg-card rounded-2xl border p-5">
                <Label
                    for="order-attachments"
                    class="flex items-center gap-2 font-black"
                    ><FileUp />Adjuntos</Label
                >
                <p class="text-muted-foreground mt-1 text-sm">
                    PDF o imagen, máximo 5 archivos de 5 MB.
                </p>
                <Input
                    id="order-attachments"
                    type="file"
                    multiple
                    accept="application/pdf,image/jpeg,image/png,image/webp"
                    class="mt-4"
                    @change="
                        form.attachments = Array.from(
                            ($event.target as HTMLInputElement).files ?? [],
                        )
                    "
                /><InputError
                    :message="
                        form.errors.attachments ?? form.errors['attachments.0']
                    "
                />
            </section>
            <aside class="bg-racing-black rounded-2xl p-5 text-white">
                <h2 class="font-black">Resumen estimado</h2>
                <dl class="mt-4 grid gap-3 text-sm">
                    <div class="flex justify-between">
                        <dt class="text-zinc-400">Subtotal</dt>
                        <dd>{{ money(subtotal) }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-zinc-400">
                            Impuesto ({{ Number(form.tax_rate) || 0 }}%)
                        </dt>
                        <dd>{{ money(tax) }}</dd>
                    </div>
                    <div
                        class="border-racing-yellow/30 flex justify-between border-t pt-3 text-lg font-black"
                    >
                        <dt>Total</dt>
                        <dd class="text-racing-yellow">
                            {{ money(subtotal + tax) }}
                        </dd>
                    </div>
                </dl>
            </aside>
        </div>
        <div class="flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
            <Button as-child variant="outline"
                ><Link :href="cancelHref">Cancelar</Link></Button
            ><Button
                type="submit"
                :disabled="form.processing"
                class="bg-racing-yellow text-racing-black"
                ><LoaderCircle
                    v-if="form.processing"
                    class="animate-spin"
                /><Save v-else />{{
                    form.processing ? 'Guardando…' : submitLabel
                }}</Button
            >
        </div>
    </form>
</template>
