<script setup>
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { ChevronLeft, Building2, Pencil, Phone, Mail, MapPin, User, Users } from 'lucide-vue-next';

const props = defineProps({
    payer: Object,
});

const payer = computed(() => props.payer?.data || props.payer || {});
const profiles = computed(() => payer.value.social_security_profiles || []);
</script>

<template>
    <AppLayout>
        <div class="max-w-4xl mx-auto space-y-6">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <Link href="/payers" class="inline-flex items-center gap-2 text-sm text-gray-500 hover:text-brand-600 transition-colors">
                        <ChevronLeft class="h-4 w-4" />
                        Volver a pagadores
                    </Link>
                    <div class="flex items-center gap-2 mt-1">
                        <span :class="[
                            'inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-medium',
                            payer.is_active ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-600'
                        ]">
                            {{ payer.is_active ? 'Activo' : 'Inactivo' }}
                        </span>
                    </div>
                </div>
                <Link :href="`/payers/${payer.id}/edit`" class="inline-flex items-center gap-2 px-4 py-2 bg-white border border-gray-300 text-gray-700 rounded-xl hover:bg-gray-50 transition-colors">
                    <Pencil class="h-4 w-4" />
                    Editar
                </Link>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <div class="lg:col-span-2 space-y-6">
                    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                        <div class="flex items-start gap-6">
                            <div class="h-14 w-14 rounded-xl bg-brand-100 flex items-center justify-center flex-shrink-0">
                                <Building2 class="h-7 w-7 text-brand-700" />
                            </div>
                            <div class="min-w-0 flex-1">
                                <h2 class="text-lg font-semibold text-gray-900">{{ payer.name }}</h2>
                                <p class="mt-1 text-sm text-gray-600">{{ payer.document_type_label }} {{ payer.document_number }}</p>
                                <dl class="mt-4 space-y-2">
                                    <div v-if="payer.address" class="flex items-start gap-2">
                                        <MapPin class="h-4 w-4 text-gray-400 mt-0.5 flex-shrink-0" />
                                        <span class="text-sm text-gray-600">{{ payer.address }}</span>
                                    </div>
                                    <div v-if="payer.phone" class="flex items-center gap-2">
                                        <Phone class="h-4 w-4 text-gray-400 flex-shrink-0" />
                                        <span class="text-sm text-gray-600">{{ payer.phone }}</span>
                                    </div>
                                    <div v-if="payer.email" class="flex items-center gap-2">
                                        <Mail class="h-4 w-4 text-gray-400 flex-shrink-0" />
                                        <a :href="`mailto:${payer.email}`" class="text-sm text-brand-600 hover:underline">{{ payer.email }}</a>
                                    </div>
                                    <div v-if="payer.contact_person" class="flex items-center gap-2">
                                        <User class="h-4 w-4 text-gray-400 flex-shrink-0" />
                                        <span class="text-sm text-gray-600">Contacto: {{ payer.contact_person }}</span>
                                    </div>
                                </dl>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="space-y-6">
                    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                        <h3 class="text-sm font-medium text-gray-500 uppercase tracking-wide">Afiliados con este pagador</h3>
                        <p class="mt-2 text-2xl font-semibold text-gray-900">{{ profiles.length }}</p>
                        <ul v-if="profiles.length" class="mt-4 space-y-2">
                            <li v-for="p in profiles" :key="p.id" class="flex items-center gap-2">
                                <Users class="h-4 w-4 text-gray-400 flex-shrink-0" />
                                <Link v-if="p.affiliate" :href="`/affiliates/${p.affiliate.id}`" class="text-sm text-brand-600 hover:underline">
                                    {{ p.affiliate.full_name }} ({{ p.affiliate.document_type_abbreviation }} {{ p.affiliate.document_number }})
                                </Link>
                                <span v-else class="text-sm text-gray-500">Afiliado #{{ p.affiliate_id }}</span>
                            </li>
                        </ul>
                        <p v-else class="mt-2 text-sm text-gray-500">Ningún afiliado asignado aún</p>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
