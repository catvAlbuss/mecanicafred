<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { FileUp, LoaderCircle, PackageCheck } from '@lucide/vue';
import { computed } from 'vue';
import { toast } from 'vue-sonner';
import BarcodeScanner from '@/components/BarcodeScanner.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useProductLookup } from '@/composables/useProductLookup';
import type { OrderDetail } from '@/types';

const props = defineProps<{
    order: OrderDetail;
    action: string;
    idempotencyKey: string;
}>();
const pending = (ordered: string, received = '0') =>
    Math.max(0, Number(ordered) - Number(received));
const localDateTime = new Date(
    Date.now() - new Date().getTimezoneOffset() * 60_000,
)
    .toISOString()
    .slice(0, 16);
const form = useForm({
    idempotency_key: props.idempotencyKey,
    received_at: localDateTime,
    supplier_document_number: '',
    notes: '',
    items: props.order.items.map((item) => ({
        purchase_order_item_id: item.id!,
        quantity_received: '0',
        unit_cost: item.unit_cost,
    })),
    attachments: [] as File[],
});
const { findByCode } = useProductLookup();
const unitsToReceive = computed(() =>
    form.items.reduce(
        (sum, item) => sum + (Number(item.quantity_received) || 0),
        0,
    ),
);
const errorFor = (key: string): string | undefined =>
    (form.errors as Record<string, string | undefined>)[key];
const priceChanged = (index: number): boolean => {
    const current = Number(form.items[index].unit_cost);
    const ordered = Number(props.order.items[index].unit_cost);
    return Number.isFinite(current) && current > 0 && current !== ordered;
};
function completePending(): void {
    form.items.forEach((item, index) => {
        item.quantity_received = pending(
            props.order.items[index].quantity_ordered,
            props.order.items[index].quantity_received,
        ).toFixed(3);
    });
}
async function onScan(code: string): Promise<void> {
    try {
        const product = await findByCode(code);
        const index = props.order.items.findIndex(
            (item) => Number(item.product_id) === product.id,
        );
        if (index === -1) {
            toast.error(`${product.name} no está en este pedido.`);
            return;
        }
        const max = pending(
            props.order.items[index].quantity_ordered,
            props.order.items[index].quantity_received,
        );
        const next = Math.min(
            max,
            (Number(form.items[index].quantity_received) || 0) + 1,
        );
        if (next === Number(form.items[index].quantity_received)) {
            toast.info(`${product.name} ya alcanzó la cantidad pendiente.`);
            return;
        }
        form.items[index].quantity_received = next.toFixed(3);
        toast.success(
            `${product.name}: ${next.toFixed(0)} de ${max.toFixed(0)} ${props.order.items[index].unit_label}.`,
        );
    } catch (error) {
        toast.error(
            error instanceof Error ? error.message : 'Código no válido.',
        );
    }
}
function submit(): void {
    form.post(props.action, { forceFormData: true, preserveScroll: true });
}
</script>

