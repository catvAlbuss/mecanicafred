<script setup lang="ts">
import { Form, Link } from '@inertiajs/vue3';
import { Building2, FileUp, ImageUp, LoaderCircle, Save } from '@lucide/vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import type { SupplierFormData } from '@/types';

const props = defineProps<{
    action: string;
    cancelHref: string;
    submitLabel: string;
    supplier?: SupplierFormData;
}>();
</script>

<template>
    <Form :action="action" method="post" enctype="multipart/form-data" class="grid gap-6"
        #default="{ errors, processing }">
        <input v-if="props.supplier" type="hidden" name="_method" value="patch" />

        <section class="bg-card rounded-2xl border p-5 shadow-sm sm:p-6">
            <div class="flex items-start gap-3">
                <span
                    class="bg-racing-yellow/15 text-racing-yellow flex size-10 shrink-0 items-center justify-center rounded-xl">
                    <Building2 class="size-5" />
                </span>
                <div>
                    <h2 class="font-black">Información comercial</h2>
                    <p class="text-muted-foreground text-sm">
                        Datos fiscales y nombre del proveedor.
                    </p>
                </div>
            </div>

            <div class="mt-6 grid gap-5 md:grid-cols-2">
                <div class="grid gap-2">
                    <Label for="tax_id">RUC o documento fiscal</Label>
                    <Input id="tax_id" name="tax_id" inputmode="numeric" autocomplete="off"
                        :default-value="props.supplier?.tax_id ?? ''" required maxlength="20" />
                    <InputError :message="errors.tax_id" />
                </div>

                <div class="grid gap-2">
                    <Label for="business_name">Razón social</Label>
                    <Input id="business_name" name="business_name" :default-value="props.supplier?.business_name ?? ''"
                        required maxlength="255" />
                    <InputError :message="errors.business_name" />
                </div>

                <div class="grid gap-2">
                    <Label for="trade_name">Nombre comercial</Label>
                    <Input id="trade_name" name="trade_name" :default-value="props.supplier?.trade_name ?? ''"
                        maxlength="255" />
                    <InputError :message="errors.trade_name" />
                </div>

                <label class="flex items-center gap-3 self-end rounded-xl border px-4 py-3">
                    <input type="hidden" name="is_active" value="0" />
                    <input type="checkbox" name="is_active" value="1" :checked="props.supplier?.is_active ?? true"
                        class="border-input accent-racing-green size-4 rounded" />
                    <span>
                        <span class="block text-sm font-bold">Proveedor activo</span>
                        <span class="text-muted-foreground block text-xs">Disponible para consultas y pedidos.</span>
                    </span>
                </label>
            </div>
        </section>

        <section class="bg-card rounded-2xl border p-5 shadow-sm sm:p-6">
            <h2 class="font-black">Contacto</h2>
            <p class="text-muted-foreground text-sm">
                Persona y canales para coordinar compras.
            </p>

            <div class="mt-6 grid gap-5 md:grid-cols-2">
                <div class="grid gap-2">
                    <Label for="contact_name">Persona de contacto</Label>
                    <Input id="contact_name" name="contact_name" :default-value="props.supplier?.contact_name ?? ''"
                        autocomplete="name" />
                    <InputError :message="errors.contact_name" />
                </div>

                <div class="grid gap-2">
                    <Label for="email">Correo electrónico</Label>
                    <Input id="email" name="email" type="email" :default-value="props.supplier?.email ?? ''"
                        autocomplete="email" />
                    <InputError :message="errors.email" />
                </div>

                <div class="grid gap-2">
                    <Label for="phone">Teléfono principal</Label>
                    <Input id="phone" name="phone" type="tel" :default-value="props.supplier?.phone ?? ''"
                        autocomplete="tel" />
                    <InputError :message="errors.phone" />
                </div>

                <div class="grid gap-2">
                    <Label for="secondary_phone">Teléfono secundario</Label>
                    <Input id="secondary_phone" name="secondary_phone" type="tel"
                        :default-value="props.supplier?.secondary_phone ?? ''" />
                    <InputError :message="errors.secondary_phone" />
                </div>
            </div>
        </section>

        <section class="bg-card rounded-2xl border p-5 shadow-sm sm:p-6">
            <h2 class="font-black">Ubicación y notas</h2>
            <div class="mt-6 grid gap-5 md:grid-cols-2">
                <div class="grid gap-2 md:col-span-2">
                    <Label for="address">Dirección</Label>
                    <Input id="address" name="address" :default-value="props.supplier?.address ?? ''"
                        autocomplete="street-address" />
                    <InputError :message="errors.address" />
                </div>

                <div class="grid gap-2">
                    <Label for="district">Distrito</Label>
                    <Input id="district" name="district" :default-value="props.supplier?.district ?? ''" />
                    <InputError :message="errors.district" />
                </div>

                <div class="grid gap-2">
                    <Label for="province">Provincia</Label>
                    <Input id="province" name="province" :default-value="props.supplier?.province ?? ''" />
                    <InputError :message="errors.province" />
                </div>

                <div class="grid gap-2 md:col-span-2">
                    <Label for="notes">Notas</Label>
                    <textarea id="notes" name="notes" rows="4" maxlength="2000"
                        class="border-input focus-visible:border-ring focus-visible:ring-ring/50 dark:bg-input/30 w-full rounded-md border bg-transparent px-3 py-2 text-sm shadow-xs transition outline-none focus-visible:ring-3"
                        :value="props.supplier?.notes ?? ''" />
                    <InputError :message="errors.notes" />
                </div>
            </div>
        </section>

        <section class="bg-card rounded-2xl border p-5 shadow-sm sm:p-6">
            <h2 class="font-black">Logo y documentos</h2>
            <p class="text-muted-foreground text-sm">
                Imágenes hasta 2 MB y adjuntos PDF o imagen hasta 5 MB.
            </p>

            <div class="mt-6 grid gap-5 md:grid-cols-2">
                <div class="grid gap-2">
                    <Label for="logo" class="flex items-center gap-2">
                        <ImageUp class="size-4" /> Logo
                    </Label>
                    <Input id="logo" name="logo" type="file" accept="image/jpeg,image/png,image/webp" />
                    <InputError :message="errors.logo" />
                </div>

                <div class="grid gap-2">
                    <Label for="attachments" class="flex items-center gap-2">
                        <FileUp class="size-4" /> Adjuntos
                    </Label>
                    <Input id="attachments" name="attachments[]" type="file" multiple
                        accept="application/pdf,image/jpeg,image/png,image/webp" />
                    <InputError :message="errors.attachments ?? errors['attachments.0']" />
                </div>
            </div>
        </section>

        <div class="flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
            <Button as-child variant="outline">
                <Link :href="cancelHref">Cancelar</Link>
            </Button>
            <Button type="submit" :disabled="processing"
                class="bg-racing-yellow text-racing-black hover:bg-racing-yellow/90">
                <LoaderCircle v-if="processing" class="size-4 animate-spin" />
                <Save v-else class="size-4" />
                {{ processing ? 'Guardando…' : submitLabel }}
            </Button>
        </div>
    </Form>
</template>
