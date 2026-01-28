<script setup>
import { useForm, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { computed, onMounted } from 'vue';

const props = defineProps({
    patient: Object,
    epsList: Array,
    documentTypes: Array,
    patientTypes: Array,
});

// Los datos pueden venir envueltos en "data" por el Resource
const patientData = computed(() => props.patient?.data || props.patient || {});

onMounted(() => {
    console.log('=== Patient Edit DEBUG ===');
    console.log('Props patient:', props.patient);
    console.log('Patient data:', patientData.value);
});

const form = useForm({
    document_type: patientData.value.document_type || '',
    document_number: patientData.value.document_number || '',
    first_name: patientData.value.first_name || '',
    last_name: patientData.value.last_name || '',
    phone: patientData.value.phone || '',
    whatsapp: patientData.value.whatsapp || '',
    email: patientData.value.email || '',
    address: patientData.value.address || '',
    eps_id: patientData.value.eps?.id || patientData.value.eps_id || '',
    patient_type: patientData.value.patient_type || '',
    birth_date: patientData.value.birth_date || '',
    notes: patientData.value.notes || '',
});

const submit = () => form.put(`/patients/${patientData.value.id}`);
</script>

<template>
    <AppLayout>
        <div class="max-w-3xl mx-auto">
            <div class="mb-6">
                <Link :href="`/patients/${patientData.id}`" class="text-sm text-gray-500 hover:text-gray-700">← Volver al paciente</Link>
                <h1 class="text-2xl font-bold text-gray-900 mt-2">Editar: {{ patientData.full_name || 'Paciente' }}</h1>
            </div>

            <form @submit.prevent="submit" class="space-y-6">
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
                        <h2 class="text-lg font-semibold">Información Personal</h2>
                    </div>
                    <div class="px-6 py-4 grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Tipo de documento</label>
                            <select v-model="form.document_type" class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500">
                                <option v-for="dt in documentTypes" :key="dt.value" :value="dt.value">{{ dt.label }}</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Número de documento</label>
                            <input v-model="form.document_number" type="text" :class="['block w-full rounded-lg border-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500', form.errors.document_number ? 'border-red-300' : '']" />
                            <p v-if="form.errors.document_number" class="mt-1 text-sm text-red-600">{{ form.errors.document_number }}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Nombres</label>
                            <input v-model="form.first_name" type="text" class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500" />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Apellidos</label>
                            <input v-model="form.last_name" type="text" class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500" />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Fecha de nacimiento</label>
                            <input v-model="form.birth_date" type="date" class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500" />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Correo electrónico</label>
                            <input v-model="form.email" type="email" class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500" />
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
                        <h2 class="text-lg font-semibold">Contacto y EPS</h2>
                    </div>
                    <div class="px-6 py-4 grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Teléfono</label>
                            <input v-model="form.phone" type="tel" class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500" />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">WhatsApp</label>
                            <input v-model="form.whatsapp" type="tel" class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500" />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">EPS</label>
                            <select v-model="form.eps_id" class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500">
                                <option value="">Seleccione...</option>
                                <option v-for="eps in epsList" :key="eps.id" :value="eps.id">{{ eps.name }}</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Tipo de afiliado</label>
                            <select v-model="form.patient_type" class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500">
                                <option v-for="pt in patientTypes" :key="pt.value" :value="pt.value">{{ pt.label }}</option>
                            </select>
                        </div>
                        <div class="sm:col-span-2">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Dirección</label>
                            <input v-model="form.address" type="text" class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500" />
                        </div>
                        <div class="sm:col-span-2">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Notas</label>
                            <textarea v-model="form.notes" rows="2" class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500"></textarea>
                        </div>
                    </div>
                </div>

                <div class="flex justify-end space-x-4">
                    <Link :href="`/patients/${patientData.id}`" class="inline-flex items-center justify-center px-6 py-3 font-medium rounded-lg bg-white text-gray-700 border border-gray-300 hover:bg-gray-50">
                        Cancelar
                    </Link>
                    <button type="submit" :disabled="form.processing" class="inline-flex items-center justify-center px-6 py-3 font-medium rounded-lg bg-green-600 text-white hover:bg-green-700 disabled:opacity-50">
                        {{ form.processing ? 'Guardando...' : 'Guardar Cambios' }}
                    </button>
                </div>
            </form>
        </div>
    </AppLayout>
</template>