<template>
    <form
        class="bg-card rounded-2xl border p-5 sm:p-6"
        @submit.prevent="submit"
    >
        <div
            class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between"
        >
            <div>
                <h2 class="flex items-center gap-2 font-black">
                    <PackageCheck class="text-racing-green" />Registrar
                    recepción
                </h2>
                <p class="text-muted-foreground mt-1 text-sm">
                    El stock aumentará únicamente por las cantidades que
                    confirmes aquí.
                </p>
            </div>
            <div class="flex flex-wrap gap-2">
                <BarcodeScanner
                    trigger-label="Escanear"
                    trigger-size="sm"
                    description="Cada escaneo suma una unidad a la línea del producto."
                    @detected="onScan"
                />
                <Button
                    type="button"
                    variant="outline"
                    size="sm"
                    @click="completePending"
                    >Completar pendientes</Button
                >
            </div>
        </div>
        <InputError
            class="mt-3"
            :message="
                errorFor('order') ??
                errorFor('items') ??
                form.errors.idempotency_key
            "
        />
        <div class="mt-5 grid gap-3">
            <article
                v-for="(item, index) in order.items"
                :key="item.id"
                class="grid gap-3 rounded-xl border p-4 sm:grid-cols-[1fr_9rem_9rem]"
            >
                <div>
                    <p class="font-black">{{ item.product_name }}</p>
                    <p class="text-muted-foreground text-xs">
                        {{ item.product_sku }} · pedido
                        {{ item.quantity_ordered }} · recibido
                        {{ item.quantity_received || '0.000' }} · pendiente
                        {{
                            pending(
                                item.quantity_ordered,
                                item.quantity_received,
                            ).toFixed(3)
                        }}
                        {{ item.unit_label }}
                    </p>
                </div>
                <div class="grid gap-2">
                    <Label :for="`received-${item.id}`">Recibido ahora</Label
                    ><Input
                        :id="`received-${item.id}`"
                        v-model="form.items[index].quantity_received"
                        type="number"
                        min="0"
                        :max="
                            pending(
                                item.quantity_ordered,
                                item.quantity_received,
                            )
                        "
                        step="0.001"
                    /><InputError
                        :message="errorFor(`items.${index}.quantity_received`)"
                    />
                </div>
                <div class="grid gap-2">
                    <Label :for="`cost-${item.id}`">Costo recibido</Label
                    ><Input
                        :id="`cost-${item.id}`"
                        v-model="form.items[index].unit_cost"
                        type="number"
                        min="0"
                        step="0.0001"
                    />
                    <p class="text-muted-foreground text-xs">
                        Pedido: S/ {{ item.unit_cost }}
                        <span
                            v-if="priceChanged(index)"
                            class="text-racing-red font-bold"
                            >· precio cambió</span
                        >
                    </p>
                    <InputError
                        :message="errorFor(`items.${index}.unit_cost`)"
                    />
                </div>
            </article>
        </div>
        <div class="mt-5 grid gap-5 md:grid-cols-2">
            <div class="grid gap-2">
                <Label for="received-at">Fecha y hora de recepción</Label
                ><Input
                    id="received-at"
                    v-model="form.received_at"
                    type="datetime-local"
                    required
                /><InputError :message="form.errors.received_at" />
            </div>
            <div class="grid gap-2">
                <Label for="document-number">Factura o guía</Label
                ><Input
                    id="document-number"
                    v-model="form.supplier_document_number"
                    maxlength="100"
                    placeholder="Ej. F001-458 o G-1024"
                /><InputError :message="form.errors.supplier_document_number" />
            </div>
            <div class="grid gap-2 md:col-span-2">
                <Label for="receipt-notes">Observaciones</Label
                ><textarea
                    id="receipt-notes"
                    v-model="form.notes"
                    rows="2"
                    maxlength="2000"
                    class="rounded-md border px-3 py-2 text-sm"
                    placeholder="Estado de los productos o diferencias encontradas"
                /><InputError :message="form.errors.notes" />
            </div>
            <div class="grid gap-2 md:col-span-2">
                <Label for="receipt-files" class="flex items-center gap-2"
                    ><FileUp />Evidencia</Label
                ><Input
                    id="receipt-files"
                    type="file"
                    multiple
                    accept="application/pdf,image/jpeg,image/png,image/webp"
                    @change="
                        form.attachments = Array.from(
                            ($event.target as HTMLInputElement).files ?? [],
                        )
                    "
                />
                <p class="text-muted-foreground text-xs">
                    Hasta 5 archivos PDF o imagen, máximo 5 MB cada uno.
                </p>
                <InputError
                    :message="
                        form.errors.attachments ?? form.errors['attachments.0']
                    "
                />
            </div>
        </div>
        <div
            class="mt-5 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between"
        >
            <p class="text-muted-foreground text-sm">
                <strong class="text-foreground">{{
                    unitsToReceive.toFixed(3)
                }}</strong>
                unidades seleccionadas
            </p>
            <Button
                type="submit"
                :disabled="form.processing || unitsToReceive <= 0"
                class="bg-racing-green text-white"
                ><LoaderCircle
                    v-if="form.processing"
                    class="animate-spin"
                /><PackageCheck v-else />{{
                    form.processing ? 'Registrando…' : 'Confirmar recepción'
                }}</Button
            >
        </div>
    </form>
</template>
