<script setup lang="ts">
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import {
    ArrowLeft,
    Check,
    FileText,
    History,
    Pencil,
    Send,
    ShoppingCart,
    Trash2,
    X,
} from '@lucide/vue';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import InputError from '@/components/InputError.vue';
import PurchaseReceiptForm from '@/components/purchases/PurchaseReceiptForm.vue';
import { show as showInquiry } from '@/routes/purchases/inquiries';
import { destroy, edit, index } from '@/routes/purchases/orders';
import { destroy as destroyMedia } from '@/routes/purchases/orders/media';
import { update as changeStatus } from '@/routes/purchases/orders/status';
import { store as storeReceipt } from '@/routes/purchases/orders/receipts';
import { show as showProduct } from '@/routes/inventory/products';
import type { OrderDetail } from '@/types';
const props = defineProps<{
    order: OrderDetail;
    canEdit: boolean;
    canDelete: boolean;
    canSend: boolean;
    canConfirm: boolean;
    canCancel: boolean;
    canReceive: boolean;
    receiptKey: string;
}>();
defineOptions({
    layout: { breadcrumbs: [{ title: 'Pedidos', href: index() }] },
});
const cancellation = useForm({ action: 'cancel', reason: '' });
function transition(action: 'send' | 'confirm'): void {
    const message =
        action === 'send'
            ? '¿Enviar este pedido al proveedor? Ya no podrá editarse.'
            : '¿Confirmar la aceptación de este pedido?';
    if (confirm(message))
        router.patch(
            changeStatus.url(props.order.id),
            { action },
            { preserveScroll: true },
        );
}
function cancelOrder(): void {
    cancellation.patch(changeStatus.url(props.order.id), {
        preserveScroll: true,
    });
}
function removeOrder(): void {
    if (confirm('¿Eliminar definitivamente este borrador?'))
        router.delete(destroy.url(props.order.id));
}
function removeAttachment(id: number): void {
    if (confirm('¿Eliminar este adjunto?'))
        router.delete(
            destroyMedia.url({ purchaseOrder: props.order.id, media: id }),
            { preserveScroll: true },
        );
}
const money = (value: string) =>
    new Intl.NumberFormat('es-PE', {
        style: 'currency',
        currency: 'PEN',
    }).format(Number(value));
