<script setup>
import { Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { Settings, Building2, Shield, ShieldAlert, Wallet, Truck, UserCircle, FileText, ClipboardList, BookOpen, Percent, Heart } from 'lucide-vue-next';

const props = defineProps({
    catalogs: Array,
});

const icons = {
    eps: Heart,
    afps: Building2,
    arps: Shield,
    ccfs: Wallet,
    'operadores-pago': Truck,
    'client-types': UserCircle,
    'contributor-types': FileText,
    'novelty-types': ClipboardList,
    'accounting-registries': BookOpen,
    'risk-classes': ShieldAlert,
    'contribution-parameters': Percent,
};
</script>

<template>
    <AppLayout>
        <div class="max-w-4xl mx-auto space-y-6">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 flex items-center gap-2">
                    <Settings class="h-6 w-6 text-brand-600" />
                    Configuración
                </h1>
                <p class="mt-1 text-sm text-gray-500">Catálogos de seguridad social y tipos configurables</p>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <Link
                    v-for="cat in (catalogs || [])"
                    :key="cat.key"
                    :href="cat.href"
                    class="flex items-start gap-4 p-5 bg-white rounded-xl shadow-sm border border-gray-200 hover:shadow-md hover:border-brand-200 transition-all group"
                >
                    <div class="rounded-lg p-3 bg-brand-50 text-brand-600 group-hover:bg-brand-100 transition-colors">
                        <component :is="icons[cat.key] || Settings" class="h-6 w-6" />
                    </div>
                    <div class="min-w-0 flex-1">
                        <p class="font-semibold text-gray-900 group-hover:text-brand-700">{{ cat.label }}</p>
                        <p class="text-sm text-gray-500 mt-0.5">{{ cat.description }}</p>
                    </div>
                </Link>
            </div>
        </div>
    </AppLayout>
</template>
