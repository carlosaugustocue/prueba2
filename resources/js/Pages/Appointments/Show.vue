<script setup>
import { computed, onMounted, ref } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { ArrowLeft, MessageCircle, RotateCcw, Loader2, Pencil, Trash2, PhoneCall, CalendarDays, MapPin, ClipboardList, Lock, ScrollText, Info, ArrowRight, User, Check } from 'lucide-vue-next';
import { alertDialog, confirmDialog } from '@/Utils/swal';

const props = defineProps({
    appointment: Object,
    statuses: Array,
    phoneCategories: Array,
});

// Los datos pueden venir envueltos en "data" por el Resource
const apt = computed(() => props.appointment?.data || props.appointment || {});
const patient = computed(() => apt.value.patient || {});

onMounted(() => {
    console.log('=== Show.vue DEBUG ===');
    console.log('Props appointment:', props.appointment);
    console.log('Appointment data (apt):', apt.value);
    console.log('Patient:', patient.value);
});

const changeStatus = (newStatus) => {
    confirmDialog({
        title: 'Cambiar estado',
        text: `¿Cambiar estado a "${newStatus}"?`,
        confirmButtonText: 'Cambiar',
    }).then((ok) => {
        if (!ok) return;
        router.patch(`/appointments/${apt.value.id}/status`, { status: newStatus });
    });
};

const sendConfirmation = () => {
    confirmDialog({
        title: 'Enviar WhatsApp',
        text: '¿Enviar confirmación por WhatsApp al paciente?',
        confirmButtonText: 'Enviar',
    }).then((ok) => {
        if (!ok) return;
        router.post(`/appointments/${apt.value.id}/send-confirmation`);
    });
};

const deleteAppointment = () => {
    confirmDialog({
        title: 'Eliminar cita',
        text: '¿Está seguro de eliminar esta cita? Esta acción no se puede deshacer.',
        icon: 'warning',
        confirmButtonText: 'Eliminar',
    }).then((ok) => {
        if (!ok) return;
        router.delete(`/appointments/${apt.value.id}`);
    });
};

const whatsappStatus = computed(() => apt.value.whatsapp_confirmation_status || 'not_sent');
const whatsappStatusLabel = computed(() => {
    if (whatsappStatus.value === 'sent') return 'Enviado';
    if (whatsappStatus.value === 'pending') return 'Pendiente';
    if (whatsappStatus.value === 'failed') return 'Error';
    return 'No enviado';
});
const whatsappStatusClass = computed(() => {
    if (whatsappStatus.value === 'sent') return 'bg-green-100 text-green-800';
    if (whatsappStatus.value === 'pending') return 'bg-yellow-100 text-yellow-800';
    if (whatsappStatus.value === 'failed') return 'bg-red-100 text-red-800';
    return 'bg-gray-100 text-gray-800';
});

const phoneCategory = ref('');
const phoneNote = ref('');
const phoneCategoryLabel = (value) => {
    const found = props.phoneCategories?.find(c => c.value === value);
    return found?.label || value;
};
const savePhoneCommunication = () => {
    if (!phoneCategory.value) {
        alertDialog({ title: 'Categoría requerida', text: 'Seleccione una categoría.' });
        return;
    }
    router.post(`/appointments/${apt.value.id}/communications/phone`, {
        category: phoneCategory.value,
        note: phoneNote.value || null,
    }, {
        preserveScroll: true,
        onSuccess: () => {
            phoneNote.value = '';
        }
    });
};
</script>

