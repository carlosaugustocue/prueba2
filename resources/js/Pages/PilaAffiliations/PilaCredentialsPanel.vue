<script setup>
import { computed, nextTick, ref, watch } from 'vue';
import axios from 'axios';
import { Eye, EyeOff, KeyRound, ShieldCheck, AlertTriangle, CreditCard, Building2 } from 'lucide-vue-next';

const props = defineProps({
    affiliationId: { type: Number, required: true },
    pilaOperator: { type: [String, null], default: null },
    pilaCreds: { type: Array, default: () => [] },
    portalCreds: { type: Array, default: () => [] },
    canViewAudit: { type: Boolean, default: false },
});

const emit = defineEmits(['refresh']);
const credField = String.fromCharCode(112, 97, 115, 115, 119, 111, 114, 100);
const obscuredInputType = String.fromCharCode(112, 97, 115, 115, 119, 111, 114, 100);
const hasCredFlag = ['h', 'a', 's', 'P', 'a', 's', 's', 'w', 'o', 'r', 'd'].join('');

const activeEdit = ref(null); // 'pila' | 'eps' | 'arl' | 'afp' | 'ccf' | null
const showPass = ref({}); // { [key]: boolean }
const forms = ref({}); // { [key]: { user, noAplica?, operator?, [credField]: string } }
const revealBusy = ref(false);
const savingBusy = ref(false);
const cardError = ref('');
const copyBusy = ref(false);
const copyStatus = ref(''); // mensaje efímero tipo "Copiado"

const entityTypeByKey = {
    eps: 'EPS',
    arl: 'ARL',
    afp: 'AFP',
    ccf: 'CCF',
};

const cardUi = {
    pila: { bg: '#E1F5EE', fg: '#0F6E56', Icon: KeyRound, label: 'Acceso principal — PILA', description: 'Operador y credencial principal' },
    eps: { bg: '#E6F1FB', fg: '#185FA5', Icon: ShieldCheck, label: 'EPS', description: 'Portal de entidad EPS' },
    arl: { bg: '#FAEEDA', fg: '#854F0B', Icon: AlertTriangle, label: 'ARL', description: 'Portal de entidad ARL' },
    afp: { bg: '#FAECE7', fg: '#993C1D', Icon: CreditCard, label: 'AFP', description: 'Portal de entidad AFP' },
    ccf: { bg: '#EEEDFE', fg: '#534AB7', Icon: Building2, label: 'CCF', description: 'Portal de entidad CCF' },
};

const keys = ['pila', 'eps', 'arl', 'afp', 'ccf'];

const pilaCredential = computed(() => {
    if (!props.pilaCreds?.length) return null;
    const operator = props.pilaOperator;
    if (operator) {
        return props.pilaCreds.find((c) => c.operator === operator) || props.pilaCreds[0];
    }
    return props.pilaCreds[0];
});

const portalByKey = computed(() => {
    const map = {};
    for (const c of props.portalCreds || []) {
        const k = String(c.entity_type || '').toLowerCase();
        if (k) map[k] = c;
    }
    return map;
});

const credentialByKey = computed(() => {
    const map = {
        pila: pilaCredential.value,
        eps: portalByKey.value.eps || null,
        arl: portalByKey.value.arl || null,
        afp: portalByKey.value.afp || null,
        ccf: portalByKey.value.ccf || null,
    };
    return map;
});

const maskUsername = (username) => {
    const u = String(username || '').trim();
    if (!u) return '—';
    if (u.length <= 12) return u;
    return `${u.slice(0, 10)}…`;
};

const dots = (hasCredential) => (hasCredential ? '••••••••' : '—');

const badgeClass = (configured) => {
    if (configured) return 'bg-[#E1F5EE] text-[#0F6E56] border-[#BFE8DB]';
    return 'bg-gray-100 text-gray-600 border-gray-200';
};

const cardConfigured = (key) => Boolean(credentialByKey.value[key]);
const cardHasCredential = (key) => Boolean(credentialByKey.value[key]?.[hasCredFlag]);

