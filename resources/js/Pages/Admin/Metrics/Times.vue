<script setup>
import { computed } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { Filter } from 'lucide-vue-next';

const props = defineProps({
    filters: Object,
    summary: Object,
});

const apply = (key, value) => {
    router.get('/admin/metricas/tiempos', {
        ...props.filters,
        [key]: value || undefined,
    }, { preserveState: true, replace: true });
};
</script>

<template>
    <AppLayout>
        <div class="max-w-5xl mx-auto space-y-6">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">Resumen de Tiempos</h1>
                    <p class="text-sm text-gray-500">Vista global del rango seleccionado (solo administrador)</p>
                </div>
                <div class="flex gap-3">
                    <Link href="/admin/metricas/operadores" class="text-sm font-medium text-brand-600 hover:text-brand-700">
                        ← Operadores
                    </Link>
                    <Link href="/admin/anotaciones" class="text-sm font-medium text-brand-600 hover:text-brand-700">
                        Anotaciones →
                    </Link>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4">
                <div class="flex flex-wrap items-center gap-3">
                    <div class="flex items-center gap-2 text-gray-500">
                        <Filter class="h-4 w-4" />
                        <span class="text-sm font-medium">Filtros</span>
                    </div>
                    <input
                        type="date"
                        :value="filters.from"
                        @change="apply('from', $event.target.value)"
                        class="rounded-lg border-gray-300 text-sm focus:border-brand-500 focus:ring-brand-500"
                    />
                    <input
                        type="date"
                        :value="filters.to"
                        @change="apply('to', $event.target.value)"
                        class="rounded-lg border-gray-300 text-sm focus:border-brand-500 focus:ring-brand-500"
                    />
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-4 gap-4">
                <div class="bg-white rounded-xl border border-gray-200 p-5">
                    <p class="text-xs text-gray-500">Solicitudes cerradas</p>
                    <p class="mt-1 text-2xl font-bold text-gray-900">{{ summary.total_cerradas }}</p>
                </div>
                <div class="bg-white rounded-xl border border-gray-200 p-5">
                    <p class="text-xs text-gray-500">Promedio total (min)</p>
                    <p class="mt-1 text-2xl font-bold text-gray-900">{{ summary.avg_total_min ?? '-' }}</p>
                </div>
                <div class="bg-white rounded-xl border border-gray-200 p-5">
                    <p class="text-xs text-gray-500">Mínimo (min)</p>
                    <p class="mt-1 text-2xl font-bold text-gray-900">{{ summary.min_total_min ?? '-' }}</p>
                </div>
                <div class="bg-white rounded-xl border border-gray-200 p-5">
                    <p class="text-xs text-gray-500">Máximo (min)</p>
                    <p class="mt-1 text-2xl font-bold text-gray-900">{{ summary.max_total_min ?? '-' }}</p>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

