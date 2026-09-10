<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import {
    BadgeCheck,
    CalendarDays,
    Clock3,
    MapPin,
    MessageCircle,
    Phone,
    Sparkles,
} from '@lucide/vue';
import { onBeforeUnmount, onMounted, ref } from 'vue';
import AppLogoIcon from '@/components/AppLogoIcon.vue';
import { home } from '@/routes';

const page = usePage();
const name = page.props.name;

const promos = [
    {
        eyebrow: 'Servicio premium para tu moto',
        title: 'Más kilómetros. Menos preocupaciones.',
        description:
            'Mantenimiento, diagnóstico y repuestos para volver a la ruta con confianza.',
        image: 'https://images.unsplash.com/photo-1558981806-ec527fa84c39?auto=format&fit=crop&w=1400&q=85',
        alt: 'Motocicleta deportiva en carretera',
    },
    {
        eyebrow: 'Campaña de temporada',
        title: 'Tu próxima salida empieza aquí.',
        description:
            'Revisión general y recomendaciones claras para cuidar tu máquina.',
        image: 'https://images.unsplash.com/photo-1568772585407-9361f9bf3a87?auto=format&fit=crop&w=1400&q=85',
        alt: 'Motocicleta junto a un taller',
    },
];

const brands = [
    { name: 'Honda', mark: 'H' },
    { name: 'Yamaha', mark: 'Y' },
    { name: 'Kawasaki', mark: 'K' },
    { name: 'Suzuki', mark: 'S' },
    { name: 'Bajaj', mark: 'B' },
];
const activePromo = ref(0);
let promoTimer: ReturnType<typeof setInterval> | undefined;

function nextPromo(): void {
    activePromo.value = (activePromo.value + 1) % promos.length;
}

onMounted(() => {
    promoTimer = setInterval(nextPromo, 7000);
});

