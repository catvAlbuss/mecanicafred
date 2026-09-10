<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import {
    LoaderCircle,
    Search,
    ShoppingBag,
    Trash2,
    TriangleAlert,
    Wallet,
} from '@lucide/vue';
import { computed, ref } from 'vue';
import { toast } from 'vue-sonner';
import BarcodeScanner from '@/components/BarcodeScanner.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useProductLookup } from '@/composables/useProductLookup';
import { useSaleProductSearch } from '@/composables/useSaleProductSearch';
import { index as cashier } from '@/routes/cashier';
import { index, store } from '@/routes/sales';
import type { CashOption, SaleProduct } from '@/types';

const props = defineProps<{
    register: {
        id: number;
        number: string;
        opened_by: string;
        opened_at: string;
    } | null;
    paymentMethods: CashOption[];
    saleKey: string;
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Ventas', href: index() },
            { title: 'Nueva venta', href: index() },
        ],
    },
});

type CartLine = {
    product_id: number;
    name: string;
    sku: string;
    unit_label: string;
    stock: number;
    quantity: string;
    unit_price: string;
};

const { findByCode } = useProductLookup();
const { findProducts } = useSaleProductSearch();

const cart = ref<CartLine[]>([]);
const term = ref('');
const results = ref<SaleProduct[]>([]);
const searching = ref(false);

const form = useForm({
    idempotency_key: props.saleKey,
    payment_method: 'cash',
    customer_name: '',
    customer_document: '',
    discount: '',
    notes: '',
});

const money = (value: number) =>
    new Intl.NumberFormat('es-PE', {
        style: 'currency',
        currency: 'PEN',
    }).format(value);
const errorFor = (key: string): string | undefined =>
    (form.errors as Record<string, string | undefined>)[key];
const lineTotal = (line: CartLine) =>
    (Number(line.quantity) || 0) * (Number(line.unit_price) || 0);
const subtotal = computed(() =>
    cart.value.reduce((sum, line) => sum + lineTotal(line), 0),
);
const discount = computed(() =>
    Math.min(Math.max(Number(form.discount) || 0, 0), subtotal.value),
);
const total = computed(() => subtotal.value - discount.value);
const overStock = (line: CartLine) => Number(line.quantity) > line.stock;
const hasOverStock = computed(() => cart.value.some(overStock));

function addProduct(product: {
    id: number;
    name: string;
    sku: string;
    unit_label: string;
    current_stock: string;
    sale_price: string | null;
}): void {
    const existing = cart.value.find((line) => line.product_id === product.id);
    if (existing) {
        existing.quantity = String((Number(existing.quantity) || 0) + 1);
        return;
    }
    cart.value.push({
        product_id: product.id,
        name: product.name,
        sku: product.sku,
        unit_label: product.unit_label,
        stock: Number(product.current_stock),
        quantity: '1',
        unit_price: product.sale_price ?? '',
    });
}

async function onScan(code: string): Promise<void> {
    try {
        const product = await findByCode(code);
        if (!product.is_active) {
            toast.error(`${product.name} está inactivo.`);
            return;
        }
        addProduct({
            id: product.id,
            name: product.name,
            sku: product.sku,
            unit_label: product.unit_label,
            current_stock: product.current_stock,
            sale_price: product.sale_price,
        });
        toast.success(`${product.name} agregado.`);
    } catch (error) {
        toast.error(
            error instanceof Error ? error.message : 'Código no válido.',
        );
    }
}

let searchTimer: ReturnType<typeof setTimeout> | undefined;
function onSearch(): void {
    clearTimeout(searchTimer);
    const value = term.value.trim();
    if (value.length < 2) {
        results.value = [];
        return;
    }
    searchTimer = setTimeout(async () => {
        searching.value = true;
        try {
            results.value = await findProducts(value);
        } catch {
            results.value = [];
        } finally {
            searching.value = false;
        }
    }, 250);
}
function pickResult(product: SaleProduct): void {
    addProduct({
        id: product.id,
        name: product.name,
        sku: product.sku,
        unit_label: product.unit_label,
        current_stock: product.current_stock,
        sale_price: product.sale_price,
    });
    term.value = '';
    results.value = [];
}