const colors: Record<string, string> = {
    draft: 'bg-muted text-muted-foreground',
    sent: 'bg-blue-500/15 text-blue-600 dark:text-blue-400',
    confirmed: 'bg-racing-green/15 text-racing-green',
    partially_received:
        'bg-racing-yellow/20 text-amber-700 dark:text-racing-yellow',
    received: 'bg-racing-green text-white',
    cancelled: 'bg-racing-red/15 text-racing-red',
};
</script>
<template>
    <Head :title="order.number" />
    <div class="flex flex-1 flex-col gap-6 p-4 sm:p-6 lg:p-8">
        <header
            class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between"
        >
            <div class="flex items-start gap-4">
                <Button as-child size="icon" variant="outline"
                    ><Link :href="index()"><ArrowLeft /></Link></Button
                ><span
                    class="bg-racing-yellow text-racing-black flex size-11 shrink-0 items-center justify-center rounded-xl"
                    ><ShoppingCart
                /></span>
                <div>
                    <div class="flex flex-wrap items-center gap-2">
                        <h1 class="text-2xl font-black sm:text-3xl">
                            {{ order.number }}
                        </h1>
                        <span
                            :class="[
                                'rounded-full px-2 py-1 text-xs font-bold',
                                colors[order.status],
                            ]"
                            >{{ order.status_label }}</span
                        >
                    </div>
                    <p class="text-muted-foreground text-sm">
                        {{ order.supplier_name }} · creado por
                        {{ order.creator_name }}
                    </p>
                </div>
            </div>
            <div class="flex flex-wrap gap-2">
                <Button v-if="canEdit" as-child variant="outline"
                    ><Link :href="edit(order.id)"
                        ><Pencil />Editar</Link
                    ></Button
                ><Button
                    v-if="canSend"
                    class="bg-racing-yellow text-racing-black"
                    @click="transition('send')"
                    ><Send />Enviar</Button
                ><Button
                    v-if="canConfirm"
                    class="bg-racing-green text-white"
                    @click="transition('confirm')"
                    ><Check />Confirmar</Button
                ><Button
                    v-if="canDelete"
                    size="icon"
                    variant="destructive"
                    aria-label="Eliminar borrador"
                    @click="removeOrder"
                    ><Trash2
                /></Button>
            </div>
        </header>
        <div
            v-if="order.cancellation_reason"
            class="border-racing-red/30 bg-racing-red/10 text-racing-red rounded-xl border p-4"
        >
            <strong>Pedido cancelado:</strong> {{ order.cancellation_reason }}
        </div>
        <div class="grid gap-6 xl:grid-cols-[minmax(0,1fr)_21rem]">
            <main class="grid gap-6">
                <section class="bg-card overflow-hidden rounded-2xl border">
                    <div class="p-5">
                        <h2 class="font-black">Detalle de productos</h2>
                        <p
                            v-if="order.notes"
                            class="text-muted-foreground mt-1 text-sm"
                        >
                            {{ order.notes }}
                        </p>
                    </div>
                    <div class="hidden overflow-x-auto sm:block">
                        <table class="w-full text-sm">
                            <thead class="bg-muted text-left text-xs uppercase">
                                <tr>
                                    <th class="px-5 py-3">Producto</th>
                                    <th class="px-5 py-3">Cantidad</th>
                                    <th class="px-5 py-3">Recibido</th>
                                    <th class="px-5 py-3">Costo</th>
                                    <th class="px-5 py-3 text-right">
                                        Subtotal
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr
                                    v-for="item in order.items"
                                    :key="item.id"
                                    class="border-t"
                                >
                                    <td class="px-5 py-4">
                                        <Link
                                            :href="
                                                showProduct(
                                                    Number(item.product_id),
                                                )
                                            "
                                            class="hover:text-racing-yellow font-bold"
                                            >{{ item.product_name }}</Link
                                        >
                                        <p
                                            class="text-muted-foreground text-xs"
                                        >
                                            {{ item.product_sku }} ·
                                            {{ item.unit_label }}
                                        </p>
                                    </td>
                                    <td class="px-5 py-4">
                                        {{ item.quantity_ordered }}
                                    </td>
                                    <td class="px-5 py-4">
                                        {{ item.quantity_received || '0.000' }}
                                    </td>
                                    <td class="px-5 py-4">
                                        {{ money(item.unit_cost) }}
                                    </td>
                                    <td class="px-5 py-4 text-right font-black">
                                        {{ money(item.subtotal || '0') }}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div class="divide-y sm:hidden">
                        <article
                            v-for="item in order.items"
                            :key="item.id"
                            class="p-5"
                        >
                            <Link
                                :href="showProduct(Number(item.product_id))"
                                class="font-black"
                                >{{ item.product_name }}</Link
                            >
                            <p class="text-muted-foreground text-xs">
                                {{ item.product_sku }} ·
                                {{ item.quantity_ordered }}
                                {{ item.unit_label }}
                            </p>
                            <p class="text-racing-green mt-1 text-xs font-bold">
                                Recibido {{ item.quantity_received || '0.000' }}
                            </p>
                            <div class="mt-2 flex justify-between text-sm">
                                <span>{{ money(item.unit_cost) }} c/u</span
                                ><strong>{{
                                    money(item.subtotal || '0')
                                }}</strong>
                            </div>
                        </article>
                    </div>
                </section>
                <PurchaseReceiptForm
                    v-if="canReceive"
                    :order="order"
                    :action="storeReceipt.url(order.id)"
                    :idempotency-key="receiptKey"
                />
                <section
                    v-if="order.receipts.length"
                    class="bg-card rounded-2xl border p-5 sm:p-6"
                >
                    <h2 class="flex items-center gap-2 font-black">
                        <History class="text-racing-yellow" />Recepciones
                        registradas
                    </h2>
                    <div class="mt-5 grid gap-4">
                        <article
                            v-for="receipt in order.receipts"
                            :key="receipt.id"
                            class="rounded-xl border p-4"
                        >
                            <div
                                class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between"
                            >
                                <div>
                                    <p class="font-black">
                                        {{ receipt.number }}
                                    </p>
                                    <p class="text-muted-foreground text-xs">
                                        {{ receipt.receiver_name }} ·
                                        {{
                                            new Date(
                                                receipt.received_at,
                                            ).toLocaleString('es-PE')
                                        }}
                                    </p>
                                </div>
                                <span
                                    v-if="receipt.supplier_document_number"
                                    class="bg-muted rounded-full px-2 py-1 text-xs font-bold"
                                    >{{
                                        receipt.supplier_document_number
                                    }}</span
                                >
                            </div>
                            <div class="mt-3 flex flex-wrap gap-2 text-xs">
                                <span
                                    v-for="item in receipt.items"
                                    :key="item.id"
                                    class="bg-racing-green/10 text-racing-green rounded-full px-2 py-1 font-bold"
                                    >{{ item.product_name }}:
                                    {{ item.quantity_received }}
                                    {{ item.unit_label }} ·
                                    {{ money(item.unit_cost) }}
                                    <span
                                        v-if="
                                            item.unit_cost !==
                                            item.ordered_unit_cost
                                        "
                                        class="text-racing-red"
                                        >(pedido
                                        {{
                                            money(item.ordered_unit_cost)
                                        }})</span
                                    ></span
                                >
                            </div>
                            <p
                                v-if="receipt.notes"
                                class="text-muted-foreground mt-3 text-sm"
                            >
                                {{ receipt.notes }}
                            </p>
                            <div
                                v-if="receipt.attachments.length"
                                class="mt-3 flex flex-wrap gap-2"
                            >
                                <a
                                    v-for="file in receipt.attachments"
                                    :key="file.id"
                                    :href="file.url"
                                    target="_blank"
                                    class="hover:border-racing-yellow rounded-lg border px-3 py-2 text-xs font-bold"
                                    ><FileText class="mr-1 inline size-3" />{{
                                        file.file_name
                                    }}</a
                                >
                            </div>
                        </article>
                    </div>
                </section>
                <section
                    v-if="canCancel"
                    class="border-racing-red/30 bg-card rounded-2xl border p-5"
                >
                    <h2 class="flex items-center gap-2 font-black">
                        <X class="text-racing-red" />Cancelar pedido
                    </h2>
                    <p class="text-muted-foreground mt-1 text-sm">
                        Indica el motivo para conservar la trazabilidad.
                    </p>
                    <form
                        class="mt-4 grid gap-3 sm:grid-cols-[1fr_auto]"
                        @submit.prevent="cancelOrder"
                    >
                        <div class="grid gap-2">
                            <Label for="cancel-reason">Motivo</Label
                            ><textarea
                                id="cancel-reason"
                                v-model="cancellation.reason"
                                required
                                maxlength="255"
                                rows="2"
                                class="rounded-md border px-3 py-2 text-sm"
                                placeholder="Ej. proveedor sin disponibilidad"
                            /><InputError
                                :message="
                                    cancellation.errors.reason ??
                                    cancellation.errors.action
                                "
                            />
                        </div>
                        <Button
                            type="submit"
                            variant="destructive"
                            class="self-end"
                            :disabled="cancellation.processing"
                            ><X />Cancelar pedido</Button
                        >
                    </form>
                </section>
            </main>
            <aside class="grid h-fit gap-4">
                <section class="bg-racing-black rounded-2xl p-5 text-white">
                    <h2 class="font-black">Resumen</h2>
                    <dl class="mt-4 grid gap-3 text-sm">
                        <div class="flex justify-between">
                            <dt class="text-zinc-400">Subtotal</dt>
                            <dd>{{ money(order.subtotal) }}</dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-zinc-400">
                                Impuesto ({{ order.tax_rate }}%)
                            </dt>
                            <dd>{{ money(order.tax) }}</dd>
                        </div>
                        <div
                            class="border-racing-yellow/30 flex justify-between border-t pt-3 text-lg font-black"
                        >
                            <dt>Total</dt>
                            <dd class="text-racing-yellow">
                                {{ money(order.total) }}
                            </dd>
                        </div>
                        <div class="border-t border-white/10 pt-3">
                            <dt class="text-zinc-400">Entrega esperada</dt>
                            <dd>
                                {{ order.expected_at || 'Sin fecha definida' }}
                            </dd>
                        </div>
                        <div>
                            <dt class="text-zinc-400">Enviado</dt>
                            <dd>
                                {{
                                    order.ordered_at
                                        ? new Date(
                                              order.ordered_at,
                                          ).toLocaleString('es-PE')
                                        : 'Aún no enviado'
                                }}
                            </dd>
                        </div>
                        <div v-if="order.approver_name">
                            <dt class="text-zinc-400">Confirmado por</dt>
                            <dd>{{ order.approver_name }}</dd>
                        </div>
                    </dl>
                </section>
                <section
                    v-if="order.inquiry"
                    class="bg-card rounded-2xl border p-5"
                >
                    <p
                        class="text-muted-foreground text-xs font-bold uppercase"
                    >
                        Origen
                    </p>
                    <Link
                        :href="showInquiry(order.inquiry.id)"
                        class="hover:text-racing-yellow mt-1 block font-black"
                        >Consulta {{ order.inquiry.number }}</Link
                    >
                </section>
                <section class="bg-card rounded-2xl border p-5">
                    <h2 class="flex items-center gap-2 font-black">
                        <FileText />Adjuntos
                    </h2>
                    <div
                        v-if="order.attachments.length"
                        class="mt-3 grid gap-2"
                    >
                        <div
                            v-for="file in order.attachments"
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
                                aria-label="Eliminar adjunto"
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
