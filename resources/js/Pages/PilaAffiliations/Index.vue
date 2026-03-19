<script setup>
import { ref, watch, computed } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Pagination from '@/Components/Pagination.vue';
import { Search, Eye, Pencil, UserPlus, Filter, X, Loader2, Download } from 'lucide-vue-next';

const props = defineProps({
    affiliations: Object,
    filters: Object,
    filterOptions: Object,
});

const search = ref(props.filters?.search ?? '');
const payment_status = ref(props.filters?.payment_status ?? '');
const pila_operator = ref(props.filters?.pila_operator ?? '');
const employer_id = ref(props.filters?.employer_id ?? '');
const payment_business_day = ref(props.filters?.payment_business_day ?? '');
const eps_id = ref(props.filters?.eps_id ?? '');
const afp_id = ref(props.filters?.afp_id ?? '');
const arp_id = ref(props.filters?.arp_id ?? '');
const ccf_id = ref(props.filters?.ccf_id ?? '');
const last_payment_period = ref(props.filters?.last_payment_period ?? '');

const showFilters = ref(false);
const opts = computed(() => props.filterOptions ?? {});

const buildParams = () => {
    const p = {};
    if (search.value) p.search = search.value;
    if (payment_status.value) p.payment_status = payment_status.value;
    if (pila_operator.value) p.pila_operator = pila_operator.value;
    if (employer_id.value) p.employer_id = employer_id.value;
    if (payment_business_day.value) p.payment_business_day = payment_business_day.value;
    if (eps_id.value) p.eps_id = eps_id.value;
    if (afp_id.value) p.afp_id = afp_id.value;
    if (arp_id.value) p.arp_id = arp_id.value;
    if (ccf_id.value) p.ccf_id = ccf_id.value;
    if (last_payment_period.value) p.last_payment_period = last_payment_period.value;
    return p;
};

const clearFilters = () => {
    search.value = '';
    payment_status.value = '';
    pila_operator.value = '';
    employer_id.value = '';
    payment_business_day.value = '';
    eps_id.value = '';
    afp_id.value = '';
    arp_id.value = '';
    ccf_id.value = '';
    last_payment_period.value = '';
    applyFilters();
};

const hasActiveFilters = computed(() => payment_status.value || pila_operator.value || employer_id.value || payment_business_day.value || eps_id.value || afp_id.value || arp_id.value || ccf_id.value || last_payment_period.value);

let searchDebounce;
const searchLoading = ref(false);
let requestFromSearch = false;

const exportBusy = ref(false);

const applyFiltersWithSource = (fromSearch) => {
    requestFromSearch = fromSearch;
    router.get('/pila/affiliations', buildParams(), {
        preserveState: true,
        replace: true,
        onStart: () => {
            if (requestFromSearch) searchLoading.value = true;
        },
        onFinish: () => {
            if (requestFromSearch) searchLoading.value = false;
        },
    });
};

const applyFilters = () => applyFiltersWithSource(false);

watch(search, () => {
    clearTimeout(searchDebounce);
    searchDebounce = setTimeout(() => applyFiltersWithSource(true), 350);
});
watch([payment_status, pila_operator, employer_id, payment_business_day, eps_id, afp_id, arp_id, ccf_id, last_payment_period], () => {
    applyFilters();
});

const paymentStatusBadge = (status) => {
    if (!status) return { class: 'bg-gray-100 text-gray-700', label: '—' };
    const map = { current: { class: 'bg-green-100 text-green-800', label: 'Al día' }, overdue: { class: 'bg-red-100 text-red-800', label: 'En mora' }, anticipated: { class: 'bg-blue-100 text-blue-800', label: 'Anticipado' } };
    return map[status] || { class: 'bg-gray-100 text-gray-700', label: status };
};

const escapeHtml = (s) => String(s ?? '')
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&#039;');

const escapeRegExp = (s) => String(s ?? '').replace(/[.*+?^${}()|[\]\\]/g, '\\$&');

