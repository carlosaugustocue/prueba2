<script setup>
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';

const props = defineProps({
    links: { type: Array, default: () => [] },
});

const visibleLinks = computed(() => props.links || []);
const shouldRender = computed(() => visibleLinks.value.length > 3);
</script>

<template>
    <div v-if="shouldRender" class="flex justify-center gap-1 flex-wrap">
        <Link
            v-for="link in visibleLinks"
            :key="link.label"
            :href="link.url || ''"
            v-html="link.label"
            :class="[
                'px-3 py-2 text-sm rounded-lg transition-colors',
                link.active ? 'bg-brand-500 text-white' : 'bg-white text-gray-700 hover:bg-gray-50 border border-gray-200',
                !link.url ? 'opacity-50 pointer-events-none' : ''
            ]"
        />
    </div>
</template>

