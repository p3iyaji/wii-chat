<script setup lang="ts">
import { dashboard, login, register } from '@/routes';
import { Head, Link } from '@inertiajs/vue3';

withDefaults(
    defineProps<{
        canRegister: boolean;
    }>(),
    {
        canRegister: true,
    },
);
</script>

<template>

    <Head title="Welcome to BOA Chat">
        <link rel="preconnect" href="https://rsms.me/" />
        <link rel="stylesheet" href="https://rsms.me/inter/inter.css" />
    </Head>

    <div class="relative min-h-screen bg-black text-white">
        <!-- Full Page Boa Constrictor Background -->
        <div class="absolute inset-0 overflow-hidden">
            <div class="absolute inset-0 overflow-hidden">
                <img src="/images/boa_constrictor.jpg" alt="Boa Constrictor"
                    class="absolute inset-0 w-full h-full object-cover opacity-70" />
                <div class="absolute inset-0 bg-gradient-to-b from-black/70 via-black/50 to-black/70"></div>
            </div>

            <!-- Gradient overlay for better text readability -->
            <div class="absolute inset-0 bg-gradient-to-b from-black/70 via-black/50 to-black/70"></div>
        </div>

        <!-- Navigation -->
        <header class="relative z-10 p-6 lg:p-8">
            <nav class="flex items-center justify-end gap-4">
                <Link v-if="$page.props.auth.user" :href="dashboard()"
                    class="inline-block rounded-lg border border-white/20 px-5 py-1.5 text-sm leading-normal text-white hover:border-white/40 hover:bg-white/10 transition-all duration-300">
                    Dashboard
                </Link>
                <template v-else>
                    <Link :href="login()"
                        class="inline-block rounded-lg border border-transparent px-5 py-1.5 text-sm leading-normal text-white hover:border-white/20 hover:bg-white/5 transition-all duration-300">
                        Log in
                    </Link>
                    <Link v-if="canRegister" :href="register()"
                        class="inline-block rounded-lg border border-white/20 px-5 py-1.5 text-sm leading-normal text-white hover:border-white/40 hover:bg-white/10 transition-all duration-300">
                        Register
                    </Link>
                </template>
            </nav>
        </header>

        <!-- Main Content -->
        <main class="relative z-10 flex min-h-[calc(100vh-80px)] items-center justify-center p-6 lg:p-8">
            <div class="w-full max-w-4xl text-center">
                <!-- Logo -->
                <div class="mb-12 flex justify-center">
                    <div class="relative">
                        <!-- Outer glow effect -->
                        <div
                            class="absolute -inset-4 bg-gradient-to-r from-emerald-500 to-green-600 rounded-full blur-xl opacity-30 animate-pulse">
                        </div>

                        <!-- Main logo container -->
                        <div
                            class="relative rounded-full bg-gradient-to-br from-emerald-500 to-green-600 p-4 shadow-2xl ring-4 ring-emerald-200/20">
                            <img src="/images/realpay1-logo.png" alt="RealPay Logo"
                                class="h-32 w-32 object-contain filter drop-shadow-lg" />
                        </div>

                        <!-- Animated ring -->
                        <div class="absolute -inset-2 rounded-full border-4 border-emerald-400/30 animate-ping"></div>
                    </div>
                </div>

                <!-- Main Title -->
                <h1 class="mb-6 text-5xl lg:text-7xl font-bold tracking-tight">
                    <span class="bg-gradient-to-r from-green-400 to-emerald-300 bg-clip-text text-transparent">
                        Welcome to
                    </span>
                    <br>
                    <span class="mt-4 block text-6xl lg:text-8xl font-black">
                        BOA CHAT
                    </span>
                </h1>

                <!-- Subtitle -->
                <div class="mb-10">
                    <p class="text-2xl lg:text-3xl text-gray-300 font-medium">
                        by
                        <span class="text-emerald-300 font-bold">Realpay Global Services</span>
                    </p>
                    <div class="mt-4 h-1 w-24 bg-gradient-to-r from-green-500 to-emerald-400 mx-auto rounded-full">
                    </div>
                </div>

                <!-- Description -->
                <p class="mx-auto mb-10 max-w-2xl text-lg lg:text-xl text-gray-300 leading-relaxed">
                    Experience secure, real-time communication wrapped in the power and elegance of the mighty boa.
                    Connect, collaborate, and constrict your conversations with confidence.
                </p>

                <!-- Call to Action -->
                <div class="flex flex-col sm:flex-row gap-4 justify-center">
                    <Link v-if="!$page.props.auth.user" :href="login()"
                        class="inline-flex items-center justify-center rounded-xl bg-gradient-to-r from-green-600 to-emerald-500 px-8 py-4 text-lg font-semibold text-white hover:from-green-700 hover:to-emerald-600 transition-all duration-300 transform hover:-translate-y-1 hover:shadow-2xl">
                        <svg class="mr-3 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1" />
                        </svg>
                        Get Started
                    </Link>

                    <Link v-if="!$page.props.auth.user && canRegister" :href="register()"
                        class="inline-flex items-center justify-center rounded-xl border-2 border-emerald-400/30 px-8 py-4 text-lg font-semibold text-emerald-300 hover:border-emerald-400 hover:bg-emerald-400/10 transition-all duration-300">
                        <svg class="mr-3 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" />
                        </svg>
                        Create Account
                    </Link>

                    <Link v-if="$page.props.auth.user" :href="dashboard()"
                        class="inline-flex items-center justify-center rounded-xl bg-gradient-to-r from-green-600 to-emerald-500 px-8 py-4 text-lg font-semibold text-white hover:from-green-700 hover:to-emerald-600 transition-all duration-300 transform hover:-translate-y-1 hover:shadow-2xl">
                        <svg class="mr-3 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                        </svg>
                        Go to Dashboard
                    </Link>
                </div>

                <!-- Features Grid -->
                <div class="mt-16 grid grid-cols-1 md:grid-cols-3 gap-6 max-w-4xl mx-auto">
                    <div
                        class="rounded-2xl bg-white/5 p-6 backdrop-blur-sm border border-white/10 hover:border-emerald-400/30 transition-all duration-300">
                        <div class="mb-4 inline-flex rounded-lg bg-emerald-900/30 p-3">
                            <svg class="h-8 w-8 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                    d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z" />
                            </svg>
                        </div>
                        <h3 class="mb-2 text-xl font-bold text-white">Real-time Chat</h3>
                        <p class="text-gray-400">Instant messaging with typing indicators and file sharing</p>
                    </div>

                    <div
                        class="rounded-2xl bg-white/5 p-6 backdrop-blur-sm border border-white/10 hover:border-emerald-400/30 transition-all duration-300">
                        <div class="mb-4 inline-flex rounded-lg bg-emerald-900/30 p-3">
                            <svg class="h-8 w-8 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                    d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                            </svg>
                        </div>
                        <h3 class="mb-2 text-xl font-bold text-white">Bank-level Security</h3>
                        <p class="text-gray-400">End-to-end encryption and secure data handling</p>
                    </div>

                    <div
                        class="rounded-2xl bg-white/5 p-6 backdrop-blur-sm border border-white/10 hover:border-emerald-400/30 transition-all duration-300">
                        <div class="mb-4 inline-flex rounded-lg bg-emerald-900/30 p-3">
                            <svg class="h-8 w-8 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                    d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9" />
                            </svg>
                        </div>
                        <h3 class="mb-2 text-xl font-bold text-white">Global Connectivity</h3>
                        <p class="text-gray-400">Connect with anyone, anywhere in the world</p>
                    </div>
                </div>

                <!-- Footer Note -->
                <div class="mt-16 pt-8 border-t border-white/10">
                    <p class="text-gray-400 text-sm">
                        <span class="text-emerald-400">BOA Chat</span> is powered by
                        <span class="font-semibold text-white">Realpay Global Services</span> •
                        Secure financial communication since 2023
                    </p>
                </div>
            </div>
        </main>

        <!-- Animated Background Elements -->
        <div class="absolute inset-0 overflow-hidden pointer-events-none">
            <!-- Animated snake-like elements -->
            <div class="absolute -top-20 -left-20 w-96 h-96 bg-emerald-500/10 rounded-full blur-3xl animate-pulse">
            </div>
            <div
                class="absolute -bottom-20 -right-20 w-96 h-96 bg-green-500/10 rounded-full blur-3xl animate-pulse delay-1000">
            </div>
            <div
                class="absolute top-1/2 right-1/4 w-64 h-64 bg-emerald-400/5 rounded-full blur-2xl animate-pulse delay-500">
            </div>
        </div>
    </div>
</template>