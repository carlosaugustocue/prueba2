<script setup>
import { computed } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { CalendarDays, Clock, AlertTriangle, CheckCircle, Plus, UserPlus, CalendarCheck, ArrowRight, ClipboardList, Play, Users, Shield, FileCheck, Send, CalendarClock, AlertCircle } from 'lucide-vue-next';

const page = usePage();
const userRole = computed(() => page.props.auth?.user?.role ?? '');

const props = defineProps({
    stats: Object,
    todayAppointments: Object,
    inProgressRequests: Object,
});

const isSeguridadSocialOnly = computed(() => userRole.value === 'seguridad_social');

const statCards = computed(() => [
    { name: 'Citas Hoy', value: props.stats?.today || 0, icon: CalendarDays, color: 'bg-blue-50 text-blue-600', link: '/appointments?today=1' },
    { name: 'Solicitudes Pendientes', value: props.stats?.pending_requests || 0, icon: Clock, color: 'bg-yellow-50 text-yellow-600', link: '/appointment-requests?status=pending' },
    { name: 'Solicitudes en Progreso', value: props.stats?.in_progress_requests || 0, icon: Play, color: 'bg-indigo-50 text-indigo-600', link: '/appointment-requests?status=in_progress' },
    { name: 'Solicitudes Urgentes', value: props.stats?.urgent_requests || 0, icon: AlertTriangle, color: 'bg-red-50 text-red-600', link: '/appointment-requests?priority=urgent' },
    { name: 'Citas Confirmadas', value: props.stats?.confirmed || 0, icon: CheckCircle, color: 'bg-green-50 text-green-600', link: '/appointments?status=confirmed' },
]);

// RF-AUT-16: tarjetas de autorizaciones
const authorizationStatCards = computed(() => [
    { name: 'Pendientes radicación', value: props.stats?.authorizations_pending_radication || 0, icon: FileCheck, color: 'bg-amber-50 text-amber-600', link: '/authorizations?status=pending_radication' },
    { name: 'Radicadas', value: props.stats?.authorizations_radicated || 0, icon: Send, color: 'bg-blue-50 text-blue-600', link: '/authorizations?status=radicated' },
    { name: 'Aprobadas sin cita', value: props.stats?.authorizations_approved_without_appointment || 0, icon: CalendarCheck, color: 'bg-green-50 text-green-600', link: '/authorizations?status=approved&without_appointment=1' },
    { name: 'Próximas a vencer', value: props.stats?.authorizations_expiring_soon || 0, icon: AlertCircle, color: 'bg-orange-50 text-orange-600', link: '/authorizations?expiring_soon=1' },
]);

const appointments = computed(() => props.todayAppointments?.data || []);
const inProgressRequests = computed(() => props.inProgressRequests?.data || []);
</script>

