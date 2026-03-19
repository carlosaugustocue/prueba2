<script setup>
import { useForm, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import EmployerForm from './Form.vue';
import { ChevronLeft } from 'lucide-vue-next';

const props = defineProps({
    allowedDocumentTypes: Array,
});

const form = useForm({
    document_type: '',
    document_number: '',
    check_digit: '',
    name: '',
    address: '',
    city: '',
    department: '',
    phone: '',
    email: '',
    payment_business_day: '',
    is_active: true,
    is_self_employed: false,
    notes: '',
});

const submit = () => form.post('/pila/employers');
</script>

<template>
    <AppLayout>
        <div class="max-w-3xl mx-auto space-y-6">
            <div class="flex items-center justify-between">
                <div>
                    <Link href="/pila/employers" class="inline-flex items-center gap-2 text-sm text-gray-500 hover:text-brand-700">
                        <ChevronLeft class="h-4 w-4" />
                        Volver a empleadores
                    </Link>
                    <h1 class="mt-2 text-2xl font-bold text-gray-900">Nuevo empleador</h1>
                    <p class="mt-1 text-sm text-gray-500">Registro de aportante/empleador para operación PILA</p>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                <EmployerForm :form="form" :allowedDocumentTypes="allowedDocumentTypes" submitLabel="Crear" @submit="submit" />
            </div>
        </div>
    </AppLayout>
</template>

