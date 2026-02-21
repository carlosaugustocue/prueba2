<script setup>
import { ref, watch } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Pagination from '@/Components/Pagination.vue';
import { Building2, Search, Eye, Pencil, UserPlus } from 'lucide-vue-next';

const props = defineProps({
    payers: Object,
    filters: Object,
    documentTypes: Array,
});

const search = ref(props.filters?.search || '');
const isActive = ref(props.filters?.is_active ?? '');

const applyFilters = () => {
    router.get('/payers', {
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
                    <h1 class="text-2xl font-bold text-gray-900">Pagadores</h1>
                    <p class="mt-1 text-sm text-gray-500">Quién paga la seguridad social (empresas o personas)</p>
                </div>
                <Link href="/payers/create" class="inline-flex items-center justify-center px-4 py-2 font-medium rounded-lg bg-brand-500 text-white hover:bg-brand-600">
                    <UserPlus class="h-5 w-5 mr-2" />
                    Nuevo Pagador
                </Link>
            </div>

            <!-- Búsqueda y filtro -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4">
                <div class="flex flex-col sm:flex-row gap-4">
                    <div class="relative flex-1">
                        <Search class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-gray-400" />
                        <input v-model="search" type="text" placeholder="Buscar por nombre, NIT, contacto..." class="block w-full pl-10 rounded-lg border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500" />
                    </div>
                    <select v-model="isActive" class="rounded-lg border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500 sm:w-40">
                        <option value="">Todos</option>
                        <option value="1">Activos</option>
                        <option value="0">Inactivos</option>
                    </select>
                </div>
            </div>

            <!-- Tabla -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Pagador</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Documento</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Contacto</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Afiliados</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <tr v-for="payer in payers.data" :key="payer.id" class="hover:bg-gray-50">
                            <td class="px-6 py-4">
                                <div class="flex items-center space-x-3">
                                    <div class="h-10 w-10 rounded-lg bg-brand-100 flex items-center justify-center">
                                        <Building2 class="h-5 w-5 text-brand-700" />
                                    </div>
                                    <div>
                                        <Link :href="`/payers/${payer.id}`" class="font-medium text-gray-900 hover:text-brand-700 hover:underline underline-offset-2">
                                            {{ payer.name }}
                                        </Link>
                                        <p v-if="payer.email" class="text-sm text-gray-500">{{ payer.email }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-900">{{ payer.document_type_abbreviation }} {{ payer.document_number }}</td>
                            <td class="px-6 py-4 text-sm text-gray-500">{{ payer.contact_person || payer.phone || '-' }}</td>
                            <td class="px-6 py-4 text-sm text-gray-500">{{ payer.social_security_profiles_count ?? 0 }}</td>
                            <td class="px-6 py-4 text-right space-x-2">
                                <Link :href="`/payers/${payer.id}`" class="inline-flex items-center justify-center h-9 w-9 rounded-lg text-gray-400 hover:text-gray-700 hover:bg-gray-100 transition-colors" aria-label="Ver">
                                    <Eye class="h-4 w-4" />
                                </Link>
                                <Link :href="`/payers/${payer.id}/edit`" class="inline-flex items-center justify-center h-9 w-9 rounded-lg text-gray-400 hover:text-brand-700 hover:bg-brand-50 transition-colors" aria-label="Editar">
                                    <Pencil class="h-4 w-4" />
                                </Link>
                            </td>
                        </tr>
                        <tr v-if="!payers.data?.length">
                            <td colspan="5" class="px-6 py-12 text-center text-gray-500">No se encontraron pagadores</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div v-if="payers?.links?.length" class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                <p v-if="payers?.meta" class="text-sm text-gray-500">
                    Mostrando {{ payers.meta.from ?? 0 }} a {{ payers.meta.to ?? 0 }} de {{ payers.meta.total ?? 0 }} resultados
                </p>
                <Pagination :links="payers?.links" />
            </div>
        </div>
    </AppLayout>
</template>