const highlightMatch = (text, searchTerm) => {
    const rawText = String(text ?? '');
    const q = String(searchTerm ?? '').trim();
    if (!q) return escapeHtml(rawText);

    const terms = q.split(/\s+/).map((t) => t.trim()).filter(Boolean);
    if (!terms.length) return escapeHtml(rawText);

    const pattern = terms.map(escapeRegExp).join('|');
    const regex = new RegExp(`(${pattern})`, 'ig');

    let html = '';
    let lastIndex = 0;
    for (const m of rawText.matchAll(regex)) {
        if (m.index === undefined) continue;
        const start = m.index;
        const matchText = m[0] ?? '';

        html += escapeHtml(rawText.slice(lastIndex, start));
        html += `<span class="bg-[#E1F5EE] text-[#085041] font-semibold rounded px-0.5">${escapeHtml(matchText)}</span>`;
        lastIndex = start + matchText.length;
    }
    html += escapeHtml(rawText.slice(lastIndex));
    return html;
};

const exportToExcel = () => {
    if (exportBusy.value) return;
    exportBusy.value = true;

    const params = buildParams();
    const qs = new URLSearchParams(params).toString();
    const url = `/pila/affiliations/export${qs ? `?${qs}` : ''}`;

    // Descarga directa del navegador (stream response).
    window.location.href = url;

    // El flujo navega/descarga, pero dejamos la bandera por si el navegador bloquea.
    setTimeout(() => {
        exportBusy.value = false;
    }, 1500);
};
</script>

