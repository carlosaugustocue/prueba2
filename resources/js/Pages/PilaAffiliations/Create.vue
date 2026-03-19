<script setup>
import { useForm, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import AffiliationForm from './Form.vue';
import { ChevronLeft } from 'lucide-vue-next';

const props = defineProps({
    affiliateOptions: Array,
    employerOptions: Array,
    cotizanteTypeOptions: Array,
    riskClassOptions: Array,
    epsOptions: Array,
    afpOptions: Array,
    arpOptions: Array,
    ccfOptions: Array,
    paymentStatusOptions: Array,
    paymentPeriodicityOptions: Array,
    billingTypeOptions: Array,
});

const form = useForm({
    affiliate_id: '',
    employer_id: '',
    cotizante_type_id: '',
    pila_operator: '',
    last_novelty_type: '',
    last_novelty_date: '',
    ibc: '',
    pays_parafiscales: false,
    self_employed: false,
    arp_id: '',
    risk_class_id: '',
    ccf_id: '',
    eps_id: '',
    afp_id: '',
    payment_periodicity: '',
    billing_type: '',
    last_document_number: '',
    last_payment_period: '',
    payment_status: '',
    is_current: true,
});

const submit = () => form.post('/pila/affiliations');
</script>

<template>
    <AppLayout>
        <div class="max-w-4xl mx-auto space-y-6">
            <div>
                <Link href="/pila/affiliations" class="inline-flex items-center gap-2 text-sm text-gray-500 hover:text-brand-700">
                    <ChevronLeft class="h-4 w-4" />
                    Volver a afiliaciones
                </Link>
                <h1 class="mt-2 text-2xl font-bold text-gray-900">Nueva afiliación PILA</h1>
            </div>

            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                <AffiliationForm
                    :form="form"
                    :affiliateOptions="affiliateOptions"
                    :employerOptions="employerOptions"
                    :cotizanteTypeOptions="cotizanteTypeOptions"
                    :riskClassOptions="riskClassOptions"
                    :epsOptions="epsOptions"
                    :afpOptions="afpOptions"
                    :arpOptions="arpOptions"
                    :ccfOptions="ccfOptions"
                    :paymentStatusOptions="paymentStatusOptions"
                    :paymentPeriodicityOptions="paymentPeriodicityOptions"
                    :billingTypeOptions="billingTypeOptions"
                    submitLabel="Crear"
                    @submit="submit"
                />
            </div>
        </div>
    </AppLayout>
</template>

