<script setup>
import { ref, computed, watch, onMounted, onUnmounted } from 'vue';
import { Calendar, ChevronLeft, ChevronRight } from 'lucide-vue-next';

const props = defineProps({
    modelValue: { type: String, default: '' },
    label: { type: String, default: '' },
    required: { type: Boolean, default: false },
    min: { type: String, default: null },
    max: { type: String, default: null },
    hint: { type: String, default: '' },
    /** Año mínimo en el selector (p. ej. 1900 para fechas de nacimiento). */
    minYear: { type: Number, default: 1950 },
    /** Año máximo en el selector. */
    maxYear: { type: Number, default: null },
});

const emit = defineEmits(['update:modelValue']);

const containerRef = ref(null);
const popoverOpen = ref(false);
const currentMonth = ref(new Date());

const monthNames = ['Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'];
const dayNames = ['Dom', 'Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb'];

const yearEnd = computed(() => props.maxYear ?? new Date().getFullYear() + 1);
const yearOptions = computed(() => {
    const list = [];
    for (let y = yearEnd.value; y >= props.minYear; y--) list.push(y);
    return list;
});
const monthOptions = computed(() =>
    monthNames.map((name, i) => ({ value: i, label: name }))
);

function toYmd(value) {
    if (!value || typeof value !== 'string') return '';
    const trimmed = value.trim();
    if (!trimmed) return '';
    const d = new Date(trimmed);
    if (Number.isNaN(d.getTime())) return '';
    const y = d.getFullYear();
    const m = String(d.getMonth() + 1).padStart(2, '0');
    const day = String(d.getDate()).padStart(2, '0');
    return `${y}-${m}-${day}`;
}

function formatDisplay(ymd) {
    if (!ymd) return '';
    const d = new Date(ymd);
    if (Number.isNaN(d.getTime())) return '';
    const day = String(d.getDate()).padStart(2, '0');
    const m = String(d.getMonth() + 1).padStart(2, '0');
    const y = d.getFullYear();
    return `${day}/${m}/${y}`;
}

const displayText = computed(() => formatDisplay(props.modelValue ? toYmd(props.modelValue) : ''));

const calendarDays = computed(() => {
    const year = currentMonth.value.getFullYear();
    const month = currentMonth.value.getMonth();
    const firstDay = new Date(year, month, 1);
    const lastDay = new Date(year, month + 1, 0);
    const days = [];
    const today = new Date();
    const todayDate = new Date(today.getFullYear(), today.getMonth(), today.getDate());
    const minDate = props.min ? new Date(props.min) : null;
    const maxDate = props.max ? new Date(props.max) : null;

    const startDay = firstDay.getDay();
    for (let i = startDay - 1; i >= 0; i--) {
        const d = new Date(year, month, -i);
        days.push({ date: d, isCurrentMonth: false, isToday: false, isDisabled: false });
    }
    for (let i = 1; i <= lastDay.getDate(); i++) {
        const d = new Date(year, month, i);
        const isToday = d.toDateString() === today.toDateString();
        let isDisabled = false;
        if (minDate && d < minDate) isDisabled = true;
        if (maxDate && d > maxDate) isDisabled = true;
        days.push({ date: d, isCurrentMonth: true, isToday, isDisabled });
    }
    return days;
});

function openCalendar() {
    if (props.modelValue && toYmd(props.modelValue)) {
        const d = new Date(toYmd(props.modelValue));
        if (!Number.isNaN(d.getTime())) currentMonth.value = new Date(d.getFullYear(), d.getMonth(), 1);
    } else {
        currentMonth.value = new Date(new Date().getFullYear(), new Date().getMonth(), 1);
    }
    popoverOpen.value = true;
}

function closeCalendar() {
    popoverOpen.value = false;
}

const currentYear = computed(() => currentMonth.value.getFullYear());
const currentMonthIndex = computed(() => currentMonth.value.getMonth());

function setYear(year) {
    const y = Number(year);
    if (Number.isNaN(y)) return;
    currentMonth.value = new Date(y, currentMonth.value.getMonth(), 1);
}

function setMonth(monthIndex) {
    const m = Number(monthIndex);
    if (Number.isNaN(m) || m < 0 || m > 11) return;
    currentMonth.value = new Date(currentMonth.value.getFullYear(), m, 1);
}

function prevMonth() {
    currentMonth.value = new Date(currentMonth.value.getFullYear(), currentMonth.value.getMonth() - 1, 1);
}

function nextMonth() {
    currentMonth.value = new Date(currentMonth.value.getFullYear(), currentMonth.value.getMonth() + 1, 1);
}

function selectDay(day) {
    if (!day.isCurrentMonth || day.isDisabled) return;
    const d = day.date;
    const ymd = `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`;
    emit('update:modelValue', ymd);
    closeCalendar();
}

function isSelected(day) {
    if (!props.modelValue || !day.isCurrentMonth) return false;
    const ymd = toYmd(props.modelValue);
    const d = day.date;
    const dayYmd = `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`;
    return ymd === dayYmd;
}

function onDocumentClick(e) {
    if (!popoverOpen.value) return;
    if (containerRef.value && containerRef.value.contains(e.target)) return;
    closeCalendar();
}

