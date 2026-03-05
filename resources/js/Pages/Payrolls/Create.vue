<script setup>
import { ref, computed, watch } from 'vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import SearchSelect from '@/Components/SearchSelect.vue';
import { ChevronLeft, Calculator, User, FileText } from 'lucide-vue-next';

const props = defineProps({
    payers: Array,
    affiliates: Array,
    defaultYear: Number,
    defaultMonth: Number,
    months: Array,
});

const page = usePage();
const flash = computed(() => page.props.flash);
const previewData = computed(() => page.props.flash?.preview || null);

const affiliateId = ref('');
const year = ref(props.defaultYear || new Date().getFullYear());
const month = ref(props.defaultMonth || new Date().getMonth() + 1);
const payerFilter = ref('');
const loading = ref(false);
/** Días trabajados en el mes (solo tipo 51 - independiente flexible). 1-30; vacío = mes completo. */
const daysWorked = ref('');

const filteredAffiliates = computed(() => {
    let list = props.affiliates || [];
    if (payerFilter.value) {
        list = list.filter((a) => String(a.payer_id) === String(payerFilter.value));
    }
    return list;
});

const payerOptions = computed(() =>
    (props.payers || []).map((p) => ({
        id: p.id,
        label: p.name,
        description: p.document_number ? `Documento: ${p.document_number}` : '',
    }))
);

const affiliateOptions = computed(() =>
    filteredAffiliates.value.map((a) => ({
        id: a.id,
        label: `${a.full_name} — ${a.document_number}`,
        description: a.payer_name ? `Pagador: ${a.payer_name}` : 'Sin pagador asignado',
    }))
);

const selectedAffiliate = computed(() => {
    if (!affiliateId.value) return null;
    return (props.affiliates || []).find((a) => String(a.id) === String(affiliateId.value));
});

const previewContractPayerIds = computed(() => {
    const ids = previewData.value?.parameters_used?.contracts?.contract_payer_ids || [];
    return ids.map((id) => Number(id)).filter((id) => Number.isInteger(id) && id > 0);
});

const previewHasPayerMismatch = computed(() => {
    if (previewData.value?.parameters_used?.ibc_source !== 'contracts') return false;
    const profilePayerId = Number(selectedAffiliate.value?.payer_id || 0);
    if (!profilePayerId || !previewContractPayerIds.value.length) return false;
    return !previewContractPayerIds.value.includes(profilePayerId);
});

const previewHasMultipleContractPayers = computed(() => previewContractPayerIds.value.length > 1);
const previewContractsWithoutPayer = computed(() => Number(previewData.value?.parameters_used?.contracts?.contracts_without_payer_count || 0));

/** True si el afiliado seleccionado es tipo 51 (independiente flexible) y debe mostrar días trabajados. */
const isType51 = computed(() => selectedAffiliate.value?.contributor_type_code === '51');

watch(affiliateId, () => { if (!isType51.value) daysWorked.value = ''; });

const runPreview = () => {
    if (!affiliateId.value || !year.value || !month.value) {
        alert('Seleccione afiliado, año y mes.');
        return;
    }
    loading.value = true;
    const payload = {
        affiliate_id: affiliateId.value,
        year: year.value,
        month: month.value,
    };
    if (isType51.value && daysWorked.value) payload.days_worked = Math.min(30, Math.max(1, Number(daysWorked.value)));
    router.post('/payrolls/preview', payload, {
        preserveScroll: true,
        onFinish: () => { loading.value = false; },
    });
};

const createPayroll = () => {
    if (!affiliateId.value || !year.value || !month.value) {
        alert('Seleccione afiliado, año y mes.');
        return;
    }
    loading.value = true;
    const payload = {
        affiliate_id: affiliateId.value,
        year: year.value,
        month: month.value,
    };
    if (isType51.value && daysWorked.value) payload.days_worked = Math.min(30, Math.max(1, Number(daysWorked.value)));
    router.post('/payrolls', payload, {
        preserveScroll: true,
        onFinish: () => { loading.value = false; },
    });
};

const formatMoney = (n) => {
    if (n == null) return '—';
    return new Intl.NumberFormat('es-CO', { style: 'currency', currency: 'COP', maximumFractionDigits: 0 }).format(n);
};
</script>

