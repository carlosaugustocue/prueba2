<script setup>
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
    stats: Object,
    todayAppointments: Object,
});

const statCards = computed(() => [
    { name: 'Citas Hoy', value: props.stats?.today || 0, icon: '📅', color: 'bg-blue-50 text-blue-600' },
    { name: 'Pendientes', value: props.stats?.pending || 0, icon: '⏰', color: 'bg-yellow-50 text-yellow-600' },
    { name: 'Urgentes', value: props.stats?.urgent || 0, icon: '🚨', color: 'bg-red-50 text-red-600' },
    { name: 'Por Confirmar', value: props.stats?.to_confirm || 0, icon: '✅', color: 'bg-purple-50 text-purple-600' },
]);

const appointments = computed(() => props.todayAppointments?.data || []);
</script>

<template>
    <AppLayout>
        <div class="space-y-6">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">Dashboard</h1>
                    <p class="mt-1 text-sm text-gray-500">Resumen de la Central de Citas</p>
                </div>
                <Link href="/appointments/create" class="inline-flex items-center justify-center px-4 py-2 font-medium rounded-lg bg-green-600 text-white hover:bg-green-700 transition-colors">
                    ➕ Nueva Cita
                </Link>
            </div>

            <!-- Stats -->
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <div v-for="stat in statCards" :key="stat.name" class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                    <div class="p-5">
                        <div class="flex items-center">
                            <div :class="[stat.color, 'rounded-lg p-3 text-2xl']">{{ stat.icon }}</div>
                            <div class="ml-4">
                                <p class="text-sm font-medium text-gray-500">{{ stat.name }}</p>
                                <p class="text-2xl font-bold text-gray-900">{{ stat.value }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Today's appointments -->
                <div class="lg:col-span-2 bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
                        <h2 class="text-lg font-semibold text-gray-900">Citas de Hoy</h2>
                        <Link href="/appointments?today=1" class="text-sm text-green-600 hover:text-green-700">Ver todas →</Link>
                    </div>
                    <div class="divide-y divide-gray-100">
                        <div v-for="appointment in appointments" :key="appointment.id" class="p-4 hover:bg-gray-50">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center space-x-4">
                                    <div class="w-12 h-12 rounded-lg bg-green-100 flex items-center justify-center">
                                        <span class="text-green-700 font-medium text-sm">{{ appointment.appointment_time || '--:--' }}</span>
                                    </div>
                                    <div>
                                        <p class="text-sm font-medium text-gray-900">{{ appointment.patient?.full_name }}</p>
                                        <p class="text-sm text-gray-500">{{ appointment.type_label }} • {{ appointment.patient?.eps?.name }}</p>
                                    </div>
                                </div>
                                <span :class="[appointment.status_badge_class, 'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium']">{{ appointment.status_label }}</span>
                            </div>
                        </div>
                        <div v-if="appointments.length === 0" class="p-8 text-center">
                            <p class="text-gray-500">📅 No hay citas programadas para hoy</p>
                        </div>
                    </div>
                </div>

                <!-- Quick actions -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h2 class="text-lg font-semibold text-gray-900">Acciones Rápidas</h2>
                    </div>
                    <div class="p-4 space-y-3">
                        <Link href="/appointments/create" class="flex items-center p-3 rounded-lg bg-green-50 text-green-700 hover:bg-green-100">
                            ➕ <span class="ml-3 font-medium">Nueva Cita</span>
                        </Link>
                        <Link href="/patients/create" class="flex items-center p-3 rounded-lg bg-blue-50 text-blue-700 hover:bg-blue-100">
                            👤 <span class="ml-3 font-medium">Nuevo Paciente</span>
                        </Link>
                        <Link href="/appointments?status=pending" class="flex items-center p-3 rounded-lg bg-yellow-50 text-yellow-700 hover:bg-yellow-100">
                            ⏰ <span class="ml-3 font-medium">Ver Pendientes</span>
                        </Link>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
