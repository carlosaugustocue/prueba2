<script setup>
import { Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { ChevronLeft, Plus, Pencil, Trash2 } from 'lucide-vue-next';
import { confirmDialog } from '@/Utils/swal';

const props = defineProps({
    type: String,
    label: String,
    items: Array,
    routePrefix: String,
});

const baseUrl = `/admin/configuracion/${props.type}`;

const destroy = (item) => {
    confirmDialog({
        title: 'Eliminar registro',
        text: `¿Eliminar "${item.name}"? Esta acción no se puede deshacer.`,
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
                        <h1 class="text-2xl font-bold text-gray-900">{{ label }}</h1>
                        <p class="text-sm text-gray-500">Catálogo de {{ label.toLowerCase() }}</p>
                    </div>
                </div>
                <Link
                    :href="`${baseUrl}/create`"
                    class="inline-flex items-center justify-center gap-2 px-4 py-2 font-medium rounded-lg bg-brand-500 text-white hover:bg-brand-600 transition-colors"
                >
                    <Plus class="h-5 w-5" />
                    Nuevo
                </Link>
            </div>

            <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nombre</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Código</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Estado</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Acciones</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <tr v-for="item in (items || [])" :key="item.id" class="hover:bg-gray-50">
                                <td class="px-6 py-4 text-sm font-medium text-gray-900">{{ item.name }}</td>
                                <td class="px-6 py-4 text-sm text-gray-500">{{ item.code || '—' }}</td>
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
                                        class="inline-flex items-center justify-center h-9 w-9 rounded-lg text-gray-400 hover:text-brand-700 hover:bg-brand-50 transition-colors"
                                        aria-label="Editar"
                                        title="Editar"
                                    >
                                        <Pencil class="h-4 w-4" />
                                    </Link>
                                    <button
                                        type="button"
                                        @click="destroy(item)"
                                        class="inline-flex items-center justify-center h-9 w-9 rounded-lg text-gray-400 hover:text-red-700 hover:bg-red-50 transition-colors"
                                        aria-label="Eliminar"
                                        title="Eliminar"
                                    >
                                        <Trash2 class="h-4 w-4" />
                                    </button>
                                </td>
                            </tr>
                            <tr v-if="!items?.length">
                                <td colspan="4" class="px-6 py-10 text-center text-sm text-gray-500">
                                    No hay registros. <Link :href="`${baseUrl}/create`" class="text-brand-600 hover:text-brand-700">Crear uno</Link>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
