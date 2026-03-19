<script setup>
import { Link, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { ChevronLeft, Loader2, Check } from 'lucide-vue-next';

const props = defineProps({
    item: Object,
});

const isEdit = !!props.item?.id;
const baseUrl = '/admin/configuracion/clases-riesgo-arl';

const form = useForm({
    level: props.item?.level ?? 0,
    class_name: props.item?.class_name ?? '',
    description: props.item?.description ?? '',
    rate_percent: props.item?.rate_percent ?? 0,
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
                        {{ isEdit ? 'Editar clase de riesgo ARL' : 'Nueva clase de riesgo ARL' }}
                    </h1>
                    <p class="text-sm text-gray-500">Nivel 0 (No aplica) a 5 (Riesgo máximo). Tarifa en %.</p>
                </div>
            </div>

            <form
                :action="isEdit ? undefined : baseUrl"
                method="post"
                @submit.prevent="isEdit ? form.put(`${baseUrl}/${item.id}`) : form.post(baseUrl)"
                class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 space-y-4"
            >
                <div>
                    <label for="level" class="block text-sm font-medium text-gray-700">Nivel (0–5) *</label>
                    <input
                        id="level"
                        v-model.number="form.level"
                        type="number"
                        min="0"
                        max="5"
                        step="1"
                        required
                        class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500"
                        placeholder="0"
                    />
                    <p class="mt-1 text-xs text-gray-500">0 = No aplica, 1 = I (mínimo), 2 = II, 3 = III, 4 = IV, 5 = V (máximo)</p>
                    <p v-if="form.errors.level" class="mt-1 text-sm text-red-600">{{ form.errors.level }}</p>
                </div>

                <div>
                    <label for="class_name" class="block text-sm font-medium text-gray-700">Clase (romano)</label>
                    <input
                        id="class_name"
                        v-model="form.class_name"
                        type="text"
                        maxlength="5"
                        class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500"
                        placeholder="I, II, III, IV, V (vacío para nivel 0)"
                    />
                    <p v-if="form.errors.class_name" class="mt-1 text-sm text-red-600">{{ form.errors.class_name }}</p>
                </div>

                <div>
                    <label for="description" class="block text-sm font-medium text-gray-700">Descripción *</label>
                    <input
                        id="description"
                        v-model="form.description"
                        type="text"
                        required
                        maxlength="100"
                        class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500"
                        placeholder="Ej: Riesgo mínimo, Riesgo bajo..."
                    />
                    <p v-if="form.errors.description" class="mt-1 text-sm text-red-600">{{ form.errors.description }}</p>
                </div>

                <div>
                    <label for="rate_percent" class="block text-sm font-medium text-gray-700">Tarifa (%) *</label>
                    <input
                        id="rate_percent"
                        v-model.number="form.rate_percent"
                        type="number"
                        min="0"
                        max="100"
                        step="0.001"
                        required
                        class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500"
                        placeholder="Ej: 0.522 para 0,522%"
                    />
                    <p class="mt-1 text-xs text-gray-500">Ej: 0.522 = 0,522%, 6.96 = 6,96% (normativa vigente)</p>
                    <p v-if="form.errors.rate_percent" class="mt-1 text-sm text-red-600">{{ form.errors.rate_percent }}</p>
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
