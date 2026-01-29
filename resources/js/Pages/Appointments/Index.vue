<script setup>
import { ref, computed, watch, onMounted } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Pagination from '@/Components/Pagination.vue';
import { ClipboardList, Search, X, Eye, Pencil } from 'lucide-vue-next';

const props = defineProps({
    appointments: Object,
    filters: Object,
    statuses: Array,
    types: Array,
    priorities: Array,
});

onMounted(() => {
    console.log('=== Appointments Index DEBUG ===');
    console.log('Props appointments:', props.appointments);
    console.log('Filters:', props.filters);
});

const search = ref(props.filters?.search || '');
const status = ref(props.filters?.status || '');

const applyFilters = () => {
    router.get('/appointments', {
        search: search.value || undefined,
        status: status.value || undefined,
    }, { preserveState: true, replace: true });
};

const clearFilters = () => {
    search.value = '';
    status.value = '';
    router.get('/appointments', {}, { preserveState: true, replace: true });
};

let searchTimeout;
watch(search, () => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(applyFilters, 500);
});

const appointments = computed(() => props.appointments?.data || []);
</script>

<template>
    <AppLayout>
        <div class="space-y-6">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">Citas Registradas</h1>
                    <p class="mt-1 text-sm text-gray-500">Historial de citas obtenidas de las EPS/IPS</p>
                </div>
                <Link href="/appointment-requests" class="inline-flex items-center justify-center px-4 py-2 font-medium rounded-lg bg-brand-500 text-white hover:bg-brand-600 transition-colors">
                    <ClipboardList class="h-5 w-5 mr-2" />
                    Ver Solicitudes
                </Link>
            </div>

            <!-- Filters -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4">
                <div class="flex flex-col sm:flex-row gap-4">
                    <div class="flex-1">
                        <div class="relative">
                            <Search class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-gray-400" />
                            <input v-model="search" type="text" placeholder="Buscar por paciente, doctor..." class="block w-full pl-10 rounded-lg border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500" />
                        </div>
                    </div>
                    <select v-model="status" @change="applyFilters" class="block w-full sm:w-48 rounded-lg border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500">
                        <option value="">Todos los estados</option>
                        <option v-for="s in statuses" :key="s.value" :value="s.value">{{ s.label }}</option>
                    </select>
                    <button v-if="search || status" @click="clearFilters" class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200">
                        <X class="h-4 w-4 inline-block mr-1" />
                        Limpiar filtros
                    </button>
                </div>
            </div>

            <!-- Table -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Paciente</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tipo</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Fecha / Hora</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Estado</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Prioridad</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <tr v-for="apt in appointments" :key="apt.id" class="hover:bg-gray-50">
                            <td class="px-6 py-4">
                                <div class="text-sm font-medium text-gray-900">{{ apt.patient?.full_name }}</div>
                                <div class="text-sm text-gray-500">{{ apt.patient?.document_type_abbreviation }} {{ apt.patient?.document_number }}</div>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-900">{{ apt.type_label }}</td>
                            <td class="px-6 py-4 text-sm text-gray-500">{{ apt.formatted_datetime || 'Por definir' }}</td>
                            <td class="px-6 py-4"><span :class="[apt.status_badge_class, 'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium']">{{ apt.status_label }}</span></td>
                            <td class="px-6 py-4"><span :class="[apt.priority_badge_class, 'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium']">{{ apt.priority_label }}</span></td>
                            <td class="px-6 py-4 text-right space-x-2">
                                <Link :href="`/appointments/${apt.id}`" class="inline-flex items-center justify-center h-9 w-9 rounded-lg text-gray-400 hover:text-gray-700 hover:bg-gray-100 transition-colors" aria-label="Ver">
                                    <Eye class="h-4 w-4" />
                                </Link>
                                <Link :href="`/appointments/${apt.id}/edit`" class="inline-flex items-center justify-center h-9 w-9 rounded-lg text-gray-400 hover:text-brand-700 hover:bg-brand-50 transition-colors" aria-label="Editar">
                                    <Pencil class="h-4 w-4" />
                                </Link>
                            </td>
                        </tr>
                        <tr v-if="appointments.length === 0">
                            <td colspan="6" class="px-6 py-12 text-center text-gray-500">No se encontraron citas</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <Pagination :links="props.appointments?.links" />
        </div>
    </AppLayout>
</template>
