<script setup>
import { computed, ref, watch } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Pagination from '@/Components/Pagination.vue';
import { UserCog, UserPlus, Search, Pencil, Power, Trash2, Filter, X } from 'lucide-vue-next';
import { confirmDialog } from '@/Utils/swal';

const props = defineProps({
    users: Object,
    roles: Array,
    filters: Object,
});

const search = ref(props.filters?.search || '');
const roleId = ref(props.filters?.role_id || '');
const status = ref(props.filters?.status || '');
let searchTimeout;

const hasFilters = computed(() => !!(search.value || roleId.value || status.value));

const applyFilters = () => {
    router.get('/admin/usuarios', {
        search: search.value || undefined,
        role_id: roleId.value || undefined,
        status: status.value || undefined,
    }, {
        preserveState: true,
        replace: true,
    });
};

watch(search, () => {
    // debounce simple
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(applyFilters, 350);
});

const clearFilters = () => {
    search.value = '';
    roleId.value = '';
    status.value = '';
    applyFilters();
};

const toggleActive = (u) => {
    confirmDialog({
        title: u.is_active ? 'Desactivar usuario' : 'Activar usuario',
        text: `¿${u.is_active ? 'Desactivar' : 'Activar'} a ${u.name}?`,
        confirmButtonText: u.is_active ? 'Desactivar' : 'Activar',
        icon: 'warning',
    }).then((ok) => {
        if (!ok) return;
        router.patch(`/admin/usuarios/${u.id}/toggle-active`, {}, { preserveScroll: true });
    });
};

const destroyUser = (u) => {
    confirmDialog({
        title: 'Eliminar usuario',
        text: `¿Eliminar a ${u.name}? Esta acción no se puede deshacer.`,
        confirmButtonText: 'Eliminar',
        icon: 'warning',
    }).then((ok) => {
        if (!ok) return;
        router.delete(`/admin/usuarios/${u.id}`, { preserveScroll: true });
    });
};
</script>

<template>
    <AppLayout>
        <div class="max-w-6xl mx-auto space-y-6">
            <!-- Header -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 flex items-center gap-2">
                        <UserCog class="h-6 w-6 text-brand-600" />
                        Usuarios
                    </h1>
                    <p class="mt-1 text-sm text-gray-500">Administración de usuarios y roles</p>
                </div>
                <Link href="/admin/usuarios/create" class="inline-flex items-center justify-center px-4 py-2 font-medium rounded-lg bg-brand-500 text-white hover:bg-brand-600 transition-colors">
                    <UserPlus class="h-5 w-5 mr-2" />
                    Nuevo usuario
                </Link>
            </div>

            <!-- Filters -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4">
                <div class="flex flex-col lg:flex-row gap-4">
                    <div class="flex-1">
                        <div class="relative">
                            <Search class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-gray-400" />
                            <input
                                v-model="search"
                                type="text"
                                placeholder="Buscar por nombre, correo o teléfono..."
                                class="block w-full pl-10 rounded-lg border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500"
                            />
                        </div>
                    </div>

                    <div class="w-full lg:w-64">
                        <label class="sr-only">Rol</label>
                        <select v-model="roleId" @change="applyFilters" class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500">
                            <option value="">Todos los roles</option>
                            <option v-for="r in roles" :key="r.id" :value="r.id">{{ r.display_name }}</option>
                        </select>
                    </div>

                    <div class="w-full lg:w-56">
                        <label class="sr-only">Estado</label>
                        <select v-model="status" @change="applyFilters" class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500">
                            <option value="">Todos los estados</option>
                            <option value="active">Activos</option>
                            <option value="inactive">Inactivos</option>
                        </select>
                    </div>

                    <button v-if="hasFilters" type="button" @click="clearFilters" class="inline-flex items-center justify-center px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200">
                        <X class="h-4 w-4 mr-1" />
                        Limpiar
                    </button>
                </div>
            </div>

            <!-- Table -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Usuario</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Rol</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Estado</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Creado</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Acciones</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <tr v-for="u in users.data" :key="u.id" class="hover:bg-gray-50">
                                <td class="px-6 py-4">
                                    <div class="text-sm font-medium text-gray-900">{{ u.name }}</div>
                                    <div class="text-sm text-gray-500">{{ u.email }}</div>
                                    <div v-if="u.phone" class="text-xs text-gray-400 mt-0.5">{{ u.phone }}</div>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-700">
                                    {{ u.role?.display_name || 'Sin rol' }}
                                </td>
                                <td class="px-6 py-4">
                                    <span :class="u.is_active ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-700'" class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium">
                                        {{ u.is_active ? 'Activo' : 'Inactivo' }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-500">
                                    {{ u.created_at_formatted || '-' }}
                                </td>
                                <td class="px-6 py-4 text-right space-x-2 whitespace-nowrap">
                                    <button
                                        type="button"
                                        @click="toggleActive(u)"
                                        class="inline-flex items-center justify-center h-9 w-9 rounded-lg text-gray-400 hover:text-gray-700 hover:bg-gray-100 transition-colors"
                                        :aria-label="u.is_active ? 'Desactivar' : 'Activar'"
                                        :title="u.is_active ? 'Desactivar' : 'Activar'"
                                    >
                                        <Power class="h-4 w-4" />
                                    </button>
                                    <Link
                                        :href="`/admin/usuarios/${u.id}/edit`"
                                        class="inline-flex items-center justify-center h-9 w-9 rounded-lg text-gray-400 hover:text-brand-700 hover:bg-brand-50 transition-colors"
                                        aria-label="Editar"
                                        title="Editar"
                                    >
                                        <Pencil class="h-4 w-4" />
                                    </Link>
                                    <button
                                        type="button"
                                        @click="destroyUser(u)"
                                        class="inline-flex items-center justify-center h-9 w-9 rounded-lg text-gray-400 hover:text-red-700 hover:bg-red-50 transition-colors"
                                        aria-label="Eliminar"
                                        title="Eliminar"
                                    >
                                        <Trash2 class="h-4 w-4" />
                                    </button>
                                </td>
                            </tr>

                            <tr v-if="!users.data?.length">
                                <td colspan="5" class="px-6 py-10 text-center text-sm text-gray-500">
                                    No hay usuarios para mostrar.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div v-if="users?.links?.length" class="px-4 py-3 border-t border-gray-200">
                    <Pagination :links="users.links" />
                </div>
            </div>
        </div>
    </AppLayout>
</template>