onBeforeUnmount(() => {
    if (promoTimer) {
        clearInterval(promoTimer);
    }
});

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
            <Transition name="auth-promo-fade" mode="out-in">
                <div :key="activePromo" class="absolute inset-0">
                    <img
                        :src="promos[activePromo].image"
                        :alt="promos[activePromo].alt"
                        class="size-full object-cover opacity-45"
                    />
                    <div class="absolute inset-0 bg-black/65" />
                    <div
                        class="absolute inset-0 bg-linear-to-r from-black/80 via-black/45 to-black/20"
                    />
                </div>
            </Transition>
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
                    <Sparkles class="size-4" />
                    {{ promos[activePromo].eyebrow }}
                </div>
                <div class="space-y-4">
                    <h1
                        class="max-w-xl text-5xl leading-[1.05] font-black tracking-tight xl:text-6xl"
                    >
                        {{ promos[activePromo].title }}
                    </h1>
                    <p class="max-w-lg text-base leading-7 text-zinc-400">
                        {{ promos[activePromo].description }}
                    </p>
                </div>
                <div class="flex flex-wrap gap-3 text-sm text-zinc-300">
                    <span
                        class="inline-flex items-center gap-2 rounded-lg border border-white/10 bg-white/5 px-3 py-2"
                    >
                        <CalendarDays class="text-racing-yellow size-4" />
                        Reserva tu cita
                    </span>
                    <span
                        class="inline-flex items-center gap-2 rounded-lg border border-white/10 bg-white/5 px-3 py-2"
                    >
                        <BadgeCheck class="text-racing-green size-4" /> Servicio
                        confiable
                    </span>
                </div>
                <div
                    class="flex flex-wrap items-center gap-2 text-xs font-bold text-zinc-300"
                >
                    <span
                        v-for="brand in brands"
                        :key="brand.name"
                        class="inline-flex items-center gap-1.5 rounded-md border border-white/10 bg-white/5 px-2 py-1"
                    >
                        <span
                            class="bg-racing-yellow text-racing-black flex size-4 items-center justify-center rounded-sm text-[10px] font-black"
                            >{{ brand.mark }}</span
                        >{{ brand.name }}
                    </span>
                </div>
            </div>

            <div class="relative z-10 grid gap-3 text-xs sm:grid-cols-3">
                <a
                    href="tel:+51999999999"
                    class="flex items-center gap-2 text-zinc-300 transition-colors hover:text-white"
                >
                    <Phone class="text-racing-yellow size-4" />
                    <span
                        ><strong class="block text-white">Llámanos</strong>+51
                        999 999 999</span
                    >
                </a>
                <a
                    href="https://wa.me/51999999999"
                    target="_blank"
                    rel="noreferrer"
                    class="flex items-center gap-2 text-zinc-300 transition-colors hover:text-white"
                >
                    <MessageCircle class="text-racing-green size-4" />
                    <span
                        ><strong class="block text-white">WhatsApp</strong
                        >Reserva tu cita</span
                    >
                </a>
                <a
                    href="https://maps.google.com/?q=Fredy+Racing+Lima"
                    target="_blank"
                    rel="noreferrer"
                    class="flex items-center gap-2 text-zinc-300 transition-colors hover:text-white"
                >
                    <MapPin class="text-racing-red size-4" />
                    <span
                        ><strong class="block text-white">Cómo llegar</strong
                        >Lima, Perú</span
                    >
                </a>
            </div>
            <div class="relative z-10 flex items-center justify-between gap-4">
                <p class="text-xs tracking-wide text-zinc-500">
                    Fredy Racing · Pasión por la mecánica
                </p>
                <div class="flex gap-2">
                    <span
                        v-for="(_, index) in promos"
                        :key="index"
                        class="h-1.5 rounded-full transition-all"
                        :class="
                            activePromo === index
                                ? 'bg-racing-yellow w-8'
                                : 'w-2 bg-white/40'
                        "
                    />
                </div>
            </div>
        </section>

        <main
            class="relative flex min-h-svh items-center justify-center overflow-hidden px-5 py-10 sm:px-8 lg:px-12"
        >
            <div
                class="from-racing-yellow via-racing-red to-racing-green absolute top-0 left-0 h-1 w-full bg-linear-to-r lg:hidden"
            />
            <div
                class="mx-auto flex w-full max-w-md flex-col justify-center gap-8"
            >
                <Transition name="auth-promo-fade" mode="out-in">
                    <div
                        :key="activePromo"
                        class="relative isolate min-h-40 overflow-hidden rounded-2xl p-5 text-white sm:min-h-48 lg:hidden"
                    >
                        <img
                            :src="promos[activePromo].image"
                            :alt="promos[activePromo].alt"
                            class="absolute inset-0 -z-20 size-full object-cover"
                        />
                        <div class="absolute inset-0 -z-10 bg-black/65" />
                        <div class="relative z-10">
                            <p
                                class="text-racing-yellow flex items-center gap-2 text-[10px] font-black tracking-[.16em] uppercase"
                            >
                                <Sparkles class="size-3" />{{
                                    promos[activePromo].eyebrow
                                }}
                            </p>
                            <p
                                class="mt-3 max-w-xs text-xl leading-tight font-black"
                            >
                                {{ promos[activePromo].title }}
                            </p>
                            <p class="mt-2 text-xs leading-5 text-zinc-300">
                                {{ promos[activePromo].description }}
                            </p>
                        </div>
                    </div>
                </Transition>
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
                    <Clock3 class="mr-1 inline size-3.5" />
                    Atención: lunes a sábado, 8:00 a 18:00 · ¿Necesitas acceso?
                    Solicítalo al administrador.
                </p>
            </div>
        </main>
    </div>
</template>

<style scoped>
.auth-promo-fade-enter-active,
.auth-promo-fade-leave-active {
    transition: opacity 450ms ease;
}

.auth-promo-fade-enter-from,
.auth-promo-fade-leave-to {
    opacity: 0;
}
</style>
