<script setup>
import AppLayout from '@/layouts/AppLayout.vue';
import { dashboard } from '@/routes';
import { Head, router, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';


const page = usePage();

const props = defineProps({
    users: {
        type: Array,
        required: true
    }
})

const breadcrumbs = [
    {
        title: 'Dashboard',
        href: dashboard().url,
    },
];

// Check if current user is admin
const isAdmin = page.props.auth.user.role === 'admin'; // Adjust based on your role field

// Filter users based on role
const filteredUsers = computed(() => {
    if (isAdmin) {
        // Admin sees all users except themselves
        return props.users.filter(user => user.id !== page.props.auth.user.id);
    } else {
        // Regular users only see admin users
        return props.users.filter(user => user.role === 'admin' && user.id !== page.props.auth.user.id);
    }
});

const getInitials = (name) => {
    return name.split(' ').map(n => n[0]).join('').toUpperCase();
}

const getRandomColor = (id) => {
    const colors = [
        'bg-blue-500', 'bg-green-500', 'bg-purple-500',
        'bg-pink-500', 'bg-indigo-500', 'bg-teal-500',
        'bg-orange-500', 'bg-cyan-500', 'bg-amber-500'
    ];
    return colors[id % colors.length];
}

const startChat = (userId) => {
    router.get(`chat/${userId}`);
}
</script>

<template>

    <Head title="Dashboard" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="p-6">
            <!-- Header -->
            <div class="mb-8">
                <h1 class="text-2xl font-bold text-gray-900">
                    {{ isAdmin ? 'All Users' : 'Support Team' }}
                </h1>
                <p class="text-gray-600 mt-2">
                    {{ isAdmin ? 'Manage and view all system users' : 'Chat with our support team' }}
                </p>

                <!-- Role Badge -->
                <div class="mt-2">
                    <span :class="[
                        'inline-flex items-center px-3 py-1 rounded-full text-sm font-medium',
                        isAdmin
                            ? 'bg-purple-100 text-purple-800'
                            : 'bg-blue-100 text-blue-800'
                    ]">
                        {{ isAdmin ? 'Administrator' : 'User' }}
                    </span>
                </div>
            </div>

            <!-- Stats Cards -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
                <div class="bg-white rounded-lg shadow p-6">
                    <div class="flex items-center">
                        <div class="p-3 rounded-full bg-blue-100 text-blue-600">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z" />
                            </svg>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-600">Total Users</p>
                            <p class="text-2xl font-bold text-gray-900">{{ filteredUsers.length }}</p>
                        </div>
                    </div>
                </div>

                <!-- Admin-only stats -->
                <div v-if="isAdmin" class="bg-white rounded-lg shadow p-6">
                    <div class="flex items-center">
                        <div class="p-3 rounded-full bg-green-100 text-green-600">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                            </svg>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-600">Admin Users</p>
                            <p class="text-2xl font-bold text-gray-900">
                                {{props.users.filter(user => user.role === 'admin').length}}
                            </p>
                        </div>
                    </div>
                </div>

                <div v-if="isAdmin" class="bg-white rounded-lg shadow p-6">
                    <div class="flex items-center">
                        <div class="p-3 rounded-full bg-orange-100 text-orange-600">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                            </svg>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-600">Regular Users</p>
                            <p class="text-2xl font-bold text-gray-900">
                                {{props.users.filter(user => user.role !== 'admin').length}}
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Users Grid -->
            <div class="flex h-full flex-1 flex-col gap-6 overflow-x-auto rounded-xl p-4">
                <!-- Header Section -->
                <div class="flex items-center justify-between">
                    <div>
                        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
                            {{ isAdmin ? 'All Users' : 'Support Team' }}
                        </h1>
                        <p class="text-gray-600 dark:text-gray-400 mt-1">
                            {{ isAdmin
                                ? 'Manage your team members and their account permissions here.'
                                : 'Our support team is here to help you with any questions or issues.'
                            }}
                        </p>
                    </div>
                    <div class="text-sm text-gray-500 dark:text-gray-400">
                        {{ isAdmin ? `Total: ${filteredUsers.length} users` : `${filteredUsers.length} support staff
                        available` }}
                    </div>
                </div>

                <!-- Users Grid -->
                <div class="grid gap-6 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
                    <div v-for="user in filteredUsers" :key="user.id"
                        class="group relative overflow-hidden rounded-xl border border-sidebar-border/70 bg-white p-6 transition-all duration-200 hover:shadow-lg dark:border-sidebar-border dark:bg-gray-800 hover:border-gray-300 dark:hover:border-gray-600">

                        <!-- User Avatar/Initials -->
                        <div class="flex items-center gap-4">
                            <div :class="[
                                'flex h-12 w-12 items-center justify-center rounded-full text-white font-semibold text-sm',
                                user.role === 'admin'
                                    ? 'bg-gradient-to-br from-purple-500 to-pink-600'
                                    : 'bg-gradient-to-br from-blue-500 to-cyan-600'
                            ]">
                                {{ getInitials(user.name) }}
                            </div>
                            <div class="flex-1 min-w-0">
                                <h3
                                    class="font-semibold text-gray-900 truncate dark:text-white group-hover:text-blue-600 dark:group-hover:text-blue-400 transition-colors">
                                    {{ user.name }}
                                </h3>
                                <p class="text-sm text-gray-500 truncate dark:text-gray-400 mt-1">
                                    {{ user.email }}
                                </p>
                                <!-- Role Badge -->
                                <span :class="[
                                    'inline-block mt-1 px-2 py-0.5 rounded-full text-xs font-medium',
                                    user.role === 'admin'
                                        ? 'bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-300'
                                        : 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300'
                                ]">
                                    {{ user.role === 'admin' ? 'Admin' : 'User' }}
                                </span>
                            </div>
                        </div>

                        <!-- User Details -->
                        <div class="mt-4 space-y-2">
                            <div class="flex items-center justify-between text-sm">
                                <span class="text-gray-500 dark:text-gray-400">User ID:</span>
                                <span class="font-mono text-gray-700 dark:text-gray-300">#{{ user.id }}</span>
                            </div>
                            <div v-if="user.created_at" class="flex items-center justify-between text-sm">
                                <span class="text-gray-500 dark:text-gray-400">Joined:</span>
                                <span class="text-gray-700 dark:text-gray-300">
                                    {{ new Date(user.created_at).toLocaleDateString() }}
                                </span>
                            </div>
                        </div>

                        <!-- Action Buttons -->
                        <div class="mt-6 flex gap-2">
                            <button @click="startChat(user.id)"
                                class="flex-1 rounded-lg bg-blue-50 px-3 py-2 text-sm font-medium text-blue-600 transition-all hover:bg-blue-100 dark:bg-blue-900/20 dark:text-blue-400 dark:hover:bg-blue-900/30">
                                {{ isAdmin ? 'Chat' : 'Chat' }}
                            </button>
                            <button v-if="isAdmin"
                                class="flex-1 rounded-lg bg-gray-50 px-3 py-2 text-sm font-medium text-gray-600 transition-all hover:bg-gray-100 dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-gray-600">
                                Edit
                            </button>
                        </div>

                        <!-- Hover effect background -->
                        <div :class="[
                            'absolute inset-0 -z-10 bg-gradient-to-br opacity-0 transition-opacity duration-200 group-hover:opacity-100',
                            user.role === 'admin'
                                ? 'from-purple-50 to-pink-50 dark:from-purple-900/10 dark:to-pink-900/10'
                                : 'from-blue-50 to-cyan-50 dark:from-blue-900/10 dark:to-cyan-900/10'
                        ]" />
                    </div>
                </div>

                <!-- Empty State -->
                <div v-if="filteredUsers.length === 0"
                    class="flex flex-col items-center justify-center rounded-xl border-2 border-dashed border-gray-300 p-12 text-center dark:border-gray-600">
                    <div class="rounded-full bg-gray-100 p-4 dark:bg-gray-700">
                        <svg class="h-8 w-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z" />
                        </svg>
                    </div>
                    <h3 class="mt-4 text-lg font-medium text-gray-900 dark:text-white">
                        {{ isAdmin ? 'No users found' : 'No support staff available' }}
                    </h3>
                    <p class="mt-2 text-gray-500 dark:text-gray-400">
                        {{ isAdmin
                            ? 'Get started by creating your first user.'
                            : 'Please check back later or contact system administrator.'
                        }}
                    </p>
                    <button v-if="isAdmin"
                        class="mt-4 rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white transition-colors hover:bg-blue-700">
                        Add User
                    </button>
                </div>
            </div>
        </div>
    </AppLayout>
</template>