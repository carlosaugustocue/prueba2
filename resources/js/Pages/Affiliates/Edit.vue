<script setup>
import { ref, watch, computed } from 'vue';
import { useForm, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import DatePicker from '@/Components/DatePicker.vue';
import axios from 'axios';
import {
    ChevronLeft, User, Users, Search, Loader2, X, Check,
    Heart, Phone, MessageSquare, Building2, FileText, Link2, Info
} from 'lucide-vue-next';

const props = defineProps({
    affiliate: Object,
    epsList: Array,
    clientTypes: Array,
    contributorTypes: Array,
    afpList: Array,
    arpList: Array,
    ccfList: Array,
    paymentOperatorList: Array,
    accountingRegistries: Array,
    payerList: Array,
    documentTypes: Array,
    patientTypes: Array,
    relationshipTypes: Array,
});

const affiliateData = computed(() => props.affiliate?.data || props.affiliate || {});
const currentHolder = computed(() => affiliateData.value.holder || null);
const hasBeneficiaries = computed(() => affiliateData.value.beneficiaries_count > 0);

// IBC: mostrar en pantalla con formato miles/millones (ej. 1.500.000), enviar número al backend
const formatIbcDisplay = (n) => {
    if (n === '' || n === null || n === undefined) return '';
    const num = Number(n);
    if (Number.isNaN(num)) return '';
    return num.toLocaleString('es-CO', { maximumFractionDigits: 0 });
};
const parseIbcInput = (s) => {
    const digits = String(s).replace(/\D/g, '');
    if (digits === '') return '';
    return parseInt(digits, 10);
};
const ibcDisplay = ref(formatIbcDisplay(affiliateData.value.ibc ?? ''));
const onIbcInput = (e) => {
    const raw = e.target.value;
    const parsed = parseIbcInput(raw);
    form.ibc = parsed === '' ? '' : parsed;
    ibcDisplay.value = formatIbcDisplay(form.ibc);
};

const form = useForm({
    document_type: affiliateData.value.document_type || '',
    document_number: affiliateData.value.document_number || '',
    document_issue_date: affiliateData.value.document_issue_date || '',
    first_name: affiliateData.value.first_name || '',
    second_name: affiliateData.value.second_name || '',
    last_name: affiliateData.value.last_name || '',
    second_last_name: affiliateData.value.second_last_name || '',
    phone: affiliateData.value.phone || '',
    phone_2: affiliateData.value.phone_2 || '',
    whatsapp: affiliateData.value.whatsapp || '',
    email: affiliateData.value.email || '',
    address: affiliateData.value.address || '',
    neighborhood: affiliateData.value.neighborhood || '',
    eps_id: affiliateData.value.eps?.id || affiliateData.value.eps_id || '',
    city: affiliateData.value.city || '',
    department: affiliateData.value.department || '',
    client_type_id: affiliateData.value.client_type_id || '',
    contributor_type_id: affiliateData.value.contributor_type_id || '',
    afp_id: affiliateData.value.afp_id || affiliateData.value.afp?.id || '',
    arp_id: affiliateData.value.arp_id || affiliateData.value.arp?.id || '',
    arp_risk_class: affiliateData.value.arp_risk_class || '',
    ccf_id: affiliateData.value.ccf_id || affiliateData.value.ccf?.id || '',
    payment_operator_id: affiliateData.value.payment_operator_id || affiliateData.value.payment_operator?.id || '',
    accounting_registry_id: affiliateData.value.accounting_registry_id || '',
    payer_id: affiliateData.value.payer_id || affiliateData.value.payer?.id || '',
    ibc: affiliateData.value.ibc ?? '',
    payment_day: affiliateData.value.payment_day ?? '',
    payment_periodicity: affiliateData.value.payment_periodicity || '',
    has_parafiscales: affiliateData.value.has_parafiscales ?? false,
    observations: affiliateData.value.observations || '',
    patient_type: affiliateData.value.patient_type || '',
    holder_id: affiliateData.value.holder_id || '',
    relationship_type: affiliateData.value.relationship_type || '',
    birth_date: affiliateData.value.birth_date || '',
    gender: affiliateData.value.gender || '',
    status: affiliateData.value.status || 'ACTIVO',
    notes: affiliateData.value.notes || '',
});

// Búsqueda de cotizantes
const holderSearch = ref('');
const holderResults = ref([]);
const selectedHolder = ref(currentHolder.value);
const isSearchingHolder = ref(false);

const searchHolders = async () => {
    if (holderSearch.value.length < 2) {
        holderResults.value = [];
        return;
    }
    
    isSearchingHolder.value = true;
    try {
        const response = await axios.get('/api/affiliates/search-holders', {
            params: { term: holderSearch.value }
        });
        // Excluir el afiliado actual de los resultados
        holderResults.value = (response.data.data || []).filter(h => h.id !== affiliateData.value.id);
    } catch (error) {
        console.error(error);
        holderResults.value = [];
    } finally {
        isSearchingHolder.value = false;
    }
};

const selectHolder = (holder) => {
    selectedHolder.value = holder;
    form.holder_id = holder.id;
    holderSearch.value = '';
    holderResults.value = [];
};

const clearHolder = () => {
    selectedHolder.value = null;
    form.holder_id = '';
    holderSearch.value = '';
};

let searchTimeout;
watch(holderSearch, () => {
    clearTimeout(searchTimeout);
    if (holderSearch.value.length >= 2) {
        searchTimeout = setTimeout(searchHolders, 400);
    } else {
        holderResults.value = [];
    }
});

// Limpiar holder_id y relationship_type si cambia a cotizante
watch(() => form.patient_type, (newType) => {
    if (newType === 'cotizante') {
        selectedHolder.value = null;
        form.holder_id = '';
        form.relationship_type = '';
    }
});

// Mantener display IBC sincronizado si el form.ibc cambia por fuera (ej. validación)
watch(() => form.ibc, (v) => {
    const current = parseIbcInput(ibcDisplay.value);
    const next = v === '' || v === null ? '' : Number(v);
    if (current !== next) ibcDisplay.value = formatIbcDisplay(v);
}, { immediate: true });

// Obtener la descripción del tipo de parentesco seleccionado
const selectedRelationshipDescription = ref('');
watch(() => form.relationship_type, (newType) => {
    const rel = props.relationshipTypes?.find(r => r.value === newType);
    selectedRelationshipDescription.value = rel?.description || '';
}, { immediate: true });

const submit = () => form.put(`/affiliates/${affiliateData.value.id}`);
</script>

<template>
    <AppLayout>
        <div class="max-w-3xl mx-auto">
            <div class="mb-8">
                <Link :href="`/affiliates/${affiliateData.id}`" class="inline-flex items-center gap-2 text-sm text-gray-500 hover:text-brand-600 transition-colors">
                    <ChevronLeft class="h-4 w-4" />
                    Volver al afiliado
                </Link>
                <h1 class="text-3xl font-bold text-gray-900 mt-3">Editar Afiliado</h1>
                <p class="text-gray-500 mt-1">{{ affiliateData.full_name }}</p>
            </div>

            <form @submit.prevent="submit" class="space-y-6">
                <!-- Tipo de Afiliado -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                    <h2 class="flex items-center gap-2 text-lg font-semibold text-gray-900 mb-4">
                        <Users class="h-5 w-5 text-brand-600" />
                        Tipo de Afiliado
                    </h2>
                    
                    <!-- Advertencia si tiene beneficiarios y quiere cambiar a beneficiario -->
                    <div v-if="hasBeneficiaries && affiliateData.patient_type === 'cotizante'" class="mb-4 p-4 bg-amber-50 border border-amber-200 rounded-xl">
                        <p class="text-amber-800 text-sm">
                            <strong>Nota:</strong> Este afiliado tiene beneficiarios asociados. No puede cambiar a beneficiario mientras tenga beneficiarios.
                        </p>
                    </div>

                    <div v-if="affiliateData.patient_type === 'beneficiario'" class="mb-4 p-4 bg-blue-50 border border-blue-200 rounded-xl">
                        <p class="text-blue-800 text-sm">
                            <strong>Cotizante principal:</strong> Si esta persona pasa a ser afiliado principal, seleccione «Cotizante». Se desvinculará del titular actual y podrá tener su propia EPS y beneficiarios.
                        </p>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <button
                            type="button"
                            @click="form.patient_type = 'cotizante'"
                            :class="[
                                'relative p-4 rounded-xl border-2 transition-all duration-200 text-left',
                                form.patient_type === 'cotizante'
                                    ? 'border-brand-500 bg-brand-50 ring-2 ring-brand-500/20'
                                    : 'border-gray-200 hover:border-brand-300 hover:bg-gray-50'
                            ]"
                        >
                            <User :class="['h-8 w-8 mb-2', form.patient_type === 'cotizante' ? 'text-brand-600' : 'text-gray-400']" />
                            <p :class="['font-semibold', form.patient_type === 'cotizante' ? 'text-brand-900' : 'text-gray-700']">
                                Cotizante
                            </p>
                            <p class="text-sm text-gray-500 mt-1">Afiliado principal</p>
                            <div v-if="form.patient_type === 'cotizante'" class="absolute top-3 right-3">
                                <Check class="h-5 w-5 text-brand-600" />
                            </div>
                        </button>
                        
                        <button
                            type="button"
                            @click="form.patient_type = 'beneficiario'"
                            :disabled="hasBeneficiaries"
                            :class="[
                                'relative p-4 rounded-xl border-2 transition-all duration-200 text-left',
                                form.patient_type === 'beneficiario'
                                    ? 'border-brand-500 bg-brand-50 ring-2 ring-brand-500/20'
                                    : 'border-gray-200 hover:border-brand-300 hover:bg-gray-50',
                                hasBeneficiaries ? 'opacity-50 cursor-not-allowed' : ''
                            ]"
                        >
                            <Heart :class="['h-8 w-8 mb-2', form.patient_type === 'beneficiario' ? 'text-brand-600' : 'text-gray-400']" />
                            <p :class="['font-semibold', form.patient_type === 'beneficiario' ? 'text-brand-900' : 'text-gray-700']">
                                Beneficiario
                            </p>
                            <p class="text-sm text-gray-500 mt-1">Familiar del cotizante</p>
                            <div v-if="form.patient_type === 'beneficiario'" class="absolute top-3 right-3">
                                <Check class="h-5 w-5 text-brand-600" />
                            </div>
                        </button>
                    </div>
                </div>

                <!-- Selección de Cotizante y Parentesco (solo si es beneficiario) -->
                <div v-if="form.patient_type === 'beneficiario'" class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                    <h2 class="flex items-center gap-2 text-lg font-semibold text-gray-900 mb-4">
                        <Link2 class="h-5 w-5 text-brand-600" />
                        Relación con Cotizante
                    </h2>

                    <!-- Cotizante Titular -->
                    <div class="mb-6">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Cotizante Titular *</label>

                        <!-- Cotizante seleccionado -->
                        <div v-if="selectedHolder" class="bg-brand-50 border border-brand-200 rounded-xl p-4">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-4">
                                    <div class="h-12 w-12 rounded-full bg-brand-100 flex items-center justify-center">
                                        <User class="h-6 w-6 text-brand-600" />
                                    </div>
                                    <div>
                                        <p class="font-semibold text-gray-900">{{ selectedHolder.full_name }}</p>
                                        <p class="text-sm text-gray-600">{{ selectedHolder.document_type_abbreviation }} {{ selectedHolder.document_number }}</p>
                                    </div>
                                </div>
                                <button type="button" @click="clearHolder" class="flex items-center gap-2 px-3 py-2 text-sm text-red-600 hover:bg-red-50 rounded-lg transition-colors">
                                    <X class="h-4 w-4" />
                                    Cambiar
                                </button>
                            </div>
                        </div>

                        <!-- Búsqueda de cotizante -->
                        <div v-else class="space-y-3">
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                    <Loader2 v-if="isSearchingHolder" class="h-5 w-5 text-brand-500 animate-spin" />
                                    <Search v-else class="h-5 w-5 text-gray-400" />
                                </div>
                                <input
                                    v-model="holderSearch"
                                    type="text"
                                    placeholder="Buscar cotizante por nombre o documento..."
                                    class="block w-full pl-12 pr-4 py-3 rounded-xl border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500"
                                />
                            </div>

                            <!-- Resultados -->
                            <div v-if="holderResults.length > 0" class="bg-white border border-gray-200 rounded-xl shadow-lg max-h-64 overflow-auto">
                                <button
                                    v-for="holder in holderResults"
                                    :key="holder.id"
                                    type="button"
                                    @click="selectHolder(holder)"
                                    class="w-full px-4 py-3 text-left hover:bg-brand-50 border-b last:border-0 flex items-center gap-3 transition-colors"
                                >
                                    <div class="h-10 w-10 rounded-full bg-gray-100 flex items-center justify-center flex-shrink-0">
                                        <User class="h-5 w-5 text-gray-500" />
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <p class="font-medium text-gray-900 truncate">{{ holder.full_name }}</p>
                                        <p class="text-sm text-gray-500">{{ holder.document_type_abbreviation }} {{ holder.document_number }}</p>
                                    </div>
                                    <Check class="h-5 w-5 text-brand-500" />
                                </button>
                            </div>
                        </div>
                        <p v-if="form.errors.holder_id" class="mt-2 text-sm text-red-600">{{ form.errors.holder_id }}</p>
                    </div>

                    <!-- Tipo de Parentesco -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Tipo de Parentesco *</label>
                        <select 
                            v-model="form.relationship_type" 
                            :class="['block w-full rounded-xl border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500 py-3', form.errors.relationship_type ? 'border-red-300' : '']"
                        >
                            <option value="">Seleccione el parentesco...</option>
                            <option v-for="rt in relationshipTypes" :key="rt.value" :value="rt.value">
                                {{ rt.label }}
                            </option>
                        </select>
                        <p v-if="form.errors.relationship_type" class="mt-2 text-sm text-red-600">{{ form.errors.relationship_type }}</p>
                        
                        <!-- Descripción del tipo de parentesco -->
                        <div v-if="selectedRelationshipDescription" class="mt-3 p-3 bg-blue-50 border border-blue-200 rounded-lg flex items-start gap-2">
                            <Info class="h-5 w-5 text-blue-500 flex-shrink-0 mt-0.5" />
                            <p class="text-sm text-blue-700">{{ selectedRelationshipDescription }}</p>
                        </div>
                    </div>
                </div>

                <!-- Información Personal -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-200 bg-gradient-to-r from-gray-50 to-white">
                        <h2 class="flex items-center gap-2 text-lg font-semibold text-gray-900">
                            <FileText class="h-5 w-5 text-brand-600" />
                            Información Personal
                        </h2>
                    </div>
                    <div class="px-6 py-4 grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Tipo de documento</label>
                            <select v-model="form.document_type" class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500">
                                <option v-for="dt in documentTypes" :key="dt.value" :value="dt.value">{{ dt.label }}</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Número de documento</label>
                            <input v-model="form.document_number" type="text" :class="['block w-full rounded-lg border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500', form.errors.document_number ? 'border-red-300' : '']" />
                            <p v-if="form.errors.document_number" class="mt-1 text-sm text-red-600">{{ form.errors.document_number }}</p>
                        </div>
                        <div>
                            <DatePicker v-model="form.document_issue_date" label="Fecha de expedición del documento" />
                            <p v-if="form.errors.document_issue_date" class="mt-1 text-sm text-red-600">{{ form.errors.document_issue_date }}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Primer nombre</label>
                            <input v-model="form.first_name" type="text" :class="['block w-full rounded-lg border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500', form.errors.first_name ? 'border-red-300' : '']" />
                            <p v-if="form.errors.first_name" class="mt-1 text-sm text-red-600">{{ form.errors.first_name }}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Segundo nombre</label>
                            <input v-model="form.second_name" type="text" class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500" />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Primer apellido</label>
                            <input v-model="form.last_name" type="text" :class="['block w-full rounded-lg border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500', form.errors.last_name ? 'border-red-300' : '']" />
                            <p v-if="form.errors.last_name" class="mt-1 text-sm text-red-600">{{ form.errors.last_name }}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Segundo apellido</label>
                            <input v-model="form.second_last_name" type="text" class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500" />
                        </div>
                        <div>
                            <DatePicker v-model="form.birth_date" label="Fecha de nacimiento" />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Sexo</label>
                            <select v-model="form.gender" class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500">
                                <option value="">Seleccione...</option>
                                <option value="M">Masculino</option>
                                <option value="F">Femenino</option>
                                <option value="Otro">Otro</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Correo electrónico</label>
                            <input v-model="form.email" type="email" class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500" />
                        </div>
                    </div>
                </div>

                <!-- Contacto y EPS -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-200 bg-gradient-to-r from-gray-50 to-white">
                        <h2 class="flex items-center gap-2 text-lg font-semibold text-gray-900">
                            <Phone class="h-5 w-5 text-brand-600" />
                            Contacto y EPS
                        </h2>
                    </div>
                    <div class="px-6 py-4 grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Teléfono 1</label>
                            <input v-model="form.phone" type="tel" class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500" />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Teléfono 2</label>
                            <input v-model="form.phone_2" type="tel" class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500" />
                        </div>
                        <div>
                            <label class="flex items-center gap-2 text-sm font-medium text-gray-700 mb-2">
                                <MessageSquare class="h-4 w-4 text-green-500" />
                                WhatsApp / Celular
                            </label>
                            <input v-model="form.whatsapp" type="tel" class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500" />
                        </div>
                        <div>
                            <label class="flex items-center gap-2 text-sm font-medium text-gray-700 mb-2">
                                <Building2 class="h-4 w-4 text-gray-400" />
                                EPS
                            </label>
                            <select v-model="form.eps_id" class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500">
                                <option value="">Seleccione...</option>
                                <option v-for="eps in epsList" :key="eps.id" :value="eps.id">{{ eps.name }}</option>
                            </select>
                        </div>
                        <div class="sm:col-span-2">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Dirección</label>
                            <input v-model="form.address" type="text" class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500" placeholder="Calle, número, barrio..." />
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
                            <label class="block text-sm font-medium text-gray-700 mb-2">Estado del afiliado</label>
                            <select v-model="form.status" class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500">
                                <option value="ACTIVO">ACTIVO</option>
                                <option value="INACTIVO">INACTIVO</option>
                                <option value="SUSPENDIDO">SUSPENDIDO</option>
                                <option value="">Otro / Sin especificar</option>
                            </select>
                        </div>
                        <div class="sm:col-span-2">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Notas</label>
                            <textarea v-model="form.notes" rows="2" class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500"></textarea>
                        </div>
                    </div>
                </div>

                <!-- Seguridad social (AFP / ARP) - solo para cotizantes -->
                <div v-if="form.patient_type === 'cotizante'" class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-200 bg-gradient-to-r from-gray-50 to-white">
                        <h2 class="flex items-center gap-2 text-lg font-semibold text-gray-900">
                            <Building2 class="h-5 w-5 text-brand-600" />
                            Seguridad social (cotizante)
                        </h2>
                        <p class="text-sm text-gray-500 mt-1">Opcional. AFP y ARP aplican principalmente a cotizantes.</p>
                    </div>
                    <div class="px-6 py-4 grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Tipo de cliente</label>
                            <select v-model="form.client_type_id" class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500">
                                <option value="">Seleccione...</option>
                                <option v-for="item in (clientTypes || [])" :key="item.id" :value="item.id">{{ item.name }}</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Tipo de cotizante</label>
                            <select v-model="form.contributor_type_id" class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500">
                                <option value="">Seleccione...</option>
                                <option v-for="item in (contributorTypes || [])" :key="item.id" :value="item.id">{{ item.code }} – {{ item.name }}</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Registro contable</label>
                            <select v-model="form.accounting_registry_id" class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500">
                                <option value="">Ninguno</option>
                                <option v-for="item in (accountingRegistries || [])" :key="item.id" :value="item.id">{{ item.name }}</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">AFP</label>
                            <select v-model="form.afp_id" class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500">
                                <option value="">Ninguna</option>
                                <option v-for="item in (afpList || [])" :key="item.id" :value="item.id">{{ item.name }}</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">ARP</label>
                            <select v-model="form.arp_id" class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500">
                                <option value="">Ninguna</option>
                                <option v-for="item in (arpList || [])" :key="item.id" :value="item.id">{{ item.name }}</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Clase de riesgo ARP</label>
                            <input v-model="form.arp_risk_class" type="text" class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500" placeholder="Ej: 1, 2, 3..." />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">CCF</label>
                            <select v-model="form.ccf_id" class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500">
                                <option value="">Ninguna</option>
                                <option v-for="item in (ccfList || [])" :key="item.id" :value="item.id">{{ item.name }}</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Operador de pago</label>
                            <select v-model="form.payment_operator_id" class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500">
                                <option value="">Ninguno</option>
                                <option v-for="item in (paymentOperatorList || [])" :key="item.id" :value="item.id">{{ item.name }}</option>
                            </select>
                        </div>
                        <div class="sm:col-span-2">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Pagador</label>
                            <select v-model="form.payer_id" class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500">
                                <option value="">Ninguno</option>
                                <option v-for="p in (payerList || [])" :key="p.id" :value="p.id">{{ p.name }} ({{ p.document_type_abbreviation }} {{ p.document_number }})</option>
                            </select>
                            <p class="mt-1 text-xs text-gray-500">Quién paga los aportes (empresa o persona)</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">IBC (Ingreso base cotización)</label>
                            <input
                                :value="ibcDisplay"
                                type="text"
                                inputmode="numeric"
                                autocomplete="off"
                                class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500"
                                placeholder="Ej: 1.500.000"
                                @input="onIbcInput"
                            />
                            <p v-if="form.errors.ibc" class="mt-1 text-sm text-red-600">{{ form.errors.ibc }}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Día de pago (2-16)</label>
                            <input v-model="form.payment_day" type="number" min="2" max="16" class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500" placeholder="Según NIT/documento" />
                            <p class="mt-1 text-xs text-gray-500">Día hábil del mes; se puede calcular automático si se deja vacío</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Periodicidad de pago</label>
                            <select v-model="form.payment_periodicity" class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500">
                                <option value="">—</option>
                                <option value="CURRENT">Al día (current)</option>
                                <option value="OVERDUE">En mora (overdue)</option>
                            </select>
                        </div>
                        <div class="flex items-center">
                            <input id="has_parafiscales_edit" v-model="form.has_parafiscales" type="checkbox" class="rounded border-gray-300 text-brand-600 focus:ring-brand-500" />
                            <label for="has_parafiscales_edit" class="ml-2 text-sm text-gray-700">Tiene parafiscales</label>
                        </div>
                        <div class="sm:col-span-2">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Observaciones (perfil SS)</label>
                            <textarea v-model="form.observations" rows="2" class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500" placeholder="Observaciones del perfil de seguridad social..."></textarea>
                            <p v-if="form.errors.observations" class="mt-1 text-sm text-red-600">{{ form.errors.observations }}</p>
                        </div>
                    </div>
                </div>

                <!-- Botones -->
                <div class="flex flex-col sm:flex-row justify-end gap-4 pt-4">
                    <Link :href="`/affiliates/${affiliateData.id}`" class="inline-flex items-center justify-center gap-2 px-6 py-3 font-medium rounded-xl bg-white text-gray-700 border border-gray-300 hover:bg-gray-50 transition-colors">
                        <X class="h-5 w-5" />
                        Cancelar
                    </Link>
                    <button type="submit" :disabled="form.processing" class="inline-flex items-center justify-center gap-2 px-8 py-3 font-semibold rounded-xl bg-brand-500 text-white hover:bg-brand-600 transition-colors disabled:opacity-50 shadow-lg shadow-brand-500/30">
                        <Loader2 v-if="form.processing" class="h-5 w-5 animate-spin" />
                        <Check v-else class="h-5 w-5" />
                        {{ form.processing ? 'Guardando...' : 'Guardar Cambios' }}
                    </button>
                </div>
            </form>
        </div>
    </AppLayout>
</template>
