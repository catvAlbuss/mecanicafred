<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3';
import {
    AlertTriangle,
    ArrowDown,
    ArrowUp,
    CheckCircle2,
    DollarSign,
    PackagePlus,
    Search,
    ShieldCheck,
    TrendingUp,
} from '@lucide/vue';
import { onBeforeUnmount, onMounted, ref } from 'vue';
import { dashboard } from '@/routes';
import {
    create as productCreate,
    index as inventoryIndex,
    show as productShow,
} from '@/routes/inventory/products';

type DashboardInventory = {
    total: number;
    available: number;
    low: number;
    out: number;
    stock_cost: string;
    stock_value: string;
    attention_products: {
        id: number;
        name: string;
        sku: string;
        current_stock: string;
        minimum_stock: string;
        unit_label: string;
        is_out: boolean;
    }[];
    recent_movements: {
        id: number;
        product_name: string;
        type_label: string;
        quantity: string;
        occurred_at: string;
    }[];
};

const props = defineProps<{ inventory: DashboardInventory | null }>();

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Panel principal',
                href: dashboard(),
            },
        ],
    },
});

const user = usePage().props.auth.user;

const promoSlides = [
    {
        eyebrow: 'Servicio de temporada',
        title: 'Tu moto lista para la ruta.',
        description:
            'Revisión, mantenimiento y repuestos confiables para que el taller no se detenga.',
        action: 'Ver inventario',
        href: inventoryIndex(),
        image: 'https://images.unsplash.com/photo-1558981806-ec527fa84c39?auto=format&fit=crop&w=1200&q=85',
        alt: 'Motocicleta deportiva en carretera',
    },
    {
        eyebrow: 'Campaña del taller',
        title: 'Mantenimiento que se nota.',
        description:
            'Promoción de revisión general para motos urbanas y de alto rendimiento.',
        action: 'Registrar producto',
        href: productCreate(),
        image: 'https://images.unsplash.com/photo-1568772585407-9361f9bf3a87?auto=format&fit=crop&w=1200&q=85',
        alt: 'Motocicleta estacionada frente a un taller',
    },
];

const motorcycleBrands = [
    'Honda',
    'Yamaha',
    'Kawasaki',
    'Suzuki',
    'BMW Motorrad',
];
const activePromo = ref(0);
let promoTimer: ReturnType<typeof setInterval> | undefined;

function nextPromo(): void {
    activePromo.value = (activePromo.value + 1) % promoSlides.length;
}

function selectPromo(index: number): void {
    activePromo.value = index;
}

onMounted(() => {
    promoTimer = setInterval(nextPromo, 6500);
});

onBeforeUnmount(() => {
    if (promoTimer) {
        clearInterval(promoTimer);
    }
});

function money(value: string): string {
    return new Intl.NumberFormat('es-PE', {
        style: 'currency',
        currency: 'PEN',
        maximumFractionDigits: 2,
    }).format(Number(value));
}

function movementDate(value: string): string {
    return new Intl.DateTimeFormat('es-PE', {
        dateStyle: 'short',
        timeStyle: 'short',
    }).format(new Date(value));
}
</script>

