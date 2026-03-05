<script setup>
import { computed } from 'vue';
import { Link, router, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { ChevronLeft, FileText, User, Building2, Calculator, History } from 'lucide-vue-next';

const props = defineProps({
    payroll: Object,
    statusOptions: Array,
});

const payroll = computed(() => props.payroll?.data ?? props.payroll ?? {});

const formatMoney = (n) => {
    if (n == null) return '—';
    return new Intl.NumberFormat('es-CO', { style: 'currency', currency: 'COP', maximumFractionDigits: 0 }).format(n);
};

const monthLabel = (m) => {
    const names = ['', 'Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'];
    return names[m] || m;
};

const statusClass = (s) => {
    const map = {
        PENDING: 'bg-gray-100 text-gray-700',
        SETTLED: 'bg-blue-100 text-blue-700',
        SENT_TO_CLIENT: 'bg-amber-100 text-amber-700',
        PAID: 'bg-green-100 text-green-700',
        OVERDUE: 'bg-red-100 text-red-700',
    };
    return map[s] || 'bg-gray-100 text-gray-600';
};

const canSettle = computed(() => payroll.value.status === 'PENDING' || payroll.value.status === 'OVERDUE');
const canMarkSent = computed(() => payroll.value.status === 'SETTLED');
const canMarkPaid = computed(() => payroll.value.status === 'SENT_TO_CLIENT');

const settle = () => {
    if (!confirm('¿Liquidar esta planilla con los datos vigentes (perfil y/o contratos activos)?')) return;
    router.post(`/payrolls/${payroll.value.id}/settle`, {}, { preserveScroll: true });
};

const markSent = () => {
    router.post(`/payrolls/${payroll.value.id}/mark-sent`, {}, { preserveScroll: true });
};

const markPaid = () => {
    router.post(`/payrolls/${payroll.value.id}/mark-paid`, {}, { preserveScroll: true });
};

const meta = computed(() => payroll.value.calculation_metadata || {});
const contractPayerIds = computed(() => {
    const ids = meta.value?.parameters_used?.contracts?.contract_payer_ids || [];
    return ids.map((id) => Number(id)).filter((id) => Number.isInteger(id) && id > 0);
});
const hasMultipleContractPayers = computed(() => contractPayerIds.value.length > 1);
const hasProfileVsContractsMismatch = computed(() => {
    if (meta.value?.parameters_used?.ibc_source !== 'contracts') return false;
    const profilePayerId = Number(payroll.value?.affiliate_profile?.payer_id || 0);
    if (!profilePayerId || !contractPayerIds.value.length) return false;
    return !contractPayerIds.value.includes(profilePayerId);
});
const contractsWithoutPayerCount = computed(() => Number(meta.value?.parameters_used?.contracts?.contracts_without_payer_count || 0));
</script>

<template>
    <AppLayout>
        <div class="max-w-4xl mx-auto space-y-6">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <Link href="/payrolls" class="inline-flex items-center gap-2 text-sm text-gray-500 hover:text-brand-600 transition-colors">
                        <ChevronLeft class="h-4 w-4" />
                        Volver a planillas
                    </Link>
                    <div class="flex items-center gap-2 mt-1">
                        <span :class="['inline-flex px-2.5 py-0.5 rounded-full text-xs font-medium', statusClass(payroll.status)]">
                            {{ payroll.status_label ?? payroll.status }}
                        </span>
                        <span class="text-sm text-gray-500">Período: {{ monthLabel(payroll.month) }} {{ payroll.year }}</span>
                    </div>
                </div>
                <div class="flex flex-wrap gap-2">
                    <button v-if="canSettle" type="button" @click="settle" class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-blue-600 text-white hover:bg-blue-700 text-sm font-medium">
                        <Calculator class="h-4 w-4" />
                        Liquidar
                    </button>
                    <button v-if="canMarkSent" type="button" @click="markSent" class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-amber-600 text-white hover:bg-amber-700 text-sm font-medium">
                        Marcar enviada
                    </button>
                    <button v-if="canMarkPaid" type="button" @click="markPaid" class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-green-600 text-white hover:bg-green-700 text-sm font-medium">
                        Marcar pagada
                    </button>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <div class="lg:col-span-2 space-y-6">
                    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                        <h2 class="text-lg font-semibold text-gray-900 flex items-center gap-2">
                            <User class="h-5 w-5 text-brand-600" />
                            Afiliado
                        </h2>
                        <div class="mt-4">
                            <Link v-if="payroll.affiliate" :href="`/affiliates/${payroll.affiliate.id}`" class="font-medium text-brand-600 hover:underline">
                                {{ payroll.affiliate.full_name }}
                            </Link>
                            <p class="text-sm text-gray-500 mt-0.5">{{ payroll.affiliate?.document_number ?? '—' }}</p>
                        </div>
                        <div v-if="payroll.affiliate_profile?.payer" class="mt-3 flex items-center gap-2 text-sm text-gray-600">
                            <Building2 class="h-4 w-4" />
                            Pagador: {{ payroll.affiliate_profile.payer.name }} ({{ payroll.affiliate_profile.payer.document_number }})
                        </div>
                    </div>

                    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                        <h2 class="text-lg font-semibold text-gray-900 flex items-center gap-2">
                            <Calculator class="h-5 w-5 text-brand-600" />
                            Desglose de aportes
                        </h2>
                        <p
                            v-if="meta?.parameters_used?.ibc_source === 'contracts'"
                            class="mt-3 rounded-lg border border-blue-200 bg-blue-50 px-3 py-2 text-xs text-blue-900"
                        >
                            Esta liquidación usó IBC consolidado de
                            {{ meta?.parameters_used?.contracts?.contracts_count || 0 }} contrato(s) activos.
                        </p>
                        <div
                            v-if="hasProfileVsContractsMismatch || hasMultipleContractPayers || contractsWithoutPayerCount > 0"
                            class="mt-3 rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 text-xs text-amber-900"
                        >
                            <p v-if="hasProfileVsContractsMismatch">
                                El pagador del perfil no coincide con los pagadores de contratos usados en la liquidación.
                            </p>
                            <p v-if="hasMultipleContractPayers" class="mt-1">
                                Esta planilla consolidó contratos de múltiples pagadores.
                            </p>
                            <p v-if="contractsWithoutPayerCount > 0" class="mt-1">
                                Se detectaron {{ contractsWithoutPayerCount }} contrato(s) sin pagador al momento del cálculo.
                            </p>
                        </div>
                        <dl class="mt-4 space-y-2">
                            <div class="flex justify-between text-sm">
                                <dt class="text-gray-500">Salud</dt>
                                <dd class="font-medium text-gray-900">{{ formatMoney(payroll.health_amount) }}</dd>
                            </div>
                            <div class="flex justify-between text-sm">
                                <dt class="text-gray-500">Pensión</dt>
                                <dd class="font-medium text-gray-900">{{ formatMoney(payroll.pension_amount) }}</dd>
                            </div>
                            <div class="flex justify-between text-sm">
                                <dt class="text-gray-500">ARL</dt>
                                <dd class="font-medium text-gray-900">{{ formatMoney(payroll.arl_amount) }}</dd>
                            </div>
                            <div class="flex justify-between text-sm">
                                <dt class="text-gray-500">CCF</dt>
                                <dd class="font-medium text-gray-900">{{ formatMoney(payroll.ccf_amount) }}</dd>
                            </div>
                            <div v-if="payroll.parafiscal_amount" class="flex justify-between text-sm">
                                <dt class="text-gray-500">Parafiscales</dt>
                                <dd class="font-medium text-gray-900">{{ formatMoney(payroll.parafiscal_amount) }}</dd>
                            </div>
                            <div v-if="payroll.fsp_amount" class="flex justify-between text-sm">
                                <dt class="text-gray-500">FSP</dt>
                                <dd class="font-medium text-gray-900">{{ formatMoney(payroll.fsp_amount) }}</dd>
                            </div>
                            <div class="flex justify-between text-sm pt-2 border-t border-gray-200">
                                <dt class="font-medium text-gray-700">Total</dt>
                                <dd class="font-semibold text-gray-900">{{ formatMoney(payroll.total_amount) }}</dd>
                            </div>
                        </dl>
                    </div>
                </div>

                <div class="space-y-6">
                    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                        <h3 class="text-sm font-medium text-gray-500 uppercase tracking-wide">Período y vencimiento</h3>
                        <p class="mt-2 text-gray-900">{{ monthLabel(payroll.month) }} {{ payroll.year }}</p>
                        <p class="text-sm text-gray-600">Vencimiento PILA: <strong>{{ payroll.due_date ?? '—' }}</strong></p>
                        <p v-if="payroll.sent_at" class="mt-2 text-xs text-gray-500">Enviada: {{ new Date(payroll.sent_at).toLocaleString('es-CO') }}</p>
                        <p v-if="payroll.paid_at" class="mt-1 text-xs text-green-600">Pagada: {{ new Date(payroll.paid_at).toLocaleString('es-CO') }}</p>
                    </div>

                    <div v-if="payroll.trackings?.length" class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                        <h3 class="text-sm font-medium text-gray-500 uppercase tracking-wide flex items-center gap-1">
                            <History class="h-4 w-4" />
                            Historial
                        </h3>
                        <ul class="mt-3 space-y-2">
                            <li v-for="t in payroll.trackings" :key="t.id" class="text-xs text-gray-600 flex gap-2">
                                <span class="text-gray-400">{{ t.old_status }} → {{ t.new_status }}</span>
                                <span class="text-gray-400">{{ t.created_at ? new Date(t.created_at).toLocaleString('es-CO') : '' }}</span>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
