<script setup>
import { computed } from 'vue';

const props = defineProps({
    form: Object,
    allowedDocumentTypes: Array,
    submitLabel: String,
});

const minBusinessDay = computed(() => 2);
const maxBusinessDay = computed(() => 16);
</script>

<template>
    <form @submit.prevent="$emit('submit')" class="space-y-5">
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Tipo documento</label>
                <select v-model="form.document_type" class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500">
                    <option value="" disabled>Seleccione…</option>
                    <option v-for="t in (allowedDocumentTypes || [])" :key="t" :value="t">{{ t }}</option>
                </select>
                <p v-if="form.errors.document_type" class="mt-1 text-sm text-red-600">{{ form.errors.document_type }}</p>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Número</label>
                <input v-model="form.document_number" type="text" class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500" />
                <p v-if="form.errors.document_number" class="mt-1 text-sm text-red-600">{{ form.errors.document_number }}</p>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">DV (si aplica)</label>
                <input v-model="form.check_digit" type="text" maxlength="1" class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500" />
                <p v-if="form.errors.check_digit" class="mt-1 text-sm text-red-600">{{ form.errors.check_digit }}</p>
            </div>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Nombre / Razón social</label>
            <input v-model="form.name" type="text" class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500" />
            <p v-if="form.errors.name" class="mt-1 text-sm text-red-600">{{ form.errors.name }}</p>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Correo</label>
                <input v-model="form.email" type="email" class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500" />
                <p v-if="form.errors.email" class="mt-1 text-sm text-red-600">{{ form.errors.email }}</p>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Teléfono</label>
                <input v-model="form.phone" type="text" class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500" />
                <p v-if="form.errors.phone" class="mt-1 text-sm text-red-600">{{ form.errors.phone }}</p>
            </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Ciudad</label>
                <input v-model="form.city" type="text" class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500" />
                <p v-if="form.errors.city" class="mt-1 text-sm text-red-600">{{ form.errors.city }}</p>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Departamento</label>
                <input v-model="form.department" type="text" class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500" />
                <p v-if="form.errors.department" class="mt-1 text-sm text-red-600">{{ form.errors.department }}</p>
            </div>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Dirección</label>
            <input v-model="form.address" type="text" class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500" />
            <p v-if="form.errors.address" class="mt-1 text-sm text-red-600">{{ form.errors.address }}</p>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Día hábil de pago (2–16)</label>
                <input v-model="form.payment_business_day" type="number" :min="minBusinessDay" :max="maxBusinessDay" class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500" />
                <p class="mt-1 text-xs text-gray-500">Si lo dejas vacío, se calculará por los últimos 2 dígitos del documento.</p>
                <p v-if="form.errors.payment_business_day" class="mt-1 text-sm text-red-600">{{ form.errors.payment_business_day }}</p>
            </div>
            <div class="flex items-center gap-4 pt-7">
                <label class="inline-flex items-center gap-2 text-sm text-gray-700">
                    <input v-model="form.is_active" type="checkbox" class="rounded border-gray-300 text-brand-600 focus:ring-brand-500" />
                    Activo
                </label>
                <label class="inline-flex items-center gap-2 text-sm text-gray-700">
                    <input v-model="form.is_self_employed" type="checkbox" class="rounded border-gray-300 text-brand-600 focus:ring-brand-500" />
                    Es independiente (empleador = afiliado)
                </label>
            </div>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Notas</label>
            <textarea v-model="form.notes" rows="3" class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500" />
            <p v-if="form.errors.notes" class="mt-1 text-sm text-red-600">{{ form.errors.notes }}</p>
        </div>

        <div class="flex items-center justify-end gap-3">
            <button type="submit" :disabled="form.processing" class="inline-flex items-center justify-center px-4 py-2 font-medium rounded-lg bg-brand-500 text-white hover:bg-brand-600 disabled:opacity-50">
                {{ submitLabel || 'Guardar' }}
            </button>
        </div>
    </form>
</template>

