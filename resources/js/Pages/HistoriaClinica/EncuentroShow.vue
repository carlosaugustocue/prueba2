<script setup>
import { ref, computed, watch } from 'vue';
import { Link, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import DatePicker from '@/Components/DatePicker.vue';
import { ChevronLeft, Stethoscope, FileText, Activity, Plus, X } from 'lucide-vue-next';

const props = defineProps({
    encuentro: Object,
    historiaClinica: Object,
    antecedentes: Array,
    tiposAntecedente: Array,
});

const encuentroData = computed(() => props.encuentro?.data || props.encuentro || {});
const hc = computed(() => props.historiaClinica?.data || props.historiaClinica || {});
const affiliateId = computed(() => hc.value?.affiliate?.id ?? hc.value?.affiliate_id);
const antecedentesList = computed(() => props.antecedentes?.data || props.antecedentes || []);
const examen = computed(() => encuentroData.value.examen_fisico?.data || encuentroData.value.examen_fisico || null);

const showAntecedenteModal = ref(false);

const antecedenteForm = useForm({
    tipo: 'PATOLOGICO',
    descripcion: '',
    fecha_registro: new Date().toISOString().slice(0, 10),
    encuentro_id: null,
});

const examenForm = useForm({
    peso_kg: '',
    talla_cm: '',
    imc: '',
    presion_arterial_sistolica: '',
    presion_arterial_diastolica: '',
    frecuencia_cardiaca: '',
    frecuencia_respiratoria: '',
    temperatura: '',
    saturacion_oxigeno: '',
    resumen_general: '',
});

// Rellenar formulario cuando exista examen físico
watch(() => props.encuentro, (enc) => {
    const e = enc?.data?.examen_fisico ?? enc?.examen_fisico;
    if (e) {
        const d = e?.data ?? e;
        examenForm.peso_kg = d.peso_kg ?? '';
        examenForm.talla_cm = d.talla_cm ?? '';
        examenForm.imc = d.imc ?? '';
        examenForm.presion_arterial_sistolica = d.presion_arterial_sistolica ?? '';
        examenForm.presion_arterial_diastolica = d.presion_arterial_diastolica ?? '';
        examenForm.frecuencia_cardiaca = d.frecuencia_cardiaca ?? '';
        examenForm.frecuencia_respiratoria = d.frecuencia_respiratoria ?? '';
        examenForm.temperatura = d.temperatura ?? '';
        examenForm.saturacion_oxigeno = d.saturacion_oxigeno ?? '';
        examenForm.resumen_general = d.resumen_general ?? '';
    }
}, { immediate: true });

function openAntecedenteModal() {
    antecedenteForm.reset();
    antecedenteForm.tipo = 'PATOLOGICO';
    antecedenteForm.fecha_registro = new Date().toISOString().slice(0, 10);
    antecedenteForm.encuentro_id = encuentroData.value.id;
    showAntecedenteModal.value = true;
}

function submitAntecedente() {
    antecedenteForm.transform((data) => ({ ...data, encuentro_id: encuentroData.value.id })).post(`/affiliates/${affiliateId.value}/historia-clinica/antecedentes`, {
        preserveScroll: true,
        onSuccess: () => {
            showAntecedenteModal.value = false;
        },
    });
}

function submitExamen() {
    const payload = {
        peso_kg: examenForm.peso_kg || null,
        talla_cm: examenForm.talla_cm || null,
        imc: examenForm.imc || null,
        presion_arterial_sistolica: examenForm.presion_arterial_sistolica || null,
        presion_arterial_diastolica: examenForm.presion_arterial_diastolica || null,
        frecuencia_cardiaca: examenForm.frecuencia_cardiaca || null,
        frecuencia_respiratoria: examenForm.frecuencia_respiratoria || null,
        temperatura: examenForm.temperatura || null,
        saturacion_oxigeno: examenForm.saturacion_oxigeno || null,
        resumen_general: examenForm.resumen_general || null,
    };
    examenForm.transform(() => payload).post(`/affiliates/${affiliateId.value}/historia-clinica/encuentros/${encuentroData.value.id}/examen-fisico`, {
        preserveScroll: true,
    });
}

const tipoAntecedenteLabel = (value) => props.tiposAntecedente?.find(t => t.value === value)?.label || value;

// IMC calculado al cambiar peso/talla (el backend también lo calcula)
watch([() => examenForm.peso_kg, () => examenForm.talla_cm], ([p, t]) => {
    const peso = parseFloat(p);
    const talla = parseFloat(t);
    if (Number.isFinite(peso) && Number.isFinite(talla) && talla > 0) {
        const imc = peso / ((talla / 100) ** 2);
        examenForm.imc = Math.round(imc * 100) / 100;
    } else {
        examenForm.imc = '';
    }
});
</script>

<template>
    <AppLayout>
        <div class="max-w-4xl mx-auto space-y-6">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <Link :href="`/affiliates/${affiliateId}/historia-clinica`" class="inline-flex items-center gap-2 text-sm text-gray-500 hover:text-brand-600 transition-colors">
                        <ChevronLeft class="h-4 w-4" />
                        Volver a historia clínica
                    </Link>
                    <h1 class="text-2xl font-bold text-gray-900 mt-1">Encuentro clínico</h1>
                    <p class="text-gray-500 mt-0.5">
                        {{ encuentroData.tipo_atencion_label }} · {{ encuentroData.fecha_atencion_formatted }}
                    </p>
                </div>
            </div>

            <!-- Datos del encuentro -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200 bg-teal-50">
                    <h2 class="flex items-center gap-2 font-semibold text-gray-900">
                        <Stethoscope class="h-5 w-5 text-teal-600" />
                        Información del encuentro
                    </h2>
                </div>
                <div class="p-6 space-y-4">
                    <div>
                        <p class="text-xs text-gray-500 uppercase tracking-wide mb-1">Motivo de consulta</p>
                        <p class="text-gray-900">{{ encuentroData.motivo_consulta }}</p>
                    </div>
                    <div v-if="encuentroData.enfermedad_actual">
                        <p class="text-xs text-gray-500 uppercase tracking-wide mb-1">Enfermedad actual</p>
                        <p class="text-gray-900">{{ encuentroData.enfermedad_actual }}</p>
                    </div>
                    <div v-if="encuentroData.estado_mental">
                        <p class="text-xs text-gray-500 uppercase tracking-wide mb-1">Estado mental</p>
                        <p class="text-gray-900">{{ encuentroData.estado_mental }}</p>
                    </div>
                </div>
            </div>

            <!-- Antecedentes (de la historia clínica) -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200 bg-gradient-to-r from-amber-50 to-white flex items-center justify-between">
                    <h2 class="flex items-center gap-2 font-semibold text-gray-900">
                        <FileText class="h-5 w-5 text-amber-600" />
                        Antecedentes
                        <span v-if="antecedentesList.length" class="ml-2 px-2 py-0.5 bg-amber-100 text-amber-700 text-xs rounded-full">{{ antecedentesList.length }}</span>
                    </h2>
                    <button type="button" @click="openAntecedenteModal" class="inline-flex items-center gap-2 px-3 py-1.5 text-sm font-medium text-brand-600 hover:bg-brand-50 rounded-lg transition-colors">
                        <Plus class="h-4 w-4" />
                        Agregar antecedente
                    </button>
                </div>
                <div class="divide-y divide-gray-100">
                    <div v-for="a in antecedentesList" :key="a.id" class="px-6 py-4">
                        <span class="inline-flex px-2 py-0.5 rounded text-xs font-medium bg-amber-100 text-amber-700">{{ tipoAntecedenteLabel(a.tipo) }}</span>
                        <span class="text-sm text-gray-500 ml-2">{{ a.fecha_registro_formatted }}</span>
                        <p class="mt-2 text-gray-900">{{ a.descripcion }}</p>
                    </div>
                    <div v-if="!antecedentesList.length" class="px-6 py-8 text-center text-gray-500 text-sm">
                        No hay antecedentes registrados. Puede agregar desde este encuentro o desde la historia clínica.
                    </div>
                </div>
            </div>

            <!-- Examen físico (uno por encuentro) -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200 bg-gradient-to-r from-sky-50 to-white">
                    <h2 class="flex items-center gap-2 font-semibold text-gray-900">
                        <Activity class="h-5 w-5 text-sky-600" />
                        Examen físico
                    </h2>
                    <p class="text-sm text-gray-500 mt-0.5">Signos vitales y hallazgos de este encuentro</p>
                </div>
                <form @submit.prevent="submitExamen" class="p-6 space-y-4">
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Peso (kg)</label>
                            <input v-model="examenForm.peso_kg" type="number" step="0.1" min="0" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500" placeholder="—" />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Talla (cm)</label>
                            <input v-model="examenForm.talla_cm" type="number" step="0.1" min="0" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500" placeholder="—" />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">IMC</label>
                            <input v-model="examenForm.imc" type="number" step="0.01" min="0" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500 bg-gray-50" placeholder="Auto" readonly />
                        </div>
                    </div>
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">PA Sistólica</label>
                            <input v-model="examenForm.presion_arterial_sistolica" type="number" min="0" max="300" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500" placeholder="—" />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">PA Diastólica</label>
                            <input v-model="examenForm.presion_arterial_diastolica" type="number" min="0" max="200" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500" placeholder="—" />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">FC (lpm)</label>
                            <input v-model="examenForm.frecuencia_cardiaca" type="number" min="0" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500" placeholder="—" />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">FR (rpm)</label>
                            <input v-model="examenForm.frecuencia_respiratoria" type="number" min="0" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500" placeholder="—" />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Temp (°C)</label>
                            <input v-model="examenForm.temperatura" type="number" step="0.1" min="30" max="45" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500" placeholder="—" />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">SpO₂ (%)</label>
                            <input v-model="examenForm.saturacion_oxigeno" type="number" min="0" max="100" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500" placeholder="—" />
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Resumen general</label>
                        <textarea v-model="examenForm.resumen_general" rows="3" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500" placeholder="Hallazgos y observaciones"></textarea>
                    </div>
                    <div class="flex justify-end">
                        <button type="submit" class="px-4 py-2 bg-brand-600 text-white rounded-lg hover:bg-brand-700" :disabled="examenForm.processing">
                            {{ examenForm.processing ? 'Guardando...' : 'Guardar examen físico' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Modal agregar antecedente -->
        <Teleport to="body">
            <div v-if="showAntecedenteModal" class="fixed inset-0 z-50 overflow-y-auto" aria-modal="true">
                <div class="flex min-h-full items-center justify-center p-4">
                    <div class="fixed inset-0 bg-black/50" @click="showAntecedenteModal = false" />
                    <div class="relative bg-white rounded-xl shadow-xl max-w-lg w-full p-6">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-lg font-semibold text-gray-900">Agregar antecedente</h3>
                            <button type="button" @click="showAntecedenteModal = false" class="text-gray-400 hover:text-gray-600">
                                <X class="h-5 w-5" />
                            </button>
                        </div>
                        <form @submit.prevent="submitAntecedente" class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Tipo</label>
                                <select v-model="antecedenteForm.tipo" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500" required>
                                    <option v-for="t in tiposAntecedente" :key="t.value" :value="t.value">{{ t.label }}</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Fecha de registro</label>
                                <DatePicker v-model="antecedenteForm.fecha_registro" class="w-full" />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Descripción *</label>
                                <textarea v-model="antecedenteForm.descripcion" rows="4" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500" required placeholder="Describa el antecedente"></textarea>
                            </div>
                            <div class="flex justify-end gap-2 pt-4">
                                <button type="button" @click="showAntecedenteModal = false" class="px-4 py-2 text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200">Cancelar</button>
                                <button type="submit" class="px-4 py-2 bg-brand-600 text-white rounded-lg hover:bg-brand-700" :disabled="antecedenteForm.processing">
                                    {{ antecedenteForm.processing ? 'Guardando...' : 'Guardar' }}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </Teleport>
    </AppLayout>
</template>
