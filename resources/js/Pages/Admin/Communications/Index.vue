<script setup>
import { computed } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { Download, Filter, MessageSquare, Send, Clock } from 'lucide-vue-next';
import Pagination from '@/Components/Pagination.vue';

const props = defineProps({
    filters: Object,
    items: Object,
    operators: Array,
    epsList: Array,
    statuses: Array,
    channels: Array,
    whatsapp_pending_count: { type: Number, default: 0 },
});

const rows = computed(() => props.items?.data || []);

const apply = (key, value) => {
    router.get('/admin/comunicaciones', {
        ...props.filters,
        [key]: value || undefined,
    }, { preserveState: true, replace: true });
};

const clear = () => {
    router.get('/admin/comunicaciones', {}, { preserveState: true, replace: true });
};

const csvUrl = computed(() => {
    const q = new URLSearchParams({ ...props.filters, format: 'csv' }).toString();
    return `/admin/comunicaciones?${q}`;
});

const statusLabel = (status) => {
    const s = props.statuses?.find(x => x.value === status);
    return s?.label ?? status;
};

const isPendingWhatsApp = (r) => r.channel === 'whatsapp' && (r.status === 'pending' || r.status === 'processing');
</script>

<template>
    <AppLayout>
        <div class="max-w-6xl mx-auto space-y-6">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">Comunicaciones</h1>
                    <p class="text-sm text-gray-500">Auditoría de WhatsApp y llamadas telefónicas (solo admin)</p>
                </div>
                <div class="flex items-center gap-2">
                    <Link
                        v-if="whatsapp_pending_count > 0"
                        href="/admin/whatsapp-envios?status=pending"
                        class="inline-flex items-center gap-2 px-4 py-2 bg-amber-50 text-amber-800 border border-amber-200 rounded-lg text-sm font-medium hover:bg-amber-100"
                    >
                        <Send class="h-4 w-4" />
                        {{ whatsapp_pending_count }} por enviar → Envíos WhatsApp
                    </Link>
                    <a :href="csvUrl" class="inline-flex items-center gap-2 px-4 py-2 bg-white border border-gray-300 rounded-lg text-sm font-medium hover:bg-gray-50">
                        <Download class="h-4 w-4" />
                        Exportar CSV
                    </a>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4">
                <div class="flex flex-wrap items-center gap-3">
                    <div class="flex items-center gap-2 text-gray-500">
                        <Filter class="h-4 w-4" />
                        <span class="text-sm font-medium">Filtros</span>
                    </div>
                    <input type="date" :value="filters.from" @change="apply('from', $event.target.value)" class="rounded-lg border-gray-300 text-sm focus:border-brand-500 focus:ring-brand-500" />
                    <input type="date" :value="filters.to" @change="apply('to', $event.target.value)" class="rounded-lg border-gray-300 text-sm focus:border-brand-500 focus:ring-brand-500" />

                    <select :value="filters.channel || ''" @change="apply('channel', $event.target.value)" class="rounded-lg border-gray-300 text-sm focus:border-brand-500 focus:ring-brand-500">
                        <option value="">Todos los canales</option>
                        <option v-for="c in channels" :key="c.value" :value="c.value">{{ c.label }}</option>
                    </select>

                    <select :value="filters.status || ''" @change="apply('status', $event.target.value)" class="rounded-lg border-gray-300 text-sm focus:border-brand-500 focus:ring-brand-500">
                        <option value="">Todos los estados</option>
                        <option v-for="s in statuses" :key="s.value" :value="s.value">{{ s.label }}</option>
                    </select>

                    <select :value="filters.operator_id || ''" @change="apply('operator_id', $event.target.value)" class="rounded-lg border-gray-300 text-sm focus:border-brand-500 focus:ring-brand-500">
                        <option value="">Todos los operadores</option>
                        <option v-for="op in operators" :key="op.id" :value="op.id">{{ op.name }}</option>
                    </select>

                    <select :value="filters.eps_id || ''" @change="apply('eps_id', $event.target.value)" class="rounded-lg border-gray-300 text-sm focus:border-brand-500 focus:ring-brand-500">
                        <option value="">Todas las EPS</option>
                        <option v-for="e in epsList" :key="e.id" :value="e.id">{{ e.name }}</option>
                    </select>

                    <button @click="clear" class="text-sm text-gray-500 hover:text-gray-700">Limpiar</button>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200 bg-gray-50 flex items-center gap-2">
                    <MessageSquare class="h-5 w-5 text-brand-600" />
                    <h2 class="font-semibold text-gray-900">Listado</h2>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-white">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Fecha</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Canal</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tipo</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Estado</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Cita</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Afiliado</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">EPS</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Operador</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Detalle</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <tr v-for="r in rows" :key="`${r.channel}-${r.id}`" class="hover:bg-gray-50" :class="{ 'bg-amber-50/50': isPendingWhatsApp(r) }">
                                <td class="px-6 py-4 text-sm text-gray-700">{{ r.created_at }}</td>
                                <td class="px-6 py-4 text-sm text-gray-700">{{ r.channel }}</td>
                                <td class="px-6 py-4 text-sm text-gray-700">{{ r.type }}</td>
                                <td class="px-6 py-4 text-sm">
                                    <Link
                                        v-if="isPendingWhatsApp(r)"
                                        href="/admin/whatsapp-envios?status=pending"
                                        class="inline-flex items-center gap-1 px-2 py-1 rounded-full text-xs font-medium bg-amber-100 text-amber-800 border border-amber-300 hover:bg-amber-200"
                                    >
                                        <Clock class="h-3.5 w-3.5" />
                                        Por enviar →
                                    </Link>
                                    <span v-else class="inline-flex px-2 py-0.5 rounded text-xs font-medium" :class="{
                                        'bg-green-100 text-green-800': r.status === 'sent',
                                        'bg-red-100 text-red-800': r.status === 'failed',
                                        'bg-gray-100 text-gray-600': r.status === 'cancelled',
                                        'bg-blue-100 text-blue-800': r.status === 'processing',
                                        'bg-gray-100 text-gray-700': !['sent','failed','cancelled','processing'].includes(r.status)
                                    }">
                                        {{ r.channel === 'phone' ? 'Registrada' : statusLabel(r.status) }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-sm">
                                    <Link :href="`/appointments/${r.appointment_id}`" class="text-brand-600 hover:text-brand-700 font-medium">
                                        #{{ r.appointment_id }}
                                    </Link>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-700">{{ r.affiliate_name }}</td>
                                <td class="px-6 py-4 text-sm text-gray-700">{{ r.eps_name || '-' }}</td>
                                <td class="px-6 py-4 text-sm text-gray-700">{{ r.operator_name || '-' }}</td>
                                <td class="px-6 py-4 text-sm text-gray-700">
                                    <div v-if="r.channel === 'phone'">
                                        <span class="text-xs bg-gray-100 text-gray-700 px-2 py-0.5 rounded-full">{{ r.category }}</span>
                                        <div v-if="r.note" class="text-xs text-gray-600 mt-1 whitespace-pre-line">{{ r.note }}</div>
                                    </div>
                                    <div v-else>
                                        <div v-if="r.recipient" class="text-xs text-gray-600">To: {{ r.recipient }}</div>
                                        <div v-if="r.error_message" class="text-xs text-red-600 mt-1">Error: {{ r.error_message }}</div>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="!rows.length">
                                <td colspan="9" class="px-6 py-10 text-center text-sm text-gray-500">
                                    Sin resultados.
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

