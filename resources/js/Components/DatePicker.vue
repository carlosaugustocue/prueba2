<script setup>
import { ref, computed, watch, onMounted, onUnmounted, nextTick } from 'vue';
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
const popoverRef = ref(null);
const popoverOpen = ref(false);
const currentMonth = ref(new Date());
const popoverStyles = ref({
    top: '0px',
    left: '0px',
});

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

function parseYmdParts(value) {
    if (!value || typeof value !== 'string') return '';
    const trimmed = value.trim();
    if (!trimmed) return '';
    const match = trimmed.match(/^(\d{4})-(\d{2})-(\d{2})$/);
    if (!match) return '';
    const y = Number(match[1]);
    const m = Number(match[2]);
    const day = Number(match[3]);
    const probe = new Date(y, m - 1, day);
    if (
        Number.isNaN(probe.getTime()) ||
        probe.getFullYear() !== y ||
        probe.getMonth() !== (m - 1) ||
        probe.getDate() !== day
    ) {
        return '';
    }
    return {
        y,
        m,
        day,
    };
}

function toYmd(value) {
    if (!value || typeof value !== 'string') return '';
    const trimmed = value.trim();
    if (!trimmed) return '';

    const ymdParts = parseYmdParts(trimmed);
    if (ymdParts) {
        return `${ymdParts.y}-${String(ymdParts.m).padStart(2, '0')}-${String(ymdParts.day).padStart(2, '0')}`;
    }

    const d = new Date(trimmed);
    if (Number.isNaN(d.getTime())) return '';
    const y = d.getFullYear();
    const m = String(d.getMonth() + 1).padStart(2, '0');
    const day = String(d.getDate()).padStart(2, '0');
    return `${y}-${m}-${day}`;
}

function formatDisplay(ymd) {
    if (!ymd) return '';
    const parts = parseYmdParts(ymd);
    if (!parts) return '';
    return `${String(parts.day).padStart(2, '0')}/${String(parts.m).padStart(2, '0')}/${parts.y}`;
}

function ymdToLocalDate(value) {
    const parts = parseYmdParts(toYmd(value));
    if (!parts) return null;
    return new Date(parts.y, parts.m - 1, parts.day);
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
    const minDate = props.min ? ymdToLocalDate(props.min) : null;
    const maxDate = props.max ? ymdToLocalDate(props.max) : null;

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
        const d = ymdToLocalDate(props.modelValue);
        if (!Number.isNaN(d.getTime())) currentMonth.value = new Date(d.getFullYear(), d.getMonth(), 1);
    } else {
        currentMonth.value = new Date(new Date().getFullYear(), new Date().getMonth(), 1);
    }
    popoverOpen.value = true;
    nextTick(() => updatePopoverPosition());
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

function updatePopoverPosition() {
    if (!containerRef.value) return;

    const rect = containerRef.value.getBoundingClientRect();
    const viewportWidth = window.innerWidth;
    const viewportHeight = window.innerHeight;
    const popoverWidth = 280;
    const verticalGap = 8;
    const estimatedPopoverHeight = 320;

    let left = rect.left;
    if (left + popoverWidth > viewportWidth - 8) {
        left = viewportWidth - popoverWidth - 8;
    }
    if (left < 8) left = 8;

    const spaceBelow = viewportHeight - rect.bottom;
    const showAbove = spaceBelow < estimatedPopoverHeight && rect.top > estimatedPopoverHeight;
    const maxTop = Math.max(8, viewportHeight - estimatedPopoverHeight - 8);
    const top = showAbove
        ? Math.max(8, rect.top - estimatedPopoverHeight - verticalGap)
        : Math.max(8, Math.min(maxTop, rect.bottom + verticalGap));

    popoverStyles.value = {
        top: `${top}px`,
        left: `${left}px`,
    };
}

function onViewportChange() {
    if (!popoverOpen.value) return;
    updatePopoverPosition();
}

function onKeydown(e) {
    if (!popoverOpen.value) return;
    if (e.key === 'Escape') {
        e.preventDefault();
        closeCalendar();
    }
}

onMounted(() => {
    document.addEventListener('keydown', onKeydown);
    window.addEventListener('resize', onViewportChange);
    window.addEventListener('scroll', onViewportChange, true);
});

onUnmounted(() => {
    document.removeEventListener('keydown', onKeydown);
    window.removeEventListener('resize', onViewportChange);
    window.removeEventListener('scroll', onViewportChange, true);
});

watch(() => props.modelValue, (v) => {
    const ymd = toYmd(v || '');
    if (ymd && popoverOpen.value) {
        const d = ymdToLocalDate(ymd);
        if (!Number.isNaN(d.getTime())) currentMonth.value = new Date(d.getFullYear(), d.getMonth(), 1);
    }
});

watch(popoverOpen, (isOpen) => {
    if (!isOpen) return;
    nextTick(() => updatePopoverPosition());
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
                @click.stop="openCalendar"
                @mousedown.stop
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
        <Teleport to="body">
            <div
                v-show="popoverOpen"
                class="fixed inset-0 z-[1090]"
                aria-hidden="true"
                @mousedown="closeCalendar"
            />
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
                    ref="popoverRef"
                    :style="popoverStyles"
                    class="fixed z-[1100] w-[280px] rounded-xl border border-gray-200 bg-white shadow-lg ring-1 ring-black/5"
                    @click.stop
                    @mousedown.stop
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
        </Teleport>

        <p v-if="hint" class="mt-1 text-xs text-gray-500">{{ hint }}</p>
    </div>
</template>