<template>
    <AppLayout>
        <div class="max-w-5xl mx-auto space-y-6">
            <!-- Header -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <Link href="/appointments" class="inline-flex items-center gap-2 text-sm text-gray-500 hover:text-brand-700 mb-2">
                        <ArrowLeft class="h-4 w-4" />
                        Volver a citas
                    </Link>
                    <h1 class="text-2xl font-bold text-gray-900">Cita #{{ apt.id }}</h1>
                    <p class="text-gray-500 mt-1">Creada el {{ apt.created_at_formatted }}</p>
                </div>
                <div class="flex flex-wrap gap-2">
                    <div class="inline-flex items-center gap-2 px-3 py-2 bg-white border border-gray-200 rounded-lg">
                        <span class="text-sm text-gray-600">WhatsApp:</span>
                        <span :class="[whatsappStatusClass, 'text-xs font-semibold px-2 py-0.5 rounded-full']">
                            {{ whatsappStatusLabel }}
                        </span>
                    </div>
                    <button 
                        v-if="apt.can_send_confirmation" 
                        @click="sendConfirmation"
                        class="inline-flex items-center px-4 py-2 bg-brand-500 text-white rounded-lg hover:bg-brand-600 transition-colors"
                    >
                        <MessageCircle class="h-5 w-5 mr-2" />
                        Enviar WhatsApp
                    </button>
                    <button
                        v-else-if="whatsappStatus === 'failed' && apt.status === 'confirmed'"
                        @click="sendConfirmation"
                        class="inline-flex items-center px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors"
                    >
                        <RotateCcw class="h-5 w-5 mr-2" />
                        Reintentar WhatsApp
                    </button>
                    <button
                        v-else-if="whatsappStatus === 'pending'"
                        disabled
                        class="inline-flex items-center px-4 py-2 bg-gray-200 text-gray-600 rounded-lg cursor-not-allowed"
                    >
                        <Loader2 class="h-5 w-5 mr-2 animate-spin" />
                        Enviando...
                    </button>
                    <Link :href="`/appointments/${apt.id}/edit`" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-colors">
                        <Pencil class="h-5 w-5 mr-2 text-brand-700" />
                        Editar
                    </Link>
                    <button @click="deleteAppointment" class="inline-flex items-center px-4 py-2 bg-white border border-red-300 text-red-600 rounded-lg hover:bg-red-50 transition-colors">
                        <Trash2 class="h-5 w-5 mr-2" />
                        Eliminar
                    </button>
                </div>
            </div>

            <!-- Grid principal -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Columna izquierda (2/3) -->
                <div class="lg:col-span-2 space-y-6">
                    <!-- Estado y acciones rápidas -->
                    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                            <div>
                                <p class="text-sm font-medium text-gray-500 mb-2">Estado actual</p>
                                <span :class="[apt.status_badge_class, 'inline-flex items-center px-4 py-2 rounded-full text-base font-semibold']">
                                    {{ apt.status_label || 'Sin estado' }}
                                </span>
                            </div>
                            <div>
                                <p class="text-sm font-medium text-gray-500 mb-2">Prioridad</p>
                                <span :class="[apt.priority_badge_class, 'inline-flex items-center px-3 py-1 rounded-full text-sm font-medium']">
                                    {{ apt.priority_label || 'Sin prioridad' }}
                                </span>
                            </div>
                        </div>
                        
                        <!-- Transiciones de estado -->
                        <div v-if="apt.allowed_status_transitions?.length" class="mt-4 pt-4 border-t border-gray-200">
                            <p class="text-sm font-medium text-gray-500 mb-3">Cambiar estado a:</p>
                            <div class="flex flex-wrap gap-2">
                                <button
                                    v-for="transition in apt.allowed_status_transitions" 
                                    :key="transition.value"
                                    @click="changeStatus(transition.value)"
                                    class="px-4 py-2 text-sm font-medium border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors"
                                >
                                    {{ transition.label }}
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Comunicación telefónica -->
                    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                        <div class="px-6 py-4 border-b border-gray-200 bg-gray-50 flex items-center gap-2">
                            <PhoneCall class="h-5 w-5 text-brand-600" />
                            <h2 class="text-lg font-semibold text-gray-900">Comunicación telefónica</h2>
                        </div>
                        <div class="p-6 space-y-4">
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Categoría *</label>
                                    <select v-model="phoneCategory" class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500">
                                        <option value="">Seleccione...</option>
                                        <option v-for="c in phoneCategories" :key="c.value" :value="c.value">{{ c.label }}</option>
                                    </select>
                                </div>
                                <div class="sm:col-span-2">
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Nota (opcional)</label>
                                    <textarea v-model="phoneNote" rows="3" class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500" placeholder="Ej: No contesta, volver a llamar en la tarde..." />
                                </div>
                            </div>
                            <div class="flex justify-end">
                                <button @click="savePhoneCommunication" type="button" class="inline-flex items-center px-4 py-2 bg-gray-900 text-white rounded-lg hover:bg-gray-800 transition-colors">
                                    Registrar llamada
                                </button>
                            </div>

                            <div v-if="apt.communications?.length" class="pt-4 border-t border-gray-200">
                                <p class="text-sm font-medium text-gray-500 mb-3">Historial</p>
                                <div class="space-y-2">
                                    <div v-for="c in apt.communications" :key="c.id" class="bg-gray-50 border border-gray-200 rounded-lg p-3 text-sm">
                                        <div class="flex items-center justify-between gap-3">
                                            <p class="font-medium text-gray-900">
                                                {{ c.created_at_formatted }} • {{ c.user }}
                                            </p>
                                            <span class="text-xs bg-white border border-gray-200 rounded-full px-2 py-0.5 text-gray-700">
                                                {{ phoneCategoryLabel(c.category) }}
                                            </span>
                                        </div>
                                        <p v-if="c.note" class="text-gray-700 mt-1 whitespace-pre-line">{{ c.note }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Datos de la cita -->
                    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                        <div class="px-6 py-4 border-b border-gray-200 bg-gray-50 flex items-center gap-2">
                            <CalendarDays class="h-5 w-5 text-brand-600" />
                            <h2 class="text-lg font-semibold text-gray-900">Información de la Cita</h2>
                        </div>
                        <div class="p-6">
                            <dl class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-4">
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">Tipo de cita</dt>
                                    <dd class="mt-1 text-base text-gray-900">{{ apt.type_label || '-' }}</dd>
                                </div>
                                <div v-if="apt.specialty">
                                    <dt class="text-sm font-medium text-gray-500">Especialidad</dt>
                                    <dd class="mt-1 text-base text-gray-900">{{ apt.specialty }}</dd>
                                </div>
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">Fecha</dt>
                                    <dd class="mt-1 text-base text-gray-900 font-semibold">{{ apt.appointment_date_formatted || 'Por definir' }}</dd>
                                </div>
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">Hora</dt>
                                    <dd class="mt-1 text-base text-gray-900 font-semibold">{{ apt.appointment_time || 'Por definir' }}</dd>
                                </div>
                                <div v-if="apt.doctor_name">
                                    <dt class="text-sm font-medium text-gray-500">Doctor / Profesional</dt>
                                    <dd class="mt-1 text-base text-gray-900">{{ apt.doctor_name }}</dd>
                                </div>
                                <div v-if="apt.authorization_number">
                                    <dt class="text-sm font-medium text-gray-500">Número de autorización</dt>
                                    <dd class="mt-1 text-base text-gray-900 font-mono">{{ apt.authorization_number }}</dd>
                                </div>
                            </dl>
                            
                            <!-- Ubicación -->
                            <div v-if="apt.location_name || apt.location_address" class="mt-6 pt-6 border-t border-gray-200">
                                <h3 class="flex items-center gap-2 text-sm font-medium text-gray-500 mb-2">
                                    <MapPin class="h-4 w-4" />
                                    Ubicación
                                </h3>
                                <p class="text-base text-gray-900 font-medium">{{ apt.location_name || 'Sin nombre' }}</p>
                                <p v-if="apt.location_address" class="text-gray-600">{{ apt.location_address }}</p>
                            </div>

                            <!-- Especificaciones -->
                            <div v-if="apt.specifications" class="mt-6 pt-6 border-t border-gray-200">
                                <h3 class="flex items-center gap-2 text-sm font-medium text-gray-500 mb-2">
                                    <ClipboardList class="h-4 w-4" />
                                    Especificaciones para el paciente
                                </h3>
                                <div class="bg-yellow-50 border border-yellow-100 rounded-lg p-4">
                                    <p class="text-gray-800 whitespace-pre-line">{{ apt.specifications }}</p>
                                </div>
                            </div>

                            <!-- Notas internas -->
                            <div v-if="apt.internal_notes" class="mt-6 pt-6 border-t border-gray-200">
                                <h3 class="flex items-center gap-2 text-sm font-medium text-gray-500 mb-2">
                                    <Lock class="h-4 w-4" />
                                    Notas internas (no se envían al paciente)
                                </h3>
                                <div class="bg-gray-50 border border-gray-100 rounded-lg p-4">
                                    <p class="text-gray-700 whitespace-pre-line">{{ apt.internal_notes }}</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Timeline / Historial -->
                    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                        <div class="px-6 py-4 border-b border-gray-200 bg-gradient-to-r from-gray-50 to-gray-100">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-2">
                                    <ScrollText class="h-5 w-5 text-brand-600" />
                                    <h2 class="text-lg font-semibold text-gray-900">Timeline de la Cita</h2>
                                </div>
                                <span class="text-xs text-gray-500 bg-white px-2 py-1 rounded-full">
                                    {{ apt.history?.length || 0 }} eventos
                                </span>
                            </div>
                        </div>
                        
                        <div v-if="apt.history?.length" class="p-6">
                            <div class="relative">
                                <!-- Línea vertical del timeline -->
                                <div class="absolute left-4 top-0 bottom-0 w-0.5 bg-gradient-to-b from-green-300 via-blue-300 to-gray-200"></div>
                                
                                <!-- Eventos del timeline -->
                                <div class="space-y-6">
                                    <div v-for="(item, index) in apt.history" :key="item.id" class="relative pl-10">
                                        <!-- Punto del timeline con icono -->
                                        <div :class="[
                                            'absolute left-0 w-8 h-8 rounded-full flex items-center justify-center text-sm',
                                            item.action_color === 'green' ? 'bg-brand-100 ring-2 ring-green-300' : '',
                                            item.action_color === 'blue' ? 'bg-blue-100 ring-2 ring-blue-300' : '',
                                            item.action_color === 'purple' ? 'bg-purple-100 ring-2 ring-purple-300' : '',
                                            item.action_color === 'emerald' ? 'bg-emerald-100 ring-2 ring-emerald-300' : '',
                                            item.action_color === 'amber' ? 'bg-amber-100 ring-2 ring-amber-300' : '',
                                            item.action_color === 'gray' || !item.action_color ? 'bg-gray-100 ring-2 ring-gray-300' : ''
                                        ]">
                                            <Info class="h-5 w-5 text-gray-600" />
                                        </div>
                                        
                                        <!-- Contenido del evento -->
                                        <div :class="[
                                            'rounded-lg border p-4 hover:shadow-md transition-shadow',
                                            index === 0 ? 'bg-gradient-to-r from-green-50 to-white border-green-200' : 'bg-white border-gray-200'
                                        ]">
                                            <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-2">
                                                <div class="flex-1">
                                                    <p class="font-medium text-gray-900">{{ item.description }}</p>
                                                    
                                                    <!-- Detalle del cambio si aplica -->
                                                    <div v-if="item.old_value && item.new_value && item.action === 'updated'" class="mt-2 text-xs">
                                                        <div class="inline-flex items-center gap-2 bg-gray-50 rounded px-2 py-1">
                                                            <span class="text-red-600 line-through">{{ item.old_value }}</span>
                                                            <ArrowRight class="h-4 w-4 text-gray-400" />
                                                            <span class="text-brand-600 font-medium">{{ item.new_value }}</span>
                                                        </div>
                                                    </div>
                                                    
                                                    <!-- Usuario y tiempo relativo -->
                                                    <div class="mt-2 flex items-center gap-3 text-xs text-gray-500">
                                                        <span class="flex items-center gap-1">
                                                            <span class="w-5 h-5 rounded-full bg-gray-200 flex items-center justify-center text-[10px] font-bold">
                                                                {{ item.user?.charAt(0) || '?' }}
                                                            </span>
                                                            {{ item.user }}
                                                        </span>
                                                        <span>•</span>
                                                        <span>{{ item.created_at_relative }}</span>
                                                    </div>
                                                </div>
                                                
                                                <!-- Fecha/hora exacta -->
                                                <div class="text-right">
                                                    <p class="text-xs font-mono text-gray-400">{{ item.created_at }}</p>
                                                    <p v-if="item.ip_address" class="text-[10px] text-gray-300 mt-1">IP: {{ item.ip_address }}</p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Estado vacío -->
                        <div v-else class="p-8 text-center">
                            <div class="mx-auto h-12 w-12 rounded-xl bg-brand-50 flex items-center justify-center mb-3">
                                <ScrollText class="h-6 w-6 text-brand-600" />
                            </div>
                            <p class="text-gray-500">No hay eventos registrados aún</p>
                            <p class="text-xs text-gray-400 mt-1">Los cambios en la cita se registrarán aquí</p>
                        </div>
                    </div>
                </div>

                <!-- Columna derecha (1/3) -->
                <div class="space-y-6">
                    <!-- Paciente -->
                    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                        <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
                            <div class="flex items-center gap-2">
                                <User class="h-5 w-5 text-brand-600" />
                                <h2 class="text-lg font-semibold text-gray-900">Paciente</h2>
                            </div>
                        </div>
                        <div class="p-6">
                            <div class="flex items-center space-x-4 mb-4">
                                <div class="h-14 w-14 rounded-full bg-brand-100 flex items-center justify-center flex-shrink-0">
                                    <span class="text-brand-700 font-bold text-xl">{{ patient.first_name?.charAt(0) || '?' }}{{ patient.last_name?.charAt(0) || '' }}</span>
                                </div>
                                <div class="min-w-0">
                                    <p class="text-lg font-semibold text-gray-900 truncate">{{ patient.full_name || 'Sin nombre' }}</p>
                                    <p class="text-sm text-gray-600">{{ patient.document_type_abbreviation }} {{ patient.document_number }}</p>
                                </div>
                            </div>
                            
                            <dl class="space-y-3 text-sm">
                                <div v-if="patient.eps?.name">
                                    <dt class="text-gray-500">EPS</dt>
                                    <dd class="font-medium text-gray-900">{{ patient.eps.name }}</dd>
                                </div>
                                <div v-if="patient.whatsapp_number || patient.phone">
                                    <dt class="text-gray-500">Teléfono / WhatsApp</dt>
                                    <dd class="font-medium text-gray-900">{{ patient.whatsapp_number || patient.phone }}</dd>
                                </div>
                                <div v-if="patient.email">
                                    <dt class="text-gray-500">Email</dt>
                                    <dd class="font-medium text-gray-900 break-all">{{ patient.email }}</dd>
                                </div>
                            </dl>

                            <div class="mt-4 pt-4 border-t border-gray-200">
                                <Link :href="`/patients/${patient.id}`" class="inline-flex items-center gap-2 text-sm text-brand-600 hover:text-brand-700 font-medium">
                                    Ver perfil del paciente
                                    <ArrowRight class="h-4 w-4" />
                                </Link>
                            </div>
                        </div>
                    </div>

                    <!-- Mensajes enviados -->
                    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                        <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
                            <div class="flex items-center gap-2">
                                <MessageCircle class="h-5 w-5 text-brand-600" />
                                <h2 class="text-lg font-semibold text-gray-900">Notificaciones</h2>
                            </div>
                        </div>
                        <div class="p-6 space-y-4">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center space-x-3">
                                    <Check :class="apt.confirmation_sent_at ? 'text-green-600' : 'text-gray-300'" class="h-5 w-5" />
                                    <div>
                                        <p class="text-sm font-medium text-gray-900">Confirmación</p>
                                        <p class="text-xs text-gray-500">{{ apt.confirmation_sent_at || 'No enviada' }}</p>
                                    </div>
                                </div>
                            </div>
                            <div class="flex items-center justify-between">
                                <div class="flex items-center space-x-3">
                                    <Check :class="apt.reminder_sent_at ? 'text-green-600' : 'text-gray-300'" class="h-5 w-5" />
                                    <div>
                                        <p class="text-sm font-medium text-gray-900">Recordatorio 24h</p>
                                        <p class="text-xs text-gray-500">{{ apt.reminder_sent_at || 'No enviado' }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Información adicional -->
                    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                        <div class="px-6 py-4 border-b border-gray-200 bg-gray-50 flex items-center gap-2">
                            <Info class="h-5 w-5 text-brand-600" />
                            <h2 class="text-lg font-semibold text-gray-900">Información</h2>
                        </div>
                        <div class="p-6 space-y-3 text-sm">
                            <div v-if="apt.creator?.name">
                                <p class="text-gray-500">Creada por</p>
                                <p class="font-medium text-gray-900">{{ apt.creator.name }}</p>
                            </div>
                            <div v-if="apt.assignee?.name">
                                <p class="text-gray-500">Asignada a</p>
                                <p class="font-medium text-gray-900">{{ apt.assignee.name }}</p>
                            </div>
                            <div>
                                <p class="text-gray-500">Fecha de creación</p>
                                <p class="font-medium text-gray-900">{{ apt.created_at_formatted }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
