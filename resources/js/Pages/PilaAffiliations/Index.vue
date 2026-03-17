<script setup>
import { ref, watch } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Pagination from '@/Components/Pagination.vue';
import { Search, Eye, Pencil, UserPlus } from 'lucide-vue-next';

const props = defineProps({
    affiliations: Object,
    filters: Object,
});

const search = ref(props.filters?.search || '');

const applyFilters = () => {
    router.get('/pila/affiliations', { search: search.value || undefined }, { preserveState: true, replace: true });
};

let t;
watch(search, () => {
    clearTimeout(t);
    t = setTimeout(applyFilters, 500);
});
</script>

<template>
    <AppLayout>
        <div class="space-y-6">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">Afiliaciones (PILA)</h1>
                    <p class="mt-1 text-sm text-gray-500">Registro operativo del afiliado: tipo cotizante, entidades, estado de pago</p>
                </div>
                <Link href="/pila/affiliations/create" class="inline-flex items-center justify-center px-4 py-2 font-medium rounded-lg bg-brand-500 text-white hover:bg-brand-600">
                    <UserPlus class="h-5 w-5 mr-2" />
                    Nueva afiliación
                </Link>
            </div>

            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4">
                <div class="relative">
                    <Search class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-gray-400" />
                    <input v-model="search" type="text" placeholder="Buscar por nombre o documento del afiliado..." class="block w-full pl-10 rounded-lg border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500" />
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                <div class="overflow-x-auto min-w-0">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Afiliado</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tipo cotizante</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Operador</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Acciones</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <tr v-for="a in affiliations.data" :key="a.id" class="hover:bg-gray-50">
                                <td class="px-6 py-4">
                                    <div>
                                        <p class="font-medium text-gray-900">{{ a.affiliate?.full_name || '—' }}</p>
                                        <p class="text-sm text-gray-500">
                                            {{ a.affiliate?.document_type_abbreviation }} {{ a.affiliate?.document_number }}
                                        </p>
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-700">{{ a.cotizante_type?.code || '—' }}</td>
                                <td class="px-6 py-4 text-sm text-gray-700">{{ a.pila_operator || '—' }}</td>
                                <td class="px-6 py-4 text-right space-x-2">
                                    <Link :href="`/pila/affiliations/${a.id}`" class="inline-flex items-center justify-center h-9 w-9 rounded-lg text-gray-400 hover:text-gray-700 hover:bg-gray-100 transition-colors" aria-label="Ver">
                                        <Eye class="h-4 w-4" />
                                    </Link>
                                    <Link :href="`/pila/affiliations/${a.id}/edit`" class="inline-flex items-center justify-center h-9 w-9 rounded-lg text-gray-400 hover:text-brand-700 hover:bg-brand-50 transition-colors" aria-label="Editar">
                                        <Pencil class="h-4 w-4" />
                                    </Link>
                                </td>
                            </tr>
                            <tr v-if="!affiliations.data?.length">
                                <td colspan="4" class="px-6 py-12 text-center text-gray-500">No se encontraron afiliaciones</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <div v-if="affiliations?.links?.length" class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                <p v-if="affiliations?.meta" class="text-sm text-gray-500">
                    Mostrando {{ affiliations.meta.from ?? 0 }} a {{ affiliations.meta.to ?? 0 }} de {{ affiliations.meta.total ?? 0 }} resultados
                </p>
                <Pagination :links="affiliations?.links" />
            </div>
        </div>
    </AppLayout>
</template>

