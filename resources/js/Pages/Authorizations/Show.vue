<script setup>
import { ref, computed } from 'vue';
import { Link, router, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { confirmDialog, toast } from '@/Utils/swal';
import { ChevronLeft, FileText, Upload, Download, History, User, Building2, Calendar, ClipboardList, ArrowRight, Unlink, Trash2 } from 'lucide-vue-next';

const props = defineProps({
    authorization: Object,
    statuses: Array,
    documentTypes: Array,
});

const auth = computed(() => props.authorization?.data || props.authorization || {});

const statusForm = useForm({
    status: auth.value.status || '',
    radicated_at: auth.value.radicated_at || '',
    radicado_number: auth.value.radicado_number || '',
    authorization_number: auth.value.authorization_number || '',
    authorized_ips_name: auth.value.authorized_ips_name || '',
    valid_until: auth.value.valid_until || '',
    denial_reason: auth.value.denial_reason || '',
    notes: '',
});

const docForm = useForm({
    file: null,
    type: 'order_medica',
});

const showStatusModal = ref(false);
const showUploadModal = ref(false);

const openStatusModal = () => {
    statusForm.status = auth.value.status || '';
    statusForm.radicated_at = auth.value.radicated_at || '';
    statusForm.radicado_number = auth.value.radicado_number || '';
    statusForm.authorization_number = auth.value.authorization_number || '';
    statusForm.authorized_ips_name = auth.value.authorized_ips_name || '';
    statusForm.valid_until = auth.value.valid_until || '';
    statusForm.denial_reason = auth.value.denial_reason || '';
    statusForm.notes = '';
    showStatusModal.value = true;
};

const submitStatus = () => {
    statusForm.put(`/authorizations/${auth.value.id}`, {
        preserveScroll: true,
        onSuccess: () => showStatusModal.value = false,
    });
};

const submitDocument = () => {
    if (!docForm.file) return;
    docForm.post(`/authorizations/${auth.value.id}/documents`, {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => {
            showUploadModal.value = false;
            docForm.reset();
        },
    });
};

const docTypeLabel = (value) => props.documentTypes?.find(d => d.value === value)?.label || value;

const canTransitionTo = (toStatus) => {
    const from = auth.value.status;
    const t = {
        pending_radication: ['radicated'],
        radicated: ['approved', 'denied'],
        approved: ['expired'],
        denied: ['in_appeal'],
        in_appeal: ['approved', 'denied'],
        expired: [],
    };
    return (t[from] || []).includes(toStatus);
};

const detachFromRequest = () => {
    confirmDialog({
        title: 'Desvincular de la solicitud',
        text: 'La autorización dejará de estar vinculada a esta solicitud y la solicitud volverá a "Pendiente de autorización". ¿Continuar?',
        confirmButtonText: 'Desvincular',
    }).then((ok) => {
        if (!ok) return;
        router.post(`/authorizations/${auth.value.id}/detach-request`, {}, {
            preserveScroll: true,
            onSuccess: () => toast({ title: 'Autorización desvinculada.' }),
            onError: () => toast({ title: 'No se pudo desvincular.', icon: 'error' }),
        });
    });
};

const deactivateAuthorization = () => {
    confirmDialog({
        title: 'Desactivar autorización',
        text: 'La autorización se desactivará y dejará de aparecer en los listados. Esta acción no elimina los datos de forma permanente. ¿Continuar?',
        icon: 'warning',
        confirmButtonText: 'Desactivar',
    }).then((ok) => {
        if (!ok) return;
        router.delete(`/authorizations/${auth.value.id}`, {
            onSuccess: () => {},
        });
    });
};
</script>

<template>
    <AppLayout>
        <div class="max-w-4xl mx-auto space-y-6">
            <Link href="/authorizations" class="inline-flex items-center text-sm font-medium text-gray-500 hover:text-gray-700">
                <ChevronLeft class="h-4 w-4 mr-1" />
                Volver a autorizaciones
            </Link>

            <div class="flex items-center justify-between">
                <h1 class="text-2xl font-bold text-gray-900">Autorización #{{ auth.id }}</h1>
                <span :class="[auth.status_badge_class, 'inline-flex items-center px-3 py-1 rounded-full text-sm font-medium']">
                    {{ auth.status_label }}
                </span>
            </div>

            <!-- Número de autorización EPS (muy visible cuando está aprobada) -->
            <div v-if="auth.authorization_number" class="rounded-xl border-2 border-green-200 bg-green-50 p-4 sm:p-5">
                <p class="text-xs font-semibold text-green-800 uppercase tracking-wide mb-1">Número de autorización (EPS)</p>
                <p class="text-xl sm:text-2xl font-bold font-mono text-green-900 tracking-tight">{{ auth.authorization_number }}</p>
                <p v-if="auth.valid_until_formatted" class="text-sm text-green-700 mt-1">Vigente hasta {{ auth.valid_until_formatted }}</p>
            </div>

            <!-- Número de radicado (visible cuando está radicada) -->
            <div v-else-if="auth.radicado_number" class="rounded-xl border-2 border-blue-200 bg-blue-50 p-4 sm:p-5">
                <p class="text-xs font-semibold text-blue-800 uppercase tracking-wide mb-1">Número de radicado (EPS)</p>
                <p class="text-xl sm:text-2xl font-bold font-mono text-blue-900 tracking-tight">{{ auth.radicado_number }}</p>
                <p v-if="auth.radicated_at_formatted" class="text-sm text-blue-700 mt-1">Radicada el {{ auth.radicated_at_formatted }}</p>
            </div>

            <!-- Solicitud de cita vinculada -->
            <div v-if="auth.appointment_request" class="rounded-xl border-2 border-brand-200 bg-brand-50 p-4 sm:p-5">
                <div class="flex flex-wrap items-center justify-between gap-4">
                    <div class="flex items-center gap-3">
                        <ClipboardList class="h-8 w-8 text-brand-600 flex-shrink-0" />
                        <div>
                            <p class="text-xs font-semibold text-brand-800 uppercase tracking-wide">Solicitud vinculada</p>
                            <p class="text-lg font-semibold text-brand-900 mt-0.5">
                                Solicitud #{{ auth.appointment_request.id }}
                                <span v-if="auth.appointment_request.specialty" class="text-brand-700 font-normal"> — {{ auth.appointment_request.specialty }}</span>
                            </p>
                            <span class="inline-flex mt-1 px-2 py-0.5 rounded text-xs font-medium bg-white/80 text-brand-800">
                                {{ auth.appointment_request.status_label }}
                            </span>
                        </div>
                    </div>
                    <div class="flex items-center gap-2 flex-wrap">
                        <Link
                            :href="`/appointment-requests/${auth.appointment_request.id}`"
                            class="inline-flex items-center gap-2 px-4 py-2 bg-white border border-brand-300 text-brand-700 rounded-lg hover:bg-brand-100 font-medium text-sm"
                        >
                            Ver solicitud
                            <ArrowRight class="h-4 w-4" />
                        </Link>
                        <Link
                            v-if="auth.status === 'approved' && !auth.appointment_request.has_appointment"
                            :href="`/appointment-requests/${auth.appointment_request.id}/create-appointment`"
                            class="inline-flex items-center gap-2 px-4 py-2 bg-brand-600 text-white rounded-lg hover:bg-brand-700 font-medium text-sm"
                        >
                            <Calendar class="h-4 w-4" />
                            Crear cita desde esta solicitud
                        </Link>
                        <button
                            v-if="!auth.appointment_request.has_appointment"
                            type="button"
                            @click="detachFromRequest"
                            class="inline-flex items-center gap-2 px-4 py-2 bg-amber-50 border border-amber-300 text-amber-800 rounded-lg hover:bg-amber-100 font-medium text-sm"
                        >
                            <Unlink class="h-4 w-4" />
                            Desvincular de la solicitud
                        </button>
                    </div>
                </div>
            </div>

            <!-- Datos principales -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                <div class="p-6 space-y-4">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <p class="text-xs font-medium text-gray-500 uppercase">Afiliado</p>
                            <Link v-if="auth.affiliate" :href="`/affiliates/${auth.affiliate.id}`" class="text-base font-medium text-brand-600 hover:underline">
                                {{ auth.affiliate.full_name }}
                            </Link>
                            <p v-if="auth.affiliate" class="text-sm text-gray-500">{{ auth.affiliate.document_number }}</p>
                        </div>
                        <div>
                            <p class="text-xs font-medium text-gray-500 uppercase">EPS</p>
                            <p class="text-base text-gray-900">{{ auth.eps?.name || '—' }}</p>
                        </div>
                    </div>
                    <div>
                        <p class="text-xs font-medium text-gray-500 uppercase">Tipo de servicio</p>
                        <p class="text-base text-gray-900">{{ auth.service_type }}</p>
                    </div>
                    <div v-if="auth.diagnosis_or_reason">
                        <p class="text-xs font-medium text-gray-500 uppercase">Diagnóstico o motivo</p>
                        <p class="text-base text-gray-900 whitespace-pre-wrap">{{ auth.diagnosis_or_reason }}</p>
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <p class="text-xs font-medium text-gray-500 uppercase">Radicada el</p>
                            <p class="text-base text-gray-900">{{ auth.radicated_at_formatted || '—' }}</p>
                        </div>
                        <div v-if="auth.radicado_number">
                            <p class="text-xs font-medium text-gray-500 uppercase">Número de radicado (EPS)</p>
                            <p class="text-base font-mono text-gray-900">{{ auth.radicado_number }}</p>
                        </div>
                        <div v-if="auth.authorization_number">
                            <p class="text-xs font-medium text-gray-500 uppercase">Número de autorización</p>
                            <p class="text-base font-mono text-gray-900">{{ auth.authorization_number }}</p>
                        </div>
                        <div v-if="auth.authorized_ips_name">
                            <p class="text-xs font-medium text-gray-500 uppercase">IPS autorizada</p>
                            <p class="text-base text-gray-900">{{ auth.authorized_ips_name }}</p>
                        </div>
                        <div v-if="auth.valid_until_formatted">
                            <p class="text-xs font-medium text-gray-500 uppercase">Vigencia hasta</p>
                            <p class="text-base text-gray-900">{{ auth.valid_until_formatted }}</p>
                        </div>
                    </div>
                    <div v-if="auth.denial_reason">
                        <p class="text-xs font-medium text-gray-500 uppercase">Motivo de negación</p>
                        <p class="text-base text-red-700 whitespace-pre-wrap">{{ auth.denial_reason }}</p>
                    </div>
                </div>
                <div class="px-6 py-4 bg-gray-50 border-t border-gray-200 space-y-3">
                    <p v-if="auth.status === 'pending_radication'" class="text-sm text-gray-600">
                        Cuando radique la autorización ante la EPS, haga clic en <strong>Cambiar estado</strong>, elija <strong>Radicada</strong> e ingrese la <strong>fecha de radicación</strong> y el <strong>número de radicado</strong> que entrega la EPS para seguimiento.
                    </p>
                    <button
                        type="button"
                        @click="openStatusModal"
                        class="inline-flex items-center px-4 py-2 font-medium rounded-lg bg-brand-500 text-white hover:bg-brand-600"
                    >
                        Cambiar estado
                    </button>
                </div>
            </div>

            <!-- Historial de estados -->
            <div v-if="auth.state_histories?.length" class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                <h2 class="px-6 py-4 border-b border-gray-200 font-semibold text-gray-900 flex items-center">
                    <History class="h-5 w-5 mr-2 text-gray-500" />
                    Historial de estados
                </h2>
                <ul class="divide-y divide-gray-200">
                    <li v-for="h in auth.state_histories" :key="h.id" class="px-6 py-3 text-sm">
                        <span class="text-gray-500">{{ h.created_at }}</span>
                        — {{ h.from_status || '—' }} → {{ h.to_status }}
                        <span v-if="h.notes" class="text-gray-600"> ({{ h.notes }})</span>
                    </li>
                </ul>
            </div>

            <!-- Documentos -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
                    <h2 class="font-semibold text-gray-900 flex items-center">
                        <FileText class="h-5 w-5 mr-2 text-gray-500" />
                        Documentos
                    </h2>
                    <button
                        type="button"
                        @click="showUploadModal = true"
                        class="inline-flex items-center px-3 py-1.5 text-sm font-medium rounded-lg bg-gray-100 text-gray-700 hover:bg-gray-200"
                    >
                        <Upload class="h-4 w-4 mr-1" />
                        Adjuntar
                    </button>
                </div>
                <ul class="divide-y divide-gray-200">
                    <li v-for="d in (auth.documents || [])" :key="d.id" class="px-6 py-3 flex items-center justify-between">
                        <span class="text-sm text-gray-900">{{ d.original_name }}</span>
                        <span class="text-xs text-gray-500">{{ docTypeLabel(d.type) }} · {{ d.created_at }}</span>
                        <a
                            :href="`/authorizations/${auth.id}/documents/${d.id}/download`"
                            class="text-sm text-brand-600 hover:underline inline-flex items-center"
                        >
                            <Download class="h-4 w-4 mr-1" />
                            Descargar
                        </a>
                    </li>
                    <li v-if="!auth.documents?.length" class="px-6 py-8 text-center text-gray-500 text-sm">
                        No hay documentos adjuntos.
                    </li>
                </ul>
            </div>

            <!-- Desactivar autorización (corregir errores de asignación) -->
            <div class="rounded-xl border border-red-200 bg-red-50/50 p-6">
                <h3 class="text-sm font-semibold text-red-900 mb-2">Acciones de corrección</h3>
                <p class="text-sm text-red-800 mb-4">
                    Si vinculó esta autorización a una solicitud por error, puede desvincularla arriba (botón «Desvincular de la solicitud»). Para ocultar esta autorización de los listados sin borrar datos, use desactivar.
                </p>
                <button
                    type="button"
                    @click="deactivateAuthorization"
                    class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-red-700 bg-white border border-red-300 rounded-lg hover:bg-red-50 transition-colors"
                >
                    <Trash2 class="h-4 w-4" />
                    Desactivar autorización
                </button>
            </div>
        </div>

        <!-- Modal cambiar estado -->
        <Teleport to="body">
            <div v-if="showStatusModal" class="fixed inset-0 z-50 overflow-y-auto" aria-modal="true">
                <div class="flex min-h-full items-center justify-center p-4">
                    <div class="fixed inset-0 bg-black/50" @click="showStatusModal = false" />
                    <div class="relative bg-white rounded-xl shadow-xl max-w-lg w-full p-6 space-y-4">
                        <h3 class="text-lg font-semibold text-gray-900">Cambiar estado</h3>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Nuevo estado</label>
                            <select v-model="statusForm.status" class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500">
                                <option v-for="s in statuses" :key="s.value" :value="s.value" :disabled="!canTransitionTo(s.value) && s.value !== auth.status">
                                    {{ s.label }} {{ !canTransitionTo(s.value) && s.value !== auth.status ? '(no permitido)' : '' }}
                                </option>
                            </select>
                        </div>
                        <div v-if="statusForm.status === 'radicated'" class="p-3 bg-blue-50 rounded-lg border border-blue-100 space-y-3">
                            <p class="text-sm text-blue-800 font-medium">Registre la radicación ante la EPS</p>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Fecha de radicación</label>
                                <input v-model="statusForm.radicated_at" type="date" class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm" />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Número de radicado (EPS) *</label>
                                <input v-model="statusForm.radicado_number" type="text" placeholder="Ej. RAD-2026-001234 — número que entrega la EPS para seguimiento" class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm" />
                            </div>
                        </div>
                        <div v-if="statusForm.status === 'approved'">
                            <label class="block text-sm font-medium text-gray-700">Número de autorización *</label>
                            <input v-model="statusForm.authorization_number" type="text" class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm" />
                            <label class="block text-sm font-medium text-gray-700 mt-2">IPS autorizada</label>
                            <input v-model="statusForm.authorized_ips_name" type="text" class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm" />
                            <label class="block text-sm font-medium text-gray-700 mt-2">Vigencia hasta *</label>
                            <input v-model="statusForm.valid_until" type="date" class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm" />
                        </div>
                        <div v-if="statusForm.status === 'denied'">
                            <label class="block text-sm font-medium text-gray-700">Motivo de negación *</label>
                            <textarea v-model="statusForm.denial_reason" rows="3" class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm" />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Notas (opcional)</label>
                            <input v-model="statusForm.notes" type="text" class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm" />
                        </div>
                        <div class="flex justify-end gap-2 pt-2">
                            <button type="button" @click="showStatusModal = false" class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200">Cancelar</button>
                            <button type="button" @click="submitStatus" :disabled="statusForm.processing" class="px-4 py-2 text-sm font-medium text-white bg-brand-500 rounded-lg hover:bg-brand-600 disabled:opacity-50">Guardar</button>
                        </div>
                    </div>
                </div>
            </div>
        </Teleport>

        <!-- Modal adjuntar documento -->
        <Teleport to="body">
            <div v-if="showUploadModal" class="fixed inset-0 z-50 overflow-y-auto" aria-modal="true">
                <div class="flex min-h-full items-center justify-center p-4">
                    <div class="fixed inset-0 bg-black/50" @click="showUploadModal = false" />
                    <div class="relative bg-white rounded-xl shadow-xl max-w-md w-full p-6 space-y-4">
                        <h3 class="text-lg font-semibold text-gray-900">Adjuntar documento</h3>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Tipo</label>
                            <select v-model="docForm.type" class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm">
                                <option v-for="d in documentTypes" :key="d.value" :value="d.value">{{ d.label }}</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Archivo (PDF, JPG, PNG — máx. 10 MB)</label>
                            <input type="file" accept=".pdf,.jpg,.jpeg,.png" @change="docForm.file = $event.target.files?.[0]" class="mt-1 block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:bg-brand-50 file:text-brand-700" />
                        </div>
                        <div class="flex justify-end gap-2 pt-2">
                            <button type="button" @click="showUploadModal = false" class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200">Cancelar</button>
                            <button type="button" @click="submitDocument" :disabled="!docForm.file" class="px-4 py-2 text-sm font-medium text-white bg-brand-500 rounded-lg hover:bg-brand-600 disabled:opacity-50">Subir</button>
                        </div>
                    </div>
                </div>
            </div>
        </Teleport>
    </AppLayout>
</template>
