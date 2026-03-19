<script setup>
import { ref } from 'vue';
import { useForm, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import DatePicker from '@/Components/DatePicker.vue';
import SearchSelect from '@/Components/SearchSelect.vue';
import {
    ChevronLeft,
    ArrowRight,
    Building2,
    User,
    ShieldCheck,
    Briefcase,
    Loader2,
    Check,
    X,
} from 'lucide-vue-next';

const props = defineProps({
    documentTypes: Array,
    epsOptions: Array,
    afpOptions: Array,
    arpOptions: Array,
    ccfOptions: Array,
    riskClassOptions: Array,
    cotizanteTypeOptions: Array,
    pilaOperatorOptions: Array,
    paymentStatusOptions: Array,
    paymentPeriodicityOptions: Array,
    billingTypeOptions: Array,
});

const step = ref(1);

const steps = [
    { n: 1, label: 'Afiliado' },
    { n: 2, label: 'Empleador' },
    { n: 3, label: 'Entidades' },
    { n: 4, label: 'PILA' },
];

const form = useForm({
    // Afiliado (cotizante)
    document_type: 'cc',
    document_number: '',
    document_issue_date: '',
    first_name: '',
    second_name: '',
    last_name: '',
    second_last_name: '',
    phone: '',
    phone_2: '',
    whatsapp: '',
    email: '',
    address: '',
    neighborhood: '',
    city: '',
    department: '',
    birth_date: '',
    gender: '',
    status: 'ACTIVO',
    notes: '',
    patient_type: 'cotizante',
    holder_id: null,
    relationship_type: null,

    // Empleador
    employer_nit: '',
    employer_name: '',
    employer_address: '',
    employer_city: '',
    employer_department: '',
    employer_phone: '',
    employer_email: '',
    employer_payment_business_day: '',
    employer_is_self_employed: false,
    employer_is_active: true,

    // Entidades (SS unificadas)
    eps_id: '',
    afp_id: '',
    arp_id: '',
    ccf_id: '',
    risk_class_id: '',

    // PILA
    cotizante_type_id: '',
    pila_operator: '',
    last_novelty_type: '',
    last_novelty_date: '',
    ibc: '',
    pays_parafiscales: false,
    self_employed: false,
    payment_periodicity: '',
    billing_type: '',
    last_document_number: '',
    last_payment_period: '',
    payment_status: '',
});

const goNext = () => {
    if (step.value < 4) step.value++;
};

const goBack = () => {
    if (step.value > 1) step.value--;
};

const submit = () => form.post('/affiliates');

const isSubmitting = form.processing;
</script>

<template>
    <AppLayout>
        <div class="max-w-4xl mx-auto space-y-6">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <Link href="/affiliates" class="inline-flex items-center gap-2 text-sm text-gray-500 hover:text-brand-600 transition-colors">
                        <ChevronLeft class="h-4 w-4" />
                        Volver a afiliados
                    </Link>
                    <h1 class="text-3xl font-bold text-gray-900 mt-3">Registro unificado</h1>
                    <p class="text-gray-500 mt-1">Afiliado + Afiliación PILA en una sola operación</p>
                </div>

                <div class="flex flex-col items-end">
                    <div class="flex items-center gap-2">
                        <span v-for="s in steps" :key="s.n"
                            class="inline-flex items-center justify-center w-8 h-8 rounded-full border text-sm font-semibold"
                            :class="s.n === step ? 'bg-brand-50 border-brand-300 text-brand-700' : 'bg-white border-gray-200 text-gray-400'">
                            {{ s.n }}
                        </span>
                    </div>
                    <div class="text-xs text-gray-500 mt-2">
                        Paso {{ step }} de 4
                    </div>
                </div>
            </div>

            <form @submit.prevent="submit" class="space-y-6">
                <!-- STEP 1 -->
                <div v-if="step === 1" class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                    <h2 class="flex items-center gap-2 text-lg font-semibold text-gray-900 mb-4">
                        <User class="h-5 w-5 text-brand-600" />
                        Afiliado (cotizante)
                    </h2>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Tipo de documento</label>
                            <select v-model="form.document_type" class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500">
                                <option v-for="d in documentTypes" :key="d.value" :value="d.value">
                                    {{ d.label }}
                                </option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Número de documento</label>
                            <input v-model="form.document_number" type="text" class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500" />
                            <p v-if="form.errors.document_number" class="mt-1 text-sm text-red-600">{{ form.errors.document_number }}</p>
                        </div>

                        <div>
                            <DatePicker v-model="form.document_issue_date" label="Fecha de expedición" />
                        </div>

                        <div>
                            <DatePicker v-model="form.birth_date" label="Fecha de nacimiento" />
                        </div>
                    </div>

                    <div class="mt-4 grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Primer nombre *</label>
                            <input v-model="form.first_name" type="text" class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500" />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Segundo nombre</label>
                            <input v-model="form.second_name" type="text" class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500" />
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Primer apellido *</label>
                            <input v-model="form.last_name" type="text" class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500" />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Segundo apellido</label>
                            <input v-model="form.second_last_name" type="text" class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500" />
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Teléfono 1</label>
                            <input v-model="form.phone" type="tel" class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500" />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Teléfono 2</label>
                            <input v-model="form.phone_2" type="tel" class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500" />
                        </div>

                        <div class="sm:col-span-2">
                            <label class="block text-sm font-medium text-gray-700 mb-2">WhatsApp / Celular</label>
                            <input v-model="form.whatsapp" type="tel" class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500" />
                        </div>

                        <div class="sm:col-span-2">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Correo electrónico</label>
                            <input v-model="form.email" type="email" class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500" />
                        </div>
                    </div>

                    <div class="mt-4 grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Dirección</label>
                            <input v-model="form.address" type="text" class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500" />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Barrio</label>
                            <input v-model="form.neighborhood" type="text" class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500" />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Ciudad</label>
                            <input v-model="form.city" type="text" class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500" />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Departamento</label>
                            <input v-model="form.department" type="text" class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500" />
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Género</label>
                            <select v-model="form.gender" class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500">
                                <option value="">Seleccione...</option>
                                <option value="M">Masculino</option>
                                <option value="F">Femenino</option>
                                <option value="Otro">Otro</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Estado</label>
                            <select v-model="form.status" class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500">
                                <option value="ACTIVO">ACTIVO</option>
                                <option value="INACTIVO">INACTIVO</option>
                                <option value="SUSPENDIDO">SUSPENDIDO</option>
                            </select>
                        </div>
                    </div>

                    <div class="mt-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Notas</label>
                        <textarea v-model="form.notes" rows="3" class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500"></textarea>
                    </div>
                </div>

                <!-- STEP 2 -->
                <div v-else-if="step === 2" class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                    <h2 class="flex items-center gap-2 text-lg font-semibold text-gray-900 mb-4">
                        <Building2 class="h-5 w-5 text-brand-600" />
                        Empleador (NIT)
                    </h2>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">NIT (con o sin dígito verificador)</label>
                            <input v-model="form.employer_nit" type="text" class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500" placeholder="901776975-4" />
                            <p v-if="form.errors.employer_nit" class="mt-1 text-sm text-red-600">{{ form.errors.employer_nit }}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Nombre del empleador</label>
                            <input v-model="form.employer_name" type="text" class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500" />
                            <p v-if="form.errors.employer_name" class="mt-1 text-sm text-red-600">{{ form.errors.employer_name }}</p>
                        </div>
                    </div>

                    <div class="mt-4 grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Dirección</label>
                            <input v-model="form.employer_address" type="text" class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500" />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Ciudad</label>
                            <input v-model="form.employer_city" type="text" class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500" />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Departamento</label>
                            <input v-model="form.employer_department" type="text" class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500" />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Teléfono</label>
                            <input v-model="form.employer_phone" type="tel" class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500" />
                        </div>
                        <div class="sm:col-span-2">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Email</label>
                            <input v-model="form.employer_email" type="email" class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500" />
                        </div>
                    </div>

                    <div class="mt-4 grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Día hábil de pago (2..16)</label>
                            <input v-model="form.employer_payment_business_day" type="number" min="2" max="16" class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500" />
                        </div>

                        <div class="flex items-end gap-3">
                            <label class="inline-flex items-center gap-2 text-sm text-gray-700">
                                <input v-model="form.employer_is_self_employed" type="checkbox" class="rounded border-gray-300 text-brand-600 focus:ring-brand-500" />
                                Empleador independiente
                            </label>
                        </div>
                    </div>
                </div>

                <!-- STEP 3 -->
                <div v-else-if="step === 3" class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                    <h2 class="flex items-center gap-2 text-lg font-semibold text-gray-900 mb-4">
                        <ShieldCheck class="h-5 w-5 text-brand-600" />
                        Entidades (EPS, AFP, ARL, CCF)
                    </h2>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">EPS</label>
                            <SearchSelect v-model="form.eps_id" :options="epsOptions" placeholder="Seleccionar EPS..." />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">AFP</label>
                            <SearchSelect v-model="form.afp_id" :options="afpOptions" placeholder="Seleccionar AFP..." />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">ARL</label>
                            <SearchSelect v-model="form.arp_id" :options="arpOptions" placeholder="Seleccionar ARL..." />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Clase de riesgo</label>
                            <SearchSelect v-model="form.risk_class_id" :options="riskClassOptions" placeholder="Seleccionar..." />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">CCF</label>
                            <SearchSelect v-model="form.ccf_id" :options="ccfOptions" placeholder="Seleccionar CCF..." />
                        </div>
                    </div>
                </div>

                <!-- STEP 4 -->
                <div v-else class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                    <h2 class="flex items-center gap-2 text-lg font-semibold text-gray-900 mb-4">
                        <Briefcase class="h-5 w-5 text-brand-600" />
                        Datos PILA
                    </h2>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Tipo cotizante</label>
                            <SearchSelect v-model="form.cotizante_type_id" :options="cotizanteTypeOptions" placeholder="Seleccionar..." />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Operador PILA</label>
                            <SearchSelect
                                v-model="form.pila_operator"
                                :options="pilaOperatorOptions"
                                placeholder="Seleccionar operador..."
                                valueKey="value"
                                labelKey="label"
                            />
                        </div>
                    </div>

                    <div class="mt-4 grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Última novedad (tipo)</label>
                            <input v-model="form.last_novelty_type" type="text" maxlength="10" class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500" placeholder="ING / RET / ..." />
                        </div>
                        <div>
                            <DatePicker v-model="form.last_novelty_date" label="Última novedad (fecha)" />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">IBC</label>
                            <input v-model="form.ibc" type="number" min="0" step="0.01" class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500" />
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

                    <div class="mt-4 grid grid-cols-1 sm:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Periodicidad</label>
                            <select v-model="form.payment_periodicity" class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500">
                                <option value="">Seleccionar...</option>
                                <option v-for="o in paymentPeriodicityOptions" :key="o.value" :value="o.value">
                                    {{ o.label }}
                                </option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Tipo de comprobante</label>
                            <select v-model="form.billing_type" class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500">
                                <option value="">Seleccionar...</option>
                                <option v-for="o in billingTypeOptions" :key="o.value" :value="o.value">
                                    {{ o.label }}
                                </option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Estado pago</label>
                            <select v-model="form.payment_status" class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500">
                                <option value="">Seleccionar...</option>
                                <option v-for="o in paymentStatusOptions" :key="o.value" :value="o.value">
                                    {{ o.label }}
                                </option>
                            </select>
                        </div>
                    </div>

                    <div class="mt-4 grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Último documento</label>
                            <input v-model="form.last_document_number" type="text" maxlength="30" class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500" />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Último período pagado (AAAAMM)</label>
                            <input v-model="form.last_payment_period" type="text" maxlength="6" class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500" placeholder="202603" />
                        </div>
                    </div>
                </div>

                <!-- Botones -->
                <div class="flex flex-col sm:flex-row justify-between gap-4 pt-2">
                    <div class="flex gap-3">
                        <Link href="/affiliates" class="inline-flex items-center justify-center gap-2 px-6 py-3 font-medium rounded-xl bg-white text-gray-700 border border-gray-300 hover:bg-gray-50 transition-colors">
                            <X class="h-5 w-5" />
                            Cancelar
                        </Link>
                    </div>

                    <div class="flex gap-3">
                        <button v-if="step > 1" type="button" @click="goBack"
                            class="inline-flex items-center justify-center gap-2 px-6 py-3 font-medium rounded-xl bg-white text-gray-700 border border-gray-300 hover:bg-gray-50 transition-colors">
                            <ChevronLeft class="h-4 w-4" />
                            Atrás
                        </button>

                        <button v-if="step < 4" type="button" @click="goNext"
                            class="inline-flex items-center justify-center gap-2 px-8 py-3 font-semibold rounded-xl bg-brand-500 text-white hover:bg-brand-600 transition-colors disabled:opacity-50 shadow-lg shadow-brand-500/30">
                            Siguiente
                            <ArrowRight class="h-4 w-4" />
                        </button>

                        <button v-else type="submit" :disabled="isSubmitting"
                            class="inline-flex items-center justify-center gap-2 px-10 py-3 font-semibold rounded-xl bg-brand-500 text-white hover:bg-brand-600 transition-colors disabled:opacity-50 shadow-lg shadow-brand-500/30">
                            <Loader2 v-if="isSubmitting" class="h-5 w-5 animate-spin" />
                            <Check v-else class="h-5 w-5" />
                            {{ isSubmitting ? 'Guardando...' : 'Guardar afiliado' }}
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </AppLayout>
</template>

