<script setup lang="ts">
import { Download } from '@lucide/vue';
import { computed, ref } from 'vue';
import { Button } from '@/components/ui/button';
import { barcodeFilename, createBarcodeGeometry } from '@/lib/barcode';

const props = withDefaults(
    defineProps<{
        value: string | null;
        name: string;
        sku: string;
        moduleWidth?: number;
    }>(),
    { moduleWidth: 2 },
);

const svg = ref<SVGSVGElement | null>(null);
const geometry = computed(() =>
    props.value ? createBarcodeGeometry(props.value, props.moduleWidth) : null,
);
const labelWidth = computed(() => Math.max(geometry.value?.width ?? 0, 280));
const barcodeOffset = computed(
    () => (labelWidth.value - (geometry.value?.width ?? 0)) / 2,
);
const displayedName = computed(() =>
    props.name.length > 34 ? `${props.name.slice(0, 33)}…` : props.name,
);

function download(): void {
    if (!svg.value || !geometry.value) {
        return;
    }

    const serialized = new XMLSerializer().serializeToString(svg.value);
    const blob = new Blob(
        [`<?xml version="1.0" encoding="UTF-8"?>\n${serialized}`],
        {
            type: 'image/svg+xml;charset=utf-8',
        },
    );
    const url = URL.createObjectURL(blob);
    const anchor = document.createElement('a');
    anchor.href = url;
    anchor.download = barcodeFilename(props.name, props.sku);
    document.body.appendChild(anchor);
    anchor.click();
    anchor.remove();
    window.setTimeout(() => URL.revokeObjectURL(url), 1000);
}
</script>

<template>
    <div v-if="geometry" class="grid min-w-0 gap-3">
        <div class="overflow-x-auto rounded-xl border bg-white p-3">
            <svg
                ref="svg"
                xmlns="http://www.w3.org/2000/svg"
                :width="labelWidth"
                height="150"
                :viewBox="`0 0 ${labelWidth} 150`"
                role="img"
                :aria-label="`Etiqueta de ${name}, código ${value}`"
                class="mx-auto block h-auto max-w-full bg-white"
            >
                <rect width="100%" height="100%" fill="#ffffff" />
                <text
                    x="50%"
                    y="24"
                    fill="#0d0d0d"
                    font-family="Arial, sans-serif"
                    font-size="16"
                    font-weight="700"
                    text-anchor="middle"
                >
                    {{ displayedName }}
                </text>
                <text
                    x="50%"
                    y="42"
                    fill="#4b5563"
                    font-family="Arial, sans-serif"
                    font-size="10"
                    text-anchor="middle"
                >
                    SKU {{ sku }} · {{ geometry.format }}
                </text>
                <g :transform="`translate(${barcodeOffset} 52)`">
                    <rect
                        v-for="(bar, index) in geometry.bars"
                        :key="index"
                        :x="bar.x"
                        y="0"
                        :width="bar.width"
                        height="68"
                        fill="#000000"
                    />
                </g>
                <text
                    x="50%"
                    y="139"
                    fill="#0d0d0d"
                    font-family="monospace"
                    font-size="13"
                    letter-spacing="2"
                    text-anchor="middle"
                >
                    {{ value }}
                </text>
            </svg>
        </div>
        <Button
            type="button"
            variant="outline"
            class="w-full"
            @click="download"
        >
            <Download />Descargar etiqueta SVG
        </Button>
        <p class="text-muted-foreground text-xs">
            SVG nítido para imprimir. Se conserva EAN-13 cuando sus 13 dígitos
            son válidos; los códigos alfanuméricos se generan como Code 128.
        </p>
    </div>
    <p v-else class="text-muted-foreground text-xs">
        Este código contiene caracteres que no se pueden representar en la
        etiqueta.
    </p>
</template>