<template>
    <AppLayout>
        <div class="space-y-6">
            <!-- Dashboard Seguridad Social (solo afiliados) -->
            <template v-if="isSeguridadSocialOnly">
                <div class="flex items-center justify-between">
                    <div>
                        <h1 class="text-2xl font-bold text-gray-900">Seguridad Social</h1>
                        <p class="mt-1 text-sm text-gray-500">Gestión de afiliados y datos de seguridad social</p>
                    </div>
                </div>
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    <Link href="/affiliates" class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden hover:shadow-md transition-shadow group p-6 flex items-center gap-4">
                        <div class="rounded-lg p-3 bg-brand-50 text-brand-600">
                            <Users class="h-8 w-8" />
                        </div>
                        <div>
                            <p class="font-semibold text-gray-900">Afiliados</p>
                            <p class="text-sm text-gray-500">Consultar y gestionar afiliados, cotizantes y beneficiarios</p>
                        </div>
                    </Link>
                    <Link href="/affiliates/create" class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden hover:shadow-md transition-shadow group p-6 flex items-center gap-4">
                        <div class="rounded-lg p-3 bg-green-50 text-green-600">
                            <UserPlus class="h-8 w-8" />
                        </div>
                        <div>
                            <p class="font-semibold text-gray-900">Nuevo Afiliado</p>
                            <p class="text-sm text-gray-500">Registrar cotizante o beneficiario</p>
                        </div>
                    </Link>
                </div>
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                    <div class="flex items-start gap-3">
                        <Shield class="h-6 w-6 text-gray-400 flex-shrink-0 mt-0.5" />
                        <div>
                            <h2 class="font-semibold text-gray-900">Módulo de Seguridad Social</h2>
                            <p class="text-sm text-gray-500 mt-1">Desde aquí puede administrar afiliados, perfiles de seguridad social (EPS, AFP, ARP, CCF) y datos necesarios para el proceso de afiliación.</p>
                        </div>
                    </div>
                </div>
            </template>

            <!-- Dashboard Citas (atención, supervisores, admin) -->
            <template v-else>
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">Dashboard</h1>
                    <p class="mt-1 text-sm text-gray-500">Resumen operativo de la central de citas</p>
                </div>
                <Link href="/appointment-requests/create" class="inline-flex items-center justify-center gap-2 px-4 py-2 font-medium rounded-lg bg-brand-500 text-white hover:bg-brand-600 transition-colors">
                    <ClipboardList class="h-5 w-5" />
                    Nueva Solicitud
                </Link>
            </div>

            <!-- Bloque de indicadores principales (citas + autorizaciones) -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
                    <div>
                        <h2 class="text-base font-semibold text-gray-900">Indicadores principales</h2>
                        <p class="text-xs text-gray-500 mt-0.5">Visión rápida de carga de trabajo y estado de autorizaciones</p>
                    </div>
                    <Link href="/authorizations" class="hidden sm:inline-flex items-center gap-1 text-xs font-medium text-brand-600 hover:text-brand-700">
                        Ver autorizaciones <ArrowRight class="h-4 w-4" />
                    </Link>
                </div>
                <div class="px-6 py-5 space-y-6">
                    <!-- Métricas de solicitudes y citas -->
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                        <Link
                            v-for="stat in statCards"
                            :key="stat.name"
                            :href="stat.link"
                            class="rounded-xl border border-gray-100 bg-gray-50/60 hover:bg-white hover:shadow-sm transition-all group"
                        >
                            <div class="p-4 flex items-center">
                                <div :class="[stat.color, 'rounded-lg p-3 ring-1 ring-inset ring-white/60']">
                                    <component :is="stat.icon" class="h-5 w-5" />
                                </div>
                                <div class="ml-4">
                                    <p class="text-xs font-medium text-gray-500 group-hover:text-gray-700">{{ stat.name }}</p>
                                    <p class="text-xl font-semibold text-gray-900">{{ stat.value }}</p>
                                </div>
                            </div>
                        </Link>
                    </div>

                    <!-- Métricas de autorizaciones -->
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                        <Link
                            v-for="stat in authorizationStatCards"
                            :key="stat.name"
                            :href="stat.link"
                            class="rounded-xl border border-gray-100 bg-gray-50/60 hover:bg-white hover:shadow-sm transition-all group"
                        >
                            <div class="p-4 flex items-center">
                                <div :class="[stat.color, 'rounded-lg p-3 ring-1 ring-inset ring-white/60']">
                                    <component :is="stat.icon" class="h-5 w-5" />
                                </div>
                                <div class="ml-4">
                                    <p class="text-xs font-medium text-gray-500 group-hover:text-gray-700">{{ stat.name }}</p>
                                    <p class="text-xl font-semibold text-gray-900">{{ stat.value }}</p>
                                </div>
                            </div>
                        </Link>
                    </div>

                    <div class="flex items-center justify-between pt-1 text-xs text-gray-500">
                        <p>Haz clic en cualquier tarjeta para ir directamente al listado filtrado.</p>
                        <Link href="/authorizations" class="inline-flex items-center gap-1 text-brand-600 hover:text-brand-700 sm:hidden">
                            Ver autorizaciones <ArrowRight class="h-3 w-3" />
                        </Link>
                    </div>
                </div>
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
                        <Link
                            v-for="appointment in appointments"
                            :key="appointment.id"
                            :href="`/appointments/${appointment.id}`"
                            class="block p-4 hover:bg-gray-50 transition-colors focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-500 focus-visible:ring-offset-2"
                            :aria-label="`Ver cita #${appointment.id}`"
                        >
                            <div class="flex items-center justify-between">
                                <div class="flex items-center space-x-4">
                                    <div class="w-12 h-12 rounded-lg bg-brand-100 flex items-center justify-center">
                                        <span class="text-brand-700 font-medium text-sm">{{ appointment.appointment_time || '--:--' }}</span>
                                    </div>
                                    <div>
                                        <p class="text-sm font-medium text-gray-900">{{ appointment.affiliate?.full_name }}</p>
                                        <p class="text-sm text-gray-500">{{ appointment.type_label }} • {{ appointment.affiliate?.eps?.name }}</p>
                                    </div>
                                </div>
                                <span :class="[appointment.status_badge_class, 'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium']">{{ appointment.status_label }}</span>
                            </div>
                        </Link>
                        <div v-if="appointments.length === 0" class="p-8 text-center">
                            <CalendarCheck class="h-12 w-12 mx-auto text-gray-300 mb-3" />
                            <p class="text-gray-500">No hay citas programadas para hoy</p>
                        </div>
                    </div>
                </div>

                <div class="space-y-6">
                    <!-- In-progress requests -->
                    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                        <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
                            <h2 class="text-lg font-semibold text-gray-900">Solicitudes en Progreso</h2>
                            <Link href="/appointment-requests?status=in_progress" class="text-sm text-brand-600 hover:text-brand-700 inline-flex items-center gap-1">
                                Ver todas <ArrowRight class="h-4 w-4" />
                            </Link>
                        </div>
                        <div class="divide-y divide-gray-100">
                            <Link
                                v-for="r in inProgressRequests"
                                :key="r.id"
                                :href="`/appointment-requests/${r.id}`"
                                class="block p-4 hover:bg-gray-50 transition-colors focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-500 focus-visible:ring-offset-2"
                                :aria-label="`Ver solicitud #${r.id}`"
                            >
                                <div class="flex items-center justify-between gap-4">
                                    <div class="min-w-0">
                                        <p class="text-sm font-medium text-gray-900 truncate">
                                            Solicitud #{{ r.id }} • {{ r.affiliate?.full_name }}
                                        </p>
                                        <p class="text-sm text-gray-500 truncate">
                                            {{ r.type_label }} • {{ r.affiliate?.eps?.name || 'Sin EPS' }}
                                        </p>
                                    </div>
                                    <span :class="[r.priority_badge_class, 'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium whitespace-nowrap']">
                                        {{ r.priority_label }}
                                    </span>
                                </div>
                            </Link>
                            <div v-if="inProgressRequests.length === 0" class="p-6 text-center">
                                <Clock class="h-10 w-10 mx-auto text-gray-300 mb-2" />
                                <p class="text-gray-500 text-sm">No hay solicitudes en progreso</p>
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
                            <Link href="/affiliates/create" class="flex items-center p-3 rounded-lg bg-accent-500/10 text-brand-700 hover:bg-accent-500/20 transition-colors">
                                <UserPlus class="h-5 w-5" />
                                <span class="ml-3 font-medium">Nuevo Afiliado</span>
                            </Link>
                            <Link href="/appointments" class="flex items-center p-3 rounded-lg bg-green-50 text-green-700 hover:bg-green-100 transition-colors">
                                <CalendarCheck class="h-5 w-5" />
                                <span class="ml-3 font-medium">Ver Citas Registradas</span>
                            </Link>
                        </div>
                    </div>
                </div>
            </div>
            </template>
        </div>
    </AppLayout>
</template>
