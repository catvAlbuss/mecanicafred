<script setup lang="ts">
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import {
    ArrowLeft,
    Check,
    ClipboardCheck,
    FileText,
    Pencil,
    Send,
    ShoppingCart,
    Trash2,
    X,
} from '@lucide/vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import InputError from '@/components/InputError.vue';
import { destroy, edit, index } from '@/routes/purchases/inquiries';
import { store as convert } from '@/routes/purchases/inquiries/convert';
import { destroy as destroyMedia } from '@/routes/purchases/inquiries/media';
import { update as respond } from '@/routes/purchases/inquiries/response';
import { update as changeStatus } from '@/routes/purchases/inquiries/status';
import { show as showProduct } from '@/routes/inventory/products';
import { show as showOrder } from '@/routes/purchases/orders';
import type { InquiryDetail } from '@/types';
const props = defineProps<{
    inquiry: InquiryDetail;
    canEdit: boolean;
    canTransition: boolean;
    canConvert: boolean;
}>();
defineOptions({
    layout: { breadcrumbs: [{ title: 'Consultas', href: index() }] },
});
const response = useForm({
    valid_until: props.inquiry.valid_until ?? '',
    items: props.inquiry.items.map((item) => ({
        id: item.id!,
        is_available: item.is_available ?? true,
        quantity_available: item.quantity_available ?? item.quantity_requested,
        quoted_unit_cost: item.quoted_unit_cost ?? '',
        supplier_notes: item.supplier_notes ?? '',
    })),
});
function transition(action: 'send' | 'close' | 'cancel'): void {
    const reason =
        action === 'cancel'
            ? (prompt('Motivo de cancelación (opcional)') ?? undefined)
            : undefined;
    router.patch(
        changeStatus.url(props.inquiry.id),
        { action, reason },
        { preserveScroll: true },
    );
}
function removeInquiry(): void {
    if (confirm('¿Eliminar este borrador?'))
        router.delete(destroy.url(props.inquiry.id));
}
function convertToOrder(): void {
    if (confirm('¿Crear un pedido borrador con los productos disponibles?'))
        router.post(
            convert.url(props.inquiry.id),
            {},
            { preserveScroll: true },
        );
}
function removeAttachment(id: number): void {
    if (confirm('¿Eliminar adjunto?'))
        router.delete(
            destroyMedia.url({ inquiry: props.inquiry.id, media: id }),
            { preserveScroll: true },
        );
}
function submitResponse(): void {
    response.put(respond.url(props.inquiry.id), { preserveScroll: true });
}
const money = (value: string | null) =>
    value === null ? '—' : `S/ ${Number(value).toFixed(2)}`;
