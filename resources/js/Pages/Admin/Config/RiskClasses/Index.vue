<script setup>
import { Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { ChevronLeft, Plus, Pencil, Trash2 } from 'lucide-vue-next';
import { confirmDialog } from '@/Utils/swal';

const props = defineProps({
    items: Array,
});

const baseUrl = '/admin/configuracion/clases-riesgo-arl';

const formatRate = (rate) => {
    if (rate == null) return '—';
    const pct = Number(rate) * 100;
    return `${pct.toFixed(3)}%`;
};

const destroy = (item) => {
    const label = item.level === 0 ? 'No aplica' : `${item.level} (${item.class_name || ''}) — ${item.description}`;
    confirmDialog({
        title: 'Eliminar clase de riesgo',
        text: `¿Eliminar "${label}"? Esta acción no se puede deshacer.`,
        confirmButtonText: 'Eliminar',
        icon: 'warning',
    }).then((ok) => {
        if (!ok) return;
        router.delete(`${baseUrl}/${item.id}`, { preserveScroll: true });
    });
};
</script>

<template>
    <AppLayout>
        <div class="max-w-4xl mx-auto space-y-6">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div class="flex items-center gap-3">
                    <Link
                        :href="'/admin/configuracion'"
                        class="inline-flex items-center justify-center h-10 w-10 rounded-lg text-gray-500 hover:bg-gray-100 hover:text-gray-700 transition-colors"
                        title="Volver a Configuración"
                    >
                        <ChevronLeft class="h-5 w-5" />
                    </Link>
                    <div>
                        <h1 class="text-2xl font-bold text-gray-900">Clases de riesgo ARL</h1>
                        <p class="text-sm text-gray-500">Niveles 0–5 según normativa colombiana (tarifa %)</p>
                    </div>
                </div>
                <Link
                    :href="`${baseUrl}/create`"
                    class="inline-flex items-center justify-center gap-2 px-4 py-2 font-medium rounded-lg bg-brand-500 text-white hover:bg-brand-600 transition-colors"
                >
                    <Plus class="h-5 w-5" />
                    Nueva clase
                </Link>
            </div>

            <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nivel</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Clase</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Descripción</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tarifa %</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Estado</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Acciones</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <tr v-for="item in (items || [])" :key="item.id" class="hover:bg-gray-50">
                                <td class="px-6 py-4 text-sm font-medium text-gray-900">{{ item.level }}</td>
                                <td class="px-6 py-4 text-sm text-gray-600">{{ item.class_name || '—' }}</td>
                                <td class="px-6 py-4 text-sm text-gray-900">{{ item.description }}</td>
                                <td class="px-6 py-4 text-sm font-mono text-gray-700">{{ formatRate(item.rate) }}</td>
                                <td class="px-6 py-4">
                                    <span
                                        :class="item.is_active ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-600'"
                                        class="inline-flex px-2.5 py-0.5 rounded-full text-xs font-medium"
                                    >
                                        {{ item.is_active ? 'Activo' : 'Inactivo' }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-right space-x-2 whitespace-nowrap">
                                    <Link
                                        :href="`${baseUrl}/${item.id}/edit`"
                                        class="inline-flex items-center gap-1 px-2 py-1.5 text-sm font-medium rounded-lg text-brand-600 hover:bg-brand-50 transition-colors"
                                    >
                                        <Pencil class="h-4 w-4" />
                                        Editar
                                    </Link>
                                    <button
                                        type="button"
                                        class="inline-flex items-center gap-1 px-2 py-1.5 text-sm font-medium rounded-lg text-red-600 hover:bg-red-50 transition-colors"
                                        @click="destroy(item)"
                                    >
                                        <Trash2 class="h-4 w-4" />
                                        Eliminar
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <p v-if="!items?.length" class="p-6 text-sm text-gray-500 text-center">
                    No hay clases de riesgo registradas. Ejecute el seeder o cree las 6 clases (0–5) según la normativa.
                </p>
            </div>
        </div>
    </AppLayout>
</template>
