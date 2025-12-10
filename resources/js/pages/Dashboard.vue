<script setup>
import AppLayout from '@/layouts/AppLayout.vue';
import { dashboard } from '@/routes';
import { Head, router, usePage } from '@inertiajs/vue3';
import { computed, onMounted, onUnmounted, ref } from 'vue';
import { useNotifications } from '@/composables/useNotifications';
import axios from 'axios';

const page = usePage();
const { getNotificationCount, markAsRead, setupGlobalListener } = useNotifications();
const onlineStatuses = ref({});

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
const isAdmin = page.props.auth.user.role === 'admin';

// Initialize online statuses from props
props.users.forEach(user => {
    onlineStatuses.value[user.id] = user.is_online_now;
});

// Filter users based on role
const filteredUsers = computed(() => {
    if (isAdmin) {
        return props.users.filter(user => user.id !== page.props.auth.user.id);
    } else {
        return props.users.filter(user => user.role === 'admin' && user.id !== page.props.auth.user.id);
    }
});

const getInitials = (name) => {
    return name.split(' ').map(n => n[0]).join('').toUpperCase();
}

const startChat = (userId) => {
    markAsRead(userId);
    router.get(`/chat/${userId}`);
}

// JavaScript ping system
let pingInterval = null;
let onlineCheckInterval = null;

const sendPing = () => {
    axios.post('/user/ping').catch(() => {
        console.log('Ping failed');
    });
};

// Periodically check online status of other users
const checkOnlineStatus = () => {
    filteredUsers.value.forEach(user => {
        if (user.last_seen_at) {
            const lastSeen = new Date(user.last_seen_at);
            const now = new Date();
            const diffMs = now - lastSeen;
            // Consider online if last seen within 3 minutes
            onlineStatuses.value[user.id] = diffMs < 3 * 60 * 1000;
        }
    });
};

// Setup Echo listener for real-time online status updates
const setupOnlineStatusListener = () => {
    if (window.Echo) {
        try {
            const channel = window.Echo.join('online-users');

            channel.here((users) => {
                users.forEach(user => {
                    if (user.id !== page.props.auth.user.id) {
                        onlineStatuses.value[user.id] = true;
                    }
                });
            })
                .joining((user) => {
                    if (user.id !== page.props.auth.user.id) {
                        onlineStatuses.value[user.id] = true;
                    }
                })
                .leaving((user) => {
                    if (user.id !== page.props.auth.user.id) {
                        onlineStatuses.value[user.id] = false;
                    }
                })
                .listen('.UserOnlineStatusUpdated', (e) => {
                    if (e.user && e.user.id !== page.props.auth.user.id) {
                        onlineStatuses.value[e.user.id] = e.is_online;
                    }
                });

            return () => {
                window.Echo.leave('online-users');
            };
        } catch (error) {
            console.error('Echo error:', error);
        }
    }
    return () => { };
};

onMounted(() => {
    // Send initial ping
    sendPing();

    // Setup ping every 30 seconds
    pingInterval = setInterval(sendPing, 30000);

    // Check online status every 10 seconds
    onlineCheckInterval = setInterval(checkOnlineStatus, 10000);

    // Setup online status listener
    const cleanupOnlineListener = setupOnlineStatusListener();

    // Setup notification listener
    const cleanupNotificationListener = setupGlobalListener();

    // Handle page visibility
    document.addEventListener('visibilitychange', handleVisibilityChange);

    // Cleanup on unmount
    onUnmounted(() => {
        if (pingInterval) clearInterval(pingInterval);
        if (onlineCheckInterval) clearInterval(onlineCheckInterval);
        if (cleanupOnlineListener) cleanupOnlineListener();
        if (cleanupNotificationListener) cleanupNotificationListener();

        // Mark as offline when leaving
        axios.post('/user/offline').catch(() => { });

        document.removeEventListener('visibilitychange', handleVisibilityChange);
    });
});

