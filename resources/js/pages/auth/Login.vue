<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import {
    ArrowRight,
    ArrowUpRight,
    BatteryCharging,
    Bike,
    CircleCheckBig,
    Clock3,
    Cog,
    Disc3,
    Droplet,
    Gauge,
    LogIn,
    MapPin,
    Menu,
    MessageCircle,
    Moon,
    Phone,
    Route,
    ScanLine,
    Star,
    Sun,
    Timer,
    Wrench,
    X,
    Zap,
} from '@lucide/vue';
import { onBeforeUnmount, onMounted, ref } from 'vue';
import AppLogoIcon from '@/components/AppLogoIcon.vue';
import InputError from '@/components/InputError.vue';
import PasswordInput from '@/components/PasswordInput.vue';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { useAppearance } from '@/composables/useAppearance';
import { store } from '@/routes/login';
import { request } from '@/routes/password';

const props = defineProps<{
    status?: string;
    canResetPassword: boolean;
}>();

/* ---- Datos del negocio ---- */
const address = 'Carretera Central, paradero 15 — Huánuco';
const phones = ['+51 930 955 836', '+51 902 465 531'];
const whatsappText =
    'https://wa.me/51930955836?text=Hola%20Fredy%20Racing%2C%20quiero%20reservar%20una%20cita%20para%20mi%20moto.';
const mapUrl =
    'https://www.google.com/maps/search/?api=1&query=Carretera+Central+paradero+15+Huanuco';
const heroImage =
    'https://images.unsplash.com/photo-1558981806-ec527fa84c39?auto=format&fit=crop&w=1900&q=80';

const ticker = [
    { icon: Route, text: 'Revisión pre-viaje Huánuco – Lima en 40 minutos' },
    { icon: Wrench, text: 'Cambio de aceite + ajuste de cadena esta semana' },
    { icon: MapPin, text: 'Carretera Central, paradero 15 · Huánuco' },
    { icon: MessageCircle, text: 'Reservas por WhatsApp: 930 955 836' },
    { icon: Cog, text: 'Repuestos con garantía: Pulsar, FZ, Italika, Honda' },
];

const navLinks = [
    { href: '#servicios', label: 'Servicios' },
    { href: '#ofertas', label: 'Ofertas' },
    { href: '#taller', label: 'El taller' },
    { href: '#contacto', label: 'Contacto' },
];

const heroService = {
    icon: Gauge,
    title: 'Mantenimiento por kilometraje',
    text: 'El servicio completo: aceite y filtro del grado correcto para la altura, bujía, sincronización, frenos, cadena y 22 puntos de control. Sales con la moto lista para la Central.',
};

const services = [
    {
        icon: Zap,
        title: 'Afinamiento de motor',
        text: 'Carburación o inyección, sincronía y puesta a punto de potencia.',
    },
    {
        icon: Disc3,
        title: 'Frenos y seguridad',
        text: 'Pastillas, discos, purgado y regulación para bajadas largas.',
    },
    {
        icon: Cog,
        title: 'Suspensión y dirección',
        text: 'Barras, retenes, amortiguadores y alineación para carga y curva.',
    },
    {
        icon: Droplet,
        title: 'Cambio de aceite y filtros',
        text: 'Aceite adecuado a tu cilindrada y clima, con registro del servicio.',
    },
    {
        icon: BatteryCharging,
        title: 'Electricidad y diagnóstico',
        text: 'Batería, arranque, luces, sensores y lectura de fallas.',
    },
    {
        icon: Route,
        title: 'Cadena y transmisión',
        text: 'Kit de arrastre, tensión y lubricación. Marcas DID y KMC.',
    },
];

const offers = [
    {
        tag: 'Más pedido',
        title: 'Mantenimiento 125 – 160 cc',
        detail: 'Aceite + filtro + bujía + ajuste general y revisión de frenos.',
        price: 'S/ 90',
        note: 'Repuestos aparte según marca',
    },
    {
        tag: 'Antes del viaje',
        title: 'Revisión pre-viaje · 22 puntos',
        detail: 'Frenos, llantas, luces, cadena, niveles y prueba en ruta.',
        price: 'S/ 45',
        note: 'Gratis si dejas el servicio completo',
    },
    {
        tag: 'Transmisión',
        title: 'Kit de arrastre + instalación',
        detail: 'Piñón, catalina y cadena. Regulación y engrase incluidos.',
        price: 'desde S/ 180',
        note: 'DID / KMC según disponibilidad',
    },
];

const pillars = [
    'Presupuesto por escrito. Sin cobros sorpresa.',
    'Repuestos con garantía y comprobante.',
    'Técnicos de moto, no de todo.',
    'Listo cuando dijimos que estaría.',
];

const steps = [
    {
        n: '01',
        icon: ScanLine,
        title: 'Recepción',
        text: 'Anotamos síntomas, kilometraje y estado general.',
    },
    {
        n: '02',
        icon: Gauge,
        title: 'Diagnóstico',
        text: 'Revisión en banco y prueba en ruta si hace falta.',
    },
    {
        n: '03',
        icon: CircleCheckBig,
        title: 'Presupuesto',
        text: 'Te lo pasamos por escrito. Nada se toca sin tu OK.',
    },
    {
        n: '04',
        icon: Timer,
        title: 'Entrega',
        text: 'Aviso por WhatsApp y el historial del servicio.',
    },
];

const stats = [
    { n: 6500, decimals: 0, suffix: '+', label: 'Motos atendidas' },
    { n: 12, decimals: 0, suffix: '', label: 'Años en la Central' },
    { n: 4.9, decimals: 1, suffix: '', label: 'Promedio de reseñas' },
    { n: 40, decimals: 0, suffix: ' min', label: 'Revisión pre-viaje' },
];