<template>
    <AppLayout>
        <div class="space-y-6">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">Afiliaciones PILA</h1>
                    <p class="mt-1 text-sm text-gray-500">Panel operativo: tipo cotizante, entidades, estado de pago</p>
                </div>
                    <div class="flex items-center gap-3">
                        <button
                            type="button"
                            class="inline-flex items-center justify-center px-4 py-2 font-medium rounded-lg bg-white text-gray-700 border border-gray-200 hover:bg-gray-50 disabled:opacity-60"
                            :disabled="exportBusy"
                            @click="exportToExcel"
                        >
                            <Download class="h-5 w-5 mr-2" />
                            Exportar
                        </button>
                        <Link href="/pila/affiliations/create" class="inline-flex items-center justify-center px-4 py-2 font-medium rounded-lg bg-brand-500 text-white hover:bg-brand-600">
                            <UserPlus class="h-5 w-5 mr-2" />
                            Nueva afiliación
                        </Link>
                    </div>
            </div>

            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4 space-y-4">
                <div class="flex flex-wrap items-center gap-3">
                    <div class="relative flex-1 min-w-[200px]">
                        <Search class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-gray-400" />
                        <Loader2
                            v-if="searchLoading"
                            class="absolute right-3 top-1/2 -translate-y-1/2 h-4 w-4 text-gray-400 animate-spin"
                        />
                        <input v-model="search" type="text" placeholder="Buscar por nombre o documento..." class="block w-full pl-10 rounded-lg border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500" />
                    </div>
                    <button type="button" @click="showFilters = !showFilters" class="inline-flex items-center gap-2 px-3 py-2 rounded-lg border border-gray-300 text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                        <Filter class="h-4 w-4" />
                        Filtros
                        <span v-if="hasActiveFilters" class="inline-flex items-center justify-center w-5 h-5 text-xs rounded-full bg-brand-100 text-brand-700">{{ [payment_status, pila_operator, employer_id, payment_business_day, eps_id, afp_id, arp_id, ccf_id, last_payment_period].filter(Boolean).length }}</span>
                    </button>
                    <button v-if="hasActiveFilters" type="button" @click="clearFilters" class="inline-flex items-center gap-2 px-3 py-2 rounded-lg border border-gray-300 text-sm text-gray-600 hover:bg-gray-50">
                        <X class="h-4 w-4" />
                        Limpiar
                    </button>
                </div>

                <div v-show="showFilters" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 pt-3 border-t border-gray-200">
                    <div>
                        <label class="block text-xs font-medium text-gray-500 mb-1">Estado pago</label>
                        <select v-model="payment_status" class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500 text-sm">
                            <option value="">Todos</option>
                            <option v-for="s in opts.payment_statuses" :key="s.value" :value="s.value">{{ s.label }}</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-500 mb-1">Operador PILA</label>
                        <select v-model="pila_operator" class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500 text-sm">
                            <option value="">Todos</option>
                            <option v-for="o in opts.pila_operators" :key="o.value" :value="o.value">{{ o.label }}</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-500 mb-1">Día hábil</label>
                        <select v-model="payment_business_day" class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500 text-sm">
                            <option value="">Todos</option>
                            <option v-for="d in opts.payment_business_days" :key="d.value" :value="d.value">{{ d.label }}</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-500 mb-1">Empleador</label>
                        <select v-model="employer_id" class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500 text-sm">
                            <option value="">Todos</option>
                            <option v-for="e in opts.employers" :key="e.id" :value="e.id">{{ e.label }}</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-500 mb-1">EPS</label>
                        <select v-model="eps_id" class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500 text-sm">
                            <option value="">Todas</option>
                            <option v-for="e in opts.epsOptions" :key="e.id" :value="e.id">{{ e.label }}</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-500 mb-1">AFP</label>
                        <select v-model="afp_id" class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500 text-sm">
                            <option value="">Todas</option>
                            <option v-for="e in opts.afpOptions" :key="e.id" :value="e.id">{{ e.label }}</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-500 mb-1">ARL</label>
                        <select v-model="arp_id" class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500 text-sm">
                            <option value="">Todas</option>
                            <option v-for="e in opts.arpOptions" :key="e.id" :value="e.id">{{ e.label }}</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-500 mb-1">CCF</label>
                        <select v-model="ccf_id" class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500 text-sm">
                            <option value="">Todas</option>
                            <option v-for="e in opts.ccfOptions" :key="e.id" :value="e.id">{{ e.label }}</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-500 mb-1">Período (AAAAMM)</label>
                        <input v-model="last_payment_period" type="text" maxlength="6" placeholder="202603" class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500 text-sm" />
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                <div class="overflow-x-auto min-w-0">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Afiliado</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Empleador</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tipo</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Operador</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Día</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Fecha límite</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Estado pago</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Acciones</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <tr v-for="a in affiliations.data" :key="a.id" class="hover:bg-gray-50">
                                <td class="px-6 py-4">
                                    <div>
                                        <p
                                            class="font-medium text-gray-900"
                                            v-html="highlightMatch(a.affiliate?.full_name, search)"
                                        />
                                        <p
                                            class="text-sm text-gray-500"
                                            v-html="highlightMatch(`${a.affiliate?.document_type_abbreviation || ''} ${a.affiliate?.document_number || ''}`, search)"
                                        />
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-700">{{ a.employer?.name || '—' }}</td>
                                <td class="px-6 py-4 text-sm text-gray-700">{{ a.cotizante_type?.code || '—' }}</td>
                                <td class="px-6 py-4 text-sm text-gray-700">{{ a.pila_operator || '—' }}</td>
                                <td class="px-6 py-4 text-sm text-gray-700">{{ a.employer?.payment_business_day ?? '—' }}</td>
                                <td class="px-6 py-4 text-sm text-gray-700">
                                    <template v-if="a.next_due_date">
                                        {{ a.next_due_date }}
                                    </template>
                                    <template v-else>
                                        <span class="text-gray-500 italic">Sin registrar</span>
                                    </template>
                                </td>
                                <td class="px-6 py-4">
                                    <span :class="['inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium', paymentStatusBadge(a.payment_status).class]">
                                        {{ paymentStatusBadge(a.payment_status).label }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-right space-x-2">
                                    <Link :href="`/pila/affiliations/${a.id}`" class="inline-flex items-center justify-center h-9 w-9 rounded-lg text-gray-400 hover:text-gray-700 hover:bg-gray-100 transition-colors" aria-label="Ver">
                                        <Eye class="h-4 w-4" />
                                    </Link>
                                    <Link :href="`/pila/affiliations/${a.id}/edit`" class="inline-flex items-center justify-center h-9 w-9 rounded-lg text-gray-400 hover:text-brand-700 hover:bg-brand-50 transition-colors" aria-label="Editar">
                                        <Pencil class="h-4 w-4" />
                                    </Link>
                                </td>
                            </tr>
                            <tr v-if="!affiliations.data?.length">
                                <td colspan="7" class="px-6 py-12 text-center text-gray-500">No se encontraron afiliaciones</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <div v-if="affiliations?.links?.length" class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                <p v-if="affiliations?.meta" class="text-sm text-gray-500">
                    Mostrando {{ affiliations.meta.from ?? 0 }} a {{ affiliations.meta.to ?? 0 }} de {{ affiliations.meta.total ?? 0 }} resultados
                </p>
                <Pagination :links="affiliations?.links" />
            </div>
        </div>
    </AppLayout>
</template>

