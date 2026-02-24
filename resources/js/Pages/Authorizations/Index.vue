<script setup>
import { ref, computed, watch } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Pagination from '@/Components/Pagination.vue';
import { FileCheck, Search, X, Eye, Plus } from 'lucide-vue-next';

const props = defineProps({
    authorizations: Object,
    filters: Object,
    statuses: Array,
    epsList: Array,
});

const status = ref(props.filters?.status || '');
const epsId = ref(props.filters?.eps_id || '');
const affiliateId = ref(props.filters?.affiliate_id || '');
const serviceType = ref(props.filters?.service_type || '');
const authorizationNumber = ref(props.filters?.authorization_number || '');
const dateFrom = ref(props.filters?.date_from || '');
const dateTo = ref(props.filters?.date_to || '');
const hideUsed = ref(props.filters?.hide_used === undefined || props.filters?.hide_used === true || props.filters?.hide_used === '1');
const withoutAppointment = ref(props.filters?.without_appointment === true || props.filters?.without_appointment === '1');
const expiringSoon = ref(props.filters?.expiring_soon === true || props.filters?.expiring_soon === '1');

const applyFilters = () => {
    router.get('/authorizations', {
        status: status.value || undefined,
        eps_id: epsId.value || undefined,
        affiliate_id: affiliateId.value || undefined,
        service_type: serviceType.value || undefined,
        authorization_number: authorizationNumber.value || undefined,
        date_from: dateFrom.value || undefined,
        date_to: dateTo.value || undefined,
        hide_used: hideUsed.value ? '1' : '0',
        without_appointment: withoutAppointment.value ? '1' : undefined,
        expiring_soon: expiringSoon.value ? '1' : undefined,
    }, { preserveState: true, replace: true });
};

const clearFilters = () => {
    status.value = '';
    epsId.value = '';
    affiliateId.value = '';
    serviceType.value = '';
    authorizationNumber.value = '';
    dateFrom.value = '';
    dateTo.value = '';
    hideUsed.value = true;
    withoutAppointment.value = false;
    expiringSoon.value = false;
    router.get('/authorizations', { hide_used: '1' }, { preserveState: true, replace: true });
};

const hasActiveFilters = computed(() =>
    status.value || epsId.value || affiliateId.value || serviceType.value || authorizationNumber.value ||
    dateFrom.value || dateTo.value || withoutAppointment.value || expiringSoon.value || !hideUsed.value
);

// Sincronizar refs cuando cambian los filtros (p. ej. al navegar con enlaces del Dashboard o atrás)
watch(() => props.filters, (f) => {
    if (!f) return;
    status.value = f.status || '';
    epsId.value = f.eps_id || '';
    affiliateId.value = f.affiliate_id || '';
    serviceType.value = f.service_type || '';
    authorizationNumber.value = f.authorization_number || '';
    dateFrom.value = f.date_from || '';
    dateTo.value = f.date_to || '';
    hideUsed.value = f.hide_used === undefined || f.hide_used === true || f.hide_used === '1';
    withoutAppointment.value = f.without_appointment === true || f.without_appointment === '1';
    expiringSoon.value = f.expiring_soon === true || f.expiring_soon === '1';
}, { immediate: false, deep: true });

const list = computed(() => props.authorizations?.data || []);
</script>