</script>
<template>
    <Head :title="inquiry.number" />
    <div class="flex flex-1 flex-col gap-6 p-4 sm:p-6 lg:p-8">
        <header
            class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between"
        >
            <div class="flex items-start gap-4">
                <Button as-child size="icon" variant="outline"
                    ><Link :href="index()"><ArrowLeft /></Link></Button
                ><span
                    class="bg-racing-yellow text-racing-black flex size-11 items-center justify-center rounded-xl"
                    ><ClipboardCheck
                /></span>
                <div>
                    <p
                        class="text-racing-yellow text-xs font-bold tracking-[.2em] uppercase"
                    >
                        {{ inquiry.status_label }}
                    </p>
                    <h1 class="text-2xl font-black sm:text-3xl">
                        {{ inquiry.number }}
                    </h1>
                    <p class="text-muted-foreground text-sm">
                        {{ inquiry.supplier_name }} · por
                        {{ inquiry.requester_name }}
                    </p>
                </div>
            </div>
            <div class="flex flex-wrap gap-2">
                <Button v-if="canEdit" as-child variant="outline"
                    ><Link :href="edit(inquiry.id)"
                        ><Pencil />Editar</Link
                    ></Button
                ><Button
                    v-if="inquiry.status === 'draft' && canTransition"
                    class="bg-racing-yellow text-racing-black"
                    @click="transition('send')"
                    ><Send />Enviar</Button
                ><Button
                    v-if="
                        inquiry.status === 'answered' &&
                        canConvert &&
                        !inquiry.purchase_order
                    "
                    class="bg-racing-green text-white"
                    @click="convertToOrder"
                    ><ShoppingCart />Crear pedido</Button
                ><Button
                    v-if="inquiry.status === 'answered' && canTransition"
                    variant="outline"
                    @click="transition('close')"
                    ><Check />Cerrar</Button
                ><Button
                    v-if="
                        ['draft', 'sent', 'answered'].includes(
                            inquiry.status,
                        ) && canTransition
                    "
                    variant="outline"
                    @click="transition('cancel')"
                    ><X />Cancelar</Button
                ><Button
                    v-if="canEdit"
                    size="icon"
                    variant="destructive"
                    @click="removeInquiry"
                    ><Trash2
                /></Button>
            </div>
        </header>
        <Link
            v-if="inquiry.purchase_order"
            :href="showOrder(inquiry.purchase_order.id)"
            class="bg-racing-green/10 text-racing-green border-racing-green/30 rounded-xl border p-4 font-bold"
        >
            Pedido borrador {{ inquiry.purchase_order.number }} creado · ver
            detalle
        </Link>
        <div class="grid gap-6 xl:grid-cols-[minmax(0,1fr)_20rem]">
            <main class="grid gap-6">
                <section class="bg-card overflow-hidden rounded-2xl border">
                    <div class="p-5">
                        <h2 class="font-black">Productos consultados</h2>
                        <p
                            v-if="inquiry.notes"
                            class="text-muted-foreground mt-1 text-sm"
                        >
                            {{ inquiry.notes }}
                        </p>
                    </div>
                    <div class="divide-y">
                        <article
                            v-for="item in inquiry.items"
                            :key="item.id"
                            class="grid gap-3 p-5 sm:grid-cols-[1fr_auto]"
                        >
                            <div>
                                <Link
                                    :href="showProduct(Number(item.product_id))"
                                    class="hover:text-racing-yellow font-black"
                                    >{{ item.product_name }}</Link
                                >
                                <p class="text-muted-foreground text-xs">
                                    {{ item.product_sku }} · solicitado
                                    {{ item.quantity_requested }}
                                    {{ item.unit_label }}
                                </p>
                            </div>
                            <div
                                v-if="
                                    inquiry.status === 'answered' ||
                                    inquiry.status === 'closed'
                                "
                                class="sm:text-right"
                            >
                                <p
                                    :class="[
                                        'font-bold',
                                        item.is_available
                                            ? 'text-racing-green'
                                            : 'text-racing-red',
                                    ]"
                                >
                                    {{
                                        item.is_available
                                            ? 'Disponible'
                                            : 'No disponible'
                                    }}
                                </p>
                                <p v-if="item.is_available" class="text-sm">
                                    {{ item.quantity_available }} ·
                                    {{ money(item.quoted_unit_cost ?? null) }}
                                </p>
                            </div>
                        </article>
                    </div>
                </section>
                <form
                    v-if="inquiry.status === 'sent' && canTransition"
                    class="bg-card rounded-2xl border p-5"
                    @submit.prevent="submitResponse"
                >
                    <h2 class="font-black">Registrar respuesta</h2>
                    <div class="mt-4 grid gap-3">
                        <div
                            v-for="(item, i) in response.items"
                            :key="item.id"
                            class="grid gap-3 rounded-xl border p-4 md:grid-cols-[1fr_8rem_10rem]"
                        >
                            <label class="flex items-center gap-2 font-bold"
                                ><input
                                    v-model="item.is_available"
                                    type="checkbox"
                                    class="accent-racing-green size-4"
                                />{{ inquiry.items[i].product_name }}</label
                            >
                            <div class="grid gap-1">
                                <Label>Cantidad</Label
                                ><Input
                                    v-model="item.quantity_available"
                                    type="number"
                                    min="0"
                                    step="0.001"
                                    :disabled="!item.is_available"
                                /><InputError
                                    :message="
                                        response.errors[
                                            `items.${i}.quantity_available`
                                        ]
                                    "
                                />
                            </div>
                            <div class="grid gap-1">
                                <Label>Costo unitario</Label
                                ><Input
                                    v-model="item.quoted_unit_cost"
                                    type="number"
                                    min="0"
                                    step="0.0001"
                                    :disabled="!item.is_available"
                                /><InputError
                                    :message="
                                        response.errors[
                                            `items.${i}.quoted_unit_cost`
                                        ]
                                    "
                                />
                            </div>
                            <Input
                                v-model="item.supplier_notes"
                                class="md:col-span-3"
                                placeholder="Nota del proveedor"
                            />
                        </div>
                        <div class="grid gap-2 sm:max-w-xs">
                            <Label>Válida hasta</Label
                            ><Input
                                v-model="response.valid_until"
                                type="date"
                            />
                        </div>
                        <Button
                            :disabled="response.processing"
                            class="bg-racing-yellow text-racing-black"
                            ><Check />Guardar respuesta</Button
                        >
                    </div>
                </form>
            </main>
            <aside class="grid h-fit gap-4">
                <section class="bg-racing-black rounded-2xl p-5 text-white">
                    <h2 class="font-black">Fechas</h2>
                    <dl class="mt-4 grid gap-3 text-sm">
                        <div>
                            <dt class="text-zinc-400">Enviada</dt>
                            <dd>
                                {{
                                    inquiry.requested_at
                                        ? new Date(
                                              inquiry.requested_at,
                                          ).toLocaleString('es-PE')
                                        : 'Aún no enviada'
                                }}
                            </dd>
                        </div>
                        <div>
                            <dt class="text-zinc-400">Respondida</dt>
                            <dd>
                                {{
                                    inquiry.responded_at
                                        ? new Date(
                                              inquiry.responded_at,
                                          ).toLocaleString('es-PE')
                                        : 'Pendiente'
                                }}
                            </dd>
                        </div>
                        <div>
                            <dt class="text-zinc-400">Cotización válida</dt>
                            <dd>{{ inquiry.valid_until || 'Sin fecha' }}</dd>
                        </div>
                    </dl>
                </section>
                <section class="bg-card rounded-2xl border p-5">
                    <h2 class="flex items-center gap-2 font-black">
                        <FileText />Adjuntos
                    </h2>
                    <div
                        v-if="inquiry.attachments.length"
                        class="mt-3 grid gap-2"
                    >
                        <div
                            v-for="file in inquiry.attachments"
                            :key="file.id"
                            class="flex items-center gap-2 rounded-lg border p-2"
                        >
                            <a
                                :href="file.url"
                                target="_blank"
                                class="min-w-0 flex-1 truncate text-sm font-bold"
                                >{{ file.file_name }}</a
                            ><Button
                                v-if="canEdit"
                                size="icon"
                                variant="ghost"
                                @click="removeAttachment(file.id)"
                                ><Trash2
                            /></Button>
                        </div>
                    </div>
                    <p v-else class="text-muted-foreground mt-2 text-sm">
                        Sin adjuntos.
                    </p>
                </section>
            </aside>
        </div>
    </div>
</template>
