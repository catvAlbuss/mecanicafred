<script setup lang="ts">
import { computed } from 'vue';

const props = withDefaults(
    defineProps<{
        value: string | null;
        moduleWidth?: number;
        height?: number;
    }>(),
    { moduleWidth: 2, height: 64 },
);

const L = [
    '0001101',
    '0011001',
    '0010011',
    '0111101',
    '0100011',
    '0110001',
    '0101111',
    '0111011',
    '0110111',
    '0001011',
];
const G = [
    '0100111',
    '0110011',
    '0011011',
    '0100001',
    '0011101',
    '0111001',
    '0000101',
    '0010001',
    '0001001',
    '0010111',
];
const R = [
    '1110010',
    '1100110',
    '1101100',
    '1000010',
    '1011100',
    '1001110',
    '1010000',
    '1000100',
    '1001000',
    '1110100',
];
const PARITY = [
    'LLLLLL',
    'LLGLGG',
    'LLGGLG',
    'LLGGGL',
    'LGLLGG',
    'LGGLLG',
    'LGGGLL',
    'LGLGLG',
    'LGLGGL',
    'LGGLGL',
];

const digits = computed(() =>
    /^\d{13}$/.test(props.value ?? '')
        ? (props.value as string).split('').map(Number)
        : null,
);

const modules = computed(() => {
    const d = digits.value;
    if (!d) return '';

    let bits = '101';
    const parity = PARITY[d[0]];

    for (let i = 0; i < 6; i++) {
        bits += parity[i] === 'L' ? L[d[i + 1]] : G[d[i + 1]];
    }

    bits += '01010';

    for (let i = 7; i < 13; i++) {
        bits += R[d[i]];
    }

    return bits + '101';
});

const bars = computed(() => {
    const out: { x: number; width: number }[] = [];
    const bits = modules.value;

    for (let i = 0; i < bits.length; i++) {
        if (bits[i] !== '1') continue;
        const last = out[out.length - 1];
        if (last && last.x + last.width === i * props.moduleWidth) {
            last.width += props.moduleWidth;
        } else {
            out.push({ x: i * props.moduleWidth, width: props.moduleWidth });
        }
    }

    return out;
});

const totalWidth = computed(() => 95 * props.moduleWidth);
</script>

<template>
    <figure v-if="digits" class="inline-flex flex-col items-center gap-1">
        <svg
            :width="totalWidth"
            :height="height"
            :viewBox="`0 0 ${totalWidth} ${height}`"
            role="img"
            :aria-label="`Código de barras EAN-13 ${value}`"
            class="bg-white"
        >
            <rect
                v-for="(bar, index) in bars"
                :key="index"
                :x="bar.x"
                y="0"
                :width="bar.width"
                :height="height"
                fill="#000"
            />
        </svg>
        <figcaption
            class="text-racing-black font-mono text-xs tracking-[.3em] tabular-nums"
        >
            {{ value }}
        </figcaption>
    </figure>
    <p v-else class="text-muted-foreground text-xs">Sin código de barras</p>
</template>
