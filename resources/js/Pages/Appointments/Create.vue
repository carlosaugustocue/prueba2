<script setup>
import { ref, computed, watch } from 'vue';
import { useForm, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import axios from 'axios';
import { alertDialog } from '@/Utils/swal';
import { 
    Search, UserPlus, X, Loader2, AlertTriangle, ChevronRight,
    Stethoscope, Heart, Brain, Bone, Eye as EyeIcon, Baby, FlaskConical, Scan,
    Calendar, Clock, User, MapPin, Building2, FileText, ClipboardList, Lock, Phone,
    MessageSquare, Check, ChevronLeft, ChevronRight as ChevronRightIcon,
    AlertCircle, Flag, Zap, ArrowRight
} from 'lucide-vue-next';

const props = defineProps({
    types: Array,
    priorities: Array,
    epsList: Array,
    documentTypes: Array,
    patientTypes: Array,
    fromRequest: Object, // Datos precargados de una solicitud
    preselectedAffiliate: Object, // Datos precargados desde perfil del afiliado
});

// Form principal de cita (si viene de solicitud con autorización, se precarga el número)
const form = useForm({
    affiliate_id: props.fromRequest?.affiliate?.id || props.preselectedAffiliate?.data?.id || props.preselectedAffiliate?.id || '',
    type: props.fromRequest?.type || 'general',
    priority: props.fromRequest?.priority || 'medium',
    specialty: props.fromRequest?.specialty || '',
    appointment_date: '',
    appointment_time: '',
    doctor_name: '',
    location_name: '',
    location_address: '',
    location_phone: '',
    authorization_number: props.fromRequest?.authorization?.authorization_number || '',
    specifications: '',
    internal_notes: props.fromRequest?.client_notes ? `Notas del cliente: ${props.fromRequest.client_notes}` : '',
    send_confirmation: true,
    appointment_request_id: props.fromRequest?.id || null,
});

// Si viene de una solicitud o perfil, precargar el afiliado
const selectedAffiliate = ref(props.fromRequest?.affiliate || (props.preselectedAffiliate?.data || props.preselectedAffiliate) || null);

// Iconos para tipos de cita
const typeIcons = {
    general: Stethoscope,
    specialist: Heart,
    laboratory: FlaskConical,
    imaging: Scan,
    procedure: ClipboardList,
};

// Colores para prioridades
const priorityConfig = {
    urgent: { color: 'red', label: 'Urgente', icon: Zap },
    high: { color: 'orange', label: 'Alta', icon: AlertCircle },
    medium: { color: 'yellow', label: 'Media', icon: Flag },
    low: { color: 'green', label: 'Baja', icon: Check },
};

// Horarios comunes para selección rápida
const commonTimes = [
    '07:00', '07:30', '08:00', '08:30', '09:00', '09:30', '10:00', '10:30',
    '11:00', '11:30', '12:00', '14:00', '14:30', '15:00', '15:30', '16:00',
    '16:30', '17:00', '17:30', '18:00'
];

// RF-AUT-15: vigencia de la autorización (la cita no puede ser después de esta fecha)
const authorizationValidUntil = computed(() => {
    const until = props.fromRequest?.authorization?.valid_until;
    if (!until) return null;
    return until; // Y-m-d
});

const currentMonth = ref(new Date());
const calendarDays = computed(() => {
    const year = currentMonth.value.getFullYear();
    const month = currentMonth.value.getMonth();
    const firstDay = new Date(year, month, 1);
    const lastDay = new Date(year, month + 1, 0);
    const days = [];
    const today = new Date();
    const todayDate = new Date(today.getFullYear(), today.getMonth(), today.getDate());
    const validUntil = authorizationValidUntil.value;

    // Días del mes anterior para completar la primera semana
    const startDay = firstDay.getDay();
    for (let i = startDay - 1; i >= 0; i--) {
        const d = new Date(year, month, -i);
        days.push({ date: d, isCurrentMonth: false, isToday: false, isPast: false, isAfterValidUntil: false });
    }

    // Días del mes actual
    for (let i = 1; i <= lastDay.getDate(); i++) {
        const d = new Date(year, month, i);
        const isToday = d.toDateString() === today.toDateString();
        const isPast = d < todayDate;
        const dStr = `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`;
        const isAfterValidUntil = validUntil ? dStr > validUntil : false;
        days.push({ date: d, isCurrentMonth: true, isToday, isPast, isAfterValidUntil });
    }

    return days;
});

const monthNames = ['Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'];
const dayNames = ['Dom', 'Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb'];

const prevMonth = () => {
    currentMonth.value = new Date(currentMonth.value.getFullYear(), currentMonth.value.getMonth() - 1, 1);
};

const nextMonth = () => {
    currentMonth.value = new Date(currentMonth.value.getFullYear(), currentMonth.value.getMonth() + 1, 1);
};

const selectDate = (day) => {
    if (day.isPast || !day.isCurrentMonth || day.isAfterValidUntil) return;
    const d = day.date;
    form.appointment_date = `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`;
};

const isSelectedDate = (day) => {
    if (!form.appointment_date || !day.isCurrentMonth) return false;
    const d = day.date;
    const selected = `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`;
    return form.appointment_date === selected;
};

const selectTime = (time) => {
    form.appointment_time = time;
};

const formattedSelectedDate = computed(() => {
    if (!form.appointment_date) return null;
    const [year, month, day] = form.appointment_date.split('-');
    const date = new Date(year, month - 1, day);
    return `${dayNames[date.getDay()]} ${day} de ${monthNames[date.getMonth()]}`;
});

// Búsqueda de afiliados
const affiliateSearch = ref('');
const affiliateResults = ref([]);
const isSearching = ref(false);
const showNoResults = ref(false);

// Modal para crear nuevo afiliado
const showCreateAffiliateModal = ref(false);
const affiliateForm = useForm({
    document_type: 'cc',
    document_number: '',
    first_name: '',
    last_name: '',
    phone: '',
    whatsapp: '',
    email: '',
    eps_id: '',
    patient_type: 'cotizante',
});

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
            params: { term: affiliateSearch.value } 
        });
        affiliateResults.value = response.data.data || response.data || [];
        showNoResults.value = affiliateResults.value.length === 0;
    } catch (error) { 
        console.error(error);
        affiliateResults.value = [];
        showNoResults.value = true;
    } finally {
        isSearching.value = false;
    }
};

