<script setup>
import { ref, watch } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Pagination from '@/Components/Pagination.vue';
import { Building2, Search, Eye, Pencil, UserPlus } from 'lucide-vue-next';

const props = defineProps({
    employers: Object,
    filters: Object,
    allowedDocumentTypes: Array,
});

const search = ref(props.filters?.search || '');
const isActive = ref(props.filters?.is_active ?? '');

const applyFilters = () => {
    router.get('/pila/employers', {
        search: search.value || undefined,
        is_active: isActive.value || undefined,
    }, { preserveState: true, replace: true });
};

let searchTimeout;
watch(search, () => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(applyFilters, 500);
});
watch(isActive, applyFilters);
</script>

<template>
    <AppLayout>
        <div class="space-y-6">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">Empleadores (PILA)</h1>
                    <p class="mt-1 text-sm text-gray-500">Aportantes / empleadores usados para vencimientos y credenciales</p>
                </div>
                <Link href="/pila/employers/create" class="inline-flex items-center justify-center px-4 py-2 font-medium rounded-lg bg-brand-500 text-white hover:bg-brand-600">
                    <UserPlus class="h-5 w-5 mr-2" />
                    Nuevo Empleador
                </Link>
            </div>

            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4">
                <div class="flex flex-col sm:flex-row gap-4">
                    <div class="relative flex-1">
                        <Search class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-gray-400" />
                        <input v-model="search" type="text" placeholder="Buscar por nombre, documento..." class="block w-full pl-10 rounded-lg border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500" />
                    </div>
                    <select v-model="isActive" class="rounded-lg border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500 sm:w-40">
                        <option value="">Todos</option>
                        <option value="1">Activos</option>
                        <option value="0">Inactivos</option>
                    </select>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                <div class="overflow-x-auto min-w-0">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Empleador</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Documento</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Día hábil</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Acciones</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <tr v-for="e in employers.data" :key="e.id" class="hover:bg-gray-50">
                                <td class="px-6 py-4">
                                    <div class="flex items-center space-x-3">
                                        <div class="h-10 w-10 rounded-lg bg-brand-100 flex items-center justify-center">
                                            <Building2 class="h-5 w-5 text-brand-700" />
                                        </div>
                                        <div>
                                            <Link :href="`/pila/employers/${e.id}`" class="font-medium text-gray-900 hover:text-brand-700 hover:underline underline-offset-2">
                                                {{ e.name }}
                                            </Link>
                                            <p v-if="e.email" class="text-sm text-gray-500">{{ e.email }}</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-900">
                                    {{ e.document_type }} {{ e.document_number }}<span v-if="e.check_digit">-{{ e.check_digit }}</span>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-500">{{ e.payment_business_day ?? '—' }}</td>
                                <td class="px-6 py-4 text-right space-x-2">
                                    <Link :href="`/pila/employers/${e.id}`" class="inline-flex items-center justify-center h-9 w-9 rounded-lg text-gray-400 hover:text-gray-700 hover:bg-gray-100 transition-colors" aria-label="Ver">
                                        <Eye class="h-4 w-4" />
                                    </Link>
                                    <Link :href="`/pila/employers/${e.id}/edit`" class="inline-flex items-center justify-center h-9 w-9 rounded-lg text-gray-400 hover:text-brand-700 hover:bg-brand-50 transition-colors" aria-label="Editar">
                                        <Pencil class="h-4 w-4" />
                                    </Link>
                                </td>
                            </tr>
                            <tr v-if="!employers.data?.length">
                                <td colspan="4" class="px-6 py-12 text-center text-gray-500">No se encontraron empleadores</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <div v-if="employers?.links?.length" class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                <p v-if="employers?.meta" class="text-sm text-gray-500">
                    Mostrando {{ employers.meta.from ?? 0 }} a {{ employers.meta.to ?? 0 }} de {{ employers.meta.total ?? 0 }} resultados
                </p>
                <Pagination :links="employers?.links" />
            </div>
        </div>
    </AppLayout>
</template>

