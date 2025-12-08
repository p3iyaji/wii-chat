<script setup>
import { useNotifications } from '@/composables/useNotifications';
import { computed } from 'vue';

const { totalUnread, clearAllNotifications } = useNotifications();

const hasNotifications = computed(() => totalUnread.value > 0);

// Alternative: Use online notification sound
// const playNotificationSound = () => {
//     const audio = new Audio('https://assets.mixkit.co/sfx/preview/mixkit-correct-answer-tone-2870.mp3');
//     audio.volume = 0.3;
//     audio.play().catch(e => console.log('Audio play failed:', e));
// };
</script>

<template>
    <div v-if="hasNotifications" class="fixed top-4 right-4 z-50">

        <div class="relative group">
            <!-- Notification Bell with Badge -->
            <div class="relative p-2 bg-white rounded-full shadow-lg cursor-pointer hover:bg-gray-50 transition-colors">
                <svg class="w-6 h-6 text-gray-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                </svg>

                <!-- Badge -->
                <span
                    class="absolute -top-1 -right-1 flex h-5 w-5 items-center justify-center rounded-full bg-gradient-to-r from-red-500 to-pink-600 text-white text-xs font-bold shadow-lg">
                    {{ totalUnread > 9 ? '9+' : totalUnread }}
                </span>
            </div>

            <!-- Dropdown (optional) -->
            <div
                class="absolute right-0 mt-2 w-64 bg-white rounded-lg shadow-xl border border-gray-200 opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all duration-200">
                <div class="p-4">
                    <div class="flex items-center justify-between mb-3">
                        <h3 class="text-sm font-semibold text-gray-900">Notifications</h3>
                        <button @click="clearAllNotifications" class="text-xs text-blue-600 hover:text-blue-800">
                            Clear All
                        </button>
                    </div>
                    <p class="text-sm text-gray-600 text-center py-4">
                        {{ totalUnread }} unread message{{ totalUnread !== 1 ? 's' : '' }}
                    </p>
                    <p class="text-xs text-gray-500 text-center">
                        Go to Dashboard to see detailed notifications
                    </p>
                </div>
            </div>
        </div>
    </div>
</template>