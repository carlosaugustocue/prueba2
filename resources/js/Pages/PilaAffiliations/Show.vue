<script setup>
import { computed, ref, onMounted } from 'vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { AlertTriangle, ChevronLeft, Pencil, Trash2, User, Building2, ShieldCheck, CreditCard, Calendar, KeyRound, Eye, EyeOff, Clock } from 'lucide-vue-next';
import axios from 'axios';
import PilaCredentialsPanel from './PilaCredentialsPanel.vue';
import CredentialAuditModal from '@/Components/CredentialAuditModal.vue';

const props = defineProps({
    affiliation: Object,
    next_due_date: { type: [String, null], default: null },
    audit_logs: { type: [Object, null], default: null },
    notes: { type: Array, default: () => [] },
});

const a = computed(() => props.affiliation?.data || props.affiliation);

const page = usePage();
const roleName = computed(() => page.props?.auth?.user?.role ?? null);
const canViewAudit = computed(() => ['admin', 'supervisor'].includes(roleName.value));

const auditModalOpen = ref(false);

const emptyValue = 'Sin registrar';
const isEmpty = (v) => v === null || v === undefined || v === '';

const paymentStatusLabel = (s) => {
    const map = { current: 'Al día', overdue: 'En mora', anticipated: 'Anticipado' };
    if (isEmpty(s)) return emptyValue;
    return map[s] || s;
};

const formatMoney = (amount) => {
    if (isEmpty(amount)) return null;
    const n = Number(amount);
    if (Number.isNaN(n)) return null;
    return `$${new Intl.NumberFormat('es-CO', { maximumFractionDigits: 0 }).format(n)}`;
};

const initials = computed(() => {
    const name = a.value?.affiliate?.full_name || '';
    const tokens = String(name).trim().split(/\s+/).filter(Boolean);
    const first = tokens[0]?.[0] ?? '';
    const second = tokens[1]?.[0] ?? tokens[0]?.[1] ?? '';
    return String(first + second).toUpperCase();
});

const clientTypeLabel = computed(() => {
    if (a.value?.self_employed) return 'Independiente';
    return 'Dependiente';
});

const affiliateActiveLabel = computed(() => (a.value?.is_current ? 'Activo' : 'Inactivo'));

const cotizanteBadgeLabel = computed(() => {
    const code = a.value?.cotizante_type?.code;
    if (isEmpty(code)) return emptyValue;
    return `${code} — ${clientTypeLabel.value}`;
});

const docBadgeLabel = computed(() => {
    const dt = a.value?.affiliate?.document_type_abbreviation;
    const dn = a.value?.affiliate?.document_number;
    if (isEmpty(dt) && isEmpty(dn)) return emptyValue;
    return `${dt || ''} ${dn || ''}`.trim();
});

// Día hábil: configurado en empleador o derivado del NIT (normativa colombiana).
const effectivePaymentDay = computed(() => a.value?.effective_payment_business_day ?? a.value?.employer?.payment_business_day);
const hasEmployerDay = computed(() => !isEmpty(effectivePaymentDay.value));
const displayNextDueDate = computed(() => a.value?.next_due_date ?? props.next_due_date);

const personalIncomplete = computed(() => {
    const phone = a.value?.affiliate?.phone;
    const email = a.value?.affiliate?.email;
    const address = a.value?.affiliate?.address;
    const city = a.value?.affiliate?.city;
    const department = a.value?.affiliate?.department;

    const hasAnyPersonalField = !isEmpty(phone) || !isEmpty(email) || !isEmpty(address) || !isEmpty(city) || !isEmpty(department);
    if (!hasAnyPersonalField) return false;

    return isEmpty(phone) || isEmpty(email) || isEmpty(address) || isEmpty(city) || isEmpty(department);
});

const paymentIncomplete = computed(() => {
    return isEmpty(a.value?.payment_status) || isEmpty(a.value?.last_payment_period) || isEmpty(a.value?.billing_type);
});

const paymentStatusMeta = (s) => {
    const map = { current: 'Al día', overdue: 'En mora', anticipated: 'Anticipado' };
    if (isEmpty(s)) return { text: emptyValue, tone: 'none' };
    if (s === 'overdue') return { text: map[s] || s, tone: 'overdue' };
    return { text: map[s] || s, tone: 'current' };
};

const destroy = () => {
    if (!confirm('¿Eliminar afiliación?')) return;
    router.delete(`/pila/affiliations/${a.value.id}`, { preserveScroll: true });
};

const loadingCreds = ref(false);
const credsError = ref('');
const credsSuccess = ref('');
const pilaCreds = ref([]);
const portalCreds = ref([]);
const revealed = ref({}); // { [`${kind}:${id}`]: { value, visible } }

const loadCredentials = async () => {
    loadingCreds.value = true;
    credsError.value = '';
    credsSuccess.value = '';
    try {
        const res = await axios.get(`/pila/affiliations/${a.value.id}/credentials`);
        pilaCreds.value = Array.isArray(res.data?.pila) ? res.data.pila : [];
        portalCreds.value = Array.isArray(res.data?.portals) ? res.data.portals : [];
    } catch (e) {
        credsError.value = 'No se pudieron cargar las credenciales.';
        pilaCreds.value = [];
        portalCreds.value = [];
    } finally {
        loadingCreds.value = false;
    }
};

const revealPassword = async (kind, id) => {
    const key = `${kind}:${id}`;
    try {
        const res = await axios.post(`/pila/credentials/${kind}/${id}/reveal`);
        revealed.value[key] = { value: res.data?.password ?? null, visible: true };
    } catch (e) {
        revealed.value[key] = { value: null, visible: true };
    }
};

const toggleVisible = (kind, id) => {
    const key = `${kind}:${id}`;
    if (!revealed.value[key]) return;
    revealed.value[key].visible = !revealed.value[key].visible;
};

const openPilaEdit = (c) => {
    // Usamos el mismo formulario de "upsert" de abajo para evitar duplicar UI.
    pilaCreateForm.value.operator = c.operator;
    pilaCreateForm.value.username = c.username || '';
    pilaCreateForm.value.password = '';
};

