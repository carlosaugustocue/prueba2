<script setup>
import { computed, ref } from 'vue';
import { useForm, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import SearchSelect from '@/Components/SearchSelect.vue';
import {
    AlertTriangle,
    ChevronLeft,
    CreditCard,
    KeyRound,
    ShieldCheck,
    User
} from 'lucide-vue-next';

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
    pilaOperatorOptions: Array,
    noveltyOptions: Array,
    paymentStatusOptions: Array,
    paymentPeriodicityOptions: Array,
    billingTypeOptions: Array,
    ibcMinAmount: { type: [Number, String], default: null },
    ibcMaxAmount: { type: [Number, String], default: null },
    ibcMinMultiplier: { type: Number, default: null },
    ibcMaxMultiplier: { type: Number, default: null },
});

const a = props.affiliation?.data || props.affiliation;

const formatMoney = (n) => {
    if (n === null || n === undefined || n === '') return '';
    const num = Number(n);
    if (Number.isNaN(num)) return '';
    return `$${new Intl.NumberFormat('es-CO', { maximumFractionDigits: 0 }).format(num)}`;
};

const parseIbc = (raw) => {
    const s = String(raw ?? '');
    // Permite dígitos y punto decimal. Quita separadores de miles/comillas.
    const cleaned = s.replace(/[^0-9.]/g, '');
    if (!cleaned) return null;
    const parts = cleaned.split('.');
    if (parts.length > 2) {
        return Number(parts[0] ?? 0);
    }
    const num = Number(cleaned);
    if (Number.isNaN(num)) return null;
    return num;
};

const formatIbcText = (value) => {
    const num = typeof value === 'string' ? parseIbc(value) : value;
    if (num === '' || num === null || num === undefined) return '';
    const n = Number(num);
    if (Number.isNaN(n)) return '';
    return new Intl.NumberFormat('es-CO', { maximumFractionDigits: 2 }).format(n);
};

const ibcText = ref(formatIbcText(a.ibc ?? ''));

