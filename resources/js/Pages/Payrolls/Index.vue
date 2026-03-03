<script setup>
import { ref, watch } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Pagination from '@/Components/Pagination.vue';
import { FileText, Search, Eye, Plus } from 'lucide-vue-next';

const props = defineProps({
    payrolls: Object,
    filters: Object,
    payers: Array,
    statusOptions: Array,
});

const search = ref(props.filters?.search || '');
const year = ref(props.filters?.year || '');
const month = ref(props.filters?.month || '');
const status = ref(props.filters?.status || '');
const payerId = ref(props.filters?.payer_id || '');
const dueDate = ref(props.filters?.due_date || '');

const applyFilters = () => {
    router.get('/payrolls', {
        search: search.value || undefined,
        year: year.value || undefined,
        month: month.value || undefined,
        status: status.value || undefined,
        payer_id: payerId.value || undefined,
        due_date: dueDate.value || undefined,
    }, { preserveState: true, replace: true });
};

let searchTimeout;
watch(search, () => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(applyFilters, 400);
});
watch([year, month, status, payerId, dueDate], applyFilters);

const statusClass = (s) => {
    const map = {
        PENDING: 'bg-gray-100 text-gray-700',
        SETTLED: 'bg-blue-100 text-blue-700',
        SENT_TO_CLIENT: 'bg-amber-100 text-amber-700',
        PAID: 'bg-green-100 text-green-700',
        OVERDUE: 'bg-red-100 text-red-700',
    };
    return map[s] || 'bg-gray-100 text-gray-600';
};

const formatMoney = (n) => {
    if (n == null) return '—';
    return new Intl.NumberFormat('es-CO', { style: 'currency', currency: 'COP', maximumFractionDigits: 0 }).format(n);
};

const monthLabel = (m) => {
    const names = ['', 'Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'];
    return names[m] || m;
};
</script>

<template>
    <AppLayout>
        <div class="space-y-6">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">Planillas</h1>
                    <p class="mt-1 text-sm text-gray-500">Liquidación de aportes por afiliado y período</p>
                </div>
                <Link href="/payrolls/create" class="inline-flex items-center justify-center px-4 py-2 font-medium rounded-lg bg-brand-500 text-white hover:bg-brand-600">
                    <Plus class="h-5 w-5 mr-2" />
                    Nueva planilla
                </Link>
            </div>

            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4">
                <div class="flex flex-col sm:flex-row gap-4 flex-wrap">
                    <div class="relative flex-1 min-w-[200px]">
                        <Search class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-gray-400" />
                        <input v-model="search" type="text" placeholder="Afiliado o documento..." class="block w-full pl-10 rounded-lg border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500" />
                    </div>
                    <select v-model="year" class="rounded-lg border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500 w-28">
                        <option value="">Año</option>
                        <option v-for="y in [new Date().getFullYear(), new Date().getFullYear()-1]" :key="y" :value="y">{{ y }}</option>
                    </select>
                    <select v-model="month" class="rounded-lg border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500 w-32">
                        <option value="">Mes</option>
                        <option v-for="m in 12" :key="m" :value="m">{{ monthLabel(m) }}</option>
                    </select>
                    <select v-model="status" class="rounded-lg border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500 w-44">
                        <option value="">Estado</option>
                        <option v-for="opt in statusOptions" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
                    </select>
                    <select v-model="payerId" class="rounded-lg border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500 w-48">
                        <option value="">Pagador</option>
                        <option v-for="p in payers" :key="p.id" :value="p.id">{{ p.name }}</option>
                    </select>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                <div class="overflow-x-auto min-w-0">
                    <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Afiliado</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Pagador</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Período</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Vencimiento</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Estado</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Total</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <tr v-for="py in payrolls?.data ?? []" :key="py.id" class="hover:bg-gray-50">
                            <td class="px-6 py-4">
                                <Link :href="`/affiliates/${py.affiliate_id}`" class="font-medium text-gray-900 hover:text-brand-700 hover:underline">
                                    {{ py.affiliate?.full_name ?? '—' }}
                                </Link>
                                <p class="text-sm text-gray-500">{{ py.affiliate?.document_number ?? '' }}</p>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-600">{{ py.affiliate_profile?.payer?.name ?? '—' }}</td>
                            <td class="px-6 py-4 text-sm text-gray-900">{{ monthLabel(py.month) }} {{ py.year }}</td>
                            <td class="px-6 py-4 text-sm text-gray-600">{{ py.due_date ?? '—' }}</td>
                            <td class="px-6 py-4">
                                <span :class="['inline-flex px-2.5 py-0.5 rounded-full text-xs font-medium', statusClass(py.status)]">
                                    {{ py.status_label ?? py.status }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-sm text-right font-medium text-gray-900">{{ formatMoney(py.total_amount) }}</td>
                            <td class="px-6 py-4 text-right">
                                <Link :href="`/payrolls/${py.id}`" class="inline-flex items-center justify-center h-9 w-9 rounded-lg text-gray-400 hover:text-brand-700 hover:bg-brand-50 transition-colors" aria-label="Ver">
                                    <Eye class="h-4 w-4" />
                                </Link>
                            </td>
                        </tr>
                        <tr v-if="!payrolls?.data?.length">
                            <td colspan="7" class="px-6 py-12 text-center text-gray-500">No hay planillas con los filtros aplicados</td>
                        </tr>
                    </tbody>
                </table>
                </div>
            </div>

            <div v-if="payrolls?.links?.length" class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                <p v-if="payrolls?.meta" class="text-sm text-gray-500">
                    Mostrando {{ payrolls.meta.from ?? 0 }} a {{ payrolls.meta.to ?? 0 }} de {{ payrolls.meta.total ?? 0 }} resultados
                </p>
                <Pagination :links="payrolls?.links" />
            </div>
        </div>
    </AppLayout>
</template>
