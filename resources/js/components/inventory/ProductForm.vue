<script setup lang="ts">
import { Form, Link } from '@inertiajs/vue3';
import { ImageUp, LoaderCircle, Save } from '@lucide/vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import type { CategoryOption, Option, ProductDetail } from '@/types';

defineProps<{
    action: string;
    cancelHref: string;
    submitLabel: string;
    categories: CategoryOption[];
    units: Option[];
    product?: ProductDetail;
}>();
</script>

<template>
    <Form
        :action="action"
        method="post"
        enctype="multipart/form-data"
        class="grid gap-6"
        #default="{ errors, processing }"
    >
        <input v-if="product" type="hidden" name="_method" value="patch" />
        <section class="bg-card rounded-2xl border p-5 shadow-sm sm:p-6">
            <h2 class="font-black">Información del producto</h2>
            <p class="text-muted-foreground text-sm">
                Los campos de stock se gestionan mediante movimientos
                auditables.
            </p>
            <div class="mt-6 grid gap-5 md:grid-cols-2">
                <div class="grid gap-2">
                    <Label for="sku">Código SKU</Label
                    ><Input
                        id="sku"
                        name="sku"
                        :default-value="product?.sku ?? ''"
                        required
                        maxlength="50"
                    /><InputError :message="errors.sku" />
                </div>
                <div class="grid gap-2">
                    <Label for="name">Nombre</Label
                    ><Input
                        id="name"
                        name="name"
                        :default-value="product?.name ?? ''"
                        required
                        maxlength="150"
                    /><InputError :message="errors.name" />
                </div>
                <div class="grid gap-2">
                    <Label for="product_category_id">Categoría</Label
                    ><select
                        id="product_category_id"
                        name="product_category_id"
                        required
                        class="border-input h-9 rounded-md border bg-transparent px-3 text-sm"
                    >
                        <option value="">Selecciona una categoría</option>
                        <option
                            v-for="category in categories"
                            :key="category.id"
                            :value="category.id"
                            :selected="
                                category.id === product?.product_category_id
                            "
                        >
                            {{ category.name }}
                        </option></select
                    ><InputError :message="errors.product_category_id" />
                </div>
                <div class="grid gap-2">
                    <Label for="brand">Marca</Label
                    ><Input
                        id="brand"
                        name="brand"
                        :default-value="product?.brand ?? ''"
                        maxlength="100"
                    /><InputError :message="errors.brand" />
                </div>
                <div class="grid gap-2">
                    <Label for="unit">Unidad</Label
                    ><select
                        id="unit"
                        name="unit"
                        required
                        class="border-input h-9 rounded-md border bg-transparent px-3 text-sm"
                    >
                        <option
                            v-for="unit in units"
                            :key="unit.value"
                            :value="unit.value"
                            :selected="unit.value === (product?.unit ?? 'unit')"
                        >
                            {{ unit.label }}
                        </option></select
                    ><InputError :message="errors.unit" />
                </div>
                <div class="grid gap-2">
                    <Label for="minimum_stock">Stock mínimo</Label
                    ><Input
                        id="minimum_stock"
                        name="minimum_stock"
                        type="number"
                        min="0"
                        step="0.001"
                        :default-value="product?.minimum_stock ?? '0'"
                        required
                    /><InputError :message="errors.minimum_stock" />
                </div>
                <div v-if="!product" class="grid gap-2">
                    <Label for="initial_stock">Stock inicial</Label
                    ><Input
                        id="initial_stock"
                        name="initial_stock"
                        type="number"
                        min="0"
                        step="0.001"
                        default-value="0"
                        required
                    /><InputError :message="errors.initial_stock" />
                </div>
                <div class="grid gap-2">
                    <Label for="last_purchase_cost">Último costo (S/)</Label
                    ><Input
                        id="last_purchase_cost"
                        name="last_purchase_cost"
                        type="number"
                        min="0"
                        step="0.0001"
                        :default-value="product?.last_purchase_cost ?? ''"
                    /><InputError :message="errors.last_purchase_cost" />
                </div>
                <div class="grid gap-2">
                    <Label for="location">Ubicación</Label
                    ><Input
                        id="location"
                        name="location"
                        :default-value="product?.location ?? ''"
                        maxlength="100"
                    /><InputError :message="errors.location" />
                </div>
                <label
                    class="flex items-center gap-3 rounded-xl border px-4 py-3"
                    ><input type="hidden" name="is_active" value="0" /><input
                        type="checkbox"
                        name="is_active"
                        value="1"
                        :checked="product?.is_active ?? true"
                        class="accent-racing-green size-4"
                    /><span class="text-sm font-bold"
                        >Producto activo</span
                    ></label
                >
                <div class="grid gap-2 md:col-span-2">
                    <Label for="description">Descripción</Label
                    ><textarea
                        id="description"
                        name="description"
                        rows="4"
                        maxlength="2000"
                        class="border-input rounded-md border bg-transparent px-3 py-2 text-sm"
                        :value="product?.description ?? ''"
                    /><InputError :message="errors.description" />
                </div>
            </div>
        </section>
        <section class="bg-card rounded-2xl border p-5 shadow-sm sm:p-6">
            <Label for="images" class="flex items-center gap-2 font-black"
                ><ImageUp class="size-5" /> Galería</Label
            >
            <p class="text-muted-foreground mt-1 text-sm">
                Hasta 5 imágenes JPG, PNG o WebP de 5 MB.
            </p>
            <Input
                id="images"
                name="images[]"
                type="file"
                multiple
                accept="image/jpeg,image/png,image/webp"
                class="mt-4"
            /><InputError :message="errors.images ?? errors['images.0']" />
        </section>
        <div class="flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
            <Button as-child variant="outline"
                ><Link :href="cancelHref">Cancelar</Link></Button
            ><Button
                type="submit"
                :disabled="processing"
                class="bg-racing-yellow text-racing-black hover:bg-racing-yellow/90"
                ><LoaderCircle
                    v-if="processing"
                    class="size-4 animate-spin"
                /><Save v-else class="size-4" />{{ submitLabel }}</Button
            >
        </div>
    </Form>
</template>
