<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ArrowLeft, Ban, Printer } from '@lucide/vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { cancel, index } from '@/routes/sales';
import type { SaleDetail } from '@/types';

const props = defineProps<{ sale: SaleDetail; canCancel: boolean }>();

defineOptions({
    layout: { breadcrumbs: [{ title: 'Ventas', href: index() }] },
});

const money = (value: string) =>
    new Intl.NumberFormat('es-PE', {
        style: 'currency',
        currency: 'PEN',
    }).format(Number(value));
const time = (value: string | null) =>
    value
        ? new Date(value).toLocaleString('es-PE', {
            dateStyle: 'medium',
            timeStyle: 'short',
        })
        : '—';

const cancelForm = useForm({ reason: '' });
const cancelError = (key: string): string | undefined =>
    (cancelForm.errors as Record<string, string | undefined>)[key];
function submitCancel(): void {
    cancelForm.post(cancel.url(props.sale.id), { preserveScroll: true });
}
function printTicket(): void {
    window.print();
}
</script>

<template>

    <Head :title="sale.number" />
    <div class="flex flex-1 flex-col gap-6 p-4 sm:p-6 lg:p-8">
        <header class="flex flex-wrap items-center justify-between gap-3 print:hidden">
            <div class="flex items-center gap-3">
                <Button as-child size="icon" variant="outline">
                    <Link :href="index()">
                        <ArrowLeft />
                    </Link>
                </Button>
                <div>
                    <h1 class="text-2xl font-black">{{ sale.number }}</h1>
                    <p class="text-muted-foreground text-sm">
                        {{ time(sale.sold_at) }} · {{ sale.seller_name }}
                    </p>
                </div>
                <span :class="[
                    'rounded-full px-2 py-1 text-xs font-bold',
                    sale.status === 'cancelled'
                        ? 'bg-racing-red/15 text-racing-red'
                        : 'bg-racing-green/15 text-racing-green',
                ]">{{ sale.status_label }}</span>
            </div>
            <div class="flex gap-2">
                <Button variant="outline" @click="printTicket">
                    <Printer />Imprimir
                </Button>
                <Dialog v-if="canCancel">
                    <DialogTrigger as-child>
                        <Button variant="destructive">
                            <Ban />Anular
                        </Button>
                    </DialogTrigger>
                    <DialogContent class="sm:max-w-md">
                        <DialogHeader>
                            <DialogTitle>Anular venta {{ sale.number }}</DialogTitle>
                        </DialogHeader>
                        <form class="grid gap-4" @submit.prevent="submitCancel">
                            <p class="text-muted-foreground text-sm">
                                Se devolverá el stock y se registrará la
                                devolución del dinero en la caja abierta.
                            </p>
                            <div class="grid gap-2">
                                <Label for="reason">Motivo</Label>
                                <Input id="reason" v-model="cancelForm.reason" maxlength="255" required />
                                <InputError :message="cancelForm.errors.reason" />
                                <InputError :message="cancelError('sale')" />
                            </div>
                            <div class="flex gap-2">
                                <Button type="submit" variant="destructive" :disabled="cancelForm.processing">Confirmar
                                    anulación</Button>
                                <DialogClose as-child>
                                    <Button type="button" variant="ghost">Cerrar</Button>
                                </DialogClose>
                            </div>
                        </form>
                    </DialogContent>
                </Dialog>
            </div>
        </header>

        <div v-if="sale.status === 'cancelled'"
            class="border-racing-red/30 bg-racing-red/10 text-racing-red rounded-xl border p-4 text-sm print:hidden">
            <strong>Venta anulada</strong>
            {{ sale.cancelled_at ? `el ${time(sale.cancelled_at)}` : '' }}
            <span v-if="sale.canceller_name">por {{ sale.canceller_name }}</span>
            <span v-if="sale.cancellation_reason">· {{ sale.cancellation_reason }}</span>
        </div>

        <article
            class="bg-card mx-auto w-full max-w-sm rounded-2xl border p-6 text-sm print:max-w-none print:border-0 print:shadow-none">
            <div class="text-center">
                <p class="text-lg font-black">FREDY RACING</p>
                <p class="text-muted-foreground text-xs">
                    Comprobante interno de venta
                </p>
            </div>
            <dl class="mt-4 grid grid-cols-2 gap-1 text-xs">
                <dt class="text-muted-foreground">Venta</dt>
                <dd class="text-right font-bold">{{ sale.number }}</dd>
                <dt class="text-muted-foreground">Fecha</dt>
                <dd class="text-right">{{ time(sale.sold_at) }}</dd>
                <dt class="text-muted-foreground">Caja</dt>
                <dd class="text-right">{{ sale.register_number }}</dd>
                <dt class="text-muted-foreground">Atendió</dt>
                <dd class="text-right">{{ sale.seller_name }}</dd>
                <dt class="text-muted-foreground">Pago</dt>
                <dd class="text-right">{{ sale.payment_method_label }}</dd>
                <template v-if="sale.customer_name">
                    <dt class="text-muted-foreground">Cliente</dt>
                    <dd class="text-right">
                        {{ sale.customer_name }}
                        <span v-if="sale.customer_document">({{ sale.customer_document }})</span>
                    </dd>
                </template>
            </dl>

            <table class="mt-4 w-full text-xs">
                <thead>
                    <tr class="border-y text-left">
                        <th class="py-1">Producto</th>
                        <th class="py-1 text-right">Cant.</th>
                        <th class="py-1 text-right">P. Unit</th>
                        <th class="py-1 text-right">Importe</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="item in sale.items" :key="item.id" class="border-b border-dashed">
                        <td class="py-1">
                            {{ item.product_name }}
                            <span class="text-muted-foreground">· {{ item.product_sku }}</span>
                        </td>
                        <td class="py-1 text-right">{{ item.quantity }}</td>
                        <td class="py-1 text-right">
                            {{ money(item.unit_price) }}
                        </td>
                        <td class="py-1 text-right">
                            {{ money(item.subtotal) }}
                        </td>
                    </tr>
                </tbody>
            </table>

            <dl class="mt-3 grid gap-1">
                <div class="flex justify-between">
                    <dt class="text-muted-foreground">Subtotal</dt>
                    <dd>{{ money(sale.subtotal) }}</dd>
                </div>
                <div v-if="Number(sale.discount) > 0" class="flex justify-between">
                    <dt class="text-muted-foreground">Descuento</dt>
                    <dd>-{{ money(sale.discount) }}</dd>
                </div>
                <div class="flex justify-between border-t pt-1 text-base font-black">
                    <dt>Total</dt>
                    <dd>{{ money(sale.total) }}</dd>
                </div>
            </dl>

            <p v-if="sale.notes" class="text-muted-foreground mt-3 border-t pt-2 text-xs">
                {{ sale.notes }}
            </p>
            <p class="mt-4 text-center text-xs">¡Gracias por su compra!</p>
        </article>
    </div>
</template>
