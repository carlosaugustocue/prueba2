<script setup>
import { ref, watch } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { ChevronLeft, Pencil } from 'lucide-vue-next';

const props = defineProps({
    employer: Object,
    dueDate: String,
    period: Object,
    paymentBusinessDay: Number,
});

const e = props.employer?.data || props.employer;
const year = ref(props.period?.year || new Date().getFullYear());
const month = ref(props.period?.month || new Date().getMonth() + 1);

let t;
watch([year, month], () => {
    clearTimeout(t);
    t = setTimeout(() => {
        router.get(`/pila/employers/${e.id}`, { year: year.value, month: month.value }, { preserveState: true, replace: true, preserveScroll: true });
    }, 300);
});
</script>

<template>
    <AppLayout>
        <div class="max-w-3xl mx-auto space-y-6">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <Link href="/pila/employers" class="inline-flex items-center gap-2 text-sm text-gray-500 hover:text-brand-700">
                        <ChevronLeft class="h-4 w-4" />
                        Volver a empleadores
                    </Link>
                    <h1 class="mt-2 text-2xl font-bold text-gray-900">{{ e.name }}</h1>
                    <p class="mt-1 text-sm text-gray-500">
                        {{ e.document_type }} {{ e.document_number }}<span v-if="e.check_digit">-{{ e.check_digit }}</span>
                    </p>
                </div>
                <Link :href="`/pila/employers/${e.id}/edit`" class="inline-flex items-center justify-center px-4 py-2 font-medium rounded-lg bg-brand-500 text-white hover:bg-brand-600">
                    <Pencil class="h-4 w-4 mr-2" />
                    Editar
                </Link>
            </div>

            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 space-y-4">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm">
                    <div>
                        <p class="text-gray-500">Correo</p>
                        <p class="text-gray-900">{{ e.email || '—' }}</p>
                    </div>
                    <div>
                        <p class="text-gray-500">Teléfono</p>
                        <p class="text-gray-900">{{ e.phone || '—' }}</p>
                    </div>
                    <div>
                        <p class="text-gray-500">Ciudad / Departamento</p>
                        <p class="text-gray-900">{{ [e.city, e.department].filter(Boolean).join(' / ') || '—' }}</p>
                    </div>
                    <div>
                        <p class="text-gray-500">Día hábil (2–16)</p>
                        <p class="text-gray-900">{{ paymentBusinessDay ?? '—' }}</p>
                    </div>
                </div>

                <div class="pt-4 border-t border-gray-200">
                    <h2 class="text-base font-semibold text-gray-900">Fecha límite de pago (calculada)</h2>
                    <p class="mt-1 text-sm text-gray-500">Vence en el mes siguiente al período liquidado.</p>

                    <div class="mt-3 grid grid-cols-1 sm:grid-cols-3 gap-3 items-end">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Año (período)</label>
                            <input v-model.number="year" type="number" min="2020" max="2100" class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500" />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Mes (período)</label>
                            <input v-model.number="month" type="number" min="1" max="12" class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500" />
                        </div>
                        <div class="sm:pb-1">
                            <p class="text-sm text-gray-500">Vencimiento</p>
                            <p class="text-lg font-semibold text-gray-900">{{ dueDate }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

