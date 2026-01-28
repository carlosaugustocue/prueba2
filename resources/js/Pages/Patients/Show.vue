<script setup>
import { computed, onMounted } from 'vue';
import { Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
    patient: Object,
});

// Los datos pueden venir envueltos en "data" por el Resource
const patient = computed(() => props.patient?.data || props.patient || {});

onMounted(() => {
    console.log('=== Patient Show DEBUG ===');
    console.log('Props patient:', props.patient);
    console.log('Patient data:', patient.value);
});
</script>

<template>
    <AppLayout>
        <div class="max-w-4xl mx-auto space-y-6">
            <div class="flex items-center justify-between">
                <div>
                    <Link href="/patients" class="text-sm text-gray-500 hover:text-gray-700">← Volver a pacientes</Link>
                    <h1 class="text-2xl font-bold text-gray-900 mt-2">{{ patient.full_name || 'Sin nombre' }}</h1>
                </div>
                <div class="flex space-x-3">
                    <Link href="/appointments/create" class="inline-flex items-center px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700">
                        📅 Nueva Cita
                    </Link>
                    <Link :href="`/patients/${patient.id}/edit`" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50">
                        ✏️ Editar
                    </Link>
                </div>
            </div>

            <!-- Info principal -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                <div class="flex items-center space-x-6">
                    <div class="h-20 w-20 rounded-full bg-green-100 flex items-center justify-center">
                        <span class="text-green-700 font-bold text-3xl">{{ patient.first_name?.charAt(0) || '?' }}{{ patient.last_name?.charAt(0) || '' }}</span>
                    </div>
                    <div>
                        <h2 class="text-xl font-bold text-gray-900">{{ patient.full_name || 'Sin nombre' }}</h2>
                        <p class="text-gray-600">{{ patient.document_type_label || patient.document_type }} - {{ patient.document_number }}</p>
                        <p class="text-gray-500">{{ patient.eps?.name || 'Sin EPS' }} • {{ patient.patient_type_label || patient.patient_type }}</p>
                    </div>
                </div>
            </div>

            <!-- Contacto -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
                    <h3 class="font-semibold">📞 Información de Contacto</h3>
                </div>
                <div class="p-6 grid grid-cols-2 gap-4">
                    <div>
                        <p class="text-sm text-gray-500">Teléfono</p>
                        <p class="font-medium">{{ patient.phone || '-' }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500">WhatsApp</p>
                        <p class="font-medium">{{ patient.whatsapp || patient.whatsapp_number || '-' }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500">Email</p>
                        <p class="font-medium">{{ patient.email || '-' }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500">Dirección</p>
                        <p class="font-medium">{{ patient.address || '-' }}</p>
                    </div>
                </div>
            </div>

            <!-- Citas del paciente -->
            <div v-if="patient.appointments?.length" class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
                    <h3 class="font-semibold">📅 Historial de Citas</h3>
                </div>
                <div class="divide-y divide-gray-100">
                    <Link v-for="apt in patient.appointments" :key="apt.id" :href="`/appointments/${apt.id}`" class="block px-6 py-4 hover:bg-gray-50">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="font-medium text-gray-900">{{ apt.type_label }}</p>
                                <p class="text-sm text-gray-500">{{ apt.formatted_datetime || 'Sin fecha' }}</p>
                            </div>
                            <span :class="[apt.status_badge_class, 'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium']">{{ apt.status_label }}</span>
                        </div>
                    </Link>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
