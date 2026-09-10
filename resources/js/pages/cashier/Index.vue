<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import {
    ArrowDownLeft,
    ArrowUpRight,
    History,
    Lock,
    Plus,
    ShoppingBag,
    Wallet,
} from '@lucide/vue';
import { computed, ref } from 'vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { close, history, open, index } from '@/routes/cashier';
import { store as storeTransaction } from '@/routes/cashier/transactions';
import { create as newSale } from '@/routes/sales';
import type {
    CashOption,
    CashRegisterData,
    ExpenseCategoryOption,
    OpenCashRegister,
} from '@/types';

const props = defineProps<{
    register: OpenCashRegister | null;
    lastClosed: CashRegisterData | null;
    options: {
        payment_methods: CashOption[];
        expense_categories: ExpenseCategoryOption[];
    };
    can: {
        open: boolean;
        close: boolean;
        register_movement: boolean;
        sell: boolean;
    };
}>();

defineOptions({ layout: { breadcrumbs: [{ title: 'Caja', href: index() }] } });

const money = (value: string | number | null) =>
    new Intl.NumberFormat('es-PE', {
        style: 'currency',
        currency: 'PEN',
    }).format(Number(value ?? 0));
const time = (value: string) =>
    new Date(value).toLocaleString('es-PE', {
        dateStyle: 'medium',
        timeStyle: 'short',
    });

const openForm = useForm({ opening_amount: '', notes: '' });
const closeForm = useForm({ counted_cash_amount: '', notes: '' });
const movementForm = useForm({
    type: 'expense',
    category: 'operating_expense',
    payment_method: 'cash',
    amount: '',
    description: '',
    reference: '',
});
const movementOpen = ref(false);
const closing = ref(false);

const categoriesForType = computed(() =>
    props.options.expense_categories.filter((category) =>
        category.types.includes(movementForm.type),
    ),
);

function submitOpen(): void {
    openForm.post(open.url(), { preserveScroll: true });
}
function submitClose(): void {
    closeForm.post(close.url(), {
        preserveScroll: true,
        onSuccess: () => (closing.value = false),
    });
}
function submitMovement(): void {
    movementForm.post(storeTransaction.url(), {
        preserveScroll: true,
        onSuccess: () => {
            movementForm.reset('amount', 'description', 'reference');
            movementOpen.value = false;
        },
    });
}
function syncCategory(): void {
    if (
        !categoriesForType.value.some((c) => c.value === movementForm.category)
    ) {
        movementForm.category = categoriesForType.value[0]?.value ?? '';
    }
}
</script>