const gallery = [
    {
        src: 'https://images.unsplash.com/photo-1558981806-ec527fa84c39?auto=format&fit=crop&w=900&q=80',
        alt: 'Moto deportiva roja lista para la ruta',
        span: 'row-span-2',
    },
    {
        src: 'https://images.unsplash.com/photo-1568772585407-9361f9bf3a87?auto=format&fit=crop&w=700&q=80',
        alt: 'Moto en el taller',
        span: '',
    },
    {
        src: 'https://images.unsplash.com/photo-1449426468159-d96dbf08f19f?auto=format&fit=crop&w=700&q=80',
        alt: 'Detalle del motor',
        span: '',
    },
    {
        src: 'https://images.unsplash.com/photo-1609630875171-b1321377ee65?auto=format&fit=crop&w=900&q=80',
        alt: 'Motos en el garaje',
        span: 'col-span-2',
    },
];

const testimonials = [
    {
        quote: 'Subo a Cerro de Pasco cada semana. Me hacen la pre-viaje en media hora y nunca me quedé botado.',
        name: 'Carlos M.',
        detail: 'Bajaj Pulsar NS 200',
    },
    {
        quote: 'Explican qué tiene la moto y cuánto cuesta antes de tocar nada. Eso no lo hace cualquiera.',
        name: 'Andrea R.',
        detail: 'Honda Navi',
    },
    {
        quote: 'Cambio de kit de arrastre y frenos. Trabajo limpio y me duró el doble que en otro sitio.',
        name: 'Jorge T.',
        detail: 'Yamaha FZ 2.0',
    },
];

const brands = [
    'Honda',
    'Yamaha',
    'Bajaj',
    'Suzuki',
    'Pulsar',
    'Italika',
    'KTM',
    'Lifan',
    'Hero',
];

const socials = [
    { label: 'Facebook', href: 'https://facebook.com' },
    { label: 'Instagram', href: 'https://instagram.com' },
    { label: 'TikTok', href: 'https://tiktok.com' },
];

/* ---- Tema ---- */
const { resolvedAppearance, updateAppearance } = useAppearance();
function toggleTheme(): void {
    updateAppearance(resolvedAppearance.value === 'dark' ? 'light' : 'dark');
}

/* ---- Modal de acceso ---- */
const loginOpen = ref(false);
function openLogin(): void {
    loginOpen.value = true;
    menuOpen.value = false;
}

/* ---- Scroll / nav ---- */
const scrolled = ref(false);
const menuOpen = ref(false);
const progress = ref(0);
const heroReady = ref(false);

function onScroll(): void {
    const doc = document.documentElement;
    const max = doc.scrollHeight - doc.clientHeight;
    scrolled.value = window.scrollY > 16;
    progress.value = max > 0 ? Math.min(1, window.scrollY / max) : 0;
}

onMounted(() => {
    window.addEventListener('scroll', onScroll, { passive: true });
    onScroll();
    requestAnimationFrame(() => (heroReady.value = true));
    if (props.status || window.location.hash === '#acceso') {
        loginOpen.value = true;
    }
});
onBeforeUnmount(() => window.removeEventListener('scroll', onScroll));

/* ---- Directiva de aparición al hacer scroll ---- */
const reduceMotion =
    typeof window !== 'undefined' &&
    window.matchMedia('(prefers-reduced-motion: reduce)').matches;

const vReveal = {
    mounted(el: HTMLElement) {
        if (reduceMotion) {
            return;
        }
        el.classList.add('reveal');
        const io = new IntersectionObserver(
            ([entry]) => {
                if (entry.isIntersecting) {
                    el.classList.add('reveal-in');
                    io.disconnect();
                }
            },
            { threshold: 0.12, rootMargin: '0px 0px -40px 0px' },
        );
        io.observe(el);
    },
};

const vCountUp = {
    mounted(el: HTMLElement) {
        const to = Number(el.dataset.to ?? '0');
        const decimals = Number(el.dataset.decimals ?? '0');
        const suffix = el.dataset.suffix ?? '';
        const fmt = (v: number) =>
            (decimals
                ? v.toFixed(decimals)
                : Math.round(v).toLocaleString('es-PE')) + suffix;
        el.textContent = fmt(0);
        if (reduceMotion) {
            el.textContent = fmt(to);
            return;
        }
        const io = new IntersectionObserver(
            ([entry]) => {
                if (!entry.isIntersecting) {
                    return;
                }
                io.disconnect();
                const duration = 1300;
                const start = performance.now();
                const tick = (now: number) => {
                    const p = Math.min(1, (now - start) / duration);
                    el.textContent = fmt(to * (1 - Math.pow(1 - p, 3)));
                    if (p < 1) {
                        requestAnimationFrame(tick);
                    }
                };
                requestAnimationFrame(tick);
            },
            { threshold: 0.5 },
        );
        io.observe(el);
    },
};
</script>