const handleVisibilityChange = () => {
    if (document.hidden) {
        // Tab is hidden, mark as offline
        axios.post('/user/offline').catch(() => { });
    } else {
        // Tab is visible again, mark as online
        sendPing();
    }
};

// Count online users
const onlineCount = computed(() => {
    return Object.values(onlineStatuses.value).filter(status => status).length;
});

// Helper to get last seen time
const getLastSeenTime = (user) => {
    if (!user.last_seen_at) return 'Never';

    const lastSeen = new Date(user.last_seen_at);
    const now = new Date();
    const diffMs = now - lastSeen;
    const diffMins = Math.floor(diffMs / 60000);

    if (diffMins < 1) return 'Just now';
    if (diffMins < 60) return `${diffMins}m ago`;

    const diffHours = Math.floor(diffMins / 60);
    if (diffHours < 24) return `${diffHours}h ago`;

    const diffDays = Math.floor(diffHours / 24);
    return `${diffDays}d ago`;
};
</script>


<template>

    <Head title="Dashboard" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="min-h-screen bg-white dark:bg-gray-900 transition-colors duration-200">
            <!-- Mobile Menu Button (if needed for AppLayout) -->
            <!-- Add this if your AppLayout doesn't have mobile menu toggle -->

            <div class="p-4 sm:p-6">
                <!-- Header -->
                <div class="mb-6 sm:mb-8">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                        <div class="flex-1 min-w-0">
                            <h1 class="text-2xl sm:text-3xl font-bold text-gray-900 dark:text-white truncate">
                                {{ isAdmin ? 'Chat Management' : 'Chat Dashboard' }}
                            </h1>
                            <p class="text-sm sm:text-base text-gray-600 dark:text-gray-300 mt-1 sm:mt-2 truncate">
                                {{ isAdmin ? 'Manage and view all system users' : 'Chat with our support team' }}
                            </p>
                        </div>

                        <!-- Role Badge with Online Status -->
                        <div class="flex items-center gap-3 sm:gap-4 mt-2 sm:mt-0">
                            <div class="flex items-center">
                                <div class="relative mr-2">
                                    <div class="h-2 w-2 rounded-full bg-green-500"></div>
                                    <div class="absolute h-2 w-2 rounded-full bg-green-500 animate-ping"></div>
                                </div>
                                <span class="text-xs sm:text-sm text-gray-600 dark:text-gray-300 hidden xs:inline">
                                    Online
                                </span>
                            </div>
                            <span :class="[
                                'inline-flex items-center px-3 sm:px-4 py-1.5 sm:py-2 rounded-full text-xs sm:text-sm font-semibold whitespace-nowrap',
                                'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200'
                            ]">
                                {{ isAdmin ? 'Admin' : 'User' }}
                                <div
                                    class="ml-1 sm:ml-2 h-1.5 w-1.5 sm:h-2 sm:w-2 rounded-full bg-blue-500 dark:bg-blue-400">
                                </div>
                            </span>
                        </div>
                    </div>

                    <!-- Decorative line -->
                    <div class="mt-3 sm:mt-4 h-0.5 sm:h-1 w-16 sm:w-24 bg-blue-500 rounded-full"></div>
                </div>

                <!-- Stats Cards - Improved for mobile -->
                <div class="grid grid-cols-2 sm:grid-cols-2 md:grid-cols-4 gap-3 sm:gap-4 md:gap-6 mb-6 sm:mb-8">
                    <!-- Total Users Card -->
                    <div
                        class="relative overflow-hidden rounded-lg sm:rounded-xl bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 p-4 sm:p-6 hover:border-blue-400 dark:hover:border-blue-500 transition-all duration-300">
                        <div class="flex items-center">
                            <div
                                class="p-2 sm:p-3 rounded-lg bg-blue-50 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400">
                                <svg class="w-5 h-5 sm:w-6 sm:h-6" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z" />
                                </svg>
                            </div>
                            <div class="ml-3 sm:ml-4">
                                <p class="text-xs sm:text-sm font-medium text-gray-600 dark:text-gray-300 truncate">
                                    Total Users</p>
                                <p class="text-xl sm:text-2xl font-bold text-gray-900 dark:text-white">
                                    {{ filteredUsers.length }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Online Users Card -->
                    <div
                        class="relative overflow-hidden rounded-lg sm:rounded-xl bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 p-4 sm:p-6 hover:border-green-400 dark:hover:border-green-500 transition-all duration-300">
                        <div class="flex items-center">
                            <div
                                class="p-2 sm:p-3 rounded-lg bg-green-50 dark:bg-green-900/30 text-green-600 dark:text-green-400">
                                <svg class="w-5 h-5 sm:w-6 sm:h-6" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                            <div class="ml-3 sm:ml-4">
                                <p class="text-xs sm:text-sm font-medium text-gray-600 dark:text-gray-300 truncate">
                                    Online Now</p>
                                <p class="text-xl sm:text-2xl font-bold text-gray-900 dark:text-white">
                                    {{ onlineCount }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Admin Users Card - Show only on medium screens and up -->
                    <div v-if="isAdmin"
                        class="hidden sm:block relative overflow-hidden rounded-lg sm:rounded-xl bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 p-4 sm:p-6 hover:border-blue-400 dark:hover:border-blue-500 transition-all duration-300">
                        <div class="flex items-center">
                            <div
                                class="p-2 sm:p-3 rounded-lg bg-blue-50 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400">
                                <svg class="w-5 h-5 sm:w-6 sm:h-6" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                                </svg>
                            </div>
                            <div class="ml-3 sm:ml-4">
                                <p class="text-xs sm:text-sm font-medium text-gray-600 dark:text-gray-300 truncate">
                                    Admin Users</p>
                                <p class="text-xl sm:text-2xl font-bold text-gray-900 dark:text-white">
                                    {{props.users.filter(user => user.role === 'admin').length}}
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Regular Users Card - Show only on medium screens and up -->
                    <div v-if="isAdmin"
                        class="hidden sm:block relative overflow-hidden rounded-lg sm:rounded-xl bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 p-4 sm:p-6 hover:border-blue-400 dark:hover:border-blue-500 transition-all duration-300">
                        <div class="flex items-center">
                            <div
                                class="p-2 sm:p-3 rounded-lg bg-blue-50 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400">
                                <svg class="w-5 h-5 sm:w-6 sm:h-6" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                </svg>
                            </div>
                            <div class="ml-3 sm:ml-4">
                                <p class="text-xs sm:text-sm font-medium text-gray-600 dark:text-gray-300 truncate">
                                    Regular Users</p>
                                <p class="text-xl sm:text-2xl font-bold text-gray-900 dark:text-white">
                                    {{props.users.filter(user => user.role !== 'admin').length}}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Users Section -->
                <div
                    class="relative rounded-xl bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 p-4 sm:p-6">
                    <!-- Header Section -->
                    <div class="mb-6 sm:mb-8">
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 sm:gap-0">
                            <div class="flex-1 min-w-0">
                                <h2 class="text-xl sm:text-2xl font-bold text-gray-900 dark:text-white truncate">
                                    {{ isAdmin ? 'User Directory' : 'Members' }}
                                </h2>
                                <p class="text-xs sm:text-sm text-gray-600 dark:text-gray-300 mt-1 truncate">
                                    {{ isAdmin
                                        ? 'Manage your team members and their account permissions here.'
                                        : 'Chat with members today'
                                    }}
                                </p>
                            </div>
                            <div
                                class="mt-2 sm:mt-0 text-xs sm:text-sm px-2 sm:px-3 py-1 rounded-full bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 whitespace-nowrap">
                                {{ isAdmin ? `${filteredUsers.length} users` : `${filteredUsers.length} staff` }}
                                <span class="ml-1 sm:ml-2 text-green-600 dark:text-green-400">
                                    • {{ onlineCount }} online
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Users Grid - Improved for mobile -->
                    <div class="grid gap-4 sm:gap-6 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
                        <div v-for="user in filteredUsers" :key="user.id"
                            class="group relative overflow-hidden rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-4 sm:p-6 transition-all duration-300 hover:border-blue-400 dark:hover:border-blue-500 hover:shadow-sm sm:hover:shadow-md">

                            <!-- Notification Badge -->
                            <div v-if="getNotificationCount(user.id) > 0"
                                class="absolute top-2 right-2 sm:top-3 sm:right-3 z-10">
                                <div class="relative">
                                    <span
                                        class="animate-ping absolute inline-flex h-full w-full rounded-full bg-red-400 opacity-75"></span>
                                    <span
                                        class="relative inline-flex items-center justify-center h-5 w-5 sm:h-6 sm:w-6 rounded-full bg-red-500 text-white text-xs font-bold shadow">
                                        {{ getNotificationCount(user.id) > 9 ? '9+' : getNotificationCount(user.id) }}
                                    </span>
                                </div>
                            </div>

                            <!-- Online Status Indicator -->
                            <div class="absolute top-2 left-2 sm:top-3 sm:left-3 z-10">
                                <div class="relative">
                                    <div v-if="onlineStatuses[user.id]"
                                        class="h-2.5 w-2.5 sm:h-3 sm:w-3 rounded-full bg-green-500 border-2 border-white dark:border-gray-800">
                                        <div
                                            class="absolute h-2.5 w-2.5 sm:h-3 sm:w-3 rounded-full bg-green-500 animate-ping">
                                        </div>
                                    </div>
                                    <div v-else
                                        class="h-2.5 w-2.5 sm:h-3 sm:w-3 rounded-full bg-gray-400 border-2 border-white dark:border-gray-800">
                                    </div>
                                </div>
                            </div>

                            <!-- User Avatar/Initials -->
                            <div class="flex items-center gap-3 sm:gap-4">
                                <div
                                    class="relative flex h-10 w-10 sm:h-12 sm:w-12 items-center justify-center rounded-full font-bold text-sm sm:text-base bg-blue-500 text-white flex-shrink-0">
                                    {{ getInitials(user.name) }}
                                </div>
                                <div class="flex-1 min-w-0">
                                    <div class="flex flex-col sm:flex-row sm:items-center gap-1 sm:gap-2">
                                        <h3
                                            class="font-semibold text-gray-900 dark:text-white truncate group-hover:text-blue-600 dark:group-hover:text-blue-400 transition-colors text-sm sm:text-base">
                                            {{ user.name }}
                                        </h3>
                                        <span v-if="onlineStatuses[user.id]"
                                            class="text-xs text-green-600 dark:text-green-400 font-medium whitespace-nowrap">
                                            • Online
                                        </span>
                                        <span v-else class="text-xs text-gray-500 dark:text-gray-400 whitespace-nowrap">
                                            • {{ getLastSeenTime(user) }}
                                        </span>
                                    </div>
                                    <p class="text-xs sm:text-sm text-gray-600 dark:text-gray-400 truncate mt-0.5">
                                        {{ user.email }}
                                    </p>
                                    <!-- Role Badge -->
                                    <span :class="[
                                        'inline-block mt-1.5 sm:mt-2 px-2 py-0.5 sm:px-3 sm:py-1 rounded-full text-xs font-semibold',
                                        user.role === 'admin'
                                            ? 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200'
                                            : 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200'
                                    ]">
                                        {{ user.role === 'admin' ? 'Admin' : 'User' }}
                                    </span>
                                </div>
                            </div>

                            <!-- User Details -->
                            <div class="mt-4 sm:mt-6 space-y-2 sm:space-y-3">
                                <div class="flex items-center justify-between text-xs sm:text-sm">
                                    <span class="text-gray-600 dark:text-gray-400">ID:</span>
                                    <span
                                        class="font-mono text-gray-900 dark:text-white bg-gray-100 dark:bg-gray-700 px-1.5 py-0.5 sm:px-2 sm:py-1 rounded text-xs">
                                        #{{ user.id }}
                                    </span>
                                </div>
                                <div v-if="user.last_seen_at && !onlineStatuses[user.id]"
                                    class="flex items-center justify-between text-xs sm:text-sm">
                                    <span class="text-gray-600 dark:text-gray-400">Last Seen:</span>
                                    <span class="text-gray-900 dark:text-white text-xs sm:text-sm">
                                        {{ getLastSeenTime(user) }}
                                    </span>
                                </div>
                            </div>

                            <!-- Action Buttons -->
                            <div class="mt-4 sm:mt-6">
                                <button @click="startChat(user.id)"
                                    class="w-full rounded-lg px-3 py-2.5 sm:px-4 sm:py-3 text-xs sm:text-sm font-semibold transition-all duration-300 hover:shadow-sm"
                                    :class="onlineStatuses[user.id]
                                        ? 'bg-blue-500 hover:bg-blue-600 text-white'
                                        : 'bg-blue-400 hover:bg-blue-500 text-white opacity-90'">
                                    {{ onlineStatuses[user.id] ? 'Chat Now' : 'Send Message' }}
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Empty State -->
                    <div v-if="filteredUsers.length === 0"
                        class="flex flex-col items-center justify-center rounded-lg border-2 border-dashed border-gray-300 dark:border-gray-600 bg-gray-50 dark:bg-gray-800/50 p-6 sm:p-8 md:p-12 text-center">
                        <div class="rounded-full bg-blue-50 dark:bg-blue-900/30 p-4 sm:p-6">
                            <svg class="h-8 w-8 sm:h-10 sm:w-10 md:h-12 md:w-12 text-blue-500 dark:text-blue-400"
                                fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z" />
                            </svg>
                        </div>
                        <h3 class="mt-4 sm:mt-6 text-lg sm:text-xl font-semibold text-gray-900 dark:text-white">
                            {{ isAdmin ? 'No users found' : 'No support staff available' }}
                        </h3>
                        <p class="mt-2 sm:mt-3 text-xs sm:text-sm text-gray-600 dark:text-gray-400 max-w-md px-2">
                            {{ isAdmin
                                ? 'Get started by inviting your first team member.'
                                : 'Please check back later or contact system administrator.'
                            }}
                        </p>
                    </div>
                </div>

                <!-- Footer Note -->
                <div class="mt-6 sm:mt-8 text-center">
                    <p class="text-xs text-gray-500 dark:text-gray-400">
                        <span class="text-blue-600 dark:text-blue-400 font-medium">BOA Chat</span> • Secure
                        communication powered by
                        <span class="text-gray-700 dark:text-gray-300 font-medium">Realpay Global Services</span>
                    </p>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<style scoped>
/* Add smooth transitions for better mobile experience */
* {
    transition-property: background-color, border-color, color, fill, stroke, opacity, box-shadow, transform;
    transition-timing-function: cubic-bezier(0.4, 0, 0.2, 1);
    transition-duration: 150ms;
}

/* Improve touch targets for mobile */
button,
[role="button"] {
    cursor: pointer;
    touch-action: manipulation;
}

/* Ensure text is readable on small screens */
@media (max-width: 640px) {
    .text-balance {
        text-wrap: balance;
    }
}

/* Prevent text overflow on mobile */
.truncate-mobile {
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

/* Improve card shadows on mobile */
@media (max-width: 640px) {
    .hover\:shadow-sm:hover {
        box-shadow: 0 1px 2px 0 rgb(0 0 0 / 0.05);
    }
}
</style>