<template>

    <Head title="Caja" />
    <div class="flex flex-1 flex-col gap-6 p-4 sm:p-6 lg:p-8">
        <section class="bg-racing-black relative overflow-hidden rounded-2xl p-6 text-white">
            <div class="bg-racing-yellow/15 absolute -top-16 -right-12 size-48 rounded-full blur-3xl" />
            <div class="relative flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div class="flex gap-4">
                    <span
                        class="bg-racing-yellow text-racing-black flex size-12 shrink-0 items-center justify-center rounded-xl">
                        <Wallet />
                    </span>
                    <div>
                        <p class="text-racing-yellow text-xs font-bold tracking-[.2em] uppercase">
                            Tienda
                        </p>
                        <h1 class="text-2xl font-black sm:text-3xl">
                            Caja del día
                        </h1>
                        <p class="text-sm text-zinc-300">
                            {{
                                register
                                    ? `${register.number} · abierta ${time(register.opened_at)}`
                                    : 'No hay ninguna caja abierta.'
                            }}
                        </p>
                    </div>
                </div>
                <div class="flex flex-wrap gap-2">
                    <Button as-child variant="outline" class="text-racing-black">
                        <Link :href="history()">
                            <History />Historial
                        </Link>
                    </Button>
                    <Button v-if="register && can.sell" as-child class="bg-racing-yellow text-racing-black">
                        <Link :href="newSale()">
                            <ShoppingBag />Nueva venta
                        </Link>
                    </Button>
                </div>
            </div>
        </section>

        <!-- Sin caja abierta -->
        <section v-if="!register" class="bg-card rounded-2xl border p-6">
            <h2 class="flex items-center gap-2 font-black">
                <Wallet class="text-racing-yellow" />Abrir caja
            </h2>
            <p class="text-muted-foreground mt-1 text-sm">
                Registra el efectivo con el que inicias el turno.
            </p>
            <p v-if="lastClosed" class="text-muted-foreground mt-2 text-xs">
                Última caja: {{ lastClosed.number }} · cerró
                {{ lastClosed.closed_at ? time(lastClosed.closed_at) : '—' }} ·
                diferencia {{ money(lastClosed.difference) }}
            </p>
            <form v-if="can.open" class="mt-5 grid gap-4 sm:max-w-md" @submit.prevent="submitOpen">
                <div class="grid gap-2">
                    <Label for="opening-amount">Monto de apertura</Label>
                    <Input id="opening-amount" v-model="openForm.opening_amount" type="number" min="0" step="0.01"
                        required />
                    <InputError :message="openForm.errors.opening_amount" />
                </div>
                <div class="grid gap-2">
                    <Label for="opening-notes">Notas (opcional)</Label>
                    <textarea id="opening-notes" v-model="openForm.notes" rows="2" maxlength="2000"
                        class="rounded-md border px-3 py-2 text-sm" />
                    <InputError :message="openForm.errors.notes" />
                </div>
                <Button type="submit" :disabled="openForm.processing"
                    class="bg-racing-yellow text-racing-black justify-self-start">
                    <Wallet />Abrir caja
                </Button>
            </form>
            <p v-else class="text-muted-foreground mt-4 text-sm">
                No tienes permiso para abrir la caja.
            </p>
        </section>

        <!-- Caja abierta -->
        <template v-else>
            <section class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                <article class="bg-card rounded-2xl border p-4">
                    <p class="text-muted-foreground text-xs uppercase">
                        Apertura
                    </p>
                    <p class="mt-1 text-2xl font-black">
                        {{ money(register.opening_amount) }}
                    </p>
                </article>
                <article class="bg-card rounded-2xl border p-4">
                    <p class="text-muted-foreground text-xs uppercase">
                        Ventas
                    </p>
                    <p class="mt-1 text-2xl font-black">
                        {{ register.summary.sales_count }}
                    </p>
                </article>
                <article class="bg-card rounded-2xl border p-4">
                    <p class="text-racing-green flex items-center gap-1 text-xs uppercase">
                        <ArrowDownLeft class="size-3" />Ingresos
                    </p>
                    <p class="text-racing-green mt-1 text-2xl font-black">
                        {{ money(register.summary.income) }}
                    </p>
                </article>
                <article class="bg-card rounded-2xl border p-4">
                    <p class="text-racing-red flex items-center gap-1 text-xs uppercase">
                        <ArrowUpRight class="size-3" />Egresos
                    </p>
                    <p class="text-racing-red mt-1 text-2xl font-black">
                        {{ money(register.summary.expense) }}
                    </p>
                </article>
            </section>

            <section class="grid gap-6 lg:grid-cols-[1fr_20rem]">
                <div class="grid gap-6">
                    <div class="bg-card overflow-hidden rounded-2xl border">
                        <div class="flex flex-wrap items-center justify-between gap-3 p-5">
                            <h2 class="font-black">Movimientos del turno</h2>
                            <Dialog v-if="can.register_movement" v-model:open="movementOpen">
                                <DialogTrigger as-child>
                                    <Button variant="outline" size="sm">
                                        <Plus />Registrar movimiento
                                    </Button>
                                </DialogTrigger>
                                <DialogContent class="sm:max-w-md">
                                    <DialogHeader>
                                        <DialogTitle>Movimiento de caja</DialogTitle>
                                    </DialogHeader>
                                    <form class="grid gap-4" @submit.prevent="submitMovement">
                                        <div class="grid gap-2">
                                            <Label for="mv-type">Tipo</Label>
                                            <select id="mv-type" v-model="movementForm.type"
                                                class="h-9 rounded-md border px-3 text-sm" @change="syncCategory">
                                                <option value="expense">
                                                    Egreso
                                                </option>
                                                <option value="income">
                                                    Ingreso
                                                </option>
                                            </select>
                                        </div>
                                        <div class="grid gap-2">
                                            <Label for="mv-category">Categoría</Label>
                                            <select id="mv-category" v-model="movementForm.category"
                                                class="h-9 rounded-md border px-3 text-sm">
                                                <option v-for="category in categoriesForType" :key="category.value"
                                                    :value="category.value">
                                                    {{ category.label }}
                                                </option>
                                            </select>
                                            <InputError :message="movementForm.errors.category
                                                " />
                                        </div>
                                        <div class="grid gap-2">
                                            <Label for="mv-method">Método</Label>
                                            <select id="mv-method" v-model="movementForm.payment_method
                                                " class="h-9 rounded-md border px-3 text-sm">
                                                <option v-for="method in options.payment_methods" :key="method.value"
                                                    :value="method.value">
                                                    {{ method.label }}
                                                </option>
                                            </select>
                                        </div>
                                        <div class="grid gap-2">
                                            <Label for="mv-amount">Monto</Label>
                                            <Input id="mv-amount" v-model="movementForm.amount" type="number" min="0.01"
                                                step="0.01" required />
                                            <InputError :message="movementForm.errors.amount
                                                " />
                                        </div>
                                        <div class="grid gap-2">
                                            <Label for="mv-desc">Descripción</Label>
                                            <Input id="mv-desc" v-model="movementForm.description
                                                " maxlength="200" required />
                                            <InputError :message="movementForm.errors
                                                    .description
                                                " />
                                        </div>
                                        <div class="grid gap-2">
                                            <Label for="mv-ref">Referencia (opcional)</Label>
                                            <Input id="mv-ref" v-model="movementForm.reference" maxlength="150"
                                                placeholder="N° factura o guía" />
                                        </div>
                                        <Button type="submit" :disabled="movementForm.processing"
                                            class="bg-racing-yellow text-racing-black">Registrar</Button>
                                    </form>
                                </DialogContent>
                            </Dialog>
                        </div>
                        <div v-if="register.transactions.length" class="divide-y border-t">
                            <article v-for="tx in register.transactions" :key="tx.id"
                                class="flex items-center justify-between gap-3 px-5 py-3">
                                <div class="min-w-0">
                                    <p class="truncate font-bold">
                                        {{ tx.description }}
                                    </p>
                                    <p class="text-muted-foreground truncate text-xs">
                                        {{ tx.category_label }} ·
                                        {{ tx.payment_method_label }} ·
                                        {{ tx.user_name }}
                                        <span v-if="tx.reference">· {{ tx.reference }}</span>
                                    </p>
                                </div>
                                <p :class="[
                                    'shrink-0 font-black',
                                    tx.type === 'income'
                                        ? 'text-racing-green'
                                        : 'text-racing-red',
                                ]">
                                    {{ tx.type === 'income' ? '+' : '−'
                                    }}{{ money(tx.amount) }}
                                </p>
                            </article>
                        </div>
                        <p v-else class="text-muted-foreground border-t p-8 text-center text-sm">
                            Todavía no hay movimientos en este turno.
                        </p>
                    </div>
                </div>

                <aside class="grid h-fit gap-4">
                    <div class="bg-racing-black rounded-2xl p-5 text-white">
                        <h2 class="font-black">Esperado en efectivo</h2>
                        <p class="text-racing-yellow mt-1 text-3xl font-black">
                            {{ money(register.summary.expected_cash) }}
                        </p>
                        <dl class="mt-4 grid gap-2 text-sm text-zinc-300">
                            <div v-for="(row, key) in register.summary.by_method" :key="key"
                                class="flex justify-between">
                                <dt>{{ row.label }}</dt>
                                <dd>
                                    <span class="text-racing-green">+{{ money(row.in) }}</span>
                                    /
                                    <span class="text-racing-red">−{{ money(row.out) }}</span>
                                </dd>
                            </div>
                        </dl>
                    </div>

                    <div class="bg-card rounded-2xl border p-5">
                        <Button v-if="can.close && !closing" variant="outline" class="w-full" @click="closing = true">
                            <Lock />Cerrar caja
                        </Button>
                        <form v-else-if="closing" class="grid gap-3" @submit.prevent="submitClose">
                            <h2 class="font-black">Cierre de caja</h2>
                            <div class="grid gap-2">
                                <Label for="counted">Efectivo contado</Label>
                                <Input id="counted" v-model="closeForm.counted_cash_amount" type="number" min="0"
                                    step="0.01" required />
                                <InputError :message="closeForm.errors.counted_cash_amount
                                    " />
                            </div>
                            <div class="grid gap-2">
                                <Label for="closing-notes">Notas</Label>
                                <textarea id="closing-notes" v-model="closeForm.notes" rows="2" maxlength="2000"
                                    class="rounded-md border px-3 py-2 text-sm" />
                            </div>
                            <div class="flex gap-2">
                                <Button type="submit" :disabled="closeForm.processing"
                                    class="bg-racing-red text-white">Confirmar cierre</Button>
                                <Button type="button" variant="ghost" @click="closing = false">Cancelar</Button>
                            </div>
                        </form>
                        <p v-else class="text-muted-foreground text-sm">
                            No tienes permiso para cerrar la caja.
                        </p>
                    </div>
                </aside>
            </section>
        </template>
    </div>
</template>
