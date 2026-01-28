<script setup>
import { ref } from 'vue';
import { useForm } from '@inertiajs/vue3';
import { Eye, EyeOff, Mail, Lock, Loader2 } from 'lucide-vue-next';

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

const showPassword = ref(false);

const submit = () => {
    form.post('/login', {
        onFinish: () => form.reset('password'),
    });
};
</script>

<template>
    <div class="min-h-screen flex items-center justify-center bg-gradient-to-br from-brand-50 via-white to-accent-300/20 py-12 px-4">
        <div class="max-w-md w-full space-y-8">
            <!-- Logo -->
            <div class="text-center">
                <div class="mx-auto h-16 w-16 rounded-2xl bg-gradient-to-br from-brand-500 to-brand-600 flex items-center justify-center shadow-lg">
                    <span class="text-white font-bold text-3xl">S</span>
                </div>
                <h2 class="mt-6 text-3xl font-bold text-brand-900">Central de Citas</h2>
                <p class="mt-2 text-sm text-gray-600">Serviconli - Gestión de Citas Médicas</p>
            </div>

            <!-- Form -->
            <div class="bg-white py-8 px-6 shadow-xl rounded-2xl">
                <form @submit.prevent="submit" class="space-y-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Correo electrónico</label>
                        <input v-model="form.email" type="email" required autofocus placeholder="tu@gruposerviconli.com"
                            :class="['block w-full rounded-lg border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500', form.errors.email ? 'border-red-300' : '']" />
                        <p v-if="form.errors.email" class="mt-1 text-sm text-red-600">{{ form.errors.email }}</p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Contraseña</label>
                        <div class="relative">
                            <input v-model="form.password" :type="showPassword ? 'text' : 'password'" required placeholder="••••••••"
                                :class="['block w-full rounded-lg border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500 pr-10', form.errors.password ? 'border-red-300' : '']" />
                            <button type="button" @click="showPassword = !showPassword" class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-400 hover:text-gray-600 transition-colors">
                                <EyeOff v-if="showPassword" class="h-5 w-5" />
                                <Eye v-else class="h-5 w-5" />
                            </button>
                        </div>
                    </div>

                    <div class="flex items-center">
                        <input v-model="form.remember" type="checkbox" class="h-4 w-4 text-brand-600 border-gray-300 rounded" />
                        <label class="ml-2 text-sm text-gray-700">Recordarme</label>
                    </div>

                    <button type="submit" :disabled="form.processing" class="w-full inline-flex items-center justify-center gap-2 px-4 py-3 font-medium rounded-lg bg-brand-500 text-white hover:bg-brand-600 transition-colors disabled:opacity-50">
                        <Loader2 v-if="form.processing" class="h-5 w-5 animate-spin" />
                        {{ form.processing ? 'Ingresando...' : 'Ingresar' }}
                    </button>
                </form>
            </div>

            <p class="text-center text-xs text-gray-500">© {{ new Date().getFullYear() }} Serviconli</p>
        </div>
    </div>
</template>
