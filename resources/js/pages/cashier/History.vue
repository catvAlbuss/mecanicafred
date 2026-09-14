<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { ChevronLeft, ChevronRight, History } from '@lucide/vue';
import { ref } from 'vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { history, index } from '@/routes/cashier';
import type { CashRegisterHistoryPaginator } from '@/types';

const props = defineProps<{
    registers: CashRegisterHistoryPaginator;
    filters: { from: string | null; to: string | null };
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Caja', href: index() },
            { title: 'Historial', href: history() },
        ],
    },
});

const from = ref(props.filters.from ?? '');
const to = ref(props.filters.to ?? '');
const money = (value: string | null) =>
    new Intl.NumberFormat('es-PE', {
        style: 'currency',
        currency: 'PEN',
    }).format(Number(value ?? 0));
const time = (value: string | null) =>
    value
        ? new Date(value).toLocaleString('es-PE', {
              dateStyle: 'medium',
              timeStyle: 'short',
          })
        : '—';

function filter(): void {
    router.get(
        history.url(),
        { from: from.value || undefined, to: to.value || undefined },
        { preserveState: true, replace: true },
    );
}
</script>

<template>
    <Head title="Historial de caja" />
    <div class="flex flex-1 flex-col gap-6 p-4 sm:p-6 lg:p-8">
        <header class="flex items-center gap-3">
            <span
                class="bg-racing-yellow text-racing-black flex size-11 items-center justify-center rounded-xl"
            >
                <History />
            </span>
            <div>
                <h1 class="text-2xl font-black">Historial de caja</h1>
                <p class="text-muted-foreground text-sm">
                    Cajas cerradas con su cuadre.
                </p>
            </div>
        </header>

        <form
            class="bg-card grid gap-3 rounded-2xl border p-4 sm:grid-cols-[12rem_12rem_auto]"
            @submit.prevent="filter"
        >
            <div class="grid gap-2">
                <Label for="cashier-from">Desde</Label>
                <Input id="cashier-from" v-model="from" type="date" />
            </div>
            <div class="grid gap-2">
                <Label for="cashier-to">Hasta</Label>
                <Input id="cashier-to" v-model="to" type="date" />
            </div>
            <Button type="submit" class="self-end">Filtrar</Button>
        </form>

        <section
            v-if="registers.data.length"
            class="bg-card overflow-hidden rounded-2xl border"
        >
            <div class="hidden overflow-x-auto md:block">
                <table class="w-full text-sm">
                    <thead
                        class="bg-racing-black text-left text-xs text-white uppercase"
                    >
                        <tr>
                            <th class="px-5 py-3">Caja</th>
                            <th class="px-5 py-3">Cerró</th>
                            <th class="px-5 py-3">Apertura</th>
                            <th class="px-5 py-3">Esperado</th>
                            <th class="px-5 py-3">Contado</th>
                            <th class="px-5 py-3">Diferencia</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="register in registers.data"
                            :key="register.id"
                            class="border-t"
                        >
                            <td class="px-5 py-4">
                                <p class="font-black">{{ register.number }}</p>
                                <p class="text-muted-foreground text-xs">
                                    {{ register.opened_by }} →
                                    {{ register.closed_by ?? '—' }}
                                </p>
                            </td>
                            <td class="px-5 py-4">
                                {{ time(register.closed_at) }}
                            </td>
                            <td class="px-5 py-4">
                                {{ money(register.opening_amount) }}
                            </td>
                            <td class="px-5 py-4">
                                {{ money(register.expected_cash_amount) }}
                            </td>
                            <td class="px-5 py-4">
                                {{ money(register.counted_cash_amount) }}
                            </td>
                            <td
                                class="px-5 py-4 font-black"
                                :class="
                                    Number(register.difference) < 0
                                        ? 'text-racing-red'
                                        : Number(register.difference) > 0
                                          ? 'text-racing-green'
                                          : ''
                                "
                            >
                                {{ money(register.difference) }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div class="grid gap-3 p-3 md:hidden">
                <article
                    v-for="register in registers.data"
                    :key="register.id"
                    class="rounded-xl border p-4"
                >
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <p class="font-black">{{ register.number }}</p>
                            <p class="text-muted-foreground text-xs">
                                {{ time(register.closed_at) }}
                            </p>
                        </div>
                        <p
                            class="font-black"
                            :class="
                                Number(register.difference) < 0
                                    ? 'text-racing-red'
                                    : Number(register.difference) > 0
                                      ? 'text-racing-green'
                                      : ''
                            "
                        >
                            {{ money(register.difference) }}
                        </p>
                    </div>
                    <dl class="mt-4 grid grid-cols-2 gap-3 text-sm">
                        <div>
                            <dt class="text-muted-foreground text-xs">
                                Esperado
                            </dt>
                            <dd class="font-bold">
                                {{ money(register.expected_cash_amount) }}
                            </dd>
                        </div>
                        <div>
                            <dt class="text-muted-foreground text-xs">
                                Contado
                            </dt>
                            <dd class="font-bold">
                                {{ money(register.counted_cash_amount) }}
                            </dd>
                        </div>
                    </dl>
                    <p class="text-muted-foreground mt-3 border-t pt-3 text-xs">
                        {{ register.opened_by }} →
                        {{ register.closed_by ?? '—' }}
                    </p>
                </article>
            </div>
        </section>
        <div
            v-else
            class="text-muted-foreground rounded-2xl border border-dashed p-12 text-center"
        >
            <History class="mx-auto mb-3 size-8 opacity-50" />
            <p class="font-bold">No hay cajas cerradas en este rango.</p>
        </div>

        <nav v-if="registers.last_page > 1" class="flex justify-end gap-2">
            <Button as-child variant="outline" size="icon">
                <Link
                    :href="registers.prev_page_url || '#'"
                    aria-label="Página anterior"
                >
                    <ChevronLeft />
                </Link> </Button
            ><Button as-child variant="outline" size="icon">
                <Link
                    :href="registers.next_page_url || '#'"
                    aria-label="Página siguiente"
                >
                    <ChevronRight />
                </Link>
            </Button>
        </nav>
    </div>
</template>
