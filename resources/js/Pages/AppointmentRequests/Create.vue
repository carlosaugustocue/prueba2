<script setup>
import { ref, watch, computed } from 'vue';
import { useForm, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import axios from 'axios';
import { alertDialog } from '@/Utils/swal';
import {
    ChevronLeft, User, Search, Loader2, X, Check,
    Stethoscope, Heart, FlaskConical, Scan, ClipboardList,
    AlertCircle, Flag, Zap, FileText, MessageSquare, FileCheck
} from 'lucide-vue-next';

const props = defineProps({
    types: Array,
    priorities: Array,
    epsList: Array,
    documentTypes: Array,
    patientTypes: Array,
});

const form = useForm({
    affiliate_id: '',
    type: 'general',
    priority: 'medium',
    specialty: '',
    requires_authorization: false,
    client_notes: '',
});

const isAffiliateActive = (affiliate) => {
    const s = (affiliate?.status || '').toString().toUpperCase();
    return s === '' || s === 'ACTIVO';
};

const affiliateStatusBadge = (status) => {
    const s = (status || '').toString().toUpperCase();
    if (s === 'ACTIVO') return { label: 'Activo', class: 'bg-green-100 text-green-800' };
    if (s === 'INACTIVO') return { label: 'Inactivo', class: 'bg-red-100 text-red-800' };
    if (s === 'SUSPENDIDO') return { label: 'Suspendido', class: 'bg-amber-100 text-amber-800' };
    return { label: status || 'Sin estado', class: 'bg-gray-100 text-gray-700' };
};

const missingFields = computed(() => {
    const list = [];
    if (!form.affiliate_id) list.push('Afiliado');
    else if (selectedAffiliate.value && !isAffiliateActive(selectedAffiliate.value)) list.push('El afiliado seleccionado está inactivo o suspendido; no puede solicitar cita.');
    if (!form.type) list.push('Tipo de cita');
    if (String(form.type || '') === 'specialist' && !String(form.specialty || '').trim()) list.push('Especialidad');
    return list;
});

const hasValidationErrors = computed(() => Object.keys(form.errors || {}).length > 0);
const validationErrorList = computed(() =>
    Object.entries(form.errors || {}).map(([key, msg]) => ({ field: key, message: msg }))
);

// Iconos para tipos
const typeIcons = {
    general: Stethoscope,
    specialist: Heart,
    laboratory: FlaskConical,
    imaging: Scan,
    procedure: ClipboardList,
};

// Config de prioridades
const priorityConfig = {
    urgent: { color: 'red', icon: Zap },
    high: { color: 'orange', icon: AlertCircle },
    medium: { color: 'yellow', icon: Flag },
    low: { color: 'green', icon: Check },
};

// Búsqueda de afiliados
const affiliateSearch = ref('');
const affiliateResults = ref([]);
const selectedAffiliate = ref(null);
const isSearching = ref(false);
const showNoResults = ref(false);

const searchAffiliates = async () => {
    if (affiliateSearch.value.length < 2) {
        affiliateResults.value = [];
        showNoResults.value = false;
        return;
    }
    
    isSearching.value = true;
    showNoResults.value = false;
    
    try {
        const response = await axios.get('/api/affiliates/search', {
            params: { term: affiliateSearch.value, serviconli_only: 1 }
        });
        affiliateResults.value = response.data.data || response.data || [];
        showNoResults.value = affiliateResults.value.length === 0;
    } catch (error) {
        console.error(error);
        affiliateResults.value = [];
    } finally {
        isSearching.value = false;
    }
};

const selectAffiliate = (affiliate) => {
    if (!isAffiliateActive(affiliate)) {
        alertDialog({
            title: 'Afiliado no disponible',
            text: 'Este afiliado está ' + (affiliate.status === 'SUSPENDIDO' ? 'suspendido' : 'inactivo') + '. No se pueden crear solicitudes de cita para afiliados inactivos o suspendidos.',
            icon: 'warning',
        });
        return;
    }
    selectedAffiliate.value = affiliate;
    form.affiliate_id = affiliate.id;
    affiliateSearch.value = '';
    affiliateResults.value = [];
    showNoResults.value = false;
};

const clearAffiliate = () => {
    selectedAffiliate.value = null;
    form.affiliate_id = '';
};

let searchTimeout;
watch(affiliateSearch, () => {
    if (!selectedAffiliate.value && affiliateSearch.value.length >= 2) {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(searchAffiliates, 400);
    } else if (affiliateSearch.value.length < 2) {
        affiliateResults.value = [];
        showNoResults.value = false;
    }
});

const submit = () => {
    if (missingFields.value.length > 0) {
        alertDialog({
            title: 'Faltan datos requeridos',
            html: '<p class="text-left mb-2">Para continuar, complete los siguientes campos:</p><ul class="text-left list-disc list-inside space-y-1">' +
                missingFields.value.map(f => `<li>${f}</li>`).join('') + '</ul>',
            icon: 'warning',
        });
        return;
    }
    form.post('/appointment-requests');
};
</script>

<template>
    <AppLayout>
        <div class="max-w-3xl mx-auto">
            <div class="mb-8">
                <Link href="/appointment-requests" class="inline-flex items-center gap-2 text-sm text-gray-500 hover:text-brand-600 transition-colors">
                    <ChevronLeft class="h-4 w-4" />
                    Volver a solicitudes
                </Link>
                <h1 class="text-3xl font-bold text-gray-900 mt-3">Nueva Solicitud de Cita</h1>
                <p class="text-gray-500 mt-1">Registre la solicitud del cliente</p>
            </div>

            <form @submit.prevent="submit" class="space-y-6">
                <!-- Resumen de errores (backend) -->
                <div v-if="hasValidationErrors" class="rounded-xl border-2 border-red-200 bg-red-50 p-4">
                    <p class="font-semibold text-red-800 mb-2">Revisa los siguientes campos:</p>
                    <ul class="list-disc list-inside space-y-1 text-sm text-red-700">
                        <li v-for="(e, i) in validationErrorList" :key="i">{{ e.message }}</li>
                    </ul>
                </div>

                <!-- Afiliado -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                    <h2 class="flex items-center gap-2 text-lg font-semibold text-gray-900 mb-4">
                        <User class="h-5 w-5 text-brand-600" />
                        Afiliado *
                    </h2>

                    <!-- Afiliado seleccionado -->
                    <div v-if="selectedAffiliate" :class="[
                        'rounded-xl p-4 border',
                        isAffiliateActive(selectedAffiliate)
                            ? 'bg-brand-50 border-brand-200'
                            : 'bg-red-50 border-red-200'
                    ]">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-4">
                                <div class="h-12 w-12 rounded-full flex items-center justify-center" :class="isAffiliateActive(selectedAffiliate) ? 'bg-brand-100' : 'bg-red-100'">
                                    <span :class="isAffiliateActive(selectedAffiliate) ? 'text-brand-700 font-bold' : 'text-red-700 font-bold'">{{ selectedAffiliate.first_name?.charAt(0) }}{{ selectedAffiliate.last_name?.charAt(0) }}</span>
                                </div>
                                <div>
                                    <div class="flex items-center gap-2 flex-wrap">
                                        <p class="font-semibold text-gray-900">{{ selectedAffiliate.full_name }}</p>
                                        <span :class="['inline-flex px-2 py-0.5 rounded text-xs font-medium', affiliateStatusBadge(selectedAffiliate.status).class]">
                                            {{ affiliateStatusBadge(selectedAffiliate.status).label }}
                                        </span>
                                    </div>
                                    <p class="text-sm text-gray-600">{{ selectedAffiliate.document_type_abbreviation }} {{ selectedAffiliate.document_number }}</p>
                                    <p class="text-sm text-gray-500">{{ selectedAffiliate.whatsapp_number || selectedAffiliate.phone || 'Sin teléfono' }}</p>
                                    <p v-if="!isAffiliateActive(selectedAffiliate)" class="text-sm text-red-700 font-medium mt-1">
                                        No se pueden crear solicitudes de cita para este afiliado.
                                    </p>
                                </div>
                            </div>
                            <button type="button" @click="clearAffiliate" class="flex items-center gap-2 px-3 py-2 text-sm text-red-600 hover:bg-red-50 rounded-lg transition-colors">
                                <X class="h-4 w-4" />
                                Cambiar
                            </button>
                        </div>
                    </div>

                    <!-- Búsqueda -->
                    <div v-else class="space-y-3">
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                <Loader2 v-if="isSearching" class="h-5 w-5 text-brand-500 animate-spin" />
                                <Search v-else class="h-5 w-5 text-gray-400" />
                            </div>
                            <input 
                                v-model="affiliateSearch" 
                                type="text" 
                                placeholder="Buscar por nombre o documento..." 
                                class="block w-full pl-12 pr-4 py-3 rounded-xl border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500"
                            />
                        </div>

                        <div v-if="affiliateResults.length > 0" class="bg-white border border-gray-200 rounded-xl shadow-lg max-h-64 overflow-auto">
                            <button 
                                v-for="affiliate in affiliateResults" 
                                :key="affiliate.id" 
                                type="button" 
                                @click="selectAffiliate(affiliate)"
                                :class="[
                                    'w-full px-4 py-3 text-left border-b last:border-0 flex items-center gap-3 transition-colors',
                                    isAffiliateActive(affiliate)
                                        ? 'hover:bg-brand-50'
                                        : 'opacity-75 cursor-not-allowed hover:bg-gray-50'
                                ]"
                            >
                                <div class="h-10 w-10 rounded-full flex items-center justify-center flex-shrink-0" :class="isAffiliateActive(affiliate) ? 'bg-gray-100' : 'bg-gray-200'">
                                    <span class="text-gray-600 font-medium">{{ affiliate.first_name?.charAt(0) }}{{ affiliate.last_name?.charAt(0) }}</span>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center gap-2 flex-wrap">
                                        <p class="font-medium text-gray-900 truncate">{{ affiliate.full_name }}</p>
                                        <span :class="['inline-flex px-2 py-0.5 rounded text-xs font-medium flex-shrink-0', affiliateStatusBadge(affiliate.status).class]">
                                            {{ affiliateStatusBadge(affiliate.status).label }}
                                        </span>
                                    </div>
                                    <p class="text-sm text-gray-500">{{ affiliate.document_type_abbreviation }} {{ affiliate.document_number }}</p>
                                    <p v-if="!isAffiliateActive(affiliate)" class="text-xs text-amber-600 mt-0.5">No puede solicitar cita</p>
                                </div>
                                <Check v-if="isAffiliateActive(affiliate)" class="h-5 w-5 text-brand-500 flex-shrink-0" />
                            </button>
                        </div>

                        <p v-if="showNoResults" class="text-sm text-amber-600 text-center py-2">
                            No se encontraron afiliados con Serviconli como pagador. Solo se pueden crear solicitudes para afiliados gestionados por Serviconli.
                        </p>
                    </div>
                    <p v-if="form.errors.affiliate_id" class="mt-2 text-sm text-red-600">{{ form.errors.affiliate_id }}</p>
                </div>

                <!-- Tipo de Cita -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                    <h2 class="flex items-center gap-2 text-lg font-semibold text-gray-900 mb-4">
                        <Stethoscope class="h-5 w-5 text-brand-600" />
                        Tipo de Cita Solicitada *
                    </h2>
                    <div class="grid grid-cols-2 sm:grid-cols-3 gap-3">
                        <button 
                            v-for="t in types" 
                            :key="t.value"
                            type="button"
                            @click="form.type = t.value"
                            :class="[
                                'relative p-4 rounded-xl border-2 transition-all text-left',
                                form.type === t.value
                                    ? 'border-brand-500 bg-brand-50 ring-2 ring-brand-500/20'
                                    : 'border-gray-200 hover:border-brand-300 hover:bg-gray-50',
                                form.errors.type ? 'border-red-300' : ''
                            ]"
                        >
                            <component :is="typeIcons[t.value] || Stethoscope" :class="['h-6 w-6 mb-2', form.type === t.value ? 'text-brand-600' : 'text-gray-400']" />
                            <p :class="['font-medium text-sm', form.type === t.value ? 'text-brand-900' : 'text-gray-700']">
                                {{ t.label }}
                            </p>
                            <div v-if="form.type === t.value" class="absolute top-2 right-2">
                                <Check class="h-4 w-4 text-brand-600" />
                            </div>
                        </button>
                    </div>
                    <p v-if="form.errors.type" class="mt-2 text-sm text-red-600">{{ form.errors.type }}</p>
                </div>

                <!-- Especialidad -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                    <h2 class="flex items-center gap-2 text-lg font-semibold text-gray-900 mb-4">
                        <FileText class="h-5 w-5 text-brand-600" />
                        Especialidad
                        <span v-if="form.type === 'specialist'" class="text-red-600">*</span>
                    </h2>
                    <p v-if="form.type === 'specialist'" class="text-sm text-gray-500 mb-3">
                        Indique la especialidad médica solicitada (ej: Cardiología, Pediatría, Dermatología).
                    </p>
                    <input 
                        v-model="form.specialty" 
                        type="text" 
                        placeholder="Ej: Cardiología, Pediatría, Dermatología..."
                        :class="['block w-full rounded-xl border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500 py-3 px-4', form.errors.specialty ? 'border-red-300' : '']"
                    />
                    <p v-if="form.errors.specialty" class="mt-2 text-sm text-red-600">{{ form.errors.specialty }}</p>
                </div>

                <!-- Prioridad -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                    <h2 class="flex items-center gap-2 text-lg font-semibold text-gray-900 mb-4">
                        <Flag class="h-5 w-5 text-brand-600" />
                        Prioridad *
                    </h2>
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-2">
                        <button 
                            v-for="p in priorities" 
                            :key="p.value"
                            type="button"
                            @click="form.priority = p.value"
                            :class="[
                                'flex items-center gap-2 p-3 rounded-lg border-2 transition-all',
                                form.priority === p.value
                                    ? `border-${priorityConfig[p.value]?.color || 'gray'}-500 bg-${priorityConfig[p.value]?.color || 'gray'}-50`
                                    : 'border-gray-200 hover:border-gray-300'
                            ]"
                        >
                            <component :is="priorityConfig[p.value]?.icon || Flag" :class="[
                                'h-4 w-4',
                                p.value === 'urgent' ? 'text-red-600' : '',
                                p.value === 'high' ? 'text-orange-600' : '',
                                p.value === 'medium' ? 'text-yellow-600' : '',
                                p.value === 'low' ? 'text-green-600' : ''
                            ]" />
                            <span class="text-sm font-medium">{{ p.label }}</span>
                        </button>
                    </div>
                </div>

                <!-- Requiere autorización EPS -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                    <div class="flex items-start gap-4">
                        <div class="flex items-center h-6">
                            <input
                                id="requires_authorization"
                                v-model="form.requires_authorization"
                                type="checkbox"
                                class="h-4 w-4 rounded border-gray-300 text-brand-600 focus:ring-brand-500"
                            />
                        </div>
                        <div class="flex-1">
                            <label for="requires_authorization" class="flex items-center gap-2 text-sm font-semibold text-gray-900 cursor-pointer">
                                <FileCheck class="h-5 w-5 text-brand-600" />
                                Requiere autorización EPS
                            </label>
                            <p class="mt-1 text-sm text-gray-500">
                                Marque si esta solicitud necesita trámite de autorización ante la EPS antes de poder agendar la cita. Luego podrá crear la autorización desde la ficha de la solicitud.
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Notas del Cliente -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                    <label class="flex items-center gap-2 text-sm font-semibold text-gray-900 mb-3">
                        <MessageSquare class="h-4 w-4 text-brand-600" />
                        Notas del Cliente
                    </label>
                    <p class="text-sm text-gray-500 mb-3">
                        Información adicional proporcionada por el cliente sobre su solicitud
                    </p>
                    <textarea 
                        v-model="form.client_notes" 
                        rows="3"
                        placeholder="Ej: El cliente prefiere citas en la mañana, necesita doctor que hable inglés..."
                        class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500"
                    ></textarea>
                </div>

                <!-- Botones -->
                <div class="flex flex-col sm:flex-row justify-end gap-4 pt-4">
                    <Link href="/appointment-requests" class="inline-flex items-center justify-center gap-2 px-6 py-3 font-medium rounded-xl bg-white text-gray-700 border border-gray-300 hover:bg-gray-50 transition-colors">
                        <X class="h-5 w-5" />
                        Cancelar
                    </Link>
                    <button type="submit" :disabled="form.processing" class="inline-flex items-center justify-center gap-2 px-8 py-3 font-semibold rounded-xl bg-brand-500 text-white hover:bg-brand-600 transition-colors disabled:opacity-50 shadow-lg shadow-brand-500/30">
                        <Loader2 v-if="form.processing" class="h-5 w-5 animate-spin" />
                        <Check v-else class="h-5 w-5" />
                        {{ form.processing ? 'Guardando...' : 'Registrar Solicitud' }}
                    </button>
                </div>
            </form>
        </div>
    </AppLayout>
</template>
