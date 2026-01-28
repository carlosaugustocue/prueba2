<script setup>
import { computed, onMounted } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
    appointment: Object,
    statuses: Array,
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
    if (confirm(`¿Cambiar estado a "${newStatus}"?`)) {
        router.patch(`/appointments/${apt.value.id}/status`, { status: newStatus });
    }
};

const sendConfirmation = () => {
    if (confirm('¿Enviar confirmación por WhatsApp al paciente?')) {
        router.post(`/appointments/${apt.value.id}/send-confirmation`);
    }
};

const deleteAppointment = () => {
    if (confirm('¿Está seguro de eliminar esta cita? Esta acción no se puede deshacer.')) {
        router.delete(`/appointments/${apt.value.id}`);
    }
};
</script>

<template>
    <AppLayout>
        <div class="max-w-5xl mx-auto space-y-6">
            <!-- Header -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <Link href="/appointments" class="inline-flex items-center text-sm text-gray-500 hover:text-gray-700 mb-2">
                        ← Volver a citas
                    </Link>
                    <h1 class="text-2xl font-bold text-gray-900">Cita #{{ apt.id }}</h1>
                    <p class="text-gray-500 mt-1">Creada el {{ apt.created_at_formatted }}</p>
                </div>
                <div class="flex flex-wrap gap-2">
                    <button 
                        v-if="apt.can_send_confirmation" 
                        @click="sendConfirmation"
                        class="inline-flex items-center px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors"
                    >
                        📱 Enviar WhatsApp
                    </button>
                    <Link :href="`/appointments/${apt.id}/edit`" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-colors">
                        ✏️ Editar
                    </Link>
                    <button @click="deleteAppointment" class="inline-flex items-center px-4 py-2 bg-white border border-red-300 text-red-600 rounded-lg hover:bg-red-50 transition-colors">
                        🗑️ Eliminar
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
                                    → {{ transition.label }}
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Datos de la cita -->
                    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                        <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
                            <h2 class="text-lg font-semibold text-gray-900">📅 Información de la Cita</h2>
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
                                <h3 class="text-sm font-medium text-gray-500 mb-2">📍 Ubicación</h3>
                                <p class="text-base text-gray-900 font-medium">{{ apt.location_name || 'Sin nombre' }}</p>
                                <p v-if="apt.location_address" class="text-gray-600">{{ apt.location_address }}</p>
                            </div>

                            <!-- Especificaciones -->
                            <div v-if="apt.specifications" class="mt-6 pt-6 border-t border-gray-200">
                                <h3 class="text-sm font-medium text-gray-500 mb-2">📋 Especificaciones para el paciente</h3>
                                <div class="bg-yellow-50 border border-yellow-100 rounded-lg p-4">
                                    <p class="text-gray-800 whitespace-pre-line">{{ apt.specifications }}</p>
                                </div>
                            </div>

                            <!-- Notas internas -->
                            <div v-if="apt.internal_notes" class="mt-6 pt-6 border-t border-gray-200">
                                <h3 class="text-sm font-medium text-gray-500 mb-2">🔒 Notas internas (no se envían al paciente)</h3>
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
                                <h2 class="text-lg font-semibold text-gray-900">📜 Timeline de la Cita</h2>
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
                                            item.action_color === 'green' ? 'bg-green-100 ring-2 ring-green-300' : '',
                                            item.action_color === 'blue' ? 'bg-blue-100 ring-2 ring-blue-300' : '',
                                            item.action_color === 'purple' ? 'bg-purple-100 ring-2 ring-purple-300' : '',
                                            item.action_color === 'emerald' ? 'bg-emerald-100 ring-2 ring-emerald-300' : '',
                                            item.action_color === 'amber' ? 'bg-amber-100 ring-2 ring-amber-300' : '',
                                            item.action_color === 'gray' || !item.action_color ? 'bg-gray-100 ring-2 ring-gray-300' : ''
                                        ]">
                                            {{ item.action_icon || 'ℹ️' }}
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
                                                            <span class="text-gray-400">→</span>
                                                            <span class="text-green-600 font-medium">{{ item.new_value }}</span>
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
                            <div class="text-4xl mb-2">📋</div>
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
                            <h2 class="text-lg font-semibold text-gray-900">👤 Paciente</h2>
                        </div>
                        <div class="p-6">
                            <div class="flex items-center space-x-4 mb-4">
                                <div class="h-14 w-14 rounded-full bg-green-100 flex items-center justify-center flex-shrink-0">
                                    <span class="text-green-700 font-bold text-xl">{{ patient.first_name?.charAt(0) || '?' }}{{ patient.last_name?.charAt(0) || '' }}</span>
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
                                <Link :href="`/patients/${patient.id}`" class="text-sm text-green-600 hover:text-green-700 font-medium">
                                    Ver perfil del paciente →
                                </Link>
                            </div>
                        </div>
                    </div>

                    <!-- Mensajes enviados -->
                    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                        <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
                            <h2 class="text-lg font-semibold text-gray-900">📱 Notificaciones</h2>
                        </div>
                        <div class="p-6 space-y-4">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center space-x-3">
                                    <span :class="apt.confirmation_sent_at ? 'text-green-500' : 'text-gray-300'" class="text-xl">✓</span>
                                    <div>
                                        <p class="text-sm font-medium text-gray-900">Confirmación</p>
                                        <p class="text-xs text-gray-500">{{ apt.confirmation_sent_at || 'No enviada' }}</p>
                                    </div>
                                </div>
                            </div>
                            <div class="flex items-center justify-between">
                                <div class="flex items-center space-x-3">
                                    <span :class="apt.reminder_sent_at ? 'text-green-500' : 'text-gray-300'" class="text-xl">✓</span>
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
                        <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
                            <h2 class="text-lg font-semibold text-gray-900">ℹ️ Información</h2>
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