const openPortalEdit = (c) => {
    portalCreateForm.value.entity_type = c.entity_type;
    portalCreateForm.value.is_not_applicable = !!c.is_not_applicable;
    portalCreateForm.value.username = c.username || '';
    portalCreateForm.value.password = '';
};

// Formularios para alta (upsert) cuando se necesita registrar credenciales.
const pilaCreateForm = ref({
    operator: '',
    username: '',
    password: '',
});

const portalCreateForm = ref({
    entity_type: 'EPS',
    is_not_applicable: false,
    username: '',
    password: '',
});

const submitPilaCreate = async () => {
    credsError.value = '';
    credsSuccess.value = '';
    loadingCreds.value = true;

    try {
        await axios.post(`/pila/affiliations/${a.value.id}/credentials/pila`, {
            operator: pilaCreateForm.value.operator,
            username: pilaCreateForm.value.username,
            password: pilaCreateForm.value.password,
            is_active: true,
        });
        credsSuccess.value = 'Credencial PILA registrada/actualizada.';
        pilaCreateForm.value.password = '';
        await loadCredentials();
    } catch (e) {
        const data = e?.response?.data;
        credsError.value =
            data?.errors?.password?.[0] ||
            data?.message ||
            'No se pudo registrar la credencial PILA.';
    } finally {
        loadingCreds.value = false;
    }
};

const submitPortalCreate = async () => {
    credsError.value = '';
    credsSuccess.value = '';
    loadingCreds.value = true;

    try {
        await axios.post(`/pila/affiliations/${a.value.id}/credentials/portal`, {
            entity_type: portalCreateForm.value.entity_type,
            is_not_applicable: portalCreateForm.value.is_not_applicable,
            username: portalCreateForm.value.is_not_applicable ? null : portalCreateForm.value.username,
            password: portalCreateForm.value.is_not_applicable ? null : portalCreateForm.value.password,
            is_active: true,
        });
        credsSuccess.value = 'Credencial de portal registrada/actualizada.';
        portalCreateForm.value.password = '';
        await loadCredentials();
    } catch (e) {
        const data = e?.response?.data;
        credsError.value =
            data?.errors?.password?.[0] ||
            data?.message ||
            'No se pudo registrar la credencial de portal.';
    } finally {
        loadingCreds.value = false;
    }
};

const auditLoading = ref(false);
const auditError = ref('');
const auditLogs = ref([]);

const loadAuditLogs = async () => {
    if (!a.value?.id) return;
    auditLoading.value = true;
    auditError.value = '';
    auditLogs.value = [];

    try {
        const res = await axios.get(`/pila/affiliations/${a.value.id}/credentials/audit-logs`);
        auditLogs.value = Array.isArray(res.data?.logs) ? res.data.logs : [];
    } catch (e) {
        auditError.value = e?.response?.data?.message || 'No se pudieron cargar los logs de auditoría.';
    } finally {
        auditLoading.value = false;
    }
};

onMounted(() => {
    loadCredentials();
});

// ─────────────────────────────────────────────────────────────────────────────
// Notas operativas (Sprint 2.4)
// ─────────────────────────────────────────────────────────────────────────────
const noteDraftOpen = ref(false);
const noteSaving = ref(false);
const noteError = ref('');

const noteDraft = ref({
    type: 'general',
    content: '',
    is_pinned: false,
});

const editingNoteId = ref(null);
const editDraft = ref({ type: 'general', content: '', is_pinned: false });

const resetDraft = () => {
    noteDraft.value = { type: 'general', content: '', is_pinned: false };
    noteError.value = '';
};

const openEditNote = (n) => {
    editingNoteId.value = n.id;
    editDraft.value = { type: n.type, content: n.content, is_pinned: !!n.is_pinned };
    noteError.value = '';
};

const cancelEditNote = () => {
    editingNoteId.value = null;
    noteError.value = '';
};

const saveNewNote = () => {
    noteSaving.value = true;
    noteError.value = '';

    router.post(`/pila/affiliations/${a.value.id}/notes`, noteDraft.value, {
        preserveScroll: true,
        onSuccess: () => {
            noteDraftOpen.value = false;
            resetDraft();
        },
        onError: (errs) => {
            noteError.value = errs?.content || errs?.type || 'No se pudo guardar la nota.';
        },
        onFinish: () => {
            noteSaving.value = false;
        },
    });
};

const saveEditNote = () => {
    if (!editingNoteId.value) return;
    noteSaving.value = true;
    noteError.value = '';

    router.put(`/pila/affiliations/${a.value.id}/notes/${editingNoteId.value}`, editDraft.value, {
        preserveScroll: true,
        onSuccess: () => {
            editingNoteId.value = null;
        },
        onError: (errs) => {
            noteError.value = errs?.content || errs?.type || 'No se pudo actualizar la nota.';
        },
        onFinish: () => {
            noteSaving.value = false;
        },
    });
};

const deleteNote = (id) => {
    if (!confirm('¿Eliminar nota?')) return;
    noteSaving.value = true;
    noteError.value = '';

    router.delete(`/pila/affiliations/${a.value.id}/notes/${id}`, {
        preserveScroll: true,
        onFinish: () => {
            noteSaving.value = false;
        },
    });
};
</script>

