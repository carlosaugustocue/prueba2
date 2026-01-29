<script setup>
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import {
    ChevronLeft, CalendarPlus, Pencil, Phone, Mail, MapPin, 
    User, Users, Heart, UserPlus, Calendar, ArrowRight,
    MessageSquare, Building2
} from 'lucide-vue-next';

const props = defineProps({
    patient: Object,
});

// Los datos pueden venir envueltos en "data" por el Resource
const patient = computed(() => props.patient?.data || props.patient || {});
const isHolder = computed(() => patient.value.is_holder);
const isBeneficiary = computed(() => patient.value.is_beneficiary);
const beneficiaries = computed(() => patient.value.beneficiaries || []);
const holder = computed(() => patient.value.holder);
</script>

<template>
    <AppLayout>
        <div class="max-w-5xl mx-auto space-y-6">
            <!-- Header -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <Link href="/patients" class="inline-flex items-center gap-2 text-sm text-gray-500 hover:text-brand-600 transition-colors">
                        <ChevronLeft class="h-4 w-4" />
                        Volver a pacientes
                    </Link>
                    <h1 class="text-3xl font-bold text-gray-900 mt-2">{{ patient.full_name || 'Sin nombre' }}</h1>
                    <div class="flex items-center gap-2 mt-1">
                        <span :class="[
                            'inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-medium',
                            isHolder ? 'bg-brand-100 text-brand-700' : 'bg-purple-100 text-purple-700'
                        ]">
                            <User v-if="isHolder" class="h-3 w-3" />
                            <Heart v-else class="h-3 w-3" />
                            {{ patient.patient_type_label }}
                        </span>
                        <span class="text-gray-500">•</span>
                        <span class="text-gray-500 text-sm">{{ patient.eps?.name || 'Sin EPS' }}</span>
                    </div>
                </div>
                <div class="flex flex-wrap gap-3">
                    <Link :href="`/appointments/create?patient_id=${patient.id}`" class="inline-flex items-center gap-2 px-4 py-2 bg-brand-500 text-white rounded-xl hover:bg-brand-600 transition-colors">
                        <CalendarPlus class="h-5 w-5" />
                        Nueva Cita
                    </Link>
                    <Link :href="`/patients/${patient.id}/edit`" class="inline-flex items-center gap-2 px-4 py-2 bg-white border border-gray-300 text-gray-700 rounded-xl hover:bg-gray-50 transition-colors">
                        <Pencil class="h-4 w-4" />
                        Editar
                    </Link>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Columna principal -->
                <div class="lg:col-span-2 space-y-6">
                    <!-- Info principal -->
                    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                        <div class="flex items-start gap-6">
                            <div class="h-20 w-20 rounded-2xl bg-gradient-to-br from-brand-400 to-brand-600 flex items-center justify-center flex-shrink-0">
                                <span class="text-white font-bold text-3xl">{{ patient.first_name?.charAt(0) || '?' }}{{ patient.last_name?.charAt(0) || '' }}</span>
                            </div>
                            <div class="flex-1">
                                <h2 class="text-2xl font-bold text-gray-900">{{ patient.full_name || 'Sin nombre' }}</h2>
                                <p class="text-gray-600 mt-1">{{ patient.document_type_label || patient.document_type }} {{ patient.document_number }}</p>
                                <div class="flex flex-wrap gap-4 mt-4">
                                    <div v-if="patient.phone" class="flex items-center gap-2 text-gray-600">
                                        <Phone class="h-4 w-4 text-gray-400" />
                                        {{ patient.phone }}
                                    </div>
                                    <div v-if="patient.whatsapp" class="flex items-center gap-2 text-gray-600">
                                        <MessageSquare class="h-4 w-4 text-green-500" />
                                        {{ patient.whatsapp }}
                                    </div>
                                    <div v-if="patient.email" class="flex items-center gap-2 text-gray-600">
                                        <Mail class="h-4 w-4 text-gray-400" />
                                        {{ patient.email }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Cotizante Titular (si es beneficiario) -->
                    <div v-if="isBeneficiary && holder" class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                        <div class="px-6 py-4 border-b border-gray-200 bg-gradient-to-r from-purple-50 to-white">
                            <h3 class="flex items-center gap-2 font-semibold text-gray-900">
                                <User class="h-5 w-5 text-purple-600" />
                                Cotizante Titular
                            </h3>
                        </div>
                        <div class="p-6">
                            <!-- Tipo de parentesco -->
                            <div v-if="patient.relationship_type_label" class="mb-4 inline-flex items-center gap-2 px-3 py-1.5 bg-purple-100 text-purple-700 rounded-full text-sm font-medium">
                                <Heart class="h-4 w-4" />
                                {{ patient.relationship_type_label }}
                            </div>
                            
                            <Link :href="`/patients/${holder.id}`" class="flex items-center gap-4 p-4 rounded-xl bg-gray-50 hover:bg-brand-50 transition-colors group">
                                <div class="h-14 w-14 rounded-full bg-brand-100 flex items-center justify-center flex-shrink-0">
                                    <User class="h-7 w-7 text-brand-600" />
                                </div>
                                <div class="flex-1">
                                    <p class="font-semibold text-gray-900 group-hover:text-brand-700">{{ holder.full_name }}</p>
                                    <p class="text-sm text-gray-500">{{ holder.document_type_abbreviation }} {{ holder.document_number }}</p>
                                    <p v-if="holder.phone || holder.whatsapp" class="text-sm text-gray-500 mt-1">
                                        {{ holder.whatsapp || holder.phone }}
                                    </p>
                                </div>
                                <ArrowRight class="h-5 w-5 text-gray-400 group-hover:text-brand-500 transition-colors" />
                            </Link>
                        </div>
                    </div>

                    <!-- Beneficiarios (si es cotizante) -->
                    <div v-if="isHolder" class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                        <div class="px-6 py-4 border-b border-gray-200 bg-gradient-to-r from-brand-50 to-white flex items-center justify-between">
                            <h3 class="flex items-center gap-2 font-semibold text-gray-900">
                                <Users class="h-5 w-5 text-brand-600" />
                                Beneficiarios
                                <span v-if="beneficiaries.length" class="ml-2 px-2 py-0.5 bg-brand-100 text-brand-700 text-xs rounded-full">
                                    {{ beneficiaries.length }}
                                </span>
                            </h3>
                            <Link :href="`/patients/create?holder_id=${patient.id}`" class="inline-flex items-center gap-2 px-3 py-1.5 text-sm font-medium text-brand-600 hover:text-brand-700 hover:bg-brand-50 rounded-lg transition-colors">
                                <UserPlus class="h-4 w-4" />
                                Agregar Beneficiario
                            </Link>
                        </div>
                        <div class="divide-y divide-gray-100">
                            <Link 
                                v-for="beneficiary in beneficiaries" 
                                :key="beneficiary.id" 
                                :href="`/patients/${beneficiary.id}`"
                                class="flex items-center gap-4 px-6 py-4 hover:bg-gray-50 transition-colors group"
                            >
                                <div class="h-12 w-12 rounded-full bg-purple-100 flex items-center justify-center flex-shrink-0">
                                    <Heart class="h-6 w-6 text-purple-600" />
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p class="font-medium text-gray-900 group-hover:text-brand-700">{{ beneficiary.full_name }}</p>
                                    <p class="text-sm text-gray-500">{{ beneficiary.document_type_abbreviation }} {{ beneficiary.document_number }}</p>
                                    <span v-if="beneficiary.relationship_type_short" class="inline-flex items-center mt-1 px-2 py-0.5 bg-purple-50 text-purple-600 rounded text-xs font-medium">
                                        {{ beneficiary.relationship_type_short }}
                                    </span>
                                </div>
                                <div class="text-right text-sm text-gray-500">
                                    <p v-if="beneficiary.whatsapp || beneficiary.phone">{{ beneficiary.whatsapp || beneficiary.phone }}</p>
                                </div>
                                <ArrowRight class="h-5 w-5 text-gray-300 group-hover:text-brand-500 transition-colors" />
                            </Link>
                            
                            <!-- Estado vacío -->
                            <div v-if="!beneficiaries.length" class="px-6 py-12 text-center">
                                <Users class="h-12 w-12 mx-auto text-gray-300 mb-3" />
                                <p class="text-gray-500 mb-4">No hay beneficiarios registrados</p>
                                <Link :href="`/patients/create?holder_id=${patient.id}`" class="inline-flex items-center gap-2 px-4 py-2 bg-brand-500 text-white rounded-lg hover:bg-brand-600 transition-colors">
                                    <UserPlus class="h-5 w-5" />
                                    Agregar Primer Beneficiario
                                </Link>
                            </div>
                        </div>
                    </div>

                    <!-- Historial de Citas -->
                    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                        <div class="px-6 py-4 border-b border-gray-200 bg-gradient-to-r from-gray-50 to-white flex items-center justify-between">
                            <h3 class="flex items-center gap-2 font-semibold text-gray-900">
                                <Calendar class="h-5 w-5 text-brand-600" />
                                Historial de Citas
                            </h3>
                            <Link :href="`/appointments?patient_id=${patient.id}`" class="text-sm text-brand-600 hover:text-brand-700 font-medium">
                                Ver todas →
                            </Link>
                        </div>
                        <div class="divide-y divide-gray-100">
                            <Link 
                                v-for="apt in patient.appointments" 
                                :key="apt.id" 
                                :href="`/appointments/${apt.id}`" 
                                class="flex items-center justify-between px-6 py-4 hover:bg-gray-50 transition-colors"
                            >
                                <div class="flex items-center gap-3">
                                    <span class="text-xs font-mono text-gray-400 bg-gray-100 px-2 py-1 rounded">#{{ apt.id }}</span>
                                    <div>
                                        <p class="font-medium text-gray-900">{{ apt.type_label }}</p>
                                        <p class="text-sm text-gray-500">{{ apt.formatted_datetime || 'Sin fecha' }}</p>
                                    </div>
                                </div>
                                <span :class="[apt.status_badge_class, 'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium']">
                                    {{ apt.status_label }}
                                </span>
                            </Link>
                            
                            <div v-if="!patient.appointments?.length" class="px-6 py-12 text-center">
                                <Calendar class="h-12 w-12 mx-auto text-gray-300 mb-3" />
                                <p class="text-gray-500">No hay citas registradas</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Columna lateral -->
                <div class="space-y-6">
                    <!-- Información de contacto -->
                    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                        <div class="px-6 py-4 border-b border-gray-200 bg-gradient-to-r from-gray-50 to-white">
                            <h3 class="flex items-center gap-2 font-semibold text-gray-900">
                                <Phone class="h-5 w-5 text-brand-600" />
                                Contacto
                            </h3>
                        </div>
                        <div class="p-6 space-y-4">
                            <div>
                                <p class="text-xs text-gray-500 uppercase tracking-wide mb-1">Teléfono</p>
                                <p class="font-medium text-gray-900">{{ patient.phone || '-' }}</p>
                            </div>
                            <div>
                                <p class="text-xs text-gray-500 uppercase tracking-wide mb-1">WhatsApp</p>
                                <p class="font-medium text-gray-900">{{ patient.whatsapp || patient.whatsapp_number || '-' }}</p>
                            </div>
                            <div>
                                <p class="text-xs text-gray-500 uppercase tracking-wide mb-1">Email</p>
                                <p class="font-medium text-gray-900 break-all">{{ patient.email || '-' }}</p>
                            </div>
                            <div>
                                <p class="text-xs text-gray-500 uppercase tracking-wide mb-1">Dirección</p>
                                <p class="font-medium text-gray-900">{{ patient.address || '-' }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Información EPS -->
                    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                        <div class="px-6 py-4 border-b border-gray-200 bg-gradient-to-r from-gray-50 to-white">
                            <h3 class="flex items-center gap-2 font-semibold text-gray-900">
                                <Building2 class="h-5 w-5 text-brand-600" />
                                EPS
                            </h3>
                        </div>
                        <div class="p-6">
                            <p class="font-semibold text-gray-900">{{ patient.eps?.name || 'Sin EPS' }}</p>
                            <p v-if="patient.eps?.code" class="text-sm text-gray-500">Código: {{ patient.eps.code }}</p>
                        </div>
                    </div>

                    <!-- Notas -->
                    <div v-if="patient.notes" class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                        <div class="px-6 py-4 border-b border-gray-200 bg-gradient-to-r from-gray-50 to-white">
                            <h3 class="font-semibold text-gray-900">Notas</h3>
                        </div>
                        <div class="p-6">
                            <p class="text-gray-700 whitespace-pre-line">{{ patient.notes }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