const form = useForm({
    affiliate_id: a.affiliate_id || '',
    employer_id: a.employer_id || '',
    cotizante_type_id: a.cotizante_type_id || '',
    pila_operator: a.pila_operator || '',
    last_novelty_type: a.last_novelty_type || '',
    last_novelty_date: a.last_novelty_date || '',
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

const ibcNumeric = computed(() => {
    if (form.ibc === '' || form.ibc === null || form.ibc === undefined) return null;
    const n = Number(form.ibc);
    if (Number.isNaN(n)) return null;
    return n;
});

const ibcMin = computed(() => {
    const n = Number(props.ibcMinAmount);
    return Number.isNaN(n) ? null : n;
});

const ibcMax = computed(() => {
    const n = Number(props.ibcMaxAmount);
    return Number.isNaN(n) ? null : n;
});

const ibcOutOfRange = computed(() => {
    if (ibcNumeric.value === null) return false;
    if (ibcMin.value !== null && ibcNumeric.value < ibcMin.value) return true;
    if (ibcMax.value !== null && ibcNumeric.value > ibcMax.value) return true;
    return false;
});

const onIbcInput = (e) => {
    const nextText = e.target.value;
    const nextNum = parseIbc(nextText);
    ibcText.value = formatIbcText(nextNum);
    form.ibc = nextNum;
};

const isSelfEmployed = computed(() => Boolean(form.self_employed));

const toggleNovelty = (code) => {
    form.last_novelty_type = form.last_novelty_type === code ? '' : code;
};

const submit = () => form.put(`/pila/affiliations/${a.id}`);

const canSave = computed(() => {
    if (form.processing) return false;
    if (ibcOutOfRange.value) return false;
    return true;
});
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
                <p class="mt-1 text-sm text-gray-500">
                    {{ a.affiliate?.full_name || 'Afiliación' }}
                    <span v-if="a.affiliate?.document_number">
                        · {{ a.affiliate?.document_type_abbreviation }} {{ a.affiliate?.document_number }}
                    </span>
                </p>
            </div>

            <form @submit.prevent="submit" class="space-y-5">
                <!-- SECCIÓN 1 — Afiliado y empleador -->
                <section class="bg-white rounded-xl border border-gray-200 overflow-visible">
                    <div class="px-6 py-4 bg-[#E1F5EE] flex items-center justify-between gap-3">
                        <div class="flex items-center gap-3">
                            <div class="h-11 w-11 rounded-xl bg-[#E1F5EE] text-[#0F6E56] flex items-center justify-center">
                                <User class="h-5 w-5" />
                            </div>
                            <div class="text-[11px] font-semibold uppercase tracking-[0.05em] text-[#0F6E56]">
                                Afiliado y empleador
                            </div>
                        </div>
                        <span
                            v-if="form.is_current"
                            class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-green-50 text-green-700 border border-green-200"
                        >
                            Vigente
                        </span>
                    </div>

                    <div class="p-6 space-y-4">
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-medium text-gray-500 uppercase tracking-[0.05em] mb-2">Afiliado</label>
                                <SearchSelect
                                    v-model="form.affiliate_id"
                                    :options="affiliateOptions"
                                    placeholder="Seleccionar afiliado..."
                                />
                                <p v-if="form.errors.affiliate_id" class="mt-1 text-sm text-red-600">{{ form.errors.affiliate_id }}</p>
                            </div>

                            <div>
                                <label class="block text-xs font-medium text-gray-500 uppercase tracking-[0.05em] mb-2">Empleador</label>
                                <SearchSelect
                                    v-model="form.employer_id"
                                    :options="employerOptions"
                                    placeholder="Seleccionar empleador..."
                                />
                                <p v-if="form.errors.employer_id" class="mt-1 text-sm text-red-600">{{ form.errors.employer_id }}</p>

                                <div v-if="isSelfEmployed" class="mt-2 rounded-lg border border-amber-200 bg-amber-50 p-3 text-amber-800 text-sm flex items-start gap-2">
                                    <AlertTriangle class="h-4 w-4 mt-0.5" />
                                    <span>Independiente — el empleador es el mismo cotizante.</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- SECCIÓN 2 — Operación PILA -->
                <section class="bg-white rounded-xl border border-gray-200 overflow-visible">
                    <div class="px-6 py-4 bg-[#EEEDFE] flex items-center gap-3 justify-between">
                        <div class="flex items-center gap-3">
                            <div class="h-11 w-11 rounded-xl bg-[#EEEDFE] text-[#534AB7] flex items-center justify-center">
                                <KeyRound class="h-5 w-5" />
                            </div>
                            <div class="text-[11px] font-semibold uppercase tracking-[0.05em] text-[#534AB7]">
                                Operación PILA
                            </div>
                        </div>
                    </div>

                    <div class="p-6 space-y-4">
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-medium text-gray-500 uppercase tracking-[0.05em] mb-2">Tipo cotizante</label>
                                <SearchSelect
                                    v-model="form.cotizante_type_id"
                                    :options="cotizanteTypeOptions"
                                    placeholder="Seleccionar..."
                                />
                                <p v-if="form.errors.cotizante_type_id" class="mt-1 text-sm text-red-600">{{ form.errors.cotizante_type_id }}</p>
                            </div>

                            <div>
                                <label class="block text-xs font-medium text-gray-500 uppercase tracking-[0.05em] mb-2">Operador PILA</label>
                                <select v-model="form.pila_operator" class="w-full rounded-lg border-gray-300 shadow-sm text-sm focus:border-[#0F6E56] focus:ring-[#0F6E56]">
                                    <option value="">Seleccionar...</option>
                                    <option v-for="o in pilaOperatorOptions" :key="o.value" :value="o.value">{{ o.label }}</option>
                                </select>
                                <p v-if="form.errors.pila_operator" class="mt-1 text-sm text-red-600">{{ form.errors.pila_operator }}</p>
                            </div>
                        </div>

                        <div class="space-y-3">
                            <div>
                                <div class="text-xs font-medium text-gray-500 uppercase tracking-[0.05em] mb-2">Novedades</div>
                                <div class="grid grid-cols-4 gap-2">
                                    <button
                                        type="button"
                                        v-for="chip in noveltyOptions"
                                        :key="chip.value"
                                        @click="toggleNovelty(chip.value)"
                                        class="px-2 py-2 rounded-lg border text-left transition cursor-pointer"
                                        :class="form.last_novelty_type === chip.value
                                            ? 'bg-[#E1F5EE] text-[#085041] border-[#9FE1CB]'
                                            : 'bg-gray-100 text-gray-600 border-gray-200 hover:bg-[#E1F5EE]'
                                        "
                                    >
                                        <div class="text-[11px] font-semibold">{{ chip.value }}</div>
                                        <div class="text-[10px] text-gray-600" v-if="form.last_novelty_type === chip.value">
                                            {{ chip.title }}
                                        </div>
                                        <div class="text-[10px] text-gray-500" v-else>
                                            {{ chip.title }}
                                        </div>
                                    </button>
                                </div>
                                <p v-if="form.errors.last_novelty_type" class="mt-1 text-sm text-red-600">{{ form.errors.last_novelty_type }}</p>
                            </div>

                            <div>
                                <label class="block text-xs font-medium text-gray-500 uppercase tracking-[0.05em] mb-2">Fecha de la novedad</label>
                                <input type="date" v-model="form.last_novelty_date" class="w-full rounded-lg border-gray-300 shadow-sm text-sm focus:border-[#0F6E56] focus:ring-[#0F6E56]" />
                                <p v-if="form.errors.last_novelty_date" class="mt-1 text-sm text-red-600">{{ form.errors.last_novelty_date }}</p>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- SECCIÓN 3 — Salario y aportes -->
                <section class="bg-white rounded-xl border border-gray-200 overflow-visible">
                    <div class="px-6 py-4 bg-[#E6F1FB] flex items-center gap-3 justify-between">
                        <div class="flex items-center gap-3">
                            <div class="h-11 w-11 rounded-xl bg-[#E6F1FB] text-[#185FA5] flex items-center justify-center">
                                <CreditCard class="h-5 w-5" />
                            </div>
                            <div class="text-[11px] font-semibold uppercase tracking-[0.05em] text-[#185FA5]">
                                Salario y aportes
                            </div>
                        </div>
                    </div>

                    <div class="p-6 space-y-4">
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-medium text-gray-500 uppercase tracking-[0.05em] mb-2">IBC</label>
                                <div class="relative">
                                    <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-500 font-medium">$</span>
                                    <input
                                        :value="ibcText"
                                        @input="onIbcInput"
                                        type="text"
                                        inputmode="decimal"
                                        class="w-full rounded-lg border-gray-300 shadow-sm text-sm pl-8 pr-3 focus:border-[#0F6E56] focus:ring-[#0F6E56]"
                                        placeholder="Ingrese IBC"
                                    />
                                </div>
                                <p v-if="form.errors.ibc" class="mt-1 text-sm text-red-600">{{ form.errors.ibc }}</p>

                                <div
                                    v-if="ibcMin !== null && ibcMax !== null && ibcMinMultiplier !== null && ibcMaxMultiplier !== null"
                                    class="mt-2 text-xs text-gray-500"
                                >
                                    Mínimo {{ ibcMinMultiplier }} SMMLV ({{ formatMoney(ibcMin) }}) · Máximo {{ ibcMaxMultiplier }} SMMLV ({{ formatMoney(ibcMax) }})
                                </div>

                                <div v-else class="mt-2 text-xs text-gray-500">
                                    Rango IBC no parametrizado para el período actual.
                                </div>

                                <div v-if="ibcOutOfRange" class="mt-2 rounded-lg border border-red-200 bg-red-50 p-3 text-red-700 text-sm flex items-start gap-2">
                                    <AlertTriangle class="h-4 w-4 mt-0.5" />
                                    <span>El IBC debe estar entre {{ formatMoney(ibcMin) }} y {{ formatMoney(ibcMax) }}.</span>
                                </div>
                            </div>

                            <div class="space-y-3">
                                <div class="text-xs font-medium text-gray-500 uppercase tracking-[0.05em]">Opciones de cotización</div>

                                <button
                                    type="button"
                                    class="w-full text-left rounded-lg border border-gray-200 p-4 bg-gray-50 hover:bg-[#E1F5EE] transition cursor-pointer"
                                    :class="form.pays_parafiscales ? 'bg-[#E1F5EE] border-[#BFE8DB]' : ''"
                                    @click="form.pays_parafiscales = !form.pays_parafiscales"
                                >
                                    <div class="flex items-start justify-between gap-3">
                                        <div class="font-medium text-gray-900">Paga parafiscales</div>
                                        <div class="text-sm font-semibold" v-if="form.pays_parafiscales">✓</div>
                                    </div>
                                    <div class="text-sm text-gray-600 mt-1">SENA, ICBF y Caja de Compensación</div>
                                </button>

                                <button
                                    type="button"
                                    class="w-full text-left rounded-lg border border-gray-200 p-4 bg-gray-50 hover:bg-[#E1F5EE] transition cursor-pointer"
                                    :class="form.self_employed ? 'bg-[#E1F5EE] border-[#BFE8DB]' : ''"
                                    @click="form.self_employed = !form.self_employed"
                                >
                                    <div class="flex items-start justify-between gap-3">
                                        <div class="font-medium text-gray-900">Cotizante independiente</div>
                                        <div class="text-sm font-semibold" v-if="form.self_employed">✓</div>
                                    </div>
                                    <div class="text-sm text-gray-600 mt-1">Se paga a sí mismo la planilla</div>
                                </button>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- SECCIÓN 4 — Entidades afiliadas -->
                <section class="bg-white rounded-xl border border-gray-200 overflow-visible">
                    <div class="px-6 py-4 bg-[#FAEEDA] flex items-center gap-3 justify-between">
                        <div class="flex items-center gap-3">
                            <div class="h-11 w-11 rounded-xl bg-[#FAEEDA] text-[#854F0B] flex items-center justify-center">
                                <ShieldCheck class="h-5 w-5" />
                            </div>
                            <div class="text-[11px] font-semibold uppercase tracking-[0.05em] text-[#854F0B]">
                                Entidades afiliadas
                            </div>
                        </div>
                    </div>

                    <div class="p-6 space-y-4">
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-medium text-gray-500 uppercase tracking-[0.05em] mb-2 flex items-center gap-2">
                                    <span class="h-2.5 w-2.5 rounded-full bg-[#185FA5]" />
                                    EPS
                                </label>
                                <SearchSelect v-model="form.eps_id" :options="epsOptions" placeholder="Seleccionar EPS..." />
                                <p v-if="form.errors.eps_id" class="mt-1 text-sm text-red-600">{{ form.errors.eps_id }}</p>
                            </div>

                            <div>
                                <label class="block text-xs font-medium text-gray-500 uppercase tracking-[0.05em] mb-2 flex items-center gap-2">
                                    <span class="h-2.5 w-2.5 rounded-full bg-[#993C1D]" />
                                    AFP
                                </label>
                                <SearchSelect v-model="form.afp_id" :options="afpOptions" placeholder="Seleccionar AFP..." />
                                <p v-if="form.errors.afp_id" class="mt-1 text-sm text-red-600">{{ form.errors.afp_id }}</p>
                            </div>

                            <div class="sm:col-span-2">
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-xs font-medium text-gray-500 uppercase tracking-[0.05em] mb-2 flex items-center gap-2">
                                            <span class="h-2.5 w-2.5 rounded-full bg-[#854F0B]" />
                                            ARL
                                        </label>
                                        <SearchSelect v-model="form.arp_id" :options="arpOptions" placeholder="Seleccionar ARL..." />
                                        <p v-if="form.errors.arp_id" class="mt-1 text-sm text-red-600">{{ form.errors.arp_id }}</p>
                                    </div>

                                    <div>
                                        <label class="block text-xs font-medium text-gray-500 uppercase tracking-[0.05em] mb-2 flex items-center gap-2">
                                            <span class="h-2.5 w-2.5 rounded-full bg-[#854F0B]" />
                                            Clase de riesgo
                                        </label>
                                        <SearchSelect
                                            v-model="form.risk_class_id"
                                            :options="riskClassOptions"
                                            placeholder="Seleccionar clase..."
                                            :disabled="!form.arp_id"
                                        />
                                        <p v-if="form.errors.risk_class_id" class="mt-1 text-sm text-red-600">{{ form.errors.risk_class_id }}</p>
                                    </div>
                                </div>
                            </div>

                            <div>
                                <label class="block text-xs font-medium text-gray-500 uppercase tracking-[0.05em] mb-2 flex items-center gap-2">
                                    <span class="h-2.5 w-2.5 rounded-full bg-[#534AB7]" />
                                    CCF
                                </label>
                                <SearchSelect v-model="form.ccf_id" :options="ccfOptions" placeholder="Seleccionar CCF..." />
                                <p v-if="form.errors.ccf_id" class="mt-1 text-sm text-red-600">{{ form.errors.ccf_id }}</p>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- SECCIÓN 5 — Estado de pago y facturación -->
                <section class="bg-white rounded-xl border border-gray-200 overflow-visible">
                    <div class="px-6 py-4 bg-[#E1F5EE] flex items-center gap-3 justify-between">
                        <div class="flex items-center gap-3">
                            <div class="h-11 w-11 rounded-xl bg-[#E1F5EE] text-[#0F6E56] flex items-center justify-center">
                                <CreditCard class="h-5 w-5" />
                            </div>
                            <div class="text-[11px] font-semibold uppercase tracking-[0.05em] text-[#0F6E56]">
                                Estado de pago y facturación
                            </div>
                        </div>
                    </div>

                    <div class="p-6 space-y-4">
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                            <div>
                                <label class="block text-xs font-medium text-gray-500 uppercase tracking-[0.05em] mb-2">Estado de pago</label>
                                <select v-model="form.payment_status" class="w-full rounded-lg border-gray-300 shadow-sm text-sm focus:border-[#0F6E56] focus:ring-[#0F6E56]">
                                    <option value="">Seleccionar...</option>
                                    <option v-for="o in paymentStatusOptions" :key="o.value" :value="o.value">{{ o.label }}</option>
                                </select>
                                <p v-if="form.errors.payment_status" class="mt-1 text-sm text-red-600">{{ form.errors.payment_status }}</p>
                            </div>

                            <div>
                                <label class="block text-xs font-medium text-gray-500 uppercase tracking-[0.05em] mb-2">Periodicidad</label>
                                <select v-model="form.payment_periodicity" class="w-full rounded-lg border-gray-300 shadow-sm text-sm focus:border-[#0F6E56] focus:ring-[#0F6E56]">
                                    <option value="">Seleccionar...</option>
                                    <option v-for="o in paymentPeriodicityOptions" :key="o.value" :value="o.value">{{ o.label }}</option>
                                </select>
                                <p v-if="form.errors.payment_periodicity" class="mt-1 text-sm text-red-600">{{ form.errors.payment_periodicity }}</p>
                            </div>

                            <div>
                                <label class="block text-xs font-medium text-gray-500 uppercase tracking-[0.05em] mb-2">Tipo comprobante</label>
                                <select v-model="form.billing_type" class="w-full rounded-lg border-gray-300 shadow-sm text-sm focus:border-[#0F6E56] focus:ring-[#0F6E56]">
                                    <option value="">Seleccionar...</option>
                                    <option v-for="o in billingTypeOptions" :key="o.value" :value="o.value">{{ o.label }}</option>
                                </select>
                                <p v-if="form.errors.billing_type" class="mt-1 text-sm text-red-600">{{ form.errors.billing_type }}</p>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-medium text-gray-500 uppercase tracking-[0.05em] mb-2">Último documento</label>
                                <input
                                    v-model="form.last_document_number"
                                    type="text"
                                    placeholder="Ej: FVS-1754"
                                    class="w-full rounded-lg border-gray-300 shadow-sm text-sm focus:border-[#0F6E56] focus:ring-[#0F6E56]"
                                />
                                <p v-if="form.errors.last_document_number" class="mt-1 text-sm text-red-600">{{ form.errors.last_document_number }}</p>
                            </div>

                            <div>
                                <label class="block text-xs font-medium text-gray-500 uppercase tracking-[0.05em] mb-2">Último período pagado</label>
                                <input
                                    v-model="form.last_payment_period"
                                    type="text"
                                    maxlength="6"
                                    placeholder="202503"
                                    class="w-full rounded-lg border-gray-300 shadow-sm text-sm focus:border-[#0F6E56] focus:ring-[#0F6E56]"
                                />
                                <div class="mt-2 text-xs text-gray-500">
                                    Formato: año + mes · Ej: 202503 = Marzo 2025
                                </div>
                                <p v-if="form.errors.last_payment_period" class="mt-1 text-sm text-red-600">{{ form.errors.last_payment_period }}</p>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- FOOTER: Cancelar / Guardar -->
                <div class="flex justify-end gap-3 pt-2">
                    <Link
                        :href="`/pila/affiliations/${a.id}`"
                        class="inline-flex items-center justify-center px-4 py-2 rounded-lg border border-gray-200 text-gray-600 hover:bg-gray-50"
                    >
                        Cancelar
                    </Link>
                    <button
                        type="submit"
                        :disabled="!canSave"
                        class="inline-flex items-center justify-center px-4 py-2 rounded-lg bg-[#0F6E56] text-white hover:bg-[#0D5E4B] disabled:opacity-60"
                    >
                        💾 Guardar cambios
                    </button>
                </div>
            </form>
        </div>
    </AppLayout>
</template>

