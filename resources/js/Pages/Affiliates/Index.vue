<script setup>
import { ref, watch } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Pagination from '@/Components/Pagination.vue';
import { UserPlus, Search, Eye, Pencil } from 'lucide-vue-next';

const props = defineProps({
    affiliates: Object,
    filters: Object,
    epsList: Array,
});

const search = ref(props.filters?.search || '');

const applyFilters = () => {
    router.get('/affiliates', { search: search.value || undefined }, { preserveState: true, replace: true });
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
                    <h1 class="text-2xl font-bold text-gray-900">Afiliados</h1>
                    <p class="mt-1 text-sm text-gray-500">Gestión de afiliados registrados</p>
                </div>
                <Link href="/affiliates/create" class="inline-flex items-center justify-center px-4 py-2 font-medium rounded-lg bg-brand-500 text-white hover:bg-brand-600">
                    <UserPlus class="h-5 w-5 mr-2" />
                    Nuevo Afiliado
                </Link>
            </div>

            <!-- Búsqueda -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4">
                <div class="relative">
                    <Search class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-gray-400" />
                    <input v-model="search" type="text" placeholder="Buscar por nombre, documento..." class="block w-full pl-10 rounded-lg border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500" />
                </div>
            </div>

            <!-- Tabla -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Afiliado</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Documento</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">EPS</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Teléfono</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <tr v-for="affiliate in affiliates.data" :key="affiliate.id" class="hover:bg-gray-50">
                            <td class="px-6 py-4">
                                <div class="flex items-center space-x-3">
                                    <div class="h-10 w-10 rounded-full bg-brand-100 flex items-center justify-center">
                                        <span class="text-brand-700 font-medium text-sm">{{ affiliate.first_name?.charAt(0) }}{{ affiliate.last_name?.charAt(0) }}</span>
                                    </div>
                                    <div>
                                        <div class="flex items-center gap-2 flex-wrap">
                                            <Link
                                                :href="`/affiliates/${affiliate.id}`"
                                                class="font-medium text-gray-900 hover:text-brand-700 hover:underline underline-offset-2"
                                            >
                                                {{ affiliate.full_name }}
                                            </Link>
                                            <span v-if="affiliate.status" :class="[
                                                'inline-flex px-2 py-0.5 rounded text-xs font-medium',
                                                affiliate.status === 'ACTIVO' ? 'bg-green-100 text-green-800' : '',
                                                affiliate.status === 'INACTIVO' ? 'bg-red-100 text-red-800' : '',
                                                affiliate.status === 'SUSPENDIDO' ? 'bg-amber-100 text-amber-800' : '',
                                                !['ACTIVO','INACTIVO','SUSPENDIDO'].includes(affiliate.status) ? 'bg-gray-100 text-gray-700' : ''
                                            ]">{{ affiliate.status }}</span>
                                        </div>
                                        <p class="text-sm text-gray-500">{{ affiliate.patient_type_label }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-900">{{ affiliate.document_type_abbreviation }} {{ affiliate.document_number }}</td>
                            <td class="px-6 py-4 text-sm text-gray-500">{{ affiliate.eps?.name || '-' }}</td>
                            <td class="px-6 py-4 text-sm text-gray-500">{{ affiliate.whatsapp || affiliate.phone || '-' }}</td>
                            <td class="px-6 py-4 text-right space-x-2">
                                <Link :href="`/affiliates/${affiliate.id}`" class="inline-flex items-center justify-center h-9 w-9 rounded-lg text-gray-400 hover:text-gray-700 hover:bg-gray-100 transition-colors" aria-label="Ver">
                                    <Eye class="h-4 w-4" />
                                </Link>
                                <Link :href="`/affiliates/${affiliate.id}/edit`" class="inline-flex items-center justify-center h-9 w-9 rounded-lg text-gray-400 hover:text-brand-700 hover:bg-brand-50 transition-colors" aria-label="Editar">
                                    <Pencil class="h-4 w-4" />
                                </Link>
                            </td>
                        </tr>
                        <tr v-if="!affiliates.data?.length">
                            <td colspan="5" class="px-6 py-12 text-center text-gray-500">No se encontraron afiliados</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div v-if="affiliates?.links?.length" class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                <p v-if="affiliates?.meta" class="text-sm text-gray-500">
                    Mostrando {{ affiliates.meta.from ?? 0 }} a {{ affiliates.meta.to ?? 0 }} de {{ affiliates.meta.total ?? 0 }} resultados
                </p>
                <Pagination :links="affiliates?.links" />
            </div>
        </div>
    </AppLayout>
</template>
