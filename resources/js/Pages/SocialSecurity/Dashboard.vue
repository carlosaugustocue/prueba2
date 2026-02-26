<script setup>
import { ref, computed } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { LayoutDashboard, Users, Building2, FileText, AlertCircle, CalendarClock, TrendingUp, Loader2, RefreshCw, CheckCircle, ListTodo } from 'lucide-vue-next';

const props = defineProps({
    metrics: Object,
    due_today: Array,
    due_today_count: Number,
    due_next_7: Array,
    due_next_7_count: Number,
    overdue_list: Array,
    pending_list: Array,
    pending_list_total: Number,
    status_labels: Object,
    current_year: Number,
    current_month: Number,
});

const statusLabel = (s) => props.status_labels?.[s] ?? s;
const todayStr = new Date().toISOString().slice(0, 10);

const batchYear = ref(new Date().getFullYear());
const batchMonth = ref(new Date().getMonth() + 1);
const batchLoading = ref(false);
const batchResult = ref(null);

const monthOptions = [
    { value: 1, label: 'Enero' }, { value: 2, label: 'Febrero' }, { value: 3, label: 'Marzo' },
    { value: 4, label: 'Abril' }, { value: 5, label: 'Mayo' }, { value: 6, label: 'Junio' },
    { value: 7, label: 'Julio' }, { value: 8, label: 'Agosto' }, { value: 9, label: 'Septiembre' },
    { value: 10, label: 'Octubre' }, { value: 11, label: 'Noviembre' }, { value: 12, label: 'Diciembre' },
];

const formatMoney = (n) => {
    if (n == null) return '—';
    return new Intl.NumberFormat('es-CO', { style: 'currency', currency: 'COP', maximumFractionDigits: 0 }).format(n);
};

const statusBadgeClass = (s) => {
    const map = {
        PENDING: 'bg-gray-100 text-gray-700',
        SETTLED: 'bg-blue-100 text-blue-700',
        SENT_TO_CLIENT: 'bg-amber-100 text-amber-700',
        PAID: 'bg-green-100 text-green-700',
        OVERDUE: 'bg-red-100 text-red-700',
    };
    return map[s] || 'bg-gray-100 text-gray-600';
};

const runBatchGenerate = () => {
    batchLoading.value = true;
    batchResult.value = null;
    const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
        || document.cookie.replace(/(?:(?:^|.*;\s*)XSRF-TOKEN\s*=\s*([^;]*).*$)|^.*$/, '$1');
    fetch('/payrolls/batch-generate', {
        method: 'POST',
        credentials: 'same-origin',
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-XSRF-TOKEN': decodeURIComponent(csrf),
            'X-Requested-With': 'XMLHttpRequest',
        },
        body: JSON.stringify({ year: batchYear.value, month: batchMonth.value }),
    }).then((r) => r.json()).then((data) => {
        batchResult.value = data;
        batchLoading.value = false;
        if (data?.created > 0) router.reload();
    }).catch(() => {
        batchLoading.value = false;
    });
};

const settleYear = ref(new Date().getFullYear());
const settleMonth = ref(new Date().getMonth() + 1);
const settleLoading = ref(false);
const settleResult = ref(null);

const runBatchSettle = () => {
    settleLoading.value = true;
    settleResult.value = null;
    const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
        || document.cookie.replace(/(?:(?:^|.*;\s*)XSRF-TOKEN\s*=\s*([^;]*).*$)|^.*$/, '$1');
    fetch('/payrolls/batch-settle', {
        method: 'POST',
        credentials: 'same-origin',
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-XSRF-TOKEN': decodeURIComponent(csrf),
            'X-Requested-With': 'XMLHttpRequest',
        },
        body: JSON.stringify({ year: settleYear.value, month: settleMonth.value }),
    }).then((r) => r.json()).then((data) => {
        settleResult.value = data;
        settleLoading.value = false;
        if (data?.settled > 0) router.reload();
    }).catch(() => {
        settleLoading.value = false;
    });
};
</script>

