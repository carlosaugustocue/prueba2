<script setup>
import { Link, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import DatePicker from '@/Components/DatePicker.vue';
import { ChevronLeft, Loader2, Check } from 'lucide-vue-next';

const props = defineProps({
    parameter: Object,
    types: Object,
    valueTypes: Object,
});

const isEdit = !!props.parameter?.id;
const baseUrl = '/admin/configuracion/contribution-parameters';

const toDateInput = (d) => (d ? String(d).slice(0, 10) : '');

const form = useForm({
    type: props.parameter?.type ?? 'HEALTH',
    subtype: props.parameter?.subtype ?? '',
    value: props.parameter?.value ?? '',
    value_type: props.parameter?.value_type ?? 'PERCENTAGE',
    valid_from: toDateInput(props.parameter?.valid_from) || new Date().toISOString().slice(0, 10),
    valid_to: toDateInput(props.parameter?.valid_to) ?? '',
    description: props.parameter?.description ?? '',
    legal_reference: props.parameter?.legal_reference ?? '',
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
                        {{ isEdit ? 'Editar parámetro' : 'Nuevo parámetro de aportes' }}
                    </h1>
                    <p class="text-sm text-gray-500">Vigencia normativa para cálculos de planilla</p>
                </div>
            </div>

            <form
                @submit.prevent="isEdit ? form.put(`${baseUrl}/${parameter.id}`) : form.post(baseUrl)"
                class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 space-y-4"
            >
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label for="type" class="block text-sm font-medium text-gray-700">Tipo *</label>
                        <select
                            id="type"
                            v-model="form.type"
                            required
                            class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500"
                        >
                            <option v-for="(label, key) in types" :key="key" :value="key">{{ label }}</option>
                        </select>
                        <p v-if="form.errors.type" class="mt-1 text-sm text-red-600">{{ form.errors.type }}</p>
                    </div>
                    <div>
                        <label for="subtype" class="block text-sm font-medium text-gray-700">Subtipo *</label>
                        <input
                            id="subtype"
                            v-model="form.subtype"
                            type="text"
                            required
                            maxlength="50"
                            class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500"
                            placeholder="Ej: TOTAL, EMPLOYER, RISK_1"
                        />
                        <p v-if="form.errors.subtype" class="mt-1 text-sm text-red-600">{{ form.errors.subtype }}</p>
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label for="value" class="block text-sm font-medium text-gray-700">Valor *</label>
                        <input
                            id="value"
                            v-model="form.value"
                            type="number"
                            step="any"
                            min="0"
                            required
                            class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500"
                            placeholder="12.5 o 1750905"
                        />
                        <p v-if="form.errors.value" class="mt-1 text-sm text-red-600">{{ form.errors.value }}</p>
                    </div>
                    <div>
                        <label for="value_type" class="block text-sm font-medium text-gray-700">Tipo de valor *</label>
                        <select
                            id="value_type"
                            v-model="form.value_type"
                            required
                            class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500"
                        >
                            <option v-for="(label, key) in valueTypes" :key="key" :value="key">{{ label }}</option>
                        </select>
                        <p v-if="form.errors.value_type" class="mt-1 text-sm text-red-600">{{ form.errors.value_type }}</p>
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <DatePicker
                            v-model="form.valid_from"
                            label="Vigencia desde"
                            required
                        />
                        <p v-if="form.errors.valid_from" class="mt-1 text-sm text-red-600">{{ form.errors.valid_from }}</p>
                    </div>
                    <div>
                        <DatePicker
                            v-model="form.valid_to"
                            label="Vigencia hasta"
                        />
                        <button
                            v-if="form.valid_to"
                            type="button"
                            class="mt-2 text-xs text-gray-600 hover:text-gray-800 underline underline-offset-2"
                            @click="form.valid_to = ''"
                        >
                            Limpiar fecha
                        </button>
                        <p v-if="form.errors.valid_to" class="mt-1 text-sm text-red-600">{{ form.errors.valid_to }}</p>
                    </div>
                </div>

                <div>
                    <label for="description" class="block text-sm font-medium text-gray-700">Descripción</label>
                    <input
                        id="description"
                        v-model="form.description"
                        type="text"
                        maxlength="255"
                        class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500"
                        placeholder="Ej: Salud total (empleador + empleado)"
                    />
                    <p v-if="form.errors.description" class="mt-1 text-sm text-red-600">{{ form.errors.description }}</p>
                </div>

                <div>
                    <label for="legal_reference" class="block text-sm font-medium text-gray-700">Referencia normativa</label>
                    <input
                        id="legal_reference"
                        v-model="form.legal_reference"
                        type="text"
                        maxlength="255"
                        class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500"
                        placeholder="Ej: Decreto 1469/2025"
                    />
                    <p v-if="form.errors.legal_reference" class="mt-1 text-sm text-red-600">{{ form.errors.legal_reference }}</p>
                </div>

                <div class="flex items-center justify-end gap-3 pt-2">
                    <Link
                        :href="baseUrl"
                        class="px-4 py-2 rounded-lg border border-gray-300 text-gray-700 hover:bg-gray-50"
                    >
                        Cancelar
                    </Link>
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-brand-500 text-white hover:bg-brand-600 disabled:opacity-50"
                    >
                        <Loader2 v-if="form.processing" class="h-4 w-4 animate-spin" />
                        <Check v-else class="h-4 w-4" />
                        {{ isEdit ? 'Actualizar' : 'Crear' }}
                    </button>
                </div>
            </form>
        </div>
    </AppLayout>
</template>
