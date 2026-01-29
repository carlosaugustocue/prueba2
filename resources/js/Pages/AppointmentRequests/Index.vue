<script setup>
import { computed } from 'vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Pagination from '@/Components/Pagination.vue';
import {
    Plus, Search, Clock, CheckCircle, Filter,
    User, Calendar, ChevronRight, Play, XCircle, Loader2, AlertTriangle
} from 'lucide-vue-next';

const page = usePage();
const isAdmin = computed(() => page.props.auth?.user?.role === 'admin');

const props = defineProps({
    requests: Object,
    filters: Object,
    statuses: Array,
    priorities: Array,
    operators: Array,
    stats: Object,
});

const requestsList = computed(() => props.requests?.data || []);

const applyFilter = (key, value) => {
    router.get('/appointment-requests', {
        ...props.filters,
        [key]: value || undefined,
    }, { preserveState: true });
};

const clearFilters = () => {
    router.get('/appointment-requests');
};

const startRequest = (request) => {
    // Se confirma desde UI más bonita (SweetAlert)
    import('@/Utils/swal').then(({ confirmDialog }) => {
        confirmDialog({
            title: 'Tomar solicitud',
            text: '¿Desea tomar esta solicitud para tramitarla?',
            confirmButtonText: 'Tomar',
        }).then((ok) => {
            if (!ok) return;
            router.post(`/appointment-requests/${request.id}/start`);
        });
    });
};

const statusIcon = (status) => {
    const map = {
        pending: Clock,
        in_progress: Loader2,
        completed: CheckCircle,
        cancelled: XCircle,
        failed: AlertTriangle,
    };
    return map[status] || Clock;
};

const getPriorityClass = (priority) => {
    const classes = {
        urgent: 'border-l-4 border-l-red-500',
        high: 'border-l-4 border-l-orange-500',
        medium: 'border-l-4 border-l-yellow-500',
        low: 'border-l-4 border-l-green-500',
    };
    return classes[priority] || '';
};
</script>