<template>
    <Head title="Panel principal" />

    <div class="flex flex-1 flex-col gap-6 p-4 sm:p-6 lg:p-8">
        <section
            class="bg-racing-black relative isolate overflow-hidden rounded-2xl text-white"
        >
            <Transition name="promo-fade" mode="out-in">
                <div
                    :key="activePromo"
                    class="relative grid min-h-84 items-end md:min-h-96 md:grid-cols-[1.05fr_0.95fr]"
                >
                    <div class="relative z-10 p-5 sm:p-8 lg:p-10">
                        <span
                            class="text-racing-yellow inline-flex items-center gap-2 rounded-full bg-white/10 px-3 py-1 text-xs font-bold uppercase"
                        >
                            <ShieldCheck class="size-3.5" />
                            {{ promoSlides[activePromo].eyebrow }}
                        </span>
                        <p class="mt-6 text-sm text-zinc-400">
                            Turno de trabajo, {{ user?.name }}
                        </p>
                        <h1
                            class="mt-1 max-w-lg text-3xl leading-tight font-black tracking-tight sm:text-4xl"
                        >
                            {{ promoSlides[activePromo].title }}
                        </h1>
                        <p
                            class="mt-3 max-w-md text-sm leading-6 text-zinc-300"
                        >
                            {{ promoSlides[activePromo].description }}
                        </p>
                        <div class="mt-6 flex flex-wrap items-center gap-3">
                            <Link
                                :href="promoSlides[activePromo].href"
                                class="bg-racing-yellow text-racing-black inline-flex items-center rounded-md px-4 py-2.5 text-sm font-black transition-transform hover:-translate-y-0.5"
                            >
                                {{ promoSlides[activePromo].action }}
                            </Link>
                            <span
                                v-if="user?.roles.length"
                                class="text-xs text-zinc-400"
                            >
                                {{ user.roles.join(' · ') }}
                            </span>
                        </div>
                    </div>
                    <div
                        class="absolute inset-0 -z-10 md:relative md:inset-auto md:h-full"
                    >
                        <img
                            :src="promoSlides[activePromo].image"
                            :alt="promoSlides[activePromo].alt"
                            class="h-full min-h-84 w-full object-cover opacity-55 md:min-h-0 md:opacity-100"
                        />
                        <div
                            class="from-racing-black via-racing-black/75 md:from-racing-black/10 absolute inset-0 bg-linear-to-r to-transparent md:bg-linear-to-r md:via-transparent md:to-transparent"
                        />
                    </div>
                </div>
            </Transition>
            <div
                class="absolute right-5 bottom-5 z-20 flex items-center gap-2 sm:right-8"
            >
                <button
                    v-for="(_, index) in promoSlides"
                    :key="index"
                    type="button"
                    class="h-1.5 rounded-full transition-all"
                    :class="
                        activePromo === index
                            ? 'bg-racing-yellow w-8'
                            : 'w-2 bg-white/50 hover:bg-white'
                    "
                    :aria-label="`Mostrar promoción ${index + 1}`"
                    @click="selectPromo(index)"
                />
            </div>
        </section>

        <section
            class="flex flex-col gap-3 border-y py-4 sm:flex-row sm:items-center sm:justify-between"
        >
            <div>
                <p class="text-xs font-bold tracking-[.18em] uppercase">
                    Marcas que atendemos
                </p>
                <p class="text-muted-foreground text-sm">
                    Repuestos y servicio para las motos del taller.
                </p>
            </div>
            <div class="flex flex-wrap gap-2">
                <span
                    v-for="brand in motorcycleBrands"
                    :key="brand"
                    class="bg-card rounded-md border px-3 py-2 text-xs font-black tracking-wide"
                >
                    {{ brand }}
                </span>
            </div>
        </section>

        <section v-if="props.inventory" class="grid gap-6">
            <div
                class="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between"
            >
                <div>
                    <p
                        class="text-racing-yellow text-xs font-bold tracking-[.2em] uppercase"
                    >
                        Control operativo
                    </p>
                    <h2 class="text-xl font-black">Inventario para el turno</h2>
                    <p class="text-muted-foreground text-sm">
                        Lo importante para encontrar y reponer piezas sin perder
                        tiempo.
                    </p>
                </div>
                <div class="flex flex-wrap gap-2">
                    <Link
                        :href="inventoryIndex()"
                        class="border-racing-yellow text-racing-yellow hover:bg-racing-yellow/10 inline-flex items-center gap-2 rounded-md border px-3 py-2 text-sm font-bold"
                    >
                        <Search class="size-4" />Buscar pieza
                    </Link>
                    <Link
                        :href="productCreate()"
                        class="bg-racing-yellow text-racing-black hover:bg-racing-yellow/90 inline-flex items-center gap-2 rounded-md px-3 py-2 text-sm font-bold"
                    >
                        <PackagePlus class="size-4" />Registrar
                    </Link>
                </div>
            </div>

            <dl
                class="bg-card grid grid-cols-2 divide-x divide-y rounded-2xl border shadow-sm sm:grid-cols-4 sm:divide-y-0"
            >
                <div class="p-4 sm:p-5">
                    <dt
                        class="text-muted-foreground text-xs font-bold uppercase"
                    >
                        Productos
                    </dt>
                    <dd class="mt-1 text-2xl font-black">
                        {{ props.inventory.total }}
                    </dd>
                </div>
                <div class="p-4 sm:p-5">
                    <dt
                        class="text-muted-foreground text-xs font-bold uppercase"
                    >
                        Disponibles
                    </dt>
                    <dd class="text-racing-green mt-1 text-2xl font-black">
                        {{ props.inventory.available }}
                    </dd>
                </div>
                <div class="p-4 sm:p-5">
                    <dt
                        class="text-muted-foreground text-xs font-bold uppercase"
                    >
                        Por reponer
                    </dt>
                    <dd class="text-racing-yellow mt-1 text-2xl font-black">
                        {{ props.inventory.low }}
                    </dd>
                </div>
                <div class="p-4 sm:p-5">
                    <dt
                        class="text-muted-foreground text-xs font-bold uppercase"
                    >
                        Agotados
                    </dt>
                    <dd class="text-racing-red mt-1 text-2xl font-black">
                        {{ props.inventory.out }}
                    </dd>
                </div>
            </dl>

            <div class="grid gap-6 xl:grid-cols-2">
                <article class="bg-card rounded-2xl border p-5 shadow-sm">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <h3 class="font-black">Capital en repuestos</h3>
                            <p class="text-muted-foreground text-sm">
                                Referencia para compras y valorización del
                                stock.
                            </p>
                        </div>
                        <DollarSign class="text-racing-green size-5" />
                    </div>
                    <dl
                        class="mt-5 grid divide-y border-y sm:grid-cols-2 sm:divide-x sm:divide-y-0"
                    >
                        <div class="py-3 sm:pr-4">
                            <p class="text-muted-foreground text-xs uppercase">
                                Costo acumulado
                            </p>
                            <p class="mt-1 text-xl font-black">
                                {{ money(props.inventory.stock_cost) }}
                            </p>
                        </div>
                        <div class="py-3 sm:pl-4">
                            <p class="text-muted-foreground text-xs uppercase">
                                Valor de venta
                            </p>
                            <p class="mt-1 text-xl font-black">
                                {{ money(props.inventory.stock_value) }}
                            </p>
                        </div>
                    </dl>
                </article>

                <article class="bg-card rounded-2xl border p-5 shadow-sm">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <h3 class="font-black">Atención inmediata</h3>
                            <p class="text-muted-foreground text-sm">
                                Agotados primero, luego productos bajo mínimo.
                            </p>
                        </div>
                        <AlertTriangle class="text-racing-yellow size-5" />
                    </div>
                    <div
                        v-if="props.inventory.attention_products.length"
                        class="mt-4 grid gap-2"
                    >
                        <Link
                            v-for="product in props.inventory
                                .attention_products"
                            :key="product.id"
                            :href="productShow(product.id)"
                            class="hover:border-racing-yellow/60 flex items-center justify-between gap-3 border-b py-3 transition first:border-t"
                        >
                            <span class="min-w-0">
                                <span
                                    class="block truncate text-sm font-bold"
                                    >{{ product.name }}</span
                                >
                                <span
                                    class="text-muted-foreground block text-xs"
                                >
                                    {{ product.sku }} ·
                                    <span
                                        :class="
                                            product.is_out
                                                ? 'text-racing-red'
                                                : 'text-racing-yellow'
                                        "
                                    >
                                        {{
                                            product.is_out
                                                ? 'Agotado'
                                                : 'Stock bajo'
                                        }}
                                    </span>
                                </span>
                            </span>
                            <span
                                class="shrink-0 text-right text-sm font-black"
                            >
                                {{ product.current_stock }}
                                {{ product.unit_label }}
                                <span
                                    class="text-muted-foreground block text-xs font-normal"
                                >
                                    mín. {{ product.minimum_stock }}
                                </span>
                            </span>
                        </Link>
                    </div>
                    <p
                        v-else
                        class="text-muted-foreground mt-4 rounded-xl border border-dashed p-5 text-center text-sm"
                    >
                        No hay productos que requieran atención.
                    </p>
                </article>
            </div>

            <article class="bg-card rounded-2xl border p-5 shadow-sm">
                <div class="flex items-center justify-between gap-3">
                    <div>
                        <h3 class="font-black">Movimientos recientes</h3>
                        <p class="text-muted-foreground text-sm">
                            Últimas entradas, salidas y ajustes de stock.
                        </p>
                    </div>
                    <TrendingUp class="text-racing-green size-5" />
                </div>
                <div
                    v-if="props.inventory.recent_movements.length"
                    class="mt-4 divide-y border-y"
                >
                    <div
                        v-for="movement in props.inventory.recent_movements"
                        :key="movement.id"
                        class="grid grid-cols-[auto_minmax(0,1fr)_auto] items-center gap-3 py-3"
                    >
                        <span
                            class="flex size-8 items-center justify-center rounded-full"
                            :class="
                                Number(movement.quantity) >= 0
                                    ? 'bg-racing-green/10'
                                    : 'bg-racing-red/10'
                            "
                        >
                            <ArrowUp
                                v-if="Number(movement.quantity) >= 0"
                                class="text-racing-green size-4"
                            />
                            <ArrowDown v-else class="text-racing-red size-4" />
                        </span>
                        <div class="flex items-center justify-between gap-2">
                            <span class="min-w-0">
                                <span class="block truncate text-sm font-bold">
                                    {{ movement.product_name }}
                                </span>
                                <span
                                    class="text-muted-foreground block truncate text-xs"
                                >
                                    {{ movement.type_label }} ·
                                    {{ movementDate(movement.occurred_at) }}
                                </span>
                            </span>
                        </div>
                        <p class="text-right text-lg font-black">
                            {{ Number(movement.quantity) > 0 ? '+' : ''
                            }}{{ movement.quantity }}
                        </p>
                    </div>
                </div>
                <p
                    v-else
                    class="text-muted-foreground mt-4 rounded-xl border border-dashed p-5 text-center text-sm"
                >
                    Todavía no hay movimientos registrados.
                </p>
            </article>
        </section>
    </div>
</template>

<style scoped>
.promo-fade-enter-active,
.promo-fade-leave-active {
    transition:
        opacity 280ms ease,
        transform 280ms ease;
}

.promo-fade-enter-from {
    opacity: 0;
    transform: translateY(8px);
}

.promo-fade-leave-to {
    opacity: 0;
    transform: translateY(-8px);
}
</style>
