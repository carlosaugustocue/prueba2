<script setup>
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import Pagination from '@/Components/Pagination.vue';
import { X } from 'lucide-vue-next';

const props = defineProps({
    open: { type: Boolean, default: false },
    logs: { type: [Object, null], default: null },
});

const emit = defineEmits(['close']);

const closing = ref(false);

const close = () => {
    if (closing.value) return;
    closing.value = true;
    emit('close');
};

const escapeHandler = (e) => {
    if (!props.open) return;
    if (e.key === 'Escape') close();
};

onMounted(() => {
    window.addEventListener('keydown', escapeHandler);
});

onBeforeUnmount(() => {
    window.removeEventListener('keydown', escapeHandler);
});

watch(
    () => props.open,
    (v) => {
        if (v) closing.value = false;
    }
);

const badgeClass = (action) => {
    const a = String(action || '').toLowerCase();
    if (a === 'viewed') return 'bg-gray-100 text-gray-600';
    if (a === 'created') return 'bg-green-50 text-green-700';
    if (a === 'updated') return 'bg-blue-50 text-blue-700';
    if (a === 'deleted') return 'bg-red-50 text-red-700';
    return 'bg-gray-100 text-gray-600';
};

const formatDate = (isoLike) => {
    if (!isoLike) return '—';
    const d = new Date(isoLike);
    if (Number.isNaN(d.getTime())) return String(isoLike);
    return new Intl.DateTimeFormat('es-CO', { day: '2-digit', month: 'short', year: 'numeric' }).format(d);
};

const formatTime = (isoLike) => {
    if (!isoLike) return '—';
    const d = new Date(isoLike);
    if (Number.isNaN(d.getTime())) return '—';
    return new Intl.DateTimeFormat('es-CO', { hour: '2-digit', minute: '2-digit' }).format(d);
};

const auditTypeLabel = (kind) => {
    const k = String(kind || '').toLowerCase();
    if (k === 'pila') return 'PILA';
    if (k === 'portal') return 'PORTAL';
    return k ? k.toUpperCase() : '—';
};

const paginatedLinks = computed(() => props.logs?.links ?? []);
const paginatedData = computed(() => props.logs?.data ?? []);
</script>

<template>
    <Teleport to="body">
        <div v-if="open" class="fixed inset-0 z-50">
            <!-- overlay -->
            <div
                class="absolute inset-0 bg-black/40"
                @click="close"
                aria-hidden="true"
            />

            <!-- drawer -->
            <transition
                enter-active-class="transition duration-200 ease-out"
                enter-from-class="opacity-0 translate-x-full"
                enter-to-class="opacity-100 translate-x-0"
                leave-active-class="transition duration-150 ease-in"
                leave-from-class="opacity-100 translate-x-0"
                leave-to-class="opacity-0 translate-x-full"
            >
                <aside
                    class="absolute top-0 right-0 h-full w-full sm:w-[520px] bg-white shadow-xl border-l border-gray-200 flex flex-col"
                    @click.stop
                    role="dialog"
                    aria-modal="true"
                    aria-label="Historial de accesos a credenciales"
                >
                    <header class="flex items-center justify-between gap-3 px-5 py-4 border-b border-gray-200">
                        <h2 class="text-sm font-semibold text-gray-900">
                            Historial de accesos a credenciales
                        </h2>
                        <button
                            type="button"
                            class="h-9 w-9 inline-flex items-center justify-center rounded-lg hover:bg-gray-100 text-gray-700"
                            @click="close"
                            aria-label="Cerrar"
                        >
                            <X class="h-4 w-4" />
                        </button>
                    </header>

                    <div class="flex-1 overflow-y-auto px-5 py-4">
                        <div v-if="!paginatedData.length" class="text-sm text-gray-500">
                            Sin registros de auditoría.
                        </div>

                        <div v-else class="space-y-3">
                            <div
                                v-for="log in paginatedData"
                                :key="log.id"
                                class="p-4 rounded-xl border border-gray-100 bg-white"
                            >
                                <div class="flex items-start justify-between gap-3">
                                    <div class="min-w-0 flex items-start gap-3">
                                        <span
                                            class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold border whitespace-nowrap"
                                            :class="[badgeClass(log.action), 'border-transparent']"
                                        >
                                            {{ log.action || '—' }}
                                        </span>

                                        <div class="min-w-0">
                                            <div class="text-sm font-semibold text-gray-900">
                                                {{ auditTypeLabel(log.credential_kind) }} #{{ log.credential_id ?? '—' }}
                                            </div>
                                        </div>
                                    </div>

                                    <div class="text-xs text-gray-500 whitespace-nowrap">
                                        {{ formatTime(log.created_at) }}
                                    </div>
                                </div>

                                <div class="mt-2 flex items-center justify-between gap-3 text-xs text-gray-500">
                                    <div class="truncate">
                                        {{ log.user || '—' }} · {{ log.ip_address || '—' }}
                                    </div>
                                    <div class="whitespace-nowrap">
                                        {{ formatDate(log.created_at) }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <footer class="px-5 py-4 border-t border-gray-200">
                        <Pagination v-if="paginatedLinks.length" :links="paginatedLinks" />
                    </footer>
                </aside>
            </transition>
        </div>
    </Teleport>
</template>

