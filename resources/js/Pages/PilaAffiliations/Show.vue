<script setup>
import { computed } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { ChevronLeft, Pencil, Trash2 } from 'lucide-vue-next';

const props = defineProps({
    affiliation: Object,
});

const a = computed(() => props.affiliation?.data || props.affiliation);

const destroy = () => {
    if (!confirm('¿Eliminar afiliación?')) return;
    router.delete(`/pila/affiliations/${a.value.id}`, { preserveScroll: true });
};
</script>

<template>
    <AppLayout>
        <div class="max-w-4xl mx-auto space-y-6">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <Link href="/pila/affiliations" class="inline-flex items-center gap-2 text-sm text-gray-500 hover:text-brand-700">
                        <ChevronLeft class="h-4 w-4" />
                        Volver a afiliaciones
                    </Link>
                    <h1 class="mt-2 text-2xl font-bold text-gray-900">
                        {{ a.affiliate?.full_name || 'Afiliación' }}
                    </h1>
                    <p class="mt-1 text-sm text-gray-500">
                        {{ a.affiliate?.document_type_abbreviation }} {{ a.affiliate?.document_number }}
                    </p>
                </div>
                <div class="flex items-center gap-2">
                    <Link :href="`/pila/affiliations/${a.id}/edit`" class="inline-flex items-center justify-center px-4 py-2 font-medium rounded-lg bg-brand-500 text-white hover:bg-brand-600">
                        <Pencil class="h-4 w-4 mr-2" />
                        Editar
                    </Link>
                    <button type="button" @click="destroy" class="inline-flex items-center justify-center px-4 py-2 font-medium rounded-lg bg-gray-100 text-gray-700 hover:bg-gray-200">
                        <Trash2 class="h-4 w-4 mr-2" />
                        Eliminar
                    </button>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                <dl class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm">
                    <div>
                        <dt class="text-gray-500">Empleador</dt>
                        <dd class="text-gray-900">{{ a.employer?.name || '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500">Tipo cotizante</dt>
                        <dd class="text-gray-900">{{ a.cotizante_type?.code || '—' }} {{ a.cotizante_type?.name ? `— ${a.cotizante_type.name}` : '' }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500">Operador PILA</dt>
                        <dd class="text-gray-900">{{ a.pila_operator || '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500">IBC</dt>
                        <dd class="text-gray-900">{{ a.ibc ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500">Parafiscales</dt>
                        <dd class="text-gray-900">{{ a.pays_parafiscales ? 'Sí' : 'No' }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500">Independiente</dt>
                        <dd class="text-gray-900">{{ a.self_employed ? 'Sí' : 'No' }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500">Periodicidad</dt>
                        <dd class="text-gray-900">{{ a.payment_periodicity || '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500">Estado pago</dt>
                        <dd class="text-gray-900">{{ a.payment_status || '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500">Último período pagado</dt>
                        <dd class="text-gray-900">{{ a.last_payment_period || '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500">Vigente</dt>
                        <dd class="text-gray-900">{{ a.is_current ? 'Sí' : 'No' }}</dd>
                    </div>
                </dl>
            </div>
        </div>
    </AppLayout>
</template>