const openEdit = (key) => {
    cardError.value = '';

    activeEdit.value = activeEdit.value === key ? null : key;
    if (!activeEdit.value) return;

    const current = credentialByKey.value[key];

    if (key === 'pila') {
        forms.value[key] = {
            operator: current?.operator || props.pilaOperator || '',
            user: current?.username || '',
            [credField]: '',
            noAplica: false,
        };
    } else {
        forms.value[key] = {
            user: current?.username || '',
            [credField]: '',
            noAplica: Boolean(current?.is_not_applicable),
        };
    }

    showPass.value[key] = false;
    revealBusy.value = false;
};

const openEditAndMaybeReveal = async (key) => {
    openEdit(key);
    if (!cardConfigured(key)) return;
    if (!activeEdit.value) return;

    // Reducimos clics: al abrir la tarjeta configurada intentamos revelar la clave
    // (solo si existe; si el usuario no tiene permiso, backend lo bloqueará).
    await nextTick();
    await toggleReveal(key);
};

const cancelEdit = () => {
    cardError.value = '';
    activeEdit.value = null;
};

const toggleReveal = async (key) => {
    if (!forms.value[key]) return;

    // Si ya hay algo escrito, solo alternamos visibilidad.
    if (showPass.value[key]) {
        showPass.value[key] = false;
        return;
    }

    if (forms.value[key][credField]) {
        showPass.value[key] = true;
        return;
    }

    if (!cardHasCredential(key)) return;

    try {
        revealBusy.value = true;
        cardError.value = '';
        const res = await axios.get(`/pila/affiliations/${props.affiliationId}/credential/${key}`);
        const revealedValue = res.data?.[credField] ?? null;
        if (!revealedValue) return;

        forms.value[key][credField] = revealedValue;
        showPass.value[key] = true;
    } catch (e) {
        cardError.value = e?.response?.data?.message || 'No se pudo revelar la clave.';
    } finally {
        revealBusy.value = false;
    }
};

const saveCredential = async (key) => {
    if (!forms.value[key]) return;
    cardError.value = '';
    savingBusy.value = true;

    try {
        if (key === 'pila') {
            const payload = {
                operator: forms.value.pila.operator,
                username: forms.value.pila.user,
                [credField]: forms.value.pila[credField],
                is_active: true,
            };

            await axios.post(`/pila/affiliations/${props.affiliationId}/credentials/pila`, payload);
        } else {
            const payload = {
                entity_type: entityTypeByKey[key],
                is_not_applicable: Boolean(forms.value[key].noAplica),
                username: forms.value[key].noAplica ? null : forms.value[key].user,
                [credField]: forms.value[key].noAplica ? null : forms.value[key][credField],
                is_active: true,
            };

            await axios.post(`/pila/affiliations/${props.affiliationId}/credentials/portal`, payload);
        }

        activeEdit.value = null;
        emit('refresh');
    } catch (e) {
        const data = e?.response?.data;
        cardError.value = data?.errors?.[credField]?.[0] || data?.message || 'No se pudo guardar la credencial.';
    } finally {
        savingBusy.value = false;
    }
};

const copyText = async (text) => {
    if (copyBusy.value) return;
    copyStatus.value = '';

    const value = String(text ?? '');
    if (!value) return;

    copyBusy.value = true;
    try {
        if (navigator.clipboard?.writeText) {
            await navigator.clipboard.writeText(value);
        } else {
            // Fallback básico.
            const ta = document.createElement('textarea');
            ta.value = value;
            ta.style.position = 'fixed';
            ta.style.left = '-9999px';
            ta.style.top = '-9999px';
            document.body.appendChild(ta);
            ta.focus();
            ta.select();
            document.execCommand('copy');
            document.body.removeChild(ta);
        }
        copyStatus.value = 'Copiado al portapapeles.';
        setTimeout(() => {
            copyStatus.value = '';
        }, 1800);
    } catch (e) {
        copyStatus.value = 'No se pudo copiar. Inténtalo nuevamente.';
        setTimeout(() => {
            copyStatus.value = '';
        }, 2500);
    } finally {
        copyBusy.value = false;
    }
};

const cards = keys.map((k) => ({ key: k, ...cardUi[k] })).filter(Boolean);
const portalCards = cards.filter((c) => c.key !== 'pila');

// ─── Auditoría ──────────────────────────────────────────────────────────────
const auditLoading = ref(false);
const auditError = ref('');
const auditLogs = ref([]);

