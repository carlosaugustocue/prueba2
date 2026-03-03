<script setup>
import { computed, ref } from 'vue';
import { Link, useForm, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import DatePicker from '@/Components/DatePicker.vue';
import {
    ChevronLeft, CalendarPlus, Pencil, Phone, Mail, MapPin,
    User, Users, Heart, UserPlus, Calendar, ArrowRight,
    MessageSquare, Building2, CalendarClock, FileText, FileCheck, Plus, X,
    Key, Paperclip, Download, Trash2, Loader2, Eye, EyeOff, Copy
} from 'lucide-vue-next';
import { confirmDialog } from '@/Utils/swal';

const props = defineProps({
    affiliate: Object,
    pila_next_due_date: String,
    pila_next_due_label: String,
    pila_next_due_is_soon: Boolean,
    payments_up_to_date: { type: Boolean, default: null },
    noveltyTypes: { type: Array, default: () => [] },
    operatorCredentialProviderLabels: { type: Object, default: () => ({}) },
});

const showNoveltyModal = ref(false);
const noveltyForm = useForm({
    novelty_type_id: '',
    effective_date: new Date().toISOString().slice(0, 10),
    description: '',
    old_value: '',
    new_value: '',
});

function openNoveltyModal() {
    noveltyForm.reset();
    noveltyForm.effective_date = new Date().toISOString().slice(0, 10);
    showNoveltyModal.value = true;
}

const affiliateId = computed(() => props.affiliate?.id ?? props.affiliate?.data?.id);

function submitNovelty() {
    noveltyForm.post(`/affiliates/${affiliateId.value}/novelties`, {
        preserveScroll: true,
        onSuccess: () => {
            showNoveltyModal.value = false;
        },
    });
}

/** Calcula la edad en años a partir de una fecha Y-m-d (o null). */
function getAgeFromBirthDate(birthDateStr) {
    if (!birthDateStr) return null;
    const birth = new Date(birthDateStr);
    if (Number.isNaN(birth.getTime())) return null;
    const today = new Date();
    let age = today.getFullYear() - birth.getFullYear();
    const m = today.getMonth() - birth.getMonth();
    if (m < 0 || (m === 0 && today.getDate() < birth.getDate())) age--;
    return age >= 0 ? age : null;
}

const affiliate = computed(() => props.affiliate?.data || props.affiliate || {});
const affiliateAge = computed(() => getAgeFromBirthDate(affiliate.value?.birth_date));
const isHolder = computed(() => affiliate.value.is_holder);
const isBeneficiary = computed(() => affiliate.value.is_beneficiary);
const beneficiaries = computed(() => affiliate.value.beneficiaries || []);
const holder = computed(() => affiliate.value.holder);
const novelties = computed(() => affiliate.value.novelties || []);
const authorizations = computed(() => affiliate.value.authorizations || []);
const operatorCredentials = computed(() => affiliate.value.operator_credentials || []);
const supportDocuments = computed(() => affiliate.value.support_documents || []);
const payments = computed(() => affiliate.value.payments || []);

const providerLabels = computed(() => props.operatorCredentialProviderLabels || {});

const showCredentialModal = ref(false);
const credentialForm = useForm({
    provider_type: '',
    username: '',
    password: '',
});
const editingCredentialId = ref(null);

function openCredentialModal(credential = null) {
    editingCredentialId.value = credential?.id ?? null;
    credentialForm.reset();
    if (credential) {
        credentialForm.username = '';
        credentialForm.password = '';
        credentialForm.provider_type = credential.provider_type;
    } else {
        credentialForm.provider_type = 'PAYMENT_OPERATOR';
    }
    showCredentialModal.value = true;
}

function submitCredential() {
    const base = `/affiliates/${affiliateId.value}/operator-credentials`;
    if (editingCredentialId.value) {
        credentialForm.put(`${base}/${editingCredentialId.value}`, {
            preserveScroll: true,
            onSuccess: () => { showCredentialModal.value = false; },
        });
    } else {
        credentialForm.post(base, {
            preserveScroll: true,
            onSuccess: () => { showCredentialModal.value = false; },
        });
    }
}

function deleteCredential(cred) {
    confirmDialog({ title: 'Eliminar credencial', text: `¿Eliminar credencial de ${providerLabels.value[cred.provider_type] || cred.provider_type}?`, confirmButtonText: 'Eliminar', icon: 'warning' })
        .then((ok) => {
            if (!ok) return;
            router.delete(`/affiliates/${affiliateId.value}/operator-credentials/${cred.id}`, { preserveScroll: true });
        });
}

// Modal Ver credencial (usuario y contraseña)
const showViewCredentialModal = ref(false);
const viewCredentialLoading = ref(false);
const viewCredentialData = ref(null);
const viewCredentialCred = ref(null); // cred del listado (id, provider_type) para Editar
const viewCredentialShowPassword = ref(false);

function openViewCredentialModal(cred) {
    viewCredentialData.value = null;
    viewCredentialCred.value = cred;
    viewCredentialShowPassword.value = false;
    showViewCredentialModal.value = true;
    viewCredentialLoading.value = true;
    const url = `/affiliates/${affiliateId.value}/operator-credentials/${cred.id}`;
    fetch(url, { headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' } })
        .then((r) => r.json())
        .then((data) => {
            viewCredentialData.value = data;
        })
        .catch(() => {
            viewCredentialData.value = { provider_type: cred.provider_type, username: '—', password: '—' };
        })
        .finally(() => {
            viewCredentialLoading.value = false;
        });
}

function copyToClipboard(text) {
    if (!text) return;
    navigator.clipboard.writeText(text).catch(() => {});
}

const showSupportForm = ref(false);
const supportForm = useForm({
    title: '',
    document: null,
});

function submitSupportDocument() {
    const fd = new FormData();
    fd.append('title', supportForm.title);
    if (supportForm.document) fd.append('document', supportForm.document);
    router.post(`/affiliates/${affiliateId.value}/support-documents`, fd, {
        preserveScroll: true,
        forceFormData: true,
        onSuccess: () => {
            showSupportForm.value = false;
            supportForm.reset();
            supportForm.document = null;
        },
    });
}

function deleteSupportDoc(doc) {
    confirmDialog({ title: 'Eliminar documento', text: `¿Eliminar "${doc.title}"?`, confirmButtonText: 'Eliminar', icon: 'warning' })
        .then((ok) => {
            if (!ok) return;
            router.delete(`/affiliates/${affiliateId.value}/support-documents/${doc.id}`, { preserveScroll: true });
        });
}

function formatFileSize(bytes) {
    if (bytes == null) return '—';
    if (bytes < 1024) return bytes + ' B';
    if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(1) + ' KB';
    return (bytes / (1024 * 1024)).toFixed(1) + ' MB';
}
const isAffiliateActive = computed(() => {
    const s = (affiliate.value?.status || '').toString().toUpperCase();
    return s === '' || s === 'ACTIVO';
});
</script>

<template>
    <AppLayout>
        <div class="max-w-5xl mx-auto space-y-6">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <Link href="/affiliates" class="inline-flex items-center gap-2 text-sm text-gray-500 hover:text-brand-600 transition-colors">
                        <ChevronLeft class="h-4 w-4" />
                        Volver a afiliados
                    </Link>
                    <div class="flex items-center gap-2 mt-1">
                        <span :class="[
                            'inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-medium',
                            isHolder ? 'bg-brand-100 text-brand-700' : 'bg-purple-100 text-purple-700'
                        ]">
                            <User v-if="isHolder" class="h-3 w-3" />
                            <Heart v-else class="h-3 w-3" />
                            {{ affiliate.patient_type_label }}
                        </span>
                        <span class="text-gray-500">•</span>
                        <span class="text-gray-500 text-sm">{{ affiliate.eps?.name || 'Sin EPS' }}</span>
                    </div>
                </div>
                <div class="flex flex-wrap gap-3">
                    <Link v-if="isAffiliateActive" :href="`/appointments/create?affiliate_id=${affiliate.id}`" class="inline-flex items-center gap-2 px-4 py-2 bg-brand-500 text-white rounded-xl hover:bg-brand-600 transition-colors">
                        <CalendarPlus class="h-5 w-5" />
                        Nueva Cita
                    </Link>
                    <span v-else class="inline-flex items-center gap-2 px-4 py-2 bg-gray-100 text-gray-500 rounded-xl cursor-not-allowed" title="Afiliado inactivo o suspendido">
                        <CalendarPlus class="h-5 w-5" />
                        Nueva Cita (no disponible)
                    </span>
                    <Link :href="`/affiliates/${affiliate.id}/edit`" class="inline-flex items-center gap-2 px-4 py-2 bg-white border border-gray-300 text-gray-700 rounded-xl hover:bg-gray-50 transition-colors">
                        <Pencil class="h-4 w-4" />
                        Editar
                    </Link>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <div class="lg:col-span-2 space-y-6">
                    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                        <div class="flex items-start gap-6">
                            <div class="h-20 w-20 rounded-2xl bg-gradient-to-br from-brand-400 to-brand-600 flex items-center justify-center flex-shrink-0">
                                <span class="text-white font-bold text-3xl">{{ affiliate.first_name?.charAt(0) || '?' }}{{ affiliate.last_name?.charAt(0) || '' }}</span>
                            </div>
                            <div class="flex-1">
                                <h2 class="text-2xl font-bold text-gray-900">{{ affiliate.full_name || 'Sin nombre' }}</h2>
                                <p class="text-gray-600 mt-1">{{ affiliate.document_type_label || affiliate.document_type }} {{ affiliate.document_number }} <span class="text-gray-500">· Exp. {{ affiliate.document_issue_date_formatted || '—' }}</span></p>
                                <div class="flex flex-wrap gap-4 mt-4">
                                    <span v-if="affiliate.gender" class="text-sm text-gray-500">{{ affiliate.gender === 'M' ? 'Masculino' : affiliate.gender === 'F' ? 'Femenino' : affiliate.gender }}</span>
                                    <span v-if="affiliate.birth_date" class="text-sm text-gray-500">Nac. {{ affiliate.birth_date }}{{ affiliateAge != null ? ` (${affiliateAge} años)` : '' }}</span>
                                    <span v-if="affiliate.status" :class="[
                                        'inline-flex items-center px-2 py-0.5 rounded text-xs font-medium',
                                        affiliate.status === 'ACTIVO' ? 'bg-green-100 text-green-800' : '',
                                        affiliate.status === 'INACTIVO' ? 'bg-red-100 text-red-800' : '',
                                        affiliate.status === 'SUSPENDIDO' ? 'bg-amber-100 text-amber-800' : '',
                                        !['ACTIVO','INACTIVO','SUSPENDIDO'].includes(affiliate.status) ? 'bg-gray-100 text-gray-700' : ''
                                    ]">{{ affiliate.status }}</span>
                                </div>
                                <div class="flex flex-wrap gap-4 mt-2">
                                    <div v-if="affiliate.phone" class="flex items-center gap-2 text-gray-600">
                                        <Phone class="h-4 w-4 text-gray-400" />
                                        {{ affiliate.phone }}
                                    </div>
                                    <div v-if="affiliate.phone_2" class="flex items-center gap-2 text-gray-600">Tel 2: {{ affiliate.phone_2 }}</div>
                                    <div v-if="affiliate.whatsapp" class="flex items-center gap-2 text-gray-600">
                                        <MessageSquare class="h-4 w-4 text-green-500" />
                                        {{ affiliate.whatsapp }}
                                    </div>
                                    <div v-if="affiliate.email" class="flex items-center gap-2 text-gray-600">
                                        <Mail class="h-4 w-4 text-gray-400" />
                                        {{ affiliate.email }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- EPS y Seguridad Social (justo debajo de datos del afiliado) -->
                    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                        <div class="px-6 py-4 border-b border-gray-200 bg-gradient-to-r from-brand-50/80 to-white">
                            <h3 class="flex items-center gap-2 font-semibold text-gray-900">
                                <Building2 class="h-5 w-5 text-brand-600" />
                                EPS y Seguridad Social
                            </h3>
                            <p class="text-sm text-gray-500 mt-0.5">Datos de afiliación y aportes para el operador</p>
                        </div>

                        <div v-if="!affiliate.eps?.name && !affiliate.afp_name && !affiliate.arp_name && !affiliate.payer && affiliate.ibc == null && !affiliate.observations" class="p-8 text-center">
                            <Building2 class="h-12 w-12 mx-auto text-gray-300 mb-3" />
                            <p class="text-gray-500">Sin datos de seguridad social registrados.</p>
                        </div>

                        <template v-else>
                            <!-- Bloque 1: Afiliación (EPS; tipo cliente y cotizante solo para cotizantes) -->
                            <div class="px-6 py-4 border-b border-gray-100 bg-gray-50/50" :class="{ 'border-b-0': isBeneficiary && !affiliate.observations }">
                                <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-3">Afiliación</p>
                                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                                    <div class="bg-white rounded-lg px-4 py-3 border border-gray-100">
                                        <p class="text-xs text-gray-500 mb-0.5">EPS</p>
                                        <p class="font-semibold text-gray-900">{{ affiliate.eps?.name || '—' }}</p>
                                        <p v-if="affiliate.eps?.code" class="text-xs text-gray-500 mt-0.5">Cód. {{ affiliate.eps.code }}</p>
                                    </div>
                                    <template v-if="!isBeneficiary">
                                        <div class="bg-white rounded-lg px-4 py-3 border border-gray-100">
                                            <p class="text-xs text-gray-500 mb-0.5">Tipo de cliente</p>
                                            <p class="font-medium text-gray-900">{{ affiliate.client_type || '—' }}</p>
                                        </div>
                                        <div class="bg-white rounded-lg px-4 py-3 border border-gray-100">
                                            <p class="text-xs text-gray-500 mb-0.5">Tipo de cotizante</p>
                                            <p class="font-medium text-gray-900">{{ affiliate.contributor_type && affiliate.contributor_type_code ? affiliate.contributor_type + ' (' + affiliate.contributor_type_code + ')' : (affiliate.contributor_type || affiliate.contributor_type_code || '—') }}</p>
                                        </div>
                                    </template>
                                </div>
                            </div>

                            <!-- Bloque 2: Entidades (AFP, ARP, CCF) — solo cotizantes -->
                            <div v-if="!isBeneficiary" class="px-6 py-4 border-b border-gray-100">
                                <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-3">Entidades (AFP, ARP, CCF)</p>
                                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
                                    <div class="flex flex-col py-2">
                                        <span class="text-xs text-gray-500">AFP</span>
                                        <span class="font-medium text-gray-900 mt-0.5">{{ affiliate.afp_name || '—' }}</span>
                                    </div>
                                    <div class="flex flex-col py-2">
                                        <span class="text-xs text-gray-500">ARP</span>
                                        <span class="font-medium text-gray-900 mt-0.5">{{ affiliate.arp_name || '—' }}<span v-if="affiliate.arp_risk_class" class="text-gray-500 font-normal"> · Clase {{ affiliate.arp_risk_class }}</span></span>
                                    </div>
                                    <div class="flex flex-col py-2">
                                        <span class="text-xs text-gray-500">CCF</span>
                                        <span class="font-medium text-gray-900 mt-0.5">{{ affiliate.ccf_name || '—' }}</span>
                                    </div>
                                    <div class="flex flex-col py-2">
                                        <span class="text-xs text-gray-500">Operador de pago</span>
                                        <span class="font-medium text-gray-900 mt-0.5">{{ affiliate.payment_operator || '—' }}</span>
                                    </div>
                                    <div class="flex flex-col py-2">
                                        <span class="text-xs text-gray-500">Registro contable</span>
                                        <span class="font-medium text-gray-900 mt-0.5">{{ affiliate.accounting_registry || '—' }}</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Bloque 3: Pago y vencimiento — solo cotizantes -->
                            <div v-if="!isBeneficiary" class="px-6 py-4 border-b border-gray-100 bg-gray-50/30">
                                <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-3">Pago y vencimiento</p>
                                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
                                    <div class="flex flex-col py-2">
                                        <span class="text-xs text-gray-500">Pagador</span>
                                        <span class="font-medium text-gray-900 mt-0.5">
                                            <template v-if="affiliate.payer">
                                                <Link :href="`/payers/${affiliate.payer.id}`" class="text-brand-600 hover:text-brand-700 hover:underline">{{ affiliate.payer.name }}</Link>
                                                <span class="block text-xs text-gray-500 font-normal">{{ affiliate.payer.document_type_abbreviation }} {{ affiliate.payer.document_number }}</span>
                                            </template>
                                            <template v-else>—</template>
                                        </span>
                                    </div>
                                    <div class="flex flex-col py-2">
                                        <span class="text-xs text-gray-500">IBC</span>
                                        <span class="font-medium text-gray-900 mt-0.5">{{ (affiliate.ibc != null && affiliate.ibc !== '') ? Number(affiliate.ibc).toLocaleString('es-CO') : '—' }}</span>
                                    </div>
                                    <div class="flex flex-col py-2">
                                        <span class="text-xs text-gray-500">Día de pago</span>
                                        <span class="font-medium text-gray-900 mt-0.5">{{ (affiliate.payment_day != null && affiliate.payment_day !== '') ? affiliate.payment_day + 'º día hábil' : '—' }}</span>
                                    </div>
                                    <div class="flex flex-col py-2">
                                        <span class="text-xs text-gray-500">Periodicidad</span>
                                        <span class="font-medium text-gray-900 mt-0.5">{{ affiliate.payment_periodicity === 'CURRENT' ? 'Al día' : affiliate.payment_periodicity === 'OVERDUE' ? 'En mora' : affiliate.payment_periodicity || '—' }}</span>
                                    </div>
                                    <div class="flex flex-col py-2">
                                        <span class="text-xs text-gray-500">Pagos al día</span>
                                        <span class="mt-0.5 font-medium text-gray-900">
                                            <template v-if="payments_up_to_date === null">
                                                —
                                            </template>
                                            <template v-else-if="payments_up_to_date">
                                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold bg-emerald-100 text-emerald-800">
                                                    Sí
                                                </span>
                                            </template>
                                            <template v-else>
                                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold bg-red-100 text-red-800">
                                                    No
                                                </span>
                                            </template>
                                        </span>
                                    </div>
                                    <div class="flex flex-col py-2">
                                        <span class="text-xs text-gray-500">Parafiscales</span>
                                        <span class="font-medium text-gray-900 mt-0.5">
                                            <span v-if="affiliate.has_parafiscales" class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-amber-100 text-amber-800">Sí</span>
                                            <span v-else class="text-gray-400">No</span>
                                        </span>
                                    </div>
                                    <div v-if="pila_next_due_label" class="flex flex-col py-2 sm:col-span-2 lg:col-span-1">
                                        <span class="text-xs text-gray-500 flex items-center gap-1">
                                            <CalendarClock class="h-3.5 w-3.5" />
                                            Próximo vencimiento PILA
                                        </span>
                                        <span
                                            class="mt-0.5 inline-flex items-center gap-2 text-sm font-semibold"
                                            :class="pila_next_due_is_soon ? 'text-red-700' : 'text-gray-900'"
                                        >
                                            {{ pila_next_due_label }}
                                            <span
                                                v-if="pila_next_due_is_soon"
                                                class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-semibold bg-red-50 text-red-700 border border-red-200"
                                            >
                                                Próximo a vencer
                                            </span>
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <!-- Observaciones (ancho completo, solo si hay contenido) -->
                            <div v-if="affiliate.observations" class="px-6 py-4">
                                <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">Observaciones (perfil SS)</p>
                                <div class="bg-amber-50/50 border border-amber-100 rounded-lg px-4 py-3">
                                    <p class="text-gray-700 text-sm whitespace-pre-line">{{ affiliate.observations }}</p>
                                </div>
                            </div>
                        </template>
                    </div>

                    <!-- Novedades -->
                    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                        <div class="px-6 py-4 border-b border-gray-200 bg-gradient-to-r from-slate-50 to-white flex items-center justify-between">
                            <h3 class="flex items-center gap-2 font-semibold text-gray-900">
                                <FileText class="h-5 w-5 text-slate-600" />
                                Novedades
                                <span v-if="novelties.length" class="ml-2 px-2 py-0.5 bg-slate-100 text-slate-700 text-xs rounded-full">{{ novelties.length }}</span>
                            </h3>
                            <button type="button" @click="openNoveltyModal" class="inline-flex items-center gap-2 px-3 py-1.5 text-sm font-medium text-brand-600 hover:text-brand-700 hover:bg-brand-50 rounded-lg transition-colors">
                                <Plus class="h-4 w-4" />
                                Registrar novedad
                            </button>
                        </div>
                        <div class="divide-y divide-gray-100">
                            <div v-for="n in novelties" :key="n.id" class="px-6 py-4 flex flex-wrap items-start gap-3">
                                <span class="inline-flex px-2 py-0.5 rounded text-xs font-medium bg-slate-100 text-slate-700">{{ n.novelty_type?.name || 'Novedad' }}</span>
                                <span class="text-sm text-gray-500">{{ n.effective_date_formatted }}</span>
                                <p v-if="n.description" class="text-sm text-gray-700 w-full mt-0.5">{{ n.description }}</p>
                                <div v-if="n.old_value || n.new_value" class="flex flex-wrap gap-2 text-xs">
                                    <span v-if="n.old_value" class="text-gray-500">Antes: {{ n.old_value }}</span>
                                    <span v-if="n.old_value && n.new_value" class="text-gray-400">→</span>
                                    <span v-if="n.new_value" class="text-gray-700">Después: {{ n.new_value }}</span>
                                </div>
                            </div>
                            <div v-if="!novelties.length" class="px-6 py-10 text-center">
                                <FileText class="h-10 w-10 mx-auto text-gray-300 mb-2" />
                                <p class="text-gray-500 text-sm">No hay novedades registradas</p>
                                <button type="button" @click="openNoveltyModal" class="mt-3 inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-brand-600 hover:bg-brand-50 rounded-lg transition-colors">
                                    <Plus class="h-4 w-4" />
                                    Registrar primera novedad
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Cotizante Titular (si es beneficiario) -->
                    <div v-if="isBeneficiary && holder" class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                        <div class="px-6 py-4 border-b border-gray-200 bg-gradient-to-r from-purple-50 to-white">
                            <h3 class="flex items-center gap-2 font-semibold text-gray-900">
                                <User class="h-5 w-5 text-purple-600" />
                                Cotizante Titular
                            </h3>
                        </div>
                        <div class="p-6">
                            <div v-if="affiliate.relationship_type_label" class="mb-4 inline-flex items-center gap-2 px-3 py-1.5 bg-purple-100 text-purple-700 rounded-full text-sm font-medium">
                                <Heart class="h-4 w-4" />
                                {{ affiliate.relationship_type_label }}
                            </div>
                            
                            <Link :href="`/affiliates/${holder.id}`" class="flex items-center gap-4 p-4 rounded-xl bg-gray-50 hover:bg-brand-50 transition-colors group">
                                <div class="h-14 w-14 rounded-full bg-brand-100 flex items-center justify-center flex-shrink-0">
                                    <User class="h-7 w-7 text-brand-600" />
                                </div>
                                <div class="flex-1">
                                    <p class="font-semibold text-gray-900 group-hover:text-brand-700">{{ holder.full_name }}</p>
                                    <p class="text-sm text-gray-500">{{ holder.document_type_abbreviation }} {{ holder.document_number }}<span v-if="holder.document_issue_date_formatted" class="text-gray-400"> · Exp. {{ holder.document_issue_date_formatted }}</span></p>
                                    <p v-if="holder.phone || holder.whatsapp" class="text-sm text-gray-500 mt-1">
                                        {{ holder.whatsapp || holder.phone }}
                                    </p>
                                </div>
                                <ArrowRight class="h-5 w-5 text-gray-400 group-hover:text-brand-500 transition-colors" />
                            </Link>
                        </div>
                    </div>

                    <!-- Beneficiarios (si es cotizante) -->
                    <div v-if="isHolder" class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                        <div class="px-6 py-4 border-b border-gray-200 bg-gradient-to-r from-brand-50 to-white flex items-center justify-between">
                            <h3 class="flex items-center gap-2 font-semibold text-gray-900">
                                <Users class="h-5 w-5 text-brand-600" />
                                Beneficiarios
                                <span v-if="beneficiaries.length" class="ml-2 px-2 py-0.5 bg-brand-100 text-brand-700 text-xs rounded-full">
                                    {{ beneficiaries.length }}
                                </span>
                            </h3>
                            <Link :href="`/affiliates/create?holder_id=${affiliate.id}`" class="inline-flex items-center gap-2 px-3 py-1.5 text-sm font-medium text-brand-600 hover:text-brand-700 hover:bg-brand-50 rounded-lg transition-colors">
                                <UserPlus class="h-4 w-4" />
                                Agregar Beneficiario
                            </Link>
                        </div>
                        <div class="divide-y divide-gray-100">
                            <Link 
                                v-for="beneficiary in beneficiaries" 
                                :key="beneficiary.id" 
                                :href="`/affiliates/${beneficiary.id}`"
                                class="flex items-center gap-4 px-6 py-4 hover:bg-gray-50 transition-colors group"
                            >
                                <div class="h-12 w-12 rounded-full bg-purple-100 flex items-center justify-center flex-shrink-0">
                                    <Heart class="h-6 w-6 text-purple-600" />
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p class="font-medium text-gray-900 group-hover:text-brand-700">{{ beneficiary.full_name }}</p>
                                    <p class="text-sm text-gray-500">{{ beneficiary.document_type_abbreviation }} {{ beneficiary.document_number }}<span v-if="beneficiary.document_issue_date_formatted" class="text-gray-400"> · Exp. {{ beneficiary.document_issue_date_formatted }}</span></p>
                                    <span v-if="beneficiary.relationship_type_short" class="inline-flex items-center mt-1 px-2 py-0.5 bg-purple-50 text-purple-600 rounded text-xs font-medium">
                                        {{ beneficiary.relationship_type_short }}
                                    </span>
                                </div>
                                <div class="text-right text-sm text-gray-500">
                                    <p v-if="beneficiary.whatsapp || beneficiary.phone">{{ beneficiary.whatsapp || beneficiary.phone }}</p>
                                </div>
                                <ArrowRight class="h-5 w-5 text-gray-300 group-hover:text-brand-500 transition-colors" />
                            </Link>
                            
                            <div v-if="!beneficiaries.length" class="px-6 py-12 text-center">
                                <Users class="h-12 w-12 mx-auto text-gray-300 mb-3" />
                                <p class="text-gray-500 mb-4">No hay beneficiarios registrados</p>
                                <Link :href="`/affiliates/create?holder_id=${affiliate.id}`" class="inline-flex items-center gap-2 px-4 py-2 bg-brand-500 text-white rounded-lg hover:bg-brand-600 transition-colors">
                                    <UserPlus class="h-5 w-5" />
                                    Agregar Primer Beneficiario
                                </Link>
                            </div>
                        </div>
                    </div>

                    <!-- Historial de Citas (abre listado en otra vista) -->
                    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                        <div class="px-6 py-4 border-b border-gray-200 bg-gradient-to-r from-gray-50 to-white">
                            <h3 class="flex items-center gap-2 font-semibold text-gray-900">
                                <Calendar class="h-5 w-5 text-brand-600" />
                                Historial de Citas
                                <span v-if="(affiliate.appointments?.length ?? 0) > 0" class="ml-2 px-2 py-0.5 bg-brand-100 text-brand-700 text-xs rounded-full">{{ affiliate.appointments.length }}</span>
                            </h3>
                            <p class="text-sm text-gray-500 mt-0.5">Citas médicas del afiliado</p>
                        </div>
                        <div class="p-6">
                            <Link :href="`/appointments?affiliate_id=${affiliate.id}`" class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-brand-600 hover:bg-brand-50 rounded-lg transition-colors">
                                Ver historial de citas del afiliado
                            </Link>
                        </div>
                    </div>

                    <!-- Autorizaciones (abre listado en otra vista) -->
                    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                        <div class="px-6 py-4 border-b border-gray-200 bg-gradient-to-r from-emerald-50 to-white">
                            <h3 class="flex items-center gap-2 font-semibold text-gray-900">
                                <FileCheck class="h-5 w-5 text-emerald-600" />
                                Autorizaciones
                                <span v-if="authorizations.length" class="ml-2 px-2 py-0.5 bg-emerald-100 text-emerald-700 text-xs rounded-full">{{ authorizations.length }}</span>
                            </h3>
                            <p class="text-sm text-gray-500 mt-0.5">Autorizaciones médicas del afiliado</p>
                        </div>
                        <div class="p-6 flex flex-wrap gap-3">
                            <Link :href="`/authorizations?affiliate_id=${affiliate.id}`" class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-brand-600 hover:bg-brand-50 rounded-lg transition-colors">
                                Ver autorizaciones del afiliado
                            </Link>
                            <Link :href="`/authorizations/create?affiliate_id=${affiliate.id}`" class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-emerald-600 hover:bg-emerald-50 rounded-lg transition-colors">
                                Crear autorización
                            </Link>
                        </div>
                    </div>
                </div>

                <div class="space-y-6">
                    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                        <div class="px-6 py-4 border-b border-gray-200 bg-gradient-to-r from-gray-50 to-white">
                            <h3 class="flex items-center gap-2 font-semibold text-gray-900">
                                <Phone class="h-5 w-5 text-brand-600" />
                                Contacto
                            </h3>
                        </div>
                        <div class="p-6 space-y-4">
                            <div>
                                <p class="text-xs text-gray-500 uppercase tracking-wide mb-1">Teléfono 1</p>
                                <p class="font-medium text-gray-900">{{ affiliate.phone || '-' }}</p>
                            </div>
                            <div v-if="affiliate.phone_2">
                                <p class="text-xs text-gray-500 uppercase tracking-wide mb-1">Teléfono 2</p>
                                <p class="font-medium text-gray-900">{{ affiliate.phone_2 }}</p>
                            </div>
                            <div>
                                <p class="text-xs text-gray-500 uppercase tracking-wide mb-1">WhatsApp / Celular</p>
                                <p class="font-medium text-gray-900">{{ affiliate.whatsapp || affiliate.whatsapp_number || '-' }}</p>
                            </div>
                            <div>
                                <p class="text-xs text-gray-500 uppercase tracking-wide mb-1">Email</p>
                                <p class="font-medium text-gray-900 break-all">{{ affiliate.email || '-' }}</p>
                            </div>
                            <div>
                                <p class="text-xs text-gray-500 uppercase tracking-wide mb-1">Dirección</p>
                                <p class="font-medium text-gray-900">{{ affiliate.address || '-' }}</p>
                            </div>
                            <div v-if="affiliate.neighborhood">
                                <p class="text-xs text-gray-500 uppercase tracking-wide mb-1">Barrio</p>
                                <p class="font-medium text-gray-900">{{ affiliate.neighborhood }}</p>
                            </div>
                            <div v-if="affiliate.city">
                                <p class="text-xs text-gray-500 uppercase tracking-wide mb-1">Ciudad</p>
                                <p class="font-medium text-gray-900">{{ affiliate.city }}</p>
                            </div>
                            <div v-if="affiliate.department">
                                <p class="text-xs text-gray-500 uppercase tracking-wide mb-1">Departamento</p>
                                <p class="font-medium text-gray-900">{{ affiliate.department }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Credenciales de operadores (SS) — solo cotizantes -->
                    <div v-if="!isBeneficiary" class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                        <div class="px-6 py-4 border-b border-gray-200 bg-gradient-to-r from-amber-50 to-white flex items-center justify-between">
                            <h3 class="flex items-center gap-2 font-semibold text-gray-900">
                                <Key class="h-5 w-5 text-amber-600" />
                                Credenciales de operadores
                                <span v-if="operatorCredentials.length" class="ml-2 px-2 py-0.5 bg-amber-100 text-amber-700 text-xs rounded-full">{{ operatorCredentials.length }}</span>
                            </h3>
                            <button type="button" @click="openCredentialModal()" class="inline-flex items-center gap-2 px-3 py-1.5 text-sm font-medium text-amber-700 hover:bg-amber-50 rounded-lg transition-colors">
                                <Plus class="h-4 w-4" />
                                Agregar credencial
                            </button>
                        </div>
                        <div class="divide-y divide-gray-100">
                            <div v-for="cred in operatorCredentials" :key="cred.id" class="px-6 py-3 flex items-center justify-between group">
                                <button type="button" @click="openViewCredentialModal(cred)" class="text-left flex-1 min-w-0 font-medium text-gray-900 hover:text-amber-700 transition-colors flex items-center gap-2">
                                    <span>{{ providerLabels[cred.provider_type] || cred.provider_type }}</span>
                                    <Eye class="h-4 w-4 text-gray-400 opacity-0 group-hover:opacity-100 transition-opacity" />
                                </button>
                                <div class="flex items-center gap-2 flex-shrink-0">
                                    <button type="button" @click="openViewCredentialModal(cred)" class="inline-flex items-center gap-1 px-2 py-1 text-sm text-gray-600 hover:bg-gray-100 rounded">Ver</button>
                                    <button type="button" @click="openCredentialModal(cred)" class="inline-flex items-center gap-1 px-2 py-1 text-sm text-amber-700 hover:bg-amber-50 rounded">Editar</button>
                                    <button type="button" @click="deleteCredential(cred)" class="inline-flex items-center gap-1 px-2 py-1 text-sm text-red-600 hover:bg-red-50 rounded">Eliminar</button>
                                </div>
                            </div>
                            <div v-if="!operatorCredentials.length" class="px-6 py-8 text-center text-sm text-gray-500">
                                No hay credenciales. Usuario y clave se guardan cifrados.
                            </div>
                        </div>
                    </div>

                    <!-- Pagos y recibos: enlace a página dedicada -->
                    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                        <div class="px-6 py-4 border-b border-gray-200 bg-gradient-to-r from-emerald-50 to-white flex items-center justify-between">
                            <h3 class="flex items-center gap-2 font-semibold text-gray-900">
                                <FileText class="h-5 w-5 text-emerald-600" />
                                Pagos y recibos
                                <span v-if="payments.length" class="ml-2 px-2 py-0.5 bg-emerald-100 text-emerald-700 text-xs rounded-full">{{ payments.length }}</span>
                            </h3>
                            <Link
                                :href="`/affiliates/${affiliate.id}/payments`"
                                class="inline-flex items-center justify-center gap-2 px-4 py-2 text-sm font-medium text-center text-white bg-emerald-600 hover:bg-emerald-700 rounded-lg transition-colors"
                            >
                                <ArrowRight class="h-4 w-4" />
                                Registrar pago
                            </Link>
                        </div>
                        <div class="px-6 py-3">
                            <p class="text-sm text-gray-500">
                                {{ payments.length ? `${payments.length} pago(s) registrado(s).` : 'Sin pagos registrados.' }}
                                Ir a la página de registro de pagos para ver el detalle y agregar nuevos.
                            </p>
                        </div>
                    </div>

                    <!-- Soportes documentales -->
                    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                        <div class="px-6 py-4 border-b border-gray-200 bg-gradient-to-r from-indigo-50 to-white flex items-center justify-between">
                            <h3 class="flex items-center gap-2 font-semibold text-gray-900">
                                <Paperclip class="h-5 w-5 text-indigo-600" />
                                Soportes documentales
                                <span v-if="supportDocuments.length" class="ml-2 px-2 py-0.5 bg-indigo-100 text-indigo-700 text-xs rounded-full">{{ supportDocuments.length }}</span>
                            </h3>
                            <button type="button" @click="showSupportForm = !showSupportForm" class="inline-flex items-center gap-2 px-3 py-1.5 text-sm font-medium text-indigo-700 hover:bg-indigo-50 rounded-lg transition-colors">
                                <Plus class="h-4 w-4" />
                                Subir documento
                            </button>
                        </div>
                        <div v-if="showSupportForm" class="px-6 py-4 bg-gray-50 border-b border-gray-100">
                            <form @submit.prevent="submitSupportDocument" class="space-y-3">
                                <input v-model="supportForm.title" type="text" required maxlength="255" placeholder="Título del documento" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500 text-sm" />
                                <input type="file" @change="supportForm.document = $event.target.files?.[0]" required class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:bg-brand-50 file:text-brand-700" />
                                <p v-if="supportForm.errors.title" class="text-sm text-red-600">{{ supportForm.errors.title }}</p>
                                <p v-if="supportForm.errors.document" class="text-sm text-red-600">{{ supportForm.errors.document }}</p>
                                <div class="flex gap-2">
                                    <button type="button" @click="showSupportForm = false" class="px-3 py-1.5 text-sm text-gray-600 hover:bg-gray-200 rounded-lg">Cancelar</button>
                                    <button type="submit" :disabled="supportForm.processing" class="px-3 py-1.5 text-sm bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 disabled:opacity-50">Subir</button>
                                </div>
                            </form>
                        </div>
                        <div class="divide-y divide-gray-100">
                            <div v-for="doc in supportDocuments" :key="doc.id" class="px-6 py-3 flex items-center justify-between gap-2">
                                <div class="min-w-0">
                                    <p class="font-medium text-gray-900 truncate">{{ doc.title }}</p>
                                    <p class="text-xs text-gray-500">{{ doc.original_name || '—' }} · {{ formatFileSize(doc.size) }} · {{ doc.created_at }}</p>
                                </div>
                                <div class="flex items-center gap-2 flex-shrink-0">
                                    <a :href="`/affiliates/${affiliate.id}/support-documents/${doc.id}/download`" target="_blank" rel="noopener" class="inline-flex items-center gap-1 px-2 py-1 text-sm text-indigo-600 hover:bg-indigo-50 rounded">
                                        <Download class="h-4 w-4" />
                                        Descargar
                                    </a>
                                    <button type="button" @click="deleteSupportDoc(doc)" class="inline-flex items-center gap-1 px-2 py-1 text-sm text-red-600 hover:bg-red-50 rounded">
                                        <Trash2 class="h-4 w-4" />
                                    </button>
                                </div>
                            </div>
                            <div v-if="!supportDocuments.length && !showSupportForm" class="px-6 py-8 text-center text-sm text-gray-500">
                                No hay documentos. Sube títulos, recibos o soportes del afiliado.
                            </div>
                        </div>
                    </div>

                    <!-- Historia clínica (solo roles atencion/admin; backend restringe acceso) -->
                    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                        <div class="px-6 py-4 border-b border-gray-200 bg-gradient-to-r from-teal-50 to-white">
                            <h3 class="flex items-center gap-2 font-semibold text-gray-900">
                                <FileText class="h-5 w-5 text-teal-600" />
                                Historia clínica
                            </h3>
                            <p class="text-sm text-gray-500 mt-0.5">Encuentros y documentos clínicos del afiliado</p>
                        </div>
                        <div class="p-6">
                            <Link :href="`/affiliates/${affiliate.id}/historia-clinica`" class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-brand-600 hover:bg-brand-50 rounded-lg transition-colors">
                                Ver historia clínica del afiliado
                            </Link>
                        </div>
                    </div>

                    <div v-if="affiliate.notes" class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                        <div class="px-6 py-4 border-b border-gray-200 bg-gradient-to-r from-gray-50 to-white">
                            <h3 class="font-semibold text-gray-900">Notas</h3>
                        </div>
                        <div class="p-6">
                            <p class="text-gray-700 whitespace-pre-line">{{ affiliate.notes }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal Registrar novedad -->
        <Teleport to="body">
            <div v-if="showNoveltyModal" class="fixed inset-0 z-50 overflow-y-auto" aria-modal="true">
                <div class="flex min-h-full items-center justify-center p-4">
                    <div class="fixed inset-0 bg-black/50 transition-opacity" @click="showNoveltyModal = false" />
                    <div class="relative bg-white rounded-xl shadow-xl max-w-md w-full p-6">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-lg font-semibold text-gray-900">Registrar novedad</h3>
                            <button type="button" @click="showNoveltyModal = false" class="text-gray-400 hover:text-gray-600 rounded-lg p-1">
                                <X class="h-5 w-5" />
                            </button>
                        </div>
                        <form @submit.prevent="submitNovelty" class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Tipo de novedad *</label>
                                <select v-model="noveltyForm.novelty_type_id" required class="w-full rounded-lg border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500 text-sm">
                                    <option value="">Seleccione...</option>
                                    <option v-for="t in noveltyTypes" :key="t.id" :value="t.id">{{ t.name }}</option>
                                </select>
                                <p v-if="noveltyForm.errors.novelty_type_id" class="mt-1 text-sm text-red-600">{{ noveltyForm.errors.novelty_type_id }}</p>
                            </div>
                            <div>
                                <DatePicker v-model="noveltyForm.effective_date" label="Fecha efectiva" required />
                                <p v-if="noveltyForm.errors.effective_date" class="mt-1 text-sm text-red-600">{{ noveltyForm.errors.effective_date }}</p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Descripción</label>
                                <input v-model="noveltyForm.description" type="text" maxlength="255" placeholder="Opcional" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500 text-sm" />
                                <p v-if="noveltyForm.errors.description" class="mt-1 text-sm text-red-600">{{ noveltyForm.errors.description }}</p>
                            </div>
                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Valor anterior</label>
                                    <input v-model="noveltyForm.old_value" type="text" maxlength="255" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500 text-sm" />
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Valor nuevo</label>
                                    <input v-model="noveltyForm.new_value" type="text" maxlength="255" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500 text-sm" />
                                </div>
                            </div>
                            <div class="flex gap-3 pt-2">
                                <button type="button" @click="showNoveltyModal = false" class="flex-1 px-4 py-2 border border-gray-300 rounded-lg text-gray-700 text-sm font-medium hover:bg-gray-50">
                                    Cancelar
                                </button>
                                <button type="submit" :disabled="noveltyForm.processing" class="flex-1 px-4 py-2 bg-brand-500 text-white rounded-lg text-sm font-medium hover:bg-brand-600 disabled:opacity-50">
                                    {{ noveltyForm.processing ? 'Guardando...' : 'Guardar novedad' }}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Modal Credencial operador -->
            <div v-if="showCredentialModal" class="fixed inset-0 z-50 overflow-y-auto" aria-modal="true">
                <div class="flex min-h-full items-center justify-center p-4">
                    <div class="fixed inset-0 bg-black/50 transition-opacity" @click="showCredentialModal = false" />
                    <div class="relative bg-white rounded-xl shadow-xl max-w-md w-full p-6">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-lg font-semibold text-gray-900">{{ editingCredentialId ? 'Editar credencial' : 'Agregar credencial' }}</h3>
                            <button type="button" @click="showCredentialModal = false" class="text-gray-400 hover:text-gray-600 rounded-lg p-1">
                                <X class="h-5 w-5" />
                            </button>
                        </div>
                        <form @submit.prevent="submitCredential" class="space-y-4">
                            <div v-if="editingCredentialId">
                                <p class="text-sm text-gray-600">{{ providerLabels[credentialForm.provider_type] || credentialForm.provider_type }}</p>
                            </div>
                            <div v-else>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Proveedor *</label>
                                <select v-model="credentialForm.provider_type" required class="w-full rounded-lg border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500 text-sm">
                                    <option v-for="(label, key) in providerLabels" :key="key" :value="key">{{ label }}</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Usuario *</label>
                                <input v-model="credentialForm.username" type="text" required maxlength="255" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500 text-sm" placeholder="Usuario o correo" />
                                <p v-if="credentialForm.errors.username" class="mt-1 text-sm text-red-600">{{ credentialForm.errors.username }}</p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">{{ editingCredentialId ? 'Nueva contraseña (dejar en blanco para no cambiar)' : 'Contraseña *' }}</label>
                                <input v-model="credentialForm.password" :type="'password'" :required="!editingCredentialId" maxlength="500" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500 text-sm" placeholder="••••••••" autocomplete="off" />
                                <p v-if="credentialForm.errors.password" class="mt-1 text-sm text-red-600">{{ credentialForm.errors.password }}</p>
                            </div>
                            <div class="flex gap-3 pt-2">
                                <button type="button" @click="showCredentialModal = false" class="flex-1 px-4 py-2 border border-gray-300 rounded-lg text-gray-700 text-sm font-medium hover:bg-gray-50">Cancelar</button>
                                <button type="submit" :disabled="credentialForm.processing" class="flex-1 px-4 py-2 bg-amber-600 text-white rounded-lg text-sm font-medium hover:bg-amber-700 disabled:opacity-50">
                                    {{ credentialForm.processing ? 'Guardando...' : (editingCredentialId ? 'Actualizar' : 'Guardar') }}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Modal Ver credencial (usuario y contraseña) -->
            <div v-if="showViewCredentialModal" class="fixed inset-0 z-50 overflow-y-auto" aria-modal="true">
                <div class="flex min-h-full items-center justify-center p-4">
                    <div class="fixed inset-0 bg-black/50 transition-opacity" @click="showViewCredentialModal = false" />
                    <div class="relative bg-white rounded-xl shadow-xl max-w-md w-full p-6">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-lg font-semibold text-gray-900">
                                {{ viewCredentialData ? (providerLabels[viewCredentialData.provider_type] || viewCredentialData.provider_type) : 'Credencial' }}
                            </h3>
                            <button type="button" @click="showViewCredentialModal = false" class="text-gray-400 hover:text-gray-600 rounded-lg p-1">
                                <X class="h-5 w-5" />
                            </button>
                        </div>
                        <div v-if="viewCredentialLoading" class="py-8 flex justify-center">
                            <Loader2 class="h-8 w-8 text-amber-500 animate-spin" />
                        </div>
                        <div v-else-if="viewCredentialData" class="space-y-4">
                            <div>
                                <label class="block text-xs font-medium text-gray-500 uppercase tracking-wide mb-1">Usuario</label>
                                <div class="flex items-center gap-2">
                                    <input :value="viewCredentialData.username" type="text" readonly class="flex-1 rounded-lg border-gray-200 bg-gray-50 px-3 py-2 text-sm font-mono" />
                                    <button type="button" @click="copyToClipboard(viewCredentialData.username)" class="p-2 text-gray-500 hover:text-amber-600 hover:bg-amber-50 rounded-lg" title="Copiar">
                                        <Copy class="h-4 w-4" />
                                    </button>
                                </div>
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-500 uppercase tracking-wide mb-1">Contraseña</label>
                                <div class="flex items-center gap-2">
                                    <input :value="viewCredentialData.password" :type="viewCredentialShowPassword ? 'text' : 'password'" readonly class="flex-1 rounded-lg border-gray-200 bg-gray-50 px-3 py-2 text-sm font-mono" />
                                    <button type="button" @click="viewCredentialShowPassword = !viewCredentialShowPassword" class="p-2 text-gray-500 hover:text-amber-600 hover:bg-amber-50 rounded-lg" :title="viewCredentialShowPassword ? 'Ocultar' : 'Mostrar'">
                                        <EyeOff v-if="viewCredentialShowPassword" class="h-4 w-4" />
                                        <Eye v-else class="h-4 w-4" />
                                    </button>
                                    <button type="button" @click="copyToClipboard(viewCredentialData.password)" class="p-2 text-gray-500 hover:text-amber-600 hover:bg-amber-50 rounded-lg" title="Copiar">
                                        <Copy class="h-4 w-4" />
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div v-else class="py-4 text-sm text-gray-500">No se pudo cargar la credencial.</div>
                        <div v-if="viewCredentialData" class="mt-6 flex gap-3 pt-2 border-t border-gray-100">
                            <button type="button" @click="showViewCredentialModal = false" class="flex-1 px-4 py-2 border border-gray-300 rounded-lg text-gray-700 text-sm font-medium hover:bg-gray-50">Cerrar</button>
                            <button type="button" @click="showViewCredentialModal = false; openCredentialModal(viewCredentialCred)" class="flex-1 px-4 py-2 bg-amber-600 text-white rounded-lg text-sm font-medium hover:bg-amber-700">
                                Editar
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </Teleport>
    </AppLayout>
</template>
