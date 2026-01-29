<script setup>
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { CalendarDays, Clock, AlertTriangle, CheckCircle, Plus, UserPlus, CalendarCheck, ArrowRight, ClipboardList } from 'lucide-vue-next';

const props = defineProps({
    stats: Object,
    todayAppointments: Object,
});

const statCards = computed(() => [
    { name: 'Citas Hoy', value: props.stats?.today || 0, icon: CalendarDays, color: 'bg-blue-50 text-blue-600', link: '/appointments?today=1' },
    { name: 'Solicitudes Pendientes', value: props.stats?.pending_requests || 0, icon: Clock, color: 'bg-yellow-50 text-yellow-600', link: '/appointment-requests?status=pending' },
    { name: 'Solicitudes Urgentes', value: props.stats?.urgent_requests || 0, icon: AlertTriangle, color: 'bg-red-50 text-red-600', link: '/appointment-requests?priority=urgent' },
    { name: 'Citas Confirmadas', value: props.stats?.confirmed || 0, icon: CheckCircle, color: 'bg-green-50 text-green-600', link: '/appointments?status=confirmed' },
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
                <Link href="/appointment-requests/create" class="inline-flex items-center justify-center gap-2 px-4 py-2 font-medium rounded-lg bg-brand-500 text-white hover:bg-brand-600 transition-colors">
                    <ClipboardList class="h-5 w-5" />
                    Nueva Solicitud
                </Link>
            </div>

            <!-- Stats -->
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <Link v-for="stat in statCards" :key="stat.name" :href="stat.link" class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden hover:shadow-md transition-shadow group">
                    <div class="p-5">
                        <div class="flex items-center">
                            <div :class="[stat.color, 'rounded-lg p-3']">
                                <component :is="stat.icon" class="h-6 w-6" />
                            </div>
                            <div class="ml-4">
                                <p class="text-sm font-medium text-gray-500 group-hover:text-gray-700">{{ stat.name }}</p>
                                <p class="text-2xl font-bold text-gray-900">{{ stat.value }}</p>
                            </div>
                        </div>
                    </div>
                </Link>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Today's appointments -->
                <div class="lg:col-span-2 bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
                        <h2 class="text-lg font-semibold text-gray-900">Citas de Hoy</h2>
                        <Link href="/appointments?today=1" class="text-sm text-brand-600 hover:text-brand-700 inline-flex items-center gap-1">
                            Ver todas <ArrowRight class="h-4 w-4" />
                        </Link>
                    </div>
                    <div class="divide-y divide-gray-100">
                        <div v-for="appointment in appointments" :key="appointment.id" class="p-4 hover:bg-gray-50">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center space-x-4">
                                    <div class="w-12 h-12 rounded-lg bg-brand-100 flex items-center justify-center">
                                        <span class="text-brand-700 font-medium text-sm">{{ appointment.appointment_time || '--:--' }}</span>
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
                            <CalendarCheck class="h-12 w-12 mx-auto text-gray-300 mb-3" />
                            <p class="text-gray-500">No hay citas programadas para hoy</p>
                        </div>
                    </div>
                </div>

                <!-- Quick actions -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h2 class="text-lg font-semibold text-gray-900">Acciones Rápidas</h2>
                    </div>
                    <div class="p-4 space-y-3">
                        <Link href="/appointment-requests/create" class="flex items-center p-3 rounded-lg bg-amber-50 text-amber-700 hover:bg-amber-100 transition-colors">
                            <ClipboardList class="h-5 w-5" />
                            <span class="ml-3 font-medium">Nueva Solicitud</span>
                        </Link>
                        <Link href="/appointment-requests" class="flex items-center p-3 rounded-lg bg-yellow-50 text-yellow-700 hover:bg-yellow-100 transition-colors">
                            <Clock class="h-5 w-5" />
                            <span class="ml-3 font-medium">Ver Solicitudes Pendientes</span>
                        </Link>
                        <Link href="/patients/create" class="flex items-center p-3 rounded-lg bg-accent-500/10 text-brand-700 hover:bg-accent-500/20 transition-colors">
                            <UserPlus class="h-5 w-5" />
                            <span class="ml-3 font-medium">Nuevo Paciente</span>
                        </Link>
                        <Link href="/appointments" class="flex items-center p-3 rounded-lg bg-green-50 text-green-700 hover:bg-green-100 transition-colors">
                            <CalendarCheck class="h-5 w-5" />
                            <span class="ml-3 font-medium">Ver Citas Registradas</span>
                        </Link>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
