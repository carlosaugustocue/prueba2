<script setup>
import { Link, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { ChevronLeft, Loader2, Check } from 'lucide-vue-next';

const props = defineProps({
    type: String,
    label: String,
    labelPlural: String,
    item: Object,
    routePrefix: String,
});

const isEdit = !!props.item?.id;
const baseUrl = `/admin/configuracion/${props.type}`;

const form = useForm({
    name: props.item?.name ?? '',
    code: props.item?.code ?? '',
    is_active: props.item !== null ? props.item.is_active : true,
});
</script>

<template>
    <AppLayout>
        <div class="max-w-xl mx-auto space-y-6">
            <div class="flex items-center gap-3">
                <Link
                    :href="baseUrl"
                    class="inline-flex items-center justify-center h-10 w-10 rounded-lg text-gray-500 hover:bg-gray-100 hover:text-gray-700 transition-colors"
                    title="Volver al listado"
                >
                    <ChevronLeft class="h-5 w-5" />
                </Link>
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">
                        {{ isEdit ? `Editar ${label}` : `Nuevo ${label}` }}
                    </h1>
                    <p class="text-sm text-gray-500">Catálogo: {{ labelPlural }}</p>
                </div>
            </div>

            <form
                :action="isEdit ? undefined : baseUrl"
                method="post"
                @submit.prevent="isEdit ? form.put(`${baseUrl}/${item.id}`) : form.post(baseUrl)"
                class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 space-y-4"
            >
                <div>
                    <label for="name" class="block text-sm font-medium text-gray-700">Nombre *</label>
                    <input
                        id="name"
                        v-model="form.name"
                        type="text"
                        required
                        class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500"
                        placeholder="Ej: Porvenir, SURA..."
                    />
                    <p v-if="form.errors.name" class="mt-1 text-sm text-red-600">{{ form.errors.name }}</p>
                </div>

                <div>
                    <label for="code" class="block text-sm font-medium text-gray-700">Código</label>
                    <input
                        id="code"
                        v-model="form.code"
                        type="text"
                        class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500"
                        placeholder="Opcional"
                    />
                    <p v-if="form.errors.code" class="mt-1 text-sm text-red-600">{{ form.errors.code }}</p>
                </div>

                <div class="flex items-center gap-2">
                    <input
                        id="is_active"
                        v-model="form.is_active"
                        type="checkbox"
                        class="rounded border-gray-300 text-brand-600 focus:ring-brand-500"
                    />
                    <label for="is_active" class="text-sm font-medium text-gray-700">Activo</label>
                </div>

                <div class="flex gap-3 pt-2">
                    <Link
                        :href="baseUrl"
                        class="inline-flex items-center justify-center gap-2 px-4 py-2 font-medium rounded-lg border border-gray-300 text-gray-700 bg-white hover:bg-gray-50 transition-colors"
                    >
                        Cancelar
                    </Link>
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="inline-flex items-center justify-center gap-2 px-5 py-2 font-medium rounded-lg bg-brand-500 text-white hover:bg-brand-600 disabled:opacity-50 transition-colors"
                    >
                        <Loader2 v-if="form.processing" class="h-4 w-4 animate-spin" />
                        <Check v-else class="h-4 w-4" />
                        {{ form.processing ? 'Guardando...' : (isEdit ? 'Guardar cambios' : 'Crear') }}
                    </button>
                </div>
            </form>
        </div>
    </AppLayout>
</template>
