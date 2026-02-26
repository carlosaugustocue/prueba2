<script setup>
import { ref, computed, watch } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import { LayoutDashboard, CalendarDays, Users, LogOut, Menu, CheckCircle, XCircle, ClipboardList, BarChart3, MessageSquareText, Send, X, UserCog, Settings, Building2, FileCheck, FileText, ChevronDown, ChevronRight, Shield, Briefcase, Sliders } from 'lucide-vue-next';

const page = usePage();
const user = computed(() => page.props.auth?.user);
const flash = computed(() => page.props.flash);
const currentPath = computed(() => window.location.pathname);

const sidebarOpen = ref(false);

const ROLES_SOLO_SS = ['seguridad_social'];

/** Secciones del menú. Cada una tiene key, title, icon, items[] y opcionalmente roles (solo se muestra si el usuario tiene uno de esos roles). */
const sections = computed(() => {
    const role = user.value?.role;
    const list = [];

    list.push({
        key: 'principal',
        title: 'Principal',
        icon: LayoutDashboard,
        items: [{ name: 'Dashboard', href: '/dashboard', icon: LayoutDashboard }],
    });

    if (!ROLES_SOLO_SS.includes(role)) {
        list.push({
            key: 'atencion',
            title: 'Atención',
            icon: Briefcase,
            items: [
                { name: 'Solicitudes', href: '/appointment-requests', icon: ClipboardList },
                { name: 'Citas', href: '/appointments', icon: CalendarDays },
                { name: 'Autorizaciones', href: '/authorizations', icon: FileCheck },
            ],
        });
    }

    list.push({
        key: 'seguridad_social',
        title: 'Seguridad Social',
        icon: Shield,
        items: [
            { name: 'Dashboard SS', href: '/dashboard-ss', icon: LayoutDashboard },
            { name: 'Afiliados', href: '/affiliates', icon: Users },
            { name: 'Pagadores', href: '/payers', icon: Building2 },
            { name: 'Planillas', href: '/payrolls', icon: FileText },
        ],
    });

    if (role === 'admin') {
        list.push({
            key: 'admin',
            title: 'Administración',
            icon: Sliders,
            roles: ['admin'],
            items: [
                { name: 'Configuración', href: '/admin/configuracion', icon: Settings },
                { name: 'Usuarios', href: '/admin/usuarios', icon: UserCog },
                { name: 'Métricas', href: '/admin/metricas/operadores', icon: BarChart3 },
                { name: 'Comunicaciones', href: '/admin/comunicaciones', icon: MessageSquareText },
                { name: 'Envíos WhatsApp', href: '/admin/whatsapp-envios', icon: Send },
            ],
        });
    }

    return list;
});

/** Qué sección tiene un ítem activo (para abrirla por defecto). */
const sectionWithActiveLink = computed(() => {
    const path = currentPath.value;
    for (const section of sections.value) {
        const hasActive = section.items.some((item) => path.startsWith(item.href) || (item.href !== '/dashboard' && path === item.href));
        if (hasActive) return section.key;
    }
    return null;
});

/** Solo una sección abierta a la vez: la que contiene la ruta actual. Al cambiar de sección, las demás se colapsan. */
const openSections = ref(new Set());

watch(
    () => sectionWithActiveLink.value,
    (key) => {
        openSections.value = key ? new Set([key]) : new Set();
    },
    { immediate: true }
);

const toggleSection = (key) => {
    if (openSections.value.has(key)) {
        openSections.value = new Set();
    } else {
        openSections.value = new Set([key]);
    }
};

const isSectionOpen = (key) => openSections.value.has(key);
const isActive = (href) => currentPath.value.startsWith(href) || (href !== '/dashboard' && currentPath.value === href);

/** Estilo del título de sección: un solo color para todos (evitar confusión). */
const sectionHeaderClass = () => 'border-gray-200/80 bg-gray-100 text-gray-700 hover:bg-gray-200/90 border-gray-300';
</script>

