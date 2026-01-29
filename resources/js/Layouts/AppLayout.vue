<script setup>
import { ref, computed } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import { LayoutDashboard, CalendarDays, Users, LogOut, Menu, CheckCircle, XCircle, ClipboardList, BarChart3, MessageSquareText, X } from 'lucide-vue-next';

const page = usePage();
const user = computed(() => page.props.auth?.user);
const flash = computed(() => page.props.flash);

const sidebarOpen = ref(false);

const navigation = computed(() => {
    const items = [
        { name: 'Dashboard', href: '/dashboard', icon: LayoutDashboard },
        { name: 'Solicitudes', href: '/appointment-requests', icon: ClipboardList },
        { name: 'Citas', href: '/appointments', icon: CalendarDays },
        { name: 'Pacientes', href: '/patients', icon: Users },
    ];

    if (user.value?.role === 'admin') {
        items.push({ name: 'Métricas', href: '/admin/metricas/operadores', icon: BarChart3 });
        items.push({ name: 'Comunicaciones', href: '/admin/comunicaciones', icon: MessageSquareText });
    }

    return items;
});

const isActive = (href) => window.location.pathname.startsWith(href);
</script>

<template>
    <div class="min-h-screen bg-gray-50">
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
                <nav class="flex-1 mt-6 px-3 space-y-1">
                    <Link
                        v-for="item in navigation"
                        :key="item.name"
                        :href="item.href"
                        :class="[
                            isActive(item.href)
                                ? 'bg-brand-50 text-brand-700 border-l-4 border-brand-500'
                                : 'text-gray-600 hover:bg-gray-50 border-l-4 border-transparent',
                            'group flex items-center px-3 py-2.5 rounded-r-lg text-sm font-medium transition-colors'
                        ]"
                    >
                        <component :is="item.icon" class="mr-3 h-5 w-5" />
                        {{ item.name }}
                    </Link>
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
                    <Link
                        v-for="item in navigation"
                        :key="item.name"
                        :href="item.href"
                        @click="sidebarOpen = false"
                        :class="[
                            isActive(item.href)
                                ? 'bg-brand-50 text-brand-700 border-l-4 border-brand-500'
                                : 'text-gray-600 hover:bg-gray-50 border-l-4 border-transparent',
                            'group flex items-center px-3 py-2.5 rounded-r-lg text-sm font-medium transition-colors'
                        ]"
                    >
                        <component :is="item.icon" class="mr-3 h-5 w-5" />
                        {{ item.name }}
                    </Link>
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
