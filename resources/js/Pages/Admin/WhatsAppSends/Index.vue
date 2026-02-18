<script setup>
import { computed } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { MessageCircle, Filter, Clock, CheckCircle, XCircle, Send, Ban } from 'lucide-vue-next';
import Pagination from '@/Components/Pagination.vue';
import { confirmDialog } from '@/Utils/swal';

const props = defineProps({
    filters: Object,
    items: Object,
    pending_count: Number,
    statuses: Array,
    types: Array,
});

const rows = computed(() => props.items?.data || []);

const apply = (key, value) => {
    router.get('/admin/whatsapp-envios', {
        ...props.filters,
        [key]: value || undefined,
    }, { preserveState: true, replace: true });
};

function statusBadgeClass(status, isPending) {
    if (isPending) return 'bg-amber-100 text-amber-800 border border-amber-300';
    switch (status) {
        case 'sent': return 'bg-green-100 text-green-800';
        case 'failed': return 'bg-red-100 text-red-800';
        case 'cancelled': return 'bg-gray-100 text-gray-600';
        case 'processing': return 'bg-blue-100 text-blue-800';
        default: return 'bg-gray-100 text-gray-700';
    }
}

async function cancelSend(reminderId) {
    const ok = await confirmDialog({
        title: 'Cancelar envío por WhatsApp',
        text: '¿Cancelar este envío? No se enviará el mensaje al paciente.',
        icon: 'warning',
        confirmButtonText: 'Sí, cancelar envío',
        cancelButtonText: 'No, mantener',
    });
    if (!ok) return;
    router.post(`/admin/whatsapp-envios/${reminderId}/cancel`, {}, { preserveScroll: true });
}
</script>

<template>
    <AppLayout>
        <div class="max-w-6xl mx-auto space-y-6">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">Envíos WhatsApp</h1>
                    <p class="text-sm text-gray-500">Administrar mensajes programados (confirmaciones y recordatorios). Puede cancelar los que aún no se han enviado.</p>
                </div>
                <div class="flex items-center gap-3">
                    <Link
                        v-if="pending_count > 0"
                        href="/admin/comunicaciones?channel=whatsapp&status=pending"
                        class="inline-flex items-center gap-2 px-4 py-2 bg-amber-50 text-amber-800 border border-amber-200 rounded-lg text-sm font-medium hover:bg-amber-100"
                    >
                        <Clock class="h-4 w-4" />
                        {{ pending_count }} por enviar → Comunicaciones
                    </Link>
                    <Link
                        href="/admin/comunicaciones"
                        class="inline-flex items-center gap-2 px-4 py-2 bg-white border border-gray-300 rounded-lg text-sm font-medium hover:bg-gray-50"
                    >
                        <MessageCircle class="h-4 w-4" />
                        Ver todas las comunicaciones
                    </Link>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4">
                <div class="flex flex-wrap items-center gap-3">
                    <div class="flex items-center gap-2 text-gray-500">
                        <Filter class="h-4 w-4" />
                        <span class="text-sm font-medium">Filtros</span>
                    </div>
                    <select :value="filters.status || ''" @change="apply('status', $event.target.value)" class="rounded-lg border-gray-300 text-sm focus:border-brand-500 focus:ring-brand-500">
                        <option value="">Todos los estados</option>
                        <option v-for="s in statuses" :key="s.value" :value="s.value">{{ s.label }}</option>
                    </select>
                    <select :value="filters.type || ''" @change="apply('type', $event.target.value)" class="rounded-lg border-gray-300 text-sm focus:border-brand-500 focus:ring-brand-500">
                        <option value="">Todos los tipos</option>
                        <option v-for="t in types" :key="t.value" :value="t.value">{{ t.label }}</option>
                    </select>
                    <input type="date" :value="filters.from" @change="apply('from', $event.target.value)" class="rounded-lg border-gray-300 text-sm focus:border-brand-500 focus:ring-brand-500" />
                    <input type="date" :value="filters.to" @change="apply('to', $event.target.value)" class="rounded-lg border-gray-300 text-sm focus:border-brand-500 focus:ring-brand-500" />
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200 bg-gray-50 flex items-center gap-2">
                    <Send class="h-5 w-5 text-brand-600" />
                    <h2 class="font-semibold text-gray-900">Listado de envíos</h2>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-white">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Fecha / Programado</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tipo</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Estado</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Cita</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Paciente</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Destino</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Acciones</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <tr v-for="r in rows" :key="r.id" class="hover:bg-gray-50" :class="{ 'bg-amber-50/50': r.is_pending }">
                                <td class="px-6 py-4 text-sm text-gray-700">
                                    <span class="text-gray-500">Creado:</span> {{ r.created_at_formatted }}
                                    <template v-if="r.scheduled_at_formatted">
                                        <br /><span class="text-gray-500">Envío:</span> {{ r.scheduled_at_formatted }}
                                    </template>
                                    <template v-if="r.sent_at_formatted">
                                        <br /><span class="text-green-600">Enviado:</span> {{ r.sent_at_formatted }}
                                    </template>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-700">{{ r.type_label }}</td>
                                <td class="px-6 py-4">
                                    <span
                                        :class="['inline-flex items-center gap-1 px-2 py-1 rounded-full text-xs font-medium', statusBadgeClass(r.status, r.is_pending)]"
                                    >
                                        <Clock v-if="r.is_pending" class="h-3.5 w-3.5" />
                                        <CheckCircle v-else-if="r.status === 'sent'" class="h-3.5 w-3.5" />
                                        <XCircle v-else-if="r.status === 'failed' || r.status === 'cancelled'" class="h-3.5 w-3.5" />
                                        {{ r.status_label }}
                                        <span v-if="r.is_pending" class="font-semibold">(por enviar)</span>
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-sm">
                                    <Link
                                        v-if="r.appointment_id"
                                        :href="`/appointments/${r.appointment_id}`"
                                        class="text-brand-600 hover:text-brand-700 font-medium"
                                    >
                                        #{{ r.appointment_id }}
                                    </Link>
                                    <span v-else class="text-gray-400">—</span>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-700">{{ r.affiliate?.full_name || '—' }}</td>
                                <td class="px-6 py-4 text-sm text-gray-600">{{ r.recipient || '—' }}</td>
                                <td class="px-6 py-4 text-sm">
                                    <button
                                        v-if="r.is_cancellable"
                                        type="button"
                                        @click="cancelSend(r.id)"
                                        class="inline-flex items-center gap-1.5 px-3 py-1.5 text-sm font-medium text-red-700 bg-red-50 hover:bg-red-100 rounded-lg transition-colors"
                                    >
                                        <Ban class="h-4 w-4" />
                                        Cancelar envío
                                    </button>
                                    <span v-else class="text-gray-400">—</span>
                                </td>
                            </tr>
                            <tr v-if="!rows.length">
                                <td colspan="7" class="px-6 py-10 text-center text-sm text-gray-500">
                                    No hay envíos para los filtros seleccionados.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="px-6 py-4 border-t border-gray-200">
                    <Pagination :links="items?.links" />
                </div>
            </div>
        </div>
    </AppLayout>
</template>
