<script setup lang="ts">
import { Head, usePage } from '@inertiajs/vue3';
import {
    Boxes,
    CarFront,
    CheckCircle2,
    ClipboardList,
    Clock3,
    ShieldCheck,
    TrendingUp,
    Users,
    Wrench,
} from '@lucide/vue';
import { dashboard } from '@/routes';

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

const summaryCards = [
    {
        label: 'Órdenes activas',
        value: '0',
        detail: 'Sin trabajos pendientes',
        icon: ClipboardList,
        color: 'text-racing-yellow',
        surface: 'bg-racing-yellow/10',
    },
    {
        label: 'Vehículos en taller',
        value: '0',
        detail: 'Bahías disponibles',
        icon: CarFront,
        color: 'text-racing-red',
        surface: 'bg-racing-red/10',
    },
    {
        label: 'Servicios finalizados',
        value: '0',
        detail: 'Durante este mes',
        icon: CheckCircle2,
        color: 'text-racing-green',
        surface: 'bg-racing-green/10',
    },
];

const modules = [
    {
        name: 'Clientes',
        description: 'Historial y datos de contacto.',
        icon: Users,
        accent: 'group-hover:text-racing-yellow',
    },
    {
        name: 'Vehículos',
        description: 'Fichas técnicas y kilometraje.',
        icon: CarFront,
        accent: 'group-hover:text-racing-red',
    },
    {
        name: 'Órdenes de trabajo',
        description: 'Diagnóstico, tareas y avances.',
        icon: Wrench,
        accent: 'group-hover:text-racing-green',
    },
    {
        name: 'Inventario',
        description: 'Repuestos, stock y movimientos.',
        icon: Boxes,
        accent: 'group-hover:text-racing-yellow',
    },
];
</script>

<template>
    <Head title="Panel principal" />

    <div class="flex flex-1 flex-col gap-6 p-4 sm:p-6 lg:p-8">
        <section
            class="bg-racing-black relative overflow-hidden rounded-2xl px-5 py-7 text-white sm:px-8 sm:py-9"
        >
            <div
                class="bg-racing-yellow/20 absolute -top-24 -right-16 size-64 rounded-full blur-3xl"
            />
            <div
                class="bg-racing-red absolute right-8 bottom-0 h-24 w-2 -skew-x-12 sm:right-24"
            />
            <div
                class="relative z-10 flex flex-col gap-6 md:flex-row md:items-end md:justify-between"
            >
                <div class="space-y-3">
                    <span
                        class="text-racing-yellow inline-flex items-center gap-2 rounded-full bg-white/10 px-3 py-1 text-xs font-semibold"
                    >
                        <ShieldCheck class="size-3.5" />
                        Sistema operativo
                    </span>
                    <div>
                        <p class="text-sm text-zinc-400">Bienvenido,</p>
                        <h1
                            class="text-2xl font-black tracking-tight sm:text-3xl"
                        >
                            {{ user?.name }}
                        </h1>
                    </div>
                    <p class="max-w-xl text-sm leading-6 text-zinc-400">
                        La base de Fredy Racing está lista para organizar la
                        operación diaria del taller.
                    </p>
                </div>
                <div v-if="user?.roles.length" class="flex flex-wrap gap-2">
                    <span
                        v-for="role in user.roles"
                        :key="role"
                        class="rounded-lg border border-white/10 bg-white/5 px-3 py-2 text-xs font-bold tracking-wide uppercase"
                    >
                        {{ role }}
                    </span>
                </div>
            </div>
        </section>

        <section class="grid gap-4 md:grid-cols-3">
            <article
                v-for="card in summaryCards"
                :key="card.label"
                class="bg-card rounded-2xl border p-5 shadow-sm"
            >
                <div class="flex items-start justify-between gap-4">
                    <div class="space-y-2">
                        <p class="text-muted-foreground text-sm font-medium">
                            {{ card.label }}
                        </p>
                        <p class="text-3xl font-black">{{ card.value }}</p>
                        <p class="text-muted-foreground text-xs">
                            {{ card.detail }}
                        </p>
                    </div>
                    <span
                        class="flex size-11 items-center justify-center rounded-xl"
                        :class="[card.surface, card.color]"
                    >
                        <component :is="card.icon" class="size-5" />
                    </span>
                </div>
            </article>
        </section>

        <section class="grid gap-6 xl:grid-cols-[1.45fr_0.55fr]">
            <div class="bg-card rounded-2xl border p-5 sm:p-6">
                <div class="mb-5 flex items-center justify-between gap-4">
                    <div>
                        <h2 class="text-lg font-black">Módulos del taller</h2>
                        <p class="text-muted-foreground text-sm">
                            Estructura preparada para las siguientes etapas.
                        </p>
                    </div>
                    <TrendingUp class="text-racing-green size-5" />
                </div>
                <div class="grid gap-3 sm:grid-cols-2">
                    <article
                        v-for="module in modules"
                        :key="module.name"
                        class="group bg-background hover:border-racing-yellow/50 flex gap-4 rounded-xl border p-4 transition-colors"
                    >
                        <component
                            :is="module.icon"
                            class="text-muted-foreground mt-0.5 size-5 shrink-0 transition-colors"
                            :class="module.accent"
                        />
                        <div class="min-w-0">
                            <div class="flex flex-wrap items-center gap-2">
                                <h3 class="font-bold">{{ module.name }}</h3>
                                <span
                                    class="bg-muted text-muted-foreground rounded-full px-2 py-0.5 text-[10px] font-bold uppercase"
                                >
                                    Próximamente
                                </span>
                            </div>
                            <p
                                class="text-muted-foreground mt-1 text-xs leading-5"
                            >
                                {{ module.description }}
                            </p>
                        </div>
                    </article>
                </div>
            </div>

            <aside class="bg-card rounded-2xl border p-5 sm:p-6">
                <div class="flex items-center gap-3">
                    <span
                        class="bg-racing-green/10 text-racing-green flex size-10 items-center justify-center rounded-xl"
                    >
                        <Clock3 class="size-5" />
                    </span>
                    <div>
                        <h2 class="font-black">Estado inicial</h2>
                        <p class="text-muted-foreground text-xs">
                            Base configurada
                        </p>
                    </div>
                </div>
                <ul class="mt-6 grid gap-4 text-sm">
                    <li class="flex items-center gap-3">
                        <CheckCircle2
                            class="text-racing-green size-4 shrink-0"
                        />
                        Acceso privado sin registro
                    </li>
                    <li class="flex items-center gap-3">
                        <CheckCircle2
                            class="text-racing-green size-4 shrink-0"
                        />
                        Roles y permisos por área
                    </li>
                    <li class="flex items-center gap-3">
                        <CheckCircle2
                            class="text-racing-green size-4 shrink-0"
                        />
                        Gestión de archivos preparada
                    </li>
                </ul>
            </aside>
        </section>
    </div>
</template>