<template>
    <AppLayout>
        <div class="space-y-6">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">Autorizaciones médicas</h1>
                    <p class="mt-1 text-sm text-gray-500">Gestión de autorizaciones ante la EPS</p>
                </div>
                <Link
                    href="/authorizations/create"
                    class="inline-flex items-center justify-center px-4 py-2 font-medium rounded-lg bg-brand-500 text-white hover:bg-brand-600 transition-colors"
                >
                    <Plus class="h-5 w-5 mr-2" />
                    Nueva autorización
                </Link>
            </div>

            <!-- Filtros (RF-AUT-18) -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4">
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-4">
                    <div>
                        <label class="block text-xs font-medium text-gray-500 mb-1">Estado</label>
                        <select v-model="status" @change="applyFilters" class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500 text-sm">
                            <option value="">Todos</option>
                            <option v-for="s in statuses" :key="s.value" :value="s.value">{{ s.label }}</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-500 mb-1">EPS</label>
                        <select v-model="epsId" @change="applyFilters" class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500 text-sm">
                            <option value="">Todas</option>
                            <option v-for="e in epsList" :key="e.id" :value="e.id">{{ e.name }}</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-500 mb-1">ID Afiliado</label>
                        <input v-model="affiliateId" type="number" min="1" placeholder="Opcional" @change="applyFilters" class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500 text-sm" />
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-500 mb-1">Tipo de servicio</label>
                        <input v-model="serviceType" type="text" placeholder="Buscar por texto" @change="applyFilters" class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500 text-sm" />
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-500 mb-1">N.º autorización</label>
                        <input v-model="authorizationNumber" type="text" placeholder="Número EPS" @change="applyFilters" class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500 text-sm" />
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-500 mb-1">Desde fecha</label>
                        <input v-model="dateFrom" type="date" @change="applyFilters" class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500 text-sm" />
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-500 mb-1">Hasta fecha</label>
                        <input v-model="dateTo" type="date" @change="applyFilters" class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500 text-sm" />
                    </div>
                </div>
                <div class="flex flex-wrap items-center gap-4 pt-2 border-t border-gray-100">
                    <label class="flex items-center gap-2 cursor-pointer select-none">
                        <input v-model="hideUsed" type="checkbox" @change="applyFilters" class="rounded border-gray-300 text-brand-600 focus:ring-brand-500" />
                        <span class="text-sm text-gray-700">Ocultar ya utilizadas (con cita creada)</span>
                    </label>
                    <label class="flex items-center gap-2 cursor-pointer select-none">
                        <input v-model="withoutAppointment" type="checkbox" @change="applyFilters" class="rounded border-gray-300 text-brand-600 focus:ring-brand-500" />
                        <span class="text-sm text-gray-700">Solo aprobadas sin cita</span>
                    </label>
                    <label class="flex items-center gap-2 cursor-pointer select-none">
                        <input v-model="expiringSoon" type="checkbox" @change="applyFilters" class="rounded border-gray-300 text-brand-600 focus:ring-brand-500" />
                        <span class="text-sm text-gray-700">Próximas a vencer (7 días)</span>
                    </label>
                    <button v-if="hasActiveFilters" @click="clearFilters" class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200 inline-flex items-center gap-1">
                        <X class="h-4 w-4" />
                        Limpiar filtros
                    </button>
                </div>
            </div>

            <!-- Tabla -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">#</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Afiliado</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">EPS</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tipo servicio</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Radicado EPS</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Estado</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Vigencia</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <tr v-for="auth in list" :key="auth.id" class="hover:bg-gray-50">
                            <td class="px-6 py-4 text-sm font-medium text-gray-900">#{{ auth.id }}</td>
                            <td class="px-6 py-4">
                                <div class="text-sm font-medium text-gray-900">{{ auth.affiliate?.full_name }}</div>
                                <div class="text-xs text-gray-500">{{ auth.affiliate?.document_number }}</div>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-600">{{ auth.eps?.name || '—' }}</td>
                            <td class="px-6 py-4 text-sm text-gray-600">{{ auth.service_type }}</td>
                            <td class="px-6 py-4 text-sm text-gray-900 font-mono">
                                {{ auth.radicado_number || '—' }}
                            </td>
                            <td class="px-6 py-4">
                                <span :class="[auth.status_badge_class, 'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium']">
                                    {{ auth.status_label }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-500">{{ auth.valid_until_formatted || '—' }}</td>
                            <td class="px-6 py-4 text-right">
                                <Link
                                    :href="`/authorizations/${auth.id}`"
                                    class="inline-flex items-center justify-center h-9 w-9 rounded-lg text-gray-400 hover:text-brand-700 hover:bg-brand-50 transition-colors"
                                    aria-label="Ver"
                                >
                                    <Eye class="h-4 w-4" />
                                </Link>
                            </td>
                        </tr>
                        <tr v-if="!list.length">
                            <td colspan="8" class="px-6 py-12 text-center text-gray-500">
                                No hay autorizaciones. <Link href="/authorizations/create" class="text-brand-600 hover:underline">Crear una</Link>.
                            </td>
                        </tr>
                    </tbody>
                </table>
                <div v-if="authorizations?.links" class="border-t border-gray-200 px-4 py-3">
                    <Pagination :links="authorizations.links" />
                </div>
            </div>
        </div>
    </AppLayout>
</template>
