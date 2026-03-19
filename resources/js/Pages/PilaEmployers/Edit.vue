<script setup>
import { useForm, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import EmployerForm from './Form.vue';
import { ChevronLeft } from 'lucide-vue-next';

const props = defineProps({
    employer: Object,
    allowedDocumentTypes: Array,
});

const e = props.employer?.data || props.employer;
const form = useForm({
    document_type: e.document_type || '',
    document_number: e.document_number || '',
    check_digit: e.check_digit || '',
    name: e.name || '',
    address: e.address || '',
    city: e.city || '',
    department: e.department || '',
    phone: e.phone || '',
    email: e.email || '',
    payment_business_day: e.payment_business_day ?? '',
    is_active: !!e.is_active,
    is_self_employed: !!e.is_self_employed,
    notes: e.notes || '',
});

const submit = () => form.put(`/pila/employers/${e.id}`);
</script>

<template>
    <AppLayout>
        <div class="max-w-3xl mx-auto space-y-6">
            <div>
                <Link :href="`/pila/employers/${(employer?.data?.id ?? employer?.id)}`" class="inline-flex items-center gap-2 text-sm text-gray-500 hover:text-brand-700">
                    <ChevronLeft class="h-4 w-4" />
                    Volver al detalle
                </Link>
                <h1 class="mt-2 text-2xl font-bold text-gray-900">Editar empleador</h1>
            </div>

            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                <EmployerForm :form="form" :allowedDocumentTypes="allowedDocumentTypes" submitLabel="Guardar cambios" @submit="submit" />
            </div>
        </div>
    </AppLayout>
</template>