/** Calcula la edad en años a partir de una fecha Y-m-d (o null). */
function getAgeFromBirthDate(birthDateStr) {
    if (!birthDateStr) return null;
    const birth = new Date(birthDateStr);
    if (Number.isNaN(birth.getTime())) return null;
    const today = new Date();
    let age = today.getFullYear() - birth.getFullYear();
    const m = today.getMonth() - birth.getMonth();
    if (m < 0 || (m === 0 && today.getDate() < birth.getDate())) age--;
    return age >= 0 ? age : null;
}

/** Devuelve " • XX años" si hay fecha de nacimiento, o "" si no. */
function formatAgeSuffix(birthDateStr) {
    const age = getAgeFromBirthDate(birthDateStr);
    return age != null ? ` • ${age} años` : '';
}

/** Devuelve "Edad: XX años" para mostrar en front, o null si no hay fecha. */
function formatAgeLabel(birthDateStr) {
    const age = getAgeFromBirthDate(birthDateStr);
    return age != null ? `Edad: ${age} años` : null;
}

const selectAffiliate = (affiliate) => {
    selectedAffiliate.value = affiliate;
    form.affiliate_id = affiliate.id;
    affiliateSearch.value = '';
    affiliateResults.value = [];
    showNoResults.value = false;
};

const clearAffiliate = () => {
    selectedAffiliate.value = null;
    form.affiliate_id = '';
    affiliateSearch.value = '';
    affiliateResults.value = [];
    showNoResults.value = false;
};

const openCreateAffiliateModal = () => {
    const searchTerm = affiliateSearch.value.trim();
    
    if (/^\d+$/.test(searchTerm)) {
        affiliateForm.document_number = searchTerm;
    } else {
        const parts = searchTerm.split(' ');
        if (parts.length >= 2) {
            affiliateForm.first_name = parts[0];
            affiliateForm.last_name = parts.slice(1).join(' ');
        } else {
            affiliateForm.first_name = searchTerm;
        }
    }
    
    showCreateAffiliateModal.value = true;
    affiliateResults.value = [];
    showNoResults.value = false;
};

const closeCreateAffiliateModal = () => {
    showCreateAffiliateModal.value = false;
    affiliateForm.reset();
    affiliateForm.clearErrors();
};

