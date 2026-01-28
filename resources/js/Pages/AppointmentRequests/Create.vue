<script setup>
import { ref, watch } from 'vue';
import { useForm, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import axios from 'axios';
import {
    ChevronLeft, User, Search, Loader2, X, Check, Clock,
    Stethoscope, Heart, FlaskConical, Scan, ClipboardList,
    AlertCircle, Flag, Zap, FileText, MessageSquare
} from 'lucide-vue-next';

const props = defineProps({
    types: Array,
    priorities: Array,
    epsList: Array,
    documentTypes: Array,
    patientTypes: Array,
});

const form = useForm({
    patient_id: '',
    type: 'general',
    priority: 'medium',
    specialty: '',
    requested_at: new Date().toISOString().slice(0, 16),
    client_notes: '',
});

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

// Búsqueda de pacientes
const patientSearch = ref('');
const patientResults = ref([]);
const selectedPatient = ref(null);
const isSearching = ref(false);
const showNoResults = ref(false);

const searchPatients = async () => {
    if (patientSearch.value.length < 2) {
        patientResults.value = [];
        showNoResults.value = false;
        return;
    }
    
    isSearching.value = true;
    showNoResults.value = false;
    
    try {
        const response = await axios.get('/api/patients/search', {
            params: { term: patientSearch.value }
        });
        patientResults.value = response.data.data || response.data || [];
        showNoResults.value = patientResults.value.length === 0;
    } catch (error) {
        console.error(error);
        patientResults.value = [];
    } finally {
        isSearching.value = false;
    }
};

const selectPatient = (patient) => {
    selectedPatient.value = patient;
    form.patient_id = patient.id;
    patientSearch.value = '';
    patientResults.value = [];
    showNoResults.value = false;
};

const clearPatient = () => {
    selectedPatient.value = null;
    form.patient_id = '';
};

let searchTimeout;
watch(patientSearch, () => {
    if (!selectedPatient.value && patientSearch.value.length >= 2) {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(searchPatients, 400);
    } else if (patientSearch.value.length < 2) {
        patientResults.value = [];
        showNoResults.value = false;
    }
});

const submit = () => {
    if (!form.patient_id) {
        alert('Debe seleccionar un paciente.');
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
                <!-- Fecha de Solicitud -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                    <div class="flex items-start gap-4">
                        <div class="p-2 bg-amber-100 rounded-lg flex-shrink-0">
                            <Clock class="h-5 w-5 text-amber-600" />
                        </div>
                        <div class="flex-1">
                            <label class="block text-sm font-semibold text-gray-900 mb-1">
                                ¿Cuándo solicitó el cliente esta cita? *
                            </label>
                            <p class="text-sm text-gray-500 mb-3">
                                Registre la fecha y hora en que el cliente contactó a Serviconli
                            </p>
                            <input 
                                v-model="form.requested_at" 
                                type="datetime-local" 
                                :class="['block w-full sm:w-auto rounded-lg border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500', form.errors.requested_at ? 'border-red-300' : '']"
                            />
                            <p v-if="form.errors.requested_at" class="mt-1 text-sm text-red-600">{{ form.errors.requested_at }}</p>
                        </div>
                    </div>
                </div>

                <!-- Paciente -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                    <h2 class="flex items-center gap-2 text-lg font-semibold text-gray-900 mb-4">
                        <User class="h-5 w-5 text-brand-600" />
                        Paciente *
                    </h2>

                    <!-- Paciente seleccionado -->
                    <div v-if="selectedPatient" class="bg-brand-50 border border-brand-200 rounded-xl p-4">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-4">
                                <div class="h-12 w-12 rounded-full bg-brand-100 flex items-center justify-center">
                                    <span class="text-brand-700 font-bold">{{ selectedPatient.first_name?.charAt(0) }}{{ selectedPatient.last_name?.charAt(0) }}</span>
                                </div>
                                <div>
                                    <p class="font-semibold text-gray-900">{{ selectedPatient.full_name }}</p>
                                    <p class="text-sm text-gray-600">{{ selectedPatient.document_type_abbreviation }} {{ selectedPatient.document_number }}</p>
                                    <p class="text-sm text-gray-500">{{ selectedPatient.eps?.name }} • {{ selectedPatient.whatsapp_number || selectedPatient.phone || 'Sin teléfono' }}</p>
                                </div>
                            </div>
                            <button type="button" @click="clearPatient" class="flex items-center gap-2 px-3 py-2 text-sm text-red-600 hover:bg-red-50 rounded-lg transition-colors">
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
                                v-model="patientSearch" 
                                type="text" 
                                placeholder="Buscar por nombre o documento..." 
                                class="block w-full pl-12 pr-4 py-3 rounded-xl border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500"
                            />
                        </div>

                        <div v-if="patientResults.length > 0" class="bg-white border border-gray-200 rounded-xl shadow-lg max-h-64 overflow-auto">
                            <button 
                                v-for="patient in patientResults" 
                                :key="patient.id" 
                                type="button" 
                                @click="selectPatient(patient)"
                                class="w-full px-4 py-3 text-left hover:bg-brand-50 border-b last:border-0 flex items-center gap-3 transition-colors"
                            >
                                <div class="h-10 w-10 rounded-full bg-gray-100 flex items-center justify-center">
                                    <span class="text-gray-600 font-medium">{{ patient.first_name?.charAt(0) }}{{ patient.last_name?.charAt(0) }}</span>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p class="font-medium text-gray-900 truncate">{{ patient.full_name }}</p>
                                    <p class="text-sm text-gray-500">{{ patient.document_type_abbreviation }} {{ patient.document_number }}</p>
                                </div>
                                <Check class="h-5 w-5 text-brand-500" />
                            </button>
                        </div>

                        <p v-if="showNoResults" class="text-sm text-amber-600 text-center py-2">
                            No se encontraron pacientes. Puede crear uno nuevo desde el módulo de pacientes.
                        </p>
                    </div>
                    <p v-if="form.errors.patient_id" class="mt-2 text-sm text-red-600">{{ form.errors.patient_id }}</p>
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
                                    : 'border-gray-200 hover:border-brand-300 hover:bg-gray-50'
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
                </div>

                <!-- Prioridad y Especialidad -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Prioridad -->
                        <div>
                            <label class="flex items-center gap-2 text-sm font-semibold text-gray-900 mb-3">
                                <Flag class="h-4 w-4 text-brand-600" />
                                Prioridad *
                            </label>
                            <div class="grid grid-cols-2 gap-2">
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

                        <!-- Especialidad -->
                        <div>
                            <label class="flex items-center gap-2 text-sm font-semibold text-gray-900 mb-3">
                                <FileText class="h-4 w-4 text-brand-600" />
                                Especialidad (opcional)
                            </label>
                            <input 
                                v-model="form.specialty" 
                                type="text" 
                                placeholder="Ej: Cardiología, Pediatría..."
                                class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500"
                            />
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
