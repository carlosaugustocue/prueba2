<script setup>
import SearchSelect from '@/Components/SearchSelect.vue';

const props = defineProps({
    form: Object,
    affiliateOptions: Array,
    employerOptions: Array,
    cotizanteTypeOptions: Array,
    riskClassOptions: Array,
    epsOptions: Array,
    afpOptions: Array,
    arpOptions: Array,
    ccfOptions: Array,
    submitLabel: String,
});
</script>

<template>
    <form @submit.prevent="$emit('submit')" class="space-y-5">
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Afiliado</label>
                <SearchSelect v-model="form.affiliate_id" :options="affiliateOptions" placeholder="Buscar afiliado..." />
                <p v-if="form.errors.affiliate_id" class="mt-1 text-sm text-red-600">{{ form.errors.affiliate_id }}</p>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Empleador</label>
                <SearchSelect v-model="form.employer_id" :options="employerOptions" placeholder="Buscar empleador..." />
                <p v-if="form.errors.employer_id" class="mt-1 text-sm text-red-600">{{ form.errors.employer_id }}</p>
            </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Tipo cotizante</label>
                <SearchSelect v-model="form.cotizante_type_id" :options="cotizanteTypeOptions" placeholder="Seleccionar..." />
                <p v-if="form.errors.cotizante_type_id" class="mt-1 text-sm text-red-600">{{ form.errors.cotizante_type_id }}</p>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Operador PILA</label>
                <input v-model="form.pila_operator" type="text" class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500" placeholder="arus / simple / ..." />
                <p v-if="form.errors.pila_operator" class="mt-1 text-sm text-red-600">{{ form.errors.pila_operator }}</p>
            </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">IBC</label>
                <input v-model="form.ibc" type="number" min="0" step="0.01" class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500" />
                <p v-if="form.errors.ibc" class="mt-1 text-sm text-red-600">{{ form.errors.ibc }}</p>
            </div>
            <div class="flex items-center gap-4 pt-7">
                <label class="inline-flex items-center gap-2 text-sm text-gray-700">
                    <input v-model="form.pays_parafiscales" type="checkbox" class="rounded border-gray-300 text-brand-600 focus:ring-brand-500" />
                    Paga parafiscales
                </label>
                <label class="inline-flex items-center gap-2 text-sm text-gray-700">
                    <input v-model="form.self_employed" type="checkbox" class="rounded border-gray-300 text-brand-600 focus:ring-brand-500" />
                    Independiente
                </label>
            </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">ARL</label>
                <SearchSelect v-model="form.arp_id" :options="arpOptions" placeholder="Seleccionar ARL..." />
                <p v-if="form.errors.arp_id" class="mt-1 text-sm text-red-600">{{ form.errors.arp_id }}</p>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Clase de riesgo</label>
                <SearchSelect v-model="form.risk_class_id" :options="riskClassOptions" placeholder="Seleccionar..." />
                <p v-if="form.errors.risk_class_id" class="mt-1 text-sm text-red-600">{{ form.errors.risk_class_id }}</p>
            </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">EPS</label>
                <SearchSelect v-model="form.eps_id" :options="epsOptions" placeholder="Seleccionar EPS..." />
                <p v-if="form.errors.eps_id" class="mt-1 text-sm text-red-600">{{ form.errors.eps_id }}</p>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">AFP</label>
                <SearchSelect v-model="form.afp_id" :options="afpOptions" placeholder="Seleccionar AFP..." />
                <p v-if="form.errors.afp_id" class="mt-1 text-sm text-red-600">{{ form.errors.afp_id }}</p>
            </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">CCF</label>
                <SearchSelect v-model="form.ccf_id" :options="ccfOptions" placeholder="Seleccionar CCF..." />
                <p v-if="form.errors.ccf_id" class="mt-1 text-sm text-red-600">{{ form.errors.ccf_id }}</p>
            </div>
            <div class="flex items-center gap-3 pt-7">
                <label class="inline-flex items-center gap-2 text-sm text-gray-700">
                    <input v-model="form.is_current" type="checkbox" class="rounded border-gray-300 text-brand-600 focus:ring-brand-500" />
                    Vigente
                </label>
            </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Periodicidad</label>
                <input v-model="form.payment_periodicity" type="text" class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500" placeholder="actual / vencido" />
                <p v-if="form.errors.payment_periodicity" class="mt-1 text-sm text-red-600">{{ form.errors.payment_periodicity }}</p>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Tipo comprobante</label>
                <input v-model="form.billing_type" type="text" class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500" placeholder="recibo_caja / factura_electronica" />
                <p v-if="form.errors.billing_type" class="mt-1 text-sm text-red-600">{{ form.errors.billing_type }}</p>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Estado pago</label>
                <input v-model="form.payment_status" type="text" class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500" placeholder="current / overdue / anticipated" />
                <p v-if="form.errors.payment_status" class="mt-1 text-sm text-red-600">{{ form.errors.payment_status }}</p>
            </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Último documento</label>
                <input v-model="form.last_document_number" type="text" class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500" />
                <p v-if="form.errors.last_document_number" class="mt-1 text-sm text-red-600">{{ form.errors.last_document_number }}</p>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Último período pagado (AAAAMM)</label>
                <input v-model="form.last_payment_period" type="text" maxlength="6" class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500" placeholder="202603" />
                <p v-if="form.errors.last_payment_period" class="mt-1 text-sm text-red-600">{{ form.errors.last_payment_period }}</p>
            </div>
        </div>

        <div class="flex items-center justify-end gap-3">
            <button type="submit" :disabled="form.processing" class="inline-flex items-center justify-center px-4 py-2 font-medium rounded-lg bg-brand-500 text-white hover:bg-brand-600 disabled:opacity-50">
                {{ submitLabel || 'Guardar' }}
            </button>
        </div>
    </form>
</template>

