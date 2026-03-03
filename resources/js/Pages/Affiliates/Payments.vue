<script setup>
import { computed } from 'vue';
import { Link, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import DatePicker from '@/Components/DatePicker.vue';
import { ChevronLeft, FileText, CheckCircle, Loader2 } from 'lucide-vue-next';

const props = defineProps({
    affiliate: { type: Object, required: true },
    payments: { type: Array, default: () => [] },
    accountingRegistries: { type: Array, default: () => [] },
});

const paymentForm = useForm({
    payment_date: new Date().toISOString().slice(0, 10),
    amount: '',
    external_number: '',
    description: '',
    accounting_registry_id: '',
});

function submitPayment() {
    if (!props.affiliate?.id) return;
    paymentForm.post(`/affiliates/${props.affiliate.id}/payments`, {
        preserveScroll: true,
        onSuccess: () => {
            paymentForm.reset();
            paymentForm.payment_date = new Date().toISOString().slice(0, 10);
        },
    });
}

const formatCurrency = (value) =>
    new Intl.NumberFormat('es-CO', { style: 'currency', currency: 'COP', maximumFractionDigits: 0 }).format(value);
</script>

<template>
    <AppLayout title="Registro de pagos">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 py-6">
            <div class="mb-6">
                <Link
                    :href="`/affiliates/${affiliate.id}`"
                    class="inline-flex items-center gap-2 text-sm font-medium text-gray-600 hover:text-gray-900"
                >
                    <ChevronLeft class="h-4 w-4" />
                    Volver al afiliado
                </Link>
            </div>

            <div class="mb-6 p-4 rounded-xl bg-white border border-gray-200 shadow-sm">
                <h1 class="text-lg font-semibold text-gray-900">Registro de pagos</h1>
                <p class="text-sm text-gray-600 mt-0.5">
                    {{ affiliate.full_name || 'Afiliado' }}
                    <span v-if="affiliate.document_number" class="text-gray-500">· {{ affiliate.document_number }}</span>
                </p>
            </div>

            <div v-if="$page.props.flash?.success" class="mb-4 p-3 rounded-lg bg-emerald-50 text-emerald-800 text-sm">
                {{ $page.props.flash.success }}
            </div>

            <!-- Formulario registrar pago -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden mb-8">
                <div class="px-6 py-4 border-b border-gray-200 bg-gradient-to-r from-emerald-50 to-white">
                    <h2 class="font-semibold text-gray-900">Registrar nuevo pago</h2>
                </div>
                <div class="px-6 py-5">
                    <form @submit.prevent="submitPayment" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                        <div>
                            <DatePicker
                                v-model="paymentForm.payment_date"
                                label="Fecha de pago"
                            />
                            <p v-if="paymentForm.errors.payment_date" class="mt-1 text-sm text-red-600">
                                {{ paymentForm.errors.payment_date }}
                            </p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Valor (COP)</label>
                            <input
                                v-model="paymentForm.amount"
                                type="number"
                                min="0"
                                step="0.01"
                                class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500 text-sm"
                                placeholder="Ej: 500000"
                            />
                            <p v-if="paymentForm.errors.amount" class="mt-1 text-sm text-red-600">
                                {{ paymentForm.errors.amount }}
                            </p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Número recibo / factura</label>
                            <input
                                v-model="paymentForm.external_number"
                                type="text"
                                maxlength="100"
                                class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500 text-sm"
                                placeholder="Ej: RC-1234"
                            />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Tipo de registro</label>
                            <select
                                v-model="paymentForm.accounting_registry_id"
                                class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500 text-sm"
                            >
                                <option value="">— Seleccionar —</option>
                                <option
                                    v-for="reg in accountingRegistries"
                                    :key="reg.id"
                                    :value="reg.id"
                                >
                                    {{ reg.name }}
                                </option>
                            </select>
                        </div>
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Descripción</label>
                            <input
                                v-model="paymentForm.description"
                                type="text"
                                maxlength="255"
                                class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500 text-sm"
                                placeholder="Ej: Pago planilla enero 2026"
                            />
                        </div>
                        <div class="md:col-span-2 flex items-end justify-end">
                            <button
                                type="submit"
                                :disabled="paymentForm.processing"
                                class="inline-flex items-center gap-2 px-5 py-2.5 text-sm font-semibold rounded-lg bg-emerald-600 text-white hover:bg-emerald-700 disabled:opacity-50"
                            >
                                <Loader2 v-if="paymentForm.processing" class="h-4 w-4 animate-spin" />
                                <CheckCircle v-else class="h-4 w-4" />
                                {{ paymentForm.processing ? 'Guardando...' : 'Guardar pago' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Listado de pagos -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200 bg-gradient-to-r from-gray-50 to-white flex items-center gap-2">
                    <FileText class="h-5 w-5 text-gray-600" />
                    <h2 class="font-semibold text-gray-900">Pagos registrados</h2>
                    <span v-if="payments.length" class="px-2 py-0.5 bg-gray-200 text-gray-700 text-xs rounded-full">
                        {{ payments.length }}
                    </span>
                </div>
                <div class="overflow-x-auto">
                    <table v-if="payments.length" class="min-w-full text-sm">
                        <thead>
                            <tr class="text-left text-xs font-semibold text-gray-500 uppercase tracking-wide border-b border-gray-200 bg-gray-50">
                                <th class="py-3 px-4">Fecha</th>
                                <th class="py-3 px-4">Valor</th>
                                <th class="py-3 px-4">Tipo</th>
                                <th class="py-3 px-4">Número recibo / factura</th>
                                <th class="py-3 px-4">Descripción</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="p in payments" :key="p.id" class="border-b border-gray-100 hover:bg-gray-50/50">
                                <td class="py-3 px-4 text-gray-800">{{ p.payment_date_formatted }}</td>
                                <td class="py-3 px-4 font-semibold text-gray-900">{{ formatCurrency(p.amount) }}</td>
                                <td class="py-3 px-4 text-gray-700">{{ p.accounting_registry?.name || '—' }}</td>
                                <td class="py-3 px-4 text-gray-700">{{ p.external_number || '—' }}</td>
                                <td class="py-3 px-4 text-gray-700">{{ p.description || '—' }}</td>
                            </tr>
                        </tbody>
                    </table>
                    <p v-else class="px-6 py-8 text-sm text-gray-500 text-center">
                        Aún no se han registrado pagos para este afiliado.
                    </p>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