<template>
    <div class="min-h-screen bg-gray-100">
        <!-- Desktop sidebar -->
        <div class="hidden lg:fixed lg:inset-y-0 lg:flex lg:w-64 lg:flex-col">
            <div class="flex flex-col flex-grow bg-white border-r border-gray-200">
                <!-- Logo -->
                <Link
                    href="/dashboard"
                    class="flex items-center h-16 px-4 border-b border-gray-200 hover:bg-gray-50 transition-colors focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-500 focus-visible:ring-offset-2"
                >
                    <div class="h-10 w-10 rounded-lg bg-gradient-to-br from-brand-500 to-brand-600 flex items-center justify-center">
                        <span class="text-white font-bold text-lg">S</span>
                    </div>
                    <span class="ml-3 text-xl font-bold text-brand-900">Serviconli</span>
                </Link>

                <!-- Navigation -->
                <nav class="flex-1 mt-4 px-3 space-y-1 overflow-y-auto">
                    <div v-for="section in sections" :key="section.key" class="mb-3">
                        <button
                            v-if="section.items.length > 1"
                            type="button"
                            @click="toggleSection(section.key)"
                            class="flex items-center w-full px-3 py-2.5 text-left text-xs font-semibold uppercase tracking-wider rounded-lg transition-colors border-l-2"
                            :class="sectionHeaderClass(section.key)"
                        >
                            <component :is="section.icon" class="mr-2.5 h-4 w-4 flex-shrink-0" />
                            {{ section.title }}
                            <component :is="isSectionOpen(section.key) ? ChevronDown : ChevronRight" class="ml-auto h-4 w-4 opacity-70" />
                        </button>
                        <div
                            v-else
                            class="px-3 py-2.5 text-xs font-semibold uppercase tracking-wider rounded-lg flex items-center gap-2 border-l-2"
                            :class="sectionHeaderClass(section.key)"
                        >
                            <component :is="section.icon" class="h-4 w-4 flex-shrink-0" />
                            {{ section.title }}
                        </div>
                        <div v-show="section.items.length === 1 || isSectionOpen(section.key)" class="mt-0.5 ml-1 space-y-0.5">
                            <Link
                                v-for="item in section.items"
                                :key="item.name"
                                :href="item.href"
                                :class="[
                                    isActive(item.href)
                                        ? 'bg-brand-50 text-brand-700 border-l-4 border-brand-500'
                                        : 'text-gray-600 hover:bg-gray-50 border-l-4 border-transparent',
                                    'group flex items-center px-3 py-2 rounded-r-lg text-sm font-medium transition-colors'
                                ]"
                            >
                                <component :is="item.icon" class="mr-3 h-5 w-5 flex-shrink-0" />
                                <span class="truncate">{{ item.name }}</span>
                            </Link>
                        </div>
                    </div>
                </nav>

                <!-- User menu -->
                <div class="p-4 border-t border-gray-200">
                    <div class="flex items-center">
                        <div class="h-9 w-9 rounded-full bg-brand-100 flex items-center justify-center">
                            <span class="text-brand-700 font-medium text-sm">{{ user?.name?.charAt(0).toUpperCase() }}</span>
                        </div>
                        <div class="ml-3 flex-1">
                            <p class="text-sm font-medium text-gray-700">{{ user?.name }}</p>
                            <p class="text-xs text-gray-500">{{ user?.role }}</p>
                        </div>
                        <Link href="/logout" method="post" as="button" class="text-gray-400 hover:text-red-500 transition-colors">
                            <LogOut class="h-5 w-5" />
                        </Link>
                    </div>
                </div>
            </div>
        </div>

        <!-- Mobile sidebar (drawer) -->
        <div v-if="sidebarOpen" class="fixed inset-0 z-50 lg:hidden">
            <div class="absolute inset-0 bg-gray-900/40" @click="sidebarOpen = false" />

            <div class="absolute inset-y-0 left-0 w-72 max-w-[85vw] bg-white shadow-xl flex flex-col">
                <!-- Header -->
                <div class="flex items-center justify-between h-16 px-4 border-b border-gray-200">
                    <Link
                        href="/dashboard"
                        @click="sidebarOpen = false"
                        class="flex items-center gap-3 rounded-lg focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-500 focus-visible:ring-offset-2"
                    >
                        <div class="h-9 w-9 rounded-lg bg-gradient-to-br from-brand-500 to-brand-600 flex items-center justify-center">
                            <span class="text-white font-bold text-base">S</span>
                        </div>
                        <span class="text-lg font-bold text-brand-900">Serviconli</span>
                    </Link>

                    <button
                        type="button"
                        @click="sidebarOpen = false"
                        class="p-2 rounded-lg text-gray-500 hover:text-gray-700 hover:bg-gray-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-500 focus-visible:ring-offset-2"
                        aria-label="Cerrar menú"
                    >
                        <X class="h-5 w-5" />
                    </button>
                </div>

                <!-- Navigation -->
                <nav class="flex-1 mt-4 px-3 space-y-1 overflow-y-auto">
                    <div v-for="section in sections" :key="section.key" class="mb-3">
                        <button
                            v-if="section.items.length > 1"
                            type="button"
                            @click="toggleSection(section.key)"
                            class="flex items-center w-full px-3 py-2.5 text-left text-xs font-semibold uppercase tracking-wider rounded-lg transition-colors border-l-2"
                            :class="sectionHeaderClass(section.key)"
                        >
                            <component :is="section.icon" class="mr-2.5 h-4 w-4 flex-shrink-0" />
                            {{ section.title }}
                            <component :is="isSectionOpen(section.key) ? ChevronDown : ChevronRight" class="ml-auto h-4 w-4 opacity-70" />
                        </button>
                        <div
                            v-else
                            class="px-3 py-2.5 text-xs font-semibold uppercase tracking-wider rounded-lg flex items-center gap-2 border-l-2"
                            :class="sectionHeaderClass(section.key)"
                        >
                            <component :is="section.icon" class="h-4 w-4 flex-shrink-0" />
                            {{ section.title }}
                        </div>
                        <div v-show="section.items.length === 1 || isSectionOpen(section.key)" class="mt-0.5 ml-1 space-y-0.5">
                            <Link
                                v-for="item in section.items"
                                :key="item.name"
                                :href="item.href"
                                @click="sidebarOpen = false"
                                :class="[
                                    isActive(item.href)
                                        ? 'bg-brand-50 text-brand-700 border-l-4 border-brand-500'
                                        : 'text-gray-600 hover:bg-gray-50 border-l-4 border-transparent',
                                    'group flex items-center px-3 py-2 rounded-r-lg text-sm font-medium transition-colors'
                                ]"
                            >
                                <component :is="item.icon" class="mr-3 h-5 w-5 flex-shrink-0" />
                                <span class="truncate">{{ item.name }}</span>
                            </Link>
                        </div>
                    </div>
                </nav>

                <!-- User menu -->
                <div class="p-4 border-t border-gray-200">
                    <div class="flex items-center">
                        <div class="h-9 w-9 rounded-full bg-brand-100 flex items-center justify-center">
                            <span class="text-brand-700 font-medium text-sm">{{ user?.name?.charAt(0).toUpperCase() }}</span>
                        </div>
                        <div class="ml-3 flex-1 min-w-0">
                            <p class="text-sm font-medium text-gray-700 truncate">{{ user?.name }}</p>
                            <p class="text-xs text-gray-500">{{ user?.role }}</p>
                        </div>
                        <Link
                            href="/logout"
                            method="post"
                            as="button"
                            class="text-gray-400 hover:text-red-500 transition-colors"
                        >
                            <LogOut class="h-5 w-5" />
                        </Link>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main content -->
        <div class="lg:pl-64">
            <!-- Top bar mobile -->
            <div class="sticky top-0 z-40 flex h-16 items-center gap-x-4 bg-white border-b border-gray-200 px-4 lg:hidden">
                <button @click="sidebarOpen = true" class="text-gray-500 hover:text-gray-700">
                    <Menu class="h-6 w-6" />
                </button>
                <Link
                    href="/dashboard"
                    class="font-bold text-brand-900 hover:text-brand-700 transition-colors focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-500 focus-visible:ring-offset-2 rounded"
                >
                    Serviconli
                </Link>
            </div>

            <!-- Flash messages -->
            <div v-if="flash?.success || flash?.error" class="px-4 sm:px-6 lg:px-8 mt-4">
                <div v-if="flash.success" class="rounded-lg bg-brand-50 border border-brand-200 p-4 flex items-center gap-3">
                    <CheckCircle class="h-5 w-5 text-brand-600 flex-shrink-0" />
                    <p class="text-sm text-brand-700">{{ flash.success }}</p>
                </div>
                <div v-if="flash.error" class="rounded-lg bg-red-50 border border-red-200 p-4 flex items-center gap-3">
                    <XCircle class="h-5 w-5 text-red-600 flex-shrink-0" />
                    <p class="text-sm text-red-700">{{ flash.error }}</p>
                </div>
            </div>

            <!-- Page content -->
            <main class="py-6 px-4 sm:px-6 lg:px-8">
                <slot />
            </main>
        </div>
    </div>
</template>
