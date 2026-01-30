<script setup>
import { ref, computed, watchEffect } from 'vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { confirmDialog, toast } from '@/Utils/swal';
import {
    ChevronLeft, Clock, User, Calendar, Play, XCircle, AlertTriangle,
    CheckCircle, Phone, Mail, Building2, FileText, MessageSquare, ArrowRight
} from 'lucide-vue-next';

const page = usePage();
const isAdmin = computed(() => page.props.auth?.user?.role === 'admin');

const props = defineProps({
    appointmentRequest: Object,
    statuses: Array,
    types: Array,
    priorities: Array,
});

const request = computed(() => props.appointmentRequest?.data || props.appointmentRequest || {});
const patient = computed(() => request.value.patient || {});

const notesDraft = ref('');
const savingNotes = ref(false);
const canEditNotes = computed(() => {
    const userId = page.props.auth?.user?.id;
    if (!userId) return false;
    if (isAdmin.value) return true;
    if (!request.value?.is_active) return false;
    // Solo la operadora asignada (si existe) puede editar
    if (request.value?.assignee?.id && request.value.assignee.id !== userId) return false;
    return true;
});

watchEffect(() => {
    notesDraft.value = request.value?.operator_notes || '';
});

// Modal para marcar como fallida
const showFailedModal = ref(false);
const failedReason = ref('');

// Modal para cancelar
const showCancelModal = ref(false);
const cancelReason = ref('');

const startProcessing = () => {
    confirmDialog({
        title: 'Tomar solicitud',
        text: '¿Desea tomar esta solicitud para tramitarla?',
        confirmButtonText: 'Tomar',
    }).then((ok) => {
        if (!ok) return;
        router.post(`/appointment-requests/${request.value.id}/start`);
    });
};

const markAsFailed = () => {
    router.post(`/appointment-requests/${request.value.id}/mark-failed`, {
        reason: failedReason.value
    });
    showFailedModal.value = false;
};

const cancelRequest = () => {
    router.post(`/appointment-requests/${request.value.id}/cancel`, {
        reason: cancelReason.value
    });
    showCancelModal.value = false;
};

const deleteRequest = () => {
    confirmDialog({
        title: 'Eliminar solicitud',
        text: '¿Está seguro de eliminar esta solicitud? Esta acción no se puede deshacer.',
        icon: 'warning',
        confirmButtonText: 'Eliminar',
    }).then((ok) => {
        if (!ok) return;
        router.delete(`/appointment-requests/${request.value.id}`);
    });
};

const saveNotes = () => {
    if (!canEditNotes.value) return;
    savingNotes.value = true;
    router.post(`/appointment-requests/${request.value.id}/notes`, {
        operator_notes: notesDraft.value,
    }, {
        preserveScroll: true,
        onSuccess: () => {
            toast({ title: 'Anotaciones guardadas.' });
        },
        onError: () => {
            toast({ title: 'No se pudieron guardar las anotaciones.', icon: 'error' });
        },
        onFinish: () => {
            savingNotes.value = false;
        }
    });
};

const getTimeClass = (minutes) => {
    if (!minutes) return 'bg-gray-100 text-gray-700';
    if (minutes <= 60) return 'bg-green-100 text-green-700';
    if (minutes <= 240) return 'bg-yellow-100 text-yellow-700';
    return 'bg-red-100 text-red-700';
};
</script>