<template>
    <Head title="Taller de motos en la Carretera Central — Huánuco">
        <meta
            name="description"
            content="Fredy Racing — taller de motos en la Carretera Central, paradero 15, Huánuco. Mantenimiento, afinamiento, frenos y revisión pre-viaje con presupuesto por escrito."
        />
    </Head>

    <div
        class="bg-background text-foreground min-h-svh scroll-smooth antialiased"
    >
        <div
            class="bg-racing-yellow fixed top-0 left-0 z-60 h-0.5 origin-left"
            :style="{ transform: `scaleX(${progress})` }"
        />

        <!-- Ticker -->
        <div
            class="bg-racing-black text-racing-white relative z-40 overflow-hidden border-b border-white/10"
        >
            <div class="marquee flex py-2 whitespace-nowrap">
                <div
                    v-for="copy in [0, 1]"
                    :key="copy"
                    class="flex shrink-0 items-center"
                >
                    <span
                        v-for="(item, i) in ticker"
                        :key="`${copy}-${i}`"
                        class="mx-5 flex items-center gap-2 text-xs font-semibold text-zinc-300"
                    >
                        <component
                            :is="item.icon"
                            class="text-racing-yellow size-3.5"
                        />
                        {{ item.text }}
                        <span class="text-racing-yellow/50 ml-5">/</span>
                    </span>
                </div>
            </div>
        </div>

        <!-- ====== NAV ====== -->
        <header
            class="text-foreground sticky top-0 z-40 transition-[height,background-color] duration-300"
            :class="
                scrolled || menuOpen
                    ? 'bg-background/90 supports-backdrop-filter:bg-background/75 border-b backdrop-blur'
                    : 'border-b border-transparent'
            "
        >
            <div
                class="mx-auto flex max-w-6xl items-center justify-between px-4 sm:px-6"
                :class="scrolled ? 'h-14' : 'h-16'"
            >
                <a
                    href="#top"
                    class="group flex items-center gap-2.5 font-black tracking-tight uppercase"
                >
                    <span
                        class="bg-racing-yellow text-racing-black flex size-9 items-center justify-center rounded-lg transition-transform group-hover:-rotate-6"
                    >
                        <AppLogoIcon class="size-6" />
                    </span>
                    <span class="leading-none"
                        >Fredy Racing<span
                            class="text-muted-foreground block text-[10px] font-semibold tracking-[0.16em]"
                            >Carretera Central · Huánuco</span
                        ></span
                    >
                </a>

                <nav class="hidden items-center gap-1 lg:flex">
                    <a
                        v-for="link in navLinks"
                        :key="link.href"
                        :href="link.href"
                        class="text-muted-foreground hover:text-foreground hover:bg-muted rounded-md px-3 py-2 text-sm font-semibold transition-colors"
                        >{{ link.label }}</a
                    >
                </nav>

                <div class="flex items-center gap-1.5">
                    <button
                        type="button"
                        class="text-muted-foreground hover:text-foreground hover:bg-muted flex size-9 items-center justify-center rounded-md transition-colors"
                        :aria-label="
                            resolvedAppearance === 'dark'
                                ? 'Modo claro'
                                : 'Modo oscuro'
                        "
                        @click="toggleTheme"
                    >
                        <Sun
                            v-if="resolvedAppearance === 'dark'"
                            class="size-5"
                        />
                        <Moon v-else class="size-5" />
                    </button>
                    <Button
                        size="sm"
                        class="bg-racing-yellow text-racing-black hover:bg-racing-yellow/90 hidden sm:inline-flex"
                        @click="openLogin"
                    >
                        <LogIn class="size-4" />Ingresar
                    </Button>
                    <button
                        type="button"
                        class="text-muted-foreground hover:text-foreground hover:bg-muted flex size-9 items-center justify-center rounded-md transition-colors lg:hidden"
                        :aria-label="menuOpen ? 'Cerrar menú' : 'Abrir menú'"
                        @click="menuOpen = !menuOpen"
                    >
                        <X v-if="menuOpen" class="size-5" />
                        <Menu v-else class="size-5" />
                    </button>
                </div>
            </div>

            <div
                v-if="menuOpen"
                class="bg-background text-foreground border-b lg:hidden"
            >
                <nav class="mx-auto grid max-w-6xl gap-1 px-4 py-3">
                    <a
                        v-for="link in navLinks"
                        :key="link.href"
                        :href="link.href"
                        class="hover:bg-muted rounded-lg px-3 py-2.5 font-semibold"
                        @click="menuOpen = false"
                        >{{ link.label }}</a
                    >
                    <button
                        type="button"
                        class="bg-racing-yellow text-racing-black mt-1 rounded-lg px-3 py-2.5 text-center font-bold"
                        @click="openLogin"
                    >
                        Ingresar al sistema
                    </button>
                </nav>
            </div>
        </header>

        <!-- ====== HERO ====== -->
        <section
            id="top"
            class="relative isolate flex min-h-136 items-center overflow-hidden py-16 sm:min-h-160 sm:py-20 xl:min-h-176"
        >
            <img
                :src="heroImage"
                alt=""
                aria-hidden="true"
                class="absolute inset-0 -z-20 size-full object-cover"
                :class="heroReady ? 'hero-img-in' : 'hero-img'"
            />
            <div
                class="absolute inset-0 -z-10 bg-linear-to-b from-black/80 via-black/60 to-black/90"
            />
            <div
                class="absolute inset-x-0 top-0 -z-10 h-36 bg-linear-to-b from-black/75 to-transparent"
            />
            <div
                class="from-background absolute inset-x-0 bottom-0 -z-10 h-32 bg-linear-to-t to-transparent"
            />

            <!-- Moto que entra corriendo -->
            <div
                class="pointer-events-none absolute bottom-6 left-0 -z-10 w-56 text-white/15 sm:bottom-10 sm:w-72 xl:w-96"
                :class="heroReady ? 'moto-in' : 'moto'"
            >
                <svg viewBox="0 0 300 150" fill="none" class="w-full">
                    <g
                        class="speed"
                        stroke="var(--color-racing-yellow)"
                        stroke-width="5"
                        stroke-linecap="round"
                        opacity="0.55"
                    >
                        <line x1="-40" y1="46" x2="34" y2="46" />
                        <line x1="-60" y1="78" x2="18" y2="78" />
                        <line x1="-30" y1="110" x2="46" y2="110" />
                    </g>
                    <g fill="currentColor">
                        <circle cx="58" cy="108" r="34" />
                        <circle cx="238" cy="108" r="34" />
                        <path
                            d="M58 108 C 58 82 76 68 104 68 L 128 68 L 150 44 L 196 42 C 206 42 212 50 210 60 L 226 66 C 240 70 246 84 240 96 L 238 108 L 172 100 C 138 106 100 112 58 108 Z"
                        />
                    </g>
                    <g
                        stroke="currentColor"
                        stroke-width="10"
                        stroke-linecap="round"
                    >
                        <line x1="226" y1="66" x2="238" y2="108" />
                        <line x1="196" y1="42" x2="216" y2="28" />
                        <path
                            d="M96 110 L 140 122 L 176 122"
                            stroke-width="8"
                            fill="none"
                        />
                    </g>
                </svg>
            </div>

            <div
                class="hero-stagger relative mx-auto w-full max-w-6xl px-4 text-white sm:px-6"
                :class="{ ready: heroReady }"
            >
                <span
                    class="inline-flex w-fit items-center gap-2 rounded-full border border-white/20 bg-white/5 px-3 py-1 text-[11px] font-bold tracking-[0.16em] uppercase backdrop-blur"
                >
                    <span
                        class="bg-racing-red size-1.5 animate-pulse rounded-full"
                    />
                    Abierto · Lun a Sáb 8:00–18:00
                </span>

                <h1
                    class="mt-6 max-w-3xl text-[2.7rem] leading-[0.95] font-black tracking-[-0.02em] sm:text-6xl lg:text-7xl"
                >
                    La Central
                    <span class="relative inline-block">
                        <span class="relative z-10">no perdona</span>
                        <svg
                            class="text-racing-yellow absolute -bottom-1 left-0 h-[0.35em] w-full"
                            viewBox="0 0 200 12"
                            preserveAspectRatio="none"
                            aria-hidden="true"
                        >
                            <path
                                d="M2 8 C 50 2, 150 2, 198 7"
                                stroke="currentColor"
                                stroke-width="5"
                                fill="none"
                                stroke-linecap="round"
                            />
                        </svg> </span
                    ><br />tu moto sí puede.
                </h1>

                <p class="mt-6 max-w-xl text-base text-zinc-200 sm:text-lg">
                    Taller de motos en la
                    <strong class="text-white">Carretera Central</strong>,
                    paradero 15, Huánuco. Mantenimiento, afinamiento y frenos
                    con presupuesto por escrito y repuestos con garantía.
                </p>

                <div class="mt-8 flex flex-wrap gap-3">
                    <Button
                        as-child
                        size="lg"
                        class="bg-racing-yellow text-racing-black hover:bg-racing-yellow/90 h-12 text-base"
                    >
                        <a
                            :href="whatsappText"
                            target="_blank"
                            rel="noreferrer"
                        >
                            <MessageCircle class="size-5" />Reservar por
                            WhatsApp
                            <ArrowRight class="size-4" />
                        </a>
                    </Button>
                    <Button
                        size="lg"
                        variant="outline"
                        class="h-12 border-white/30 bg-white/5 text-base text-white backdrop-blur hover:bg-white/15 hover:text-white"
                        @click="openLogin"
                    >
                        <LogIn class="size-4" />Ingresar al sistema
                    </Button>
                </div>

                <dl
                    class="mt-10 grid max-w-2xl grid-cols-2 gap-3 md:grid-cols-4"
                >
                    <div
                        v-for="stat in stats"
                        :key="stat.label"
                        class="rounded-xl border border-white/15 bg-white/10 p-3 backdrop-blur"
                    >
                        <dt
                            v-count-up
                            :data-to="stat.n"
                            :data-decimals="stat.decimals"
                            :data-suffix="stat.suffix"
                            class="text-2xl font-black tabular-nums"
                        >
                            {{ stat.n }}
                        </dt>
                        <dd class="mt-0.5 text-xs text-zinc-300">
                            {{ stat.label }}
                        </dd>
                    </div>
                </dl>
            </div>
        </section>

        <!-- Marquee de marcas -->
        <div class="bg-muted/60 overflow-hidden border-y py-4">
            <div
                class="marquee marquee-slow flex items-center whitespace-nowrap"
            >
                <div
                    v-for="copy in [0, 1]"
                    :key="copy"
                    class="flex shrink-0 items-center"
                >
                    <span
                        v-for="brand in brands"
                        :key="`${copy}-${brand}`"
                        class="text-muted-foreground/70 mx-6 text-lg font-black tracking-tight uppercase"
                        >{{ brand }}</span
                    >
                </div>
            </div>
        </div>

        <!-- ====== SERVICIOS ====== -->
        <section id="servicios" class="scroll-mt-24 py-20 lg:py-28">
            <div class="mx-auto max-w-6xl px-4 sm:px-6">
                <div
                    v-reveal
                    class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between"
                >
                    <div>
                        <p
                            class="text-muted-foreground text-xs font-black tracking-[0.2em]"
                        >
                            01 — SERVICIOS
                        </p>
                        <h2
                            class="mt-2 text-3xl font-black tracking-tight sm:text-4xl"
                        >
                            Lo que resolvemos
                        </h2>
                    </div>
                    <p class="text-muted-foreground max-w-sm text-sm">
                        Motos lineales, scooters y de trabajo. ¿No lo ves aquí?
                        Escríbenos y lo revisamos.
                    </p>
                </div>

                <div class="mt-10 grid gap-4 lg:grid-cols-[1.15fr_1fr]">
                    <article
                        v-reveal
                        class="bg-foreground text-background relative flex flex-col justify-between overflow-hidden rounded-2xl p-7 sm:p-9"
                    >
                        <div
                            class="checker pointer-events-none absolute -right-8 -bottom-8 size-44 opacity-10"
                        />
                        <div class="relative">
                            <component :is="heroService.icon" class="size-9" />
                            <h3 class="mt-5 text-2xl font-black">
                                {{ heroService.title }}
                            </h3>
                            <p class="text-background/70 mt-3 max-w-md text-sm">
                                {{ heroService.text }}
                            </p>
                        </div>
                        <a
                            :href="whatsappText"
                            target="_blank"
                            rel="noreferrer"
                            class="relative mt-8 inline-flex items-center gap-2 text-sm font-bold underline-offset-4 hover:underline"
                        >
                            Reservar este servicio<ArrowUpRight
                                class="size-4"
                            />
                        </a>
                    </article>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <article
                            v-for="service in services"
                            :key="service.title"
                            v-reveal
                            class="group hover:border-racing-yellow bg-card rounded-2xl border p-5 transition-colors"
                        >
                            <component
                                :is="service.icon"
                                class="text-racing-yellow size-6 transition-transform group-hover:-translate-y-0.5"
                            />
                            <h3 class="mt-4 font-black">{{ service.title }}</h3>
                            <p class="text-muted-foreground mt-1.5 text-sm">
                                {{ service.text }}
                            </p>
                        </article>
                    </div>
                </div>
            </div>
        </section>

        <!-- ====== OFERTAS ====== -->
        <section
            id="ofertas"
            class="bg-muted/60 scroll-mt-24 border-y py-20 lg:py-28"
        >
            <div class="mx-auto max-w-6xl px-4 sm:px-6">
                <div v-reveal>
                    <p
                        class="text-muted-foreground text-xs font-black tracking-[0.2em]"
                    >
                        02 — OFERTAS
                    </p>
                    <h2
                        class="mt-2 text-3xl font-black tracking-tight sm:text-4xl"
                    >
                        Precios claros esta semana
                    </h2>
                    <p class="text-muted-foreground mt-3 max-w-lg text-sm">
                        Mano de obra incluida. Los repuestos se cotizan según la
                        marca y el modelo de tu moto.
                    </p>
                </div>

                <div
                    v-reveal
                    class="mt-10 grid gap-4 sm:grid-cols-2 lg:grid-cols-3"
                >
                    <article
                        v-for="(offer, i) in offers"
                        :key="offer.title"
                        class="bg-card flex flex-col rounded-2xl border p-6"
                        :class="
                            i === 0
                                ? 'ring-racing-yellow ring-2 sm:col-span-2 lg:col-span-1'
                                : ''
                        "
                    >
                        <span
                            class="bg-racing-yellow/15 text-racing-yellow w-fit rounded-full px-2.5 py-1 text-[11px] font-black tracking-wide uppercase"
                            >{{ offer.tag }}</span
                        >
                        <h3 class="mt-4 text-lg font-black">
                            {{ offer.title }}
                        </h3>
                        <p class="text-muted-foreground mt-2 flex-1 text-sm">
                            {{ offer.detail }}
                        </p>
                        <p class="mt-5 text-3xl font-black">
                            {{ offer.price }}
                        </p>
                        <p class="text-muted-foreground mt-1 text-xs">
                            {{ offer.note }}
                        </p>
                        <Button
                            as-child
                            class="bg-racing-yellow text-racing-black hover:bg-racing-yellow/90 mt-5"
                        >
                            <a
                                :href="whatsappText"
                                target="_blank"
                                rel="noreferrer"
                                >Reservar<ArrowRight class="size-4"
                            /></a>
                        </Button>
                    </article>
                </div>
            </div>
        </section>

        <!-- ====== MANIFIESTO ====== -->
        <section
            class="bg-foreground text-background relative overflow-hidden py-20 lg:py-28"
        >
            <div
                class="checker pointer-events-none absolute inset-y-0 right-0 w-40 opacity-10"
            />
            <div class="relative mx-auto max-w-6xl px-4 sm:px-6">
                <div
                    class="grid gap-10 lg:grid-cols-[0.9fr_1.1fr] lg:items-center"
                >
                    <h2
                        v-reveal
                        class="text-3xl font-black tracking-tight sm:text-4xl lg:text-5xl"
                    >
                        No te vendemos lo que
                        <span
                            class="decoration-racing-yellow underline decoration-[6px] underline-offset-[6px]"
                            >no necesitas.</span
                        >
                    </h2>
                    <ul v-reveal class="grid gap-4 sm:grid-cols-2">
                        <li
                            v-for="pillar in pillars"
                            :key="pillar"
                            class="border-background/15 bg-background/5 flex items-start gap-3 rounded-xl border p-4 text-sm"
                        >
                            <CircleCheckBig class="mt-0.5 size-5 shrink-0" />
                            {{ pillar }}
                        </li>
                    </ul>
                </div>
            </div>
        </section>

        <!-- ====== PROCESO ====== -->
        <section class="py-20 lg:py-28">
            <div class="mx-auto max-w-6xl px-4 sm:px-6">
                <div v-reveal>
                    <p
                        class="text-muted-foreground text-xs font-black tracking-[0.2em]"
                    >
                        03 — CÓMO TRABAJAMOS
                    </p>
                    <h2
                        class="mt-2 text-3xl font-black tracking-tight sm:text-4xl"
                    >
                        De la recepción a la ruta
                    </h2>
                </div>

                <ol
                    v-reveal
                    class="mt-10 grid gap-4 sm:grid-cols-2 lg:grid-cols-4"
                >
                    <li
                        v-for="step in steps"
                        :key="step.n"
                        class="bg-card relative rounded-2xl border p-6"
                    >
                        <span
                            class="text-muted-foreground/25 absolute top-4 right-5 text-3xl font-black"
                            >{{ step.n }}</span
                        >
                        <component
                            :is="step.icon"
                            class="text-racing-yellow size-7"
                        />
                        <h3 class="mt-4 font-black">{{ step.title }}</h3>
                        <p class="text-muted-foreground mt-1.5 text-sm">
                            {{ step.text }}
                        </p>
                    </li>
                </ol>
            </div>
        </section>

        <!-- ====== TALLER ====== -->
        <section
            id="taller"
            class="bg-muted/60 scroll-mt-24 border-y py-20 lg:py-28"
        >
            <div
                class="mx-auto grid max-w-6xl items-center gap-12 px-4 sm:px-6 lg:grid-cols-2"
            >
                <div v-reveal>
                    <p
                        class="text-muted-foreground text-xs font-black tracking-[0.2em]"
                    >
                        04 — EL TALLER
                    </p>
                    <h2
                        class="mt-2 text-3xl font-black tracking-tight sm:text-4xl"
                    >
                        Nacimos en la Carretera Central
                    </h2>
                    <p class="text-muted-foreground mt-4">
                        Empezamos arreglando las motos de los transportistas del
                        paradero. Doce años después seguimos igual: se revisa,
                        se explica y recién ahí se decide. La moto sale con su
                        historial de servicio.
                    </p>

                    <dl class="mt-8 grid grid-cols-2 gap-4 sm:grid-cols-4">
                        <div v-for="stat in stats" :key="stat.label">
                            <dt
                                v-count-up
                                :data-to="stat.n"
                                :data-decimals="stat.decimals"
                                :data-suffix="stat.suffix"
                                class="text-racing-yellow text-2xl font-black tabular-nums"
                            >
                                {{ stat.n }}
                            </dt>
                            <dd class="text-muted-foreground mt-1 text-xs">
                                {{ stat.label }}
                            </dd>
                        </div>
                    </dl>

                    <div class="mt-8 flex flex-wrap gap-3">
                        <Button
                            as-child
                            class="bg-racing-yellow text-racing-black hover:bg-racing-yellow/90"
                        >
                            <a :href="`tel:${phones[0].replace(/\s/g, '')}`"
                                ><Phone class="size-4" />{{ phones[0] }}</a
                            >
                        </Button>
                        <Button as-child variant="outline">
                            <a :href="`tel:${phones[1].replace(/\s/g, '')}`">{{
                                phones[1]
                            }}</a>
                        </Button>
                    </div>
                </div>

                <div
                    v-reveal
                    class="grid auto-rows-32 grid-cols-2 gap-3 sm:auto-rows-40"
                >
                    <img
                        v-for="image in gallery"
                        :key="image.src"
                        :src="image.src"
                        :alt="image.alt"
                        loading="lazy"
                        class="size-full rounded-xl border object-cover transition-transform duration-500 hover:scale-[1.03]"
                        :class="image.span"
                    />
                </div>
            </div>
        </section>

        <!-- ====== RESEÑAS ====== -->
        <section class="py-20 lg:py-28">
            <div class="mx-auto max-w-6xl px-4 sm:px-6">
                <div v-reveal>
                    <p
                        class="text-muted-foreground text-xs font-black tracking-[0.2em]"
                    >
                        05 — RESEÑAS
                    </p>
                    <h2
                        class="mt-2 text-3xl font-black tracking-tight sm:text-4xl"
                    >
                        Motociclistas de Huánuco
                    </h2>
                </div>
                <div
                    v-reveal
                    class="mt-10 grid gap-4 sm:grid-cols-2 lg:grid-cols-3"
                >
                    <figure
                        v-for="item in testimonials"
                        :key="item.name"
                        class="bg-card flex flex-col rounded-2xl border p-6"
                    >
                        <div class="text-racing-yellow flex gap-0.5">
                            <Star
                                v-for="n in 5"
                                :key="n"
                                class="size-4 fill-current"
                            />
                        </div>
                        <blockquote class="mt-4 flex-1 text-sm leading-relaxed">
                            “{{ item.quote }}”
                        </blockquote>
                        <figcaption class="mt-5 border-t pt-4">
                            <p class="font-bold">{{ item.name }}</p>
                            <p class="text-muted-foreground text-xs">
                                {{ item.detail }}
                            </p>
                        </figcaption>
                    </figure>
                </div>
            </div>
        </section>

        <!-- ====== CONTACTO ====== -->
        <section
            id="contacto"
            class="bg-muted/60 scroll-mt-24 border-t py-20 lg:py-28"
        >
            <div
                class="mx-auto grid max-w-6xl gap-10 px-4 sm:px-6 lg:grid-cols-2"
            >
                <div v-reveal>
                    <p
                        class="text-muted-foreground text-xs font-black tracking-[0.2em]"
                    >
                        06 — CONTACTO
                    </p>
                    <h2
                        class="mt-2 text-3xl font-black tracking-tight sm:text-4xl"
                    >
                        Pasa por el taller
                    </h2>
                    <p class="text-muted-foreground mt-3">
                        Estamos sobre la vía, en el paradero 15. Deja la moto y
                        te avisamos cuando esté lista.
                    </p>

                    <div class="mt-8 grid gap-3">
                        <a
                            :href="mapUrl"
                            target="_blank"
                            rel="noreferrer"
                            class="bg-card hover:border-foreground/25 flex items-start gap-3 rounded-xl border p-4 transition-colors"
                        >
                            <MapPin
                                class="text-racing-red mt-0.5 size-5 shrink-0"
                            />
                            <span
                                ><strong class="block">Dirección</strong
                                ><span class="text-muted-foreground text-sm">{{
                                    address
                                }}</span></span
                            >
                        </a>
                        <div class="grid gap-3 sm:grid-cols-2">
                            <a
                                v-for="p in phones"
                                :key="p"
                                :href="`tel:${p.replace(/\s/g, '')}`"
                                class="bg-card hover:border-foreground/25 flex items-center gap-3 rounded-xl border p-4 transition-colors"
                            >
                                <Phone
                                    class="text-racing-yellow size-5 shrink-0"
                                />
                                <span class="text-sm font-semibold">{{
                                    p
                                }}</span>
                            </a>
                        </div>
                        <a
                            :href="whatsappText"
                            target="_blank"
                            rel="noreferrer"
                            class="bg-card hover:border-foreground/25 flex items-center gap-3 rounded-xl border p-4 transition-colors"
                        >
                            <MessageCircle
                                class="text-racing-green size-5 shrink-0"
                            />
                            <span
                                ><strong class="block">WhatsApp</strong
                                ><span class="text-muted-foreground text-sm"
                                    >Reservas y consultas</span
                                ></span
                            >
                        </a>
                        <div
                            class="bg-card flex items-center gap-3 rounded-xl border p-4"
                        >
                            <Clock3
                                class="text-racing-yellow size-5 shrink-0"
                            />
                            <span
                                ><strong class="block">Horario</strong
                                ><span class="text-muted-foreground text-sm"
                                    >Lunes a sábado · 8:00 – 18:00</span
                                ></span
                            >
                        </div>
                    </div>
                </div>

                <div
                    v-reveal
                    class="relative flex min-h-72 flex-col justify-end overflow-hidden rounded-2xl border p-6"
                >
                    <img
                        src="https://images.unsplash.com/photo-1568772585407-9361f9bf3a87?auto=format&fit=crop&w=1000&q=80"
                        alt="Taller Fredy Racing"
                        class="absolute inset-0 size-full object-cover"
                    />
                    <div
                        class="absolute inset-0 bg-linear-to-t from-black/85 via-black/40 to-transparent"
                    />
                    <div class="relative text-white">
                        <p class="flex items-center gap-2 font-black">
                            <Bike class="text-racing-yellow size-5" />Fredy
                            Racing
                        </p>
                        <p class="mt-1 text-sm text-zinc-300">{{ address }}</p>
                        <Button
                            as-child
                            size="sm"
                            class="bg-racing-yellow text-racing-black hover:bg-racing-yellow/90 mt-4"
                        >
                            <a :href="mapUrl" target="_blank" rel="noreferrer"
                                >Cómo llegar<ArrowUpRight class="size-4"
                            /></a>
                        </Button>
                    </div>
                </div>
            </div>
        </section>

        <!-- ====== FOOTER ====== -->
        <footer class="bg-background border-t">
            <div
                class="mx-auto flex max-w-6xl flex-col gap-8 px-4 py-12 sm:px-6 md:flex-row md:justify-between"
            >
                <div class="max-w-sm">
                    <div
                        class="flex items-center gap-2.5 font-black tracking-tight uppercase"
                    >
                        <span
                            class="bg-racing-yellow text-racing-black flex size-9 items-center justify-center rounded-lg"
                        >
                            <AppLogoIcon class="size-6" />
                        </span>
                        Fredy Racing
                    </div>
                    <p class="text-muted-foreground mt-3 text-sm">
                        Taller de motos en la Carretera Central, paradero 15 —
                        Huánuco. Mantenimiento honesto y repuestos con garantía.
                    </p>
                    <div class="mt-4 flex flex-wrap gap-2">
                        <a
                            v-for="social in socials"
                            :key="social.label"
                            :href="social.href"
                            target="_blank"
                            rel="noreferrer"
                            class="hover:border-foreground/25 rounded-lg border px-3 py-1.5 text-xs font-semibold transition-colors"
                            >{{ social.label }}</a
                        >
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-10 text-sm sm:gap-16">
                    <div>
                        <p
                            class="text-muted-foreground text-xs font-black tracking-[0.16em]"
                        >
                            EXPLORA
                        </p>
                        <ul class="mt-3 grid gap-2">
                            <li v-for="link in navLinks" :key="link.href">
                                <a
                                    :href="link.href"
                                    class="text-muted-foreground hover:text-foreground"
                                    >{{ link.label }}</a
                                >
                            </li>
                        </ul>
                    </div>
                    <div>
                        <p
                            class="text-muted-foreground text-xs font-black tracking-[0.16em]"
                        >
                            PERSONAL
                        </p>
                        <ul class="mt-3 grid gap-2">
                            <li>
                                <button
                                    type="button"
                                    class="text-muted-foreground hover:text-foreground"
                                    @click="openLogin"
                                >
                                    Ingresar al sistema
                                </button>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
            <div class="border-t py-5">
                <p
                    class="text-muted-foreground mx-auto max-w-6xl px-4 text-xs sm:px-6"
                >
                    © {{ new Date().getFullYear() }} Fredy Racing · Huánuco,
                    Perú
                </p>
            </div>
        </footer>

        <!-- WhatsApp flotante -->
        <a
            :href="whatsappText"
            target="_blank"
            rel="noreferrer"
            aria-label="Escríbenos por WhatsApp"
            class="bg-racing-green fixed right-4 bottom-4 z-40 flex size-14 items-center justify-center rounded-full text-white shadow-lg transition-transform hover:scale-105 sm:right-6 sm:bottom-6"
        >
            <MessageCircle class="size-7" />
        </a>

        <!-- ====== MODAL DE ACCESO ====== -->
        <Dialog v-model:open="loginOpen">
            <DialogContent class="overflow-hidden p-0 sm:max-w-md">
                <div
                    class="from-racing-yellow via-racing-red to-racing-green h-1 bg-linear-to-r"
                />
                <div class="p-6 sm:p-8">
                    <DialogHeader>
                        <DialogTitle
                            class="flex items-center gap-2 text-xl font-black"
                        >
                            <span
                                class="bg-racing-yellow text-racing-black flex size-7 items-center justify-center rounded-md"
                            >
                                <AppLogoIcon class="size-5" />
                            </span>
                            Ingresa al sistema
                        </DialogTitle>
                        <DialogDescription>
                            Inventario, compras, caja y ventas de Fredy Racing.
                        </DialogDescription>
                    </DialogHeader>

                    <div
                        v-if="status"
                        class="text-racing-green bg-racing-green/10 mt-4 rounded-lg px-3 py-2 text-sm font-medium"
                    >
                        {{ status }}
                    </div>

                    <Form
                        v-bind="store.form()"
                        :reset-on-success="['password']"
                        v-slot="{ errors, processing }"
                        class="mt-6 flex flex-col gap-5"
                    >
                        <div class="grid gap-2">
                            <Label for="email">Correo electrónico</Label>
                            <Input
                                id="email"
                                type="email"
                                name="email"
                                required
                                autofocus
                                :tabindex="1"
                                autocomplete="email"
                                placeholder="nombre@fredyracing.com"
                            />
                            <InputError :message="errors.email" />
                        </div>

                        <div class="grid gap-2">
                            <div
                                class="flex flex-wrap items-center justify-between gap-x-3"
                            >
                                <Label for="password">Contraseña</Label>
                                <Link
                                    v-if="canResetPassword"
                                    :href="request()"
                                    class="text-muted-foreground hover:text-foreground text-xs font-semibold underline-offset-4 hover:underline"
                                    :tabindex="5"
                                >
                                    ¿Olvidaste tu contraseña?
                                </Link>
                            </div>
                            <PasswordInput
                                id="password"
                                name="password"
                                required
                                :tabindex="2"
                                autocomplete="current-password"
                                placeholder="Tu contraseña"
                            />
                            <InputError :message="errors.password" />
                        </div>

                        <Label
                            for="remember"
                            class="text-muted-foreground flex items-center gap-3 text-sm"
                        >
                            <Checkbox
                                id="remember"
                                name="remember"
                                :tabindex="3"
                            />
                            Mantener la sesión iniciada
                        </Label>

                        <Button
                            type="submit"
                            class="bg-racing-yellow text-racing-black hover:bg-racing-yellow/90 h-11 w-full text-base"
                            :tabindex="4"
                            :disabled="processing"
                            data-test="login-button"
                        >
                            <Spinner v-if="processing" />
                            Ingresar
                        </Button>
                    </Form>

                    <p
                        class="text-muted-foreground mt-5 border-t pt-4 text-center text-xs"
                    >
                        ¿Eres cliente?
                        <a
                            :href="whatsappText"
                            target="_blank"
                            rel="noreferrer"
                            class="text-foreground font-semibold underline-offset-2 hover:underline"
                            >Reserva tu cita por WhatsApp</a
                        >
                    </p>
                </div>
            </DialogContent>
        </Dialog>
    </div>
