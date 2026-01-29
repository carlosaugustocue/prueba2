<script setup>
import { computed } from 'vue';
import { Link, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { ArrowLeft, Save, Shield, KeyRound, Phone, Mail, User, UserCog } from 'lucide-vue-next';

const props = defineProps({
    user: Object,
    roles: Array,
});

const form = useForm({
    name: props.user?.name || '',
    email: props.user?.email || '',
    phone: props.user?.phone || '',
    role_id: props.user?.role_id || '',
    is_active: !!props.user?.is_active,
    password: '',
    password_confirmation: '',
});

const selectedRole = computed(() => props.roles?.find(r => String(r.id) === String(form.role_id)));
const rolePermissions = computed(() => selectedRole.value?.permissions || []);

const submit = () => {
    form.put(`/admin/usuarios/${props.user.id}`);
};
</script>

<template>
    <AppLayout>
        <div class="max-w-3xl mx-auto space-y-6">
            <!-- Header -->
            <div>
                <Link href="/admin/usuarios" class="inline-flex items-center gap-2 text-sm text-gray-500 hover:text-brand-700 mb-2">
                    <ArrowLeft class="h-4 w-4" />
                    Volver a usuarios
                </Link>
                <h1 class="text-2xl font-bold text-gray-900 flex items-center gap-2">
                    <UserCog class="h-6 w-6 text-brand-600" />
                    Editar usuario
                </h1>
                <p class="mt-1 text-sm text-gray-500">Actualice datos, rol y estado</p>
            </div>

            <form @submit.prevent="submit" class="space-y-6">
                <!-- Datos -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 space-y-5">
                    <div>
                        <label class="flex items-center gap-2 text-sm font-semibold text-gray-900 mb-2">
                            <User class="h-4 w-4 text-brand-600" />
                            Nombre *
                        </label>
                        <input v-model="form.name" type="text" class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500" />
                        <p v-if="form.errors.name" class="mt-2 text-sm text-red-600">{{ form.errors.name }}</p>
                    </div>

                    <div>
                        <label class="flex items-center gap-2 text-sm font-semibold text-gray-900 mb-2">
                            <Mail class="h-4 w-4 text-brand-600" />
                            Correo *
                        </label>
                        <input v-model="form.email" type="email" class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500" />
                        <p v-if="form.errors.email" class="mt-2 text-sm text-red-600">{{ form.errors.email }}</p>
                    </div>

                    <div>
                        <label class="flex items-center gap-2 text-sm font-semibold text-gray-900 mb-2">
                            <Phone class="h-4 w-4 text-brand-600" />
                            Teléfono (opcional)
                        </label>
                        <input v-model="form.phone" type="text" class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500" />
                        <p v-if="form.errors.phone" class="mt-2 text-sm text-red-600">{{ form.errors.phone }}</p>
                    </div>
                </div>

                <!-- Rol -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 space-y-4">
                    <div class="flex items-center justify-between gap-4">
                        <h2 class="text-lg font-semibold text-gray-900 flex items-center gap-2">
                            <Shield class="h-5 w-5 text-brand-600" />
                            Rol y permisos
                        </h2>
                        <label class="inline-flex items-center gap-2 text-sm text-gray-700">
                            <input v-model="form.is_active" type="checkbox" class="rounded border-gray-300 text-brand-600 focus:ring-brand-500" />
                            Activo
                        </label>
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-gray-900 mb-2">Rol *</label>
                        <select v-model="form.role_id" class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500">
                            <option value="" disabled>Seleccione...</option>
                            <option v-for="r in roles" :key="r.id" :value="r.id">{{ r.display_name }}</option>
                        </select>
                        <p v-if="form.errors.role_id" class="mt-2 text-sm text-red-600">{{ form.errors.role_id }}</p>
                    </div>

                    <div v-if="selectedRole" class="bg-gray-50 border border-gray-200 rounded-lg p-4">
                        <p class="text-sm font-medium text-gray-900">{{ selectedRole.display_name }}</p>
                        <p class="text-xs text-gray-500 mt-0.5">{{ selectedRole.name }}</p>
                        <div class="mt-3 flex flex-wrap gap-2">
                            <span v-for="p in rolePermissions" :key="p" class="text-xs bg-white border border-gray-200 rounded-full px-2 py-0.5 text-gray-700">
                                {{ p }}
                            </span>
                            <span v-if="!rolePermissions?.length" class="text-xs text-gray-500">Sin permisos definidos</span>
                        </div>
                    </div>
                </div>

                <!-- Contraseña (opcional) -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 space-y-4">
                    <h2 class="text-lg font-semibold text-gray-900 flex items-center gap-2">
                        <KeyRound class="h-5 w-5 text-brand-600" />
                        Cambiar contraseña (opcional)
                    </h2>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-semibold text-gray-900 mb-2">Nueva contraseña</label>
                            <input v-model="form.password" type="password" class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500" />
                            <p v-if="form.errors.password" class="mt-2 text-sm text-red-600">{{ form.errors.password }}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-900 mb-2">Confirmación</label>
                            <input v-model="form.password_confirmation" type="password" class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500" />
                        </div>
                    </div>
                </div>

                <!-- Actions -->
                <div class="flex items-center justify-end gap-2">
                    <Link href="/admin/usuarios" class="px-4 py-2 rounded-lg border border-gray-300 text-gray-700 hover:bg-gray-50">
                        Cancelar
                    </Link>
                    <button type="submit" :disabled="form.processing" class="inline-flex items-center px-4 py-2 rounded-lg bg-brand-500 text-white hover:bg-brand-600 disabled:opacity-60">
                        <Save class="h-5 w-5 mr-2" />
                        Guardar cambios
                    </button>
                </div>
            </form>
        </div>
    </AppLayout>
</template>

