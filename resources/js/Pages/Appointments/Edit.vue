<script setup>
import { useForm, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { computed, onMounted } from 'vue';
import {
    ArrowLeft, User, Building2, Phone, BadgeInfo,
    ClipboardList, CalendarDays, MapPin, FileText,
    Save, Loader2, X
} from 'lucide-vue-next';

const props = defineProps({
    appointment: Object,
    types: Array,
    priorities: Array,
    statuses: Array,
    epsList: Array,
});

// Los datos vienen envueltos en "data" por el Resource
const appointmentData = computed(() => props.appointment?.data || props.appointment || {});
const patient = computed(() => appointmentData.value?.patient || {});

// Debug
onMounted(() => {
    console.log('Appointment Data:', appointmentData.value);
    console.log('Patient:', patient.value);
});

// Inicializar el formulario con los valores del appointment
const form = useForm({
    type: appointmentData.value?.type || 'general',
    priority: appointmentData.value?.priority || 'medium',
    specialty: appointmentData.value?.specialty || '',
    appointment_date: appointmentData.value?.appointment_date || '',
    appointment_time: appointmentData.value?.appointment_time || '',
    doctor_name: appointmentData.value?.doctor_name || '',
    location_name: appointmentData.value?.location_name || '',
    location_address: appointmentData.value?.location_address || '',
    authorization_number: appointmentData.value?.authorization_number || '',
    specifications: appointmentData.value?.specifications || '',
    internal_notes: appointmentData.value?.internal_notes || '',
});

const requiresDetails = computed(() => {
    const type = props.types?.find(t => t.value === form.type);
    return type?.requiresDetails ?? true;
});

const submit = () => {
    form.put(`/appointments/${appointmentData.value.id}`);
};
</script>

<template>
    <AppLayout>
        <div class="max-w-4xl mx-auto">
            <!-- Header -->
            <div class="mb-6">
                <Link :href="`/appointments/${appointmentData.id}`" class="inline-flex items-center gap-2 text-sm text-gray-500 hover:text-brand-700 mb-2">
                    <ArrowLeft class="h-4 w-4" />
                    Volver al detalle
                </Link>
                <h1 class="text-2xl font-bold text-gray-900">Editar Cita #{{ appointmentData.id }}</h1>
            </div>

            <!-- Paciente (solo lectura) -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 mb-6">
                <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-4">
                        <div class="h-14 w-14 rounded-full bg-brand-100 flex items-center justify-center">
                            <span class="text-brand-700 font-bold text-xl">
                                {{ patient.first_name?.charAt(0) || '?' }}{{ patient.last_name?.charAt(0) || '' }}
                            </span>
                        </div>
                        <div>
                            <p class="text-lg font-semibold text-gray-900">
                                {{ patient.full_name || 'Paciente no cargado' }}
                            </p>
                            <p class="text-sm text-gray-600">
                                {{ patient.document_type_abbreviation || '' }} {{ patient.document_number || '' }}
                            </p>
                            <div class="mt-1 flex flex-wrap items-center gap-x-3 gap-y-1 text-sm text-gray-500">
                                <span class="inline-flex items-center gap-1">
                                    <Building2 class="h-4 w-4 text-gray-400" />
                                    {{ patient.eps?.name || 'Sin EPS' }}
                                </span>
                                <span class="inline-flex items-center gap-1">
                                    <Phone class="h-4 w-4 text-gray-400" />
                                    {{ patient.whatsapp_number || patient.phone || 'Sin teléfono' }}
                                </span>
                            </div>
                        </div>
                    </div>
                    <div class="text-right">
                        <span :class="[appointmentData.status_badge_class || 'bg-gray-100 text-gray-800', 'inline-flex items-center px-3 py-1 rounded-full text-sm font-medium']">
                            {{ appointmentData.status_label || 'Sin estado' }}
                        </span>
                        <p class="text-xs text-gray-500 mt-1">{{ appointmentData.type_label }}</p>
                    </div>
                </div>
            </div>

            <!-- Datos actuales -->
            <div class="bg-brand-50 border border-brand-200 rounded-xl p-4 mb-6">
                <div class="flex items-center gap-2 mb-3">
                    <div class="h-9 w-9 rounded-lg bg-brand-100 flex items-center justify-center">
                        <BadgeInfo class="h-5 w-5 text-brand-700" />
                    </div>
                    <h3 class="font-semibold text-brand-900">Datos actuales de la cita</h3>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 text-sm">
                    <div class="bg-white border border-brand-100 rounded-lg p-3">
                        <p class="text-xs font-medium text-gray-500">Fecha</p>
                        <p class="mt-1 font-semibold text-gray-900">{{ appointmentData.appointment_date_formatted || 'No definida' }}</p>
                    </div>
                    <div class="bg-white border border-brand-100 rounded-lg p-3">
                        <p class="text-xs font-medium text-gray-500">Hora</p>
                        <p class="mt-1 font-semibold text-gray-900">{{ appointmentData.appointment_time || 'No definida' }}</p>
                    </div>
                    <div class="bg-white border border-brand-100 rounded-lg p-3">
                        <p class="text-xs font-medium text-gray-500">Profesional</p>
                        <p class="mt-1 font-semibold text-gray-900">{{ appointmentData.doctor_name || 'No definido' }}</p>
                    </div>
                    <div class="bg-white border border-brand-100 rounded-lg p-3">
                        <p class="text-xs font-medium text-gray-500">Lugar</p>
                        <p class="mt-1 font-semibold text-gray-900">{{ appointmentData.location_name || 'No definido' }}</p>
                    </div>
                </div>
            </div>

            <form @submit.prevent="submit" class="space-y-6">
                <!-- Tipo y Prioridad -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-200 bg-gray-50 flex items-center gap-2">
                        <ClipboardList class="h-5 w-5 text-brand-600" />
                        <h2 class="text-lg font-semibold text-gray-900">Tipo y Prioridad</h2>
                    </div>
                    <div class="px-6 py-4 grid grid-cols-1 sm:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Tipo de cita *</label>
                            <select v-model="form.type" class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500">
                                <option v-for="t in types" :key="t.value" :value="t.value">{{ t.label }}</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Prioridad *</label>
                            <select v-model="form.priority" class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500">
                                <option v-for="p in priorities" :key="p.value" :value="p.value">{{ p.label }}</option>
                            </select>
                        </div>
                        <div v-if="form.type === 'specialist'">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Especialidad</label>
                            <input v-model="form.specialty" type="text" placeholder="Ej: Cardiología" class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500" />
                        </div>
                    </div>
                </div>

                <!-- Fecha, Hora y Doctor -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-200 bg-gray-50 flex items-center gap-2">
                        <CalendarDays class="h-5 w-5 text-brand-600" />
                        <h2 class="text-lg font-semibold text-gray-900">Fecha y Profesional</h2>
                    </div>
                    <div class="px-6 py-4 grid grid-cols-1 sm:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Fecha</label>
                            <input v-model="form.appointment_date" type="date" class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500" />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Hora</label>
                            <input v-model="form.appointment_time" type="time" class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500" />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Doctor / Profesional</label>
                            <input v-model="form.doctor_name" type="text" placeholder="Nombre del profesional" class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500" />
                        </div>
                    </div>
                </div>

                <!-- Ubicación -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-200 bg-gray-50 flex items-center gap-2">
                        <MapPin class="h-5 w-5 text-brand-600" />
                        <h2 class="text-lg font-semibold text-gray-900">Ubicación</h2>
                    </div>
                    <div class="px-6 py-4 grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Nombre del lugar</label>
                            <input v-model="form.location_name" type="text" placeholder="Centro médico, clínica..." class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500" />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Dirección</label>
                            <input v-model="form.location_address" type="text" placeholder="Dirección completa" class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500" />
                        </div>
                        <div class="sm:col-span-2">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Número de autorización</label>
                            <input v-model="form.authorization_number" type="text" placeholder="Número de autorización EPS" class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500" />
                        </div>
                    </div>
                </div>

                <!-- Especificaciones y Notas -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-200 bg-gray-50 flex items-center gap-2">
                        <FileText class="h-5 w-5 text-brand-600" />
                        <h2 class="text-lg font-semibold text-gray-900">Especificaciones y Notas</h2>
                    </div>
                    <div class="px-6 py-4 space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                Especificaciones para el paciente
                            </label>
                            <textarea v-model="form.specifications" rows="3" class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500" placeholder="Ej: Ayuno de 8 horas..."></textarea>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                Notas internas
                            </label>
                            <textarea v-model="form.internal_notes" rows="3" class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500" placeholder="Notas para el equipo..."></textarea>
                        </div>
                    </div>
                </div>

                <!-- Botones -->
                <div class="flex flex-col sm:flex-row sm:justify-between gap-4 pt-4">
                    <Link :href="`/appointments/${appointmentData.id}`" class="inline-flex items-center gap-2 justify-center px-6 py-3 font-medium rounded-lg bg-white text-gray-700 border border-gray-300 hover:bg-gray-50 transition-colors">
                        <X class="h-4 w-4" />
                        Cancelar
                    </Link>
                    <button type="submit" :disabled="form.processing" class="inline-flex items-center gap-2 justify-center px-8 py-3 font-medium rounded-lg bg-brand-500 text-white hover:bg-brand-600 transition-colors disabled:opacity-50">
                        <Loader2 v-if="form.processing" class="h-5 w-5 animate-spin" />
                        <Save v-else class="h-5 w-5" />
                        {{ form.processing ? 'Guardando...' : 'Guardar cambios' }}
                    </button>
                </div>
            </form>
        </div>
    </AppLayout>
</template>