function submit(): void {
    if (cart.value.length === 0) {
        toast.error('Agrega al menos un producto.');
        return;
    }
    form.transform((data) => ({
        ...data,
        discount: data.discount || '0',
        items: cart.value.map((line) => ({
            product_id: line.product_id,
            quantity: Number(line.quantity || 0).toFixed(3),
            unit_price: Number(line.unit_price || 0).toFixed(2),
        })),
    })).post(store.url(), { preserveScroll: true });
}
</script>

<template>

    <Head title="Nueva venta" />
    <div class="flex flex-1 flex-col gap-6 p-4 sm:p-6 lg:p-8">
        <header class="flex items-center gap-3">
            <span class="bg-racing-yellow text-racing-black flex size-11 items-center justify-center rounded-xl">
                <ShoppingBag />
            </span>
            <div>
                <h1 class="text-2xl font-black">Nueva venta</h1>
                <p class="text-muted-foreground text-sm">
                    Escanea o busca productos, cobra e imprime el ticket.
                </p>
            </div>
        </header>

        <div v-if="!register"
            class="border-racing-yellow/40 bg-racing-yellow/10 flex flex-col items-start gap-3 rounded-2xl border p-6">
            <p class="flex items-center gap-2 font-bold">
                <Wallet class="text-racing-yellow" />No hay una caja abierta.
            </p>
            <p class="text-muted-foreground text-sm">
                Abre la caja del día para poder registrar ventas.
            </p>
            <Button as-child class="bg-racing-yellow text-racing-black">
                <Link :href="cashier()">Ir a caja</Link>
            </Button>
        </div>

        <div v-else class="grid gap-6 lg:grid-cols-[1fr_22rem]">
            <div class="grid h-fit gap-4">
                <div class="bg-card rounded-2xl border p-4">
                    <div class="flex flex-wrap items-center gap-2">
                        <div class="relative min-w-0 flex-1">
                            <Search class="text-muted-foreground absolute top-2.5 left-3 size-4" />
                            <Input v-model="term" class="pl-9" placeholder="Buscar producto por nombre, SKU o marca"
                                @input="onSearch" />
                        </div>
                        <BarcodeScanner trigger-label="Escanear"
                            description="Cada escaneo agrega el producto al carrito." @detected="onScan" />
                    </div>
                    <div v-if="term.length >= 2" class="mt-3 grid gap-1">
                        <p v-if="searching" class="text-muted-foreground p-2 text-sm">
                            Buscando…
                        </p>
                        <p v-else-if="!results.length" class="text-muted-foreground p-2 text-sm">
                            Sin resultados.
                        </p>
                        <button v-for="product in results" :key="product.id" type="button"
                            class="hover:bg-muted flex items-center justify-between gap-3 rounded-lg px-3 py-2 text-left text-sm"
                            @click="pickResult(product)">
                            <span class="min-w-0">
                                <span class="font-bold">{{
                                    product.name
                                    }}</span>
                                <span class="text-muted-foreground">
                                    · {{ product.sku }} · stock
                                    {{ product.current_stock }}</span>
                            </span>
                            <span class="shrink-0 font-black">{{
                                money(Number(product.sale_price ?? 0))
                                }}</span>
                        </button>
                    </div>
                </div>

                <div class="bg-card overflow-hidden rounded-2xl border">
                    <div v-if="cart.length" class="divide-y">
                        <article v-for="(line, index) in cart" :key="line.product_id"
                            class="grid gap-3 p-4 sm:grid-cols-[1fr_6rem_7rem_auto] sm:items-center">
                            <div class="min-w-0">
                                <p class="truncate font-bold">
                                    {{ line.name }}
                                </p>
                                <p class="text-muted-foreground text-xs">
                                    {{ line.sku }} · stock {{ line.stock }}
                                    {{ line.unit_label }}
                                </p>
                                <p v-if="overStock(line)"
                                    class="text-racing-red flex items-center gap-1 text-xs font-bold">
                                    <TriangleAlert class="size-3" />Supera el
                                    stock
                                </p>
                            </div>
                            <Input v-model="line.quantity" type="number" min="0.001" step="0.001"
                                aria-label="Cantidad" />
                            <Input v-model="line.unit_price" type="number" min="0" step="0.01"
                                aria-label="Precio unitario" />
                            <div class="flex items-center justify-between gap-2 sm:flex-col sm:items-end">
                                <span class="font-black">{{
                                    money(lineTotal(line))
                                    }}</span>
                                <Button type="button" size="icon" variant="ghost" class="text-racing-red"
                                    :aria-label="`Quitar ${line.name}`" @click="cart.splice(index, 1)">
                                    <Trash2 />
                                </Button>
                            </div>
                        </article>
                    </div>
                    <p v-else class="text-muted-foreground p-10 text-center text-sm">
                        El carrito está vacío. Escanea o busca un producto.
                    </p>
                </div>
            </div>

            <aside class="grid h-fit gap-4">
                <div class="bg-card rounded-2xl border p-5">
                    <p class="text-muted-foreground text-xs">
                        {{ register.number }} · {{ register.opened_by }}
                    </p>
                    <dl class="mt-3 grid gap-2 text-sm">
                        <div class="flex justify-between">
                            <dt class="text-muted-foreground">Subtotal</dt>
                            <dd>{{ money(subtotal) }}</dd>
                        </div>
                        <div class="flex items-center justify-between gap-2">
                            <dt class="text-muted-foreground">Descuento</dt>
                            <dd class="w-28">
                                <Input v-model="form.discount" type="number" min="0" step="0.01" class="h-8 text-right"
                                    aria-label="Descuento" />
                            </dd>
                        </div>
                        <div class="flex justify-between border-t pt-2 text-lg font-black">
                            <dt>Total</dt>
                            <dd class="text-racing-yellow">
                                {{ money(total) }}
                            </dd>
                        </div>
                    </dl>
                    <InputError class="mt-2" :message="form.errors.discount" />
                    <InputError class="mt-2" :message="errorFor('items')" />
                </div>

                <div class="bg-card grid gap-4 rounded-2xl border p-5">
                    <div class="grid gap-2">
                        <Label for="method">Método de pago</Label>
                        <select id="method" v-model="form.payment_method" class="h-9 rounded-md border px-3 text-sm">
                            <option v-for="option in paymentMethods" :key="option.value" :value="option.value">
                                {{ option.label }}
                            </option>
                        </select>
                    </div>
                    <div class="grid gap-2">
                        <Label for="customer">Cliente (opcional)</Label>
                        <Input id="customer" v-model="form.customer_name" maxlength="150" placeholder="Nombre" />
                    </div>
                    <div class="grid gap-2">
                        <Label for="document">Documento (opcional)</Label>
                        <Input id="document" v-model="form.customer_document" maxlength="20" placeholder="DNI / RUC" />
                    </div>
                    <div class="grid gap-2">
                        <Label for="notes">Notas</Label>
                        <textarea id="notes" v-model="form.notes" rows="2" maxlength="2000"
                            class="rounded-md border px-3 py-2 text-sm" />
                    </div>
                </div>

                <Button type="button" :disabled="form.processing || cart.length === 0"
                    class="bg-racing-green h-12 text-base text-white" @click="submit">
                    <LoaderCircle v-if="form.processing" class="animate-spin" />
                    <ShoppingBag v-else />
                    {{
                        form.processing
                            ? 'Registrando…'
                            : `Cobrar ${money(total)}`
                    }}
                </Button>
                <p v-if="hasOverStock" class="text-racing-red text-center text-xs font-bold">
                    Hay líneas que superan el stock disponible.
                </p>
            </aside>
        </div>
    </div>
</template>