<template>
    <AppLayout>
        <div class="max-w-2xl mx-auto space-y-6">
            <div>
                <Link href="/payrolls" class="inline-flex items-center gap-2 text-sm text-gray-500 hover:text-brand-600 transition-colors">
                    <ChevronLeft class="h-4 w-4" />
                    Volver a planillas
                </Link>
                <h1 class="mt-2 text-2xl font-bold text-gray-900">Nueva planilla</h1>
                <p class="mt-1 text-sm text-gray-500">Seleccione afiliado y período; simule y luego cree la planilla.</p>
            </div>

            <div v-if="flash?.error" class="rounded-lg bg-red-50 border border-red-200 p-4 text-sm text-red-700">
                {{ flash.error }}
            </div>
            <div v-if="flash?.success" class="rounded-lg bg-green-50 border border-green-200 p-4 text-sm text-green-700">
                {{ flash.success }}
            </div>

            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                <h2 class="text-lg font-semibold text-gray-900 mb-4">Datos de la planilla</h2>
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Pagador (filtrar)</label>
                        <SearchSelect
                            v-model="payerFilter"
                            :options="payerOptions"
                            placeholder="Buscar pagador por nombre o documento..."
                            no-results-text="No hay pagadores que coincidan."
                        />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Afiliado <span class="text-red-500">*</span></label>
                        <SearchSelect
                            v-model="affiliateId"
                            :options="affiliateOptions"
                            placeholder="Buscar afiliado por nombre o documento..."
                            no-results-text="No hay afiliados que coincidan."
                        />
                        <p v-if="!filteredAffiliates.length" class="mt-1 text-xs text-amber-600">No hay afiliados con perfil SS{{ payerFilter ? ' para este pagador' : '' }}.</p>
                    </div>
                    <div v-if="isType51" class="rounded-lg bg-amber-50 border border-amber-200 p-3">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Días trabajados en el mes (tipo 51)</label>
                        <input
                            v-model="daysWorked"
                            type="number"
                            min="1"
                            max="30"
                            placeholder="Ej: 15"
                            class="block w-full max-w-xs rounded-lg border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500"
                        />
                        <p class="mt-1 text-xs text-gray-600">Indique 1 a 30 para aportes proporcionales. Si deja vacío se asume mes completo.</p>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Año</label>
                            <select v-model="year" class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500">
                                <option v-for="y in [new Date().getFullYear(), new Date().getFullYear()-1]" :key="y" :value="y">{{ y }}</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Mes</label>
                            <select v-model="month" class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500">
                                <option v-for="m in months" :key="m.value" :value="m.value">{{ m.label }}</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="mt-6 flex gap-3">
                    <button type="button" :disabled="loading || !affiliateId" @click="runPreview" class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-gray-100 text-gray-700 hover:bg-gray-200 font-medium disabled:opacity-50 disabled:pointer-events-none">
                        <Calculator class="h-4 w-4" />
                        {{ loading ? '...' : 'Simular aportes' }}
                    </button>
                    <button type="button" :disabled="loading || !affiliateId" @click="createPayroll" class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-brand-500 text-white hover:bg-brand-600 font-medium disabled:opacity-50 disabled:pointer-events-none">
                        <FileText class="h-4 w-4" />
                        Crear planilla
                    </button>
                </div>
            </div>

            <div v-if="previewData" class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                <h2 class="text-lg font-semibold text-gray-900 flex items-center gap-2 mb-4">
                    <Calculator class="h-5 w-5 text-brand-600" />
                    Simulación de aportes
                </h2>
                <dl class="space-y-2 text-sm">
                    <div class="flex justify-between">
                        <dt class="text-gray-500">IBC</dt>
                        <dd class="font-medium">{{ formatMoney(previewData.ibc) }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-gray-500">Salud</dt>
                        <dd class="font-medium">{{ formatMoney(previewData.health_total) }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-gray-500">Pensión</dt>
                        <dd class="font-medium">{{ formatMoney(previewData.pension_total) }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-gray-500">ARL</dt>
                        <dd class="font-medium">{{ formatMoney(previewData.arl_amount) }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-gray-500">CCF</dt>
                        <dd class="font-medium">{{ formatMoney(previewData.ccf_amount) }}</dd>
                    </div>
                    <div class="flex justify-between pt-2 border-t border-gray-200 font-medium">
                        <dt class="text-gray-700">Total</dt>
                        <dd class="text-gray-900">{{ formatMoney(previewData.total_amount) }}</dd>
                    </div>
                </dl>
                <div
                    v-if="previewData?.parameters_used?.ibc_source === 'contracts'"
                    class="mt-3 rounded-lg border border-blue-200 bg-blue-50 px-3 py-2 text-xs text-blue-900"
                >
                    IBC consolidado desde contratos activos:
                    {{ previewData?.parameters_used?.contracts?.contracts_count || 0 }} contrato(s),
                    ingreso mensualizado {{ formatMoney(previewData?.parameters_used?.contracts?.total_monthly_income || 0) }},
                    porcentaje {{ previewData?.parameters_used?.contracts?.ibc_percent || 40 }}%.
                </div>
                <div
                    v-if="previewHasPayerMismatch || previewHasMultipleContractPayers || previewContractsWithoutPayer > 0"
                    class="mt-3 rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 text-xs text-amber-900"
                >
                    <p v-if="previewHasPayerMismatch">
                        El pagador del perfil del afiliado no coincide con los pagadores de contratos usados para este cálculo.
                    </p>
                    <p v-if="previewHasMultipleContractPayers" class="mt-1">
                        Se detectaron múltiples pagadores en contratos activos para este período.
                    </p>
                    <p v-if="previewContractsWithoutPayer > 0" class="mt-1">
                        Hay {{ previewContractsWithoutPayer }} contrato(s) activo(s) sin pagador; complete ese dato para evitar inconsistencias.
                    </p>
                </div>
                <p class="mt-3 text-xs text-gray-500">Período: {{ previewData.period_date }}. Use "Crear planilla" para generar la planilla con estos montos (se liquidará al crearla si el perfil es válido).</p>
            </div>
        </div>
    </AppLayout>
</template>