</template>

<style scoped>
.reveal {
    opacity: 0;
    transform: translateY(28px);
    transition:
        opacity 0.7s cubic-bezier(0.16, 1, 0.3, 1),
        transform 0.7s cubic-bezier(0.16, 1, 0.3, 1);
}
.reveal-in {
    opacity: 1;
    transform: none;
}

/* Hero: imagen y contenido al cargar */
.hero-img {
    transform: scale(1.12);
    opacity: 0;
}
.hero-img-in {
    transform: scale(1);
    opacity: 1;
    transition:
        transform 1.4s cubic-bezier(0.16, 1, 0.3, 1),
        opacity 0.8s ease;
}

.hero-stagger > * {
    opacity: 0;
    transform: translateY(20px);
}
.hero-stagger.ready > * {
    opacity: 1;
    transform: none;
    transition:
        opacity 0.6s ease,
        transform 0.6s cubic-bezier(0.16, 1, 0.3, 1);
}
.hero-stagger.ready > *:nth-child(1) {
    transition-delay: 0.15s;
}
.hero-stagger.ready > *:nth-child(2) {
    transition-delay: 0.25s;
}
.hero-stagger.ready > *:nth-child(3) {
    transition-delay: 0.35s;
}
.hero-stagger.ready > *:nth-child(4) {
    transition-delay: 0.45s;
}
.hero-stagger.ready > *:nth-child(5) {
    transition-delay: 0.55s;
}