<template>
    <AppLayout>
        <div class="max-w-4xl mx-auto space-y-6">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <Link href="/pila/affiliations" class="inline-flex items-center gap-2 text-sm text-gray-500 hover:text-[#0F6E56]">
                        <ChevronLeft class="h-4 w-4" />
                        Volver a afiliaciones
                    </Link>

                    <div class="mt-3 flex items-start gap-4">
                        <div class="h-[52px] w-[52px] rounded-full bg-[#E1F5EE] text-[#0F6E56] flex items-center justify-center text-lg font-semibold">
                            {{ initials || 'AF' }}
                        </div>

                        <div>
                            <div class="text-[22px] font-medium text-gray-900 leading-7">
                                {{ a.affiliate?.full_name || 'Afiliación' }}
                            </div>

                            <div class="mt-2 flex flex-wrap items-center gap-2">
                                <span
                                    class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold border"
                                    :class="a.is_current ? 'bg-green-50 text-green-700 border-green-200' : 'bg-gray-100 text-gray-600 border-gray-200'"
                                >
                                    {{ affiliateActiveLabel }}
                                </span>
                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold border bg-blue-50 text-blue-700 border-blue-200">
                                    {{ clientTypeLabel }}
                                </span>
                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold border bg-purple-50 text-purple-700 border-purple-200">
                                    {{ cotizanteBadgeLabel }}
                                </span>
                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold border bg-gray-100 text-gray-600 border-gray-200">
                                    {{ docBadgeLabel }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="flex items-center gap-2">
                    <Link
                        :href="`/pila/affiliations/${a.id}/edit`"
                        class="inline-flex items-center justify-center px-4 py-2 font-medium rounded-lg bg-[#0F6E56] text-white hover:bg-[#0D5E4B]"
                    >
                        <Pencil class="h-4 w-4 mr-2" />
                        Editar
                    </Link>
                    <button
                        type="button"
                        @click="destroy"
                        class="inline-flex items-center justify-center px-4 py-2 font-medium rounded-lg bg-gray-100 text-gray-700 hover:bg-gray-200"
                    >
                        <Trash2 class="h-4 w-4 mr-2" />
                        Eliminar
                    </button>
                </div>
            </div>

            <!-- Card 1: Datos personales -->
            <section class="bg-white rounded-xl border border-gray-200 p-6">
                <div class="flex items-center gap-3 mb-4">
                    <div class="h-11 w-11 rounded-xl bg-[#E1F5EE] text-[#0F6E56] flex items-center justify-center">
                        <User class="h-5 w-5" />
                    </div>
                    <h2 class="text-sm font-semibold text-gray-900 tracking-[0.05em]">
                        Datos personales
                    </h2>
                </div>

                <div
                    v-if="personalIncomplete"
                    class="mb-4 rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 flex items-start gap-2 text-amber-800 text-sm"
                >
                    <AlertTriangle class="h-4 w-4 mt-0.5" />
                    <span>Teléfono, correo y dirección aún no registrados para este afiliado.</span>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-4">
                    <div>
                        <div class="text-[11px] font-medium text-gray-500 uppercase tracking-[0.05em] mb-1">Nombre completo</div>
                        <div class="text-[14px] font-medium text-gray-900">
                            {{ a.affiliate?.full_name || emptyValue }}
                        </div>
                    </div>

                    <div>
                        <div class="text-[11px] font-medium text-gray-500 uppercase tracking-[0.05em] mb-1">Documento</div>
                        <div class="text-[14px] font-medium text-gray-900">
                            <template v-if="!isEmpty(a.affiliate?.document_number)">
                                {{ a.affiliate?.document_type_abbreviation }} {{ a.affiliate?.document_number }}
                            </template>
                            <template v-else>
                                <span class="text-gray-500 italic text-[13px]">{{ emptyValue }}</span>
                            </template>
                        </div>
                    </div>

                    <div>
                        <div class="text-[11px] font-medium text-gray-500 uppercase tracking-[0.05em] mb-1">Teléfono</div>
                        <div class="text-[14px] font-medium text-gray-900">
                            <span v-if="!isEmpty(a.affiliate?.phone)">{{ a.affiliate?.phone }}</span>
                            <span v-else class="text-gray-500 italic text-[13px]">{{ emptyValue }}</span>
                        </div>
                    </div>

                    <div>
                        <div class="text-[11px] font-medium text-gray-500 uppercase tracking-[0.05em] mb-1">Correo</div>
                        <div class="text-[14px] font-medium text-gray-900">
                            <span v-if="!isEmpty(a.affiliate?.email)">{{ a.affiliate?.email }}</span>
                            <span v-else class="text-gray-500 italic text-[13px]">{{ emptyValue }}</span>
                        </div>
                    </div>

                    <div class="sm:col-span-2">
                        <div class="text-[11px] font-medium text-gray-500 uppercase tracking-[0.05em] mb-1">Dirección</div>
                        <div class="text-[14px] font-medium text-gray-900">
                            <span v-if="!isEmpty(a.affiliate?.address)">{{ a.affiliate?.address }}</span>
                            <span v-else class="text-gray-500 italic text-[13px]">{{ emptyValue }}</span>
                        </div>
                    </div>

                    <div>
                        <div class="text-[11px] font-medium text-gray-500 uppercase tracking-[0.05em] mb-1">Ciudad</div>
                        <div class="text-[14px] font-medium text-gray-900">
                            <span v-if="!isEmpty(a.affiliate?.city)">{{ a.affiliate?.city }}</span>
                            <span v-else class="text-gray-500 italic text-[13px]">{{ emptyValue }}</span>
                        </div>
                    </div>

                    <div>
                        <div class="text-[11px] font-medium text-gray-500 uppercase tracking-[0.05em] mb-1">Departamento</div>
                        <div class="text-[14px] font-medium text-gray-900">
                            <span v-if="!isEmpty(a.affiliate?.department)">{{ a.affiliate?.department }}</span>
                            <span v-else class="text-gray-500 italic text-[13px]">{{ emptyValue }}</span>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Card 2: Empleador / pagador -->
            <section class="bg-white rounded-xl border border-gray-200 p-6">
                <div class="flex items-center gap-3 mb-4">
                    <div class="h-11 w-11 rounded-xl bg-[#E6F1FB] text-[#185FA5] flex items-center justify-center">
                        <Building2 class="h-5 w-5" />
                    </div>
                    <h2 class="text-sm font-semibold text-gray-900 tracking-[0.05em]">
                        Empleador / pagador
                    </h2>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-4">
                    <div class="sm:col-span-2">
                        <div class="text-[11px] font-medium text-gray-500 uppercase tracking-[0.05em] mb-1">Razón social</div>
                        <div class="text-[14px] font-medium text-gray-900">
                            {{ a.employer?.name || emptyValue }}
                        </div>
                    </div>

                    <div>
                        <div class="text-[11px] font-medium text-gray-500 uppercase tracking-[0.05em] mb-1">NIT/Documento</div>
                        <div class="text-[14px] font-medium text-gray-900">
                            <span v-if="!isEmpty(a.employer?.document_number)">
                                {{ a.employer?.document_type }} {{ a.employer?.document_number }}
                            </span>
                            <span v-else class="text-gray-500 italic text-[13px]">{{ emptyValue }}</span>
                        </div>
                    </div>

                    <div>
                        <div class="text-[11px] font-medium text-gray-500 uppercase tracking-[0.05em] mb-1">Día hábil de pago</div>
                        <div class="text-[14px] font-medium text-gray-900">
                            <template v-if="hasEmployerDay">
                                {{ effectivePaymentDay }}
                            </template>
                            <template v-else>
                                <div class="flex items-center gap-2 text-amber-700">
                                    <AlertTriangle class="h-4 w-4" />
                                    <Link :href="`/pila/affiliations/${a.id}/edit`" class="underline">
                                        Requiere día hábil
                                    </Link>
                                </div>
                            </template>
                        </div>
                    </div>

                    <div>
                        <div class="text-[11px] font-medium text-gray-500 uppercase tracking-[0.05em] mb-1">Operador PILA</div>
                        <div class="text-[14px] font-medium text-gray-900">
                            <span v-if="!isEmpty(a.pila_operator)">{{ a.pila_operator }}</span>
                            <span v-else class="text-gray-500 italic text-[13px]">{{ emptyValue }}</span>
                        </div>
                    </div>

                    <div>
                        <div class="text-[11px] font-medium text-gray-500 uppercase tracking-[0.05em] mb-1">Fecha límite estimada</div>
                        <div class="text-[14px] font-medium text-gray-900">
                            <template v-if="!isEmpty(displayNextDueDate)">
                                <span class="inline-flex items-center gap-2">
                                    <Calendar class="h-4 w-4 text-gray-500" />
                                    {{ displayNextDueDate }}
                                </span>
                            </template>
                            <template v-else>
                                <div class="flex items-center gap-2 text-amber-700">
                                    <AlertTriangle class="h-4 w-4" />
                                    <Link :href="`/pila/affiliations/${a.id}/edit`" class="underline">
                                        Requiere día hábil
                                    </Link>
                                </div>
                            </template>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Card 3: Entidades de seguridad social -->
            <section class="bg-white rounded-xl border border-gray-200 p-6">
                <div class="flex items-center gap-3 mb-4">
                    <div class="h-11 w-11 rounded-xl bg-[#EEEDFE] text-[#534AB7] flex items-center justify-center">
                        <ShieldCheck class="h-5 w-5" />
                    </div>
                    <h2 class="text-sm font-semibold text-gray-900 tracking-[0.05em]">
                        Entidades de seguridad social
                    </h2>
                </div>

                <div
                    v-if="!(a.eps && a.afp && a.arp && a.ccf)"
                    class="mb-4 rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 flex items-start gap-2 text-amber-800 text-sm"
                >
                    <AlertTriangle class="h-4 w-4 mt-0.5" />
                    <span>EPS, AFP, ARL y CCF deben estar registrados para completar este afiliado.</span>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-4 mb-4">
                    <div>
                        <div class="text-[11px] font-medium text-gray-500 uppercase tracking-[0.05em] mb-1">IBC</div>
                        <div class="text-[18px] font-semibold text-blue-700">
                            <template v-if="!isEmpty(formatMoney(a.ibc))">
                                {{ formatMoney(a.ibc) }}
                            </template>
                            <template v-else>
                                <span class="text-gray-500 italic text-[13px]">{{ emptyValue }}</span>
                            </template>
                        </div>
                    </div>
                    <div>
                        <div class="text-[11px] font-medium text-gray-500 uppercase tracking-[0.05em] mb-1">Parafiscales</div>
                        <div class="text-[14px] font-semibold">
                            <span
                                v-if="a.pays_parafiscales === true"
                                class="inline-flex items-center px-2.5 py-1 rounded-full text-xs bg-green-50 text-green-700 border border-green-200"
                            >
                                Sí
                            </span>
                            <span
                                v-else-if="a.pays_parafiscales === false"
                                class="inline-flex items-center px-2.5 py-1 rounded-full text-xs bg-gray-100 text-gray-600 border border-gray-200"
                            >
                                No
                            </span>
                            <span v-else class="text-gray-500 italic text-[13px]">{{ emptyValue }}</span>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div class="rounded-xl border border-gray-200 p-4 bg-white">
                        <div class="flex items-start gap-3">
                            <span class="h-2.5 w-2.5 mt-2 rounded-full bg-blue-500" />
                            <div class="min-w-0">
                                <div class="text-[11px] uppercase tracking-[0.05em] text-gray-500 font-medium mb-1">EPS</div>
                                <div class="text-[14px] font-medium text-gray-900">
                                    <template v-if="!isEmpty(a.eps?.name)">
                                        {{ a.eps?.name }}
                                    </template>
                                    <template v-else>
                                        <span class="text-gray-500 italic text-[13px]">{{ emptyValue }}</span>
                                    </template>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="rounded-xl border border-gray-200 p-4 bg-white">
                        <div class="flex items-start gap-3">
                            <span class="h-2.5 w-2.5 mt-2 rounded-full bg-[#993C1D]" />
                            <div class="min-w-0">
                                <div class="text-[11px] uppercase tracking-[0.05em] text-gray-500 font-medium mb-1">AFP</div>
                                <div class="text-[14px] font-medium text-gray-900">
                                    <template v-if="!isEmpty(a.afp?.name)">
                                        {{ a.afp?.name }}
                                    </template>
                                    <template v-else>
                                        <span class="text-gray-500 italic text-[13px]">{{ emptyValue }}</span>
                                    </template>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="rounded-xl border border-gray-200 p-4 bg-white">
                        <div class="flex items-start gap-3">
                            <span class="h-2.5 w-2.5 mt-2 rounded-full bg-[#854F0B]" />
                            <div class="min-w-0">
                                <div class="text-[11px] uppercase tracking-[0.05em] text-gray-500 font-medium mb-1">ARL</div>
                                <div class="text-[14px] font-medium text-gray-900">
                                    <template v-if="!isEmpty(a.arp?.name)">
                                        {{ a.arp?.name }}
                                    </template>
                                    <template v-else>
                                        <span class="text-gray-500 italic text-[13px]">{{ emptyValue }}</span>
                                    </template>
                                </div>
                                <div v-if="a.risk_class && !isEmpty(a.risk_class.description)" class="text-[12px] text-gray-500 mt-1">
                                    Clase de riesgo: {{ a.risk_class.level === 0 ? 'No aplica' : `${a.risk_class.level}` }}
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="rounded-xl border border-gray-200 p-4 bg-white">
                        <div class="flex items-start gap-3">
                            <span class="h-2.5 w-2.5 mt-2 rounded-full bg-[#534AB7]" />
                            <div class="min-w-0">
                                <div class="text-[11px] uppercase tracking-[0.05em] text-gray-500 font-medium mb-1">CCF</div>
                                <div class="text-[14px] font-medium text-gray-900">
                                    <template v-if="!isEmpty(a.ccf?.name)">
                                        {{ a.ccf?.name }}
                                    </template>
                                    <template v-else>
                                        <span class="text-gray-500 italic text-[13px]">{{ emptyValue }}</span>
                                    </template>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Card 4: Estado de pago -->
            <section class="bg-white rounded-xl border border-gray-200 p-6">
                <div class="flex items-center gap-3 mb-4">
                    <div class="h-11 w-11 rounded-xl bg-[#FAEEDA] text-[#854F0B] flex items-center justify-center">
                        <CreditCard class="h-5 w-5" />
                    </div>
                    <h2 class="text-sm font-semibold text-gray-900 tracking-[0.05em]">
                        Estado de pago
                    </h2>
                </div>

                <div
                    v-if="paymentIncomplete"
                    class="mb-4 rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 flex items-start gap-2 text-amber-800 text-sm"
                >
                    <AlertTriangle class="h-4 w-4 mt-0.5" />
                    <span>Estado, último período pagado y comprobante aún no registrados para este afiliado.</span>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div>
                        <div class="text-[11px] font-medium text-gray-500 uppercase tracking-[0.05em] mb-1">Estado</div>
                        <div class="text-[14px] font-medium text-gray-900">
                            <template v-if="!isEmpty(a.payment_status)">
                                <span
                                    v-if="a.payment_status === 'overdue'"
                                    class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-red-50 text-red-700 border border-red-200"
                                >
                                    {{ paymentStatusMeta(a.payment_status).text }}
                                </span>
                                <span
                                    v-else-if="a.payment_status === 'current'"
                                    class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-green-50 text-green-700 border border-green-200"
                                >
                                    {{ paymentStatusMeta(a.payment_status).text }}
                                </span>
                                <span
                                    v-else
                                    class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-gray-100 text-gray-600 border border-gray-200"
                                >
                                    {{ paymentStatusMeta(a.payment_status).text }}
                                </span>
                            </template>
                            <template v-else>
                                <span class="text-gray-500 italic text-[13px]">{{ emptyValue }}</span>
                            </template>
                        </div>
                    </div>

                    <div>
                        <div class="text-[11px] font-medium text-gray-500 uppercase tracking-[0.05em] mb-1">Último período pagado</div>
                        <div class="text-[14px] font-medium text-gray-900">
                            <template v-if="!isEmpty(a.last_payment_period)">
                                {{ a.last_payment_period }}
                            </template>
                            <template v-else>
                                <span class="text-gray-500 italic text-[13px]">{{ emptyValue }}</span>
                            </template>
                        </div>
                    </div>

                    <div>
                        <div class="text-[11px] font-medium text-gray-500 uppercase tracking-[0.05em] mb-1">Tipo comprobante</div>
                        <div class="text-[14px] font-medium text-gray-900">
                            <template v-if="!isEmpty(a.billing_type)">
                                {{ a.billing_type }}
                            </template>
                            <template v-else>
                                <span class="text-gray-500 italic text-[13px]">{{ emptyValue }}</span>
                            </template>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Credenciales -->
            <section v-if="false" class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                <h2 class="flex items-center justify-between gap-3 text-sm font-semibold text-gray-900 uppercase tracking-wide mb-4">
                    <span class="inline-flex items-center gap-2">
                        <KeyRound class="h-4 w-4" />
                        Credenciales
                    </span>
                    <button
                        type="button"
                        class="text-xs font-semibold text-gray-600 hover:text-gray-900"
                        @click="loadCredentials"
                        :disabled="loadingCreds"
                    >
                        {{ loadingCreds ? 'Cargando…' : 'Actualizar' }}
                    </button>
                </h2>

                <p v-if="credsError" class="text-sm text-red-600 mb-3">{{ credsError }}</p>
                <p v-else-if="credsSuccess" class="text-sm text-green-600 mb-3">{{ credsSuccess }}</p>

                <div class="space-y-4">
                    <div>
                        <h3 class="text-xs font-bold tracking-widest text-gray-400 uppercase mb-2">Operador PILA (Empleador)</h3>
                        <div v-if="!pilaCreds.length" class="text-sm text-gray-500">Sin credenciales PILA registradas.</div>
                        <div v-else class="divide-y divide-gray-100 border border-gray-200 rounded-lg overflow-hidden">
                            <div v-for="c in pilaCreds" :key="c.id" class="p-3 flex items-center gap-3">
                                <div class="min-w-0 flex-1">
                                    <div class="text-sm font-semibold text-gray-900">{{ c.operator }}</div>
                                    <div class="text-xs text-gray-500">Usuario: {{ c.username || emptyValue }}</div>
                                </div>
                                <div class="flex items-center gap-2">
                                    <button
                                        v-if="!revealed[`pila:${c.id}`]"
                                        type="button"
                                        class="inline-flex items-center gap-2 px-3 py-1.5 rounded-lg bg-gray-100 hover:bg-gray-200 text-sm font-medium text-gray-700"
                                        @click="revealPassword('pila', c.id)"
                                    >
                                        <Eye class="h-4 w-4" />
                                        Ver
                                    </button>
                                    <template v-else>
                                        <div class="text-sm font-mono px-3 py-1.5 rounded-lg border border-gray-200 bg-white">
                                            <span v-if="revealed[`pila:${c.id}`].visible">{{ revealed[`pila:${c.id}`].value ?? emptyValue }}</span>
                                            <span v-else>••••••••</span>
                                        </div>
                                        <button
                                            type="button"
                                            class="inline-flex items-center justify-center h-9 w-9 rounded-lg bg-gray-100 hover:bg-gray-200 text-gray-700"
                                            @click="toggleVisible('pila', c.id)"
                                            :aria-label="revealed[`pila:${c.id}`].visible ? 'Ocultar' : 'Mostrar'"
                                        >
                                            <EyeOff v-if="revealed[`pila:${c.id}`].visible" class="h-4 w-4" />
                                            <Eye v-else class="h-4 w-4" />
                                        </button>
                                    </template>

                                    <button
                                        type="button"
                                        class="ml-1 inline-flex items-center px-3 py-1.5 rounded-lg bg-brand-50 hover:bg-brand-100 text-sm font-semibold text-brand-700"
                                        @click="openPilaEdit(c)"
                                    >
                                        Cambiar
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Edit / upsert PILA -->
                        <div class="mt-4 border border-gray-200 rounded-lg p-3 space-y-3">
                            <h4 class="text-sm font-semibold text-gray-900">Registrar / editar PILA</h4>
                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                                <div>
                                    <label class="block text-xs font-medium text-gray-500">Operador</label>
                                    <input
                                        v-model="pilaCreateForm.operator"
                                        class="mt-1 w-full rounded-lg border-gray-300 shadow-sm text-sm"
                                        placeholder="Ej: arus, simple..."
                                    />
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-gray-500">Usuario</label>
                                    <input
                                        v-model="pilaCreateForm.username"
                                        class="mt-1 w-full rounded-lg border-gray-300 shadow-sm text-sm"
                                        placeholder="Usuario PILA"
                                    />
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-gray-500">Contraseña</label>
                                    <input
                                        v-model="pilaCreateForm.password"
                                        type="password"
                                        class="mt-1 w-full rounded-lg border-gray-300 shadow-sm text-sm"
                                        placeholder="Nueva contraseña"
                                    />
                                </div>
                            </div>
                            <div class="flex items-center justify-end gap-2">
                                <button
                                    type="button"
                                    class="px-4 py-2 rounded-lg bg-brand-500 text-white text-sm font-semibold hover:bg-brand-600 disabled:opacity-50"
                                    :disabled="loadingCreds || !pilaCreateForm.operator || !pilaCreateForm.username || !pilaCreateForm.password || (pilaCreateForm.password?.length ?? 0) < 6"
                                    @click="submitPilaCreate"
                                >
                                    Guardar
                                </button>
                            </div>
                        </div>
                    </div>

                    <div>
                        <h3 class="text-xs font-bold tracking-widest text-gray-400 uppercase mb-2">Portales (ARL / EPS / AFP / CCF)</h3>
                        <div v-if="!portalCreds.length" class="text-sm text-gray-500">Sin credenciales de portales registradas.</div>
                        <div v-else class="divide-y divide-gray-100 border border-gray-200 rounded-lg overflow-hidden">
                            <div v-for="c in portalCreds" :key="c.id" class="p-3 flex items-center gap-3">
                                <div class="min-w-0 flex-1">
                                    <div class="text-sm font-semibold text-gray-900">{{ c.entity_type }}</div>
                                    <div class="text-xs text-gray-500">
                                        <span v-if="c.is_not_applicable">No aplica</span>
                                        <span v-else>Usuario: {{ c.username || emptyValue }}</span>
                                    </div>
                                </div>
                                <div class="flex items-center gap-2">
                                    <span v-if="c.is_not_applicable" class="text-xs font-semibold px-2 py-1 rounded-full bg-gray-100 text-gray-700">N/A</span>
                                    <template v-else>
                                        <button
                                            v-if="!revealed[`portal:${c.id}`]"
                                            type="button"
                                            class="inline-flex items-center gap-2 px-3 py-1.5 rounded-lg bg-gray-100 hover:bg-gray-200 text-sm font-medium text-gray-700"
                                            @click="revealPassword('portal', c.id)"
                                        >
                                            <Eye class="h-4 w-4" />
                                            Ver
                                        </button>
                                        <template v-else>
                                            <div class="text-sm font-mono px-3 py-1.5 rounded-lg border border-gray-200 bg-white">
                                                <span v-if="revealed[`portal:${c.id}`].visible">{{ revealed[`portal:${c.id}`].value ?? emptyValue }}</span>
                                                <span v-else>••••••••</span>
                                            </div>
                                            <button
                                                type="button"
                                                class="inline-flex items-center justify-center h-9 w-9 rounded-lg bg-gray-100 hover:bg-gray-200 text-gray-700"
                                                @click="toggleVisible('portal', c.id)"
                                                :aria-label="revealed[`portal:${c.id}`].visible ? 'Ocultar' : 'Mostrar'"
                                            >
                                                <EyeOff v-if="revealed[`portal:${c.id}`].visible" class="h-4 w-4" />
                                                <Eye v-else class="h-4 w-4" />
                                            </button>
                                        </template>
                                    </template>

                                    <button
                                        v-if="!c.is_not_applicable"
                                        type="button"
                                        class="ml-1 inline-flex items-center px-3 py-1.5 rounded-lg bg-brand-50 hover:bg-brand-100 text-sm font-semibold text-brand-700"
                                        @click="openPortalEdit(c)"
                                    >
                                        Cambiar
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div class="mt-4 border border-gray-200 rounded-lg p-3 space-y-3">
                            <h4 class="text-sm font-semibold text-gray-900">Registrar / editar portal</h4>

                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                                <div>
                                    <label class="block text-xs font-medium text-gray-500">Entidad</label>
                                    <select
                                        v-model="portalCreateForm.entity_type"
                                        class="mt-1 w-full rounded-lg border-gray-300 shadow-sm text-sm"
                                    >
                                        <option value="ARL">ARL</option>
                                        <option value="EPS">EPS</option>
                                        <option value="AFP">AFP</option>
                                        <option value="CCF">CCF</option>
                                    </select>
                                </div>

                                <div class="sm:col-span-1 flex items-center gap-2 mt-6">
                                    <input
                                        id="portal-not-app"
                                        type="checkbox"
                                        v-model="portalCreateForm.is_not_applicable"
                                    />
                                    <label for="portal-not-app" class="text-sm text-gray-700 font-medium">No aplica</label>
                                </div>

                                <div v-if="!portalCreateForm.is_not_applicable">
                                    <label class="block text-xs font-medium text-gray-500">Usuario</label>
                                    <input
                                        v-model="portalCreateForm.username"
                                        class="mt-1 w-full rounded-lg border-gray-300 shadow-sm text-sm"
                                        placeholder="Usuario portal"
                                    />
                                </div>

                                <div v-if="!portalCreateForm.is_not_applicable">
                                    <label class="block text-xs font-medium text-gray-500">Contraseña</label>
                                    <input
                                        v-model="portalCreateForm.password"
                                        type="password"
                                        class="mt-1 w-full rounded-lg border-gray-300 shadow-sm text-sm"
                                        placeholder="Nueva contraseña"
                                    />
                                </div>
                            </div>

                            <div class="flex items-center justify-end gap-2">
                                <button
                                    type="button"
                                    class="px-4 py-2 rounded-lg bg-brand-500 text-white text-sm font-semibold hover:bg-brand-600 disabled:opacity-50"
                                    :disabled="loadingCreds || (!portalCreateForm.is_not_applicable && (!portalCreateForm.username || !portalCreateForm.password || (portalCreateForm.password?.length ?? 0) < 6))"
                                    @click="submitPortalCreate"
                                >
                                    Guardar
                                </button>
                            </div>
                        </div>
                    </div>

                    <div v-if="canViewAudit" class="border border-gray-200 rounded-lg p-4">
                        <h3 class="text-sm font-semibold text-gray-900 uppercase tracking-wide flex items-center justify-between gap-3">
                            Auditoría de credenciales
                            <button
                                type="button"
                                class="text-xs font-semibold text-gray-600 hover:text-gray-900 disabled:opacity-50"
                                :disabled="auditLoading"
                                @click="loadAuditLogs"
                            >
                                {{ auditLoading ? 'Cargando…' : 'Actualizar' }}
                            </button>
                        </h3>

                        <p v-if="auditError" class="text-sm text-red-600 mt-2">{{ auditError }}</p>
                        <div v-else class="mt-3">
                            <div v-if="auditLoading" class="text-sm text-gray-500">Cargando auditoría…</div>
                            <div v-else-if="!auditLogs.length" class="text-sm text-gray-500">Sin registros de auditoría.</div>
                            <div v-else class="space-y-2">
                                <div
                                    v-for="log in auditLogs"
                                    :key="log.id"
                                    class="p-3 rounded-lg border border-gray-100 bg-white"
                                >
                                    <div class="text-xs text-gray-500 flex items-center justify-between gap-3">
                                        <span>{{ log.created_at }}</span>
                                        <span class="font-medium">{{ log.action }}</span>
                                    </div>
                                    <div class="text-sm font-semibold text-gray-900 mt-1">
                                        {{ log.credential_kind }} #{{ log.credential_id }}
                                    </div>
                                    <div class="text-xs text-gray-600 mt-1">
                                        Usuario: {{ log.user || emptyValue }} · IP: {{ log.ip_address || emptyValue }}
                                    </div>
                                    <div v-if="log.metadata" class="text-xs text-gray-700 mt-1">
                                        {{
                                            log.metadata.operator
                                                ? `Operador: ${log.metadata.operator}`
                                                : (log.metadata.entity_type
                                                    ? `Entidad: ${log.metadata.entity_type}`
                                                    : `Metadata: ${JSON.stringify(log.metadata)}`)
                                        }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Notas operativas -->
            <section class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                <div class="flex items-center justify-between gap-3">
                    <h2 class="text-sm font-semibold text-gray-900 tracking-[0.05em]">
                        Notas operativas
                    </h2>
                    <button
                        type="button"
                        class="inline-flex items-center gap-2 px-3 py-2 rounded-lg text-sm font-medium bg-gray-100 text-gray-700 hover:bg-gray-200"
                        @click="noteDraftOpen = !noteDraftOpen; if (!noteDraftOpen) resetDraft()"
                    >
                        {{ noteDraftOpen ? 'Cerrar' : 'Nueva nota' }}
                    </button>
                </div>

                <div v-if="noteDraftOpen" class="mt-4 rounded-lg border border-gray-200 bg-gray-50 p-4">
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                        <div class="sm:col-span-1">
                            <label class="block text-xs font-medium text-gray-600 uppercase tracking-wide mb-1">Tipo</label>
                            <select v-model="noteDraft.type" class="w-full rounded-lg border-gray-300 text-sm focus:border-[#0F6E56] focus:ring-[#0F6E56]">
                                <option value="general">General</option>
                                <option value="affiliation">Afiliación</option>
                                <option value="payment">Pago</option>
                            </select>
                        </div>
                        <div class="sm:col-span-2 flex items-end">
                            <label class="inline-flex items-center gap-2 text-sm text-gray-700">
                                <input v-model="noteDraft.is_pinned" type="checkbox" class="rounded border-gray-300 text-[#0F6E56] focus:ring-[#0F6E56]" />
                                Fijar nota
                            </label>
                        </div>
                    </div>
                    <div class="mt-3">
                        <label class="block text-xs font-medium text-gray-600 uppercase tracking-wide mb-1">Contenido</label>
                        <textarea
                            v-model="noteDraft.content"
                            rows="3"
                            class="w-full rounded-lg border-gray-300 text-sm focus:border-[#0F6E56] focus:ring-[#0F6E56]"
                            placeholder="Escribe la nota..."
                        />
                        <p v-if="noteError" class="mt-2 text-sm text-red-600">{{ noteError }}</p>
                    </div>
                    <div class="mt-3 flex justify-end">
                        <button
                            type="button"
                            :disabled="noteSaving"
                            class="inline-flex items-center justify-center px-4 py-2 rounded-lg text-sm font-medium bg-[#0F6E56] text-white hover:bg-[#0B5946] disabled:opacity-60"
                            @click="saveNewNote"
                        >
                            Guardar nota
                        </button>
                    </div>
                </div>

                <div class="mt-4">
                    <div v-if="!props.notes?.length" class="py-3 text-sm text-gray-500 italic">
                        Sin notas registradas.
                    </div>

                    <div v-else class="divide-y divide-gray-100">
                        <div v-for="n in props.notes" :key="n.id" class="py-4">
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0 flex-1">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <span
                                            v-if="n.is_pinned"
                                            class="inline-flex items-center px-2 py-0.5 rounded-full text-xs bg-amber-50 text-amber-800 border border-amber-100"
                                        >Fijada</span>
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs bg-gray-100 text-gray-700">
                                            {{ n.type === 'payment' ? 'Pago' : n.type === 'affiliation' ? 'Afiliación' : 'General' }}
                                        </span>
                                        <span class="text-xs text-gray-500" v-if="n.created_by">· {{ n.created_by }}</span>
                                    </div>

                                    <div v-if="editingNoteId !== n.id" class="mt-2 text-sm text-gray-900 whitespace-pre-wrap break-words">
                                        {{ n.content }}
                                    </div>

                                    <div v-else class="mt-3 space-y-3">
                                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                                            <div class="sm:col-span-1">
                                                <label class="block text-xs font-medium text-gray-600 uppercase tracking-wide mb-1">Tipo</label>
                                                <select v-model="editDraft.type" class="w-full rounded-lg border-gray-300 text-sm focus:border-[#0F6E56] focus:ring-[#0F6E56]">
                                                    <option value="general">General</option>
                                                    <option value="affiliation">Afiliación</option>
                                                    <option value="payment">Pago</option>
                                                </select>
                                            </div>
                                            <div class="sm:col-span-2 flex items-end">
                                                <label class="inline-flex items-center gap-2 text-sm text-gray-700">
                                                    <input v-model="editDraft.is_pinned" type="checkbox" class="rounded border-gray-300 text-[#0F6E56] focus:ring-[#0F6E56]" />
                                                    Fijar nota
                                                </label>
                                            </div>
                                        </div>
                                        <div>
                                            <label class="block text-xs font-medium text-gray-600 uppercase tracking-wide mb-1">Contenido</label>
                                            <textarea
                                                v-model="editDraft.content"
                                                rows="3"
                                                class="w-full rounded-lg border-gray-300 text-sm focus:border-[#0F6E56] focus:ring-[#0F6E56]"
                                            />
                                        </div>
                                        <p v-if="noteError" class="text-sm text-red-600">{{ noteError }}</p>
                                        <div class="flex items-center justify-end gap-2">
                                            <button type="button" class="px-3 py-2 rounded-lg text-sm bg-gray-100 text-gray-700 hover:bg-gray-200" @click="cancelEditNote">
                                                Cancelar
                                            </button>
                                            <button
                                                type="button"
                                                :disabled="noteSaving"
                                                class="px-3 py-2 rounded-lg text-sm font-medium bg-[#0F6E56] text-white hover:bg-[#0B5946] disabled:opacity-60"
                                                @click="saveEditNote"
                                            >
                                                Guardar
                                            </button>
                                        </div>
                                    </div>
                                </div>

                                <div v-if="editingNoteId !== n.id" class="flex items-center gap-2">
                                    <button
                                        type="button"
                                        class="p-2 rounded-lg text-gray-600 hover:bg-gray-100"
                                        title="Editar"
                                        @click="openEditNote(n)"
                                    >
                                        <Pencil class="h-4 w-4" />
                                    </button>
                                    <button
                                        type="button"
                                        class="p-2 rounded-lg text-red-600 hover:bg-red-50"
                                        title="Eliminar"
                                        @click="deleteNote(n.id)"
                                    >
                                        <Trash2 class="h-4 w-4" />
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <section class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                <div class="flex items-center gap-3 mb-4">
                    <div class="h-11 w-11 rounded-xl bg-[#E1F5EE] text-[#0F6E56] flex items-center justify-center">
                        <KeyRound class="h-5 w-5" />
                    </div>
                    <h2 class="text-sm font-semibold text-gray-900 tracking-[0.05em]">
                        Credenciales
                    </h2>
                </div>
                <PilaCredentialsPanel
                    :affiliation-id="a.id"
                    :pila-operator="a.pila_operator"
                    :pila-creds="pilaCreds"
                    :portal-creds="portalCreds"
                    :can-view-audit="false"
                    @refresh="loadCredentials"
                />

                <div
                    v-if="$page.props.auth.user.role === 'admin'"
                    class="mt-4 flex justify-end"
                >
                    <button
                        type="button"
                        class="inline-flex items-center gap-2 text-sm font-medium text-gray-700 hover:text-gray-900 bg-white border border-gray-200 rounded-lg px-3 py-2"
                        @click="auditModalOpen = true"
                    >
                        <Clock class="h-4 w-4" />
                        Ver historial de accesos
                    </button>
                </div>

                <CredentialAuditModal
                    v-if="$page.props.auth.user.role === 'admin'"
                    :open="auditModalOpen"
                    :logs="audit_logs"
                    @close="auditModalOpen = false"
                />
            </section>
        </div>
    </AppLayout>
</template>

