<script setup>
import { ref, watch } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Pagination from '@/Components/Pagination.vue';
import { ChevronLeft, Plus, Pencil, Trash2, Search } from 'lucide-vue-next';
import { confirmDialog } from '@/Utils/swal';

const props = defineProps({
    parameters: Object,
    filters: Object,
    types: Object,
    valueTypes: Object,
});

const baseUrl = '/admin/configuracion/contribution-parameters';
const search = ref(props.filters?.search || '');
const typeFilter = ref(props.filters?.type || '');

const applyFilters = () => {
    router.get(baseUrl, {
        search: search.value || undefined,
        type: typeFilter.value || undefined,
    }, { preserveState: true, replace: true });
};

let searchTimeout;
watch(search, () => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(applyFilters, 400);
});
watch(typeFilter, applyFilters);

const formatDate = (d) => {
    if (!d) return '—';
    return new Date(d).toLocaleDateString('es-CO', { year: 'numeric', month: 'short', day: 'numeric' });
};

const formatValue = (p) => {
    if (p.value_type === 'PERCENTAGE') return `${Number(p.value)}%`;
    if (p.value_type === 'AMOUNT') return new Intl.NumberFormat('es-CO', { style: 'currency', currency: 'COP', maximumFractionDigits: 0 }).format(p.value);
    return p.value;
};

const destroy = (p) => {
    confirmDialog({
        title: 'Eliminar parámetro',
        text: `¿Eliminar ${p.type} / ${p.subtype} (vigente desde ${formatDate(p.valid_from)})?`,
        confirmButtonText: 'Eliminar',
        icon: 'warning',
    }).then((ok) => {
        if (!ok) return;
        router.delete(`${baseUrl}/${p.id}`, { preserveScroll: true });
    });
};
</script>

<template>
    <AppLayout>
        <div class="max-w-6xl mx-auto space-y-6">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div class="flex items-center gap-3">
                    <Link
                        href="/admin/configuracion"
                        class="inline-flex items-center justify-center h-10 w-10 rounded-lg text-gray-500 hover:bg-gray-100 hover:text-gray-700 transition-colors"
                        title="Volver a Configuración"
                    >
                        <ChevronLeft class="h-5 w-5" />
                    </Link>
                    <div>
                        <h1 class="text-2xl font-bold text-gray-900">Parámetros de aportes</h1>
                        <p class="text-sm text-gray-500">Porcentajes, SMLMV y vigencia normativa</p>
                    </div>
                </div>
                <Link
                    :href="`${baseUrl}/create`"
                    class="inline-flex items-center justify-center gap-2 px-4 py-2 font-medium rounded-lg bg-brand-500 text-white hover:bg-brand-600 transition-colors"
                >
                    <Plus class="h-5 w-5" />
                    Nuevo parámetro
                </Link>
            </div>

            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4">
                <div class="flex flex-col sm:flex-row gap-4 flex-wrap">
                    <div class="relative flex-1 min-w-[200px]">
                        <Search class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-gray-400" />
                        <input
                            v-model="search"
                            type="text"
                            placeholder="Buscar por subtipo, descripción..."
                            class="block w-full pl-10 rounded-lg border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500"
                        />
                    </div>
                    <select
                        v-model="typeFilter"
                        class="rounded-lg border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500 w-48"
                    >
                        <option value="">Todos los tipos</option>
                        <option v-for="(label, key) in types" :key="key" :value="key">{{ label }}</option>
                    </select>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tipo</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Subtipo</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Valor</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tipo valor</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Vigencia desde</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Vigencia hasta</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Descripción</th>
                                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Acciones</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <tr v-for="p in (parameters?.data ?? [])" :key="p.id" class="hover:bg-gray-50">
                                <td class="px-4 py-3 text-sm font-medium text-gray-900">{{ types[p.type] || p.type }}</td>
                                <td class="px-4 py-3 text-sm text-gray-700">{{ p.subtype }}</td>
                                <td class="px-4 py-3 text-sm">{{ formatValue(p) }}</td>
                                <td class="px-4 py-3 text-sm text-gray-500">{{ valueTypes[p.value_type] || p.value_type }}</td>
                                <td class="px-4 py-3 text-sm text-gray-600">{{ formatDate(p.valid_from) }}</td>
                                <td class="px-4 py-3 text-sm text-gray-600">{{ formatDate(p.valid_to) }}</td>
                                <td class="px-4 py-3 text-sm text-gray-500 whitespace-normal min-w-[180px] max-w-md">{{ p.description || '—' }}</td>
                                <td class="px-4 py-3 text-right space-x-2 whitespace-nowrap">
                                    <Link
                                        :href="`${baseUrl}/${p.id}/edit`"
                                        class="inline-flex items-center justify-center h-9 w-9 rounded-lg text-gray-400 hover:text-brand-700 hover:bg-brand-50 transition-colors"
                                        aria-label="Editar"
                                        title="Editar"
                                    >
                                        <Pencil class="h-4 w-4" />
                                    </Link>
                                    <button
                                        type="button"
                                        @click="destroy(p)"
                                        class="inline-flex items-center justify-center h-9 w-9 rounded-lg text-gray-400 hover:text-red-700 hover:bg-red-50 transition-colors"
                                        aria-label="Eliminar"
                                        title="Eliminar"
                                    >
                                        <Trash2 class="h-4 w-4" />
                                    </button>
                                </td>
                            </tr>
                            <tr v-if="!(parameters?.data?.length)">
                                <td colspan="8" class="px-4 py-10 text-center text-sm text-gray-500">
                                    No hay parámetros. <Link :href="`${baseUrl}/create`" class="text-brand-600 hover:text-brand-700">Crear uno</Link>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div v-if="parameters?.links?.length" class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 px-4 py-3 border-t border-gray-200">
                    <p v-if="parameters?.meta" class="text-sm text-gray-500">
                        Mostrando {{ parameters.meta.from ?? 0 }} a {{ parameters.meta.to ?? 0 }} de {{ parameters.meta.total ?? 0 }}
                    </p>
                    <Pagination :links="parameters?.links" />
                </div>
            </div>
        </div>
    </AppLayout>
</template>
