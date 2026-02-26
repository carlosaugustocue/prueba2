<script setup>
import { ref, computed } from 'vue';
import { Link, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import DatePicker from '@/Components/DatePicker.vue';
import { ChevronLeft, FileText, Stethoscope, Upload, Download, User, Calendar, Plus, X } from 'lucide-vue-next';

const props = defineProps({
    historiaClinica: Object,
    tiposAtencion: Array,
    tiposDocumento: Array,
});

const hc = computed(() => props.historiaClinica?.data || props.historiaClinica || {});
const affiliate = computed(() => hc.value.affiliate || {});
const affiliateId = computed(() => affiliate.value.id ?? hc.value.affiliate_id);
const encuentros = computed(() => hc.value.encuentros || []);
const documentos = computed(() => hc.value.documentos || []);

const showEncuentroModal = ref(false);
const showDocumentoModal = ref(false);

const encuentroForm = useForm({
    tipo_atencion: 'CONSULTA',
    fecha_atencion: new Date().toISOString().slice(0, 10),
    motivo_consulta: '',
    enfermedad_actual: '',
    estado_mental: '',
});

const documentoForm = useForm({
    file: null,
    tipo: 'LABORATORIO',
    fecha_documento: new Date().toISOString().slice(0, 10),
});

function openEncuentroModal() {
    encuentroForm.reset();
    encuentroForm.tipo_atencion = 'CONSULTA';
    encuentroForm.fecha_atencion = new Date().toISOString().slice(0, 10);
    encuentroForm.motivo_consulta = '';
    encuentroForm.enfermedad_actual = '';
    encuentroForm.estado_mental = '';
    showEncuentroModal.value = true;
}

function submitEncuentro() {
    encuentroForm.post(`/affiliates/${affiliateId.value}/historia-clinica/encuentros`, {
        preserveScroll: true,
        onSuccess: () => {
            showEncuentroModal.value = false;
        },
    });
}

function openDocumentoModal() {
    documentoForm.reset();
    documentoForm.tipo = 'LABORATORIO';
    documentoForm.fecha_documento = new Date().toISOString().slice(0, 10);
    documentoForm.file = null;
    showDocumentoModal.value = true;
}

function submitDocumento() {
    if (!documentoForm.file) return;
    documentoForm.post(`/affiliates/${affiliateId.value}/historia-clinica/documentos`, {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => {
            showDocumentoModal.value = false;
        },
    });
}

const tipoAtencionLabel = (value) => props.tiposAtencion?.find(t => t.value === value)?.label || value;
const tipoDocumentoLabel = (value) => props.tiposDocumento?.find(t => t.value === value)?.label || value;
</script>

<template>
    <AppLayout>
        <div class="max-w-5xl mx-auto space-y-6">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <Link :href="`/affiliates/${affiliateId}`" class="inline-flex items-center gap-2 text-sm text-gray-500 hover:text-brand-600 transition-colors">
                        <ChevronLeft class="h-4 w-4" />
                        Volver al afiliado
                    </Link>
                    <h1 class="text-2xl font-bold text-gray-900 mt-1">Historia clínica</h1>
                    <p class="text-gray-500 mt-0.5">
                        {{ affiliate.full_name || 'Afiliado' }}
                        <span class="text-gray-400">·</span>
                        <span class="font-mono text-sm">{{ hc.numero_historia }}</span>
                        <span :class="['ml-2 px-2 py-0.5 rounded text-xs font-medium', hc.estado === 'ACTIVA' ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-700']">
                            {{ hc.estado_label || hc.estado }}
                        </span>
                    </p>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <div class="lg:col-span-2 space-y-6">
                    <!-- Encuentros clínicos -->
                    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                        <div class="px-6 py-4 border-b border-gray-200 bg-gradient-to-r from-teal-50 to-white flex items-center justify-between">
                            <h2 class="flex items-center gap-2 font-semibold text-gray-900">
                                <Stethoscope class="h-5 w-5 text-teal-600" />
                                Encuentros clínicos
                                <span v-if="encuentros.length" class="ml-2 px-2 py-0.5 bg-teal-100 text-teal-700 text-xs rounded-full">{{ encuentros.length }}</span>
                            </h2>
                            <button type="button" @click="openEncuentroModal" class="inline-flex items-center gap-2 px-3 py-1.5 text-sm font-medium text-brand-600 hover:text-brand-700 hover:bg-brand-50 rounded-lg transition-colors">
                                <Plus class="h-4 w-4" />
                                Nuevo encuentro
                            </button>
                        </div>
                        <div class="divide-y divide-gray-100">
                            <Link
                                v-for="e in encuentros"
                                :key="e.id"
                                :href="`/affiliates/${affiliateId}/historia-clinica/encuentros/${e.id}`"
                                class="flex items-start justify-between gap-3 px-6 py-4 hover:bg-gray-50 transition-colors group"
                            >
                                <div>
                                    <span class="inline-flex px-2 py-0.5 rounded text-xs font-medium bg-teal-100 text-teal-700">{{ tipoAtencionLabel(e.tipo_atencion) }}</span>
                                    <span class="text-sm text-gray-500 ml-2">{{ e.fecha_atencion_formatted }}</span>
                                    <p class="mt-2 font-medium text-gray-900 group-hover:text-brand-700">{{ e.motivo_consulta }}</p>
                                    <p v-if="e.enfermedad_actual" class="mt-1 text-sm text-gray-600">{{ e.enfermedad_actual }}</p>
                                </div>
                                <span class="text-sm text-brand-600 font-medium">Ver detalle</span>
                            </Link>
                            <div v-if="!encuentros.length" class="px-6 py-12 text-center">
                                <Stethoscope class="h-12 w-12 mx-auto text-gray-300 mb-3" />
                                <p class="text-gray-500">No hay encuentros registrados</p>
                                <button type="button" @click="openEncuentroModal" class="mt-3 inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-brand-600 hover:bg-brand-50 rounded-lg transition-colors">
                                    <Plus class="h-4 w-4" />
                                    Registrar primer encuentro
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Documentos clínicos -->
                    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                        <div class="px-6 py-4 border-b border-gray-200 bg-gradient-to-r from-slate-50 to-white flex items-center justify-between">
                            <h2 class="flex items-center gap-2 font-semibold text-gray-900">
                                <FileText class="h-5 w-5 text-slate-600" />
                                Documentos
                                <span v-if="documentos.length" class="ml-2 px-2 py-0.5 bg-slate-100 text-slate-700 text-xs rounded-full">{{ documentos.length }}</span>
                            </h2>
                            <button type="button" @click="openDocumentoModal" class="inline-flex items-center gap-2 px-3 py-1.5 text-sm font-medium text-brand-600 hover:text-brand-700 hover:bg-brand-50 rounded-lg transition-colors">
                                <Upload class="h-4 w-4" />
                                Subir documento
                            </button>
                        </div>
                        <div class="divide-y divide-gray-100">
                            <div v-for="d in documentos" :key="d.id" class="px-6 py-4 flex items-center justify-between">
                                <div class="flex items-center gap-3">
                                    <FileText class="h-10 w-10 text-gray-300 flex-shrink-0" />
                                    <div>
                                        <p class="font-medium text-gray-900">{{ d.nombre_archivo }}</p>
                                        <p class="text-sm text-gray-500">{{ tipoDocumentoLabel(d.tipo) }} <span v-if="d.fecha_documento_formatted">· {{ d.fecha_documento_formatted }}</span></p>
                                    </div>
                                </div>
                                <a :href="`/affiliates/${affiliateId}/historia-clinica/documentos/${d.id}/download`" class="inline-flex items-center gap-1 px-3 py-1.5 text-sm font-medium text-brand-600 hover:bg-brand-50 rounded-lg transition-colors" target="_blank" rel="noopener">
                                    <Download class="h-4 w-4" />
                                    Descargar
                                </a>
                            </div>
                            <div v-if="!documentos.length" class="px-6 py-12 text-center">
                                <FileText class="h-12 w-12 mx-auto text-gray-300 mb-3" />
                                <p class="text-gray-500">No hay documentos adjuntos</p>
                                <button type="button" @click="openDocumentoModal" class="mt-3 inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-brand-600 hover:bg-brand-50 rounded-lg transition-colors">
                                    <Upload class="h-4 w-4" />
                                    Subir primer documento
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="space-y-6">
                    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                        <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
                            <h3 class="flex items-center gap-2 font-semibold text-gray-900">
                                <User class="h-5 w-5 text-brand-600" />
                                Datos de la historia
                            </h3>
                        </div>
                        <div class="p-6 space-y-4">
                            <div>
                                <p class="text-xs text-gray-500 uppercase tracking-wide mb-1">N.º historia</p>
                                <p class="font-mono font-medium text-gray-900">{{ hc.numero_historia }}</p>
                            </div>
                            <div>
                                <p class="text-xs text-gray-500 uppercase tracking-wide mb-1">Fecha apertura</p>
                                <p class="font-medium text-gray-900">{{ hc.fecha_apertura_formatted || '-' }}</p>
                            </div>
                            <div>
                                <p class="text-xs text-gray-500 uppercase tracking-wide mb-1">Estado</p>
                                <span :class="['inline-flex px-2.5 py-0.5 rounded text-xs font-medium', hc.estado === 'ACTIVA' ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-700']">
                                    {{ hc.estado_label || hc.estado }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal nuevo encuentro -->
        <Teleport to="body">
            <div v-if="showEncuentroModal" class="fixed inset-0 z-50 overflow-y-auto" aria-modal="true">
                <div class="flex min-h-full items-center justify-center p-4">
                    <div class="fixed inset-0 bg-black/50" @click="showEncuentroModal = false" />
                    <div class="relative bg-white rounded-xl shadow-xl max-w-lg w-full p-6">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-lg font-semibold text-gray-900">Nuevo encuentro clínico</h3>
                            <button type="button" @click="showEncuentroModal = false" class="text-gray-400 hover:text-gray-600">
                                <X class="h-5 w-5" />
                            </button>
                        </div>
                        <form @submit.prevent="submitEncuentro" class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Tipo de atención</label>
                                <select v-model="encuentroForm.tipo_atencion" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500" required>
                                    <option v-for="t in tiposAtencion" :key="t.value" :value="t.value">{{ t.label }}</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Fecha de atención</label>
                                <DatePicker v-model="encuentroForm.fecha_atencion" class="w-full" />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Motivo de consulta *</label>
                                <textarea v-model="encuentroForm.motivo_consulta" rows="3" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500" required placeholder="Describa el motivo de consulta"></textarea>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Enfermedad actual</label>
                                <textarea v-model="encuentroForm.enfermedad_actual" rows="2" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500" placeholder="Opcional"></textarea>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Estado mental</label>
                                <input v-model="encuentroForm.estado_mental" type="text" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500" placeholder="Opcional" />
                            </div>
                            <div class="flex justify-end gap-2 pt-4">
                                <button type="button" @click="showEncuentroModal = false" class="px-4 py-2 text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200">Cancelar</button>
                                <button type="submit" class="px-4 py-2 bg-brand-600 text-white rounded-lg hover:bg-brand-700" :disabled="encuentroForm.processing">
                                    {{ encuentroForm.processing ? 'Guardando...' : 'Guardar' }}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </Teleport>

        <!-- Modal subir documento -->
        <Teleport to="body">
            <div v-if="showDocumentoModal" class="fixed inset-0 z-50 overflow-y-auto" aria-modal="true">
                <div class="flex min-h-full items-center justify-center p-4">
                    <div class="fixed inset-0 bg-black/50" @click="showDocumentoModal = false" />
                    <div class="relative bg-white rounded-xl shadow-xl max-w-lg w-full p-6">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-lg font-semibold text-gray-900">Subir documento clínico</h3>
                            <button type="button" @click="showDocumentoModal = false" class="text-gray-400 hover:text-gray-600">
                                <X class="h-5 w-5" />
                            </button>
                        </div>
                        <form @submit.prevent="submitDocumento" class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Tipo</label>
                                <select v-model="documentoForm.tipo" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500" required>
                                    <option v-for="t in tiposDocumento" :key="t.value" :value="t.value">{{ t.label }}</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Fecha del documento</label>
                                <DatePicker v-model="documentoForm.fecha_documento" class="w-full" />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Archivo (PDF, JPG, PNG — máx. 10 MB)</label>
                                <input type="file" accept=".pdf,.jpg,.jpeg,.png" @change="e => documentoForm.file = e.target?.files?.[0]" class="w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:bg-brand-50 file:text-brand-700" required />
                                <p v-if="documentoForm.errors?.file" class="mt-1 text-sm text-red-600">{{ documentoForm.errors.file }}</p>
                            </div>
                            <div class="flex justify-end gap-2 pt-4">
                                <button type="button" @click="showDocumentoModal = false" class="px-4 py-2 text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200">Cancelar</button>
                                <button type="submit" class="px-4 py-2 bg-brand-600 text-white rounded-lg hover:bg-brand-700" :disabled="documentoForm.processing || !documentoForm.file">
                                    {{ documentoForm.processing ? 'Subiendo...' : 'Subir' }}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </Teleport>
    </AppLayout>
</template>
