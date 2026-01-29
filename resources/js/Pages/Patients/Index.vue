<script setup>
import { ref, watch } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Pagination from '@/Components/Pagination.vue';

const props = defineProps({
    patients: Object,
    filters: Object,
    epsList: Array,
});

const search = ref(props.filters?.search || '');

const applyFilters = () => {
    router.get('/patients', { search: search.value || undefined }, { preserveState: true, replace: true });
};

let searchTimeout;
watch(search, () => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(applyFilters, 500);
});
</script>

<template>
    <AppLayout>
        <div class="space-y-6">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">Pacientes</h1>
                    <p class="mt-1 text-sm text-gray-500">Gestión de pacientes registrados</p>
                </div>
                <Link href="/patients/create" class="inline-flex items-center justify-center px-4 py-2 font-medium rounded-lg bg-brand-500 text-white hover:bg-brand-600">
                    ➕ Nuevo Paciente
                </Link>
            </div>

            <!-- Búsqueda -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4">
                <input v-model="search" type="text" placeholder="🔍 Buscar por nombre, documento..." class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500" />
            </div>

            <!-- Tabla -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Paciente</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Documento</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">EPS</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Teléfono</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <tr v-for="patient in patients.data" :key="patient.id" class="hover:bg-gray-50">
                            <td class="px-6 py-4">
                                <div class="flex items-center space-x-3">
                                    <div class="h-10 w-10 rounded-full bg-brand-100 flex items-center justify-center">
                                        <span class="text-brand-700 font-medium text-sm">{{ patient.first_name?.charAt(0) }}{{ patient.last_name?.charAt(0) }}</span>
                                    </div>
                                    <div>
                                        <p class="font-medium text-gray-900">{{ patient.full_name }}</p>
                                        <p class="text-sm text-gray-500">{{ patient.patient_type_label }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-900">{{ patient.document_type_abbreviation }} {{ patient.document_number }}</td>
                            <td class="px-6 py-4 text-sm text-gray-500">{{ patient.eps?.name || '-' }}</td>
                            <td class="px-6 py-4 text-sm text-gray-500">{{ patient.whatsapp || patient.phone || '-' }}</td>
                            <td class="px-6 py-4 text-right space-x-2">
                                <Link :href="`/patients/${patient.id}`" class="text-gray-400 hover:text-gray-600">👁️</Link>
                                <Link :href="`/patients/${patient.id}/edit`" class="text-gray-400 hover:text-brand-600">✏️</Link>
                            </td>
                        </tr>
                        <tr v-if="!patients.data?.length">
                            <td colspan="5" class="px-6 py-12 text-center text-gray-500">No se encontraron pacientes</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <Pagination :links="patients?.links" />
        </div>
    </AppLayout>
</template>
