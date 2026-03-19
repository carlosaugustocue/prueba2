<script setup>
import { ref, computed, onMounted } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import axios from 'axios';
import AppSidebar from '@/Components/AppSidebar.vue';
import { CheckCircle, XCircle, ClipboardList, AlertTriangle } from 'lucide-vue-next';

const page = usePage();
const user = computed(() => page.props.auth?.user);
const flash = computed(() => page.props.flash);
const currentPath = computed(() => window.location.pathname);

const sidebarBadges = computed(() => page.props.sidebarBadges || {});

// --- Tareas internas por afiliado (cartera, seguridad social) ---
const affiliateTasks = ref([]);
const affiliateTasksLoading = ref(false);
const affiliateTasksError = ref('');
const showAffiliateTasksPanel = ref(false);

const shouldShowAffiliateTasksBanner = computed(() => affiliateTasks.value.length > 0);

const loadAffiliateTasks = async () => {
    affiliateTasksLoading.value = true;
    affiliateTasksError.value = '';
    try {
        const response = await axios.get('/api/affiliate-tasks/my-pending');
        affiliateTasks.value = Array.isArray(response.data) ? response.data : [];
    } catch (e) {
        // 401: sesión no enviada o expirada en esta petición; no mostrar error para no confundir con la página actual
        if (e?.response?.status !== 401) {
            affiliateTasksError.value = 'No se pudieron cargar las tareas pendientes.';
        }
        affiliateTasks.value = [];
    } finally {
        affiliateTasksLoading.value = false;
    }
};

const pendingTasksCount = computed(() => affiliateTasks.value.length);

const completeAffiliateTask = async (taskId) => {
    try {
        await axios.post(`/api/affiliate-tasks/${taskId}/complete`);
        affiliateTasks.value = affiliateTasks.value.filter((t) => t.id !== taskId);
        if (affiliateTasks.value.length === 0) {
            showAffiliateTasksPanel.value = false;
        }
    } catch (e) {
        console.error(e);
        affiliateTasksError.value = 'No se pudo marcar la tarea como completada.';
    }
};

onMounted(() => {
    if (user.value) {
        loadAffiliateTasks();
    }
});
</script>

<template>
    <div class="min-h-screen bg-gray-100">
        <AppSidebar :current-route="currentPath" :user="user" :badges="sidebarBadges" />

        <!-- Main content -->
        <div class="lg:pl-72 pb-20 lg:pb-0">
            <!-- Top bar mobile -->
            <div class="sticky top-0 z-30 flex h-14 items-center bg-white border-b border-gray-200 px-4 lg:hidden">
                <Link href="/dashboard" class="font-extrabold text-gray-900">
                    Serviconli
                </Link>
            </div>

            <!-- Banner de tareas pendientes por afiliado (cartera / seguridad social) -->
            <div v-if="shouldShowAffiliateTasksBanner" class="px-4 sm:px-6 lg:px-8 mt-2">
                <div class="rounded-lg bg-red-50 border border-red-300 p-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 shadow-sm">
                    <div class="flex items-start gap-3">
                        <AlertTriangle class="h-6 w-6 text-red-600 mt-0.5 flex-shrink-0" />
                        <div>
                            <p class="text-base font-bold text-red-900">
                                Hay {{ pendingTasksCount }} afiliado{{ pendingTasksCount === 1 ? '' : 's' }} con tareas pendientes.
                            </p>
                            <p class="text-xs text-red-800">
                                Recuerde atender las tareas de cartera y seguridad social para cada afiliado nuevo.
                            </p>
                            <p v-if="affiliateTasksError" class="mt-1 text-xs text-red-700">
                                {{ affiliateTasksError }}
                            </p>
                        </div>
                    </div>
                    <div class="flex items-center gap-2">
                        <button
                            type="button"
                            class="inline-flex items-center gap-1.5 rounded-lg px-3 py-2 text-xs font-semibold text-white bg-red-600 hover:bg-red-700 transition-colors shadow"
                            @click="showAffiliateTasksPanel = !showAffiliateTasksPanel"
                        >
                            <ClipboardList class="h-4 w-4" />
                            {{ showAffiliateTasksPanel ? 'Ocultar pendientes' : 'Ver pendientes' }}
                        </button>
                        <button
                            v-if="affiliateTasksLoading"
                            type="button"
                            class="text-xs text-red-800"
                            disabled
                        >
                            Cargando...
                        </button>
                    </div>
                </div>
                <div v-if="showAffiliateTasksPanel" class="mt-3 rounded-lg bg-white border border-amber-100 p-3 shadow-sm">
                    <ul class="divide-y divide-gray-100">
                        <li v-for="task in affiliateTasks" :key="task.id" class="py-2 flex items-start justify-between gap-3">
                            <div>
                                <p class="text-sm font-medium text-gray-900">
                                    {{ task.affiliate_name || 'Afiliado #' + task.affiliate_id }}
                                </p>
                                <p class="text-xs text-gray-500">
                                    {{ task.affiliate_document ? `Doc: ${task.affiliate_document} · ` : '' }}Área: {{ task.area === 'cartera' ? 'Cartera' : (task.area === 'seguridad_social' ? 'Seguridad Social' : task.area) }}
                                </p>
                                <p class="mt-0.5 text-xs text-gray-700">
                                    {{ task.description }}
                                </p>
                            </div>
                            <div class="flex items-center gap-2 flex-shrink-0">
                                <button
                                    type="button"
                                    class="inline-flex items-center gap-1 rounded-lg px-2 py-1 text-xs font-semibold text-green-700 bg-green-50 hover:bg-green-100 border border-green-200 transition-colors"
                                    @click="completeAffiliateTask(task.id)"
                                >
                                    <CheckCircle class="h-4 w-4" />
                                    Hecho
                                </button>
                            </div>
                        </li>
                    </ul>
                </div>
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
