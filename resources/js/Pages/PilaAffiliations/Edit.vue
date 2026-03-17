<script setup>
import { useForm, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import AffiliationForm from './Form.vue';
import { ChevronLeft } from 'lucide-vue-next';

const props = defineProps({
    affiliation: Object,
    affiliateOptions: Array,
    employerOptions: Array,
    cotizanteTypeOptions: Array,
    riskClassOptions: Array,
    epsOptions: Array,
    afpOptions: Array,
    arpOptions: Array,
    ccfOptions: Array,
});

const a = props.affiliation?.data || props.affiliation;
const form = useForm({
    affiliate_id: a.affiliate_id || '',
    employer_id: a.employer_id || '',
    cotizante_type_id: a.cotizante_type_id || '',
    pila_operator: a.pila_operator || '',
    ibc: a.ibc ?? '',
    pays_parafiscales: !!a.pays_parafiscales,
    self_employed: !!a.self_employed,
    arp_id: a.arp_id || '',
    risk_class_id: a.risk_class_id || '',
    ccf_id: a.ccf_id || '',
    eps_id: a.eps_id || '',
    afp_id: a.afp_id || '',
    payment_periodicity: a.payment_periodicity || '',
    billing_type: a.billing_type || '',
    last_document_number: a.last_document_number || '',
    last_payment_period: a.last_payment_period || '',
    payment_status: a.payment_status || '',
    is_current: !!a.is_current,
});

const submit = () => form.put(`/pila/affiliations/${a.id}`);
</script>

<template>
    <AppLayout>
        <div class="max-w-4xl mx-auto space-y-6">
            <div>
                <Link :href="`/pila/affiliations/${(affiliation?.data?.id ?? affiliation?.id)}`" class="inline-flex items-center gap-2 text-sm text-gray-500 hover:text-brand-700">
                    <ChevronLeft class="h-4 w-4" />
                    Volver al detalle
                </Link>
                <h1 class="mt-2 text-2xl font-bold text-gray-900">Editar afiliación PILA</h1>
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
                    submitLabel="Guardar cambios"
                    @submit="submit"
                />
            </div>
        </div>
    </AppLayout>
</template>

