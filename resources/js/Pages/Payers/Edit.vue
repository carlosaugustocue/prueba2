<script setup>
import { useForm, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { ChevronLeft, Building2 } from 'lucide-vue-next';

const props = defineProps({
    payer: Object,
    documentTypes: Array,
});

const payer = props.payer?.data || props.payer || {};

const form = useForm({
    name: payer.name ?? '',
    document_type: payer.document_type ?? 'nit',
    document_number: payer.document_number ?? '',
    address: payer.address ?? '',
    phone: payer.phone ?? '',
    email: payer.email ?? '',
    contact_person: payer.contact_person ?? '',
    is_active: payer.is_active ?? true,
});
</script>

<template>
    <AppLayout>
        <div class="max-w-2xl mx-auto space-y-6">
            <div>
                <Link href="/payers" class="inline-flex items-center gap-2 text-sm text-gray-500 hover:text-brand-600 transition-colors">
                    <ChevronLeft class="h-4 w-4" />
                    Volver a pagadores
                </Link>
                <h1 class="mt-2 text-2xl font-bold text-gray-900">Editar pagador</h1>
                <p class="mt-1 text-sm text-gray-500">{{ payer.name }}</p>
            </div>

            <form @submit.prevent="form.put(`/payers/${payer.id}`)" class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 space-y-5">
                <div class="flex items-center gap-3 pb-4 border-b border-gray-200">
                    <div class="h-12 w-12 rounded-lg bg-brand-100 flex items-center justify-center">
                        <Building2 class="h-6 w-6 text-brand-700" />
                    </div>
                    <div>
                        <h2 class="font-medium text-gray-900">Datos del pagador</h2>
                        <p class="text-sm text-gray-500">NIT o documento único</p>
                    </div>
                </div>

                <div>
                    <label for="name" class="block text-sm font-medium text-gray-700">Nombre o razón social *</label>
                    <input id="name" v-model="form.name" type="text" required class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500" />
                    <p v-if="form.errors.name" class="mt-1 text-sm text-red-600">{{ form.errors.name }}</p>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label for="document_type" class="block text-sm font-medium text-gray-700">Tipo documento *</label>
                        <select id="document_type" v-model="form.document_type" required class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500">
                            <option v-for="opt in documentTypes" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
                        </select>
                        <p v-if="form.errors.document_type" class="mt-1 text-sm text-red-600">{{ form.errors.document_type }}</p>
                    </div>
                    <div>
                        <label for="document_number" class="block text-sm font-medium text-gray-700">Número documento *</label>
                        <input id="document_number" v-model="form.document_number" type="text" required class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500" />
                        <p v-if="form.errors.document_number" class="mt-1 text-sm text-red-600">{{ form.errors.document_number }}</p>
                    </div>
                </div>

                <div>
                    <label for="address" class="block text-sm font-medium text-gray-700">Dirección</label>
                    <input id="address" v-model="form.address" type="text" class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500" />
                    <p v-if="form.errors.address" class="mt-1 text-sm text-red-600">{{ form.errors.address }}</p>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label for="phone" class="block text-sm font-medium text-gray-700">Teléfono</label>
                        <input id="phone" v-model="form.phone" type="text" class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500" />
                        <p v-if="form.errors.phone" class="mt-1 text-sm text-red-600">{{ form.errors.phone }}</p>
                    </div>
                    <div>
                        <label for="email" class="block text-sm font-medium text-gray-700">Correo electrónico</label>
                        <input id="email" v-model="form.email" type="email" class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500" />
                        <p v-if="form.errors.email" class="mt-1 text-sm text-red-600">{{ form.errors.email }}</p>
                    </div>
                </div>

                <div>
                    <label for="contact_person" class="block text-sm font-medium text-gray-700">Persona de contacto</label>
                    <input id="contact_person" v-model="form.contact_person" type="text" class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500" />
                    <p v-if="form.errors.contact_person" class="mt-1 text-sm text-red-600">{{ form.errors.contact_person }}</p>
                </div>

                <div class="flex items-center">
                    <input id="is_active" v-model="form.is_active" type="checkbox" class="rounded border-gray-300 text-brand-600 focus:ring-brand-500" />
                    <label for="is_active" class="ml-2 text-sm text-gray-700">Activo</label>
                </div>

                <div class="flex flex-wrap gap-3 pt-4 border-t border-gray-200">
                    <button type="submit" :disabled="form.processing" class="inline-flex items-center justify-center px-4 py-2 font-medium rounded-lg bg-brand-500 text-white hover:bg-brand-600 disabled:opacity-50">
                        {{ form.processing ? 'Guardando...' : 'Guardar cambios' }}
                    </button>
                    <Link :href="`/payers/${payer.id}`" class="inline-flex items-center justify-center px-4 py-2 font-medium rounded-lg border border-gray-300 text-gray-700 hover:bg-gray-50">
                        Cancelar
                    </Link>
                </div>
            </form>
        </div>
    </AppLayout>
</template>
