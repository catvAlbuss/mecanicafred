<script setup lang="ts">
import type { BrowserMultiFormatReader } from '@zxing/library';
import { LoaderCircle, ScanBarcode, X } from '@lucide/vue';
import { nextTick, onBeforeUnmount, ref, useTemplateRef, watch } from 'vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';

const props = withDefaults(
    defineProps<{
        title?: string;
        description?: string;
        triggerLabel?: string;
        triggerVariant?: 'default' | 'outline' | 'secondary';
        triggerSize?: 'default' | 'sm';
    }>(),
    {
        title: 'Escanear código de barras',
        description:
            'Apunta la cámara al código del producto. Puedes escanear varios seguidos.',
        triggerLabel: 'Escanear',
        triggerVariant: 'outline',
        triggerSize: 'default',
    },
);

const emit = defineEmits<{ detected: [code: string] }>();

const open = ref(false);
const starting = ref(false);
const errorMessage = ref('');
const video = useTemplateRef<HTMLVideoElement>('video');

let reader: BrowserMultiFormatReader | null = null;
let lastCode = '';
let lastAt = 0;

function stop(): void {
    reader?.reset();
}

async function start(): Promise<void> {
    errorMessage.value = '';

    if (!window.isSecureContext) {
        errorMessage.value =
            'La cámara solo funciona en HTTPS o en localhost. Abre el sistema con una dirección segura.';

        return;
    }

    if (!navigator.mediaDevices?.getUserMedia) {
        errorMessage.value =
            'Este dispositivo o navegador no permite usar la cámara.';

        return;
    }

    starting.value = true;

    try {
        const { BarcodeFormat, BrowserMultiFormatReader, DecodeHintType } =
            await import('@zxing/library');

        if (reader === null) {
            const hints = new Map();
            hints.set(DecodeHintType.POSSIBLE_FORMATS, [
                BarcodeFormat.EAN_13,
                BarcodeFormat.EAN_8,
                BarcodeFormat.UPC_A,
                BarcodeFormat.CODE_128,
                BarcodeFormat.CODE_39,
                BarcodeFormat.QR_CODE,
            ]);
            reader = new BrowserMultiFormatReader(hints);
        }

        await nextTick();

        if (video.value === null) {
            return;
        }

        await reader.decodeFromConstraints(
            { video: { facingMode: { ideal: 'environment' } } },
            video.value,
            (result) => {
                if (!result) {
                    return;
                }

                const code = result.getText().trim();
                const now = Date.now();

                if (code === lastCode && now - lastAt < 1500) {
                    return;
                }

                lastCode = code;
                lastAt = now;
                navigator.vibrate?.(60);
                emit('detected', code);
            },
        );
    } catch (error) {
        errorMessage.value =
            error instanceof DOMException && error.name === 'NotAllowedError'
                ? 'Permiso de cámara denegado. Habilítalo en los ajustes del navegador.'
                : 'No se pudo iniciar la cámara. Revisa que ninguna otra app la esté usando.';
    } finally {
        starting.value = false;
    }
}

watch(
    open,
    (isOpen) => {
        if (isOpen) {
            lastCode = '';
            void start();
        } else {
            stop();
        }
    },
    { flush: 'post' },
);

onBeforeUnmount(stop);
</script>

<template>
    <Dialog v-model:open="open">
        <DialogTrigger as-child>
            <slot name="trigger">
                <Button
                    type="button"
                    :variant="props.triggerVariant"
                    :size="props.triggerSize"
                >
                    <ScanBarcode />{{ props.triggerLabel }}
                </Button>
            </slot>
        </DialogTrigger>
        <DialogContent class="sm:max-w-md">
            <DialogHeader>
                <DialogTitle>{{ props.title }}</DialogTitle>
                <DialogDescription>{{ props.description }}</DialogDescription>
            </DialogHeader>
            <div
                class="relative aspect-square overflow-hidden rounded-xl border bg-black"
            >
                <video
                    ref="video"
                    class="size-full object-cover"
                    playsinline
                    muted
                ></video>
                <div
                    class="border-racing-yellow/80 pointer-events-none absolute inset-6 rounded-lg border-2"
                ></div>
                <div
                    v-if="starting"
                    class="absolute inset-0 flex items-center justify-center gap-2 text-sm text-white"
                >
                    <LoaderCircle class="animate-spin" />Iniciando cámara…
                </div>
            </div>
            <p v-if="errorMessage" class="text-racing-red text-sm">
                {{ errorMessage }}
            </p>
            <DialogClose as-child>
                <Button type="button" variant="outline" class="w-full">
                    <X />Cerrar
                </Button>
            </DialogClose>
        </DialogContent>
    </Dialog>
</template>
