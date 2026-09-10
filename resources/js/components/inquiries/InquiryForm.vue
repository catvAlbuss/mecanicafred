<script setup lang="ts">
import { Link, useForm } from '@inertiajs/vue3';
import { FileUp, LoaderCircle, Plus, Save, Trash2 } from '@lucide/vue';
import { computed } from 'vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import type { InquiryDetail, InquirySupplier } from '@/types';
const props = defineProps<{
    action: string;
    cancelHref: string;
    submitLabel: string;
    suppliers: InquirySupplier[];
    inquiry?: InquiryDetail;
    preselectedSupplierId?: number | null;
    preselectedProductId?: number | null;
}>();
const initialSupplier =
    props.inquiry?.supplier_id ?? props.preselectedSupplierId ?? '';
const initialItems =
    props.inquiry?.items.map((item) => ({
        product_id: String(item.product_id),
        quantity_requested: item.quantity_requested,
    })) ??
    (props.preselectedProductId
        ? [
              {
                  product_id: String(props.preselectedProductId),
                  quantity_requested: '1.000',
              },
          ]
        : [{ product_id: '', quantity_requested: '1.000' }]);
const form = useForm({
    supplier_id: String(initialSupplier),
    valid_until: props.inquiry?.valid_until ?? '',
    notes: props.inquiry?.notes ?? '',
    items: initialItems,
    attachments: [] as File[],
});
const products = computed(
    () =>
        props.suppliers.find(
            (supplier) => String(supplier.id) === form.supplier_id,
        )?.products ?? [],
);
function changeSupplier(): void {
    form.items = [{ product_id: '', quantity_requested: '1.000' }];
}
function addItem(): void {
    form.items.push({ product_id: '', quantity_requested: '1.000' });
}
function submit(): void {
    if (props.inquiry) form.patch(props.action, { forceFormData: true });
    else form.post(props.action, { forceFormData: true });
}
</script>
<template>
    <form class="grid gap-6" @submit.prevent="submit">
        <section class="bg-card rounded-2xl border p-5 sm:p-6">
            <h2 class="font-black">Datos de la consulta</h2>
            <div class="mt-5 grid gap-5 md:grid-cols-2">
                <div class="grid gap-2">
                    <Label>Proveedor</Label
                    ><select
                        v-model="form.supplier_id"
                        required
                        class="border-input h-9 rounded-md border bg-transparent px-3 text-sm"
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
                    <Label>Válida hasta</Label
                    ><Input v-model="form.valid_until" type="date" /><InputError
                        :message="form.errors.valid_until"
                    />
                </div>
                <div class="grid gap-2 md:col-span-2">
                    <Label>Notas</Label
                    ><textarea
                        v-model="form.notes"
                        rows="3"
                        maxlength="2000"
                        class="border-input rounded-md border bg-transparent px-3 py-2 text-sm"
                    />
                </div>
            </div>
        </section>
        <section class="bg-card rounded-2xl border p-5 sm:p-6">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="font-black">Productos solicitados</h2>
                    <p class="text-muted-foreground text-sm">
                        Solo aparecen productos del catálogo del proveedor.
                    </p>
                </div>
                <Button
                    type="button"
                    size="sm"
                    variant="outline"
                    @click="addItem"
                    ><Plus />Agregar</Button
                >
            </div>
            <div class="mt-5 grid gap-3">
                <div
                    v-for="(item, index) in form.items"
                    :key="index"
                    class="grid gap-3 rounded-xl border p-4 sm:grid-cols-[1fr_10rem_auto]"
                >
                    <div class="grid gap-2">
                        <Label>Producto</Label
                        ><select
                            v-model="item.product_id"
                            required
                            class="border-input h-9 rounded-md border bg-transparent px-3 text-sm"
                        >
                            <option value="">Selecciona</option>
                            <option
                                v-for="product in products"
                                :key="product.id"
                                :value="String(product.id)"
                            >
                                {{ product.sku }} · {{ product.name }}
                            </option></select
                        ><InputError
                            :message="form.errors[`items.${index}.product_id`]"
                        />
                    </div>
                    <div class="grid gap-2">
                        <Label>Cantidad</Label
                        ><Input
                            v-model="item.quantity_requested"
                            type="number"
                            min="0.001"
                            step="0.001"
                            required
                        /><InputError
                            :message="
                                form.errors[`items.${index}.quantity_requested`]
                            "
                        />
                    </div>
                    <Button
                        type="button"
                        size="icon"
                        variant="ghost"
                        class="text-racing-red self-end"
                        :disabled="form.items.length === 1"
                        @click="form.items.splice(index, 1)"
                        ><Trash2
                    /></Button>
                </div>
            </div>
        </section>
        <section class="bg-card rounded-2xl border p-5 sm:p-6">
            <Label class="flex items-center gap-2 font-black"
                ><FileUp />Adjuntos</Label
            ><Input
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
                /><Save v-else />{{ submitLabel }}</Button
            >
        </div>
    </form>
</template>