<template>
    <AppLayout>
        <div class="space-y-6">
            <!-- Header -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900">Solicitudes de Citas</h1>
                    <p class="text-gray-500 mt-1">Gestiona las solicitudes de los clientes</p>
                </div>
                <Link href="/appointment-requests/create" class="inline-flex items-center gap-2 px-6 py-3 bg-brand-500 text-white font-semibold rounded-xl hover:bg-brand-600 transition-colors shadow-lg shadow-brand-500/30">
                    <Plus class="h-5 w-5" />
                    Nueva Solicitud
                </Link>
            </div>

            <!-- Stats Cards -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                    <div class="flex items-center gap-4">
                        <div class="p-3 bg-yellow-100 rounded-xl">
                            <Clock class="h-6 w-6 text-yellow-600" />
                        </div>
                        <div>
                            <p class="text-3xl font-bold text-gray-900">{{ stats.pending }}</p>
                            <p class="text-sm text-gray-500">Pendientes</p>
                        </div>
                    </div>
                </div>
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                    <div class="flex items-center gap-4">
                        <div class="p-3 bg-blue-100 rounded-xl">
                            <Loader2 class="h-6 w-6 text-blue-600" />
                        </div>
                        <div>
                            <p class="text-3xl font-bold text-gray-900">{{ stats.in_progress }}</p>
                            <p class="text-sm text-gray-500">En Proceso</p>
                        </div>
                    </div>
                </div>
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                    <div class="flex items-center gap-4">
                        <div class="p-3 bg-green-100 rounded-xl">
                            <CheckCircle class="h-6 w-6 text-green-600" />
                        </div>
                        <div>
                            <p class="text-3xl font-bold text-gray-900">{{ stats.completed_today }}</p>
                            <p class="text-sm text-gray-500">Completadas Hoy</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Filters -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4">
                <div class="flex flex-wrap items-center gap-4">
                    <div class="flex items-center gap-2 text-gray-500">
                        <Filter class="h-5 w-5" />
                        <span class="font-medium">Filtros:</span>
                    </div>
                    
                    <select 
                        :value="filters.status || ''"
                        @change="applyFilter('status', $event.target.value)"
                        class="rounded-lg border-gray-300 text-sm focus:border-brand-500 focus:ring-brand-500"
                    >
                        <option value="">Todos los estados</option>
                        <option v-for="status in statuses" :key="status.value" :value="status.value">
                            {{ status.label }}
                        </option>
                    </select>

                    <select 
                        :value="filters.priority || ''"
                        @change="applyFilter('priority', $event.target.value)"
                        class="rounded-lg border-gray-300 text-sm focus:border-brand-500 focus:ring-brand-500"
                    >
                        <option value="">Todas las prioridades</option>
                        <option v-for="priority in priorities" :key="priority.value" :value="priority.value">
                            {{ priority.label }}
                        </option>
                    </select>

                    <div class="relative flex-1 min-w-[200px]">
                        <Search class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-gray-400" />
                        <input 
                            type="text"
                            :value="filters.search || ''"
                            @input="applyFilter('search', $event.target.value)"
                            placeholder="Buscar paciente..."
                            class="w-full pl-10 pr-4 py-2 rounded-lg border-gray-300 text-sm focus:border-brand-500 focus:ring-brand-500"
                        />
                    </div>

                    <button 
                        v-if="Object.keys(filters).length" 
                        @click="clearFilters"
                        class="text-sm text-gray-500 hover:text-gray-700"
                    >
                        Limpiar filtros
                    </button>
                </div>
            </div>

            <!-- Requests List -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                <div class="divide-y divide-gray-100">
                    <div 
                        v-for="request in requestsList" 
                        :key="request.id"
                        :class="[
                            'p-4 hover:bg-gray-50 transition-colors',
                            getPriorityClass(request.priority)
                        ]"
                    >
                        <div class="flex items-center gap-4">
                            <!-- Status Icon -->
                            <div :class="[
                                'flex-shrink-0 h-12 w-12 rounded-full flex items-center justify-center',
                                request.status === 'pending' ? 'bg-yellow-100' : '',
                                request.status === 'in_progress' ? 'bg-blue-100' : '',
                                request.status === 'completed' ? 'bg-green-100' : '',
                                request.status === 'cancelled' ? 'bg-gray-100' : '',
                                request.status === 'failed' ? 'bg-red-100' : '',
                            ]">
                                <component
                                    :is="statusIcon(request.status)"
                                    :class="[
                                        'h-6 w-6',
                                        request.status === 'pending' ? 'text-yellow-700' : '',
                                        request.status === 'in_progress' ? 'text-blue-700 animate-spin' : '',
                                        request.status === 'completed' ? 'text-green-700' : '',
                                        request.status === 'cancelled' ? 'text-gray-600' : '',
                                        request.status === 'failed' ? 'text-red-700' : '',
                                    ]"
                                />
                            </div>

                            <!-- Info -->
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center gap-2 flex-wrap">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-gray-100 text-gray-700">
                                        Solicitud #{{ request.id }}
                                    </span>
                                    <Link :href="`/appointment-requests/${request.id}`" class="font-semibold text-gray-900 hover:text-brand-600">
                                        {{ request.patient?.full_name || 'Sin paciente' }}
                                    </Link>
                                    <span :class="[request.status_badge_class, 'px-2 py-0.5 rounded-full text-xs font-medium']">
                                        {{ request.status_label }}
                                    </span>
                                    <span :class="[request.priority_badge_class, 'px-2 py-0.5 rounded-full text-xs font-medium']">
                                        {{ request.priority_label }}
                                    </span>
                                </div>
                                <p class="text-sm text-gray-500 mt-1">
                                    {{ request.type_label }}
                                    <span v-if="request.specialty"> • {{ request.specialty }}</span>
                                </p>
                                <div class="flex items-center gap-4 mt-2 text-xs text-gray-400">
                                    <span
                                        v-if="isAdmin && request.requested_at_formatted"
                                        class="flex items-center gap-1"
                                    >
                                        <Clock class="h-3 w-3" />
                                        Solicitado: {{ request.requested_at_formatted }}
                                    </span>
                                    <span
                                        v-if="isAdmin && request.elapsed_time_formatted"
                                        class="flex items-center gap-1"
                                    >
                                        ⏱️ {{ request.elapsed_time_formatted }}
                                    </span>
                                    <span v-if="request.assignee" class="flex items-center gap-1">
                                        <User class="h-3 w-3" />
                                        {{ request.assignee.name }}
                                    </span>
                                </div>
                            </div>

                            <!-- Actions -->
                            <div class="flex items-center gap-2">
                                <button 
                                    v-if="request.is_pending"
                                    @click="startRequest(request)"
                                    class="inline-flex items-center gap-1 px-3 py-2 bg-brand-500 text-white text-sm font-medium rounded-lg hover:bg-brand-600 transition-colors"
                                >
                                    <Play class="h-4 w-4" />
                                    Tomar
                                </button>
                                <Link 
                                    v-if="request.status === 'in_progress'"
                                    :href="`/appointment-requests/${request.id}/create-appointment`"
                                    class="inline-flex items-center gap-1 px-3 py-2 bg-brand-500 text-white text-sm font-medium rounded-lg hover:bg-brand-600 transition-colors"
                                >
                                    <Calendar class="h-4 w-4" />
                                    Crear Cita
                                </Link>
                                <Link 
                                    v-if="request.has_appointment"
                                    :href="`/appointments/${request.appointment_id}`"
                                    class="inline-flex items-center gap-1 px-3 py-2 bg-brand-50 text-brand-700 text-sm font-medium rounded-lg hover:bg-brand-100 transition-colors"
                                >
                                    <CheckCircle class="h-4 w-4" />
                                    Ver Cita #{{ request.appointment_id }}
                                </Link>
                                <Link :href="`/appointment-requests/${request.id}`" class="p-2 text-gray-400 hover:text-brand-600 hover:bg-brand-50 rounded-lg transition-colors">
                                    <ChevronRight class="h-5 w-5" />
                                </Link>
                            </div>
                        </div>
                    </div>

                    <!-- Empty State -->
                    <div v-if="!requestsList.length" class="p-12 text-center">
                        <Clock class="h-12 w-12 mx-auto text-gray-300 mb-4" />
                        <h3 class="text-lg font-medium text-gray-900 mb-2">No hay solicitudes</h3>
                        <p class="text-gray-500 mb-4">No se encontraron solicitudes con los filtros aplicados.</p>
                        <Link href="/appointment-requests/create" class="inline-flex items-center gap-2 px-4 py-2 bg-brand-500 text-white rounded-lg hover:bg-brand-600 transition-colors">
                            <Plus class="h-5 w-5" />
                            Nueva Solicitud
                        </Link>
                    </div>
                </div>
            </div>

            <!-- Pagination -->
            <Pagination :links="requests?.links" />
        </div>
    </AppLayout>
</template>
