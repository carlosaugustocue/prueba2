<script setup>
import { ref, watch } from 'vue';
import { useForm, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import DatePicker from '@/Components/DatePicker.vue';
import axios from 'axios';
import { ChevronLeft, FileCheck, Search, Loader2, X, Check, Building2 } from 'lucide-vue-next';

const props = defineProps({
    epsList: Array,
    affiliates: Array,
    preselectedAffiliate: Object,
    appointment_request_id: Number,
});

const form = useForm({
    appointment_request_id: props.appointment_request_id || null,
    affiliate_id: props.preselectedAffiliate?.id ? String(props.preselectedAffiliate.id) : '',
    eps_id: props.preselectedAffiliate?.eps_id ? String(props.preselectedAffiliate.eps_id) : '',
    service_type: '',
    diagnosis_or_reason: '',
    radicated_at: '',
});

// Búsqueda de afiliados (solo con Serviconli como pagador) — igual que en Nueva solicitud
const affiliateSearch = ref('');
const affiliateResults = ref([]);
const selectedAffiliate = ref(props.preselectedAffiliate || null);
const isSearching = ref(false);
const showNoResults = ref(false);

const isAffiliateActive = (affiliate) => {
    const s = (affiliate?.status || '').toString().toUpperCase();
    return s === '' || s === 'ACTIVO';
};

const affiliateStatusBadge = (status) => {
    const s = (status || '').toString().toUpperCase();
    if (s === 'ACTIVO') return { label: 'Activo', class: 'bg-green-100 text-green-800' };
    if (s === 'INACTIVO') return { label: 'Inactivo', class: 'bg-red-100 text-red-800' };
    if (s === 'SUSPENDIDO') return { label: 'Suspendido', class: 'bg-amber-100 text-amber-800' };
    return { label: status || 'Sin estado', class: 'bg-gray-100 text-gray-700' };
};

const searchAffiliates = async () => {
    if (affiliateSearch.value.length < 2) {
        affiliateResults.value = [];
        showNoResults.value = false;
        return;
    }
    isSearching.value = true;
    showNoResults.value = false;
    try {
        const response = await axios.get('/api/affiliates/search', {
            params: { term: affiliateSearch.value, serviconli_only: 1 },
        });
        affiliateResults.value = response.data.data || response.data || [];
        showNoResults.value = affiliateResults.value.length === 0;
    } catch (error) {
        console.error(error);
        affiliateResults.value = [];
    } finally {
        isSearching.value = false;
    }
};

const selectAffiliate = (affiliate) => {
    selectedAffiliate.value = affiliate;
    form.affiliate_id = String(affiliate.id);
    const epsId = affiliate.eps?.id ?? affiliate.eps_id ?? affiliate.socialSecurityProfile?.eps_id;
    if (epsId) form.eps_id = String(epsId);
    affiliateSearch.value = '';
    affiliateResults.value = [];
    showNoResults.value = false;
};

const clearAffiliate = () => {
    selectedAffiliate.value = null;
    form.affiliate_id = '';
    form.eps_id = '';
};

let searchTimeout;
watch(affiliateSearch, () => {
    if (!selectedAffiliate.value && affiliateSearch.value.length >= 2) {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(searchAffiliates, 400);
    } else if (affiliateSearch.value.length < 2) {
        affiliateResults.value = [];
        showNoResults.value = false;
    }
});

// Si hay preselección, sincronizar al cargar
watch(() => props.preselectedAffiliate, (aff) => {
    if (aff && !selectedAffiliate.value) {
        selectedAffiliate.value = aff;
    }
}, { immediate: true });

</script>

<template>
    <AppLayout>
        <div class="max-w-3xl mx-auto space-y-6">
            <Link href="/authorizations" class="inline-flex items-center text-sm font-medium text-gray-500 hover:text-gray-700">
                <ChevronLeft class="h-4 w-4 mr-1" />
                Volver a autorizaciones
            </Link>
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Nueva autorización médica</h1>
                <p class="mt-1 text-sm text-gray-500">Registre la autorización asociada a la solicitud de cita o al afiliado.</p>
            </div>

            <form @submit.prevent="form.post('/authorizations')" class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                <div class="p-6 space-y-6">
                    <input v-if="form.appointment_request_id" type="hidden" :value="form.appointment_request_id" name="appointment_request_id" />

                    <!-- Afiliado * (búsqueda como en Nueva solicitud) -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Afiliado *</label>
                        <div v-if="selectedAffiliate" class="rounded-xl p-4 border bg-brand-50 border-brand-200">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-4">
                                    <div class="h-12 w-12 rounded-full flex items-center justify-center bg-brand-100">
                                        <span class="text-brand-700 font-bold">{{ selectedAffiliate.first_name?.charAt(0) }}{{ selectedAffiliate.last_name?.charAt(0) }}</span>
                                    </div>
                                    <div>
                                        <div class="flex items-center gap-2 flex-wrap">
                                            <p class="font-semibold text-gray-900">{{ selectedAffiliate.full_name }}</p>
                                            <span :class="['inline-flex px-2 py-0.5 rounded text-xs font-medium', affiliateStatusBadge(selectedAffiliate.status).class]">
                                                {{ affiliateStatusBadge(selectedAffiliate.status).label }}
                                            </span>
                                        </div>
                                        <p class="text-sm text-gray-600">{{ selectedAffiliate.document_type_abbreviation }} {{ selectedAffiliate.document_number }}</p>
                                        <p class="text-sm text-gray-500">{{ selectedAffiliate.eps?.name || '—' }}</p>
                                    </div>
                                </div>
                                <button type="button" @click="clearAffiliate" class="flex items-center gap-2 px-3 py-2 text-sm text-red-600 hover:bg-red-50 rounded-lg transition-colors">
                                    <X class="h-4 w-4" />
                                    Cambiar
                                </button>
                            </div>
                        </div>
                        <div v-else class="space-y-3">
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                    <Loader2 v-if="isSearching" class="h-5 w-5 text-brand-500 animate-spin" />
                                    <Search v-else class="h-5 w-5 text-gray-400" />
                                </div>
                                <input
                                    v-model="affiliateSearch"
                                    type="text"
                                    placeholder="Buscar por nombre o documento..."
                                    class="block w-full pl-12 pr-4 py-3 rounded-xl border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500"
                                />
                            </div>
                            <div v-if="affiliateResults.length > 0" class="bg-white border border-gray-200 rounded-xl shadow-lg max-h-64 overflow-auto">
                                <button
                                    v-for="affiliate in affiliateResults"
                                    :key="affiliate.id"
                                    type="button"
                                    @click="selectAffiliate(affiliate)"
                                    class="w-full px-4 py-3 text-left border-b last:border-0 flex items-center gap-3 transition-colors hover:bg-brand-50"
                                >
                                    <div class="h-10 w-10 rounded-full flex items-center justify-center flex-shrink-0 bg-gray-100">
                                        <span class="text-gray-600 font-medium">{{ affiliate.first_name?.charAt(0) }}{{ affiliate.last_name?.charAt(0) }}</span>
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <div class="flex items-center gap-2 flex-wrap">
                                            <p class="font-medium text-gray-900 truncate">{{ affiliate.full_name }}</p>
                                            <span :class="['inline-flex px-2 py-0.5 rounded text-xs font-medium flex-shrink-0', affiliateStatusBadge(affiliate.status).class]">
                                                {{ affiliateStatusBadge(affiliate.status).label }}
                                            </span>
                                        </div>
                                        <p class="text-sm text-gray-500">{{ affiliate.document_type_abbreviation }} {{ affiliate.document_number }}</p>
                                        <p class="text-xs text-gray-500">{{ affiliate.eps?.name || '—' }}</p>
                                    </div>
                                    <Check class="h-5 w-5 text-brand-500 flex-shrink-0" />
                                </button>
                            </div>
                            <p v-if="showNoResults" class="text-sm text-amber-600 text-center py-2">
                                No se encontraron afiliados con Serviconli como pagador. Verifique que el afiliado tenga perfil SS con pagador Serviconli.
                            </p>
                        </div>
                        <p v-if="form.errors.affiliate_id" class="mt-2 text-sm text-red-600">{{ form.errors.affiliate_id }}</p>
                    </div>

                    <!-- EPS: se toma del perfil del afiliado (ya configurada) -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700">EPS</label>
                        <div v-if="selectedAffiliate" class="mt-1 flex items-center gap-2 rounded-lg border border-gray-200 bg-gray-50 px-3 py-2.5">
                            <Building2 class="h-5 w-5 text-gray-400" />
                            <span class="text-gray-900 font-medium">{{ selectedAffiliate.eps?.name || 'Sin EPS configurada' }}</span>
                            <span v-if="!selectedAffiliate.eps?.id && !selectedAffiliate.eps_id" class="text-amber-600 text-sm">Configure la EPS del afiliado en su ficha</span>
                        </div>
                        <p v-else class="mt-1 text-gray-500 italic">Seleccione un afiliado; la EPS se tomará de su perfil de seguridad social.</p>
                        <p v-if="form.errors.eps_id" class="mt-1 text-sm text-red-600">{{ form.errors.eps_id }}</p>
                        <p v-if="form.errors.affiliate_id && form.errors.affiliate_id.includes('EPS')" class="mt-1 text-sm text-red-600">{{ form.errors.affiliate_id }}</p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Tipo de servicio *</label>
                        <input
                            :value="form.service_type"
                            @input="form.service_type = ($event.target.value || '').toUpperCase()"
                            type="text"
                            placeholder="Ej. CONSULTA ESPECIALIZADA, PROCEDIMIENTO, EXAMEN"
                            required
                            maxlength="100"
                            class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500 uppercase"
                        />
                        <p v-if="form.errors.service_type" class="mt-1 text-sm text-red-600">{{ form.errors.service_type }}</p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Diagnóstico o motivo</label>
                        <textarea
                            v-model="form.diagnosis_or_reason"
                            rows="3"
                            class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500"
                        />
                        <p v-if="form.errors.diagnosis_or_reason" class="mt-1 text-sm text-red-600">{{ form.errors.diagnosis_or_reason }}</p>
                    </div>

                    <DatePicker
                        v-model="form.radicated_at"
                        label="Fecha de radicación ante la EPS"
                        hint="Opcional. Seleccione la fecha si ya radicó la autorización ante la EPS."
                    />
                    <p v-if="form.errors.radicated_at" class="mt-1 text-sm text-red-600">{{ form.errors.radicated_at }}</p>
                </div>
                <div class="px-6 py-4 bg-gray-50 border-t border-gray-200 flex justify-end gap-3">
                    <Link href="/authorizations" class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50">
                        Cancelar
                    </Link>
                    <button
                        type="submit"
                        :disabled="form.processing || (selectedAffiliate && !selectedAffiliate.eps?.id && !selectedAffiliate.eps_id)"
                        class="inline-flex items-center px-4 py-2 font-medium rounded-lg bg-brand-500 text-white hover:bg-brand-600 disabled:opacity-50"
                    >
                        <FileCheck class="h-4 w-4 mr-2" v-if="!form.processing" />
                        <span v-if="form.processing">Guardando...</span>
                        <span v-else>Registrar autorización</span>
                    </button>
                </div>
            </form>
        </div>
    </AppLayout>
</template>
