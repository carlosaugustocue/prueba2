<script setup>
import { ref, computed } from 'vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { confirmDialog, toast } from '@/Utils/swal';
import axios from 'axios';
import {
    ChevronLeft, Clock, User, Calendar, Play, XCircle, AlertTriangle,
    CheckCircle, Phone, Mail, Building2, FileText, MessageSquare, ArrowRight,
    Loader2, ClipboardList, Info, Trash2, FileCheck, Unlink
} from 'lucide-vue-next';

const page = usePage();
const isAdmin = computed(() => page.props.auth?.user?.role === 'admin');
const isAgent = computed(() => page.props.auth?.user?.role === 'agent');
const isSupervisor = computed(() => page.props.auth?.user?.role === 'supervisor');

const props = defineProps({
    appointmentRequest: Object,
    statuses: Array,
    types: Array,
    priorities: Array,
});

const currentUserId = computed(() => page.props.auth?.user?.id);

const request = computed(() => props.appointmentRequest?.data || props.appointmentRequest || {});
const affiliate = computed(() => request.value.affiliate || {});
const notes = computed(() => request.value.notes || []);

const newNoteDraft = ref('');
const savingNotes = ref(false);
const canEditNotes = computed(() => {
    const userId = page.props.auth?.user?.id;
    if (!userId) return false;
    if (isAdmin.value) return true;
    if (!request.value?.is_active) return false;
    // Agentes y supervisores pueden agregar anotaciones (aunque la solicitud esté asignada a otra persona)
    if (isAgent.value || isSupervisor.value) return true;
    // Otros roles: solo el asignado puede editar
    if (request.value?.assignee?.id && request.value.assignee.id !== userId) return false;
    return true;
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
    const note = (newNoteDraft.value || '').trim();
    if (!note) return;
    savingNotes.value = true;
    router.post(`/appointment-requests/${request.value.id}/notes`, {
        note,
    }, {
        preserveScroll: true,
        onSuccess: () => {
            toast({ title: 'Anotaciones guardadas.' });
            newNoteDraft.value = '';
            // Asegurar que se refresquen las props (autor/fecha) después de guardar
            router.reload({ only: ['appointmentRequest'], preserveScroll: true });
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

const statusIcon = (status) => {
    const map = {
        pending: Clock,
        in_progress: Loader2,
        pending_authorization: FileCheck,
        completed: CheckCircle,
        cancelled: XCircle,
        failed: AlertTriangle,
    };
    return map[status] || Clock;
};

// Crear cita: permitido si está en trámite y (no requiere autorización o autorización aprobada) y aún no tiene cita
const canCreateAppointment = computed(() => {
    if (request.value.appointment) return false;
    if (request.value.status !== 'in_progress') return false;
    if (request.value.requires_authorization) {
        return request.value.authorization?.is_approved === true;
    }
    return true;
});

// Mensaje cuando requiere autorización pero no está aprobada
const authorizationBlockMessage = computed(() => {
    if (!request.value.requires_authorization || request.value.appointment) return null;
    if (request.value.authorization?.is_approved) return null;
    if (!request.value.authorization) return 'Esta solicitud requiere autorización EPS. Cree la autorización y cuando esté aprobada podrá crear la cita.';
    return `Autorización en estado: ${request.value.authorization?.status_label || '—'}. Cuando la EPS la apruebe podrá crear la cita.`;
});

// Modal: usar autorización existente del afiliado
const showExistingAuthModal = ref(false);
const existingAuthorizations = ref([]);
const loadingExistingAuth = ref(false);
const attachingAuthId = ref(null);

const openExistingAuthModal = async () => {
    showExistingAuthModal.value = true;
    existingAuthorizations.value = [];
    loadingExistingAuth.value = true;
    try {
        const res = await axios.get(`/api/affiliates/${request.value.affiliate_id}/authorizations`, {
            params: { for_request_id: request.value.id },
        });
        const data = res.data?.data ?? res.data;
        existingAuthorizations.value = Array.isArray(data) ? data : (data?.data ?? []);
    } catch (e) {
        console.error(e);
        toast({ title: 'No se pudieron cargar las autorizaciones.', icon: 'error' });
    } finally {
        loadingExistingAuth.value = false;
    }
};

const attachAuthorization = (authorizationId) => {
    attachingAuthId.value = authorizationId;
    router.post(`/appointment-requests/${request.value.id}/attach-authorization`, {
        authorization_id: authorizationId,
    }, {
        preserveScroll: true,
        onSuccess: () => {
            showExistingAuthModal.value = false;
            toast({ title: 'Autorización vinculada. Ya puede crear la cita.' });
        },
        onError: () => {
            toast({ title: 'No se pudo vincular la autorización.', icon: 'error' });
        },
        onFinish: () => {
            attachingAuthId.value = null;
        },
    });
};

const detachAuthorizationFromRequest = () => {
    confirmDialog({
        title: 'Desvincular autorización',
        text: 'La autorización dejará de estar vinculada a esta solicitud y la solicitud volverá a "Pendiente de autorización". Podrá volver a elegir otra autorización o crear una nueva. ¿Continuar?',
        confirmButtonText: 'Desvincular',
    }).then((ok) => {
        if (!ok) return;
        router.post(`/authorizations/${request.value.authorization.id}/detach-request`, {}, {
            preserveScroll: true,
            onSuccess: () => toast({ title: 'Autorización desvinculada.' }),
            onError: () => toast({ title: 'No se pudo desvincular.', icon: 'error' }),
        });
    });
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
                        v-if="canCreateAppointment"
                        :href="`/appointment-requests/${request.id}/create-appointment`"
                        class="inline-flex items-center gap-2 px-4 py-2 bg-brand-500 text-white rounded-lg hover:bg-brand-600 transition-colors"
                    >
                        <Calendar class="h-5 w-5" />
                        Crear Cita
                    </Link>
                    <span
                        v-else-if="authorizationBlockMessage"
                        class="inline-flex items-center gap-2 px-4 py-2 bg-amber-50 text-amber-800 border border-amber-200 rounded-lg text-sm"
                        :title="authorizationBlockMessage"
                    >
                        <FileCheck class="h-5 w-5 flex-shrink-0" />
                        <span class="max-w-xs truncate">{{ authorizationBlockMessage }}</span>
                    </span>

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
                        v-if="request.appointment"
                        :href="`/appointments/${request.appointment.id}`"
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
                                    <component
                                        :is="statusIcon(request.status)"
                                        :class="[
                                            'h-5 w-5',
                                            request.status === 'in_progress' ? 'animate-spin' : ''
                                        ]"
                                    />
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
                            <h3 class="flex items-center gap-2 text-sm font-semibold text-gray-900 mb-4">
                                <Clock class="h-4 w-4 text-brand-600" />
                                Tiempos del Trámite
                            </h3>
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
                        <div class="px-6 py-4 border-b border-gray-200 bg-gray-50 flex items-center gap-2">
                            <ClipboardList class="h-5 w-5 text-brand-600" />
                            <h2 class="text-lg font-semibold text-gray-900">Detalles de la Solicitud</h2>
                        </div>
                        <div class="p-6">
                            <dl class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">Tipo de Cita</dt>
                                    <dd class="mt-1 text-base text-gray-900">{{ request.type_label }}</dd>
                                </div>
                                <div v-if="request.type === 'specialist'">
                                    <dt class="text-sm font-medium text-gray-500">Especialidad</dt>
                                    <dd class="mt-1 text-base text-gray-900">{{ request.specialty || 'No indicada' }}</dd>
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
                            <div v-if="canEditNotes || notes.length || request.operator_notes" class="mt-6 pt-6 border-t border-gray-200">
                                <h3 class="flex items-center gap-2 text-sm font-medium text-gray-500 mb-2">
                                    <FileText class="h-4 w-4" />
                                    Anotaciones internas
                                </h3>

                                <div class="space-y-3">
                                    <div v-if="notes.length" class="space-y-3">
                                        <div
                                            v-for="n in notes"
                                            :key="n.id"
                                            class="bg-gray-50 border border-gray-100 rounded-lg p-4"
                                        >
                                            <div class="flex items-start justify-between gap-3">
                                                <p class="text-sm font-semibold text-gray-900">
                                                    <span class="text-gray-500 font-medium">Autor:</span>
                                                    {{ n.author?.name || (n.user_id ? `Usuario #${n.user_id}` : 'Sistema') }}
                                                    <span
                                                        v-if="n.author?.id && currentUserId && n.author.id === currentUserId"
                                                        class="ml-2 inline-flex items-center rounded-full bg-brand-50 px-2 py-0.5 text-xs font-medium text-brand-700"
                                                    >
                                                        Tú
                                                    </span>
                                                </p>
                                                <p class="text-xs text-gray-500 whitespace-nowrap">
                                                    {{ n.created_at_formatted || n.created_at || '' }}
                                                </p>
                                            </div>
                                            <p class="mt-2 text-gray-700 whitespace-pre-line">{{ n.note }}</p>
                                        </div>
                                    </div>

                                    <!-- Fallback por si aún no hay backfill en BD -->
                                    <div v-else-if="request.operator_notes" class="bg-gray-50 border border-gray-100 rounded-lg p-4">
                                        <p class="text-gray-700 whitespace-pre-line">{{ request.operator_notes }}</p>
                                    </div>

                                    <div v-if="canEditNotes" class="pt-2">
                                        <textarea
                                            v-model="newNoteDraft"
                                            rows="3"
                                            class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500"
                                            placeholder="Agregar nueva anotación… (Ej: IPS no responde, agenda llena, volver a llamar mañana)"
                                        />
                                        <div class="flex items-center justify-end gap-2 mt-3">
                                            <button
                                                type="button"
                                                @click="saveNotes"
                                                :disabled="savingNotes || !newNoteDraft.trim()"
                                                class="inline-flex items-center gap-2 px-4 py-2 bg-brand-500 text-white rounded-lg hover:bg-brand-600 transition-colors disabled:opacity-50"
                                            >
                                                {{ savingNotes ? 'Guardando…' : 'Agregar anotación' }}
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Autorización EPS (cuando la solicitud requiere autorización) -->
                    <div v-if="request.requires_authorization" class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                        <div class="px-6 py-4 border-b border-gray-200 bg-gray-50 flex items-center justify-between flex-wrap gap-2">
                            <h2 class="flex items-center gap-2 text-lg font-semibold text-gray-900">
                                <FileCheck class="h-5 w-5 text-brand-600" />
                                Autorización EPS
                            </h2>
                            <div v-if="!request.authorization" class="flex flex-wrap gap-2">
                                <Link
                                    :href="`/authorizations/create?appointment_request_id=${request.id}&affiliate_id=${request.affiliate_id}`"
                                    class="inline-flex items-center gap-2 px-4 py-2 bg-brand-500 text-white rounded-lg hover:bg-brand-600 text-sm font-medium"
                                >
                                    <FileCheck class="h-4 w-4" />
                                    Crear autorización
                                </Link>
                                <button
                                    type="button"
                                    @click="openExistingAuthModal"
                                    class="inline-flex items-center gap-2 px-4 py-2 bg-white border border-brand-300 text-brand-700 rounded-lg hover:bg-brand-50 text-sm font-medium"
                                >
                                    <ClipboardList class="h-4 w-4" />
                                    Usar autorización existente
                                </button>
                            </div>
                            <div v-else class="flex flex-wrap gap-2">
                                <Link
                                    :href="`/authorizations/${request.authorization.id}`"
                                    class="inline-flex items-center gap-2 px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 text-sm font-medium"
                                >
                                    Ver autorización
                                    <ArrowRight class="h-4 w-4" />
                                </Link>
                                <button
                                    v-if="!request.appointment"
                                    type="button"
                                    @click="detachAuthorizationFromRequest"
                                    class="inline-flex items-center gap-2 px-4 py-2 bg-amber-50 border border-amber-300 text-amber-800 rounded-lg hover:bg-amber-100 text-sm font-medium"
                                >
                                    <Unlink class="h-4 w-4" />
                                    Desvincular autorización
                                </button>
                            </div>
                        </div>
                        <div class="p-6">
                            <template v-if="request.authorization">
                                <div class="flex flex-wrap items-center gap-3">
                                    <span :class="[request.authorization.status_badge_class, 'inline-flex items-center px-3 py-1 rounded-full text-sm font-medium']">
                                        {{ request.authorization.status_label }}
                                    </span>
                                    <span v-if="request.authorization.authorization_number" class="font-mono text-gray-900 font-medium">
                                        N.º autorización: {{ request.authorization.authorization_number }}
                                    </span>
                                    <span v-if="request.authorization.radicado_number" class="text-sm text-gray-500">
                                        Radicado: {{ request.authorization.radicado_number }}
                                    </span>
                                    <span v-if="request.authorization.valid_until_formatted" class="text-sm text-gray-500">
                                        Vigente hasta {{ request.authorization.valid_until_formatted }}
                                    </span>
                                </div>
                                <p v-if="request.authorization.is_approved" class="mt-2 text-sm text-green-700 font-medium">
                                    Autorización aprobada. Puede crear la cita con el botón «Crear Cita» arriba.
                                </p>
                            </template>
                            <p v-else class="text-gray-500 text-sm">
                                Esta solicitud requiere autorización ante la EPS. Cree una nueva o use una autorización ya aprobada del afiliado (botón «Usar autorización existente»).
                            </p>
                        </div>
                    </div>

                    <!-- Modal: Usar autorización existente -->
                    <Teleport to="body">
                        <div v-if="showExistingAuthModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50" @click.self="showExistingAuthModal = false">
                            <div class="bg-white rounded-xl shadow-xl max-w-2xl w-full max-h-[85vh] flex flex-col" @click.stop>
                                <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
                                    <h3 class="text-lg font-semibold text-gray-900">Usar autorización existente</h3>
                                    <button type="button" class="p-2 text-gray-400 hover:text-gray-600 rounded-lg" @click="showExistingAuthModal = false" aria-label="Cerrar">
                                        <XCircle class="h-5 w-5" />
                                    </button>
                                </div>
                                <div class="p-6 overflow-y-auto flex-1">
                                    <p class="text-sm text-gray-500 mb-4">
                                        Autorizaciones aprobadas y vigentes del afiliado. Elija una para vincularla a esta solicitud.
                                    </p>
                                    <div v-if="loadingExistingAuth" class="flex items-center justify-center py-12">
                                        <Loader2 class="h-8 w-8 text-brand-500 animate-spin" />
                                    </div>
                                    <div v-else-if="existingAuthorizations.length === 0" class="text-center py-8 text-gray-500">
                                        No hay autorizaciones aprobadas y vigentes para este afiliado. Cree una nueva desde «Crear autorización».
                                    </div>
                                    <ul v-else class="space-y-3">
                                        <li
                                            v-for="a in existingAuthorizations"
                                            :key="a.id"
                                            class="flex flex-wrap items-center justify-between gap-3 p-4 rounded-xl border border-gray-200 hover:border-brand-200 bg-gray-50/50"
                                        >
                                            <div class="min-w-0 flex-1">
                                                <p class="font-medium text-gray-900">
                                                    {{ a.authorization_number || a.radicado_number || `Autorización #${a.id}` }}
                                                </p>
                                                <p class="text-sm text-gray-600 mt-0.5">{{ a.service_type }}</p>
                                                <p class="text-xs text-gray-500">
                                                    {{ a.eps?.name || '—' }}
                                                    <span v-if="a.valid_until_formatted"> · Vigente hasta {{ a.valid_until_formatted }}</span>
                                                </p>
                                            </div>
                                            <button
                                                type="button"
                                                :disabled="attachingAuthId !== null"
                                                @click="attachAuthorization(a.id)"
                                                class="inline-flex items-center gap-2 px-4 py-2 bg-brand-500 text-white rounded-lg hover:bg-brand-600 text-sm font-medium disabled:opacity-50"
                                            >
                                                <Loader2 v-if="attachingAuthId === a.id" class="h-4 w-4 animate-spin" />
                                                <CheckCircle v-else class="h-4 w-4" />
                                                Vincular a esta solicitud
                                            </button>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </Teleport>

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
                    <!-- Afiliado -->
                    <div v-if="request.affiliate && (request.affiliate.id || request.affiliate.full_name)" class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                        <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
                            <h2 class="flex items-center gap-2 text-lg font-semibold text-gray-900">
                                <User class="h-5 w-5 text-brand-600" />
                                Afiliado
                            </h2>
                        </div>
                        <div class="p-6">
                            <div class="flex items-center gap-4 mb-4">
                                <div class="h-14 w-14 rounded-full bg-brand-100 flex items-center justify-center flex-shrink-0">
                                    <span class="text-brand-700 font-bold text-xl">{{ affiliate?.first_name?.charAt(0) }}{{ affiliate?.last_name?.charAt(0) }}</span>
                                </div>
                                <div class="min-w-0 flex-1">
                                    <p class="text-lg font-semibold text-gray-900 break-words">{{ affiliate?.full_name }}</p>
                                    <p class="text-sm text-gray-600">{{ affiliate?.document_type_abbreviation }} {{ affiliate?.document_number }}</p>
                                </div>
                            </div>

                            <dl class="space-y-3 text-sm">
                                <div v-if="affiliate?.eps?.name">
                                    <dt class="flex items-center gap-2 text-gray-500">
                                        <Building2 class="h-4 w-4" />
                                        EPS
                                    </dt>
                                    <dd class="font-medium text-gray-900 ml-6">{{ affiliate.eps.name }}</dd>
                                </div>
                                <div v-if="affiliate?.whatsapp_number || affiliate?.phone">
                                    <dt class="flex items-center gap-2 text-gray-500">
                                        <Phone class="h-4 w-4" />
                                        Teléfono
                                    </dt>
                                    <dd class="font-medium text-gray-900 ml-6">{{ affiliate?.whatsapp_number || affiliate?.phone }}</dd>
                                </div>
                            </dl>

                            <div v-if="affiliate?.id" class="mt-4 pt-4 border-t border-gray-200">
                                <Link :href="`/affiliates/${affiliate.id}`" class="inline-flex items-center gap-2 text-sm text-brand-600 hover:text-brand-700 font-medium">
                                    Ver perfil del afiliado
                                    <ArrowRight class="h-4 w-4" />
                                </Link>
                            </div>
                        </div>
                    </div>

                    <!-- Información -->
                    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                        <div class="px-6 py-4 border-b border-gray-200 bg-gray-50 flex items-center gap-2">
                            <Info class="h-5 w-5 text-brand-600" />
                            <h2 class="text-lg font-semibold text-gray-900">Información</h2>
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
                                <span class="inline-flex items-center justify-center gap-2">
                                    <Trash2 class="h-4 w-4" />
                                    Eliminar Solicitud
                                </span>
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
