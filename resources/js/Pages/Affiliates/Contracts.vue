<script setup>
import { computed, ref } from 'vue';
import { Link, useForm, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import DatePicker from '@/Components/DatePicker.vue';
import { ChevronLeft, FileText, Plus, Pencil, Trash2, Building2 } from 'lucide-vue-next';

const props = defineProps({
    affiliate: { type: Object, required: true },
    contracts: { type: Array, default: () => [] },
    payers: { type: Array, default: () => [] },
    ibcSuggestion: { type: Object, default: null },
    currentPeriod: { type: Object, default: () => ({ year: new Date().getFullYear(), month: new Date().getMonth() + 1 }) },
});

const page = usePage();
const flash = computed(() => page.props.flash || {});

const editingId = ref(null);

const form = useForm({
    payer_id: '',
    contract_reference: '',
    contract_type: 'SERVICE_PROVISION',
    start_date: '',
    end_date: '',
    monthly_income: '',
    risk_class: '',
    is_active: true,
    notes: '',
});

const isEditing = computed(() => editingId.value !== null);

const contractTypeOptions = [
    { value: 'SERVICE_PROVISION', label: 'Prestación de servicios' },
    { value: 'CIVIL_WORK', label: 'Obra o labor' },
    { value: 'CONSULTING', label: 'Consultoría' },
    { value: 'OTHER', label: 'Otro' },
];

const formatCurrency = (value) =>
    new Intl.NumberFormat('es-CO', { style: 'currency', currency: 'COP', maximumFractionDigits: 0 }).format(value || 0);

function resetForm() {
    editingId.value = null;
    form.reset();
    form.clearErrors();
    form.contract_type = 'SERVICE_PROVISION';
    form.is_active = true;
}

function editContract(contract) {
    editingId.value = contract.id;
    form.payer_id = contract.payer_id || '';
    form.contract_reference = contract.contract_reference || '';
    form.contract_type = contract.contract_type || 'SERVICE_PROVISION';
    form.start_date = contract.start_date || '';
    form.end_date = contract.end_date || '';
    form.monthly_income = contract.monthly_income || '';
    form.risk_class = contract.risk_class || '';
    form.is_active = !!contract.is_active;
    form.notes = contract.notes || '';
}

function submit() {
    const url = isEditing.value
        ? `/affiliates/${props.affiliate.id}/contracts/${editingId.value}`
        : `/affiliates/${props.affiliate.id}/contracts`;

    const options = {
        preserveScroll: true,
        onSuccess: () => resetForm(),
    };

    if (isEditing.value) {
        form.put(url, options);
    } else {
        form.post(url, options);
    }
}

function deleteContract(contract) {
    if (!confirm('¿Eliminar este contrato? Esta acción no se puede deshacer.')) return;
    useForm({}).delete(`/affiliates/${props.affiliate.id}/contracts/${contract.id}`, { preserveScroll: true });
}
</script>

<template>
    <AppLayout title="Contratos independientes">
        <div class="max-w-6xl mx-auto space-y-6">
            <div>
                <Link
                    :href="`/affiliates/${affiliate.id}`"
                    class="inline-flex items-center gap-2 text-sm text-gray-500 hover:text-brand-700"
                >
                    <ChevronLeft class="h-4 w-4" />
                    Volver al afiliado
                </Link>
                <h1 class="mt-2 text-2xl font-bold text-gray-900">Contratos independientes</h1>
                <p class="text-sm text-gray-500 mt-1">
                    {{ affiliate.full_name }} · {{ affiliate.document_number }}
                </p>
            </div>

            <div
                v-if="ibcSuggestion"
                class="rounded-xl border border-brand-200 bg-brand-50 px-4 py-3 text-sm text-brand-900"
            >
                Para {{ currentPeriod.month }}/{{ currentPeriod.year }} hay
                <strong>{{ ibcSuggestion.contracts_count }}</strong> contrato(s) activo(s),
                ingreso mensualizado <strong>{{ formatCurrency(ibcSuggestion.total_monthly_income) }}</strong> e IBC sugerido
                (<strong>{{ ibcSuggestion.ibc_percent }}%</strong>): <strong>{{ formatCurrency(ibcSuggestion.ibc) }}</strong>.
            </div>

            <div v-if="flash.success" class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
                {{ flash.success }}
            </div>
            <div v-if="flash.error" class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
                {{ flash.error }}
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-5 gap-6">
                <div class="lg:col-span-3 bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-200 bg-gray-50 flex items-center gap-2">
                        <FileText class="h-5 w-5 text-brand-600" />
                        <h2 class="font-semibold text-gray-900">Listado de contratos</h2>
                        <span
                            v-if="contracts.length"
                            class="px-2 py-0.5 text-xs rounded-full bg-brand-100 text-brand-700"
                        >
                            {{ contracts.length }}
                        </span>
                    </div>

                    <div class="divide-y divide-gray-100">
                        <div
                            v-for="contract in contracts"
                            :key="contract.id"
                            class="px-6 py-4 flex items-start justify-between gap-4"
                        >
                            <div class="min-w-0">
                                <div class="flex items-center gap-2 flex-wrap">
                                    <span class="font-medium text-gray-900">{{ contract.contract_reference || 'Sin referencia' }}</span>
                                    <span
                                        :class="[
                                            'text-xs px-2 py-0.5 rounded-full',
                                            contract.is_active ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-600',
                                        ]"
                                    >
                                        {{ contract.is_active ? 'Activo' : 'Inactivo' }}
                                    </span>
                                </div>
                                <p class="text-sm text-gray-600 mt-0.5">
                                    {{ contractTypeOptions.find((t) => t.value === contract.contract_type)?.label || contract.contract_type }}
                                </p>
                                <p class="text-sm text-gray-600">
                                    {{ contract.start_date }} <span v-if="contract.end_date">→ {{ contract.end_date }}</span>
                                    <span v-else>→ indefinido</span>
                                </p>
                                <p class="text-sm font-medium text-gray-900 mt-1">
                                    {{ formatCurrency(contract.monthly_income) }} / mes
                                </p>
                                <p v-if="contract.payer_name" class="text-xs text-gray-500 mt-1">
                                    <Building2 class="inline h-3.5 w-3.5 mr-1" />
                                    {{ contract.payer_name }}
                                </p>
                            </div>
                            <div class="flex items-center gap-2">
                                <button
                                    type="button"
                                    class="inline-flex items-center gap-1 px-2 py-1 text-sm text-brand-700 hover:bg-brand-50 rounded"
                                    @click="editContract(contract)"
                                >
                                    <Pencil class="h-4 w-4" />
                                    Editar
                                </button>
                                <button
                                    type="button"
                                    class="inline-flex items-center gap-1 px-2 py-1 text-sm text-red-700 hover:bg-red-50 rounded"
                                    @click="deleteContract(contract)"
                                >
                                    <Trash2 class="h-4 w-4" />
                                    Eliminar
                                </button>
                            </div>
                        </div>
                        <div v-if="!contracts.length" class="px-6 py-10 text-center text-sm text-gray-500">
                            Aun no hay contratos registrados para este afiliado.
                        </div>
                    </div>
                </div>

                <div class="lg:col-span-2 bg-white rounded-xl shadow-sm border border-gray-200">
                    <div class="px-6 py-4 border-b border-gray-200 bg-gradient-to-r from-brand-50 to-white">
                        <h2 class="font-semibold text-gray-900 flex items-center gap-2">
                            <Plus class="h-4 w-4 text-brand-600" />
                            {{ isEditing ? 'Editar contrato' : 'Nuevo contrato' }}
                        </h2>
                    </div>
                    <form class="p-6 space-y-4" @submit.prevent="submit">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Pagador</label>
                            <select
                                v-model="form.payer_id"
                                class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500"
                            >
                                <option value="">Sin pagador específico</option>
                                <option v-for="p in payers" :key="p.id" :value="p.id">{{ p.name }}</option>
                            </select>
                            <p v-if="form.errors.payer_id" class="mt-1 text-sm text-red-600">{{ form.errors.payer_id }}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Referencia del contrato</label>
                            <input
                                v-model="form.contract_reference"
                                type="text"
                                maxlength="100"
                                placeholder="Ej: CPS-2026-001"
                                class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500"
                            />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Tipo de contrato *</label>
                            <select
                                v-model="form.contract_type"
                                class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500"
                            >
                                <option v-for="opt in contractTypeOptions" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
                            </select>
                            <p v-if="form.errors.contract_type" class="mt-1 text-sm text-red-600">{{ form.errors.contract_type }}</p>
                        </div>

                        <DatePicker v-model="form.start_date" label="Fecha inicio" required />
                        <p v-if="form.errors.start_date" class="-mt-3 text-sm text-red-600">{{ form.errors.start_date }}</p>

                        <DatePicker v-model="form.end_date" label="Fecha fin (opcional)" />
                        <div v-if="form.end_date" class="-mt-3">
                            <button
                                type="button"
                                class="text-xs text-gray-600 hover:text-gray-800 underline underline-offset-2"
                                @click="form.end_date = ''"
                            >
                                Limpiar fecha fin
                            </button>
                        </div>
                        <p v-if="form.errors.end_date" class="-mt-3 text-sm text-red-600">{{ form.errors.end_date }}</p>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Ingreso mensualizado (COP) *</label>
                            <input
                                v-model="form.monthly_income"
                                type="number"
                                step="0.01"
                                min="0"
                                class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500"
                            />
                            <p v-if="form.errors.monthly_income" class="mt-1 text-sm text-red-600">{{ form.errors.monthly_income }}</p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Clase de riesgo ARL</label>
                            <select
                                v-model="form.risk_class"
                                class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500"
                            >
                                <option value="">Sin definir</option>
                                <option :value="1">Clase I</option>
                                <option :value="2">Clase II</option>
                                <option :value="3">Clase III</option>
                                <option :value="4">Clase IV</option>
                                <option :value="5">Clase V</option>
                            </select>
                        </div>

                        <div class="flex items-center">
                            <input
                                id="contract-active"
                                v-model="form.is_active"
                                type="checkbox"
                                class="rounded border-gray-300 text-brand-600 focus:ring-brand-500"
                            />
                            <label for="contract-active" class="ml-2 text-sm text-gray-700">Contrato activo</label>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Notas</label>
                            <textarea
                                v-model="form.notes"
                                rows="3"
                                maxlength="2000"
                                class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500"
                            />
                            <p v-if="form.errors.notes" class="mt-1 text-sm text-red-600">{{ form.errors.notes }}</p>
                        </div>

                        <div class="flex items-center justify-end gap-2">
                            <button
                                v-if="isEditing"
                                type="button"
                                class="px-3 py-2 rounded-lg border border-gray-300 text-gray-700 hover:bg-gray-50"
                                @click="resetForm"
                            >
                                Cancelar edición
                            </button>
                            <button
                                type="submit"
                                :disabled="form.processing"
                                class="px-4 py-2 rounded-lg bg-brand-600 text-white hover:bg-brand-700 disabled:opacity-50"
                            >
                                {{ form.processing ? 'Guardando...' : (isEditing ? 'Actualizar contrato' : 'Guardar contrato') }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