<template>
    <AppLayout>
        <div class="space-y-6">
            <div class="flex flex-wrap items-center justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">Dashboard Seguridad Social</h1>
                    <p class="mt-1 text-sm text-gray-500">Métricas y vencimientos de planillas</p>
                </div>
                <div class="flex flex-wrap items-center gap-3">
                    <Link href="/affiliates" class="inline-flex items-center gap-2 px-3 py-2 rounded-lg border border-gray-300 bg-white text-gray-700 hover:bg-gray-50">
                        <Users class="h-4 w-4" /> Afiliados
                    </Link>
                    <Link href="/payers" class="inline-flex items-center gap-2 px-3 py-2 rounded-lg border border-gray-300 bg-white text-gray-700 hover:bg-gray-50">
                        <Building2 class="h-4 w-4" /> Pagadores
                    </Link>
                    <Link href="/payrolls" class="inline-flex items-center gap-2 px-3 py-2 rounded-lg border border-gray-300 bg-white text-gray-700 hover:bg-gray-50">
                        <FileText class="h-4 w-4" /> Planillas
                    </Link>
                </div>
            </div>

            <!-- Avisos urgentes (lo primero que ve Katherine) -->
            <div v-if="(due_today_count || 0) > 0 || (metrics?.overdue_count || 0) > 0 || (metrics?.pending_to_settle_this_month || 0) > 0" class="space-y-3">
                <div v-if="(due_today_count || 0) > 0" class="flex items-center gap-3 px-4 py-3 rounded-xl bg-amber-100 border border-amber-300">
                    <AlertCircle class="h-6 w-6 text-amber-700 flex-shrink-0" />
                    <p class="font-semibold text-amber-900">
                        Hoy vencen <strong>{{ due_today_count }}</strong> planilla(s). Revisar y gestionar a tiempo.
                    </p>
                    <Link :href="`/payrolls?due_date=${todayStr}`" class="ml-auto inline-flex items-center gap-1 px-3 py-1.5 rounded-lg bg-amber-600 text-white text-sm font-medium hover:bg-amber-700">
                        Ver todas
                    </Link>
                </div>
                <div v-if="(metrics?.overdue_count || 0) > 0" class="flex items-center gap-3 px-4 py-3 rounded-xl bg-red-100 border border-red-300">
                    <AlertCircle class="h-6 w-6 text-red-700 flex-shrink-0" />
                    <p class="font-semibold text-red-900">
                        Hay <strong>{{ metrics.overdue_count }}</strong> planilla(s) en mora. Requieren atención.
                    </p>
                    <Link href="/payrolls?status=OVERDUE" class="ml-auto inline-flex items-center gap-1 px-3 py-1.5 rounded-lg bg-red-600 text-white text-sm font-medium hover:bg-red-700">
                        Ver todas
                    </Link>
                </div>
                <div v-if="(metrics?.pending_to_settle_this_month || 0) > 0" class="flex items-center gap-3 px-4 py-3 rounded-xl bg-blue-100 border border-blue-300">
                    <CheckCircle class="h-6 w-6 text-blue-700 flex-shrink-0" />
                    <p class="font-semibold text-blue-900">
                        Hay <strong>{{ metrics.pending_to_settle_this_month }}</strong> planilla(s) sin liquidar este mes. Liquidar para calcular montos.
                    </p>
                    <Link :href="`/payrolls?status=PENDING&year=${current_year}&month=${current_month}`" class="ml-auto inline-flex items-center gap-1 px-3 py-1.5 rounded-lg bg-blue-600 text-white text-sm font-medium hover:bg-blue-700">
                        Ver todas
                    </Link>
                </div>
            </div>

            <!-- Por liquidar (lista clara para no olvidar a nadie) -->
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
                <div class="px-4 py-3 border-b border-gray-200 bg-slate-100 flex items-center justify-between">
                    <h2 class="font-semibold text-gray-900 flex items-center gap-2">
                        <ListTodo class="h-5 w-5 text-slate-600" />
                        Por liquidar (este mes)
                        <span v-if="(pending_list_total || 0) > 0" class="px-2 py-0.5 bg-slate-200 text-slate-800 text-sm rounded-full">{{ pending_list_total }}</span>
                    </h2>
                    <Link v-if="(pending_list_total || 0) > (pending_list?.length || 0)" :href="`/payrolls?status=PENDING&year=${current_year}&month=${current_month}`" class="text-sm font-medium text-blue-600 hover:text-blue-700">
                        Ver todas ({{ pending_list_total }})
                    </Link>
                </div>
                <ul class="divide-y divide-gray-100 max-h-48 overflow-y-auto">
                    <li v-for="p in (pending_list || [])" :key="p.id" class="px-4 py-2 hover:bg-gray-50">
                        <Link :href="`/payrolls/${p.id}`" class="block">
                            <span class="font-medium text-gray-900">{{ p.affiliate?.full_name ?? '—' }}</span>
                            <span class="text-sm text-gray-500 block">{{ p.payer_name ?? '—' }} · Vence {{ p.due_date }}</span>
                        </Link>
                    </li>
                    <li v-if="!(pending_list?.length)" class="px-4 py-6 text-center text-sm text-gray-500">
                        No hay planillas sin liquidar este mes.
                    </li>
                </ul>
            </div>

            <!-- Qué hacer hoy (checklist para Katherine) -->
            <div class="bg-white rounded-xl border border-gray-200 p-4 shadow-sm">
                <h2 class="font-semibold text-gray-900 mb-3 flex items-center gap-2">
                    <ListTodo class="h-5 w-5 text-brand-600" />
                    Qué hacer hoy
                </h2>
                <ol class="space-y-2 text-sm">
                    <li class="flex items-start gap-2">
                        <span class="font-medium text-gray-500 w-6">1.</span>
                        <span v-if="(metrics?.pending_to_settle_this_month || 0) > 0">
                            <strong>Liquidar las {{ metrics.pending_to_settle_this_month }} planilla(s) del mes</strong> (botón "Liquidar planillas" abajo o
                            <Link :href="`/payrolls?status=PENDING&year=${current_year}&month=${current_month}`" class="text-brand-600 hover:underline">ver listado</Link>).
                        </span>
                        <span v-else class="text-gray-600">No hay planillas pendientes de liquidar este mes.</span>
                    </li>
                    <li class="flex items-start gap-2">
                        <span class="font-medium text-gray-500 w-6">2.</span>
                        <span v-if="(due_today_count || 0) > 0">
                            <strong>Revisar las {{ due_today_count }} que vencen hoy</strong>
                            <Link :href="`/payrolls?due_date=${todayStr}`" class="text-brand-600 hover:underline ml-1">Ver listado</Link>.
                        </span>
                        <span v-else class="text-gray-600">Ninguna planilla vence hoy.</span>
                    </li>
                    <li class="flex items-start gap-2">
                        <span class="font-medium text-gray-500 w-6">3.</span>
                        <span v-if="(metrics?.overdue_count || 0) > 0">
                            <strong>Revisar las {{ metrics.overdue_count }} en mora</strong>
                            <Link href="/payrolls?status=OVERDUE" class="text-red-600 hover:underline ml-1">Ver listado</Link>.
                        </span>
                        <span v-else class="text-gray-600">Ninguna planilla en mora.</span>
                    </li>
                </ol>
            </div>

            <!-- Métricas -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-6 gap-4">
                <div class="bg-white rounded-xl border border-gray-200 p-4 shadow-sm">
                    <div class="flex items-center gap-3">
                        <div class="h-10 w-10 rounded-lg bg-brand-100 flex items-center justify-center">
                            <Users class="h-5 w-5 text-brand-600" />
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-500">Afiliados con perfil SS</p>
                            <p class="text-xl font-bold text-gray-900">{{ metrics?.affiliates_with_profile ?? 0 }}</p>
                        </div>
                    </div>
                </div>
                <div class="bg-white rounded-xl border border-gray-200 p-4 shadow-sm">
                    <div class="flex items-center gap-3">
                        <div class="h-10 w-10 rounded-lg bg-slate-100 flex items-center justify-center">
                            <ListTodo class="h-5 w-5 text-slate-600" />
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-500">Por liquidar (este mes)</p>
                            <p class="text-xl font-bold text-gray-900">{{ metrics?.pending_to_settle_this_month ?? 0 }}</p>
                        </div>
                    </div>
                </div>
                <div class="bg-white rounded-xl border border-gray-200 p-4 shadow-sm">
                    <div class="flex items-center gap-3">
                        <div class="h-10 w-10 rounded-lg bg-red-100 flex items-center justify-center">
                            <AlertCircle class="h-5 w-5 text-red-600" />
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-500">En mora</p>
                            <p class="text-xl font-bold text-gray-900">{{ metrics?.overdue_count ?? 0 }}</p>
                        </div>
                    </div>
                </div>
                <div class="bg-white rounded-xl border border-gray-200 p-4 shadow-sm">
                    <div class="flex items-center gap-3">
                        <div class="h-10 w-10 rounded-lg bg-green-100 flex items-center justify-center">
                            <TrendingUp class="h-5 w-5 text-green-600" />
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-500">Pagadas este mes</p>
                            <p class="text-xl font-bold text-gray-900">{{ metrics?.paid_this_month ?? 0 }}</p>
                        </div>
                    </div>
                </div>
                <div class="bg-white rounded-xl border border-gray-200 p-4 shadow-sm">
                    <div class="flex items-center gap-3">
                        <div class="h-10 w-10 rounded-lg bg-amber-100 flex items-center justify-center">
                            <FileText class="h-5 w-5 text-amber-600" />
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-500">Planillas pendientes (total)</p>
                            <p class="text-xl font-bold text-gray-900">{{ metrics?.pending_count ?? 0 }}</p>
                        </div>
                    </div>
                </div>
                <div class="bg-white rounded-xl border border-gray-200 p-4 shadow-sm">
                    <div class="flex items-center gap-3">
                        <div class="h-10 w-10 rounded-lg bg-red-50 flex items-center justify-center">
                            <AlertCircle class="h-5 w-5 text-red-500" />
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-500">Monto en mora</p>
                            <p class="text-lg font-bold text-gray-900">{{ formatMoney(metrics?.total_overdue_amount) }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Listas (Vencen hoy / Próximos 7 / En mora) -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
                    <div class="px-4 py-3 border-b border-gray-200 bg-amber-50 flex items-center justify-between">
                        <h2 class="font-semibold text-gray-900 flex items-center gap-2">
                            <CalendarClock class="h-5 w-5 text-amber-600" /> Vencen hoy
                        </h2>
                        <Link v-if="(due_today_count || 0) > (due_today?.length || 0)" :href="`/payrolls?due_date=${todayStr}`" class="text-sm font-medium text-amber-700 hover:underline">
                            Ver todas ({{ due_today_count }})
                        </Link>
                    </div>
                    <ul class="divide-y divide-gray-100 max-h-64 overflow-y-auto">
                        <li v-for="p in (due_today || [])" :key="p.id" class="px-4 py-2 hover:bg-gray-50">
                            <Link :href="`/payrolls/${p.id}`" class="block">
                                <span class="font-medium text-gray-900">{{ p.affiliate?.full_name ?? '—' }}</span>
                                <span class="text-sm text-gray-500 block">{{ p.payer_name ?? '—' }}</span>
                                <span class="text-xs rounded px-1.5 py-0.5" :class="statusBadgeClass(p.status)">{{ statusLabel(p.status) }}</span>
                                <span class="text-sm text-gray-600">{{ formatMoney(p.total_amount) }}</span>
                            </Link>
                        </li>
                        <li v-if="!(due_today?.length)" class="px-4 py-4 text-sm text-gray-500">Ninguna planilla vence hoy</li>
                    </ul>
                </div>
                <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
                    <div class="px-4 py-3 border-b border-gray-200 bg-blue-50 flex items-center justify-between">
                        <h2 class="font-semibold text-gray-900 flex items-center gap-2">
                            <CalendarClock class="h-5 w-5 text-blue-600" /> Próximos 7 días
                        </h2>
                        <Link v-if="(due_next_7_count || 0) > (due_next_7?.length || 0)" href="/payrolls" class="text-sm font-medium text-blue-700 hover:underline">
                            Ver todas ({{ due_next_7_count }})
                        </Link>
                    </div>
                    <ul class="divide-y divide-gray-100 max-h-64 overflow-y-auto">
                        <li v-for="p in (due_next_7 || [])" :key="p.id" class="px-4 py-2 hover:bg-gray-50">
                            <Link :href="`/payrolls/${p.id}`" class="block">
                                <span class="font-medium text-gray-900">{{ p.affiliate?.full_name ?? '—' }}</span>
                                <span class="text-sm text-gray-500">Vence {{ p.due_date }}</span>
                                <span class="text-xs rounded px-1.5 py-0.5" :class="statusBadgeClass(p.status)">{{ statusLabel(p.status) }}</span>
                            </Link>
                        </li>
                        <li v-if="!(due_next_7?.length)" class="px-4 py-4 text-sm text-gray-500">Ninguna en los próximos 7 días</li>
                    </ul>
                </div>
                <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
                    <div class="px-4 py-3 border-b border-gray-200 bg-red-50 flex items-center justify-between">
                        <h2 class="font-semibold text-gray-900 flex items-center gap-2">
                            <AlertCircle class="h-5 w-5 text-red-600" /> En mora
                        </h2>
                        <Link v-if="(metrics?.overdue_count || 0) > (overdue_list?.length || 0)" href="/payrolls?status=OVERDUE" class="text-sm font-medium text-red-700 hover:underline">
                            Ver todas ({{ metrics?.overdue_count ?? 0 }})
                        </Link>
                    </div>
                    <ul class="divide-y divide-gray-100 max-h-64 overflow-y-auto">
                        <li v-for="p in (overdue_list || [])" :key="p.id" class="px-4 py-2 hover:bg-gray-50">
                            <Link :href="`/payrolls/${p.id}`" class="block">
                                <span class="font-medium text-gray-900">{{ p.affiliate?.full_name ?? '—' }}</span>
                                <span class="text-sm text-gray-500">{{ p.payer_name ?? '—' }}</span>
                                <span class="text-xs rounded px-1.5 py-0.5" :class="statusBadgeClass(p.status)">{{ statusLabel(p.status) }}</span>
                                <span class="text-sm text-red-600">{{ formatMoney(p.total_amount) }}</span>
                            </Link>
                        </li>
                        <li v-if="!(overdue_list?.length)" class="px-4 py-4 text-sm text-gray-500">Ninguna en mora</li>
                    </ul>
                </div>
            </div>

            <!-- Generar planillas del mes -->
            <div class="bg-white rounded-xl border border-gray-200 p-4 shadow-sm">
                <h2 class="text-lg font-semibold text-gray-900 mb-3">Generar planillas del mes</h2>
                <div class="flex flex-wrap items-end gap-3">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Año</label>
                        <input v-model.number="batchYear" type="number" min="2020" max="2100" class="rounded-lg border-gray-300 shadow-sm w-24 focus:border-brand-500 focus:ring-brand-500" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Mes</label>
                        <select v-model.number="batchMonth" class="rounded-lg border-gray-300 shadow-sm min-w-[140px] focus:border-brand-500 focus:ring-brand-500">
                            <option v-for="m in monthOptions" :key="m.value" :value="m.value">{{ m.label }}</option>
                        </select>
                    </div>
                    <button
                        type="button"
                        :disabled="batchLoading"
                        @click="runBatchGenerate"
                        class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-brand-500 text-white hover:bg-brand-600 disabled:opacity-50"
                    >
                        <Loader2 v-if="batchLoading" class="h-4 w-4 animate-spin" />
                        <RefreshCw v-else class="h-4 w-4" />
                        {{ batchLoading ? 'Generando...' : 'Generar planillas' }}
                    </button>
                </div>
                <div v-if="batchResult" class="mt-3 p-3 rounded-lg bg-gray-50 text-sm">
                    <span class="font-medium">Resultado:</span>
                    creadas {{ batchResult.created }}, omitidas {{ batchResult.skipped }}
                    <span v-if="Object.keys(batchResult.errors || {}).length">; errores: {{ Object.keys(batchResult.errors).length }}</span>
                    <details v-if="batchResult.errors && Object.keys(batchResult.errors).length" class="mt-2">
                        <summary class="cursor-pointer text-brand-600">Ver errores</summary>
                        <ul class="mt-1 list-disc list-inside text-gray-600">
                            <li v-for="(msg, id) in batchResult.errors" :key="id">Afiliado {{ id }}: {{ msg }}</li>
                        </ul>
                    </details>
                </div>
            </div>

            <!-- Liquidar planillas del mes -->
            <div class="bg-white rounded-xl border border-gray-200 p-4 shadow-sm">
                <h2 class="text-lg font-semibold text-gray-900 mb-3 flex items-center gap-2">
                    <CheckCircle class="h-5 w-5 text-blue-600" />
                    Liquidar planillas del mes
                </h2>
                <p class="text-sm text-gray-500 mb-3">Liquida (calcula montos y pasa a estado Liquidada) todas las planillas <strong>pendientes</strong> del período seleccionado.</p>
                <div class="flex flex-wrap items-end gap-3">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Año</label>
                        <input v-model.number="settleYear" type="number" min="2020" max="2100" class="rounded-lg border-gray-300 shadow-sm w-24 focus:border-brand-500 focus:ring-brand-500" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Mes</label>
                        <select v-model.number="settleMonth" class="rounded-lg border-gray-300 shadow-sm min-w-[140px] focus:border-brand-500 focus:ring-brand-500">
                            <option v-for="m in monthOptions" :key="m.value" :value="m.value">{{ m.label }}</option>
                        </select>
                    </div>
                    <button
                        type="button"
                        :disabled="settleLoading"
                        @click="runBatchSettle"
                        class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-blue-600 text-white hover:bg-blue-700 disabled:opacity-50"
                    >
                        <Loader2 v-if="settleLoading" class="h-4 w-4 animate-spin" />
                        <CheckCircle v-else class="h-4 w-4" />
                        {{ settleLoading ? 'Liquidando...' : 'Liquidar planillas' }}
                    </button>
                </div>
                <div v-if="settleResult" class="mt-3 p-3 rounded-lg bg-blue-50 text-sm">
                    <span class="font-medium">Resultado:</span>
                    liquidadas {{ settleResult.settled }}
                    <span v-if="Object.keys(settleResult.errors || {}).length">; errores: {{ Object.keys(settleResult.errors).length }}</span>
                    <details v-if="settleResult.errors && Object.keys(settleResult.errors).length" class="mt-2">
                        <summary class="cursor-pointer text-blue-600">Ver errores</summary>
                        <ul class="mt-1 list-disc list-inside text-gray-600">
                            <li v-for="(msg, id) in settleResult.errors" :key="id">Planilla {{ id }}: {{ msg }}</li>
                        </ul>
                    </details>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
