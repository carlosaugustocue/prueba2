<script setup>
import { computed } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { Download, Filter, FileText } from 'lucide-vue-next';
import Pagination from '@/Components/Pagination.vue';

const props = defineProps({
    filters: Object,
    items: Object,
    operators: Array,
    types: Array,
    epsList: Array,
});

const rows = computed(() => props.items?.data || []);

const apply = (key, value) => {
    router.get('/admin/anotaciones', {
        ...props.filters,
        [key]: value || undefined,
    }, { preserveState: true, replace: true });
};

const csvUrl = computed(() => {
    const q = new URLSearchParams({ ...props.filters, format: 'csv' }).toString();
    return `/admin/anotaciones?${q}`;
});
</script>

<template>
    <AppLayout>
        <div class="max-w-6xl mx-auto space-y-6">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">Anotaciones</h1>
                    <p class="text-sm text-gray-500">Historial de anotaciones internas (solo administrador)</p>
                </div>
                <a :href="csvUrl" class="inline-flex items-center gap-2 px-4 py-2 bg-white border border-gray-300 rounded-lg text-sm font-medium hover:bg-gray-50">
                    <Download class="h-4 w-4" />
                    Exportar CSV
                </a>
            </div>

            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4">
                <div class="flex flex-wrap items-center gap-3">
                    <div class="flex items-center gap-2 text-gray-500">
                        <Filter class="h-4 w-4" />
                        <span class="text-sm font-medium">Filtros</span>
                    </div>
                    <input
                        type="date"
                        :value="filters.from"
                        @change="apply('from', $event.target.value)"
                        class="rounded-lg border-gray-300 text-sm focus:border-brand-500 focus:ring-brand-500"
                    />
                    <input
                        type="date"
                        :value="filters.to"
                        @change="apply('to', $event.target.value)"
                        class="rounded-lg border-gray-300 text-sm focus:border-brand-500 focus:ring-brand-500"
                    />
                    <select
                        :value="filters.operator_id || ''"
                        @change="apply('operator_id', $event.target.value)"
                        class="rounded-lg border-gray-300 text-sm focus:border-brand-500 focus:ring-brand-500"
                    >
                        <option value="">Todos los operadores</option>
                        <option v-for="op in operators" :key="op.id" :value="op.id">{{ op.name }}</option>
                    </select>
                    <select
                        :value="filters.type || ''"
                        @change="apply('type', $event.target.value)"
                        class="rounded-lg border-gray-300 text-sm focus:border-brand-500 focus:ring-brand-500"
                    >
                        <option value="">Todos los tipos</option>
                        <option v-for="t in types" :key="t.value" :value="t.value">{{ t.label }}</option>
                    </select>
                    <select
                        :value="filters.eps_id || ''"
                        @change="apply('eps_id', $event.target.value)"
                        class="rounded-lg border-gray-300 text-sm focus:border-brand-500 focus:ring-brand-500"
                    >
                        <option value="">Todas las EPS</option>
                        <option v-for="e in epsList" :key="e.id" :value="e.id">{{ e.name }}</option>
                    </select>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200 bg-gray-50 flex items-center gap-2">
                    <FileText class="h-5 w-5 text-brand-600" />
                    <h2 class="font-semibold text-gray-900">Listado</h2>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-white">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Solicitud</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Operador</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Paciente</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">EPS</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actualizado</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Anotación</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <tr v-for="r in rows" :key="r.id" class="hover:bg-gray-50">
                                <td class="px-6 py-4 text-sm">
                                    <Link :href="`/appointment-requests/${r.id}`" class="text-brand-600 hover:text-brand-700 font-medium">
                                        #{{ r.id }}
                                    </Link>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-700">{{ r.assignee?.name || '-' }}</td>
                                <td class="px-6 py-4 text-sm text-gray-700">{{ r.patient?.full_name || '-' }}</td>
                                <td class="px-6 py-4 text-sm text-gray-700">{{ r.patient?.eps?.name || '-' }}</td>
                                <td class="px-6 py-4 text-sm text-gray-700">{{ r.updated_at }}</td>
                                <td class="px-6 py-4 text-sm text-gray-700 whitespace-pre-line">{{ r.operator_notes }}</td>
                            </tr>
                            <tr v-if="!rows.length">
                                <td colspan="6" class="px-6 py-10 text-center text-sm text-gray-500">
                                    No hay anotaciones para el rango seleccionado.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="px-6 py-4 border-t border-gray-200">
                    <Pagination :links="items?.links" />
                </div>
            </div>

            <div class="flex flex-wrap gap-3 text-sm">
                <Link href="/admin/metricas/operadores" class="text-brand-600 hover:text-brand-700 font-medium">← Operadores</Link>
                <Link href="/admin/metricas/tiempos" class="text-brand-600 hover:text-brand-700 font-medium">Tiempos →</Link>
            </div>
        </div>
    </AppLayout>
</template>

