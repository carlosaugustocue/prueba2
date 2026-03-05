<script setup>
import { computed, onMounted, onUnmounted, ref, watch } from 'vue';
import { Search, X } from 'lucide-vue-next';

const props = defineProps({
    modelValue: { type: [String, Number, null], default: null },
    options: { type: Array, default: () => [] }, // [{ id|value, label, description? }]
    placeholder: { type: String, default: 'Buscar...' },
    noResultsText: { type: String, default: 'No se encontraron resultados.' },
    valueKey: { type: String, default: 'id' },
    labelKey: { type: String, default: 'label' },
    disabled: { type: Boolean, default: false },
    clearable: { type: Boolean, default: true },
});

const emit = defineEmits(['update:modelValue']);

const containerRef = ref(null);
const searchTerm = ref('');
const isOpen = ref(false);

const normalizedOptions = computed(() =>
    (props.options || []).map((opt) => ({
        value: opt?.[props.valueKey],
        label: String(opt?.[props.labelKey] ?? ''),
        description: opt?.description ? String(opt.description) : '',
    }))
);

const selectedOption = computed(() =>
    normalizedOptions.value.find((opt) => String(opt.value) === String(props.modelValue))
);

const filteredOptions = computed(() => {
    const term = searchTerm.value.trim().toLowerCase();
    if (!term) return normalizedOptions.value.slice(0, 30);

    return normalizedOptions.value
        .filter((opt) => `${opt.label} ${opt.description}`.toLowerCase().includes(term))
        .slice(0, 30);
});

watch(
    () => props.modelValue,
    () => {
        const selected = selectedOption.value;
        if (selected && searchTerm.value !== selected.label) {
            searchTerm.value = selected.label;
            return;
        }
        if (!selected && searchTerm.value && !isOpen.value) {
            searchTerm.value = '';
        }
    },
    { immediate: true }
);

function onInput() {
    if (props.disabled) return;
    isOpen.value = true;
    const selected = selectedOption.value;
    if (selected && searchTerm.value !== selected.label) {
        emit('update:modelValue', '');
    }
}

function onFocus() {
    if (props.disabled) return;
    isOpen.value = true;
}

function selectOption(option) {
    emit('update:modelValue', option.value);
    searchTerm.value = option.label;
    isOpen.value = false;
}

function clearSelection() {
    if (props.disabled) return;
    emit('update:modelValue', '');
    searchTerm.value = '';
    isOpen.value = false;
}

function onDocumentClick(e) {
    if (!isOpen.value) return;
    if (containerRef.value?.contains(e.target)) return;
    isOpen.value = false;

    const selected = selectedOption.value;
    searchTerm.value = selected?.label || '';
}

onMounted(() => document.addEventListener('click', onDocumentClick));
onUnmounted(() => document.removeEventListener('click', onDocumentClick));
</script>

<template>
    <div ref="containerRef" class="relative">
        <Search class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-gray-400 pointer-events-none" />
        <input
            v-model="searchTerm"
            type="text"
            :placeholder="placeholder"
            :disabled="disabled"
            class="block w-full pl-10 pr-10 rounded-lg border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500 disabled:bg-gray-100 disabled:text-gray-500"
            @focus="onFocus"
            @input="onInput"
        />
        <button
            v-if="clearable && searchTerm && !disabled"
            type="button"
            class="absolute right-2 top-1/2 -translate-y-1/2 p-1 rounded text-gray-400 hover:text-gray-600 hover:bg-gray-100"
            @click="clearSelection"
            aria-label="Limpiar selección"
        >
            <X class="h-4 w-4" />
        </button>

        <div
            v-if="isOpen"
            class="absolute z-40 mt-1 w-full rounded-lg border border-gray-200 bg-white shadow-lg max-h-64 overflow-auto"
        >
            <button
                v-for="opt in filteredOptions"
                :key="opt.value"
                type="button"
                class="w-full text-left px-3 py-2 hover:bg-gray-50 border-b border-gray-100 last:border-b-0"
                @click="selectOption(opt)"
            >
                <p class="text-sm font-medium text-gray-900">{{ opt.label }}</p>
                <p v-if="opt.description" class="text-xs text-gray-500">{{ opt.description }}</p>
            </button>
            <p v-if="!filteredOptions.length" class="px-3 py-2 text-sm text-gray-500">
                {{ noResultsText }}
            </p>
        </div>
    </div>
</template>

