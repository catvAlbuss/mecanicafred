<script setup lang="ts">
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { ArrowLeft, Pencil, Plus, Tags, Trash2, X } from '@lucide/vue';
import { ref } from 'vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { destroy, index, store, update } from '@/routes/inventory/categories';
import { update as updateStatus } from '@/routes/inventory/categories/status';
import { index as productsIndex } from '@/routes/inventory/products';
import type { Option } from '@/types';
type Category = {
    id: number;
    name: string;
    type: string;
    type_label: string;
    description: string | null;
    is_active: boolean;
    products_count: number;
};
const props = defineProps<{
    categories: Category[];
    types: Option[];
    canManage: boolean;
}>();
defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Inventario', href: productsIndex() },
            { title: 'Categorías', href: index() },
        ],
    },
});
const editingId = ref<number | null>(null);
const form = useForm({ name: '', type: '', description: '', is_active: true });
function editCategory(category: Category): void {
    editingId.value = category.id;
    form.name = category.name;
    form.type = category.type;
    form.description = category.description ?? '';
    form.is_active = category.is_active;
}
function reset(): void {
    editingId.value = null;
    form.reset();
    form.clearErrors();
}
function submit(): void {
    const options = { preserveScroll: true, onSuccess: reset };
    if (editingId.value) form.patch(update.url(editingId.value), options);
    else form.post(store.url(), options);
}
function toggle(category: Category): void {
    router.patch(
        updateStatus.url(category.id),
        { is_active: !category.is_active },
        { preserveScroll: true },
    );
}
function remove(category: Category): void {
    if (
        confirm(
            '¿Eliminar esta categoría? Si tiene productos será desactivada.',
        )
    )
        router.delete(destroy.url(category.id), { preserveScroll: true });
}
</script>
<template>

    <Head title="Categorías de inventario" />
    <div class="flex flex-1 flex-col gap-6 p-4 sm:p-6 lg:p-8">
        <header class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div class="flex items-start gap-4">
                <Button as-child variant="outline" size="icon">
                    <Link :href="productsIndex()">
                        <ArrowLeft />
                    </Link>
                </Button><span
                    class="bg-racing-yellow text-racing-black flex size-10 items-center justify-center rounded-xl">
                    <Tags />
                </span>
                <div>
                    <p class="text-racing-yellow text-xs font-bold tracking-[.2em] uppercase">
                        Inventario
                    </p>
                    <h1 class="text-2xl font-black sm:text-3xl">Categorías</h1>
                    <p class="text-muted-foreground text-sm">
                        Clasifica el catálogo de Fredy Racing.
                    </p>
                </div>
            </div>
        </header>
        <div class="grid gap-6 xl:grid-cols-[22rem_minmax(0,1fr)]">
            <form v-if="canManage" class="bg-card h-fit rounded-2xl border p-5 xl:sticky xl:top-6"
                @submit.prevent="submit">
                <div class="flex items-center justify-between">
                    <h2 class="font-black">
                        {{ editingId ? 'Editar categoría' : 'Nueva categoría' }}
                    </h2>
                    <Button v-if="editingId" type="button" size="icon" variant="ghost" @click="reset">
                        <X />
                    </Button>
                </div>
                <div class="mt-5 grid gap-4">
                    <div class="grid gap-2">
                        <Label for="name">Nombre</Label><Input id="name" v-model="form.name" required maxlength="100" />
                        <InputError :message="form.errors.name" />
                    </div>
                    <div class="grid gap-2">
                        <Label for="type">Tipo</Label><select id="type" v-model="form.type" required
                            class="border-input h-9 rounded-md border bg-transparent px-3 text-sm">
                            <option value="">Selecciona un tipo</option>
                            <option v-for="type in types" :key="type.value" :value="type.value">
                                {{ type.label }}
                            </option>
                        </select>
                        <InputError :message="form.errors.type" />
                    </div>
                    <div class="grid gap-2">
                        <Label for="description">Descripción</Label><textarea id="description"
                            v-model="form.description" rows="3" maxlength="1000"
                            class="border-input rounded-md border bg-transparent px-3 py-2 text-sm" />
                        <InputError :message="form.errors.description" />
                    </div>
                    <label class="flex items-center gap-2 text-sm font-bold"><input v-model="form.is_active"
                            type="checkbox" class="accent-racing-green size-4" />Categoría activa</label><Button
                        type="submit" :disabled="form.processing" class="bg-racing-yellow text-racing-black">
                        <Pencil v-if="editingId" />
                        <Plus v-else />{{
                            editingId
                                ? 'Guardar cambios'
                                : 'Registrar categoría'
                        }}
                    </Button>
                </div>
            </form>
            <section class="grid gap-3 sm:grid-cols-2 2xl:grid-cols-3">
                <article v-for="category in categories" :key="category.id" class="bg-card rounded-2xl border p-5">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <span
                                class="bg-racing-yellow/15 text-racing-yellow rounded-full px-2 py-1 text-xs font-bold">{{
                                category.type_label }}</span>
                            <h2 class="mt-3 font-black">{{ category.name }}</h2>
                        </div>
                        <span :class="[
                            'rounded-full px-2 py-1 text-xs font-bold',
                            category.is_active
                                ? 'bg-racing-green/15 text-racing-green'
                                : 'bg-muted text-muted-foreground',
                        ]">{{
                                category.is_active ? 'Activa' : 'Inactiva'
                            }}</span>
                    </div>
                    <p class="text-muted-foreground mt-2 min-h-10 text-sm">
                        {{ category.description || 'Sin descripción.' }}
                    </p>
                    <p class="mt-4 text-sm">
                        <strong>{{ category.products_count }}</strong> productos
                    </p>
                    <div v-if="canManage" class="mt-4 flex gap-2 border-t pt-4">
                        <Button size="sm" variant="outline" @click="editCategory(category)">
                            <Pencil />Editar
                        </Button><Button size="sm" variant="outline" @click="toggle(category)">{{
                            category.is_active ? 'Desactivar' : 'Activar'
                        }}</Button><Button size="icon" variant="ghost" class="text-racing-red ml-auto"
                            @click="remove(category)">
                            <Trash2 />
                        </Button>
                    </div>
                </article>
                <div v-if="!categories.length"
                    class="text-muted-foreground rounded-2xl border border-dashed p-10 text-center sm:col-span-2">
                    No hay categorías registradas.
                </div>
            </section>
        </div>
    </div>
</template>