onMounted(() => document.addEventListener('click', onDocumentClick));
onUnmounted(() => document.removeEventListener('click', onDocumentClick));

watch(() => props.modelValue, (v) => {
    const ymd = toYmd(v || '');
    if (ymd && popoverOpen.value) {
        const d = new Date(ymd);
        if (!Number.isNaN(d.getTime())) currentMonth.value = new Date(d.getFullYear(), d.getMonth(), 1);
    }
});
</script>

<template>
    <div ref="containerRef" class="w-full relative">
        <label v-if="label" class="block text-sm font-medium text-gray-700">
            {{ label }}
            <span v-if="required" class="text-red-500">*</span>
        </label>
        <div class="mt-1 relative flex items-center">
            <input
                type="text"
                readonly
                :value="displayText"
                placeholder="Seleccione la fecha"
                class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500 bg-white pr-10 cursor-pointer"
                @focus="openCalendar"
                @click="openCalendar"
            />
            <button
                type="button"
                tabindex="-1"
                class="absolute right-2 p-1.5 rounded-lg text-gray-400 hover:text-brand-600 hover:bg-brand-50 transition-colors pointer-events-none"
                aria-hidden="true"
            >
                <Calendar class="h-5 w-5" />
            </button>
        </div>

        <!-- Calendario desplegable - estilo Serviconli -->
        <Transition
            enter-active-class="transition ease-out duration-150"
            enter-from-class="opacity-0 -translate-y-1"
            enter-to-class="opacity-100 translate-y-0"
            leave-active-class="transition ease-in duration-100"
            leave-from-class="opacity-100 translate-y-0"
            leave-to-class="opacity-0 -translate-y-1"
        >
            <div
                v-show="popoverOpen"
                class="absolute z-50 mt-2 w-[280px] rounded-xl border border-gray-200 bg-white shadow-lg ring-1 ring-black/5 left-0"
            >
                <div class="p-3 bg-gradient-to-b from-brand-50 to-white rounded-t-xl border-b border-brand-100">
                    <!-- Selectores de mes y año para saltar rápido a cualquier fecha -->
                    <div class="flex flex-wrap items-center gap-2 mb-2">
                        <select
                            :value="currentMonthIndex"
                            class="flex-1 min-w-0 rounded-lg border-gray-300 text-sm font-medium text-gray-900 bg-white shadow-sm focus:border-brand-500 focus:ring-brand-500"
                            @change="setMonth(($event.target).value)"
                        >
                            <option v-for="opt in monthOptions" :key="opt.value" :value="opt.value">
                                {{ opt.label }}
                            </option>
                        </select>
                        <select
                            :value="currentYear"
                            class="flex-1 min-w-0 rounded-lg border-gray-300 text-sm font-medium text-gray-900 bg-white shadow-sm focus:border-brand-500 focus:ring-brand-500"
                            @change="setYear(($event.target).value)"
                        >
                            <option v-for="y in yearOptions" :key="y" :value="y">{{ y }}</option>
                        </select>
                        <div class="flex items-center gap-0.5">
                            <button
                                type="button"
                                class="p-1.5 rounded-lg text-gray-600 hover:bg-brand-100 hover:text-brand-700 transition-colors"
                                @click.stop="prevMonth"
                                title="Mes anterior"
                            >
                                <ChevronLeft class="h-4 w-4" />
                            </button>
                            <button
                                type="button"
                                class="p-1.5 rounded-lg text-gray-600 hover:bg-brand-100 hover:text-brand-700 transition-colors"
                                @click.stop="nextMonth"
                                title="Mes siguiente"
                            >
                                <ChevronRight class="h-4 w-4" />
                            </button>
                        </div>
                    </div>
                    <div class="grid grid-cols-7 gap-0.5 mb-1">
                        <div
                            v-for="day in dayNames"
                            :key="day"
                            class="text-center text-[11px] font-semibold text-brand-700 py-0.5"
                        >
                            {{ day }}
                        </div>
                    </div>
                    <div class="grid grid-cols-7 gap-0.5">
                        <button
                            v-for="(day, index) in calendarDays"
                            :key="index"
                            type="button"
                            @click.stop="selectDay(day)"
                            :disabled="day.isDisabled"
                            :class="[
                                'aspect-square flex items-center justify-center rounded-md text-xs font-medium transition-all',
                                !day.isCurrentMonth ? 'text-gray-300' : '',
                                day.isCurrentMonth && !day.isDisabled && !isSelected(day) && !day.isToday
                                    ? 'text-gray-700 hover:bg-brand-100 hover:text-brand-700' : '',
                                day.isToday && !isSelected(day)
                                    ? 'bg-brand-100 text-brand-700 font-bold' : '',
                                day.isDisabled && day.isCurrentMonth ? 'text-gray-300 cursor-not-allowed' : '',
                                isSelected(day) ? 'bg-brand-500 text-white shadow-md' : ''
                            ]"
                        >
                            {{ day.date.getDate() }}
                        </button>
                    </div>
                </div>
            </div>
        </Transition>

        <p v-if="hint" class="mt-1 text-xs text-gray-500">{{ hint }}</p>
    </div>
</template>
