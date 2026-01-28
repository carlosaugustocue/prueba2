<script setup>
import { ref, computed, watch } from 'vue';
import { useForm, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import axios from 'axios';

const props = defineProps({
    types: Array,
    priorities: Array,
    epsList: Array,
    documentTypes: Array,
    patientTypes: Array,
});

// Form principal de cita
const form = useForm({
    patient_id: '',
    type: 'general',
    priority: 'medium',
    specialty: '',
    appointment_date: '',
    appointment_time: '',
    doctor_name: '',
    location_name: '',
    location_address: '',
    authorization_number: '',
    specifications: '',
    internal_notes: '',
    send_confirmation: true,
});

// Búsqueda de pacientes
const patientSearch = ref('');
const patientResults = ref([]);
const selectedPatient = ref(null);
const isSearching = ref(false);
const showNoResults = ref(false);

// Modal para crear nuevo paciente
const showCreatePatientModal = ref(false);
const patientForm = useForm({
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
        showNoResults.value = true;
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
    patientSearch.value = '';
    patientResults.value = [];
    showNoResults.value = false;
};

const openCreatePatientModal = () => {
    // Pre-llenar con el término de búsqueda
    const searchTerm = patientSearch.value.trim();
    
    // Si parece un número de documento
    if (/^\d+$/.test(searchTerm)) {
        patientForm.document_number = searchTerm;
    } else {
        // Si parece un nombre
        const parts = searchTerm.split(' ');
        if (parts.length >= 2) {
            patientForm.first_name = parts[0];
            patientForm.last_name = parts.slice(1).join(' ');
        } else {
            patientForm.first_name = searchTerm;
        }
    }
    
    showCreatePatientModal.value = true;
    patientResults.value = [];
    showNoResults.value = false;
};

const closeCreatePatientModal = () => {
    showCreatePatientModal.value = false;
    patientForm.reset();
    patientForm.clearErrors();
};

const createPatient = async () => {
    try {
        const response = await axios.post('/api/patients', patientForm.data());
        const newPatient = response.data.data || response.data;
        
        // Seleccionar el paciente recién creado
        selectPatient(newPatient);
        closeCreatePatientModal();
        
    } catch (error) {
        if (error.response?.status === 422) {
            // Errores de validación
            const errors = error.response.data.errors || {};
            Object.keys(errors).forEach(key => {
                patientForm.setError(key, errors[key][0]);
            });
        } else {
            alert('Error al crear el paciente. Intente nuevamente.');
        }
    }
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

const requiresDetails = computed(() => props.types?.find(t => t.value === form.type)?.requiresDetails ?? true);

const submit = () => {
    if (!form.patient_id) {
        alert('Debe seleccionar o crear un paciente primero.');
        return;
    }
    form.post('/appointments');
};
</script>

<template>
    <AppLayout>
        <div class="max-w-4xl mx-auto">
            <div class="mb-6">
                <Link href="/appointments" class="text-sm text-gray-500 hover:text-gray-700">← Volver a citas</Link>
                <h1 class="text-2xl font-bold text-gray-900 mt-2">Nueva Cita</h1>
            </div>

            <form @submit.prevent="submit" class="space-y-6">
                <!-- SECCIÓN PACIENTE -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
                        <h2 class="text-lg font-semibold text-gray-900">👤 Paso 1: Buscar o Crear Paciente</h2>
                        <p class="text-sm text-gray-500 mt-1">Busque por nombre, apellido o número de documento</p>
                    </div>
                    <div class="px-6 py-4 space-y-4">
                        <!-- Paciente seleccionado -->
                        <div v-if="selectedPatient" class="bg-green-50 border border-green-200 rounded-lg p-4">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center space-x-4">
                                    <div class="h-12 w-12 rounded-full bg-green-100 flex items-center justify-center">
                                        <span class="text-green-700 font-bold text-lg">{{ selectedPatient.first_name?.charAt(0) }}{{ selectedPatient.last_name?.charAt(0) }}</span>
                                    </div>
                                    <div>
                                        <p class="font-semibold text-gray-900">{{ selectedPatient.full_name }}</p>
                                        <p class="text-sm text-gray-600">{{ selectedPatient.document_type_abbreviation }} {{ selectedPatient.document_number }}</p>
                                        <p class="text-sm text-gray-500">{{ selectedPatient.eps?.name }} • {{ selectedPatient.whatsapp_number || selectedPatient.phone || 'Sin teléfono' }}</p>
                                    </div>
                                </div>
                                <button type="button" @click="clearPatient" class="px-3 py-1 text-sm text-red-600 hover:text-red-800 hover:bg-red-50 rounded-lg transition-colors">
                                    ✕ Cambiar
                                </button>
                            </div>
                        </div>

                        <!-- Búsqueda de paciente -->
                        <div v-else class="space-y-3">
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <span v-if="isSearching" class="animate-spin">⏳</span>
                                    <span v-else>🔍</span>
                                </div>
                                <input 
                                    v-model="patientSearch" 
                                    type="text" 
                                    placeholder="Escriba nombre, apellido o número de documento..." 
                                    class="block w-full pl-10 pr-4 py-3 rounded-lg border-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500 text-lg"
                                />
                            </div>

                            <!-- Resultados de búsqueda -->
                            <div v-if="patientResults.length > 0" class="bg-white border border-gray-200 rounded-lg shadow-lg max-h-72 overflow-auto">
                                <button 
                                    v-for="patient in patientResults" 
                                    :key="patient.id" 
                                    type="button" 
                                    @click="selectPatient(patient)"
                                    class="w-full px-4 py-3 text-left hover:bg-green-50 border-b last:border-0 flex items-center space-x-3 transition-colors"
                                >
                                    <div class="h-10 w-10 rounded-full bg-gray-100 flex items-center justify-center flex-shrink-0">
                                        <span class="text-gray-600 font-medium">{{ patient.first_name?.charAt(0) }}{{ patient.last_name?.charAt(0) }}</span>
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <p class="font-medium text-gray-900 truncate">{{ patient.full_name }}</p>
                                        <p class="text-sm text-gray-500">{{ patient.document_type_abbreviation }} {{ patient.document_number }} • {{ patient.eps?.name || 'Sin EPS' }}</p>
                                    </div>
                                    <span class="text-green-600 text-sm">Seleccionar →</span>
                                </button>
                            </div>

                            <!-- No se encontraron resultados -->
                            <div v-if="showNoResults && patientSearch.length >= 2" class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                                <div class="flex items-start space-x-3">
                                    <span class="text-2xl">⚠️</span>
                                    <div class="flex-1">
                                        <p class="font-medium text-yellow-800">No se encontró ningún paciente</p>
                                        <p class="text-sm text-yellow-700 mt-1">No hay resultados para "<strong>{{ patientSearch }}</strong>"</p>
                                        <button 
                                            type="button" 
                                            @click="openCreatePatientModal"
                                            class="mt-3 inline-flex items-center px-4 py-2 bg-yellow-600 text-white rounded-lg hover:bg-yellow-700 transition-colors font-medium"
                                        >
                                            ➕ Crear Nuevo Paciente
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <!-- Instrucción inicial -->
                            <p v-if="patientSearch.length < 2 && !showNoResults" class="text-sm text-gray-500 text-center py-2">
                                Escriba al menos 2 caracteres para buscar...
                            </p>
                        </div>
                    </div>
                </div>

                <!-- SECCIÓN DATOS DE LA CITA -->
                <div v-if="selectedPatient" class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
                        <h2 class="text-lg font-semibold text-gray-900">📅 Paso 2: Datos de la Cita</h2>
                    </div>
                    <div class="px-6 py-4 grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Tipo de cita *</label>
                            <select v-model="form.type" class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500">
                                <option v-for="t in types" :key="t.value" :value="t.value">{{ t.label }}</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Prioridad *</label>
                            <select v-model="form.priority" class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500">
                                <option v-for="p in priorities" :key="p.value" :value="p.value">{{ p.label }}</option>
                            </select>
                        </div>
                        <div v-if="form.type === 'specialist'">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Especialidad</label>
                            <input v-model="form.specialty" type="text" placeholder="Ej: Cardiología" class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500" />
                        </div>
                        <div v-if="requiresDetails">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Fecha</label>
                            <input v-model="form.appointment_date" type="date" class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500" />
                        </div>
                        <div v-if="requiresDetails">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Hora</label>
                            <input v-model="form.appointment_time" type="time" class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500" />
                        </div>
                        <div v-if="requiresDetails">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Doctor</label>
                            <input v-model="form.doctor_name" type="text" placeholder="Nombre del profesional" class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500" />
                        </div>
                        <div v-if="requiresDetails">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Lugar</label>
                            <input v-model="form.location_name" type="text" placeholder="Centro médico" class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500" />
                        </div>
                        <div v-if="requiresDetails">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Dirección</label>
                            <input v-model="form.location_address" type="text" placeholder="Dirección" class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500" />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Número de autorización</label>
                            <input v-model="form.authorization_number" type="text" class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500" />
                        </div>
                        <div class="sm:col-span-2">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Especificaciones para el paciente</label>
                            <textarea v-model="form.specifications" rows="2" class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500" placeholder="Ej: Ayuno de 8 horas, llevar exámenes previos..."></textarea>
                        </div>
                        <div class="sm:col-span-2">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Notas internas</label>
                            <textarea v-model="form.internal_notes" rows="2" class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500" placeholder="Notas para el equipo (no se envían al paciente)..."></textarea>
                        </div>
                    </div>
                </div>

                <!-- Opciones de envío -->
                <div v-if="selectedPatient" class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                    <div class="px-6 py-4">
                        <label class="flex items-center cursor-pointer">
                            <input v-model="form.send_confirmation" type="checkbox" class="h-5 w-5 text-green-600 border-gray-300 rounded focus:ring-green-500" />
                            <span class="ml-3">
                                <span class="font-medium text-gray-900">📱 Enviar confirmación por WhatsApp</span>
                                <span class="block text-sm text-gray-500">Se enviará un mensaje al paciente con los detalles de la cita</span>
                            </span>
                        </label>
                    </div>
                </div>

                <!-- Botones -->
                <div v-if="selectedPatient" class="flex justify-end space-x-4">
                    <Link href="/appointments" class="inline-flex items-center justify-center px-6 py-3 font-medium rounded-lg bg-white text-gray-700 border border-gray-300 hover:bg-gray-50 transition-colors">
                        Cancelar
                    </Link>
                    <button type="submit" :disabled="form.processing" class="inline-flex items-center justify-center px-6 py-3 font-medium rounded-lg bg-green-600 text-white hover:bg-green-700 transition-colors disabled:opacity-50">
                        {{ form.processing ? '⏳ Guardando...' : '✅ Guardar Cita' }}
                    </button>
                </div>
            </form>
        </div>

        <!-- MODAL CREAR PACIENTE -->
        <div v-if="showCreatePatientModal" class="fixed inset-0 z-50 overflow-y-auto">
            <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:p-0">
                <!-- Overlay -->
                <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" @click="closeCreatePatientModal"></div>

                <!-- Modal -->
                <div class="relative bg-white rounded-2xl shadow-xl max-w-2xl w-full mx-auto z-10">
                    <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
                        <h3 class="text-xl font-bold text-gray-900">➕ Crear Nuevo Paciente</h3>
                        <button @click="closeCreatePatientModal" class="text-gray-400 hover:text-gray-600 text-2xl">&times;</button>
                    </div>

                    <form @submit.prevent="createPatient" class="p-6 space-y-4">
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Tipo de documento *</label>
                                <select v-model="patientForm.document_type" class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500">
                                    <option value="cc">Cédula de Ciudadanía</option>
                                    <option value="ti">Tarjeta de Identidad</option>
                                    <option value="ce">Cédula de Extranjería</option>
                                    <option value="pa">Pasaporte</option>
                                    <option value="rc">Registro Civil</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Número de documento *</label>
                                <input v-model="patientForm.document_number" type="text" class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500" :class="{'border-red-300': patientForm.errors.document_number}" />
                                <p v-if="patientForm.errors.document_number" class="mt-1 text-sm text-red-600">{{ patientForm.errors.document_number }}</p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Nombres *</label>
                                <input v-model="patientForm.first_name" type="text" class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500" :class="{'border-red-300': patientForm.errors.first_name}" />
                                <p v-if="patientForm.errors.first_name" class="mt-1 text-sm text-red-600">{{ patientForm.errors.first_name }}</p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Apellidos *</label>
                                <input v-model="patientForm.last_name" type="text" class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500" :class="{'border-red-300': patientForm.errors.last_name}" />
                                <p v-if="patientForm.errors.last_name" class="mt-1 text-sm text-red-600">{{ patientForm.errors.last_name }}</p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Teléfono</label>
                                <input v-model="patientForm.phone" type="tel" placeholder="3001234567" class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500" />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">WhatsApp 📱</label>
                                <input v-model="patientForm.whatsapp" type="tel" placeholder="3001234567" class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500" />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">EPS *</label>
                                <select v-model="patientForm.eps_id" class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500" :class="{'border-red-300': patientForm.errors.eps_id}">
                                    <option value="">Seleccione...</option>
                                    <option v-for="eps in epsList" :key="eps.id" :value="eps.id">{{ eps.name }}</option>
                                </select>
                                <p v-if="patientForm.errors.eps_id" class="mt-1 text-sm text-red-600">{{ patientForm.errors.eps_id }}</p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Tipo de afiliado *</label>
                                <select v-model="patientForm.patient_type" class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500">
                                    <option value="cotizante">Cotizante</option>
                                    <option value="beneficiario">Beneficiario</option>
                                </select>
                            </div>
                            <div class="sm:col-span-2">
                                <label class="block text-sm font-medium text-gray-700 mb-1">Correo electrónico</label>
                                <input v-model="patientForm.email" type="email" placeholder="paciente@email.com" class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500" />
                            </div>
                        </div>

                        <div class="flex justify-end space-x-3 pt-4 border-t border-gray-200">
                            <button type="button" @click="closeCreatePatientModal" class="px-4 py-2 text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50">
                                Cancelar
                            </button>
                            <button type="submit" :disabled="patientForm.processing" class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 disabled:opacity-50">
                                {{ patientForm.processing ? 'Guardando...' : 'Crear Paciente' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
