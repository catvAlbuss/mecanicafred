<script setup lang="ts">
import type { HTMLAttributes } from 'vue';

defineOptions({
    inheritAttrs: false,
});

type Props = {
    className?: HTMLAttributes['class'];
};

defineProps<Props>();
</script>

<template>
    <svg
        xmlns="http://www.w3.org/2000/svg"
        viewBox="0 0 48 48"
        :class="className"
        v-bind="$attrs"
    >
        <circle cx="24" cy="24" r="20" fill="currentColor" />
        <path
            d="M15 14h13.5c4.5 0 7.5 2.6 7.5 6.5 0 3-1.8 5.2-4.7 6.1L37 35h-7.1l-5-7.5H21V35h-6V14Zm6 5v3.7h7c1.3 0 2-.7 2-1.9 0-1.1-.7-1.8-2-1.8h-7Z"
            fill="var(--color-racing-yellow)"
        />
        <path
            d="M9 10h8v4H9zM7 17h8v4H7zM9 24h6v4H9z"
            fill="var(--color-racing-red)"
        />
    </svg>
</template>
