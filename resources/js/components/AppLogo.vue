<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import AppLogoIcon from '@/components/AppLogoIcon.vue';

const name = usePage().props.name;
</script>

<template>
    <div
        class="bg-racing-yellow text-racing-black flex aspect-square size-9 items-center justify-center rounded-lg"
    >
        <AppLogoIcon class="size-6" />
    </div>
    <div class="ml-1 grid flex-1 text-left text-sm">
        <span class="mb-0.5 truncate leading-tight font-black tracking-wide">{{
            name
        }}</span>
        <span
            class="text-muted-foreground truncate text-[10px] tracking-widest uppercase"
        >
            Gestión de taller
        </span>
    </div>
</template>
