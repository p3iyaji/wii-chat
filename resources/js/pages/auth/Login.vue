<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import TextLink from '@/components/TextLink.vue';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import AuthBase from '@/layouts/AuthLayout.vue';
import { register } from '@/routes';
import { store } from '@/routes/login';
import { request } from '@/routes/password';
import { Form, Head } from '@inertiajs/vue3';

defineProps<{
    status?: string;
    canResetPassword: boolean;
    canRegister: boolean;
}>();
</script>

<template>

    <Head title="Log in to BOA Chat" />

    <div class="relative min-h-screen bg-black text-white">
        <!-- Full Page Boa Constrictor Background -->
        <div class="absolute inset-0 overflow-hidden">
            <!-- Background image -->
            <div class="absolute inset-0">
                <img src="/images/boa_constrictor.jpg" alt="Boa Constrictor"
                    class="w-full h-full object-cover opacity-50" />
            </div>


            <!-- Gradient overlay -->
            <div class="absolute inset-0 bg-gradient-to-b from-black/80 via-black/60 to-black/80"></div>

            <!-- Animated background elements -->
            <div class="absolute inset-0 overflow-hidden pointer-events-none">
                <div class="absolute -top-20 -left-20 w-96 h-96 bg-emerald-500/5 rounded-full blur-3xl animate-pulse">
                </div>
                <div
                    class="absolute -bottom-20 -right-20 w-96 h-96 bg-green-500/5 rounded-full blur-3xl animate-pulse delay-1000">
                </div>
            </div>
        </div>

        <!-- Main Content -->
        <div class="relative z-10 flex min-h-screen items-center justify-center p-6 lg:p-8">
            <div class="w-full max-w-md">
                <!-- Header -->
                <div class="mb-10 text-center">
                    <!-- Logo -->
                    <div class="mb-6 flex justify-center">
                        <div class="relative">
                            <div
                                class="absolute -inset-4 rounded-full bg-gradient-to-r from-emerald-500 to-green-600 blur-xl opacity-20">
                            </div>
                            <div
                                class="relative rounded-full bg-gradient-to-br from-emerald-500 to-green-600 p-3 shadow-2xl">
                                <img src="/images/realpay1-logo.png" alt="RealPay Logo"
                                    class="h-16 w-16 object-contain filter drop-shadow-lg" />
                            </div>
                        </div>
                    </div>

                    <!-- Title -->
                    <h1 class="mb-3 text-3xl font-bold tracking-tight">
                        <span class="bg-gradient-to-r from-green-400 to-emerald-300 bg-clip-text text-transparent">
                            Welcome Back
                        </span>
                    </h1>
                    <p class="text-lg text-gray-300">
                        Log in to <span class="text-emerald-300 font-semibold">BOA Chat</span>
                    </p>
                    <div class="mt-4 h-1 w-16 bg-gradient-to-r from-green-500 to-emerald-400 mx-auto rounded-full">
                    </div>
                </div>

                <!-- Status Message -->
                <div v-if="status"
                    class="mb-6 rounded-xl bg-emerald-900/30 border border-emerald-700/50 p-4 text-center text-sm font-medium text-emerald-300">
                    {{ status }}
                </div>

                <!-- Login Form -->
                <Form v-bind="store.form()" :reset-on-success="['password']" v-slot="{ errors, processing }"
                    class="flex flex-col gap-6">
                    <!-- Form Container -->
                    <div class="rounded-2xl bg-white/5 backdrop-blur-sm border border-white/10 p-8 shadow-2xl">
                        <div class="grid gap-6">
                            <!-- Email Field -->
                            <div class="grid gap-3">
                                <Label for="email" class="text-gray-200 font-medium">
                                    <span class="flex items-center gap-2">
                                        <svg class="h-4 w-4 text-emerald-400" fill="none" stroke="currentColor"
                                            viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M16 12a4 4 0 10-8 0 4 4 0 008 0zm0 0v1.5a2.5 2.5 0 005 0V12a9 9 0 10-9 9m4.5-1.206a8.959 8.959 0 01-4.5 1.207" />
                                        </svg>
                                        Email Address
                                    </span>
                                </Label>
                                <Input id="email" type="email" name="email" required autofocus :tabindex="1"
                                    autocomplete="email" placeholder="you@example.com"
                                    class="bg-white/5 border-white/20 text-white placeholder:text-gray-400 focus:border-emerald-500 focus:ring-emerald-500" />
                                <InputError :message="errors.email" class="text-red-400" />
                            </div>

                            <!-- Password Field -->
                            <div class="grid gap-3">
                                <div class="flex items-center justify-between">
                                    <Label for="password" class="text-gray-200 font-medium">
                                        <span class="flex items-center gap-2">
                                            <svg class="h-4 w-4 text-emerald-400" fill="none" stroke="currentColor"
                                                viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                                            </svg>
                                            Password
                                        </span>
                                    </Label>
                                    <TextLink v-if="canResetPassword" :href="request()"
                                        class="text-sm text-emerald-400 hover:text-emerald-300 transition-colors"
                                        :tabindex="5">
                                        Forgot password?
                                    </TextLink>
                                </div>
                                <Input id="password" type="password" name="password" required :tabindex="2"
                                    autocomplete="current-password" placeholder="••••••••"
                                    class="bg-white/5 border-white/20 text-white placeholder:text-gray-400 focus:border-emerald-500 focus:ring-emerald-500" />
                                <InputError :message="errors.password" class="text-red-400" />
                            </div>

                            <!-- Remember Me -->
                            <div class="flex items-center justify-between">
                                <Label for="remember" class="flex items-center space-x-3 cursor-pointer">
                                    <div class="relative">
                                        <Checkbox id="remember" name="remember" :tabindex="3"
                                            class="border-white/30 bg-white/5 text-emerald-500 focus:ring-emerald-500" />
                                    </div>
                                    <span class="text-gray-300">Remember me</span>
                                </Label>
                            </div>

                            <!-- Submit Button -->
                            <Button type="submit"
                                class="mt-2 w-full bg-gradient-to-r from-green-600 to-emerald-500 hover:from-green-700 hover:to-emerald-600 text-white font-semibold py-3 rounded-xl transition-all duration-300 transform hover:-translate-y-0.5 hover:shadow-2xl"
                                :tabindex="4" :disabled="processing" data-test="login-button">
                                <div class="flex items-center justify-center gap-2">
                                    <Spinner v-if="processing" class="text-white" />
                                    <span v-if="processing">Logging in...</span>
                                    <span v-else>Log in</span>
                                    <svg v-if="!processing" class="h-5 w-5" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M13 7l5 5m0 0l-5 5m5-5H6" />
                                    </svg>
                                </div>
                            </Button>
                        </div>

                        <!-- Register Link -->
                        <div class="mt-8 pt-6 border-t border-white/10 text-center text-sm text-gray-400"
                            v-if="canRegister">
                            Don't have an account?
                            <TextLink :href="register()" :tabindex="5"
                                class="ml-1 text-white hover:text-emerald-300 font-semibold">
                                Sign up now
                            </TextLink>
                        </div>
                    </div>
                </Form>

                <!-- Security Note -->
                <div class="mt-8 text-center">
                    <p class="text-xs text-gray-500">
                        <span class="text-emerald-400">BOA Chat</span> • Secure communication powered by
                        <span class="text-white font-medium">Realpay Global Services</span>
                    </p>
                </div>
            </div>
        </div>
    </div>
</template>