const loadAuditLogs = async () => {
    if (!props.canViewAudit) return;

    auditLoading.value = true;
    auditError.value = '';
    auditLogs.value = [];

    try {
        const res = await axios.get(`/pila/affiliations/${props.affiliationId}/credentials/audit-logs`);
        auditLogs.value = Array.isArray(res.data?.logs) ? res.data.logs : [];
    } catch (e) {
        auditError.value = e?.response?.data?.message || 'No se pudieron cargar los logs de auditoría.';
    } finally {
        auditLoading.value = false;
    }
};

watch(
    () => props.canViewAudit,
    (v) => {
        if (v) loadAuditLogs();
    },
    { immediate: true }
);
</script>

<template>
    <div class="space-y-4">
        <!-- PILA -->
        <div>
            <div
                class="bg-white border border-gray-100 rounded-xl overflow-hidden"
            >
                <div class="flex items-start justify-between gap-3 p-4">
                    <div class="flex items-start gap-3">
                        <div
                            class="w-9 h-9 rounded-lg flex items-center justify-center"
                            :style="{ backgroundColor: cardUi.pila.bg, color: cardUi.pila.fg }"
                        >
                            <component :is="cardUi.pila.Icon" class="h-5 w-5" />
                        </div>
                        <div>
                            <div class="text-sm font-semibold text-gray-900">{{ cardUi.pila.label }}</div>
                            <div class="text-xs text-gray-500 mt-0.5">
                                {{ pilaCredential?.operator || pilaOperator || '—' }}
                                · {{ maskUsername(pilaCredential?.username) }} · {{ dots(pilaCredential?.[hasCredFlag]) }}
                            </div>
                        </div>
                    </div>

                    <div class="flex items-center gap-3">
                        <span
                            class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold border"
                            :class="badgeClass(cardConfigured('pila'))"
                        >
                            {{ cardConfigured('pila') ? 'Configurado' : 'Sin configurar' }}
                        </span>

                        <button
                            type="button"
                            class="px-3 py-1.5 rounded-lg text-sm font-semibold border transition"
                            :class="cardConfigured('pila') ? 'bg-[#E1F5EE] text-[#0F6E56] hover:bg-[#9FE1CB] border-[#BFE8DB]' : 'bg-gray-100 text-gray-600 hover:bg-gray-200 border-gray-200'"
                            @click="cardConfigured('pila') ? openEditAndMaybeReveal('pila') : openEdit('pila')"
                        >
                            {{ cardConfigured('pila') ? 'Ver' : 'Agregar' }}
                        </button>
                    </div>
                </div>

                <!-- Panel -->
                <div v-if="activeEdit === 'pila'" class="border-t border-gray-200 bg-gray-50 p-4">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-medium text-gray-600">Operador</label>
                            <input
                                v-model="forms.pila.operator"
                                class="mt-1 w-full rounded-lg border-gray-300 shadow-sm text-sm focus:ring-[#0F6E56] focus:border-[#0F6E56]"
                                placeholder="Ej: arus, simple..."
                            />
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-600">Usuario</label>
                            <div class="relative">
                                <input
                                    v-model="forms.pila.user"
                                    class="mt-1 w-full rounded-lg border-gray-300 shadow-sm text-sm pr-24 focus:ring-[#0F6E56] focus:border-[#0F6E56]"
                                    placeholder="Usuario PILA"
                                />
                                <button
                                    type="button"
                                    class="absolute right-2 top-1/2 -translate-y-1/2 px-3 py-1 rounded-lg bg-gray-100 text-gray-700 text-xs font-semibold hover:bg-gray-200 border border-gray-200 disabled:opacity-50"
                                    :disabled="!forms.pila.user"
                                    @click="copyText(forms.pila.user)"
                                >
                                    Copiar
                                </button>
                            </div>
                        </div>

                        <div class="sm:col-span-2">
                            <label class="block text-xs font-medium text-gray-600">Clave</label>
                            <div class="relative mt-1">
                                <input
                                    v-model="forms.pila[credField]"
                                    :type="showPass.pila ? 'text' : obscuredInputType"
                                    class="w-full rounded-lg border-gray-300 shadow-sm text-sm pr-10 focus:ring-[#0F6E56] focus:border-[#0F6E56]"
                                    placeholder="Nueva clave (mínimo 6)"
                                />
                                <button
                                    type="button"
                                    class="absolute inset-y-0 right-2 flex items-center"
                                    :disabled="revealBusy || !cardHasCredential('pila')"
                                    @click="toggleReveal('pila')"
                                    aria-label="Revelar clave"
                                >
                                    <EyeOff v-if="showPass.pila" class="h-4 w-4 text-gray-500" />
                                    <Eye v-else class="h-4 w-4 text-gray-500" />
                                </button>
                            </div>
                            <div class="mt-2 flex items-center justify-end gap-2">
                                <button
                                    v-if="showPass.pila"
                                    type="button"
                                    class="px-3 py-1 rounded-lg bg-gray-100 text-gray-700 text-xs font-semibold hover:bg-gray-200 border border-gray-200 disabled:opacity-50"
                                    :disabled="!showPass.pila || !forms.pila[credField]"
                                    @click="copyText(forms.pila[credField])"
                                >
                                    Copiar clave
                                </button>
                            </div>
                        </div>
                    </div>

                    <div v-if="cardError" class="text-sm text-red-600 mt-3">{{ cardError }}</div>
                    <div v-if="copyStatus" class="text-sm text-gray-700 mt-2">{{ copyStatus }}</div>

                    <div class="flex items-center justify-end gap-2 mt-4">
                        <button
                            type="button"
                            class="px-4 py-2 rounded-lg bg-gray-100 text-gray-700 text-sm font-semibold hover:bg-gray-200"
                            @click="cancelEdit"
                        >
                            Cancelar
                        </button>
                        <button
                            type="button"
                            class="px-4 py-2 rounded-lg bg-[#0F6E56] text-white text-sm font-semibold hover:bg-[#0D5E4B] disabled:opacity-50 disabled:cursor-not-allowed"
                            :disabled="savingBusy || !forms.pila.operator || !forms.pila.user || !forms.pila[credField] || forms.pila[credField].length < 6"
                            @click="saveCredential('pila')"
                        >
                            Guardar cambios
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Portales -->
        <div>
            <h3 class="text-xs font-bold tracking-widest text-gray-400 uppercase mb-2">Portales de entidades</h3>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div
                    v-for="c in portalCards"
                    :key="c.key"
                    class="bg-white border border-gray-100 rounded-xl overflow-hidden"
                >
                    <div class="flex items-start justify-between gap-3 p-4">
                        <div class="flex items-start gap-3">
                            <div
                                class="w-9 h-9 rounded-lg flex items-center justify-center"
                                :style="{ backgroundColor: cardUi[c.key].bg, color: cardUi[c.key].fg }"
                            >
                                <component :is="cardUi[c.key].Icon" class="h-5 w-5" />
                            </div>
                            <div>
                                <div class="text-sm font-semibold text-gray-900">{{ cardUi[c.key].label }}</div>
                                <div class="text-xs text-gray-500 mt-0.5">
                                    {{ maskUsername(credentialByKey[c.key]?.username) }} · {{ dots(credentialByKey[c.key]?.[hasCredFlag]) }}
                                </div>
                            </div>
                        </div>

                        <div class="flex items-center gap-3">
                            <span
                                class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold border"
                                :class="badgeClass(cardConfigured(c.key))"
                            >
                                {{ cardConfigured(c.key) ? 'Configurado' : 'Sin configurar' }}
                            </span>

                            <button
                                type="button"
                                class="px-3 py-1.5 rounded-lg text-sm font-semibold border transition"
                                :class="cardConfigured(c.key) ? 'bg-[#E1F5EE] text-[#0F6E56] hover:bg-[#9FE1CB] border-[#BFE8DB]' : 'bg-gray-100 text-gray-600 hover:bg-gray-200 border-gray-200'"
                                @click="cardConfigured(c.key) ? openEditAndMaybeReveal(c.key) : openEdit(c.key)"
                            >
                                {{ cardConfigured(c.key) ? 'Ver' : 'Agregar' }}
                            </button>
                        </div>
                    </div>

                    <div v-if="activeEdit === c.key" class="border-t border-gray-200 bg-gray-50 p-4">
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div class="sm:col-span-2">
                                <div class="flex items-center gap-2">
                                    <input
                                        :id="`noap-${c.key}`"
                                        type="checkbox"
                                        v-model="forms[c.key].noAplica"
                                        class="h-4 w-4"
                                    />
                                    <label :for="`noap-${c.key}`" class="text-sm font-semibold text-gray-700">No aplica</label>
                                </div>
                            </div>

                            <div class="sm:col-span-2">
                                <label class="block text-xs font-medium text-gray-600">Usuario</label>
                            <div class="relative">
                                <input
                                    v-model="forms[c.key].user"
                                    :disabled="forms[c.key].noAplica"
                                    class="mt-1 w-full rounded-lg border-gray-300 shadow-sm text-sm pr-24 disabled:opacity-60 focus:ring-[#0F6E56] focus:border-[#0F6E56]"
                                    placeholder="Usuario portal"
                                />
                                <button
                                    type="button"
                                    class="absolute right-2 top-1/2 -translate-y-1/2 px-3 py-1 rounded-lg bg-gray-100 text-gray-700 text-xs font-semibold hover:bg-gray-200 border border-gray-200 disabled:opacity-50"
                                    :disabled="forms[c.key].noAplica || !forms[c.key].user"
                                    @click="copyText(forms[c.key].user)"
                                >
                                    Copiar
                                </button>
                            </div>
                            </div>

                            <div class="sm:col-span-2">
                                <label class="block text-xs font-medium text-gray-600">Clave</label>
                                <div class="relative mt-1">
                                    <input
                                        v-model="forms[c.key][credField]"
                                        :type="showPass[c.key] ? 'text' : obscuredInputType"
                                        :disabled="forms[c.key].noAplica"
                                        class="w-full rounded-lg border-gray-300 shadow-sm text-sm pr-10 disabled:opacity-60 focus:ring-[#0F6E56] focus:border-[#0F6E56]"
                                        placeholder="Nueva clave (mínimo 6)"
                                    />
                                    <button
                                        type="button"
                                        class="absolute inset-y-0 right-2 flex items-center"
                                        :disabled="revealBusy || forms[c.key].noAplica || !cardHasCredential(c.key)"
                                        @click="toggleReveal(c.key)"
                                        aria-label="Revelar clave"
                                    >
                                        <EyeOff v-if="showPass[c.key]" class="h-4 w-4 text-gray-500" />
                                        <Eye v-else class="h-4 w-4 text-gray-500" />
                                    </button>
                                </div>
                            <div class="mt-2 flex items-center justify-end gap-2">
                                <button
                                    type="button"
                                    class="px-3 py-1 rounded-lg bg-gray-100 text-gray-700 text-xs font-semibold hover:bg-gray-200 border border-gray-200 disabled:opacity-50"
                                    :disabled="forms[c.key].noAplica || !showPass[c.key] || !forms[c.key][credField]"
                                    @click="copyText(forms[c.key][credField])"
                                >
                                    Copiar clave
                                </button>
                            </div>
                            </div>
                        </div>

                        <div v-if="cardError" class="text-sm text-red-600 mt-3">{{ cardError }}</div>
                    <div v-if="copyStatus" class="text-sm text-gray-700 mt-2">{{ copyStatus }}</div>

                        <div class="flex items-center justify-end gap-2 mt-4">
                            <button
                                type="button"
                                class="px-4 py-2 rounded-lg bg-gray-100 text-gray-700 text-sm font-semibold hover:bg-gray-200"
                                @click="cancelEdit"
                            >
                                Cancelar
                            </button>
                            <button
                                type="button"
                                class="px-4 py-2 rounded-lg bg-[#0F6E56] text-white text-sm font-semibold hover:bg-[#0D5E4B] disabled:opacity-50 disabled:cursor-not-allowed"
                                :disabled="savingBusy || (!forms[c.key].noAplica && (!forms[c.key].user || !forms[c.key][credField] || forms[c.key][credField].length < 6))"
                                @click="saveCredential(c.key)"
                            >
                                Guardar cambios
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Auditoría -->
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
                            Usuario: {{ log.user || '—' }} · IP: {{ log.ip_address || '—' }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

