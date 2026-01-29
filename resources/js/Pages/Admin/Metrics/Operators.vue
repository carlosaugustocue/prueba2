<script setup>
import { computed } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { Download, Filter, Users } from 'lucide-vue-next';

const props = defineProps({
    filters: Object,
    rows: Array,
    operators: Array,
    types: Array,
    priorities: Array,
    epsList: Array,
});

const apply = (key, value) => {
    router.get('/admin/metricas/operadores', {
        ...props.filters,
        [key]: value || undefined,
    }, { preserveState: true, replace: true });
};

const clear = () => {
    router.get('/admin/metricas/operadores', {}, { preserveState: true, replace: true });
};

const csvUrl = computed(() => {
    const q = new URLSearchParams({ ...props.filters, format: 'csv' }).toString();
    return `/admin/metricas/operadores?${q}`;
});
</script>

<template>
    <AppLayout>
        <div class="max-w-6xl mx-auto space-y-6">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">Métricas de Operadores</h1>
                    <p class="text-sm text-gray-500">Análisis de tiempos y volumen de gestión (solo administrador)</p>
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
                        :value="filters.priority || ''"
                        @change="apply('priority', $event.target.value)"
                        class="rounded-lg border-gray-300 text-sm focus:border-brand-500 focus:ring-brand-500"
                    >
                        <option value="">Todas las prioridades</option>
                        <option v-for="p in priorities" :key="p.value" :value="p.value">{{ p.label }}</option>
                    </select>

                    <select
                        :value="filters.eps_id || ''"
                        @change="apply('eps_id', $event.target.value)"
                        class="rounded-lg border-gray-300 text-sm focus:border-brand-500 focus:ring-brand-500"
                    >
                        <option value="">Todas las EPS</option>
                        <option v-for="e in epsList" :key="e.id" :value="e.id">{{ e.name }}</option>
                    </select>

                    <button
                        @click="clear"
                        class="text-sm text-gray-500 hover:text-gray-700"
                    >
                        Limpiar
                    </button>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200 bg-gray-50 flex items-center gap-2">
                    <Users class="h-5 w-5 text-brand-600" />
                    <h2 class="font-semibold text-gray-900">Resumen por operador</h2>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-white">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Operador</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Cerradas</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Obtenidas</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">No obtenidas</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Canceladas</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Prom. total (min)</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Prom. espera (min)</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Prom. gestión (min)</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <tr v-for="r in rows" :key="r.operator_id" class="hover:bg-gray-50">
                                <td class="px-6 py-4 text-sm font-medium text-gray-900">{{ r.operator_name }}</td>
                                <td class="px-6 py-4 text-sm text-gray-700 text-right">{{ r.total_cerradas }}</td>
                                <td class="px-6 py-4 text-sm text-gray-700 text-right">{{ r.total_obtenidas }}</td>
                                <td class="px-6 py-4 text-sm text-gray-700 text-right">{{ r.total_no_obtenidas }}</td>
                                <td class="px-6 py-4 text-sm text-gray-700 text-right">{{ r.total_canceladas }}</td>
                                <td class="px-6 py-4 text-sm text-gray-700 text-right">{{ r.avg_total_min ?? '-' }}</td>
                                <td class="px-6 py-4 text-sm text-gray-700 text-right">{{ r.avg_espera_min ?? '-' }}</td>
                                <td class="px-6 py-4 text-sm text-gray-700 text-right">{{ r.avg_gestion_min ?? '-' }}</td>
                            </tr>
                            <tr v-if="!rows?.length">
                                <td colspan="8" class="px-6 py-10 text-center text-sm text-gray-500">
                                    No hay datos para el rango seleccionado.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="flex flex-wrap gap-3 text-sm">
                <Link href="/admin/metricas/tiempos" class="text-brand-600 hover:text-brand-700 font-medium">Ver resumen de tiempos →</Link>
                <Link href="/admin/anotaciones" class="text-brand-600 hover:text-brand-700 font-medium">Ver anotaciones →</Link>
            </div>
        </div>
    </AppLayout>
</template>