<template>
    <AppLayout>
        <div class="max-w-5xl mx-auto space-y-6">
            <!-- Header -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <Link href="/appointment-requests" class="inline-flex items-center gap-2 text-sm text-gray-500 hover:text-brand-600 transition-colors">
                        <ChevronLeft class="h-4 w-4" />
                        Volver a solicitudes
                    </Link>
                    <h1 class="text-2xl font-bold text-gray-900 mt-2">Solicitud #{{ request.id }}</h1>
                    <p v-if="isAdmin && request.requested_at_relative" class="text-gray-500 mt-1">{{ request.requested_at_relative }}</p>
                    <p v-else class="text-gray-500 mt-1">Registrada el {{ request.created_at_formatted }}</p>
                </div>
                <div class="flex flex-wrap gap-2">
                    <!-- Acciones según estado -->
                    <button 
                        v-if="request.is_pending"
                        @click="startProcessing"
                        class="inline-flex items-center gap-2 px-4 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600 transition-colors"
                    >
                        <Play class="h-5 w-5" />
                        Tomar Solicitud
                    </button>
                    
                    <Link 
                        v-if="request.status === 'in_progress'"
                        :href="`/appointment-requests/${request.id}/create-appointment`"
                        class="inline-flex items-center gap-2 px-4 py-2 bg-brand-500 text-white rounded-lg hover:bg-brand-600 transition-colors"
                    >
                        <Calendar class="h-5 w-5" />
                        Crear Cita
                    </Link>

                    <button 
                        v-if="request.is_active"
                        @click="showFailedModal = true"
                        class="inline-flex items-center gap-2 px-4 py-2 bg-white border border-orange-300 text-orange-600 rounded-lg hover:bg-orange-50 transition-colors"
                    >
                        <AlertTriangle class="h-5 w-5" />
                        No Obtenida
                    </button>

                    <button 
                        v-if="request.is_active"
                        @click="showCancelModal = true"
                        class="inline-flex items-center gap-2 px-4 py-2 bg-white border border-gray-300 text-gray-600 rounded-lg hover:bg-gray-50 transition-colors"
                    >
                        <XCircle class="h-5 w-5" />
                        Cancelar
                    </button>

                    <Link 
                        v-if="request.has_appointment"
                        :href="`/appointments/${request.appointment_id}`"
                        class="inline-flex items-center gap-2 px-4 py-2 bg-green-100 text-green-700 rounded-lg hover:bg-green-200 transition-colors"
                    >
                        <CheckCircle class="h-5 w-5" />
                        Ver Cita Obtenida
                    </Link>
                </div>
            </div>

            <!-- Grid principal -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Columna izquierda -->
                <div class="lg:col-span-2 space-y-6">
                    <!-- Estado y tiempos -->
                    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
                            <div>
                                <p class="text-sm font-medium text-gray-500 mb-2">Estado</p>
                                <span :class="[request.status_badge_class, 'inline-flex items-center gap-2 px-4 py-2 rounded-full text-base font-semibold']">
                                    <span class="text-lg">{{ request.status_icon }}</span>
                                    {{ request.status_label }}
                                </span>
                            </div>
                            <div>
                                <p class="text-sm font-medium text-gray-500 mb-2">Prioridad</p>
                                <span :class="[request.priority_badge_class, 'inline-flex items-center px-3 py-1 rounded-full text-sm font-medium']">
                                    {{ request.priority_label }}
                                </span>
                            </div>
                        </div>

                        <!-- Línea de tiempo de trámite (solo admin) -->
                        <div v-if="isAdmin" class="border-t border-gray-200 pt-6">
                            <h3 class="text-sm font-semibold text-gray-900 mb-4">⏱️ Tiempos del Trámite</h3>
                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                                <div class="bg-gray-50 rounded-lg p-4">
                                    <p class="text-xs text-gray-500 mb-1">Solicitud del Cliente</p>
                                    <p class="font-semibold text-gray-900">{{ request.requested_at_formatted || '-' }}</p>
                                </div>
                                <div class="bg-gray-50 rounded-lg p-4">
                                    <p class="text-xs text-gray-500 mb-1">Inicio de Trámite</p>
                                    <p class="font-semibold text-gray-900">{{ request.started_at_formatted || '-' }}</p>
                                    <p v-if="request.waiting_time_formatted" :class="[getTimeClass(request.waiting_time_minutes), 'text-xs px-2 py-0.5 rounded mt-1 inline-block']">
                                        Espera: {{ request.waiting_time_formatted }}
                                    </p>
                                </div>
                                <div class="bg-gray-50 rounded-lg p-4">
                                    <p class="text-xs text-gray-500 mb-1">Finalización</p>
                                    <p class="font-semibold text-gray-900">{{ request.completed_at_formatted || '-' }}</p>
                                    <p v-if="request.processing_time_formatted" :class="[getTimeClass(request.processing_time_minutes), 'text-xs px-2 py-0.5 rounded mt-1 inline-block']">
                                        Total: {{ request.processing_time_formatted }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Detalles de la solicitud -->
                    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                        <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
                            <h2 class="text-lg font-semibold text-gray-900">📋 Detalles de la Solicitud</h2>
                        </div>
                        <div class="p-6">
                            <dl class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">Tipo de Cita</dt>
                                    <dd class="mt-1 text-base text-gray-900">{{ request.type_label }}</dd>
                                </div>
                                <div v-if="request.specialty">
                                    <dt class="text-sm font-medium text-gray-500">Especialidad</dt>
                                    <dd class="mt-1 text-base text-gray-900">{{ request.specialty }}</dd>
                                </div>
                            </dl>

                            <!-- Notas del cliente -->
                            <div v-if="request.client_notes" class="mt-6 pt-6 border-t border-gray-200">
                                <h3 class="flex items-center gap-2 text-sm font-medium text-gray-500 mb-2">
                                    <MessageSquare class="h-4 w-4" />
                                    Notas del Cliente
                                </h3>
                                <div class="bg-blue-50 border border-blue-100 rounded-lg p-4">
                                    <p class="text-gray-800 whitespace-pre-line">{{ request.client_notes }}</p>
                                </div>
                            </div>

                            <!-- Anotaciones internas -->
                            <div v-if="canEditNotes || request.operator_notes" class="mt-6 pt-6 border-t border-gray-200">
                                <h3 class="flex items-center gap-2 text-sm font-medium text-gray-500 mb-2">
                                    <FileText class="h-4 w-4" />
                                    Anotaciones internas
                                </h3>

                                <div v-if="canEditNotes" class="space-y-3">
                                    <textarea
                                        v-model="notesDraft"
                                        rows="4"
                                        class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500"
                                        placeholder="Ej: IPS no responde, agenda llena, volver a llamar mañana..."
                                    />
                                    <div class="flex items-center justify-end gap-2">
                                        <button
                                            type="button"
                                            @click="saveNotes"
                                            :disabled="savingNotes"
                                            class="inline-flex items-center gap-2 px-4 py-2 bg-brand-500 text-white rounded-lg hover:bg-brand-600 transition-colors disabled:opacity-50"
                                        >
                                            {{ savingNotes ? 'Guardando…' : 'Guardar anotaciones' }}
                                        </button>
                                    </div>
                                </div>

                                <div v-else class="bg-gray-50 border border-gray-100 rounded-lg p-4">
                                    <p class="text-gray-700 whitespace-pre-line">{{ request.operator_notes }}</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Cita relacionada -->
                    <div v-if="request.appointment" class="bg-white rounded-xl shadow-sm border border-green-200 overflow-hidden">
                        <div class="px-6 py-4 border-b border-green-200 bg-green-50">
                            <h2 class="flex items-center gap-2 text-lg font-semibold text-green-800">
                                <CheckCircle class="h-5 w-5" />
                                Cita Obtenida
                            </h2>
                        </div>
                        <div class="p-6">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="font-semibold text-gray-900">
                                        {{ request.appointment.formatted_datetime || 'Fecha por definir' }}
                                    </p>
                                    <p class="text-sm text-gray-500">Cita #{{ request.appointment.id }}</p>
                                </div>
                                <Link :href="`/appointments/${request.appointment.id}`" class="inline-flex items-center gap-2 px-4 py-2 bg-green-100 text-green-700 rounded-lg hover:bg-green-200 transition-colors">
                                    Ver Cita
                                    <ArrowRight class="h-4 w-4" />
                                </Link>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Columna derecha -->
                <div class="space-y-6">
                    <!-- Paciente -->
                    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                        <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
                            <h2 class="flex items-center gap-2 text-lg font-semibold text-gray-900">
                                <User class="h-5 w-5 text-brand-600" />
                                Paciente
                            </h2>
                        </div>
                        <div class="p-6">
                            <div class="flex items-center gap-4 mb-4">
                                <div class="h-14 w-14 rounded-full bg-brand-100 flex items-center justify-center flex-shrink-0">
                                    <span class="text-brand-700 font-bold text-xl">{{ patient.first_name?.charAt(0) }}{{ patient.last_name?.charAt(0) }}</span>
                                </div>
                                <div class="min-w-0">
                                    <p class="text-lg font-semibold text-gray-900 truncate">{{ patient.full_name }}</p>
                                    <p class="text-sm text-gray-600">{{ patient.document_type_abbreviation }} {{ patient.document_number }}</p>
                                </div>
                            </div>

                            <dl class="space-y-3 text-sm">
                                <div v-if="patient.eps?.name">
                                    <dt class="flex items-center gap-2 text-gray-500">
                                        <Building2 class="h-4 w-4" />
                                        EPS
                                    </dt>
                                    <dd class="font-medium text-gray-900 ml-6">{{ patient.eps.name }}</dd>
                                </div>
                                <div v-if="patient.whatsapp_number || patient.phone">
                                    <dt class="flex items-center gap-2 text-gray-500">
                                        <Phone class="h-4 w-4" />
                                        Teléfono
                                    </dt>
                                    <dd class="font-medium text-gray-900 ml-6">{{ patient.whatsapp_number || patient.phone }}</dd>
                                </div>
                            </dl>

                            <div class="mt-4 pt-4 border-t border-gray-200">
                                <Link :href="`/patients/${patient.id}`" class="text-sm text-brand-600 hover:text-brand-700 font-medium">
                                    Ver perfil del paciente →
                                </Link>
                            </div>
                        </div>
                    </div>

                    <!-- Información -->
                    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                        <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
                            <h2 class="text-lg font-semibold text-gray-900">ℹ️ Información</h2>
                        </div>
                        <div class="p-6 space-y-3 text-sm">
                            <div v-if="request.creator?.name">
                                <p class="text-gray-500">Registrada por</p>
                                <p class="font-medium text-gray-900">{{ request.creator.name }}</p>
                            </div>
                            <div v-if="request.assignee?.name">
                                <p class="text-gray-500">Asignada a</p>
                                <p class="font-medium text-gray-900">{{ request.assignee.name }}</p>
                            </div>
                            <div>
                                <p class="text-gray-500">Fecha de registro</p>
                                <p class="font-medium text-gray-900">{{ request.created_at_formatted }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Acciones peligrosas -->
                    <div class="bg-white rounded-xl shadow-sm border border-red-200 overflow-hidden">
                        <div class="p-6">
                            <button @click="deleteRequest" class="w-full px-4 py-2 text-sm text-red-600 border border-red-300 rounded-lg hover:bg-red-50 transition-colors">
                                🗑️ Eliminar Solicitud
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal: Marcar como Fallida -->
        <div v-if="showFailedModal" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
            <div class="bg-white rounded-xl shadow-xl max-w-md w-full p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Marcar como No Obtenida</h3>
                <p class="text-gray-600 mb-4">Indique el motivo por el cual no se pudo obtener la cita:</p>
                <textarea 
                    v-model="failedReason" 
                    rows="3"
                    placeholder="Ej: No hay disponibilidad, el especialista no atiende..."
                    class="w-full rounded-lg border-gray-300 focus:border-brand-500 focus:ring-brand-500"
                ></textarea>
                <div class="flex justify-end gap-3 mt-4">
                    <button @click="showFailedModal = false" class="px-4 py-2 text-gray-700 hover:bg-gray-100 rounded-lg transition-colors">
                        Cancelar
                    </button>
                    <button @click="markAsFailed" class="px-4 py-2 bg-orange-500 text-white rounded-lg hover:bg-orange-600 transition-colors">
                        Confirmar
                    </button>
                </div>
            </div>
        </div>

        <!-- Modal: Cancelar -->
        <div v-if="showCancelModal" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
            <div class="bg-white rounded-xl shadow-xl max-w-md w-full p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Cancelar Solicitud</h3>
                <p class="text-gray-600 mb-4">Indique el motivo de la cancelación:</p>
                <textarea 
                    v-model="cancelReason" 
                    rows="3"
                    placeholder="Ej: El cliente ya no necesita la cita..."
                    class="w-full rounded-lg border-gray-300 focus:border-brand-500 focus:ring-brand-500"
                ></textarea>
                <div class="flex justify-end gap-3 mt-4">
                    <button @click="showCancelModal = false" class="px-4 py-2 text-gray-700 hover:bg-gray-100 rounded-lg transition-colors">
                        Volver
                    </button>
                    <button @click="cancelRequest" class="px-4 py-2 bg-gray-500 text-white rounded-lg hover:bg-gray-600 transition-colors">
                        Confirmar Cancelación
                    </button>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