const createAffiliate = async () => {
    try {
        const response = await axios.post('/api/affiliates', affiliateForm.data());
        const newAffiliate = response.data.data || response.data;
        if (newAffiliate?.id) {
            selectAffiliate(newAffiliate);
            closeCreateAffiliateModal();
        }
    } catch (error) {
        if (error.response?.status === 422) {
            const errors = error.response.data.errors || {};
            Object.keys(errors).forEach(key => {
                affiliateForm.setError(key, errors[key][0]);
            });
        } else {
            alertDialog({ title: 'Error', text: 'Error al crear el afiliado. Intente nuevamente.', icon: 'error' });
        }
    }
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

const requiresDetails = computed(() => props.types?.find(t => t.value === form.type)?.requiresDetails ?? true);

const missingFields = computed(() => {
    const list = [];
    if (!form.affiliate_id) list.push('Afiliado');
    if (!form.type) list.push('Tipo de cita');
    if (form.type === 'specialist' && !String(form.specialty || '').trim()) list.push('Especialidad');
    if (!form.appointment_date) list.push('Fecha de la cita');
    if (!form.appointment_time) list.push('Hora de la cita');
    return list;
});

const hasValidationErrors = computed(() => Object.keys(form.errors || {}).length > 0);

const validationErrorList = computed(() => {
    const err = form.errors || {};
    return Object.entries(err).map(([key, msg]) => ({ field: key, message: msg }));
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
    form.post('/appointments');
};
</script>

<template>
    <AppLayout>
        <div class="max-w-4xl mx-auto">
            <div class="mb-8">
                <Link href="/appointments" class="inline-flex items-center gap-2 text-sm text-gray-500 hover:text-brand-600 transition-colors">
                    <ChevronLeft class="h-4 w-4" />
                    Volver a citas
                </Link>
                <h1 class="text-3xl font-bold text-gray-900 mt-3">Nueva Cita</h1>
                <p class="text-gray-500 mt-1">Complete los pasos para programar una cita médica</p>
            </div>

            <form @submit.prevent="submit" class="space-y-6">
                <!-- Resumen de errores (backend) -->
                <div v-if="hasValidationErrors" class="rounded-xl border-2 border-red-200 bg-red-50 p-4">
                    <p class="font-semibold text-red-800 mb-2">Revisa los siguientes campos:</p>
                    <ul class="list-disc list-inside space-y-1 text-sm text-red-700">
                        <li v-for="(e, i) in validationErrorList" :key="i">{{ e.message }}</li>
                    </ul>
                </div>

                <!-- SECCIÓN PACIENTE -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-200 bg-gradient-to-r from-gray-50 to-gray-100">
                        <div class="flex items-center gap-3">
                            <div class="p-2 bg-brand-100 rounded-lg">
                                <User class="h-5 w-5 text-brand-600" />
                            </div>
                            <div>
                                <h2 class="text-lg font-semibold text-gray-900">Paso 1: Buscar o Crear Afiliado</h2>
                                <p class="text-sm text-gray-500">Busque por nombre, apellido o número de documento</p>
                            </div>
                        </div>
                    </div>
                    <div class="px-6 py-4 space-y-4">
                        <!-- Afiliado seleccionado -->
                        <div v-if="selectedAffiliate" class="bg-brand-50 border border-brand-200 rounded-xl p-4">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center space-x-4">
                                    <div class="h-14 w-14 rounded-full bg-brand-100 flex items-center justify-center">
                                        <span class="text-brand-700 font-bold text-lg">{{ selectedAffiliate.first_name?.charAt(0) }}{{ selectedAffiliate.last_name?.charAt(0) }}</span>
                                    </div>
                                    <div>
                                        <p class="font-semibold text-gray-900 text-lg">{{ selectedAffiliate.full_name }}</p>
                                        <p class="text-sm text-gray-600">{{ selectedAffiliate.document_type_abbreviation }} {{ selectedAffiliate.document_number }}</p>
                                        <p class="text-sm text-gray-500 flex items-center gap-2 flex-wrap">
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-md font-medium" :class="formatAgeLabel(selectedAffiliate.birth_date) ? 'bg-brand-100 text-brand-800' : 'bg-gray-100 text-gray-600'">{{ formatAgeLabel(selectedAffiliate.birth_date) || 'Edad: no registrada' }}</span>
                                            <span>{{ selectedAffiliate.eps?.name }} • {{ selectedAffiliate.whatsapp_number || selectedAffiliate.phone || 'Sin teléfono' }}</span>
                                        </p>
                                    </div>
                                </div>
                                <button type="button" @click="clearAffiliate" class="flex items-center gap-2 px-4 py-2 text-sm text-red-600 hover:text-red-800 hover:bg-red-50 rounded-lg transition-colors">
                                    <X class="h-4 w-4" />
                                    Cambiar
                                </button>
                            </div>
                        </div>

                        <!-- Búsqueda de paciente -->
                        <div v-else class="space-y-3">
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                    <Loader2 v-if="isSearching" class="h-5 w-5 text-brand-500 animate-spin" />
                                    <Search v-else class="h-5 w-5 text-gray-400" />
                                </div>
                                <input 
                                    v-model="patientSearch" 
                                    type="text" 
                                    placeholder="Escriba nombre, apellido o número de documento..." 
                                    class="block w-full pl-12 pr-4 py-4 rounded-xl border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500 text-lg"
                                />
                            </div>

                            <!-- Resultados de búsqueda -->
                            <div v-if="affiliateResults.length > 0" class="bg-white border border-gray-200 rounded-xl shadow-lg max-h-72 overflow-auto">
                                <button 
v-for="affiliate in affiliateResults"
                                    :key="affiliate.id"
                                    type="button"
                                    @click="selectAffiliate(affiliate)"
                                    class="w-full px-4 py-3 text-left hover:bg-brand-50 border-b last:border-0 flex items-center space-x-3 transition-colors"
                                >
                                    <div class="h-10 w-10 rounded-full bg-gray-100 flex items-center justify-center flex-shrink-0">
                                        <span class="text-gray-600 font-medium">{{ affiliate.first_name?.charAt(0) }}{{ affiliate.last_name?.charAt(0) }}</span>
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <p class="font-medium text-gray-900 truncate">{{ affiliate.full_name }}</p>
                                        <p class="text-sm text-gray-500 flex items-center gap-2 flex-wrap">
                                            <span>{{ affiliate.document_type_abbreviation }} {{ affiliate.document_number }} • {{ affiliate.eps?.name || 'Sin EPS' }}</span>
                                            <span v-if="formatAgeLabel(affiliate.birth_date)" class="inline-flex items-center px-2 py-0.5 rounded-md bg-gray-100 text-gray-700 font-medium">{{ formatAgeLabel(affiliate.birth_date) }}</span>
                                        </p>
                                    </div>
                                    <ChevronRight class="h-5 w-5 text-brand-500" />
                                </button>
                            </div>

                            <!-- No se encontraron resultados -->
                            <div v-if="showNoResults && patientSearch.length >= 2" class="bg-amber-50 border border-amber-200 rounded-xl p-5">
                                <div class="flex items-start space-x-4">
                                    <div class="p-2 bg-amber-100 rounded-lg">
                                        <AlertTriangle class="h-6 w-6 text-amber-600" />
                                    </div>
                                    <div class="flex-1">
                                        <p class="font-semibold text-amber-800">No se encontró ningún afiliado</p>
                                        <p class="text-sm text-amber-700 mt-1">No hay resultados para "<strong>{{ patientSearch }}</strong>"</p>
                                        <button 
                                            type="button" 
                                            @click="openCreateAffiliateModal"
                                            class="mt-3 inline-flex items-center gap-2 px-4 py-2 bg-amber-600 text-white rounded-lg hover:bg-amber-700 transition-colors font-medium"
                                        >
                                            <UserPlus class="h-5 w-5" />
                                            Crear Nuevo Afiliado
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <!-- Instrucción inicial -->
                            <p v-if="patientSearch.length < 2 && !showNoResults" class="text-sm text-gray-500 text-center py-4">
                                Escriba al menos 2 caracteres para buscar...
                            </p>
                        </div>
                    </div>
                </div>

                <!-- SECCIÓN DATOS DE LA CITA - REDISEÑADO -->
                <div v-if="selectedAffiliate" class="space-y-6">
                    <!-- Header del Paso 2 -->
                    <div class="bg-gradient-to-r from-brand-500 to-brand-600 rounded-xl p-6 text-white">
                        <div class="flex items-center gap-3">
                            <div class="p-2 bg-white/20 rounded-lg">
                                <Calendar class="h-6 w-6" />
                            </div>
                            <div>
                                <h2 class="text-xl font-bold">Paso 2: Datos de la Cita</h2>
                                <p class="text-brand-100 text-sm">Configure los detalles de la cita médica</p>
                            </div>
                        </div>
                    </div>

                    <!-- Info de Solicitud (cuando viene de una solicitud) -->
                    <div v-if="fromRequest" class="bg-blue-50 border border-blue-200 rounded-xl p-4">
                        <div class="flex items-start gap-3">
                            <div class="p-2 bg-blue-100 rounded-lg flex-shrink-0">
                                <Clock class="h-5 w-5 text-blue-600" />
                            </div>
                            <div class="min-w-0 flex-1">
                                <p class="text-sm font-medium text-blue-900">
                                    Creando cita desde solicitud #{{ fromRequest.id }}
                                </p>
                                <p v-if="fromRequest.specialty" class="mt-2 text-sm font-semibold text-blue-800">
                                    Especialidad solicitada: {{ fromRequest.specialty }}
                                </p>
                                <p v-if="fromRequest.client_notes" class="mt-1 text-sm text-blue-700">
                                    Notas del cliente: {{ fromRequest.client_notes }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Tipo de Cita y Prioridad -->
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                        <!-- Tipo de Cita -->
                        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                            <label class="flex items-center gap-2 text-sm font-semibold text-gray-900 mb-4">
                                <Stethoscope class="h-5 w-5 text-brand-600" />
                                Tipo de Cita
                            </label>
                            <div class="grid grid-cols-2 gap-3">
                                <button 
                                    v-for="t in types" 
                                    :key="t.value"
                                    type="button"
                                    @click="form.type = t.value"
                                    :class="[
                                        'relative p-4 rounded-xl border-2 transition-all duration-200 text-left',
                                        form.type === t.value 
                                            ? 'border-brand-500 bg-brand-50 ring-2 ring-brand-500/20' 
                                            : 'border-gray-200 hover:border-brand-300 hover:bg-gray-50'
                                    ]"
                                >
                                    <component :is="typeIcons[t.value] || Stethoscope" 
                                        :class="['h-6 w-6 mb-2', form.type === t.value ? 'text-brand-600' : 'text-gray-400']" 
                                    />
                                    <p :class="['font-medium text-sm', form.type === t.value ? 'text-brand-900' : 'text-gray-700']">
                                        {{ t.label }}
                                    </p>
                                    <div v-if="form.type === t.value" class="absolute top-2 right-2">
                                        <Check class="h-5 w-5 text-brand-600" />
                                    </div>
                                </button>
                            </div>
                            <!-- Especialidad si es especialista -->
                            <div v-if="form.type === 'specialist'" class="mt-4">
                                <label class="block text-sm font-medium text-gray-700 mb-2">Especialidad *</label>
                                <input 
                                    v-model="form.specialty" 
                                    type="text" 
                                    placeholder="Ej: Cardiología, Neurología..."
                                    :class="['block w-full rounded-lg border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500', form.errors.specialty ? 'border-red-300' : '']"
                                />
                                <p v-if="form.errors.specialty" class="mt-2 text-sm text-red-600">{{ form.errors.specialty }}</p>
                            </div>
                        </div>

                        <!-- Prioridad -->
                        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                            <label class="flex items-center gap-2 text-sm font-semibold text-gray-900 mb-4">
                                <Flag class="h-5 w-5 text-brand-600" />
                                Prioridad
                            </label>
                            <div class="space-y-3">
                                <button 
                                    v-for="p in priorities" 
                                    :key="p.value"
                                    type="button"
                                    @click="form.priority = p.value"
                                    :class="[
                                        'w-full flex items-center gap-4 p-4 rounded-xl border-2 transition-all duration-200',
                                        form.priority === p.value 
                                            ? `border-${priorityConfig[p.value]?.color || 'gray'}-500 bg-${priorityConfig[p.value]?.color || 'gray'}-50` 
                                            : 'border-gray-200 hover:border-gray-300 hover:bg-gray-50'
                                    ]"
                                >
                                    <div :class="[
                                        'p-2 rounded-lg',
                                        p.value === 'urgent' ? 'bg-red-100 text-red-600' : '',
                                        p.value === 'high' ? 'bg-orange-100 text-orange-600' : '',
                                        p.value === 'medium' ? 'bg-yellow-100 text-yellow-600' : '',
                                        p.value === 'low' ? 'bg-green-100 text-green-600' : ''
                                    ]">
                                        <component :is="priorityConfig[p.value]?.icon || Flag" class="h-5 w-5" />
                                    </div>
                                    <div class="flex-1 text-left">
                                        <p class="font-medium text-gray-900">{{ p.label }}</p>
                                    </div>
                                    <div v-if="form.priority === p.value">
                                        <Check :class="[
                                            'h-5 w-5',
                                            p.value === 'urgent' ? 'text-red-600' : '',
                                            p.value === 'high' ? 'text-orange-600' : '',
                                            p.value === 'medium' ? 'text-yellow-600' : '',
                                            p.value === 'low' ? 'text-green-600' : ''
                                        ]" />
                                    </div>
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Fecha y Hora -->
                    <div v-if="requiresDetails" class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                        <!-- Calendario -->
                        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6" :class="{ 'ring-2 ring-red-200': form.errors.appointment_date }">
                            <label class="flex items-center gap-2 text-sm font-semibold text-gray-900 mb-4">
                                <Calendar class="h-5 w-5 text-brand-600" />
                                Fecha de la Cita *
                            </label>
                            
                            <!-- Mini Calendario -->
                            <div class="bg-gray-50 rounded-xl p-4">
                                <!-- Header del calendario -->
                                <div class="flex items-center justify-between mb-4">
                                    <button type="button" @click="prevMonth" class="p-2 hover:bg-white rounded-lg transition-colors">
                                        <ChevronLeft class="h-5 w-5 text-gray-600" />
                                    </button>
                                    <span class="font-semibold text-gray-900">
                                        {{ monthNames[currentMonth.getMonth()] }} {{ currentMonth.getFullYear() }}
                                    </span>
                                    <button type="button" @click="nextMonth" class="p-2 hover:bg-white rounded-lg transition-colors">
                                        <ChevronRightIcon class="h-5 w-5 text-gray-600" />
                                    </button>
                                </div>
                                
                                <!-- Días de la semana -->
                                <div class="grid grid-cols-7 gap-1 mb-2">
                                    <div v-for="day in dayNames" :key="day" class="text-center text-xs font-medium text-gray-500 py-2">
                                        {{ day }}
                                    </div>
                                </div>
                                
                                <!-- Días del mes -->
                                <div class="grid grid-cols-7 gap-1">
                                    <button
                                        v-for="(day, index) in calendarDays"
                                        :key="index"
                                        type="button"
                                        @click="selectDate(day)"
                                        :disabled="day.isPast || !day.isCurrentMonth || day.isAfterValidUntil"
                                        :class="[
                                            'aspect-square flex items-center justify-center rounded-lg text-sm font-medium transition-all',
                                            !day.isCurrentMonth ? 'text-gray-300' : '',
                                            day.isCurrentMonth && !day.isPast && !day.isAfterValidUntil && !isSelectedDate(day) ? 'text-gray-700 hover:bg-brand-100 hover:text-brand-700' : '',
                                            (day.isPast || day.isAfterValidUntil) && day.isCurrentMonth ? 'text-gray-300 cursor-not-allowed' : '',
                                            day.isToday && !isSelectedDate(day) ? 'bg-brand-100 text-brand-700 font-bold' : '',
                                            isSelectedDate(day) ? 'bg-brand-500 text-white shadow-lg' : ''
                                        ]"
                                    >
                                        {{ day.date.getDate() }}
                                    </button>
                                </div>
                            </div>
                            
                            <!-- RF-AUT-15: aviso vigencia autorización -->
                            <p v-if="authorizationValidUntil && fromRequest?.authorization?.valid_until_formatted" class="mt-3 text-sm text-amber-700 bg-amber-50 border border-amber-200 rounded-lg px-3 py-2">
                                La cita debe agendarse antes del <strong>{{ fromRequest.authorization.valid_until_formatted }}</strong>. Después de esa fecha requiere renovación ante la EPS.
                            </p>
                            <!-- Fecha seleccionada -->
                            <div v-if="form.appointment_date" class="mt-4 p-3 bg-brand-50 border border-brand-200 rounded-lg">
                                <p class="text-sm text-brand-700">
                                    <span class="font-semibold">Fecha seleccionada:</span> {{ formattedSelectedDate }}
                                </p>
                            </div>
                            <p v-if="form.errors.appointment_date" class="mt-2 text-sm text-red-600">{{ form.errors.appointment_date }}</p>
                        </div>

                        <!-- Hora -->
                        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6" :class="{ 'ring-2 ring-red-200': form.errors.appointment_time }">
                            <label class="flex items-center gap-2 text-sm font-semibold text-gray-900 mb-4">
                                <Clock class="h-5 w-5 text-brand-600" />
                                Hora de la Cita *
                            </label>
                            
                            <!-- Grid de horarios -->
                            <div class="grid grid-cols-4 gap-2">
                                <button
                                    v-for="time in commonTimes"
                                    :key="time"
                                    type="button"
                                    @click="selectTime(time)"
                                    :class="[
                                        'py-3 px-2 rounded-lg text-sm font-medium transition-all',
                                        form.appointment_time === time
                                            ? 'bg-brand-500 text-white shadow-lg'
                                            : 'bg-gray-50 text-gray-700 hover:bg-brand-100 hover:text-brand-700'
                                    ]"
                                >
                                    {{ time }}
                                </button>
                            </div>
                            
                            <!-- Input manual de hora -->
                            <div class="mt-4">
                                <label class="block text-xs text-gray-500 mb-2">O ingrese hora manualmente:</label>
                                <input 
                                    v-model="form.appointment_time" 
                                    type="time" 
                                    class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500"
                                />
                            </div>
                            
                            <!-- Hora seleccionada -->
                            <div v-if="form.appointment_time" class="mt-4 p-3 bg-brand-50 border border-brand-200 rounded-lg">
                                <p class="text-sm text-brand-700">
                                    <span class="font-semibold">Hora seleccionada:</span> {{ form.appointment_time }}
                                </p>
                            </div>
                            <p v-if="form.errors.appointment_time" class="mt-2 text-sm text-red-600">{{ form.errors.appointment_time }}</p>
                        </div>
                    </div>

                    <!-- Profesional y Ubicación -->
                    <div v-if="requiresDetails" class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                        <h3 class="flex items-center gap-2 text-sm font-semibold text-gray-900 mb-6">
                            <User class="h-5 w-5 text-brand-600" />
                            Profesional y Ubicación
                        </h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="flex items-center gap-2 text-sm font-medium text-gray-700 mb-2">
                                    <User class="h-4 w-4 text-gray-400" />
                                    Doctor / Profesional
                                </label>
                                <input 
                                    v-model="form.doctor_name" 
                                    type="text" 
                                    placeholder="Nombre del profesional"
                                    class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500"
                                />
                            </div>
                            <div>
                                <label class="flex items-center gap-2 text-sm font-medium text-gray-700 mb-2">
                                    <Building2 class="h-4 w-4 text-gray-400" />
                                    Centro Médico / Lugar
                                </label>
                                <input 
                                    v-model="form.location_name" 
                                    type="text" 
                                    placeholder="Nombre del centro médico"
                                    class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500"
                                />
                            </div>
                            <div>
                                <label class="flex items-center gap-2 text-sm font-medium text-gray-700 mb-2">
                                    <MapPin class="h-4 w-4 text-gray-400" />
                                    Dirección
                                </label>
                                <input 
                                    v-model="form.location_address" 
                                    type="text" 
                                    placeholder="Dirección completa"
                                    class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500"
                                />
                            </div>
                            <div>
                                <label class="flex items-center gap-2 text-sm font-medium text-gray-700 mb-2">
                                    <Phone class="h-4 w-4 text-gray-400" />
                                    Teléfono del centro
                                </label>
                                <input 
                                    v-model="form.location_phone" 
                                    type="text" 
                                    placeholder="Ej: 601 1234567"
                                    class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500"
                                />
                            </div>
                            <div>
                                <!-- Bloque visible cuando la solicitud ya tiene autorización tramitada -->
                                <div v-if="fromRequest?.authorization" class="mb-3 rounded-xl border-2 border-green-200 bg-green-50 p-4">
                                    <p class="text-xs font-semibold text-green-800 uppercase tracking-wide mb-1">Autorización vinculada a esta solicitud</p>
                                    <p class="text-lg font-bold font-mono text-green-900">{{ fromRequest.authorization.authorization_number || fromRequest.authorization.radicado_number || '—' }}</p>
                                    <p v-if="fromRequest.authorization.valid_until_formatted" class="text-sm text-green-700 mt-1">Vigente hasta {{ fromRequest.authorization.valid_until_formatted }}</p>
                                    <p v-if="fromRequest.authorization.service_type" class="text-sm text-green-600 mt-0.5">{{ fromRequest.authorization.service_type }}</p>
                                    <p v-if="fromRequest.authorization.authorized_ips_name" class="text-sm text-green-800 font-medium mt-1">IPS autorizada por la EPS: {{ fromRequest.authorization.authorized_ips_name }}</p>
                                </div>
                                <label class="flex items-center gap-2 text-sm font-medium text-gray-700 mb-2">
                                    <FileText class="h-4 w-4 text-gray-400" />
                                    Número de Autorización
                                </label>
                                <input 
                                    v-model="form.authorization_number" 
                                    type="text" 
                                    placeholder="Número de autorización EPS"
                                    class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500"
                                />
                            </div>
                        </div>
                    </div>

                    <!-- Notas y Especificaciones -->
                    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                        <h3 class="flex items-center gap-2 text-sm font-semibold text-gray-900 mb-6">
                            <ClipboardList class="h-5 w-5 text-brand-600" />
                            Información Adicional
                        </h3>
                        <div class="space-y-6">
                            <div>
                                <label class="flex items-center gap-2 text-sm font-medium text-gray-700 mb-2">
                                    <MessageSquare class="h-4 w-4 text-gray-400" />
                                    Especificaciones para el Paciente
                                    <span class="text-xs text-gray-400 font-normal">(Se envían al paciente)</span>
                                </label>
                                <textarea 
                                    v-model="form.specifications" 
                                    rows="3" 
                                    placeholder="Ej: Ayuno de 8 horas, llevar exámenes previos, presentarse 15 minutos antes..."
                                    class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500"
                                ></textarea>
                            </div>
                            <div>
                                <label class="flex items-center gap-2 text-sm font-medium text-gray-700 mb-2">
                                    <Lock class="h-4 w-4 text-gray-400" />
                                    Notas Internas
                                    <span class="text-xs text-orange-500 font-normal">(Solo visible para el equipo)</span>
                                </label>
                                <textarea 
                                    v-model="form.internal_notes" 
                                    rows="2" 
                                    placeholder="Notas privadas para el equipo de trabajo..."
                                    class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500 bg-orange-50/50"
                                ></textarea>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Opciones de envío -->
                <div v-if="selectedAffiliate" class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                    <label class="flex items-start cursor-pointer group">
                        <div class="relative flex items-center">
                            <input v-model="form.send_confirmation" type="checkbox" class="sr-only peer" />
                            <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-brand-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-brand-500"></div>
                        </div>
                        <div class="ml-4">
                            <div class="flex items-center gap-2">
                                <MessageSquare class="h-5 w-5 text-brand-600" />
                                <span class="font-semibold text-gray-900">Enviar confirmación por WhatsApp</span>
                            </div>
                            <p class="text-sm text-gray-500 mt-1">Se enviará un mensaje automático al afiliado con los detalles de la cita</p>
                        </div>
                    </label>
                </div>

                <!-- Botones -->
                <div v-if="selectedAffiliate" class="flex flex-col sm:flex-row justify-end gap-4 pt-4">
                    <Link href="/appointments" class="inline-flex items-center justify-center gap-2 px-6 py-3 font-medium rounded-xl bg-white text-gray-700 border border-gray-300 hover:bg-gray-50 transition-colors">
                        <X class="h-5 w-5" />
                        Cancelar
                    </Link>
                    <button type="submit" :disabled="form.processing" class="inline-flex items-center justify-center gap-2 px-8 py-3 font-semibold rounded-xl bg-brand-500 text-white hover:bg-brand-600 transition-colors disabled:opacity-50 shadow-lg shadow-brand-500/30">
                        <Loader2 v-if="form.processing" class="h-5 w-5 animate-spin" />
                        <Check v-else class="h-5 w-5" />
                        {{ form.processing ? 'Guardando...' : 'Guardar Cita' }}
                    </button>
                </div>
            </form>
        </div>

        <!-- MODAL CREAR AFILIADO -->
        <div v-if="showCreateAffiliateModal" class="fixed inset-0 z-50 overflow-y-auto">
            <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:p-0">
                <!-- Overlay -->
                <div class="fixed inset-0 bg-gray-900/60 backdrop-blur-sm transition-opacity" @click="closeCreateAffiliateModal"></div>

                <!-- Modal -->
                <div class="relative bg-white rounded-2xl shadow-2xl max-w-2xl w-full mx-auto z-10">
                    <div class="px-6 py-5 border-b border-gray-200 bg-gradient-to-r from-brand-50 to-white flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <div class="p-2 bg-brand-100 rounded-lg">
                                <UserPlus class="h-6 w-6 text-brand-600" />
                            </div>
                            <div>
                                <h3 class="text-xl font-bold text-gray-900">Crear Nuevo Afiliado</h3>
                                <p class="text-sm text-gray-500">Complete la información del afiliado</p>
                            </div>
                        </div>
                        <button @click="closeCreateAffiliateModal" class="p-2 text-gray-400 hover:text-gray-600 hover:bg-gray-100 rounded-lg transition-colors">
                            <X class="h-5 w-5" />
                        </button>
                    </div>

                    <form @submit.prevent="createAffiliate" class="p-6 space-y-6">
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Tipo de documento *</label>
                                <select v-model="affiliateForm.document_type" class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500">
                                    <option value="cc">Cédula de Ciudadanía</option>
                                    <option value="ti">Tarjeta de Identidad</option>
                                    <option value="ce">Cédula de Extranjería</option>
                                    <option value="pa">Pasaporte</option>
                                    <option value="rc">Registro Civil</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Número de documento *</label>
                                <input v-model="affiliateForm.document_number" type="text" class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500" :class="{'border-red-300 ring-red-100': affiliateForm.errors.document_number}" />
                                <p v-if="affiliateForm.errors.document_number" class="mt-1 text-sm text-red-600">{{ affiliateForm.errors.document_number }}</p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Nombres *</label>
                                <input v-model="affiliateForm.first_name" type="text" class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500" :class="{'border-red-300 ring-red-100': affiliateForm.errors.first_name}" />
                                <p v-if="affiliateForm.errors.first_name" class="mt-1 text-sm text-red-600">{{ affiliateForm.errors.first_name }}</p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Apellidos *</label>
                                <input v-model="affiliateForm.last_name" type="text" class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500" :class="{'border-red-300 ring-red-100': affiliateForm.errors.last_name}" />
                                <p v-if="affiliateForm.errors.last_name" class="mt-1 text-sm text-red-600">{{ affiliateForm.errors.last_name }}</p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Teléfono</label>
                                <input v-model="affiliateForm.phone" type="tel" placeholder="3001234567" class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500" />
                            </div>
                            <div>
                                <label class="flex items-center gap-2 text-sm font-medium text-gray-700 mb-2">
                                    <MessageSquare class="h-4 w-4 text-green-500" />
                                    WhatsApp
                                </label>
                                <input v-model="affiliateForm.whatsapp" type="tel" placeholder="3001234567" class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500" />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">EPS *</label>
                                <select v-model="affiliateForm.eps_id" class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500" :class="{'border-red-300 ring-red-100': affiliateForm.errors.eps_id}">
                                    <option value="">Seleccione...</option>
                                    <option v-for="eps in epsList" :key="eps.id" :value="eps.id">{{ eps.name }}</option>
                                </select>
                                <p v-if="affiliateForm.errors.eps_id" class="mt-1 text-sm text-red-600">{{ affiliateForm.errors.eps_id }}</p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Tipo de afiliado *</label>
                                <select v-model="affiliateForm.patient_type" class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500">
                                    <option value="cotizante">Cotizante</option>
                                    <option value="beneficiario">Beneficiario</option>
                                </select>
                            </div>
                            <div class="sm:col-span-2">
                                <label class="block text-sm font-medium text-gray-700 mb-2">Correo electrónico</label>
                                <input v-model="affiliateForm.email" type="email" placeholder="afiliado@email.com" class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500" />
                            </div>
                        </div>

                        <div class="flex justify-end gap-3 pt-6 border-t border-gray-200">
                            <button type="button" @click="closeCreateAffiliateModal" class="inline-flex items-center gap-2 px-5 py-2.5 text-gray-700 bg-white border border-gray-300 rounded-xl hover:bg-gray-50 transition-colors">
                                <X class="h-4 w-4" />
                                Cancelar
                            </button>
                            <button type="submit" :disabled="affiliateForm.processing" class="inline-flex items-center gap-2 px-5 py-2.5 bg-brand-500 text-white rounded-xl hover:bg-brand-600 disabled:opacity-50 transition-colors">
                                <Loader2 v-if="affiliateForm.processing" class="h-4 w-4 animate-spin" />
                                <Check v-else class="h-4 w-4" />
                                {{ affiliateForm.processing ? 'Guardando...' : 'Crear Afiliado' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
