<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { Gauge, ShieldCheck, Wrench } from '@lucide/vue';
import AppLogoIcon from '@/components/AppLogoIcon.vue';
import { home } from '@/routes';

const page = usePage();
const name = page.props.name;

defineProps<{
    title?: string;
    description?: string;
}>();
</script>

<template>
    <div class="bg-background grid min-h-svh lg:grid-cols-[1.15fr_0.85fr]">
        <section
            class="bg-racing-black relative hidden min-h-svh overflow-hidden p-10 text-white lg:flex lg:flex-col lg:justify-between xl:p-14"
        >
            <div
                class="bg-racing-yellow/20 absolute -top-32 -right-32 size-96 rounded-full blur-3xl"
            />
            <div
                class="bg-racing-red/20 absolute -bottom-36 -left-28 size-96 rounded-full blur-3xl"
            />
            <div
                class="absolute inset-y-0 right-[18%] w-px -skew-x-12 bg-white/10"
            />
            <Link
                :href="home()"
                class="relative z-10 flex items-center gap-3 text-lg font-black tracking-wide uppercase"
            >
                <span
                    class="bg-racing-yellow text-racing-black shadow-racing-yellow/10 flex size-11 items-center justify-center rounded-xl shadow-lg"
                >
                    <AppLogoIcon class="size-7" />
                </span>
                {{ name }}
            </Link>

            <div class="relative z-10 max-w-2xl space-y-7">
                <div
                    class="border-racing-yellow/30 bg-racing-yellow/10 text-racing-yellow inline-flex items-center gap-2 rounded-full border px-4 py-2 text-xs font-bold tracking-[0.2em] uppercase"
                >
                    <Gauge class="size-4" />
                    Gestión de taller
                </div>
                <div class="space-y-4">
                    <h1
                        class="max-w-xl text-5xl leading-[1.05] font-black tracking-tight xl:text-6xl"
                    >
                        Tu taller, bajo control.
                    </h1>
                    <p class="max-w-lg text-base leading-7 text-zinc-400">
                        Organiza clientes, vehículos, órdenes de trabajo e
                        inventario desde una sola plataforma.
                    </p>
                </div>
                <div class="flex flex-wrap gap-3 text-sm text-zinc-300">
                    <span
                        class="inline-flex items-center gap-2 rounded-lg border border-white/10 bg-white/5 px-3 py-2"
                    >
                        <Wrench class="text-racing-red size-4" /> Operación ágil
                    </span>
                    <span
                        class="inline-flex items-center gap-2 rounded-lg border border-white/10 bg-white/5 px-3 py-2"
                    >
                        <ShieldCheck class="text-racing-green size-4" /> Acceso
                        seguro
                    </span>
                </div>
            </div>

            <p class="relative z-10 text-xs tracking-wide text-zinc-500">
                Fredy Racing · Pasión por la mecánica
            </p>
        </section>

        <main
            class="relative flex min-h-svh items-center justify-center overflow-hidden px-5 py-10 sm:px-8 lg:px-12"
        >
            <div
                class="from-racing-yellow via-racing-red to-racing-green absolute top-0 left-0 h-1 w-full bg-gradient-to-r lg:hidden"
            />
            <div
                class="mx-auto flex w-full max-w-md flex-col justify-center gap-8"
            >
                <Link
                    :href="home()"
                    class="flex items-center justify-center gap-3 font-black tracking-wide uppercase lg:hidden"
                >
                    <span
                        class="bg-racing-yellow text-racing-black flex size-11 items-center justify-center rounded-xl"
                    >
                        <AppLogoIcon class="size-7" />
                    </span>
                    {{ name }}
                </Link>

                <div class="flex flex-col gap-2 text-center lg:text-left">
                    <p
                        class="text-racing-red text-xs font-bold tracking-[0.2em] uppercase"
                    >
                        Acceso del personal
                    </p>
                    <h1
                        v-if="title"
                        class="text-3xl font-black tracking-tight sm:text-4xl"
                    >
                        {{ title }}
                    </h1>
                    <p
                        v-if="description"
                        class="text-muted-foreground text-sm leading-6"
                    >
                        {{ description }}
                    </p>
                </div>
                <div
                    class="border-border bg-card rounded-2xl border p-5 shadow-xl shadow-black/5 sm:p-7 dark:shadow-black/20"
                >
                    <slot />
                </div>
                <p class="text-muted-foreground text-center text-xs">
                    ¿Necesitas acceso? Solicítalo al administrador del taller.
                </p>
            </div>
        </main>
    </div>
</template>