/* Moto que entra corriendo */
.moto {
    transform: translateX(-140%);
    opacity: 0;
}
.moto-in {
    transform: translateX(0);
    opacity: 1;
    transition:
        transform 1.2s cubic-bezier(0.22, 1, 0.36, 1) 0.2s,
        opacity 0.4s ease 0.2s;
}
.moto-in .speed {
    animation: streak 0.7s ease-out 0.15s 2;
}
@keyframes streak {
    0% {
        opacity: 0;
        transform: translateX(40px);
    }
    35% {
        opacity: 0.7;
    }
    100% {
        opacity: 0;
        transform: translateX(-60px);
    }
}

/* Marquesinas */
.marquee {
    animation: marquee 34s linear infinite;
}
.marquee-slow {
    animation-duration: 48s;
}
@keyframes marquee {
    to {
        transform: translateX(-50%);
    }
}

.checker {
    background-image:
        repeating-linear-gradient(
            45deg,
            currentColor 0 12px,
            transparent 12px 24px
        ),
        repeating-linear-gradient(
            -45deg,
            currentColor 0 12px,
            transparent 12px 24px
        );
    color: var(--color-racing-yellow);
}

@media (prefers-reduced-motion: reduce) {
    .reveal,
    .hero-img,
    .hero-stagger > *,
    .moto {
        opacity: 1 !important;
        transform: none !important;
        transition: none !important;
    }
    .marquee,
    .moto-in .speed {
        animation: none;
    }
}
</style>
